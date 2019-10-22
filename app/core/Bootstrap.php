<?php
namespace Core;
use MDK\Bootstrap as MDKBootstrap;
use Phalcon\Di\Injectable;
use Core\Service\Store;
/**
 * Common Bootstrap.
 */
class Bootstrap extends MDKBootstrap
{
    /**
     * Register the services.
     * @throws Exception
     */
    public function registerServices() {
        $this->di->setShared('store', new class {

        });$this->di->setShared('store', new class {

        });$this->di->setShared('store', new class {

        });$this->di->setShared('store', new class {

        });$this->di->setShared('store', new class {

        });$this->di->setShared('store', new class {

        });
        return $this;
    }
}