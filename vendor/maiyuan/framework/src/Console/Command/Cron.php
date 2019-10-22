<?php
namespace MDK\Console\Command;

use MDK\Console\AbstractCommand;
use MDK\Console\CommandInterface;
use MDK\Console\ConsoleUtil;
use MDK\Exception;
use \Curl\Curl;
use \Phalcon\DI;
use \Phalcon\Cache\Backend\File as BackendCache;
use \Phalcon\Cache\Frontend\Data as FrontendCache;

/**
 * Cron command.
 *
 * @CommandName(['cron'])
 * @CommandDescription('Cron management.')
 */
class Cron extends AbstractCommand implements CommandInterface
{

    public $cache;
    public $cacheSystem;

    /**
     * Cron data.
     *
     * @return void
     */
    public function runAction()
    {
        print "初始化...".PHP_EOL;
        $this->cache = new BackendCache(new FrontendCache(), ['prefix'=>'cron_', "cacheDir" => VAR_PATH . 'crontab' . DS]);
        $this->cacheSystem = $this->getDI()->get('cacheData');

        print "模型任务开始执行...".PHP_EOL;
        $modules = $this->getDI()->getConfig()->modules->toArray();
        array_unshift($modules, 'common');
        foreach($modules AS $module){
            print "[{$module}]".PHP_EOL;
            try{
                $result = $this->touch($module);
                print $result;
            }catch (Exception $e){
                print $e->getMessage();
                exit;
            }
        }
        print "模型任务执行完毕。".PHP_EOL;
    }

    public function touch($module = null){
        $module = ucfirst($module);
        $className = "\\$module\\Cron";
        if (class_exists($className)){
            $class = new $className();
            $class->_setDI($this->getDI());
        }else{
            return '        NO Cron'.PHP_EOL;
        }
        if (!isset($class->rule) || empty($class->rule)) return '       NO Rule'.PHP_EOL;
        date_default_timezone_set("PRC");

        foreach ($class->rule AS $rule) {
            $time = $rule[0];
            $name = trim($rule[1]);
            $cacheName = strtolower("{$module}_{$name}");
            $actionClass = $name . 'Action';
            if (!method_exists($class, $actionClass)){
                print '     ' . $name . ' not found'.PHP_EOL;
                continue;
            }
            if (gettype($time) == 'string') {
                $cacheName = $cacheName . '_' . str_replace(':', '', $time);
                $runDate = $this->cache->get($cacheName, 86400);
                if ($runDate == date('Ymd')){
                    print '     ' . $name . ' executed'.PHP_EOL;
                    continue;
                }
                $nowTime = time();
                $runTime = strtotime(date('Y-m-d ') . $rule[0]);
                $lastTime = $runTime + 3600;
                if ($nowTime >= $runTime && $nowTime < $lastTime) {
                    $class->$actionClass();
                    $this->cache->save($cacheName, date('Ymd'), 86400);
                    print '     ' . $name . ' success'.PHP_EOL;
                }else{
                    print '     ' . $name . ' timeout'.PHP_EOL;
                }
            } else {
                $time = floatval($time);
                $cache = $this->cache->exists($cacheName, $time);
                if (!$cache) {
                    $class->$actionClass();
                    $this->cache->save($cacheName, ['lasttime' => date('Y-m-d H:i:s')], $time);
                    print '     ' . $name . ' success'.PHP_EOL;
                }else{
                    print '     ' . $name . ' timeout'.PHP_EOL;
                }
            }
        }
        return '';
    }

}