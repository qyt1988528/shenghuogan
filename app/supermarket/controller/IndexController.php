<?php
namespace Supermarket\Controller;
use MDK\Controller;
use function Qiniu\waterImg;


/**
 * index controller.
 * @RoutePrefix("/supermarket", name="supermarket")
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
     * @Route("/", methods="GET", name="supermarket")
     */
    public function indexAction() {
        //分页
        $page = $this->request->getParam('page',null,1);
        $config = $this->app->core->config->config->toArray();
        $supermarketGoodsType = $config['supermarket_goods_type'];
        $typeId = $this->request->getParam('type_id',null,0);
        if(empty($typeId) || !isset($supermarketGoodsType[$typeId])){
            $typeId = 0;
        }
        try{
            $supermarketData = $this->app->supermarket->api->Helper()->getIndexData($typeId,$page);
            if(empty($supermaketData)){
                $this->resultSet->error(1002,$this->_error['not_exist']);
            }
            $data['data'] = $supermarketData;
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
     * @Route("/detail", methods="GET", name="supermarket")
     */
    public function detailAction(){
        $goodsId = $this->request->getParam('id',null,'');
        if(empty($goodsId)){
            $this->resultSet->error(1001,$this->_error['invalid_input']);
        }
        try{
            $result = $this->app->supermarket->api->Helper()->detail($goodsId);
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
     * @Route("/search", methods="GET", name="supermarket")
     */
    public function searchAction(){
        $keywords = $this->request->getParam('keywords',null,'');
        $typeId = $this->request->getParam('type_id',null,0);
        $config = $this->app->core->config->config->toArray();
        $supermarketGoodsType = $config['supermarket_goods_type'];
        //暂不考虑拼音搜索
        try{
            if(empty($typeId) || !isset($supermarketGoodsType[$typeId])){
                $result = $this->app->supermarket->api->Helper()->search($keywords);
            }else{
                $result = $this->app->supermarket->api->Helper()->search($keywords,$typeId);
            }
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
     * 商品规格单位
     * @return void
     * @Route("/specs", methods="GET", name="supermarket")
     */
    public function specsAction(){
        $config = $this->app->core->config->config->toArray();
        try{
            $data['data']['supermarket_specs_unit'] = $config['supermarket_specs_unit'];
        }catch (\Exception $e){
            $this->resultSet->error($e->getCode(),$e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());
    }
    /**
     * 商品类型
     * @return void
     * @Route("/types", methods="GET", name="supermarket")
     */
    public function typeAction(){
        $config = $this->app->core->config->config->toArray();
        try{
            $data['data']['supermarket_goods_type'] = $config['supermarket_goods_type'];
        }catch (\Exception $e){
            $this->resultSet->error($e->getCode(),$e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());
    }
}
