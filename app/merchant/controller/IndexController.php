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
    private $_config;

    public function initialize()
    {
        $this->_config = $this->app->core->config->config->toArray();
        $this->_error = $this->_config['error_message'];
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
     * @Route("/goodsTypeList", methods="POST", name="merchant")
     */
    public function goodsTypeListAction()
    {
        try{
            $goodsTypes = $this->_config['goods_types'];
            $data['data'] = [
                'goods_type_list' => $goodsTypes
            ];
        }catch (\Exception $e){
            $this->resultSet->error($e->getCode(),$e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());

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
        //sudo find ./ -name ".DS_Store" -depth -exec rm {} \;
        // var_dump($this->get_client_ip());exit;
        $goodsType = $this->request->getPost('goods_type');
        $keywords = $this->request->getPost('keywords');
        if(empty($goodsType)){
            $this->resultSet->error(1001,$this->_error['invalid_input']);
        }
        try{
            $result = $this->app->merchant->api->Helper()->getDataByGoodsType($goodsType, $this->_merchantId,$keywords);
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

    public function get_client_ip()
    {
        $preg = "/\A((([0-9]?[0-9])|(1[0-9]{2})|(2[0-4][0-9])|(25[0-5]))\.){3}(([0-9]?[0-9])|(1[0-9]{2})|(2[0-4][0-9])|(25[0-5]))\Z/";
        exec("ifconfig", $out, $stats);
        if (!empty($out)) {
            if (isset($out[1]) && strstr($out[1], 'addr:')) {
                $tmpArray = explode(":", $out[1]);
                $tmpIp = explode(" ", $tmpArray[1]);
                if (preg_match($preg, trim($tmpIp[0]))) {
                    return trim($tmpIp[0]);
                }
            }
        }
        return '127.0.0.1';
    }

}
