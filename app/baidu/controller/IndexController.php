<?php

namespace Baidu\Controller;
use MDK\Controller;


/**
 * Home controller.
 * @RoutePrefix("/baidu", name="baidu")
 */
class IndexController extends Controller
{
    /**
     * 图片无损放大两倍
     * @return void
     * @Route("/enhance", methods="POST", name="baidu")
     */
    public function imageQualityEnhanceAction() {
        $image = $this->request->getParam('image');
        try{
            $data = $this->app->baidu->api->Helper()->imageQualityEnhance($image);
        }catch (\Exception $e){
            $this->resultSet->error($e->getCode(),$e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());
    }

    /**
     * 图片无损放大两倍
     * @return void
     * @Route("/code", methods="GET", name="baidu")
     */
    public function codeAction(){

        // var_dump(222);
        $d = $this->app->core->api->CoreQrcode()->corePng('https://www.baidu.com');
        var_dump($d);
        exit;

    }


}