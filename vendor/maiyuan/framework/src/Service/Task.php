<?php
namespace MDK\Service;
use Phalcon\Di;
use Phalcon\DiInterface;
use Phalcon\Di\ServiceProviderInterface;
use MDK\Exception as MDKException;

/**
 * Class Task
 * @package MDK\Service
 */
class Task implements ServiceProviderInterface
{
    public function register(DiInterface $di) {
        $di->setShared('task', function(){
            return new class($this) {
                protected $_instances = [];

                public function __construct($di) {
                    $this->options = [
                        'host' => '113.6.252.23',
                        'port' => '9637',
                        'auth' => 'maiyuan123',
                        'index' => '15',
                    ];
                    $this->id = explode(DIRECTORY_SEPARATOR, $di->getDir()->root());
                    $this->id = end($this->id);
                }

                public function __get($name) {
                    $class = "Maiyuan\Service\Task\\" . ucfirst($name);
                    if(!class_exists($class)) {
                        throw new MDKException("service {$class} not found.");
                    }
                    if(!isset($this->_instances[$class])) {
                        $this->_instances[$class] = new $class($this->id, $this->options);
                    }
                    return $this->_instances[$class];
                }
            };
        });
        return $this;
    }
}
