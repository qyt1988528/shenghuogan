<?php
namespace MDK\Service;

use MDK\MObject;
use Phalcon\DiInterface;
use Phalcon\Di\ServiceProviderInterface;
use Phalcon\Http\Request as PhalconRequest;

class Request implements ServiceProviderInterface
{
    public function register(DiInterface $di) {
        $di->setShared('request', function(){
            return new class extends PhalconRequest {

                protected $_params;

                protected $_errorMessage = [];

                public function __construct() {
                    $get = $this->getQuery();
                    $post = $this->getPost();
                    $body = $this->getJsonRawBody(true) ?: [];
                    $params = array_merge_recursive($get, $post, $body);
                    if($headerRawBODY = $this->getHeader('RAW_BODY')) {
                        $headerRawBODY = json_decode(base64_decode($headerRawBODY), true);
                        if(is_array($headerRawBODY)) {
                            $params = array_merge_recursive($params, $headerRawBODY);
                        }
                    }
                    $this->setParams($params);
                }

                public function getParams() {
                    return $this->_params;
                }

                /**
                 * @param array $params
                 * @return $this
                 */
                public function setParams(array $params) {
                    $this->_params = new MObject($params);
                    return $this;
                }

                /**
                 * @param array $params
                 * @return $this
                 */
                public function addParams(array $params) {
                    foreach ($params as $key => $value) {
                        $this->setParam($key, $value);
                    }
                    return $this;
                }

                /**
                 * Set an action parameter
                 * A $value of null will unset the $key if it exists
                 * @param string $key
                 * @param mixed $value
                 * @return Zend_Controller_Request_Abstract
                 */
                public function setParam($key, $value) {
                    $key = (string)$key;
                    if (null === $value) {
                        unset($this->_params->{$key});
                    }else{
                        $this->_params->{$key} = $value;
                    }
                    return $this;
                }

                /**
                 * Get an action parameter
                 * @param $key
                 * @param null $filters
                 * @param null $defaultValue
                 * @param bool $notAllowEmpty
                 * @param bool $noRecursive
                 * @return mixed
                 * @internal param $name
                 * @internal param string $key
                 * @internal param mixed $default Default value to use if key not found
                 */
                public function getParam($key, $filters = null, $defaultValue = null,
                                         $notAllowEmpty = false, $noRecursive = false) {
                    return $this->getHelper($this->_params->toArray(), $key, $filters, $defaultValue, $notAllowEmpty, $noRecursive);
                }


                public function isError(){
                    return empty($this->_errorMessage) ? false : true;
                }

                public function setError($message = []){
                    $this->_errorMessage[] = $message;
                    return $this;
                }

                public function getError(){
                    return $this->_errorMessage;
                }

            };
        });
        return $this;
    }
}