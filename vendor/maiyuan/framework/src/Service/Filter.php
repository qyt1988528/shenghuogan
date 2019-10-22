<?php
namespace MDK\Service;
use Phalcon\DiInterface;
use Phalcon\Di\ServiceProviderInterface;
use Phalcon\Filter as PhalconFilter;
class Filter implements ServiceProviderInterface
{
    public function register(DiInterface $di) {
        $filter = new PhalconFilter();
        $di->setShared('filter', $filter);
        return $this;
    }
}