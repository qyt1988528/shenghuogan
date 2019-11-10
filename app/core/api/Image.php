<?php
namespace Core\Api;

use MDK\Api;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\Middleware;
class Image extends Api{

    /**
     * 获取不含data:image/jpeg;base64,的base64图片
     * @param $image
     * @return bool|string
     */
    public function getBaseImage($image)
    {
        if(($start = strpos($image, ",")) !== false){
            $start ++;
            $image = substr($image, $start);
        }
        return $image;
    }

    /**
     * 获取包含data:image/jpeg;base64,的base64图片
     * @param $image
     * @return string
     */
    public function getWebBaseImage($image)
    {
        if($start = strpos($image, ",") === false){
            $image = 'data:image/jpeg;base64,'.$image;
        }
        return $image;
    }

    /**
     * 通过二进制流 获取 base64str
     * @param $blob
     * @return mixed|string
     */
    public function getBase64ByBlob($blob){
        $base64str = chunk_split(base64_encode($blob));
        $base64str = str_replace("\r\n",'',$base64str);
        $base64str = str_replace("\n",'',$base64str);
        $base64str = str_replace("\r",'',$base64str);
        return $base64str;
    }

    /**
     * 通过base64str 获取 二进制流
     * @param $base64str
     * @return bool|string
     */
    public function getBlobByBase64($base64str){
        $base64str = $this->getBaseImage($base64str);
        return base64_decode($base64str);
    }

    /**
     * 根据图片地址获取其base64编码
     * @param $imageUrl
     * @return string
     */
    public function getBase64ByImageUrl($imageUrl){
        try{
            $data = $this->imageFileGetContents($imageUrl);
            $base64str = base64_encode($data);
        }catch (\Exception $e){
            $base64str = '';
        }
        return $base64str;
    }

    /**
     * 根据图片地址获取其二进制流
     * @param $imageUrl
     * @return false|string
     */
    public function getBlobByImageUrl($imageUrl){
        try{
            $blob = $this->imageFileGetContents($imageUrl);
        }catch (\Exception $e){
            $blob = '';
        }
        return $blob;
    }

    /**
     * file_get_contents 设置https验证&超时时间
     * @param $imageUrl
     * @return false|string
     */
    public function imageFileGetContents($imageUrl){
        try{
            $arrContextOptions=array(
                "ssl"=>array(
                    "verify_peer"=>false,
                    "verify_peer_name"=>false,
                ),
                'http'=>array(
                    'method'=> "GET",
                    'timeout'=> 30,//单位秒
                )
            );
            return file_get_contents($imageUrl, false, stream_context_create($arrContextOptions));
        }catch (\Exception $e){
            return '';
        }
    }

    public function saveBase64($base64,$filename){
        try{
            if(!file_exists($filename) && !empty($filename)){
                file_put_contents($filename,base64_decode($base64));
            }
        }catch (\Exception $e){

        }
    }

    public function getInfo($url) {
        $data = parse_url($url);
        $client = new Client([
            'base_uri' => $data['scheme'].'://'.$data['host'],
            'timeout'  => 30,
            'handler' => $this->_getStack(),
        ]);
        $response = $client->request('GET', $data['path'],[
        ]);
        $result = $response->getBody();
        $result = json_decode($result,true);
        return $result;
    }

    protected function _getStack()
    {
        $stack = new HandlerStack();
        $stack->setHandler(new CurlHandler());
        $stack->push(Middleware::mapResponse($this->_getMapResponse()));
        return $stack;
    }

    /**
     * 响应报错处理
     * @return \Closure
     */
    protected function _getMapResponse()
    {
        return function( $response){
            if($response->getStatusCode() == 200){
            }elseif($response->getStatusCode() == 400){
                $result = $response->getBody()->getContents();
                $result = json_decode($result,true);
                if(isset($result['error'])&& strpos($result['error'],'too large')!==false){
                }else{
                    throw new \Exception("NetWork Error :get response code ".$response->getStatusCode(),1);
                }
            }else{
                throw new \Exception("NetWork Error :get response code ".$response->getStatusCode(),1);
            }
            return $response;
        };
    }


}
