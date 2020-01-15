<?php
namespace Admin\Core\Api;
use MDK\Api;
use Qiniu\Auth;
use Qiniu\Storage\UploadManager;

class QiniuTest extends Api
{
    private $_auth,$_config,$_helper;
    private $uploadMgr;
    public function __construct() 
    {
        $this->_helper = $this->app->admin->core->api->HelperTest();
        $this->_config = $this->_helper->getConfig('qiniu');
        $this->_config = json_decode($this->_config);
        $this->_auth = new Auth($this->_config->AccessKey,$this->_config->SecretKey);
    }

    public function getToken()
    {
        $data = $this->_helper->getConfig('qiniu/token');
        $data = json_decode($data,true);
        if(empty($data['expire']) || time() > $data['expire']){
            $data['token'] = $this->_auth->uploadToken($this->_config->buket);
//            $data['token'] = $this->_auth->uploadToken($this->_config->buket,'soufeelApp',3600,['isPrefixalScope' => 1]);
            $data['expire'] = time() + 3500;
            $this->_helper->setConfig( 'qiniu/token', json_encode($data));
        }
        return $data;
    }


    /**
     * 获取七牛上传类
     * @return UploadManager
     */
    public function getQiniuUpmgr()
    {
        if(!$this->uploadMgr){
            $this->uploadMgr = new UploadManager();
        }
        return $this->uploadMgr;
    }

    /**
     * 上传图片二进制流到七牛
     * @param $blob
     * @param $imageName
     * @return mixed|string
     * @throws \Exception
     */
    public function uploadBlobToQiniu($blob,$imageName){
        $tokenData = $this->getToken();
        $token = $tokenData['token'];
        $uploadMgr = $this->getQiniuUpmgr();
        $pathInfo = pathInfo($imageName);
        if(strpos($imageName,'http') === 0){
            $urlInfo = parse_url($imageName);
            $imageName = $urlInfo['path'];
//            $image = substr($image,1);
        }
        if(!empty($pathInfo['extension'])){
            $key = str_replace(".{$pathInfo['extension']}",".{$pathInfo['extension']}",$imageName);
        }else{
            $key = $imageName.'.png';
        }
        list($ret, $err) = $uploadMgr->put($token, $key, $blob);
        if ($err !== null) {
            if($err->message() != 'file exists'){
                throw new \Exception($err->message(), 1004);
            }
        }
        return [
            'base_url' => !empty($this->_config->domain) ? $this->_config->domain : '',
            'path_url' => !empty($key) ? $key : '',
        ];
    }
}
