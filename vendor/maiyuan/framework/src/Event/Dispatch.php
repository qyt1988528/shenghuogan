<?php
namespace MDK\Event;
use Phalcon\Di\Injectable;


class Dispatch extends Injectable
{
    public function beforeDispatch($event, $dispatcher) {
        $annotation = $this->di->getAnnotation(
            $dispatcher->getControllerClass(),
            $dispatcher->getActiveMethod()
        );
        if($before = $annotation->get('before')) {
            $before->handle();
        }
        $key = "{$dispatcher->getHandlerClass()}_{$dispatcher->getActiveMethod()}";
        $this->profiler->start($key, 'dispatch');
        return true;
    }

    public function beforeForward($event, $dispatcher, $forward) {
        $dispatcher->setModuleName($forward["module"]);
        $dispatcher->setNamespaceName($forward["namespace"]);
        return true;
    }
}