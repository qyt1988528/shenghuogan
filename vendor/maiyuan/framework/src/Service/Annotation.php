<?php
namespace MDK\Service;
use Phalcon\DiInterface;
use Phalcon\Di\ServiceProviderInterface;
use Phalcon\Di\Injectable;
class Annotation implements ServiceProviderInterface
{
    protected $_instance = [];

    public function register(DiInterface $di) {
        $di->set('annotation', function ($class, $method) {
            $key = md5($class . $method);
            if(!isset($this->_instance[$key])) {
                $object = new class($class, $method) extends Injectable{

                    protected $_annotations = [];

                    public function __construct($class, $method) {
                        if(class_exists($class)) {
                            $annotations = $this->annotations->get($class);
                            if($classAnnotations = $annotations->getClassAnnotations()) {
                                $classAnnotations = $classAnnotations->getAnnotations();
                                $this->_annotations = array_merge($this->_annotations, $classAnnotations);
                            }
                            if($methodsAnnotations = $annotations->getMethodsAnnotations()) {
                                if(isset($methodsAnnotations[$method])) {
                                    $methodsAnnotations = $methodsAnnotations[$method];
                                    $methodsAnnotations = $methodsAnnotations->getAnnotations();
                                    $this->_annotations = array_merge($this->_annotations, $methodsAnnotations);
                                }
                            }
                        }
                    }

                    public function get($name) {
                        foreach ($this->_annotations as $annotation) {

                            if($name == strtolower($annotation->getName())) {
                                $class = "MDK\Service\Annotation\\" . ucfirst($name);
                                if(class_exists($class)) {
                                    return new $class($annotation);
                                }
                            }
                        }
                        return false;
                    }
                };
                $this->_instance[$key] = $object;
            }
            return $this->_instance[$key];
        });
        return $this;
    }
}