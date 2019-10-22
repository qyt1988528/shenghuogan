<?php
namespace Maiyuan\Service;
use Phalcon\Cache\Frontend\Json as Frontend;
use Phalcon\Cache\Backend\Redis;
use Maiyuan\Service\Exception as Exception;
abstract class Task extends Redis
{
    protected $_id;

    public function __construct($id, array $options) {
        $this->_id = $id;
        $frontend = new Frontend([
            'lifetime' => 0
        ]);
        $path = explode('\\', get_called_class());
        $this->_prefix = $this->_id . ":" . strtolower(end($path)) . ":";
        if(!isset($options['index'])) {
            $options['index'] = 15;
        }
        parent::__construct($frontend, $options);
    }

    /**
     * validate data
     * @param $data
     * @return $this
     */
    protected function _convert($data) {
        if(!$data) {
            throw new Exception("Task data is required.");
        }
        return $data;
    }

    protected function _getServerInfo() {
        $args = isset($_SERVER) ? $_SERVER : [];
        foreach ($args as $key => $arg) {
            if(!preg_match('/HTTP_|SERVER_|REQUEST_*/', $key)) {
                unset($args[$key]);
            }
        }
        return $args;
    }
}