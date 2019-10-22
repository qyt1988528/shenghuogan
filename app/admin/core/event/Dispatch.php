<?php
namespace Admin\Core\Event;
use Phalcon\Di\Injectable;
use Core\Service\ResultSet;

/**
 * Common Event.
 */
class Dispatch extends Injectable
{
    public function beforeDispatch() {
        if($this->request->getMethod() == 'OPTIONS'){
            exit();
        }
        $params = $this->request->getParams();
        $uri = $this->request->getURI();
        if(strpos($uri,'/admin')===0){
            $this->getDI()->setShared('validator','Admin\Core\Service\Access');
            $allowUris = [
                '/admin/access',
                '/admin/basic/upload',
            ];
            if(!in_array($uri,$allowUris)){
                $token = $this->request->getHeader('Access-Token');
                if(!($token && $admin = $this->validator->verify($token))){
                    header($_SERVER["SERVER_PROTOCOL"]." 401 Unauthorized ");
                    exit(1);
                }
            }
        }




    }
}