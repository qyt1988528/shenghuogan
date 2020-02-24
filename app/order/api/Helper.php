<?php

namespace Order\Api;

use MDK\Api;
use Order\Model\Order;
use Order\Model\OrderDetail;
use Order\Model\OrderGoods;
use Parttimejob\Model\Parttimejob;

class Helper extends Api
{

    //支付类型
    const TYPE_WECHAT = 10;
    const TYPE_ALIPAY = 20;

    //支付渠道
    const CHANNEL_WECHAT_JSAPI = 1010; //微信公众号
    const CHANNEL_WECHAT_APP = 1020; //微信APP
    const CHANNEL_WECHAT_MINI_PRO = 1030; //微信小程序

    const CHANNEL_ALIPAY_WAP = 2010; //支付宝手机网站
    const CHANNEL_ALIPAY_APP = 2020; //支付宝APP
    const CHANNEL_ALIPAY_WEB = 2030; //支付宝网站

    //支付状态
    const PAY_STATUS_WAIT = 1; //待付款
    const PAY_STATUS_INVALID = 2; //已失效
    const PAY_STATUS_CLOSE = 4; //已关闭
    const PAY_STATUS_SUCCESS = 3; //已支付
    const PAY_STATUS_REFUNDING = 5; //退款中
    const PAY_STATUS_REFUNDED = 6; //已退款

    //订单状态
    const ORDER_STATUS_INIT = 1;//初始
    const ORDER_STATUS_FINISH = 2;//完成
    const ORDER_STATUS_CLOSE = 3;//失效
    const ORDER_STATUS_RETREAT = 4;//已退款

    //收货状态
    const SHIPPED_STATUS_WAIT_SEND = 1;//待发货
    const SHIPPED_STATUS_WAIT_RECEIVE = 2;//待收货
    const SHIPPED_STATUS_FINISH_RECEIVE = 3;//已收货(待评价)

    //订单失效时间
    const ORDER_INVALID_TIME = 900;//900秒 15分钟


    private $_config;
    private $_order;
    private $_model;
    private $_orderModel;
    private $_orderDetailModel;
    private $_orderGoodsModel;
    private $_invalid_time;

    public function __construct()
    {
        $this->_config = $this->app->core->config->config->toArray();
        $this->_order = $this->app->core->config->order->toArray();
        $this->_model = new Parttimejob();
        $this->_orderModel = new Order();
        $this->_orderDetailModel = new OrderDetail();
        $this->_orderGoodsModel = new OrderGoods();
        $this->_invalid_time = 1800;//30分钟
    }

