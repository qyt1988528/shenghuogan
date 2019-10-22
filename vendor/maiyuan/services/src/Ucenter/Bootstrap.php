<?php
namespace Maiyuan\Service\Ucenter;

use Maiyuan\Service\Standard;
use Maiyuan\Service\Exception;

class Bootstrap extends Standard
{
    public static function sendPost($body, $url=null) {
        if (!is_array($body)) {
            $result = array(
                'status'  => '0',
                'message' => '数据格式错误',
            );
            return $result;
        }
        // if (array_diff_key($body,self::$content)) {
        //     $result = array(
        //             'status'  => '0',
        //             'message' => '数据发送失败',
        //         );
        //     return $result;
        // }
        // $nullValue = array_search('', $body);
        // if ($nullValue) {
        //     $result = array(
        //             'status'  => '0',
        //             'message' => $nullValue.'不能为空',
        //         );
        //     return $result;
        // }
        if (!array_key_exists('appId', $body)) {
            $result = array(
                'status'  => '0',
                'message' => '请发送appId',
            ) ;
            return $result;
        }
        $appId = $body['appId'];
        unset($body['appId']);
        $data = json_encode($body);
        //加密数据
        $message = array();
        $message['encrypted'] = RsaCrypt::encrypt($data);
        if (!$message['encrypted']) {
            return array(
                'status'  => '0',
                'data' => $message['encrypted'],
                'message' => '发送数据加密失败,请检查自身代码或证书',
            );
        }
        if (error_get_last()) {
            return array(
                'status'  => '0',
                'message' => '发送数据加密失败,请检查自身代码',
            );
        }
        $message['appId'] = $appId;
        $response = self::sendRequest($url, Config::HTTP_POST, $message);
        return self::processResp($response);
    }


    private static function sendRequest($url, $method, $body=null, $times=1 ) {
        if (!defined('CURL_HTTP_VERSION_2_0')) {
            define('CURL_HTTP_VERSION_2_0', 3);
        }
        if (!$url) {
            $url = Config::API_URL_CN;
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, Config::USER_AGENT);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, Config::CONNECT_TIMEOUT);  // 连接建立最长耗时
        curl_setopt($ch, CURLOPT_TIMEOUT, Config::READ_TIMEOUT);  // 请求最长耗时
        // 设置SSL版本 1=CURL_SSLVERSION_TLSv1, 不指定使用默认值,curl会自动获取需要使用的CURL版本
        // curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // 如果报证书相关失败,可以考虑取消注释掉该行,强制指定证书版本
        //curl_setopt($ch, CURLOPT_SSL_CIPHER_LIST, 'TLSv1');
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_0);

        // 设置Post参数
        if ($method === Config::HTTP_POST) {
            curl_setopt($ch, CURLOPT_POST, true);
        } else if ($method === Config::HTTP_DELETE || $method === Config::HTTP_PUT) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        }
        if (!is_null($body['encrypted'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body['encrypted']);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'AppId: '.$body['appId']
        ));
        $output = curl_exec($ch);
        $response = array();
        $errorCode = curl_errno($ch);
        if ($errorCode) {
            if ($times < Config::DEFAULT_MAX_RETRY_TIMES) {
                return self::sendRequest($url, $method, $body, ++$times);
                //访问uc.soufeel.cn用户中心
            } elseif (($url == Config::API_URL_CN || $url == Config::API_URL_EN) && $times >= Config::DEFAULT_MAX_RETRY_TIMES && $times < Config::DEFAULT_MAX_RETRY_TIMES*2) {
                $url = Config::API_URL_EN;
                return self::sendRequest($url, $method, $body, ++$times);
            } else {
                $result = array(
                    'status'  => 0,
                    'http_code' => $errorCode,
                    'body' => '数据发送失败',
                );
                return $result;
            }
        } else {
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $header_text = substr($output, 0, $header_size);
            $result = substr($output, $header_size);
            $headers = array();
            foreach (explode("\r\n", $header_text) as $i => $line) {
                if (!empty($line)) {
                    if ($i === 0) {
                        $headers[0] = $line;
                    } else if (strpos($line, ": ")) {
                        list ($key, $value) = explode(': ', $line);
                        $headers[$key] = $value;
                    }
                }
            }
            // $response['headers'] = $headers;
            $response['body'] = $result;
            $response['http_code'] = $httpCode;
        }
        curl_close($ch);
        return $response;
    }

    public static function processResp($response) {
        $decrypt = isset($response['body'])?$response['body']:'';
        //解密数据
        $data =  RsaCrypt::decrypt($decrypt);
        if (error_get_last()) {
            return array(
                'status'  => '0',
                'data' => $decrypt,
                'message' => '如存在加密串,请检查是否字符串发生转码',
            );
        }
        $data = json_decode($data,true);
        if ($response['http_code'] === 200) {
//            $result = array(
//                'status'    => 1,
//                'body'      => $data,
//                'http_code' => $response['http_code'],
//                'headers'   => $response['headers'],
//            );
            return $data;
        } else {
            $result = array(
                'status'  => 0,
                'http_code' => $response['http_code'],
                'message' => '请求页面失败',
            );
            return $result;
        }
    }

}
