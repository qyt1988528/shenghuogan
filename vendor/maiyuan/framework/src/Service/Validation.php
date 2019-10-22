<?php
namespace MDK\Service;

use Phalcon\DiInterface;
use Phalcon\Di\ServiceProviderInterface;

use Phalcon\Validation AS PhalconValidation;

class Validation implements ServiceProviderInterface
{
    public function register(DiInterface $di) {
        $di->setShared('validation', function(){
            return new class extends PhalconValidation {

                public function verify($rules = null, $data = null)
                {
                    if (empty($rules)){
                        return false;
                    }
                    foreach($rules AS $rule)
                    {
                        if (gettype($rule) == 'string'){
                            $this->add($rule, new \Phalcon\Validation\Validator\PresenceOf(array(
                                'message' => 'The '.$rule.' is required.'
                            )));
                        }else{
                            if (!isset($rule[0])) continue;
                            if (count($rule) == 2){
                                $field = $rule[0];
                                $type = 'PresenceOf';
                                $param = ['message'=>$rule[1]];
                            }else if (count($rule) == 3){
                                $field = $rule[0];
                                $type = isset($rule[1]) ? $rule[1] : 'PresenceOf';
                                $param = isset($rule[2]) ? $rule[2] : [];
                            }
                            $class = '\Phalcon\Validation\Validator\\' . $type;
                            $this->add($field, new $class($param));
                        }
                    }
                    $this->validate($data);
                    return $this->getMessages();
                }

                public function error(){
                    if ($this->getMessages()->count() > 0){
                        return (string)$this->getMessages()->current();
                    }
                    return false;
                }

            };
        });
        return $this;
    }
}