    public function createOrder($goodsData, $userId, $addressId, $couponNo = '')
    {

        $needAddress = false;
        $goodsTypes = $this->_config['goods_types'];
        $goodsAmount = 0;
        $orderAmount = 0;
        $couponAmount = 0;//本期不考虑优惠券
        $orderGoodsInsertDatas = [];
        //事务begin
        $this->db->begin();
        //写订单表
        $orderModel = new Order();
        $orderModel->order_no = $this->createOrderNo();
        $orderModel->user_id = $userId;
        //本期不考虑优惠券
        $orderModel->goods_amount = $goodsAmount;
        $orderModel->order_amount = $orderAmount;
        $orderModel->pay_status = $this->_order['pay_status']['wait']['code'];
        $orderModel->order_status = $this->_order['order_status']['init']['code'];
        $orderModel->order_invalid_time = time() + $this->_order['order_invalid_time']['code'];
        $orderModel->form_id = '';
        $orderModel->add_timestamp = strtotime(date('Y-m-d'));
        $orderModel->create_time = date('Y-m-d H:i:s');

        if ($orderModel->save() === false) {
            $this->db->rollback();
            throw new \Exception('网络异常，请稍后重试', 1007);
        }
        $orderId = $orderModel->order_id;

        // var_dump($goodsData, $userId, $addressId,$couponNo);exit;
        //参数校验
        foreach ($goodsData as $gd) {
            if (empty($gd['goods_id']) || empty($gd['goods_type']) || empty($gd['goods_num'])) {
                //商品id、类型、购买数量均不能为空
                $this->db->rollback();
                throw new \Exception('信息有误', 1001);
            }
            if (!isset($goodsTypes[$gd['goods_type']])) {
                $this->db->rollback();
                throw new \Exception('类型有误', 1002);
            }
            // var_dump($goodsTypes[$gd['goods_type']]['model']);exit;
            //确认商品是否存在
            $goods = $this->modelsManager->createBuilder()
                ->columns('*')
                // ->columns('id,stock,title,img_url,original_price,self_price,description,location,is_recommend,sort,base_fav_count,base_order_count')
                ->from(['sg' => $goodsTypes[$gd['goods_type']]['model']])
                ->where('sg.id = :goods_id: ', ['goods_id' => $gd['goods_id']])
                ->andWhere('sg.status = :valid: ', ['valid' => $this->_config['data_status']['valid']])
                ->limit(1)
                ->getQuery()
                // ->execute();
                ->getSingleResult();

            $desc = $goodsTypes[$gd['goods_type']]['desc'];
            if (empty($goods)) {
                $this->db->rollback();
                throw new \Exception($desc . '不存在', 1003);
            }
            if (isset($goods->is_selling)) {
                //是否在售(美食、驾考、酒店、失物招领、租车、二手物品、超市、门票)
                if ($goods->is_selling != $this->_config['selling_status']['selling']) {
                    $this->db->rollback();
                    throw new \Exception($desc . '已下架', 1004);

                }
                //库存是否满足要求
                if ($goods->stock < $gd['goods_num']) {
                    $this->db->rollback();
                    throw new \Exception($desc . '库存不足', 1005);
                }
                //超市 -- 需要填写配送地址
                if($gd['goods_type'] == 'supermarket_goods'){
                    $needAddress = true;

                }
                //超市、门票、酒店、美食、租车、二手物品、驾考
                //失物招领
                $orderGoodsInsertDatas[] = [
                    // 'order_id',
                    'goods_id' => $goods->id,
                    'goods_name' => $goods->title ?? '',
                    'goods_num' => $gd['goods_num'],
                    'goods_amount' => $goods->original_price ?? 0,
                    'goods_cost_amount' => $goods->cost_price ?? 0,
                    'goods_current_amount' => $goods->self_price ?? 0,
                    'goods_type' => $gd['goods_type'],
                    'goods_attr' => $this->getSpecs($goods),
                    'goods_cover' => $this->getGoodsCover($goods->img_url),
                    'goods_detail_data' => $this->getJsonGoodsData($goods),
                ];
            }
            if (isset($goods->is_renting)) {
                //是否出租(租房)
                if ($goods->is_renting != $this->_config['renting_status']['renting']) {
                    $this->db->rollback();
                    throw new \Exception($desc . '已下架', 1004);
                }
                $orderGoodsInsertDatas[] = [
                    // 'order_id',
                    'goods_id' => $goods->id,
                    'goods_name' => $goods->title ?? '',
                    'goods_num' => $gd['goods_num'],
                    'goods_amount' => $goods->original_price,
                    'goods_cost_amount' => $goods->cost_price ?? 0,
                    'goods_current_amount' => $goods->self_price ?? 0,
                    'goods_type' => $gd['goods_type'],
                    'goods_attr' => $this->getSpecs($goods),
                    'goods_cover' => $this->getGoodsCover($goods->img_url??''),
                    'goods_detail_data' => $this->getJsonGoodsData($goods),
                ];

            }
            if (isset($goods->is_hiring)) {
                //是否在招人(代发快递、代取快递、兼职)
                if ($goods->is_hiring != $this->_config['hiring_status']['hiring']) {
                    $this->db->rollback();
                    throw new \Exception($desc . '已下架', 1004);
                }
                //兼职
                if(true){
                    //commission

                }
                //快递
                if(true){
                    //发快递 gratuity
                    //取快递 total_price

                }
                $orderGoodsInsertDatas[] = [
                    // 'order_id',
                    'goods_id' => $goods->id,
                    'goods_name' => $goods->title ?? '',
                    'goods_num' => $gd['goods_num'],
                    'goods_amount' => $goods->total_price,
                    'goods_cost_amount' => $goods->total_price ?? 0,
                    'goods_current_amount' => $goods->total_price ?? 0,
                    'goods_type' => $gd['goods_type'],
                    'goods_attr' => $this->getSpecs($goods),
                    'goods_cover' => $this->getGoodsCover($goods->img_url ?? ''),
                    'goods_detail_data' => $this->getJsonGoodsData($goods),
                ];
            }

        }
        if ($needAddress && empty($addressId)) {
            //需要传地址
            $this->db->rollback();
            throw new \Exception('请填写地址', 1006);
        }



        //写订单详情表
        $orderDetailModel = new OrderDetail();
        $orderDetailModel->order_id = $orderId;
        if($needAddress && !empty($addressId)){

        }
        $orderDetailModel->create_time = date('Y-m-d H:i:s');
        if ($orderDetailModel->save() === false) {
            $this->db->rollback();
            throw new \Exception('网络异常，请稍后重试', 1008);
        }
        //写订单的商品信息表
        foreach ($orderGoodsInsertDatas as $v) {
            $orderGoodsModel = new OrderGoods();
            $orderGoodsModel->order_id = $orderId;
            $orderGoodsModel->goods_id = $v['goods_id'];
            $orderGoodsModel->goods_name = $v['goods_name'];
            $orderGoodsModel->goods_num = $v['goods_num'];
            $orderGoodsModel->goods_amount = $v['goods_amount'];
            $orderGoodsModel->goods_cost_amount = $v['goods_cost_amount'];
            $orderGoodsModel->goods_current_amount = $v['goods_current_amount'];
            $orderGoodsModel->goods_type = $v['goods_type'];
            $orderGoodsModel->goods_attr = $v['goods_attr'];
            $orderGoodsModel->goods_cover = $v['goods_cover'];
            $orderGoodsModel->goods_detail_data = $v['goods_detail_data'];
            $orderGoodsModel->create_time = date('Y-m-d H:i:s');
            if ($orderGoodsModel->save() === false) {
                $this->db->rollback();
                throw new \Exception('网络异常，请稍后重试', 1009);
            }
        }
        //事务commit
        $this->db->commit();
        return $orderId;
    }

