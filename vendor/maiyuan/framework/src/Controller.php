<?php
namespace MDK;
use Phalcon\Mvc\Controller as PhalconController;
class Controller extends PhalconController
{
    protected function _success($message, $code = 1) {
        $this->response->success($message, $code);
    }
    protected function _failed($message, $code = 1) {
        $this->response->failed($message, $code);
    }
    protected function _error(array $data, $code = 0) {
        $this->response->error($data, $code);
    }
}