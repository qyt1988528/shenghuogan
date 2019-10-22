<?php
namespace MDK\Service;

use Phalcon\DiInterface;
use Phalcon\Di\ServiceProviderInterface;
use Phalcon\Mvc\Model\Transaction\Manager as TransactionManager;
class Database implements ServiceProviderInterface{

    public function register(DiInterface $di) {
        $config = (array)$di->getConfig()->database;
        $adapter = '\Phalcon\Db\Adapter\Pdo\\' . ucfirst($config['adapter']);
        unset($config['adapter']);
        $di->setShared('db', new $adapter($config));
        $di->setShared('transactions', TransactionManager::class);
        return $this;
    }
}
