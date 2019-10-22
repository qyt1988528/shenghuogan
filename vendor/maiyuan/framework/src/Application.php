<?php
namespace MDK;
use Phalcon\DI\FactoryDefault;
use Phalcon\Mvc\Application as PhalconApplication;
use Phalcon\Config as Config;
use Phalcon\Loader;
use Phalcon\Mvc\Model\MetaData\Strategy\Annotations as StrategyAnnotations;

abstract class Application extends PhalconApplication
{

	protected $_bootstrap = [
		'dir',
		'cache',
		'module',
		'config',
		'service',
		'event'
	];

	protected $_service = [
		'profiler',
		'annotation',
		'response',
		'debug',
		'registry',
		'logger',
		'url',
		'router',
		'database',
		'session',
		'flash',
		'filter',
		'crypt',
		'request',
		'view',
		'vendor',
		'translate',
		'validation',
		'app',
		'task'
	];

	protected $_moduleSpace = [
		'Controller' => 'controller',
		'Model' => 'model',
		'Command' => 'command',
		'Api' => 'api',
		'Event' => 'event',
		'Service' => 'service',
	];

	//Constructor.
	public function __construct() {
		parent::__construct(new FactoryDefault());

		$this->setDefaultModule('Core');

		$this->di->setShared('application', $this);
		foreach ($this->_bootstrap as $bootstrap) {
			$this->{'_init' . ucfirst($bootstrap)}();
		}
	}

	//init dirs
	protected function _initDir() {
		$this->di->setShared('dir', new Class() {

			public function root($path = []) {
				$_root = dirname(dirname(dirname(dirname(__DIR__))));
				if(!is_dir($_root)) {
					throw new \Exception("The application boot needs to set up the root path.");
				}
				if (!is_array($path)){
					$path = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path);
					return $_root . DIRECTORY_SEPARATOR . $path;
				}else if (!empty($path)){
					$path = implode(DIRECTORY_SEPARATOR, $path);
					$path = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path);
					return $_root . DIRECTORY_SEPARATOR . $path;
				}
				return $_root;
			}

			public function app($path = null)
			{
				return $this->root(['app', $path]);
			}

			public function config($path = null)
			{
				return $this->root(['config', $path]);
			}

			public function library($path = null)
			{
				return $this->root(['library', $path]);
			}

			public function public($path = null)
			{
				return $this->root(['public', $path]);
			}

			public function var($path = null) {
				$dir = $this->root(['var', $path]);
				if (!is_dir($dir)) {
					@mkdir($dir, 0777, true);
				}
				return $dir;
			}

		});
		return $this;
	}

	protected function _initCache() {
		$configs = require_once $this->dir->config('cache.php');
		if(isset($configs['annotations'])) {
			$config = $configs['annotations'];
			unset($configs['annotations']);
			if(!$config['enabled']) {
				$config['backend'] = $config["memory"];
			}
			$object = new $config['backend']($config);
			$this->di->set("annotations", $object);
		}

		if(isset($configs['modelsMetadata'])) {
			$config = $configs['modelsMetadata'];
			unset($configs['modelsMetadata']);
			if(!$config['enabled']) {
				$config['backend'] = $config["memory"];
			}
			$metadata = new $config['backend']($config);
			$metadata->setStrategy(new StrategyAnnotations());
			$this->di->setShared("modelsMetadata", $metadata);
		}

		foreach ($configs as $name => $config) {
			if(!$config['enabled']) {
				$config['backend'] = $config["memory"];
			}
			$frontend = new $config['frontend']([
				'lifetime' => $config['lifetime']
			]);
			$backend = new $config['backend']($frontend, $config);
			$this->di->setShared($name, $backend);
		};
		return $this;
	}

	//init configs
	protected function _initConfig() {
		$key = md5(__METHOD__);
		if(!($configs = $this->systemCache->start($key))) {
			//system configs
			$files = array_diff(glob($this->dir->config('*.php')), ['cache.php']);
			if ($files) {
				foreach ($files as $file) {
					$name = strtolower(basename($file, '.php'));
					$configs[$name] = include_once $file;
				}
			}
			$this->systemCache->save($key, $configs);
		}
		$config = new Config($configs);
		if ($config->system->debug) {
			error_reporting(E_ALL);
			ini_set('display_errors', 1);
		}else{
			ini_set('display_errors', 0);
		}
		$this->di->setShared('config', $config);
		return $this;
	}

	/**
	 * support multiple modules
	 * @param $dirs
	 * @param $modules
	 * @return mixed
	 */
	private function _getMultipleModules($dirs,$modules)
	{
		foreach ($dirs as $dir) {

			$secondDirs = array_diff(scandir($this->dir->app().'/'.$dir), ['.', '..']);

			if(in_array('controller',$secondDirs)){
				$name = str_replace(DIRECTORY_SEPARATOR,'\\',$dir);
				$name = ucwords($name,'\\');
				$modules[$name] = [
					"className" => $name . "\Bootstrap"
				];
			}else{
				foreach ($secondDirs as &$secondDir)
				{
					$secondDir = $dir.DIRECTORY_SEPARATOR.$secondDir;
				}
				$modules = $this->_getMultipleModules($secondDirs,$modules);
			}
		}
		return $modules;
	}

	//init register
	protected function _initModule() {
		$key = md5(__METHOD__);
		if(!($modules = $this->systemCache->start($key))) {
			$dirs = array_diff(scandir($this->dir->app()), ['.', '..']);
			if (!$dirs) {
				throw new \Exception('System module not found');
			}
			$modules = [];
			$modules = $this->_getMultipleModules($dirs,$modules);
			$this->systemCache->save($key, $modules);
		}
		foreach ($modules as $moduleName => $module) {
			$namespaces[ucfirst($moduleName)] = $this->dir->app(strtolower($moduleName));
			foreach($this->_moduleSpace AS $name => $path){
				$namespaces[ucfirst($moduleName) . '\\' . $name] = $this->dir->app(strtolower($moduleName) . '/' . $path);
			}
			$this->_initFunction(strtolower($moduleName));
		}
		$this->registerModules($modules);
		$loader = new Loader();

		$loader->registerNamespaces($namespaces, true);
		$loader->register();
		$this->di->setShared('loader', $loader);
		return $this;
	}

	protected function _initFunction($module = '')
	{
		$path = $this->dir->app($module. '\Function.php');
		if (!is_file($path)){
			return false;
		}
		
		include_once $path;
	}

	//initialize Services
	protected function _initService() {
		foreach ($this->_service as $name) {
			$class = 'MDK\Service\\' . ucfirst($name);
			if (!class_exists($class)){
				continue;
			}
			$this->di->register(new $class());

		}
		return $this;
	}

	//init event manager
	protected function _initEvent() {
		$eventsManager = $this->eventsManager;

		$this->setEventsManager($eventsManager);
		$this->dispatcher->setEventsManager($eventsManager);
		$this->modelsManager->setEventsManager($eventsManager);
		$this->db->setEventsManager($eventsManager);
		$this->view->setEventsManager($eventsManager);

		$eventsManager->attach("application", new Event\Application());
		$eventsManager->attach('dispatch', new Event\Dispatch());
		$eventsManager->attach('modelsManager', new Event\ModelsManager());
		$eventsManager->attach('db', new Event\Database());
		$eventsManager->attach("view", new Event\View());

		$modules = $this->getModules();
		foreach ($modules as $name => $module) {
			$class = ucfirst($name) . "\Event\Dispatch";
			if(class_exists($class)) {
				$eventsManager->attach('dispatch', new $class());
			}
		}
	}

	abstract public function run();

}