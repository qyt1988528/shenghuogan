<?php
namespace MDK\Service;

use Phalcon\DiInterface;
use Phalcon\Di\ServiceProviderInterface;
use Phalcon\Http\Response as PhalconResponse;

class Response implements ServiceProviderInterface
{
    public function register(DiInterface $di) {
        $di->setShared('response', function(){
            return new class extends PhalconResponse {

                protected function _send($data) {
                    $di = $this->getDI();
                    $dispatcher = $di->getDispatcher();
                    $profiler = $di->getProfiler();
                    $key = "{$dispatcher->getHandlerClass()}_{$dispatcher->getActiveMethod()}";
                    $profiler->stop($key, 'dispatch');
                    if($profilerData = $profiler->getData()) {
                        $data->profiler = $profilerData;
                    }
                    $this->setJsonContent($data)
                         ->setStatusCode(200, "OK")
                         ->send();
                    exit;
                }
                public function error($message, $code = 1) {
                    $data = [
                        'error' => 1,
                        'code' => $code,
                        'response' => $message
                    ];
                    $this->_send($data);
                }

                public function failed($message, $code = 1) {
                    $data = [
                        'error' => 0,
                        'code' => $code,
                        'response' => $message
                    ];
                    $this->_send($data);
                }

                public function success( $data, $code = 0) {
//                    $di = $this->getDI();
//                    $dispatcher = $di->getDispatcher();
//                    $annotation = $di->getAnnotation(
//                        $dispatcher->getControllerClass(),
//                        $dispatcher->getActiveMethod()
//                    );
//                    if($ouput = $annotation->get('output')) {
//                        $data = $ouput->filter($data);
//                    }
//                    if($cache = $annotation->get('cache')) {
//                        $cache->save($data);
//                    }
//                    $data = [
//                        'error' => 0,
//                        'code' => $code,
//                        'response' => $data
//                    ];
                    $this->_send($data);
                }
            };
        });
        return $this;
    }
}