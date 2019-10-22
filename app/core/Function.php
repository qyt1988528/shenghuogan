<?php
use Phalcon\Di;
function core_timestamp($date){
    return (string) strtotime($date);
}
function core_translate(string $string)
{
    $di = Di::getDefault();
    return $di->getTranslate()->_($string);
}

/**
 * 获取后台图片地址
 * @param $image
 * @param $isBetter bool
 * @return string 
 */
function core_getImage($image,$isBetter = false){
    $di = Di::getDefault();
    $request = $di->getRequest();
    $host ='';
    if(strpos($image,'/') === 0){
        if(strpos($request->getHttpHost(),'imaiyuan') !== false){
            $host = 'https://app.imaiyuan.com/';
        }elseif(strpos($request->getHttpHost(),'soufeel') !== false){
            $host = 'https://ik.imagekit.io/soufeel/appadmin/';
        }
        $image = $host.$image;
    }
    if($isBetter){
        $image = core_getBestCDNImage($image,'ik.imagekit.io/soufeel/appadmin/');
    }
    return (string)$image;
}

/**
 * imagekit 暂不支持zip ar资源使用后台地址
 * @param $path
 * @return string
 */
function core_getBackendUrl($path)
{
    $di = Di::getDefault();
    $request = $di->getRequest();
    $host ='';
    $url='';
    if(strpos($path,'/') === 0){
        if(strpos($request->getHttpHost(),'imaiyuan') !== false){
            $host = 'https://app.imaiyuan.com/';
        }elseif(strpos($request->getHttpHost(),'soufeel') !== false){
            $host = 'https://appadmin.soufeel.com/';
        }
        $url = $host.$path;
    }
    return (string)$url;
}

/**
 * 获取ios最佳cdn图片链接（客户端请求增加强制webp参数tr:f-webp）
 * @param $image  string
 * @param $search string   'ik.imagekit.io/soufeel/en/' 英文站 ik.imagekit.io/soufeel/appadmin/   app 后台
 * @return string
 */
function core_getBestCDNImage($image,$search = 'ik.imagekit.io/soufeel/en/')
{
    $di = Di::getDefault();
    $request = $di->getRequest();
    $helper = $di->getApp()->core->api->Helper();
    $ua = $request->getUserAgent();//根据ua及mobile-system请求头判断是否客户端请求如果是的话返回webp图片
    $isIosRequest = false;
    if ((strpos($request->getUserAgent(), 'soufeel') !== false || $request->getHeader('Mobile-System') == 'iOS') && $helper->isIos()) {
        $isIosRequest = true;
    }
    if ($isIosRequest && strpos($image, 'imagekit.io')) {
        $image = str_replace($search,$search.'tr:f-webp/',$image).'?format=webp';
    }
    return $image;
}

/**
 * 砍价活动为价格加上$符号
 * @param $price
 * @return string
 */
function core_deelprice($price)
{
    if(!empty($price) || $price == 0){
        $price = 'US$' . $price;
    }
    return $price;
}

function core_get_model_message($model)
{
    $msg ='';
    foreach ($model->getMessages() as $message) {
        $msg.=$message->getMessage().',';
    }
    return $msg;
}