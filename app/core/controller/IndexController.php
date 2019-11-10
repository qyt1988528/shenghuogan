<?php

namespace Core\Controller;
use MDK\Controller;


/**
 * Index controller.
 * @RoutePrefix("/", name="core")
 */
class IndexController extends Controller
{

    /**
     * Home action.
     * @return void
     * @Route("/upload", methods="POST", name="core")
     */
    public function uploadAction() {
        $image = $this->request->getParam('image_blob');
        $imageName = $this->request->getParam('image_name');

        try{
            if(empty($imageName)){
                $rand = mt_rand(100000, 999999);
                $time = time();
                $imageName = 'test'.$rand.'/'.date('Ymd').'/'.$time.$rand.'.jpg';
            }
            $this->app->core->api->Log()->writeLog('','upload compress image to qiniu start',$this->accessLog,$this->accessFunc);
            $qiniuUploadRet = $this->app->admin->core->api->Qiniu()->uploadBlobToQiniu($image,$imageName);
            if(!empty($qiniuUploadRet['base_url']) && !empty($qiniuUploadRet['path_url'])){
                $data['url'] = $qiniuUploadRet['base_url'].$qiniuUploadRet['path_url'];
            }else{
                $this->resultSet->error(1005,'Network Error. Please Try Again Later');
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