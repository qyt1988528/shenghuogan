<?php
namespace Express\Controller;
use MDK\Controller;


/**
 * Face controller.
 * @RoutePrefix("/express", name="express")
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
     * 获取配置
     * Index action.
     * @return void
     * @Route("/getExpressConfig", methods="GET", name="express")
     */
    public function indexAction() {
        $typeId = $this->request->getParam('type',null,1);
        if(empty($typeId)){
            $this->resultSet->error(1001,$this->_error['invalid_input']);
        }
        //分页
        try{
            $data['data'] = [];
            $tickets = $this->app->express->api->Helper()->getTypeList($typeId);
            if(!empty($tickets)){
                $data['data'] = $tickets;
            }
        }catch (\Exception $e){
            $this->resultSet->error($e->getCode(),$e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());

    }
    /**
     * 取快递列表
     * Index action.
     * @return void
     * @Route("/takeList", methods="GET", name="express")
     */
    public function takeAction() {
        $page = $this->request->getParam('page',null,1);
        //分页
        try{
            $data['data'] = [];
            $tickets = $this->app->express->api->Take()->getList($page);
            if(!empty($tickets)){
                $data['data'] = $tickets;
            }
        }catch (\Exception $e){
            $this->resultSet->error($e->getCode(),$e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());
    }
    /**
     * 寄快递列表
     * Index action.
     * @return void
     * @Route("/sendList", methods="GET", name="express")
     */
    public function sendAction() {
        $page = $this->request->getParam('page',null,1);
        //分页
        try{
            $data['data'] = [];
            $tickets = $this->app->express->api->Send()->getList($page);
            if(!empty($tickets)){
                $data['data'] = $tickets;
            }
        }catch (\Exception $e){
            $this->resultSet->error($e->getCode(),$e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());
    }
}
