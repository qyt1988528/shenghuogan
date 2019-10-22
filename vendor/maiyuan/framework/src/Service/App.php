<?php
namespace MDK\Service;

use Phalcon\DiInterface;
use Phalcon\Di\ServiceProviderInterface;
use MDK\Exception as MDKException;
use Phalcon\Config as Config;
use Phalcon\Di;

class App implements ServiceProviderInterface
{
    public function register(DiInterface $di) {
        $di->setShared('app', function() {
            return new class($this) {

                protected $_namespace;

                protected $_instance = [];

                public function __construct($di) {
                    $this->di = $di;
                }

                public function __get($name) {
                    $class = $this->_namespace . "\\" . ucfirst($name);
                    $names = explode('\\', $class);
                    if (!is_file(strtolower($this->di->getDir()->app("{$class}.php")))) {
                        $this->_namespace = $class;
                        return $this;
                    }
                    $this->_namespace = null;
                    $mode = $names[2];
                    switch (strtolower($mode)) {
                        case 'config' :
                            if(!isset($this->_instance[$class])) {
                                $file = strtolower($this->di->getDir()->app("{$class}.php"));
                                $options = is_file($file) ? (include_once $file ): [];
                                $this->_instance[$class] = new Config($options);
                            }
                            return $this->_instance[$class];
                            break;
                    }
                    throw new MDKException("The app service get exception {$class} error");
                }

                public function __call($name, array $arguments ) {
                    $class = $this->_namespace . "\\" . ucfirst($name);
                    $names = explode('\\', $class);
                    if (!class_exists($class)) {
                        $this->_namespace = $class;
                        return $this;
                    }
                    $this->_namespace = null;
                    $mode = $names[2];

                            if(!isset($this->_instance[$class])) {
                                $arguments = array_shift($arguments);
                                $arguments = is_array($arguments) ? $arguments : [];
                                if($arguments) {
                                    $keys = array_keys($arguments);
                                    if($keys == array_keys($keys)) {
                                        throw new MDKException('Api & Service construct arguments need key => value.');
                                    }
                                }
                                $object = new $class($arguments);
                                foreach ($arguments as $key => $value) {
                                    if(is_string($key)) {
                                        $object->{$key} = $value;
                                    }
                                }
                                $this->_instance[$class] = $object;
                            }
                            return $this->_instance[$class];
                            

                    throw new MDKException("The app service call exception {$class} error");
                }
            };
        });
        return $this;
    }
}