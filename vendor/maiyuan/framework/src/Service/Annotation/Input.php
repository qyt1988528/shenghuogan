<?php
namespace MDK\Service\Annotation;
use MDK\Exception;
use Phalcon\Validation;

class Input extends Abs
{

    protected $_validation;

    protected $_error = 'throw';

    public function validate($data) {
        $arguments = $this->_annotation->getArguments();
        if (isset($arguments['error'])){
            $this->_error = $arguments['error'];
        }
        if (is_array($arguments['rules'])){
            $this->_validation = new Validation();
            $result = $this->_validate(
                $data,
                $arguments['rules']
            );
            if ($result === true){
                $messages = $this->_validation->validate($data);
                if (!empty($messages)){
                    foreach($messages AS $message){
                        if ($this->_error == 'request'){
                            $this->request->setError([
                                'type'=>'validate',
                                'column'=>$message->getField(),
                                'message'=>$message->getMessage()
                            ]);
                        }else{
                            throw new \Phalcon\Annotations\Exception("Validate[".$message->getField()."]".$message->getMessage());
                        }
                    }
                }
            }
        }
        return true;
    }

    //validate input params
    private function _validate($inputValue, $arguments) {
        foreach ($arguments as $key => $ruleValue) {
            if (is_int($key)){
                $key = $ruleValue;
            }
            if (!isset($inputValue[$key])){
                if ($this->_error == 'request'){
                    $this->request->setError([
                        'type'=>'data',
                        'column'=>$key,
                        'message'=>'Field undefined'
                    ]);
                }else{
                    throw new \Phalcon\Annotations\Exception("Data[{$key}]Field undefined");
                }
                return false;
            }
            if(is_array($ruleValue)) {
                return $this->_validate($inputValue[$key], $ruleValue);
            }else if ($ruleValue != ''){
                if (is_object($ruleValue)){
                    $verify = $this->_verify($key, $ruleValue);
                    if (!$verify){
                        if ($this->_error == 'request'){
                            $this->request->setError([
                                'type'=>'data',
                                'column'=>$key,
                                'message'=>'Field validation failure'
                            ]);
                        }else{
                            throw new \Phalcon\Annotations\Exception("Data[{$key}]Field validation failure");
                        }
                        return false;
                    }
                }
            }
        }
        return true;
    }

    protected function _verify($key, $rule = []) {
        $validator = '\Phalcon\Validation\Validator\\'.ucfirst($rule->getName());
        if (!class_exists($validator)){
            if ($this->_error == 'request'){
                $this->request->setError([
                    'type'=>'validate',
                    'column'=>$key,
                    'message'=>'The validator does not exist'
                ]);
            }else{
                throw new \Phalcon\Annotations\Exception("Validate[{$key}]The validator does not exist");
            }
            return false;
        }
        $this->_validation->add($key, new $validator($rule->getArguments()));
        return true;
    }
}