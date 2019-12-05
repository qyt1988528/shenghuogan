<?php
namespace Parttimejob\Controller;
use MDK\Controller;


/**
 * Index controller.
 * @RoutePrefix("/parttimejob", name="parttimejob")
 */
class IndexController extends Controller
{

    /**
     * Index action.
     * 兼职首页(列表)
     * @return void
     * @Route("/list", methods="GET", name="parttimejob")
     */
    public function indexAction() {


        try{
            $data = $this->app->parttimejob->api->Helper()->parttimejobList();
        }catch (\Exception $e){
            $this->resultSet->error($e->getCode(),$e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());

    }

    /**
     * mergeFace action.
     * 兼职详情
     * @return void
     * @Route("/detail", methods="GET", name="parttimejob")
     */
    public function detailAction(){
        $parttimejobId = $this->request->getParam('id',null,'');
        if(empty($parttimejobId)){

        }
        try{
            $data = $this->app->parttimejob->api->Helper()->detail($parttimejobId);
        }catch (\Exception $e){
            $this->resultSet->error($e->getCode(),$e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());
    }

}
