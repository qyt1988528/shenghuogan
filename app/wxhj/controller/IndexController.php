<?php

namespace Wxhj\Controller;
use MDK\Controller;


/**
 * 玩星汇聚（爱分割） controller.
 * @RoutePrefix("/wxhj", name="wxhj")
 */
class IndexController extends Controller
{
    public $imageTypes = ['jpg','png'];

    /**
     * Home action.
     * @return void
     * @Route("/segmentBorder", methods="POST", name="wxhj")
     */
    public function segmentBorderAction(){
        $params = [];
        $params = [
            'type' => $params['image_type'],//图片类型，目前支持"jpg"和"png"两种类型
            'photo' => $params['base64'],//图片数据BASE64编码 2000*2000
            'border_ratio' => $params['border'],//加边的粗细程度0-1.0之间的值，值越大，边越粗，与原图尺寸存在一定的线性关系
            'margin_color' => '#ffffff',
        ];
        try{
            $segmentData = $this->app->wxhj->api->Helper()->segmentBorder($params);
            if(!empty($segmentData['result'])){
                $data = [
                    'output_url' => $segmentData['result']
                ];
            }else{
                $msg = $this->translate->_('Network Error. Please Try Again Later!');
                $this->resultSet->error(1001,$msg);
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
     * @Route("/segment", methods="POST", name="wxhj")
     */
    public function segmentAction(){
        $imageType = $this->request->getParam('image_type',null,'');
        $base64 = $this->request->getParam('image_base64',null,'');
        $msg = $this->translate->_('Network Error. Please Try Again Later!');
        if(empty($imageType) || empty($base64)){
            $this->resultSet->error(1001,$msg);
        }
        if(!in_array($imageType,$this->imageTypes)){
            $msg = $this->translate->_('File type error!');
            $this->resultSet->error(1002,$msg);
        }
        try{
            $blob = $this->app->core->api->Image()->getBlobByBase64($base64);
            $sizeTrillion = $this->app->core->api->Image()->sizeTrillion;
            $limitSize = 4*$sizeTrillion;
            $compressRet = $this->app->core->api->Image()->compressImage($blob,$limitSize,2000,2000);
            if(empty($compressRet['image_base64str'])){
                $this->resultSet->error(1003,$msg);
            }
            $params = [
                'image_type' => $imageType,//图片类型，目前支持"jpg"和"png"两种类型
                'base64' => $compressRet['image_base64str'],//图片数据BASE64编码 2000*2000,4MB
            ];
            $this->profiler->start('createSegment');
            $segmentData = $this->app->wxhj->api->Helper()->segment($params);
            $this->profiler->stop('createSegment');
            if(!empty($segmentData['result'])){
                $data = [
                    'output_url' => $segmentData['result']
                ];
            }else{
                $msg = $this->translate->_('Network Error. Please Try Again Later!');
                $this->resultSet->error(1003,$msg);
            }
        }catch (\Exception $e){
            $this->resultSet->error($e->getCode(),$e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());
    }


}