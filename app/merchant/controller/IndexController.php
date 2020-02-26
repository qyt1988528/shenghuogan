<?php

namespace Merchant\Controller;

use MDK\Controller;


/**
 * Index controller.
 * @RoutePrefix("/merchant", name="merchant")
 */
class IndexController extends Controller
{
    private $_error;
    private $_userId;
    private $_merchantId;

    public function initialize()
    {
        $config = $this->app->core->config->config->toArray();
        $this->_error = $config['error_message'];
        //验证用户是否登录
        $this->_userId = $this->app->tencent->api->UserApi()->getUserId();
        if (empty($this->_userId)) {
            $this->resultSet->error(1010, $this->_error['unlogin']);
            exit;
        }
        //验证是否为商户
        $this->_merchantId = $this->app->tencent->api->UserApi()->getMerchantIdByUserId($this->_userId);
        if (empty($this->_merchantId)) {
            $this->resultSet->error(1011, $this->_error['unmerchant']);
            exit;
        }
    }
    /**
     * 商户商品列表
     * Create action.
     * @return void
     * @Route("/goodsList", methods="POST", name="merchant")
     */
    public function goodsListAction()
    {


    }
    /**
     * 商户指定商品类别的商品列表
     * Create action.
     * @return void
     * @Route("/goodsListByType", methods="POST", name="merchant")
     */
    public function goodsListByTypeAction()
    {
        $goodsType = $this->request->getPost('goods_type');
        if(empty($goodsType)){
            $this->resultSet->error(1001,$this->_error['invalid_input']);
        }
        try{
            $result = $this->app->merchant->api->Helper()->getDataByGoodsType($goodsType, $this->_merchantId);
            if(!empty($result)){
                $data['data'] = [
                    'goods_list' => $result
                ];
            }else{
                $data['data'] = [
                    'goods_list' => []
                ];
            }
        }catch (\Exception $e){
            $this->resultSet->error($e->getCode(),$e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());


    }

}
