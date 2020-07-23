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
    private $_userId;

    public function initialize()
    {
        $config = $this->app->core->config->config->toArray();
        $this->_error = $config['error_message'];
        $this->_userId = $this->app->tencent->api->UserApi()->getUserId();
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
            $result = $this->app->driver->api->Helper()->detailNew($goodsId,$this->_userId);
            if(empty($result)){
                $this->resultSet->error(1002,$this->_error['not_exist']);
            }
            $data['data'] = $result;
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
    //驾校报名
    /**
     * 根据关键词搜索 商品
     * @return void
     * @Route("/sign", methods="POST", name="driving")
     */
    public function signAction(){
        $postData = $this->request->getPost();
        $postData['user_id'] = $this->_userId;
        try{
            if(empty($postData['name']) || empty($postData['cellphone']) ||empty($postData['driving_test_id'])){
                $this->resultSet->error(1001,$this->_error['invalid_input']);
            }
            $verifyPhone = $this->app->core->api->Phone()->checkPhone($postData['cellphone']);
            if (!$verifyPhone) {
                $this->resultSet->error(1002, $this->_error['cellphone']);
            }
            // var_dump($postData);exit;
            $data['data'] = [];
            $id = $this->app->driver->api->Helper()->createDrivingSign($postData);
            if(!empty($id)){
                //$this->resultSet->error(1002,$this->_error['not_exist']);
                $data['data']['id'] = $id;
            }
        }catch (\Exception $e){
            $this->resultSet->error($e->getCode(),$e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());
    }
    //驾校报名列表
    /**
     * 根据关键词搜索 商品
     * @return void
     * @Route("/signlist", methods="GET", name="driving")
     */
    public function signListAction(){
        $page = $this->request->getParam('page',null,1);
        $drivingTestId = $this->request->getParam('id',null,2);
        try{
            $data['data'] = [];
            $ret = $this->app->driver->api->Helper()->drivingSignList($drivingTestId,$page);
            if(!empty($ret)){
                $data['data'] = $ret;
            }
        }catch (\Exception $e){
            $this->resultSet->error($e->getCode(),$e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());
    }
    
}
