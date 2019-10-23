<?php

namespace Tencent\Controller;
use MDK\Controller;


/**
 * tencent controller.
 * @RoutePrefix("/tencent", name="tencent")
 */
class IndexController extends Controller
{
    /**
     * 微信session.
     * @return void
     * @Route("/login", methods="GET", name="tencent")
     */
    public function loginAction()
    {
        $jsCode = $this->request->getParam('js_code',null,'',true);
        try{
            $data = $this->app->tencent->api->WeChat()->getSession($jsCode);
        }catch (\Exception $e){
            $this->resultSet->error($e->getCode(),$e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());
    }
    /**
    public function getSessionKeyAction()
    {
        //获取微信登录sessionKey
        $jsCode = $this->request->getParam('js_code',null,'',true);
        var_dump($jsCode);exit;
        try{
            $data = $this->app->tencent->api->WeChat()->getSession($jsCode);
        }catch (\Exception $e){
            $this->resultSet->error($e->getCode(),$e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());
    }
     */
    /**
     * Home action.
     * @return void
     * @Route("/fuzzy", methods="POST", name="tencent")
     */
    public function fuzzyAction() {
        var_dump('test');exit;
        $image = $this->request->getParam('image');
        try{
//            $data = $this->app->tencent->api->Helper()->isFuzzy($image);
            //由调用腾讯api改为调用face++api 2MB 4096*4096
            $blob = $this->app->core->api->Image()->getBlobByBase64($image);
            $sizeTrillion = $this->app->core->api->Image()->sizeTrillion;
            $limitSize = 2*$sizeTrillion;
            $compressRet = $this->app->core->api->Image()->compressImage($blob,$limitSize,3000,3000);
            if(empty($compressRet)){
                $this->app->core->api->Log()->writeLog('','compress failed','fuzzy_compress_error','log');//
                $data = [
                    'fuzzy' => false,
                    'msg' => ''
                ];
            }else{
                $data = $this->app->face->api->Helper()->faceDetect([ 'image_base64'=> $compressRet['image_base64str']]);
                $fuzzy = $this->app->face->api->Helper()->isBlur($data);
                $data = [
                    'fuzzy' => $fuzzy,
                    'msg' => $fuzzy ? $this->translate->_('Your selected image is blurry, we recommend that you change a clearer one.') :''
                ];
            }
        }catch (\Exception $e){
            $this->resultSet->error($e->getCode(),$e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());


    }

    /**
     * 图片加滤镜.
     * @return void
     * @Route("/filter", methods="POST", name="tencent")
     */
    public function filterAction()
    {
        var_dump('filter-test');exit;
        $image = $this->request->getParam('image');
        $filter = $this->request->getParam('filter');
        try{
            $data = $this->app->tencent->api->Helper()->imgfilter($image,$filter);
        }catch (\Exception $e){
            $this->resultSet->error($e->getCode(),$e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());
    }

    /**
     * 图片是否模糊返回腾迅字段
     * @return void
     * @Route("/fuzzy/origin", methods="POST", name="tencent")
     */
    public function fuzzyoriginAction() {

        $image = $this->request->getParam('image');
        try{
            $data = $this->app->tencent->api->Helper()->fuzzy($image);
        }catch (\Exception $e){
            $this->resultSet->error($e->getCode(),$e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());


    }


}