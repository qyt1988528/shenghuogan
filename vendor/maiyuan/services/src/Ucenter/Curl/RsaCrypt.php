<?php 
namespace Maiyuan\Service\Ucenter;

class RsaCrypt{
    protected static $_handle;       
    public static function getHandle(){         
        self::$_handle = new \MSDK\Crypt\RSA();                                             
        return self::$_handle;
    }    

    public static function getKey(){
        $privitePem = __DIR__.'/PrivateKey.pem';              
        $privateKey = file_get_contents($privitePem);//import private key
        return $privateKey;
    } 

    public static function encrypt($plaintext){                          
        $handle = self::getHandle();
        $privateKey = self::getKey();
        $handle->loadKey($privateKey);
        $handle->loadKey($handle->getPublicKey());                        
        return base64_encode($handle->encrypt($plaintext));
    }

    public static function decrypt($encrypted){                             
        $handle = self::getHandle();
        $privateKey = self::getKey();          
        $handle->loadKey($privateKey);   
        return $handle->decrypt(base64_decode($encrypted));
    }
}
