<?php

namespace Core\Controller;
use MDK\Controller;


/**
 * Index controller.
 * @RoutePrefix("/", name="core")
 */
class IndexController extends Controller
{
    private $_error;

    public function initialize()
    {
        $config = $this->app->core->config->config->toArray();
        $this->_error = $config['error_message'];
    }
    /**
     * Home action.
     * @return void
     * @Route("/upload", methods="POST", name="core")
     */
    public function uploadAction() {
        $image = $this->request->getParam('image_blob');
        $image = $this->request->getParam('image_base64');
        $imageName = $this->request->getParam('image_name');

        try{
            if(empty($image)){
                $this->resultSet->error(1001,$this->_error['invalid_input']);
            }
            if(empty($imageName)){
                $rand = mt_rand(100000, 999999);
                $time = time();
                $imageName = 'test'.$rand.'/'.date('Ymd').'/'.$time.$rand.'.jpg';
            }
            $imageBlob = $this->app->core->api->Image()->getBlobByBase64($image);
            $qiniuUploadRet = $this->app->admin->core->api->Qiniu()->uploadBlobToQiniu($imageBlob,$imageName);
            if(!empty($qiniuUploadRet['base_url']) && !empty($qiniuUploadRet['path_url'])){
                $data['data'] = ['img_url' => $qiniuUploadRet['base_url'].$qiniuUploadRet['path_url']];
            }else{
                $this->resultSet->error(1002,$this->_error['try_later']);
            }
        }catch (\Exception $e){
            $this->resultSet->error($e->getCode(),$e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());

    }
    /**
     * Home action.
     * @return void
     * @Route("/uploadtest", methods="POST", name="core")
     */
    public function upload2Action() {
        $image = $this->request->getParam('image_blob');
        $image = $this->request->getParam('image_base64');
        $imageName = $this->request->getParam('image_name');

        try{
            if(empty($image)){
                $this->resultSet->error(1001,$this->_error['invalid_input']);
            }
            if(empty($imageName)){
                $rand = mt_rand(100000, 999999);
                $time = time();
                $imageName = 'test'.$rand.'/'.date('Ymd').'/'.$time.$rand.'.jpg';
            }
            $imageBlob = $this->app->core->api->Image()->getBlobByBase64($image);
            $qiniuUploadRet = $this->app->admin->core->api->QiniuTest()->uploadBlobToQiniu($imageBlob,$imageName);
            if(!empty($qiniuUploadRet['base_url']) && !empty($qiniuUploadRet['path_url'])){
                $data['data'] = ['img_url' => $qiniuUploadRet['base_url'].$qiniuUploadRet['path_url']];
            }else{
                $this->resultSet->error(1002,$this->_error['try_later']);
            }
        }catch (\Exception $e){
            $this->resultSet->error($e->getCode(),$e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());

    }
    public function setQiniuAction(){
        $path = 'qiniu';
        $value = '{"_url":"\/admin\/system\/config\/multiple","key":"qiniu","AccessKey":"o614SUzXUjQy-HP6LCalMo8yYUfdC6lHEJAmyG7F","SecretKey":"9Ib0u1h1UP-WiseGny23dmLbrlFRNrOmpRfqkON3","buket":"test","domain":"http://qiniu.wanjunjiaoyu.com/","lang":"en"}';
        $this->app->admin->core->api->Helper()->setConfig($path,$value);
    }


}