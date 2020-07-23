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
     */
    public function newEmpty($data)
    {
        if(empty($data)){
            return true;
        }
        if(is_object($data)){
            foreach($data as $k=>$v){
                if($k==='di'){
                    return true;
                }
            }
        }
        return false;

    }
}
