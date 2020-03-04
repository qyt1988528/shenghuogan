<?php
/**
 * Created by PhpStorm.
 * User: qiuyutao
 * Date: 2020/3/2
 * Time: 下午2:23
 */

namespace Core\Api;

use MDK\Api;

class XmlAnalysis extends Api
{
    public function curlPost($url, $params, $config = array(), $build = false, $times = 0)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        //默认连接时间和执行时间
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);

        //user_agent
        $userAgent = "curl/7.51.0";
        curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);

        foreach ($config as $key => $val) {
            curl_setopt($ch, $key, $val);
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        if ($build == false) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        }
        $response = curl_exec($ch);
//        $request_info = curl_getinfo($ch);
//        var_dump($request_info);
        if (curl_errno($ch)) {
            if (curl_errno($ch) == CURLE_OPERATION_TIMEDOUT && $times < 3) {
                //超时3次内的处理代码
                $times++;
                $this->curlPost($url, $params, $config, $build, $times);
            } else {
                //是否记录log
                throw new \Exception(curl_error($ch), 0);
            }
        } else {
            $retCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($retCode != '200') {
                throw new \Exception($response, $retCode);
            }
        }
        curl_close($ch);
        return $response;
    }
}
