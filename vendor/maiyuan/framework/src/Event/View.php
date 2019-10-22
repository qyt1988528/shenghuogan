<?php
namespace MDK\Event;

use Phalcon\Di\Injectable;

class View extends Injectable
{

    /**
     * Before view render
     * @param Event object.
     * @param View object.
     * @return $this
     */
    public function beforeRender($event, $view) {
        $this->profiler->start($view->getActiveRenderPath(), 'view');
        return true;
    }

    //after render
    public function afterRender($event, $view, $file){
        $this->profiler->stop($view->getActiveRenderPath(), 'view');
        return true;
    }

    //not found view
    public function notFoundView($event, $view, $file) {
        //throw new \Exception("View not found - {$file}");
        return true;
    }
}