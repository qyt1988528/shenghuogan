<?php
namespace Rent\Controller;
use MDK\Controller;


/**
 * Face controller.
 * @RoutePrefix("/showhouse", name="showhouse")
 */
class ShowHouseController extends Controller
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
     * @Route("/", methods="GET", name="showhouse")
     */
    public function indexAction() {
        $page = $this->request->getParam('page',null,1);
        //分页
        try{
            $data['data'] = [];
            $tickets = $this->app->rent->api->House()->getList($page);
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
     * @Route("/detail", methods="GET", name="showhouse")
     */
    public function detailAction(){
        $goodsId = $this->request->getParam('id',null,'');
        if(empty($goodsId)){
            $this->resultSet->error(1001,$this->_error['invalid_input']);
        }
        try{
            $result = $this->app->rent->api->House()->detail($goodsId,$this->_userId);
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
     * @Route("/search", methods="GET", name="showhouse")
     */
    public function searchAction(){
        $keywords = $this->request->getParam('keywords',null,'');
        $room = $this->request->getParam('room',null,0);
        $priceMin = $this->request->getParam('price_min',null,0);
        $priceMax = $this->request->getParam('price_max',null,0);
        $page = $this->request->getParam('page',null,1);
        if(empty($page)){
            $this->resultSet->error(1001,$this->_error['invalid_input']);
        }
        //暂不考虑拼音搜索
        try{
            $data['data'] = [];
            $condition = [
                'title' => trim($keywords),
                'room' => (int)$room,
                'price_min' => (int)$priceMin,
                'price_max' => (int)$priceMax,
                'page' => (int)$page,
            ];
            $result = $this->app->rent->api->House()->search($condition);
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
