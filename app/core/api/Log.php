<?php
namespace Core\Api;

use MDK\Api;
use Phalcon\DI;

class Log extends Api{
    /**
     * 记录日志
     * @param $data 要记录的数据
     * @param string $description 本次记录的描述
     * @param string $fileName 日志文件名
     * @param string $func 记录日志的方法,一般log记录access_log、error记录error_log
     * @return string
     */
    public function writeLog($data,$description='',$fileName ='error',$func = 'error'){
        $ret = '';
        if( (empty($data) && empty($description)) || empty($fileName) || !in_array($func,['log','error'])){
            return $ret;
        }
        $di = Di::getDefault();
        $logger = $di->getLogger($fileName);
        if(is_string($data)){
            $ret = $logger->$func(date('Y-m-d H:i:s').': '.$description.' string '.$data);
        }elseif(is_array($data)){
            $ret = $logger->$func(date('Y-m-d H:i:s').': '.$description.' array '.json_encode($data));
        }
        return $ret;
    }
    public function writeSkuLog($base64str='',$fileName ='error',$func = 'log'){
        $ret = '';
        if( empty($base64str) || empty($fileName) || !in_array($func,['log','error'])){
            return $ret;
        }
        $fileName = $this->getSkuFilename($fileName);
        if(is_string($base64str) && !file_exists($fileName)){
            //写日志
            file_put_contents($fileName,$base64str);
        }
        return true;
    }
    public function getSkuFilename($filename){
        $path = $this->dir->var('sku');
        $fileName = $path.DIRECTORY_SEPARATOR.$filename.'.log';
        return $fileName;
    }
}
