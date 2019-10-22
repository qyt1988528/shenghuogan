<?php
namespace MDK\Service\Annotation;
class View extends Abs
{
    //view render enable
    public function enabled() {
        $this->view->enable();
        $this->view->setViewsDir($this->dir->app("{$this->dispatcher->getModuleName()}/view"));
        if($template = $this->_annotation->getNamedParameter('template')) {
            $this->view->pick($template);
        }
        return true;
    }
}