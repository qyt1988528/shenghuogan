<?php
namespace MDK\Service;

use Phalcon\DiInterface;
use Phalcon\Di\ServiceProviderInterface;
use Phalcon\Registry as PhalconRegistry;

class Registry implements ServiceProviderInterface{

    public function register(DiInterface $di) {
        $registry = new PhalconRegistry();
        $di->set('registry', $registry);
        return $this;

    }

}