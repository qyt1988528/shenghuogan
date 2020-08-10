<?php
/**
 * Created by PhpStorm.
 * User: qiuyutao
 * Date: 2020/7/23
 * Time: 下午1:13
 */
namespace Core\Api;

use MDK\Api;

class CheckEmpty extends Api
{
    /**
     * 验证手机号是否正确
     * @param $phone
     * @return bool
     * true--表示为空 false--不为空
     */
    public function newEmpty($data)
    {
        if(empty($data)){
            return true;
        }
        if(is_object($data) || is_array($data)){
            foreach($data as $k=>$v){
                if($k==='di'){
                    return true;
                }
            }
        }
        return false;

    }
    public function newToArray($objectData){
        if($this->newEmpty($objectData)){
            return [];
        }else{
            if(is_object($objectData)){
                $data = [];
                foreach ($objectData as $k=>$v){
                    $data[$k] = $v;
                }
                return $data;

            }
        }
        return [];

    }
}
