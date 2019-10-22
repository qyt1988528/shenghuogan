<?php
namespace MDK\Service\Annotation;
use Phalcon\Di\Injectable;

abstract class Abs extends Injectable
{
    protected $_annotation;

    public function __construct($annotation) {
        $this->_annotation = $annotation;
    }
}