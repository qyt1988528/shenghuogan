<?php
namespace MDK\Service;

use Phalcon\DiInterface;
use Phalcon\Di\ServiceProviderInterface;
use Phalcon\Mvc\Router\Annotations as RouterAnnotations;

class Router implements ServiceProviderInterface {

	public function register(DiInterface $di) {
		$router = new RouterAnnotations(true);
		$defaultModule = $di->getApplication()->getDefaultModule();
		$router->setDefaultModule($defaultModule);
		$router->setDefaultNamespace(ucfirst($defaultModule) . '\\Controller');
		$router->setDefaultController("Index");
		$router->setDefaultAction("index");
		$key = md5(__METHOD__);
		if(!($routes = $di->getSystemCache()->start($key))) {
			$modules = $di->getApplication()->getModules();
			foreach ($modules as $name => $module) {
				// Get all file names.
				$dirname = str_replace('\\', '/', $name);
				$dirname = strtolower($dirname);
				$files = glob($di->getDir()->app("{$dirname}/controller/*.php"));
				// Iterate files.
				foreach ($files as $file) {
					$controllerName = basename($file);
					if(strpos($controllerName, 'Controller.php') === false) {
						continue;
					}
					$controller = ucfirst($name) . '\\Controller\\' . str_replace('Controller.php', '', $controllerName);
					$routes[$name][] = $controller;
				}
			}
			$di->getSystemCache()->save($key, $routes);
		}
		foreach ($routes as $name => $controllers) {
			foreach ($controllers as $controller) {
				$router->addModuleResource($name, $controller);
			}
		}
		$di->set('router', $router);
		return $this;
	}
}