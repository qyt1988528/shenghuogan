<?php
namespace Driver\Controller;
use MDK\Controller;


/**
 * Face controller.
 * @RoutePrefix("/driving", name="driving")
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
     * Index action.
     * @return void
     * @Route("/", methods="GET", name="driving")
     */
    public function indexAction() {
        $page = $this->request->getParam('page',null,1);
        //分页
        try{
            $data['data'] = [];
            $tickets = $this->app->driver->api->Helper()->getList($page);
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
     * mergeFace action.
     * 商品详情
     * @return void
     * @Route("/detail", methods="GET", name="driving")
     */
    public function detailAction(){
        $goodsId = $this->request->getParam('id',null,'');
        if(empty($goodsId)){
            $this->resultSet->error(1001,$this->_error['invalid_input']);
        }
        try{
            $result = $this->app->driver->api->Helper()->detail($goodsId);
            if(empty($result)){
                $this->resultSet->error(1002,$this->_error['not_exist']);
            }
            $data['data'] = $result;
            if(isset($result->promise_description)){
                $promiseData = json_decode($result->promise_description,true);
                $data['data']['promise_data'] = $promiseData;
            }
        }catch (\Exception $e){
            $this->resultSet->error($e->getCode(),$e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());
    }

    /**
     * 根据关键词搜索 商品
     * @return void
     * @Route("/search", methods="GET", name="driving")
     */
    public function searchAction(){
        $keywords = $this->request->getParam('keywords',null,'');
        if(empty($keywords)){
            $this->resultSet->error(1001,$this->_error['invalid_input']);
        }
        //暂不考虑拼音搜索
        try{
            $data['data'] = [];
            $result = $this->app->driver->api->Helper()->search($keywords);
            if(!empty($result)){
                //$this->resultSet->error(1002,$this->_error['not_exist']);
                $data['data'] = $result;
            }
        }catch (\Exception $e){
            $this->resultSet->error($e->getCode(),$e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());
    }

    
}