    public function getInsertFields()
    {
        return $insertFields = [
            'title',
            'description',
            'commission',
            'location',
            'cellphone',
            'qq',
            'wechat',
        ];
    }

    public function getDefaultInsertFields($postData)
    {
        $defaultInsertFields = [
            'is_hiring' => $this->_config['hiring_status']['hiring'],
            'create_time' => date('Y-m-d H:i:s'),
            'publish_time' => date('Y-m-d H:i:s'),
        ];
        if (!empty($postData['title'])) {
            $defaultInsertFields['title_pinyin'] = $this->app->core->api->Pinyin()->getpy($postData['title']);
        }
        //is_recommend、sort、update_time、status采用默认值
        return $defaultInsertFields;
    }

    public function createParttimejob($postData)
    {
        try {
            $insertData = $this->getDefaultInsertFields($postData);
            foreach ($this->getInsertFields() as $v) {
                $insertData[$v] = $postData[$v];
            }
            $model = $this->_model;
            $model->create($insertData);
            return !empty($model->id) ? $model->id : 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    public function updateParttimejob($postData)
    {
        try {
            $updateData = ['id' => $postData['id']];
            $updateModel = $this->_model->findFirstById($postData['id']);
            if (empty($updateModel)) {
                return false;
            }
            foreach ($this->getInsertFields() as $v) {
                $updateData[$v] = $postData[$v];
            }
            $updateModel->update($updateData);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    //下架
    public function withdrawParttimejob($orderId)
    {
        try {
            $updateModel = $this->_model->findFirstById($orderId);
            if (empty($updateModel)) {
                return false;
            }
            $updateData = [
                'id' => $orderId,
                'is_hiring' => $this->_config['hiring_status']['unhiring'],
            ];
            $updateModel->update($updateData);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function deleteParttimejob($orderId)
    {
        try {
            $invalid = $this->_config['data_status']['invalid'];
            $updateModel = $this->_model->findFirstById($orderId);
            if (empty($updateModel)) {
                return false;
            }
            $updateData = [
                'id' => $orderId,
                'status' => $invalid,
            ];
            $updateModel->update($updateData);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function detail($orderId)
    {
        $condition = "id = " . $orderId;
        $condition .= " and is_hiring = " . $this->_config['hiring_status']['hiring'];
        $condition .= " and status = " . $this->_config['data_status']['valid'];
        $goods = $this->_model->findFirst($condition);
        return $goods;
    }

    public function search($goodsName)
    {
        /*
        $slideAds = $this->modelsManager->createBuilder()
            ->columns('cts.image_ratio,cts.image,cts.jump_url,cts.title,cts.ga_name,vc.condition')
            ->from(['cts'=>'Supermarket\Model\SupermarketGoods'])
            ->join('Core\Model\VersionControl','vc.table_id = cts.id and vc.table_name="cms_home_top_slide"','vc','LEFT')
            ->where('is_selling = :selling: and status = :valid: and title like :goodsName:',['store'=>$store])
            ->andWhere('b.store = :store: AND b.active=1 and platforms like :systemType:',['store'=>$store,'systemType' => '%'.$systemType.'%'])
            ->orderBy('sort desc')
            ->getQuery()
            ->execute();*/
        $goods = $this->modelsManager->createBuilder()
            ->columns('*')
            ->from(['sg' => 'Parttimejob\Model\Parttimejob'])
            ->where('sg.is_selling = :selling: ', ['selling' => $this->_config['selling_status']['selling']])
            ->andWhere('sg.status = :valid: ', ['valid' => $this->_config['data_status']['valid']])
            ->andWhere('sg.title like :goodsName: ', ['goodsName' => '%' . $goodsName . '%'])
            ->orderBy('sort desc')
            ->getQuery()
            ->execute();
        return $goods;
    }

    public function getList()
    {
        $goods = $this->modelsManager->createBuilder()
            ->columns('*')
            ->from(['sg' => 'Parttimejob\Model\Parttimejob'])
            ->where('sg.is_hiring = :hiring: ', ['hiring' => $this->_config['hiring_status']['hiring']])
            ->andWhere('sg.status = :valid: ', ['valid' => $this->_config['data_status']['valid']])
            ->getQuery()
            ->execute();
        return $goods;

    }

    //income
    //今日收入
    public function getTodayIncome($userId)
    {
        $todayStartTime = strtotime(date('Y-m-d'));
        $todatEndTime = strtotime(date("Y-m-d", strtotime("+1 day")));

        $todayIncomeData = $this->search(['user_id' => $userId, 'update_time[>=]' => $todayStartTime, 'update_time[<]' => $todatEndTime, 'type' => \GCL\Group\Income::TYPE_INCOME, 'income_status' => \GCL\Group\Income::STATUS_SUCCES, 'status' => \GCL\Status::NORMAL]);
        $todayIncomeAmount = 0;
        foreach ($todayIncomeData as $tiaVal) {
            $todayIncomeAmount += $tiaVal['amount'];
        }

        $amountData = $this->incomeTotal($userId);

        $amount = bcmul($todayIncomeAmount, 1, 2);
        $withdrawableAmount = bcsub($amountData['income_amount'], $amountData['withdrawable_amount'], 2);
        $res = [];
        $res = [
            'amount' => $amount,
            'withdrawable_amount' => $withdrawableAmount,
            'type' => \GCL\Group\Income::TYPE_INCOME,
        ];
        return $res;
    }

    //收入列表
    public function getIncomeList($data)
    {
        $allIncomeData = $this->search(['user_id' => $data['user_id'], 'page' => $data['page'], 'page_size' => $data['page_size'], 'type' => \GCL\Group\Income::TYPE_INCOME, 'income_status' => \GCL\Group\Income::STATUS_SUCCES, 'status' => \GCL\Status::NORMAL], ['update_time DESC']);
        $res = \Util::opt($allIncomeData, ['amount', 'type', 'update_time'])['data'];
        return $res;
    }

    /**
     * @param $goodsId 商品ID
     * @param $userId  用户ID
     * @return true
     * 根据商品ID、用户ID 查询 某个时间点 这个用户的上级ID
     * 预期收入计算
     */
    public function computeAnticipationIncome($orderId)
    {

        if (empty($orderId)) {
            throw new \Exception('缺少参数', 400);
        }
        $error = [];
        //订单合法性校验
        $orderInfo = $this->getOrderSvc()->detailOrder(['order_id' => $orderId, 'pay_status' => \Payment\Common::PAY_STATUS_SUCCESS, 'status' => \GCL\Status::NORMAL]);
        if (!empty($orderInfo)) {
            $orderDetailInfo = $this->getOrderDetailSvc()->detail(['order_id' => $orderInfo['order_id'], 'shipping_status' => [\GCL\Group\Shipping::STATUS_AWAIT, \GCL\Group\Shipping::STATUS_SHIPPED], 'status' => \GCL\Status::NORMAL]);
            if (!empty($orderDetailInfo)) {
                $userId = $orderInfo['user_id'];

                $orderGoodsData = $this->getOrderGoodsSvc()->detail(['order_id' => $orderInfo['order_id'], 'status' => \GCL\Status::NORMAL]);
                $firstLevel = $this->getShareRelationSvc()->getGoodsParent($orderGoodsData['goods_id'], $userId, $orderInfo['pay_time']);

                $secondLevel = [];
                if (!empty($firstLevel['parent_id'])) {
                    $secondLevel = $this->getShareRelationSvc()->getGoodsParent($orderGoodsData['goods_id'], $firstLevel['parent_id'], $orderInfo['pay_time']);
                }

                $incomeData = [];
                $time = time();
                if (!empty($firstLevel['parent_id'])) {
                    if ($userId == $firstLevel['share_user_id']) {
                        $amount = bcmul($orderInfo['order_amount'], \GCL\Group\Income::FIRST_LEVEL_ONESELF_PERCENT, 2);
                        $snapshot = json_encode(['share_relation_id' => $firstLevel['share_relation_id'], 'cashback_msg' => '分佣受益者是本人时,分佣比例为:' . bcmul(\GCL\Group\Income::FIRST_LEVEL_ONESELF_PERCENT, 100, 2) . '%,获得收益为' . bcmul($orderInfo['order_amount'], \GCL\Group\Income::FIRST_LEVEL_ONESELF_PERCENT, 2)]);
                    } else {
                        $amount = bcmul($orderInfo['order_amount'], \GCL\Group\Income::FIRST_LEVEL_PERCENT, 2);
                        $snapshot = json_encode(['share_relation_id' => $firstLevel['share_relation_id'], 'cashback_msg' => '分佣受益来源人id为:' . $userId . ',分佣比例为:' . bcmul(\GCL\Group\Income::FIRST_LEVEL_PERCENT, 100, 2) . '%,获得收益为' . bcmul($orderInfo['order_amount'], \GCL\Group\Income::FIRST_LEVEL_PERCENT, 2)]);
                    }
                    $incomeData[] = [
                        'user_id' => $firstLevel['share_user_id'],
                        'order_id' => $orderInfo['order_id'],
                        'source_level' => 1,
                        'type' => \GCL\Group\Income::TYPE_INCOME,
                        'amount' => $amount,
                        'snapshot' => $snapshot,
                        'income_status' => \GCL\Group\Income::STATUS_ANTICIPATION,
                        'status' => \GCL\Status::NORMAL,
                        'add_time' => $time,
                        'update_time' => $time,
                    ];
                }

                if (!empty($secondLevel['parent_id'])) {
                    if ($userId == $secondLevel['share_user_id']) {
                        $amount = bcmul($orderInfo['order_amount'], \GCL\Group\Income::SECOND_LEVEL_ONESELF_PERCENT, 2);
                        $snapshot = json_encode(['share_relation_id' => $secondLevel['share_relation_id'], 'cashback_msg' => '分佣受益者是本人时,分佣比例为:' . bcmul(\GCL\Group\Income::SECOND_LEVEL_ONESELF_PERCENT, 100, 2) . '%,获得收益为' . bcmul($orderInfo['order_amount'], \GCL\Group\Income::SECOND_LEVEL_ONESELF_PERCENT, 2)]);
                    } else {
                        $amount = bcmul($orderInfo['order_amount'], \GCL\Group\Income::SECOND_LEVEL_PERCENT, 2);
                        $snapshot = json_encode(['share_relation_id' => $secondLevel['share_relation_id'], 'cashback_msg' => '分佣受益来源人id为:' . $userId . ',其上级分佣受益者id为:' . $firstLevel['share_user_id'] . ',分佣比例为:' . bcmul(\GCL\Group\Income::SECOND_LEVEL_PERCENT, 100, 2) . '%,获得收益为' . bcmul($orderInfo['order_amount'], \GCL\Group\Income::SECOND_LEVEL_PERCENT, 2)]);
                    }
                    $incomeData[] = [
                        'user_id' => $secondLevel['share_user_id'],
                        'order_id' => $orderInfo['order_id'],
                        'source_level' => 2,
                        'type' => \GCL\Group\Income::TYPE_INCOME,
                        'amount' => $amount,
                        'snapshot' => $snapshot,
                        'income_status' => \GCL\Group\Income::STATUS_ANTICIPATION,
                        'status' => \GCL\Status::NORMAL,
                        'add_time' => $time,
                        'update_time' => $time,
                    ];
                }

                if (!empty($incomeData)) {
                    $transaction = new \Transactions\IncomeTransaction();
                    $transaction->add($incomeData);
                }
                return true;
            }
        }
    }

    public function incomeTotal($userId)
    {
        $allIncomeData = $this->search(['user_id' => $userId, 'type' => \GCL\Group\Income::TYPE_INCOME, 'income_status' => \GCL\Group\Income::STATUS_SUCCES, 'status' => \GCL\Status::NORMAL]);
        $allIncomeAmount = 0;
        foreach ($allIncomeData as $aidVal) {
            $allIncomeAmount += $aidVal['amount'];
        }

        $allExpenditureData = $this->search(['user_id' => $userId, 'type' => \GCL\Group\Income::TYPE_EXPENDITURE, 'income_status' => \GCL\Group\Income::STATUS_SUCCES, 'status' => \GCL\Status::NORMAL]);
        $allExpenditureAmount = 0;
        foreach ($allExpenditureData as $aeaVal) {
            $allExpenditureAmount += $aeaVal['amount'];
        }

        $incomeAmount = bcmul($allIncomeAmount, 1, 2);
        $withdrawableAmount = bcmul($allExpenditureAmount, 1, 2);
        $res = [];
        $res = [
            'income_amount' => $incomeAmount,
            'withdrawable_amount' => $withdrawableAmount,
        ];
        return $res;
    }

    private function createOrderNo()
    {
        $date = date('YmdHis');
        $rand1 = mt_rand(10000, 99999);
        $rand2 = mt_rand(10000, 99999);
        $rand3 = mt_rand(10000, 99999);
        $orderNo = $date . '-' . $rand1 . $rand2 . $rand3;
        return $orderNo;
    }

    private function getGoodsCover($imgUrl){
        if(empty($imgUrl)){
            return '';
        }
        $imgUrl = str_replace('，',',',$imgUrl);
        $imgArr = explode(',',$imgUrl);
        return $imgArr[0];
    }

    private function getSpecs($goods){
        //超市
        if(isset($goods->specs) && isset($goods->specs_unit_id)){
            $specsNum = $goods->specs;
            $specsUnit = $this->_config['supermarket_specs_unit'][$goods->specs_unit_id];
            return $specsNum.$specsUnit;
        }
        return '';
    }

    private function getJsonGoodsData($goods){
        $arr = [];
        foreach ($goods as $k=>$v){
            $arr[$k] = $v;
        }
        return json_encode($arr);
    }

    public function getOrderDetail($orderId,$userId){
        $data = [];
        //验证是否为用户订单
        $orderData = $this->modelsManager->createBuilder()
            ->columns('*')
            ->from(['sg' => 'Order\Model\Order'])
            ->where('sg.order_id = :order_id: ', ['order_id' => $orderId])
            ->andWhere('sg.status = :valid: ', ['valid' => $this->_config['data_status']['valid']])
            ->getQuery()
            ->getSingleResult();
        if(empty($orderData)){

        }
        $data['order_data'] = $orderData;
        $orderDetailData = $this->modelsManager->createBuilder()
            ->columns('*')
            ->from(['sg' => 'Order\Model\OrderDetail'])
            ->where('sg.order_id = :order_id: ', ['order_id' => $orderId])
            ->andWhere('sg.status = :valid: ', ['valid' => $this->_config['data_status']['valid']])
            ->getQuery()
            ->execute();
        $data['order_address'] = $orderDetailData;
        $orderGoodsData = $this->modelsManager->createBuilder()
            ->columns('*')
            ->from(['sg' => 'Order\Model\OrderGoods'])
            ->where('sg.order_id = :order_id: ', ['order_id' => $orderId])
            ->andWhere('sg.status = :valid: ', ['valid' => $this->_config['data_status']['valid']])
            ->getQuery()
            ->execute();
        $data['order_goods_list'] = $orderGoodsData;
        return $data;
    }
    public function getOrderList($userId){
        $list = [];
        $orderDatas = $this->modelsManager->createBuilder()
            ->columns('order_id')
            ->from(['sg' => 'Order\Model\Order'])
            ->where('sg.user_id = :user_id: ', ['user_id' => $userId])
            ->andWhere('sg.status = :valid: ', ['valid' => $this->_config['data_status']['valid']])
            ->getQuery()
            ->execute();
        if(!empty($orderDatas)){
            foreach ($orderDatas as $v){
                $list[] = $this->getOrderDetail($v->order_id,$userId);
            }
        }
        return $list;
    }

    public function getIncome($merchantId){
        //营业总额
        //订单总数
        //用户数
        //今日营业额
        //今日订单数
        //今日新增用户数

    }


}