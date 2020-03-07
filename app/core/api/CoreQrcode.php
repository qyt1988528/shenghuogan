<?php
/**
 * Created by PhpStorm.
 * User: qiuyutao
 * Date: 2020/3/6
 * Time: 下午10:46
 */
namespace Core\Api;

use MDK\Api;
class CoreQrcode extends Api{
    public function corePng($url){
        $imageString = $this->png($url);
        $imageBase64 = $this->app->core->api->Image()->getWebBaseImage($imageString);
        // header("content-type:appliation/json; charset=utf-8");
        return $imageBase64;
    }
    //根据url生成二维码
    public function png($url) {
        include_once __DIR__ . '/../../../vendor/phpqrcode/phpqrcode.php';
        ob_start();
        \QRcode::png($url);
        //这里就是把生成的图片流从缓冲区保存到内存对象上，使用base64_encode变成编码字符串，通过json返回给页面。
        $imageString = base64_encode(ob_get_contents());
        //关闭缓冲区
        ob_end_clean();
        //把生成的base64字符串返回给前端
        header("content-type:appliation/json; charset=utf-8");
        return $imageString;
    }


}
