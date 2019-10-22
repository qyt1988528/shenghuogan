<?php
namespace MDK\Service;
use Phalcon\DiInterface;
use Phalcon\Di\ServiceProviderInterface;
use Phalcon\Mvc\View as PhalconView;

class View implements ServiceProviderInterface {
    public function register(DiInterface $di) {
        $di->setShared( 'view', function () {
            $view = new class() extends PhalconView {
                public function render($controllerName, $actionName, $params = null) {
                    $di = $this->getDI();
                    $annotation = $di->getAnnotation(
                        $di->getDispatcher()->getControllerClass(),
                        $di->getDispatcher()->getActiveMethod()
                    );
                    if($view = $annotation->get('view')) {
                        $view->enabled();
                    }
                    if($cache = $annotation->get('cache')) {
                        $cache->saveView();
                    }
                    return parent::render($controllerName, $actionName, $params);
                }
            };
            $view->registerEngines([
                '.phtml' => '\Phalcon\Mvc\View\Engine\Php'
            ]);
            $view->disable();
            return $view;
        });
        return $this;
    }
}