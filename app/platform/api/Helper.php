<?php

namespace Platform\Api;

use MDK\Api;
use Merchant\Model\MerchantOperationLog;
use Merchant\Model\MerchantPaymentCode;
use Merchant\Model\MerchantWithdrawApply;
use Order\Model\Order;
use Order\Model\OrderGoods;
use Platform\Model\PlatformImage;
use function Qiniu\waterImg;
use Tencent\Model\User;

class Helper extends Api
{
    private $_config;
    private $_order;
    private $_orderGoodsModel;
    private $_orderModel;
    private $_userModel;
    private $_merchantOperationLogModel;
    private $_merchantWithdrawApplyModel;
    private $_merchantPaymentCodeModel;
    private $_certStatus;
    private $_certApi;
    private $_applyStatus;

    public function __construct()
    {
        $this->_config = $this->app->core->config->config->toArray();
        $this->_order = $this->app->core->config->order->toArray();
        $this->_orderGoodsModel = new OrderGoods();
        $this->_orderModel = new Order();
        $this->_userModel = new User();
        $this->_merchantOperationLogModel = new MerchantOperationLog();
        $this->_merchantWithdrawApplyModel = new MerchantWithdrawApply();
        $this->_merchantPaymentCodeModel = new MerchantPaymentCode();
        $this->_certStatus = $this->app->core->config->certification->toArray();
        $this->_certApi = $this->app->parttimejob->api->Certification();
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
        // return $this->app->parttimejob->api->Certification()->getList($cellphone, $page);
        return $this->getTmpCertList($cellphone,$page);
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
        $certData = $this->app->core->api->CheckEmpty()->newToArray($certData);
        // $certData = $certData->toArray();
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


    //商户提现申请 平台打款操作
    public function passMerchantWithdraw($data){
        $id = $data['id'] ?? 0;
        if(empty($id)){
            return [
                'id' => 0,
                'msg' => '数据错误，请稍后重试!'
            ];
        }
        $applyData = $this->_merchantWithdrawApplyModel->findFirstById($id)->toArray();
        //申请ID不存在 或 已被处理
        if(empty($applyData) || $applyData['apply_status'] != $this->_applyStatus['apply_status']['auditing']['code']){
            return [
                'id' => 0,
                'msg' => '数据错误或已被处理!'
            ];
        }
        //获取该商户的最后一次上传的付款码 没有则无法完成
        $condition = " and status = " . $this->_config['data_status']['valid'];
        $paymentData = $this->_merchantPaymentCodeModel->find($condition)->toArray();

        $operateUserId = $data['user_id'] ?? 0;
        $applyStatus = $this->_applyStatus['apply_status']['passed']['code'];
        $otherField = [
            'remarks' => $data['remarks'] ?? '',
            'wechat_order_no' => $data['wechat_order_no'] ?? '',
            'merchant_payment_code_id' => $paymentData['id'] ?? 0,
        ];
        $updateRet = $this->app->merchant->api->Helper()->updateApplyStatus($id,$operateUserId,$applyStatus,$otherField);
        return $updateRet;

    }
    //商户提现申请 平台拒绝操作
    public function refuseMerchantWithdraw($data){
        //拒绝时加备注
        $id = $data['id'] ?? 0;
        $operateUserId = $data['user_id'] ?? 0;
        $applyStatus = $this->_applyStatus['apply_status']['refused']['code'];
        $otherField = [
            'remarks' => $data['remarks'] ?? '',
            'wechat_order_no' => $data['wechat_order_no'] ?? '',
            'merchant_payment_code_id' => $paymentData['id'] ?? 0,
        ];
        $updateRet = $this->app->merchant->api->Helper()->updateApplyStatus($id,$operateUserId,$applyStatus,$otherField);
        return $updateRet;
    }
    //商户提现申请列表
    public function withdrawApplyList(){
        $condition = " status = " . $this->_config['data_status']['valid'];
        $withdrawDatas = $this->_merchantWithdrawApplyModel->find($condition)->toArray();
        if(!empty($withdrawDatas)){
            foreach ($withdrawDatas as &$v){
                $v['apply_description'] = $this->app->merchant->api->Helper()->getWithdrawDescription($v['apply_status']);
            }
        }
        return $withdrawDatas;
    }



    public function personalData($userId){
        //平台-个人中心 名称、手机号、营业总额、订单总数、用户总数、今日营业额、今日订单数、今日新增用户数
        $condition = "id = " . $userId;
        $condition .= " and status = " . $this->_config['data_status']['valid'];
        $merchantData = $this->_userModel->findFirst($condition)->toArray();
        if($this->app->core->api->CheckEmpty()->newEmpty($merchantData)){
            return [];
        }
        $merchantData = $merchantData->toArray();
        $ret = [
            'platform_name'  => $merchantData['name'] ?? '',
            'platform_cellphone'  => $merchantData['cellphone'] ?? '',
        ];
        $orderData = $this->app->order->api->Helper()->getOrderData(0);
        if($this->app->core->api->CheckEmpty()->newEmpty($orderData)){
            return [
                'total_sales' => 0,
                'total_orders' => 0,
                'total_users' => 0,
                'today_sales' => 0,
                'today_orders' => 0,
                'today_users' => 0,
            ];
        }
        $ret = array_merge($ret,$orderData);



        return $ret;
    }

    public function orderManage($goodsType='',$page=1,$pageSize=10){
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
        $orderData['order_list'] = $this->app->order->api->Helper()->orderList(0,$goodsType,$page,$pageSize);
        return $orderData;

    }

    public function myWallet(){
        //平台-财务管理 总收入 本月收入
        $walletData = $this->app->order->api->Helper()->wallet(0);
        return $walletData;

    }

    public function bill($datetime,$page=1,$pageSize=10){
        //商户-我的钱包-账单
        //月份(默认当前月份)，收入、支出
        //订单列表：商家ID、商家名称、时间、商品名称、类型、收入/支出金额
        //订单列表：时间、商品名称、类型、收入/支出金额
        $data = [
            'datetime' => date('Y-m'),
            'income' => 100,
            'expend' => 30,
            'order_list' => [
                [
                    'merchant_id' => 1,
                    'merchant_name' => 'test1',
                    'order_time' => date('Y-m-d H:i:s'),
                    'goods_type' => 'goods_type',
                    'goods_type_description' => '生活用品',
                    'goods_name' => '商品名称',
                    'order_income' => 100,
                    'order_expend' => -10,
                ],
                [
                    'merchant_id' => 2,
                    'merchant_name' => 'test2',
                    'order_time' => date('Y-m-d H:i:s'),
                    'goods_type' => 'goods_type',
                    'goods_type_description' => '财务支出',
                    'goods_name' => '余额提醒',
                    'order_income' => 0,
                    'order_expend' => -20,
                ]
            ],
        ];
        $billData = $this->getTmpMerchantBill($datetime,0,$page,$pageSize);
        // $billData = $this->app->order->api->Helper()->bill($datetime,0,$page,$pageSize);
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

        //平台账单
        $all = $this->modelsManager->createBuilder()
            ->columns('ogt.merchant_id,ot.create_time as order_time,ogt.goods_type,ogt.goods_name,ogt.total_amount as order_income,ogt.real_income as order_expend')
            ->from(['ogt'=>'Order\Model\OrderGoods'])
            ->leftjoin('Order\Model\Order', 'ot.order_id = ogt.order_id','ot')
            // ->leftjoin('Order\Model\OrderDetail', 'ot.order_id = odt.order_id','odt')
            ->where('ot.status = :init:',['init'=>$this->_config['data_status']['valid']])
            ->andWhere('ot.order_status = :finish:',['finish' => $orderStatusFinish])
            ->andWhere('ot.create_time >= :month_start: and ot.create_time < :month_end:',['month_start' => $date['month_start'],'month_end' => $date['month_end']])
            // ->andWhere('ogt.merchant_id = :merchant_id:',['merchant_id'=>$merchantId])
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


    /**
     * @param string $keywords 此处为手机号 仅显示 待审核 和 已通过的
     * @param int $page
     * @param int $pageSize
     * @return mixed
     */
    public function getTmpCertList($keywords='',$page = 1, $pageSize = 20)
    {
        $keywords = trim($keywords);
        $keywords = str_replace('%','',$keywords);
        $keywords = str_replace(' ','',$keywords);
        $start = ($page - 1) * $pageSize;
        if(!empty($keywords)){
            $merchants = $this->modelsManager->createBuilder()
                ->columns('*')
                ->from(['sg' => 'Parttimejob\Model\CertificationRecord'])
                ->andWhere('sg.status = :valid: ', ['valid' => $this->_config['data_status']['valid']])
                ->andWhere('sg.cellphone like :goodsName:', ['goodsName' => '%' . $keywords . '%'])
                ->andWhere('sg.certification_status = :pass: or sg.certification_status = :auditing:',
                    ['pass' => $this->_certStatus['certification_status']['passed']['code'],
                        'auditing'=>$this->_certStatus['certification_status']['auditing']['code']])
                ->limit($start, $pageSize)
                ->getQuery()
                ->execute()
                ->toArray();
        }else{
            $merchants = $this->modelsManager->createBuilder()
                ->columns('*')
                ->from(['sg' => 'Parttimejob\Model\CertificationRecord'])
                ->andWhere('sg.status = :valid: ', ['valid' => $this->_config['data_status']['valid']])
                ->andWhere('sg.certification_status = :pass: or sg.certification_status = :auditing:',
                    ['pass' => $this->_certStatus['certification_status']['passed']['code'],
                        'auditing'=>$this->_certStatus['certification_status']['auditing']['code']])
                ->limit($start, $pageSize)
                ->getQuery()
                ->execute()
                ->toArray();
        }
        if(!empty($merchants)){
            foreach ($merchants as &$v){
                $v['certification_status_description'] = $this->getTmpCertificationDescription($v['certification_status']);
            }
        }
        return $merchants;
    }

    public function getTmpCertificationDescription($businessStatus){
        $description = '';
        $businessStatusArr = $this->_certStatus['certification_status'];
        foreach ($businessStatusArr as $v){
            if($v['code'] == $businessStatus){
                $description = $v['title'];
                break;
            }
        }
        return $description;

    }

    public function saveImage($data){
        try {
            $insertData = [
                'img_url' => $data['img_url'] ?? '',
                'type' => $data['type'] ?? 0,
                'upload_user_id' => $data['upload_user_id'] ?? 0,
                'create_time' => date('Y-m-d H:i:s'),
            ];
            $model = new PlatformImage();
            $model->create($insertData);
            return !empty($model->id) ? $model->id : 0;
        } catch (\Exception $e) {
            // var_dump($e->getMessage());exit;
            return 0;
        }

    }

    public function getCoverData(){
        $cover = $this->modelsManager->createBuilder()
            ->columns('id,img_url,type')
            ->from(['sg' => 'Platform\Model\PlatformImage'])
            ->where('sg.type = :type: ', ['type' => 1])
            ->andWhere('sg.status = :valid: ', ['valid' => $this->_config['data_status']['valid']])
            ->orderBy('id desc')
            ->getQuery()
            ->getSingleResult();
            // ->toArray();
        if(!empty($cover)){
            $cover = $cover->toArray();
        }
        return $cover;
    }





}