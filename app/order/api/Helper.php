<?php

namespace Order\Api;

use MDK\Api;
use Order\Model\Order;
use Order\Model\OrderDetail;
use Order\Model\OrderGoods;
use Parttimejob\Model\Parttimejob;

class Helper extends Api
{
    private $_config;
    private $_model;
    private $_invalid_time;

    public function __construct()
    {
        $this->_config = $this->app->core->config->config->toArray();
        $this->_model = new Parttimejob();
        $this->_invalid_time = 1800;//30分钟
    }

    public function createOrder($goodsData, $userId, $addressId,$couponNo='')
    {
        $robotPart = new FaceppDetectSingleFace();

        $needAddress = false;
        $goodsTypes = $this->_config['goods_types'];
        $orderGoodsInsertDatas = [];
        //参数校验
        foreach ($goodsData as $gd) {
            if (empty($gd['goods_id']) || empty($gd['goods_type']) || empty($gd['goods_num'])) {
                //商品id、类型、购买数量均不能为空
                return -1;
            }
            if (!isset($goodsTypes[$gd['goods_type']])) {
                return -1;
            }
            //确认商品是否存在
            $goods = $this->modelsManager->createBuilder()
                ->columns('*')
                // ->columns('id,stock,title,img_url,original_price,self_price,description,location,is_recommend,sort,base_fav_count,base_order_count')
                ->from(['sg' => $goodsTypes[$gd['goods_type']]['model']])
                ->where('sg.id = :goods_id: ', ['goods_id' => $gd['goods_id'] ])
                ->andWhere('sg.status = :valid: ', ['valid' => $this->_config['data_status']['valid']])
                ->limit(1)
                ->getQuery()
                ->execute();
            $goods=(array)$goods;
            var_dump($goods);exit;
            if (empty($goods)) {
                return -1;
            }
            if(isset($goods->is_selling)){
                //是否在售(美食、驾考、酒店、失物招领、租车、二手物品、超市、门票)
                if($goods->is_selling != $this->_config['selling_status']['selling']){

                }
                //库存是否满足要求
                if($goods->stock < $gd['goods_num']){

                }
            }
            if(isset($goods->is_renting)){
                //是否出租(租房)
                if($goods->is_renting != $this->_config['renting_status']['renting']){

                }

            }
            if(isset($goods->is_hiring)){
                //是否在招人(代发快递、代取快递、兼职)
                if($goods->is_hiring != $this->_config['hiring_status']['hiring']){

                }
            }

            if (true) {
                $needAddress = true;
            }
            $orderGoodsInsertDatas[] = [
                'user_id'
            ];
        }
        if ($needAddress && empty($addressId)) {
            //需要传地址
            return -1;
        }

        //事务begin
        $this->db->begin();
        //写订单表
        $orderModel = new Order();
        if ($orderModel->save() === false) {
            $this->db->rollback();
            return 0;
        }
        //写订单详情表
        $orderDetailModel = new OrderDetail();
        if ($orderDetailModel->save() === false) {
            $this->db->rollback();
            return 0;
        }
        //写订单的商品信息表
        $orderGoodsModel = new OrderGoods();
        if ($orderGoodsModel->save() === false) {
            $this->db->rollback();
            return 0;
        }
        //事务commit
        $this->db->commit();
        return 1;
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
    public function withdrawParttimejob($parttimejobId)
    {
        try {
            $updateModel = $this->_model->findFirstById($parttimejobId);
            if (empty($updateModel)) {
                return false;
            }
            $updateData = [
                'id' => $parttimejobId,
                'is_hiring' => $this->_config['hiring_status']['unhiring'],
            ];
            $updateModel->update($updateData);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function deleteParttimejob($parttimejobId)
    {
        try {
            $invalid = $this->_config['data_status']['invalid'];
            $updateModel = $this->_model->findFirstById($parttimejobId);
            if (empty($updateModel)) {
                return false;
            }
            $updateData = [
                'id' => $parttimejobId,
                'status' => $invalid,
            ];
            $updateModel->update($updateData);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function detail($parttimejobId)
    {
        $condition = "id = " . $parttimejobId;
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


}