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


}