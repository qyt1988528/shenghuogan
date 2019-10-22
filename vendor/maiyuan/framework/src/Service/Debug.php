<?php
namespace MDK\Service;

use Phalcon\DiInterface;
use Phalcon\Di\ServiceProviderInterface;
use Phalcon\Debug as PhalconDebug;
use MDK\Exception\PrettyExceptions;
use MDK\Exception as CoreException;

class Debug implements ServiceProviderInterface {
    public function register(DiInterface $di) {

        $config = $di->getConfig()->get('system');
        if ($config->debug === 'full'){
            $debug = new PhalconDebug();
            $debug->listen();
            return $this;
        }
        set_exception_handler(
            function ($e) use ($config, $di) {
                $resultSet = new \Core\Service\ResultSet();
                $logMessage = $resultSet->parseException($e);

                if($config->debug === 'simple') {
                    $p = new PrettyExceptions($di);
                    return $p->handleException($e);
                }elseif ($config->debug){
                    throw $e;
                }else{
                    if($e instanceof \Phalcon\Annotations\Exception){
                        $resultSet->error('1001',$e->getMessage());
                    }elseif($e instanceof \Maiyuan\Service\Exception  || $e instanceof \PDOException){
                        $resultSet->logError($logMessage);
                        $resultSet->error('5000','intenal error');
                    }else{
                        $resultSet->logError($logMessage);
                        $resultSet->error('5000',$e->getMessage());
                    }
                }
                //已经不会走到这里
                $errorId = CoreException::logException($e);
                if ($config->isConsole) {
                    echo 'Error <' . $errorId . '>: ' . $e->getMessage();
                }else{
                    $di->getResponse()->error(
                        "{$errorId} : {$e->getMessage()}",
                        $e->getCode()
                    );
                }
                return true;
            }
        );
        return $this;
    }
}