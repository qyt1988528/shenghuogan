<?php
namespace MDK\Event;
use Phalcon\Di\Injectable;
class Application extends Injectable
{
    //request send cache
    public function beforeHandleRequest($event, $application) {
        $annotation = $this->di->getAnnotation(
            $this->di->getDispatcher()->getControllerClass(),
            $this->di->getDispatcher()->getActiveMethod()
        );
        $params = $this->request->getParams()->toArray();
        $input = $annotation->get('input');
        if($input){
            return $input->validate($params);
        }
        return true;
    }
}