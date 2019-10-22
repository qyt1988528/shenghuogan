<?php

namespace Reflect\Controller;
use MDK\Controller;


/**
 * reflect controller.
 * @RoutePrefix("/reflect", name="reflect")
 */
class IndexController extends Controller
{
    public $accessLog = 'reflect_access';
    public $errorLog = 'reflect_error';
    public $accessFunc = 'log';
    public $errorFunc = 'error';
    /**
     * Home action.
     * @return void
     * @Route("/fuse", methods="POST", name="reflect")
     */
    public function fuseFaceByUrlAction(){
        $tempUrl = $this->request->getParam('temp_url',null,'');
        $userUrl = $this->request->getParam('user_url',null,'');
        $msg = $this->translate->_('Network Error. Please Try Again Later!');
        if(empty($tempUrl) || empty($userUrl)){
            $this->resultSet->error(1001,$msg);
        }

        try{
            //压缩 上传七牛 人脸识别
            //模板图 获取图片上传链接
            $url = $this->app->reflect->api->Helper()->getUploadUrl();
            // 上传图片
            $file = $this->app->core->api->Image()->getBlobByImageUrl($tempUrl);
            if(empty($file)){
                $this->resultSet->error(1002,$msg);
            }
            $this->app->reflect->api->Helper()->uploadImageOther($url,$file);
            // 获取图片信息 并保存
            $imageUrl = $this->app->reflect->api->Helper()->getImageUrl($url);
            $data = $this->app->reflect->api->Helper()->getImageInfo($imageUrl);
            $this->app->core->api->Log()->writeLog($data,' temp image info',$this->accessLog,$this->accessFunc);
            $imageIdTemp = $data['id'];
            $imageInfoIdTemp = $data['imageInfo']['id'];
            $tmp = $data['imageInfo']['faces'];
            foreach ($tmp as $faceId=>$v){
                $imageInfoIdTemp =$faceId;break;
            }
            //
            //用户图 获取图片上传链接
            $url = $this->app->reflect->api->Helper()->getUploadUrl();
            // 上传用户图片
            $file = $this->app->core->api->Image()->getBlobByImageUrl($userUrl);
            if(empty($file)){
                $this->resultSet->error(1003,$msg);
            }
            $this->app->reflect->api->Helper()->uploadImageOther($url,$file);
            // 获取图片信息 并保存
            $imageUrl = $this->app->reflect->api->Helper()->getImageUrl($url);
            $data = $this->app->reflect->api->Helper()->getImageInfo($imageUrl);
            $this->app->core->api->Log()->writeLog($data,' user image info',$this->accessLog,$this->accessFunc);
            $imageIdUser = $data['id'];
            $imageInfoIdUser = $data['imageInfo']['id'];
            $tmp = $data['imageInfo']['faces'];
            foreach ($tmp as $faceId=>$v){
                $imageInfoIdUser =$faceId;break;
            }

            // 人脸融合
            $params = [
                "image_id" => $imageIdTemp,
                "facemapping" => [ $imageInfoIdTemp =>  [  $imageInfoIdUser ] ],
                "tumbler" => true,
            ];
            $this->app->core->api->Log()->writeLog($params,' face fuse start',$this->accessLog,$this->accessFunc);
            $ret = $this->app->reflect->api->Helper()->faceFuse($params);
            $this->app->core->api->Log()->writeLog($ret,' face fuse end',$this->accessLog,$this->accessFunc);
            if(isset($ret['data']['image_path']) && !empty($ret['data']['image_path'])){
                $data = [
                    'output_url' => $ret['data']['image_path']
                ];
            }else{
                $this->resultSet->error(1004,$msg);
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
     * @Route("/upload", methods="POST", name="reflect")
     */
    public function uploadImageAction(){
        $file = $this->request->getParam('image_file',null,'');
        $msg = $this->translate->_('Network Error. Please Try Again Later!');
        if(empty($file)){
            $this->resultSet->error(1001,$msg);
        }

        try{
            $url = $this->app->reflect->api->Helper()->getUploadUrl();
            // 上传图片
            $this->app->reflect->api->Helper()->uploadImageOther($url,$file);
            // 获取图片信息 并保存
            $imageUrl = $this->app->reflect->api->Helper()->getImageUrl($url);
            $data = $this->app->reflect->api->Helper()->getImageInfo($imageUrl);
            $this->app->core->api->Log()->writeLog($data,' temp image info',$this->accessLog,$this->accessFunc);
            $imageIdTemp = $data['id'];
            $imageInfoIdTemp = $data['imageInfo']['id'];
            $tmp = $data['imageInfo']['faces'];
            foreach ($tmp as $faceId=>$v){
                $imageInfoIdTemp =$faceId;break;
            }
            $data = [
                'image_id' => $imageIdTemp,
                'face_id' => $imageInfoIdTemp,
            ];
        }catch (\Exception $e){
            $this->resultSet->error($e->getCode(),$e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());
    }
    /**
     * Home action.
     * @return void
     * @Route("/fuseFaceById", methods="POST", name="reflect")
     */
    public function fuseFaceByImageIdAction(){
        $imageIdTemp = $this->request->getParam('image_id_temp',null,0);
        $imageFaceIdTemp = $this->request->getParam('face_id_temp',null,0);
        $imageFaceIdUser = $this->request->getParam('face_id_user',null,0);
        $msg = $this->translate->_('Network Error. Please Try Again Later!');
        if(empty($imageIdTemp) || empty($imageFaceIdTemp) || empty($imageFaceIdUser)){
            $this->resultSet->error(1001,$msg);
        }
        try{
            // 人脸融合
            $params = [
                "image_id" => $imageIdTemp,
                "facemapping" => [ $imageFaceIdTemp =>  [  $imageFaceIdUser ] ],
                "tumbler" => true,
            ];
            $this->app->core->api->Log()->writeLog($params,' face-api fuse start',$this->accessLog,$this->accessFunc);
            $ret = $this->app->reflect->api->Helper()->faceFuse($params);
            $this->app->core->api->Log()->writeLog($ret,' face-api fuse end',$this->accessLog,$this->accessFunc);
            if(isset($ret['data']['image_path']) && !empty($ret['data']['image_path'])){
                $data = [
                    'output_url' => $ret['data']['image_path']
                ];
            }else{
                $this->resultSet->error(1004,$msg);
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
     * @Route("/faceFuse", methods="POST", name="reflect")
     */
    public function faceFuseAction(){
        $sku = $this->request->getParam('sku',null,1);//sku
        $ethnicity = $this->request->getParam('ethnicity',null,'');//肤色
        $imageBase64 = $this->request->getParam('image_base64',null,'');//需要换脸的用户头像BASE64（建议2M以内 2048*2048）
        try{
            $msg = $this->translate->_('Network Error. Please Try Again Later!');
            if(empty($sku) || empty($ethnicity) || empty($imageBase64)){
                $this->resultSet->error(1001,$msg);
            }
            $imageBase64 = $this->app->core->api->Image()->getBaseImage($imageBase64);
            $ethnicity = substr($ethnicity,0,3);
            $configInfo = $this->app->reflect->config->skus->toArray();
            $tempInfo = $configInfo[$sku][$ethnicity] ?? [];

            if(empty($tempInfo)){
                $this->resultSet->error(1002,$msg);
            }
            //人脸识别
            $this->profiler->start('faceDetect');
            $faceRet = $this->app->face->api->Helper()->faceDetect(['image_base64'=>$imageBase64],'',false);
            $this->profiler->stop('faceDetect');
            //人脸个数判断
            $faceNum = $this->app->face->api->Helper()->faceNum($faceRet);
            if($faceNum != 1){
                $msg = $this->translate->_('Please make sure there is only one person in the picture.');
                $this->resultSet->error(1003,$msg);
            }
            //脸部姿态判断，抬头、旋转（平面旋转）、摇头，这3个维度均为±15°
            $headPose = $this->app->face->api->Helper()->headPose($faceRet);
            if( ($headPose['pitch_angle']==2 || $headPose['pitch_angle']==3)
                || ($headPose['roll_angle']==2 || $headPose['roll_angle']==3)
                || ($headPose['yaw_angle']==2 || $headPose['yaw_angle']==3)
            ){
                if(!empty($headPose['message'])){
                    $this->resultSet->error(1004,$headPose['message']);
                }else{
                    $this->resultSet->error(1005,$msg);
                }
            }
            //创建reflect换脸任务
            $this->profiler->start('createReflectFaceTask');
            //获取用户图reflect_face_id
            $url = $this->app->reflect->api->Helper()->getUploadUrl();
            // 上传图片
            $imageFile = $this->app->core->api->Image()->getBlobByBase64($imageBase64);
            $this->app->reflect->api->Helper()->uploadImageOther($url,$imageFile);
            // 获取图片信息 并保存
            $imageUrl = $this->app->reflect->api->Helper()->getImageUrl($url);
            $dataImageInfo = $this->app->reflect->api->Helper()->getImageInfo($imageUrl);
            $userFaceId = '';
            $tmp = $dataImageInfo['imageInfo']['faces'];
            foreach ($tmp as $faceId=>$v){
                $userFaceId =$faceId;break;
            }
            if(empty($userFaceId)){
                $this->resultSet->error(1006,$msg);
            }
            $params = [
                "image_id" => $tempInfo['image_id'],
                "facemapping" => [ $tempInfo['face_id'] =>  [  $userFaceId ] ],
                "tumbler" => true,
            ];
            $ret = $this->app->reflect->api->Helper()->faceFuse($params);
            $this->app->core->api->Log()->writeLog($ret,' temp image info',$this->accessLog,$this->accessFunc);
            if(isset($ret['data']['image_path']) && !empty($ret['data']['image_path'])){
                $data = [
                    'output_url' => $ret['data']['image_path']
                ];
            }else{
                $this->resultSet->error(1007,$msg);
            }
            $this->profiler->stop('createReflectFaceTask');
        }catch (\Exception $e){
            $this->resultSet->error($e->getCode(),$e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());
    }



}