<?php
namespace MDK\Service\Annotation;
use Phalcon\Annotations\Exception as AnnotationsException;
class Before extends Abs
{
    public function handle() {
        $arguments = $this->_annotation->getArguments();
        $module = isset($arguments['module']) ? $arguments['module'] : null;
        $class = isset($arguments['class']) ? ucfirst($arguments['class']) : null;
        $method = isset($arguments['method']) ? $arguments['method'] : null;
        if (!isset($module, $class, $method)) {
            throw new AnnotationsException("Before annotation need @Before(module=?, class=?, method=?)");
        }
        $returnedValue = $this->app->{$module}->api->{$class}()->{$method}();
        if($returnedValue === true) {
            return true;
        }
        if(!$returnedValue) {
            throw new AnnotationsException("Before api ({$module}, {$class}, {$method}) returned value not allow false.");
        }
        $this->response->success($returnedValue);
    }
}