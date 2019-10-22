<?php
namespace MDK\Service;

use Phalcon\DiInterface;
use Phalcon\Di\ServiceProviderInterface;

/**
 * Vendor 统一调度器
 * 调用方法：
 *  $this->vendor->tool->crypt->auth(); //调取工具的方法，工具路径：vendor/maiyuan/tools/src/Crypt/Auth.php
 *  $this->vendor->service->magento();  //调取服务的方法，服务路径：vendor/maiyuan/services/src/Magento/Bootstrap.php
 *  $this->vendor->google->ga->autoload();  //调取三方插件，插件路径：vendor/google/ga/src/autoload.php
 */
class Vendor implements ServiceProviderInterface{

    public function register(DiInterface $di) {
        $di->setShared('vendor', function(){
            return new class{

                protected $_namespace;

                public function __construct($namespace = null)
                {
                    $this->_namespace = $namespace;
                }

                public function __get($adapter) {
                    $adapter = "{$this->_namespace}\\" . ucfirst($adapter);
                    return new self($adapter);
                }

                public function __call($name, array $arguments)
                {
                    $class = $this->_namespace . '\\' . ucfirst($name);
                    $model = current(explode('\\', trim($class, '\\')));
                    if (in_array($model, ['Tool', 'Service'])){
                        $class = "\Maiyuan{$class}";
                        if ($model == 'Service'){
                            $class .= "\Bootstrap";
                        }
                    }
                    if(!class_exists($class)) {
                        throw new \Exception("No vendor adapter was found: {$class}");
                    }
                    $reflection = new \ReflectionClass($class);
                    if ($model == 'Service' && !($reflection->isSubclassOf('\Maiyuan\Service\Di') || $reflection->isSubclassOf('\Maiyuan\Service\Standard'))){
                        throw new \Exception("Vendor {$class} must inherit Service or DI.");
                    }
                    return $reflection->newInstance(...$arguments);
                }
            };
        });
        return $this;
    }

}