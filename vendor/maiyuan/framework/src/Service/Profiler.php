<?php
namespace MDK\Service;
use Phalcon\DiInterface;
use Phalcon\Di\ServiceProviderInterface;
class Profiler implements ServiceProviderInterface{

    public function register(DiInterface $di) {
        $di->setShared('profiler', function() {
            return new class($this) {

                protected $_data = [];

                protected $_enabled;

                public function __construct($di) {
                    $this->_enabled = (bool)$di->getConfig()->system->profiler;
                }

                public function start($name, $group = 'default') {
                    if($this->_enabled) {
                        $this->_data[$group][md5($name)] = [
                            'key' => $name,
                            'time' => microtime(true),
                            'memory' => memory_get_usage(),
                            'stop' => false
                        ];
                    }
                    return $this;
                }

                public function stop($name, $group = 'default') {
                    if($this->_enabled) {
                        $data = &$this->_data[$group][md5($name)];
                        $data['time'] = (microtime(true) - $data['time']) * 1000 . 'ms';
                        $data['memory'] = (memory_get_usage() - (isset($data['memory'])?$data['memory']:0)) / 1024 . 'k';
                        $data['stop'] = true;
                    }
                    return $this;
                }

                public function getData($group = null) {
                    return isset($this->_data[$group]) && $this->_enabled ? $this->_data[$group] : $this->_data;
                }
            };
        });
        return $this;
    }
}