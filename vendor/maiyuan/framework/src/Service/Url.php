<?php
namespace MDK\Service;

use Phalcon\DiInterface;
use Phalcon\Di\ServiceProviderInterface;
use Phalcon\Mvc\Url as PhalconUrl;

class Url implements ServiceProviderInterface{

    public function register(DiInterface $di) {
        $url = new PhalconUrl();
        $url->setBaseUri($di->getConfig()->system->baseUrl);
        $di->setShared('url', $url);
        return $this;

    }

}