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

    /**
     * 商户扫码确认
     * Create action.
     * @return void
     * @Route("/confirm", methods="POST", name="merchant")
     */
    public function confirmAction(){
        $qrcodeCreateTime = $this->request->getPost('create_time');
        $orderId = $this->request->getPost('order_id');
        $merchantId = $this->_merchantId;

        try{
            $result = $this->app->merchant->api->Helper()->merchantConfirmOrder($orderId,$merchantId,$qrcodeCreateTime);
            $data['data'] = [
                'scan_result' => $result
            ];
        }catch (\Exception $e){
            $this->resultSet->error($e->getCode(),$e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());
    }

    //个人中心
    /**
     * 个人中心
     * @return void
     * @Route("/personal", methods="GET", name="merchant")
     */
    public function personalAction(){
        $merchantId = $this->_merchantId;
        try{
            $result = $this->app->merchant->api->Helper()->personalData($merchantId);
            if(empty($result)){
                $result = [];
            }
            $data['data'] = $result;
        }catch (\Exception $e){
            $this->resultSet->error($e->getCode(),$e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());
    }
    //订单管理
    /**
     * 个人中心
     * @return void
     * @Route("/orderList", methods="GET", name="merchant")
     */
    public function orderListAction(){
        $merchantId = $this->_merchantId;
        $goodsType = $this->request->getParam('goods_type', null, '');
        try{
            $result = $this->app->merchant->api->Helper()->orderManage($merchantId,$goodsType);
            $data['data'] = $result;
        }catch (\Exception $e){
            $this->resultSet->error($e->getCode(),$e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());
    }
    //我的钱包 总收入、本月收入
    /**
     * 个人中心
     * @return void
     * @Route("/myWallet", methods="GET", name="merchant")
     */
    public function myWalletAction(){
        $merchantId = $this->_merchantId;
        try{
            $result = $this->app->merchant->api->Helper()->myWallet($merchantId);
            $data['data'] = $result;
        }catch (\Exception $e){
            $this->resultSet->error($e->getCode(),$e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());
    }
    //商家-财务管理
    //账单(时间段、收入、支出、明细)、商家提现(提现记录)
    /**
     * 个人中心
     * @return void
     * @Route("/bill", methods="GET", name="merchant")
     */
    public function billAction(){
        $merchantId = $this->_merchantId;
        $currentDate = date('Y-m');
        $datetime = $this->request->getParam('datetime', null, $currentDate);
        try{
            $result = $this->app->merchant->api->Helper()->bill($merchantId,$datetime);
            if(empty($result)){
                $result = [];
            }
            $data['data'] = $result;
        }catch (\Exception $e){
            $this->resultSet->error($e->getCode(),$e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());
    }
    /**
     * 提现申请
     * Create action.
     * @return void
     * @Route("/withdrawApply", methods="POST", name="merchant")
     */
    public function withdrawApplyAction(){
        $withdrawAmount = $this->request->getParam('withdraw_amount', null, 0);
        try{
            $data = [
                'withdraw_amount' => $withdrawAmount,
                'apply_user_id' => $this->_userId,
                'apply_merchant_id' => $this->_merchantId,
            ];
            $result = $this->app->merchant->api->Helper()->withdrawApply($data);
            if(empty($result) || empty($result['id'])){
                if(isset($result['msg']) && !empty($result['msg'])){
                    $this->resultSet->error(1010, $result['msg']);
                }else{
                    $this->resultSet->error(1011, $this->_error['invalid_input']);
                }

            }else{
                $data['data'] = $result;
            }
        }catch (\Exception $e){
            $this->resultSet->error($e->getCode(),$e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());

    }
    /**
     * 上传收款码
     * Create action.
     * @return void
     * @Route("/paymentCode", methods="POST", name="merchant")
     */
    public function uploadPaymentCodeAction(){
        $imageUrl = $this->request->getParam('payment_image_url', null, '');
        try{
            $data = [
                'payment_code_image_url' => $imageUrl,
                'apply_user_id' => $this->_userId,
                'apply_merchant_id' => $this->_merchantId,
            ];
            $result = $this->app->merchant->api->Helper()->uploadPaymentCode($data);
            if(empty($result) || empty($result['id'])){
                if(isset($result['msg']) && !empty($result['msg'])){
                    $this->resultSet->error(1010, $result['msg']);
                }else{
                    $this->resultSet->error(1011, $this->_error['invalid_input']);
                }

            }else{
                $data['data'] = $result;
            }
        }catch (\Exception $e){
            $this->resultSet->error($e->getCode(),$e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());

    }
    /**
     * 已申请提现列表
     * Create action.
     * @return void
     * @Route("/withdrawApplyList", methods="GET", name="merchant")
     */
    public function withdrawApplyListAction(){
        $merchantId = $this->_merchantId;
        try{
            $result = $this->app->merchant->api->Helper()->withdrawList($merchantId);
            $data['data'] = $result;
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
