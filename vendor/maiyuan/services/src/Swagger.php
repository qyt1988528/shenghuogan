<?php
namespace Maiyuan\Service;

use Maiyuan\Service\Exception as Exception;

class Swagger
{
    public function __construct($path, array $options = []) {
        if(!is_dir($path)) {
            throw new Exception('Swagger options path need dir path.');
        }
        \Swagger\Annotations\Swagger::$_required = ['swagger', 'paths'];
        $this->swagger = \Swagger\scan($path);
        array_walk($options, function($value, $key) {
            $this->swagger->{$key} = $value;
        });
    }

    public function __toString() {
        return (string)$this->swagger;
    }
}