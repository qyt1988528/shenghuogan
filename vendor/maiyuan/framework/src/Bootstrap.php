<?php

namespace MDK;

use Phalcon\Mvc\User\Module;

/**
 * Bootstrap class.
 */
abstract class Bootstrap extends Module
{

    /**
     * Registers an autoloader related to the module
     */
    public function registerAutoloaders() {
        return $this;
    }

    /**
     * Register the services.
     * @throws Exception
     */
    public function registerServices() {
        return $this;
    }

    /**
     * Get current module directory.
     *
     * @return string
     */
    public function getModuleDirectory() {
        return $this->dir->app($this->getModuleName());
    }

    /**
     * Get current module name.
     * @return string
     */
    public function getModuleName() {
        $path = explode('\\', get_called_class());
        $name = strtolower(array_shift($path));
        if (!$name) {
            throw new Exception('Bootstrap has no module name: ' . get_called_class());
        }
        return $name;
    }
}