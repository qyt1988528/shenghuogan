<?php

namespace Core\Controller;
use MDK\Controller;


/**
 * Home controller.
 * @RoutePrefix("/", name="home")
 */
class IndexController extends Controller
{

    /**
     * Home action.
     * @return void
     * @Route("/", methods="GET", name="home")
     */
    public function indexAction() {

        $result = [
            'pageInfo'=>[
                'pageNumber' => '1',
                'pageSize' => '10'
            ]
        ];
        $this->resultSet->setData($result);
        $this->response->success($this->resultSet->filterByConfig('definitions/Common'));
//        $common = $formater->path('definitions/Common',null,'/');
//        $definitions = $formater->getData($common);
//        $result= $formater->filter($definitions,$result);
//        echo (json_encode($result));die;
//        var_dump($common);die;

    }

    public function setQiniuAction(){
        $path = 'qiniu';
        $value = '{"_url":"\/admin\/system\/config\/multiple","key":"qiniu","AccessKey":"o614SUzXUjQy-HP6LCalMo8yYUfdC6lHEJAmyG7F","SecretKey":"9Ib0u1h1UP-WiseGny23dmLbrlFRNrOmpRfqkON3","buket":"test","domain":"http://qiniu.wanjunjiaoyu.com/","lang":"en"}';
        $this->app->admin->core->api->Helper()->setConfig($path,$value);
    }


}