<?php

namespace Merchant\Api;

use MDK\Api;
use Merchant\Model\Merchant;
use Merchant\Model\MerchantOperationLog;
use Merchant\Model\MerchantPaymentCode;
use Merchant\Model\MerchantWithdrawApply;
use Order\Model\Order;
use Order\Model\OrderGoods;

class Helper extends Api
{
    private $_config;
    private $_order;
    private $_orderGoodsModel;
    private $_orderModel;
    private $_merchantModel;
    private $_merchantOperationLogModel;
    private $_merchantPaymentCodeModel;
    private $_merchantWithdrawApplyModel;
    private $_applyStatus;

    public function __construct()
    {
        $this->_config = $this->app->core->config->config->toArray();
        $this->_order = $this->app->core->config->order->toArray();
        $this->_orderGoodsModel = new OrderGoods();
        $this->_orderModel = new Order();
        $this->_merchantModel = new Merchant();
        $this->_merchantOperationLogModel = new MerchantOperationLog();
        $this->_merchantPaymentCodeModel = new MerchantPaymentCode();
        $this->_merchantWithdrawApplyModel = new MerchantWithdrawApply();
        $this->_applyStatus = $this->app->merchant->config->withdraw->toArray();
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
        //查询订单
        if (empty($orderId)) {
            throw new \Exception('订单不存在', 10002);
        }
        if($currentTime - $qrcodeCreateTime > $invalidTime){
            //超过五分钟二维码失效
            throw new \Exception('二维码已失效，请刷新页面', 10001);
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

        $scanRet = true;
        //该订单中该商户的均置为确认
        foreach ($orderGoodsIds as $ogid){
           $scanRet = $this->scanOrderGoods($ogid);
           if($scanRet == false){
               break;
           }
        }
        if($scanRet){
            //当所有的都扫过码了，需要自动完成 order_status => finish
            $updateData = [
                'order_id' => $orderId,
                'order_status' => $this->_order['order_status']['finish']['code'],
                'is_manual' => $this->_config['data_status']['valid']
            ];
            try{
                $orderData->update($updateData);
            }catch (\Exception $e){
                return false;
            }

        }

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

    //提现申请 提交金额 上传收款码
    public function withdrawApply($data){
        //校验必填字段 金额、用户商户ID
        if(empty($data['withdraw_amount'])
            || empty($data['apply_user_id'])
            || empty($data['apply_merchant_id'])){
            return [
                'id' => 0,
                'msg' => '请填写正确金额!'
            ];
        }
        //有未处理的申请，不保存本次申请
        $condition = " apply_merchant_id = " . $data['apply_merchant_id'];
        $condition .= " and apply_status = " . $this->_applyStatus['apply_status']['auditing']['code'];
        $condition .= " and status = " . $this->_config['data_status']['valid'];
        $withdrawApplies = $this->_merchantWithdrawApplyModel->find($condition);
        if(!$this->tmpNewEmpty($withdrawApplies)){
            return [
                'id' => 0,
                'msg' => '请等待上一笔提现申请审批通过后，再进行提现申请!'
            ];
        }
        //$withdrawApplies = $withdrawApplies->toArray();
        //当前商户余额小于提现申请的金额
        $condition = "id = " . $data['apply_merchant_id'];
        $condition .= " and status = " . $this->_config['data_status']['valid'];
        $merchantData = $this->_merchantModel->findFirst($condition)->toArray();
        if(empty($merchantData) ){//是否考虑已停业
            return [
                'id' => 0,
                'msg' => '商户不存在，请与平台联系!'
            ];
        }
        if(empty($merchantData['balance']) || $merchantData['balance'] < $data['withdraw_amount']){
            return [
                'id' => 0,
                'msg' => '商户余额不足!'
            ];
        }

        //新建提现申请记录
        $model = $this->_merchantWithdrawApplyModel;
        $insertData = [
            'withdraw_amount' => $data['withdraw_amount'],
            'apply_user_id' => $data['apply_user_id'],
            'apply_merchant_id' => $data['apply_merchant_id'],
            'apply_status' => $this->_applyStatus['apply_status']['auditing']['code'],
            'create_time' => date('Y-m-d H:i:s'),
        ];
        $model->create($insertData);
        if(!empty($model->id)){
            return [
                'id' => $model->id,
                'msg' => '提现申请成功，请耐心等待平台审批！'
            ];
        }else{
            return [
                'id' => 0,
                'msg' => '网络错误，请稍后重试!'
            ];
        }

    }
    public function withdrawList($merchantId){
        $condition = "id = " . $merchantId;
        $condition .= " and status = " . $this->_config['data_status']['valid'];
        $withdrawDatas = $this->_merchantWithdrawApplyModel->find($condition)->toArray();
        if(!empty($withdrawDatas)){
            foreach ($withdrawDatas as &$v){
                $v['apply_description'] = $this->getWithdrawDescription($v['apply_status']);
            }
        }
        return $withdrawDatas;

    }


    //更新提现申请的状态
    public function updateApplyStatus($id,$operateUserId,$applyStatus,$data=[]){
        try {
            $updateModel = $this->_merchantWithdrawApplyModel->findFirstById($id);
            if (empty($updateModel)) {
                return false;
            }
            $updateData = [
                'id' => $id,
                'operate_user_id' => $operateUserId,
                'apply_status' => $applyStatus,
            ];
            if(!empty($data)){
                $updateData = array_merge($updateData,$data);
            }
            $updateModel->update($updateData);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    //上传收款码
    public function uploadPaymentCode($data){
        //校验必填字段 金额、用户商户ID
        if(empty($data['payment_code_image_url'])
            || empty($data['apply_user_id'])
            || empty($data['apply_merchant_id'])){
            return [
                'id' => 0,
                'msg' => '请上传收款码图片!'
            ];
        }
        //当前商户不存在
        $condition = "id = " . $data['apply_merchant_id'];
        $condition .= " and status = " . $this->_config['data_status']['valid'];
        $merchantData = $this->_merchantModel->findFirst($condition);
        if(empty($merchantData) ){//是否考虑已停业
            return [
                'id' => 0,
                'msg' => '商户不存在，请与平台联系!'
            ];
        }


        //新建收款码记录
        $model = $this->_merchantPaymentCodeModel;
        $insertData = [
            'payment_code_image_url' => $data['payment_code_image_url'],
            'apply_user_id' => $data['apply_user_id'],
            'apply_merchant_id' => $data['apply_merchant_id'],
            'create_time' => date('Y-m-d H:i:s'),
        ];
        $model->create($insertData);
        if(!empty($model->id)){
            return [
                'id' => $model->id,
                'msg' => '已成功上传收款码图片！'
            ];
        }else{
            return [
                'id' => 0,
                'msg' => '网络错误，请稍后重试!'
            ];
        }
    }

    public function personalData($merchantId){
        //商户-个人中心 店铺名称、手机号、营业总额、订单总数、今日订单数
        $condition = "id = " . $merchantId;
        $condition .= " and status = " . $this->_config['data_status']['valid'];
        $merchantData = $this->_merchantModel->findFirst($condition);
        if($this->app->core->api->CheckEmpty()->newEmpty($merchantData)){
            return [];
        }
        $merchantData = $merchantData->toArray();
        $ret = [
            'merchant_name'  => $merchantData['name'] ?? '',
            'merchant_cellphone'  => $merchantData['cellphone'] ?? '',
            'merchant_balance'  => $merchantData['balance'] ?? 0,
        ];
        $orderData = $this->app->order->api->Helper()->getOrderData($merchantId);
        if($this->app->core->api->CheckEmpty()->newEmpty($orderData)){
            return [
                'total_sales' => 0,
                'total_orders' => 0,
                'today_sales' => 0,
                'today_orders' => 0,
            ];
        }

        $ret = array_merge($ret,$orderData);


        return $ret;
    }

    public function orderManage($merchantId,$goodsType='',$page=1,$pageSize=10){
        //商户-订单管理 goods_type为空表示全部
        $data = [
            'order_list' => [
                [
                    'order_no' => '',
                    'order_status' => 1,
                    'order_status_description' => '已完成',
                    'pay_status' => 1,
                    'pay_status_description' => '已支付',
                    'shipping_status' => 1,
                    'shipping_status_description' => '已确认收货',
                    'goods_num' => 2,
                    'order_amount' => 20.00,
                    'order_time' => '2020-05-20 10:00:00',
                ]
            ],
        ];
        $orderData['order_list'] = $this->app->order->api->Helper()->orderList($merchantId,$goodsType,$page,$pageSize);
        return $orderData;

    }

    public function myWallet($merchantId){
        //商户-我的钱包 总收入 本月收入
        $walletData = $this->app->order->api->Helper()->wallet($merchantId);
        return $walletData;
    }

    public function bill($merchantId,$datetime,$page=1,$pageSize=10){
        //商户-我的钱包-账单
        //月份(默认当前月份)，收入、支出
        //订单列表：时间、商品名称、类型、收入/支出金额
        $data = [
            'datetime' => date('Y-m'),
            'income' => 100,
            'expend' => 30,
            'order_list' => [
                [
                    'order_time' => date('Y-m-d H:i:s'),
                    'goods_type' => 'goods_type',
                    'goods_type_description' => '生活用品',
                    'goods_name' => '商品名称',
                    'order_income' => 100,
                    'order_expend' => -10,
                ],
                [
                    'order_time' => date('Y-m-d H:i:s'),
                    'goods_type' => 'goods_type',
                    'goods_type_description' => '财务支出',
                    'goods_name' => '余额提醒',
                    'order_income' => 0,
                    'order_expend' => -20,
                ]
            ],
        ];
        $billData = $this->getTmpMerchantBill($datetime,$merchantId,$page,$pageSize);
        // $billData = $this->app->order->api->Helper()->bill($datetime,$merchantId,$page,$pageSize);
        return $billData;
    }
    public function getTmpMerchantBill($datetime,$merchantId=0,$page=1,$pageSize=10){
        $checkDatetime = $this->app->order->api->Helper()->checkDatetime($datetime);
        if(!$checkDatetime){
            return [
                'datetime' => date('Y-m'),
                'income' => 0,
                'expend' => 0,
                'order_list' => [],
            ];
        }
        //查询已完成的订单
        $orderStatusFinish = $this->_order['order_status']['finish']['code'];
        $date = $this->getDateTimeArr($datetime);
        // $date = $this->app->order->api->Helper()->getDateTimeArr($datetime);
        //商户账单
        $all = $this->modelsManager->createBuilder()
            ->columns('ogt.merchant_id,ot.create_time as order_time,ogt.goods_type,ogt.goods_name,ogt.total_amount as order_income,ogt.real_income as order_expend')
            ->from(['ogt'=>'Order\Model\OrderGoods'])
            ->leftjoin('Order\Model\Order', 'ot.order_id = ogt.order_id','ot')
            // ->leftjoin('Order\Model\OrderDetail', 'ot.order_id = odt.order_id','odt')
            ->where('ot.status = :init:',['init'=>$this->_config['data_status']['valid']])
            ->andWhere('ot.order_status = :finish:',['finish' => $orderStatusFinish])
            ->andWhere('ot.create_time >= :month_start: and ot.create_time < :month_end:',['month_start' => $date['month_start'],'month_end' => $date['month_end']])
            ->andWhere('ogt.merchant_id = :merchant_id:',['merchant_id'=>$merchantId])
            // ->andWhere('ogt.goods_type = :goods_type:',['goods_type'=>$goodsType])
            // ->limit($pageSize,$start)
            ->getQuery()
            ->execute();
            // ->toArray();
        //总收入 支出
        //merchant_name
        //goods_type_description
        $data = $this->app->order->api->Helper()->getBillDescription($all,$datetime);
        return $data;
    }
    public function getDateTimeArr($datetime){
        $data = explode('-',$datetime);
        $year = (int) $data[0];
        $month = (int) $data[1];
        if($month==12){
            $date = [
                'month_start' => $year.'-'.$month.'-01 00:00:00'
            ];
            $year = $year+1;
            $date['month_end'] = $year.'-01-01 00:00:00';
        }else{
            $date = [
                'month_start' => $year.'-'.$month.'-01 00:00:00'
            ];
            $month = $month+1;
            $date['month_end'] = $year.'-'.$month.'-01 00:00:00';
        }
        return (array)$date;
    }

    public function getWithdrawDescription($applyStatus){
        $description = '';
        $businessStatusArr = $this->_applyStatus['apply_status'];
        foreach ($businessStatusArr as $v){
            if($v['code'] == $applyStatus){
                $description = $v['title'];
                break;
            }
        }
        return $description;
    }

    /**
     * 验证数据是否为空
     * @param $phone
     * @return bool
     * true--表示为空 false--不为空
     */
    public function tmpNewEmpty($data)
    {
        if(empty($data)){
            return true;
        }
        if(is_object($data) || is_array($data)){
            foreach($data as $k=>$v){
                if($k==='di'){
                    return true;
                }
            }
        }
        if(is_object($data)){
            if(empty($data->toArray())){
                return true;
            }
        }
        return false;

    }









}