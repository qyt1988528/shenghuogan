<?php
namespace Maiyuan\Tool\Crypt;

class Aes
{
    protected $_key = "1234567891111117";

    protected $_iv = "0000000000000000";

    public function __construct() {
        if(!extension_loaded("openssl")) {
            throw new Exception('Aes encrypt failed, openssl module not loaded!');
        }
        if (!$this->_key) {
            throw new Exception('Aes encrypt failed, key not found!');
        }
        if (!$this->_iv) {
            throw new Exception('Aes encrypt failed, iv not found!');
        }
    }

    public function encode($data) {
        $data = base64_encode(openssl_encrypt($data, 'aes-128-cbc', $this->_key,   OPENSSL_RAW_DATA, $this->_iv));
        $data = str_replace(array('+'),array('%2B'), $data);
        return $data;
    }

    public function decode($data) {
        $data = str_replace(array('%2B'),array('+'), $data);
        $data = base64_decode($data);
        $data = openssl_decrypt($data, 'aes-128-cbc', $this->_key,   OPENSSL_RAW_DATA, $this->_iv);
        $data = rtrim($data, "\0\4");
        return $data;
    }
}