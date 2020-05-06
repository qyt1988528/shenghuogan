<?php

namespace Platform\Api;

use MDK\Api;
use Merchant\Model\MerchantOperationLog;
use Order\Model\Order;
use Order\Model\OrderGoods;

class Helper extends Api
{
    private $_config;
    private $_order;
    private $_orderGoodsModel;
    private $_orderModel;
    private $_merchantOperationLogModel;
    private $_certStatus;
    private $_certApi;

    public function __construct()
    {
        $this->_config = $this->app->core->config->config->toArray();
        $this->_order = $this->app->core->config->order->toArray();
        $this->_orderGoodsModel = new OrderGoods();
        $this->_orderModel = new Order();
        $this->_merchantOperationLogModel = new MerchantOperationLog();
        $this->_certStatus = $this->app->core->config->certification->toArray();
        $this->_certApi = $this->app->parttimejob->api->Certification();
    }

    //根据商品类型和商户ID查询所有未删除的商品
    public function getDataByGoodsType($goodsType, $merchantId,$keywords='')
    {
        $goodsTypes = $this->_config['goods_types'];
        if(empty($keywords)){
            $goods = $this->modelsManager->createBuilder()
                ->columns('*')
                ->from(['sg' => $goodsTypes[$goodsType]['model']])
                ->where('sg.merchant_id = :merchant_id: ', ['merchant_id' => $merchantId])
                ->andWhere('sg.status = :valid: ', ['valid' => $this->_config['data_status']['valid']])
                ->getQuery()
                ->execute()
                ->toArray();
        }else{
            $goods = $this->modelsManager->createBuilder()
                ->columns('*')
                ->from(['sg' => $goodsTypes[$goodsType]['model']])
                ->where('sg.merchant_id = :merchant_id: ', ['merchant_id' => $merchantId])
                ->andWhere('sg.status = :valid: ', ['valid' => $this->_config['data_status']['valid']])
                ->andWhere('sg.title like :goodsName: ', ['goodsName' => '%' . $keywords . '%'])
                ->getQuery()
                ->execute()
                ->toArray();

        }
        foreach ($goods as &$gv){
            $gv['sales_count'] = 0;
        }
        return $goods;
    }

    public function getDatasByGoodsTypes($goodsTypes, $merchantId){
        $data = [];

    }

    public function merchantConfirmOrder($orderId,$merchantId,$qrcodeCreateTime){
        $invalidTime =$this->_order['order_qrcode_invalid_time']['code'];//5分钟
        $currentTime = time();
        if($currentTime - $qrcodeCreateTime > $invalidTime){
            //超过五分钟二维码失效
            throw new \Exception('二维码已失效，请刷新页面', 10001);
        }
        //查询订单
        if (empty($orderId)) {
            throw new \Exception('订单不存在', 10002);

        }
        $condition = "order_id = " . $orderId;
        $condition .= " and status = " . $this->_config['data_status']['valid'];
        $orderData = $this->_orderModel->findFirst($condition);
        if (empty($orderData)) {
            //订单不存在
            throw new \Exception('订单不存在', 10003);

        }
        //订单是否支付
        if($orderData->pay_status != $this->_order['pay_status']['success']['code']){
            throw new \Exception('订单未支付', 10004);
        }

        //订单和商户关系的判断
        $condition = "order_id = " . $orderId;
        $condition .= " and status = " . $this->_config['data_status']['valid'];
        $orderGoods = $this->_orderGoodsModel->find($condition)->toArray();
        if (empty($orderGoods)) {
            throw new \Exception('订单无效', 10005);
        }
        $orderGoodsIds = [];
        foreach ($orderGoods as $ogv){
            if($ogv['merchant_id'] == $merchantId){
                $orderGoodsIds[] = $ogv['order_goods_id'];
            }
        }
        if(empty($orderGoodsIds)){
            throw new \Exception('该订单为其他商户，请勿进行扫码', 10006);
        }

        //该订单中该商户的均置为确认
        foreach ($orderGoodsIds as $ogid){
            $this->scanOrderGoods($ogid);
        }
        //当所有的都扫过码了，需要自动完成 order_status => finish

        return true;


    }

    public function scanOrderGoods($orderGoodsId){
        try{
            $condition = "order_goods_id = " . $orderGoodsId;
            $condition .= " and status = " . $this->_config['data_status']['valid'];
            $updateModel = $this->_orderGoodsModel->findFirst($condition);
            // $updateModel = $this->_orderGoodsModel->findFirstById($orderGoodsId);
            if(empty($updateModel)){
                return false;
            }
            $updateData = ['order_goods_id' => $orderGoodsId];
            $updateData['is_scan'] = $this->_order['manual_status']['auto_by_all_scan']['code'];
            $updateData['scan_time'] = date('Y-m-d H:i:s');
            $updateModel->update($updateData);
            return true;
        }catch (\Exception $e){
            return false;
        }
    }

    public function operationLog($merchantId,$userId,$before,$after,$actionName,$goodsType,$goodsId){
        if(empty($userId) || empty($goodsId)){
            return 0;
        }
        $logData = [
            'merchant_id' => $merchantId,
            'user_id' => $userId,
            'before_data' => $before,
            'after_data' => $after,
            'action_name' => $actionName,
            'goods_type' => $goodsType,
            'goods_id' => $goodsId,
        ];
        $model = $this->_merchantOperationLogModel;
        $model->create($logData);
        return !empty($model->id) ? $model->id : 0;


    }

    //获取实名认证的列表(含手机号搜索)
    public function certificationList($cellphone='',$page=1){
        //获得待审核和已通过的单人的最后一条
        return $this->app->parttimejob->api->Certification()->getList($cellphone, $page);
    }

    //实名认证审核 通过 和 拒绝 通过后检查是否有相同手机号的商户，有则绑定
    public function passCertification($certId,$auditUserId){
        //查询该ID是否存在
        if(empty($certId)){
            return false;
        }
        $certData = $this->app->parttimejob->api->Certification()->detail($certId);
        if(empty($certData)){
            return false;
        }
        $certData = $certData->toArray();
        //置为通过
        $certStatus = $this->_certStatus['certification_status']['passed']['code'];
        $updateCertResult = $this->app->parttimejob->api->Certification()->updateCertStatus($certId,$certStatus,$auditUserId);
        if(empty($updateCertResult)){
            return false;
        }
        //查询是否有相同手机号的商户 有则绑定
        // var_dump($certData['cellphone']);exit;
        $merchantData = $this->app->merchant->api->MerchantManage()->detailByCellphone($certData['cellphone']);
        if(!empty($merchantData)){
            $merchantData = $merchantData->toArray();
            //绑定商户
            $userId = $certData['upload_user_id'];
            $merchantId = $merchantData['id'];
            $this->app->tencent->api->UserApi()->bindMerchant($userId, $merchantId);
        }
        return true;
    }
    //实名认证不通过
    public function refuseCertification($certId,$auditUserId){
        //查询该ID是否存在
        if(empty($certId)){
            return false;
        }
        $certData = $this->app->parttimejob->api->Certification()->detail($certId);
        if(empty($certData)){
            return false;
        }
        //置为拒绝
        $certStatus = $this->_certStatus['certification_status']['refused']['code'];
        return $this->app->parttimejob->api->Certification()->updateCertStatus($certId,$certStatus,$auditUserId);
    }







}