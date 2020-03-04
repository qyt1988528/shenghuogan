<?php
/**
 * Created by PhpStorm.
 * User: qiuyutao
 * Date: 2019/11/18
 * Time: 下午10:41
 */

/**
 * +------------------------------------------------------
 *    PHP 汉字转拼音
 * +------------------------------------------------------
 *    使用方法:
 *      $py = new PinYin();
 *      echo $py->getpy("汉字",true);
 * +------------------------------------------------------
 */

namespace Core\Api;

use MDK\Api;

class XmlAnalysis extends Api
{

    /**
     * 将数组转化为xml数据格式
     * @param $arr
     * @return string
     */
    public function arrayToXml($arr)
    {
        $xml = "<xml>";
        foreach ($arr as $key => $val) {
            if (is_array($val)) {
                $xml .= "<" . $key . ">" . $this->arrayToXml($val) . "</" . $key . ">";
            } else {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            }
        }
        $xml .= "</xml>";
        return $xml;
    }


    /**
     * 将XML转化为json/数组
     * @param $xml
     * @param string $type
     * @return mixed|string
     */
    public function xmlToArray($xml, $type = '')
    {
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);

        //simplexml_load_string()解析读取xml数据，然后转成json格式
        $xmlstring = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);

        if ($type == "json") {
            $json = json_encode($xmlstring);
            return $json;
        }
        $arr = json_decode(json_encode($xmlstring), true);
        return $arr;
    }


}