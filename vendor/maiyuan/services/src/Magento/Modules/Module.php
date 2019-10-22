<?php
namespace Maiyuan\Service\Magento\Modules;
use Phalcon\Di\Injectable;
abstract class Module extends Injectable
{

    public function __construct() {
        if(!isset($this->_path)) {
            $path = explode('\\', get_called_class());
            $this->_path = strtolower(array_pop($path));
        }
    }

}