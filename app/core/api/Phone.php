<?php
/**
 * Created by PhpStorm.
 * User: qiuyutao
 * Date: 2020/4/30
 * Time: 下午2:49
 */

namespace Core\Api;

use MDK\Api;

class Phone extends Api
{
    /**
     * 验证手机号是否正确
     * @param $phone
     * @return bool
     */
    public function checkPhone($phone)
    {
        $check = '/^(1(([35789][0-9])|(47)))\d{8}$/';
        if (preg_match($check, $phone)) {
            return true;
        } else {
            return false;
        }
    }
}
