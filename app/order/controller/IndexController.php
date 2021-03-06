<?php

namespace Order\Controller;

use MDK\Controller;


/**
 * Index controller.
 * @RoutePrefix("/order", name="order")
 */
class IndexController extends Controller
{
    private $_error;
    private $_userId;
    private $_orderDict;
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
        $this->_orderDict = $this->app->core->config->order->toArray();
        //验证是否为商户
        $this->_merchantId = $this->app->tencent->api->UserApi()->getMerchantIdByUserId($this->_userId);
    }

    /**
     * Index action.
     * 支付结果通知
     * @return void
     * @Route("/notify", methods={"GET","POST"}, name="order")
     */
    public function orderNotifyAction()
    {
        $keys = ['pay_time', 'serial_no', 'actual_amount', 'pay_channel'];
        $this->_required($keys);
        $post = $this->getPosts($keys);
        error_log(var_export($post, true));
        $order = (new \Service\Order())->detail(['serial_no' => $post['serial_no']]);

        if (empty($order)) {
            throw new \Exception('订单不存在', 400);
        }
        error_log('订单id:' . $order['order_id']);
        if ($order['pay_status'] == \Payment\Common::PAY_STATUS_WAIT && (int)($order['order_amount'] * 100) == (int)$post['actual_amount']) {
            //更新订单数据
            $params['pay_time'] = $post['pay_time'];
            $params['pay_channel'] = $post['pay_channel'];
            $params['pay_status'] = \Payment\Common::PAY_STATUS_SUCCESS;
            $result = (new \Service\Order())->update($order['order_id'], $params);
            //预收入
            (new \Service\Income())->computeAnticipationIncome($order['order_id']);

            $this->output($result);
        }
        $this->output('success');
    }

    public function indexAction()
    {
        $page = $this->request->getParam('page', null, 1);
        //分页
        try {
            $data['data'] = [];
            $tickets = $this->app->order->api->Helper()->getList($page);
            if (!empty($tickets)) {
                $data['data'] = $tickets;
            }
        } catch (\Exception $e) {
            $this->resultSet->error($e->getCode(), $e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());

    }


    //查询带有detail和产品信息的订单列表

    /**
     * 获取订单详情
     * Create action.
     * @return void
     * @Route("/list", methods="POST", name="order")
     */
    public function listAction()
    {
        try {
            header("content-type:appliation/json; charset=utf-8");
            $result = $this->app->order->api->Helper()->getOrderList($this->_userId);
            if (!empty($result)) {
                $data['data'] = $result;
                header("content-type:appliation/json; charset=utf-8");
            } else {
                $this->resultSet->error(1002, $this->_error['try_later']);
            }
        } catch (\Exception $e) {
            $this->resultSet->error($e->getCode(), $e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());
    }


    /**
     * 获取订单详情
     * Create action.
     * @return void
     * @Route("/detail", methods="POST", name="order")
     */
    public function detailAction()
    {
        //订单id
        $orderId = $this->request->getPost('order_id', null, 0);
        if (empty($orderId)) {
            $this->resultSet->error(1001,$this->_error['invalid_input']);
        }
        try {
            $result = $this->app->order->api->Helper()->getOrderDetail($orderId, $this->_userId);
            if (!empty($result)) {
                $data['data'] = $result;
            } else {
                $this->resultSet->error(1002, $this->_error['try_later']);
            }
        } catch (\Exception $e) {
            $this->resultSet->error($e->getCode(), $e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());
    }

    /**
     * 获取订单详情
     * Create action.
     * @return void
     * @Route("/income", methods="POST", name="order")
     */
    public function incomeAction()
    {
        //商户id
        if (empty($this->_merchantId)) {

        }
        try {
            $result = $this->app->order->api->Helper()->getIncome($this->_merchantId);
            if (!empty($result)) {
                $data['data'] = $result;
            } else {
                $this->resultSet->error(1002, $this->_error['try_later']);
            }
        } catch (\Exception $e) {
            $this->resultSet->error($e->getCode(), $e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());
    }

    /**
     * 创建订单
     * Create action.
     * @return void
     * @Route("/create", methods="POST", name="order")
     */
    public function createAction()
    {
        //用户id 商品id 商品类型
        // $goodsData = $this->request->getPost('goods_data');
        $orderData = $this->request->getParam('order_data');//已经是数组了
        $goodsData = $orderData['goods_data'] ?? [];
        // var_dump(empty($orderData));
        // var_dump(empty($goodsData));
        // var_dump(!is_array($goodsData));
        // exit;
        if (empty($orderData) || empty($goodsData) || !is_array($goodsData)) {
            $this->resultSet->error(1001,$this->_error['invalid_input']);
        }
        $addressId = $orderData['address_id'] ?? 0;
        $couponNo = $orderData['coupon_no'] ?? '';
        try {
            $result = $this->app->order->api->Helper()->createOrder($goodsData, $this->_userId, $addressId, $couponNo);
            if ($result) {
                $data['data'] = [
                    'order_id' => $result
                ];
            } else {
                $this->resultSet->error(1002, $this->_error['try_later']);
            }
        } catch (\Exception $e) {
            $this->resultSet->error($e->getCode(), $e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());
        exit;
    }


    /**
     * 订单支付
     * Payment action.
     * @return void
     * @Route("/payment", methods="POST", name="order")
     */
    public function paymentAction()
    {
        $orderId = $this->request->getPost('order_id');
        if (empty($orderId)) {
            $this->resultSet->error(1002, $this->_error['try_later']);
        }
        try {

            $result = $this->app->tencent->api->Pay()->pay($orderId);
            $data['data'] = [
                'pay_status' => 1,
                'pay_msg' => 'success'
            ];
        } catch (\Exception $e) {
            $this->resultSet->error($e->getCode(), $e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());
        exit;


        // $result = $this->app->tencent->api->Pay()->testPay('dafjalk');
        // exit;

    }



    /**
     * 获取订单配置字典
     * Create action.
     * @return void
     * @Route("/dict", methods="POST", name="order")
     */
    public function getOrderConfigAction()
    {
        try {
            $data['data'] = [
                'order_dict' => $this->_orderDict
            ];
        } catch (\Exception $e) {
            $this->resultSet->error($e->getCode(), $e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());

    }


    //-----------------------------分界线,下面为旧方法
        /**
     * 订单支付
     */
    public function paymentGcAction()
    {
        $this->_required('order_id', 'user_id');
        $orderId = $this->getPost('order_id');
        $userId = $this->getPost('user_id');
        $orderId = $this->request->getPost('order_id', null, 0);
        $service = new \Service\Order();
        $order = $service->detail(['order_id' => $orderId]);
        if (empty($order)) {
            throw new \Exception('订单不存在', 400);
        }
        if ($order['order_status'] != \GCL\Group\Order::STATUS_VALID) {
            throw new \Exception('订单异常', 400);
        }
        if ($order['pay_status'] === \Payment\Common::PAY_STATUS_SUCCESS) {
            throw new \Exception('订单已支付', 400);
        }
        //获取用户信息
        $user = (new \Service\User())->detail(['user_id' => $userId]);
        if (empty($user)) {
            throw new \Exception('用户不存在', 400);
        }
        if ($userId != $order['user_id']) {
            throw new \Exception('订单与用户不匹配', 400);
        }
        //生成流水号
        $serialNo = \GCL\Group\Order::makeSerialNo($orderId);
        //更新流水号
        (new \Service\Order())->update($orderId, ['serial_no' => $serialNo]);
        //支付参数
        $payment['pay_source'] = \Payment\Common::PAY_SOURCE_GROUP;
        $payment['pay_channel'] = \Payment\Common::CHANNEL_WECHAT_MINIPROGRAM;
        $payment['actual_amount'] = $order['order_amount'] * 100;
        $payment['content'] = '购买商品';
        $payment['openid'] = $user['openid'];
        $payment['serial_no'] = $serialNo;
        $host = \Yaf\Registry::get('config')->host;
        $payment['notify_url'] = $host . '/v1/payment/orderNotify';
        $result = \Payment\Api::getInst()->unifiedOrder($payment);
        $this->output($result);

    }

    public function addShippingSnAction()
    {
        $values = $this->getPost('values');
        $svc = new \Service\OrderDetail();
        $ret = $svc->addShippingSn($values);
        $this->output($ret);
    }

    //订单状态列表
    public function orderlistAction()
    {
        $this->_required(['order_status', 'user_id', 'page', 'page_size']);
        $data['status'] = $this->getQuery('order_status');
        $data['user_id'] = $this->getQuery('user_id');
        $data['page'] = $this->getQuery('page', 1);
        $data['page_size'] = $this->getQuery('page_size', 10);
        $data['is_return_sort'] = $this->getQuery('is_return_sort', 0);

        $result = (new \Service\Order())->orderList($data);
        $this->output($result);
    }

    //确认收货
    public function confirmAction()
    {
        $this->_required(['order_id']);
        $orderId = $this->getPost('order_id');

        $result = (new \Service\Order())->confirm($orderId);
        $this->output($result);
    }

    //24小时之后确认签收
    public function delayConfirmAction()
    {
        $orderId = $this->getPost('order_id');
        $svc = new \Service\OrderDetail();
        $ret = $svc->delayConfirm($orderId);
        $this->output($ret);
    }


    /**
     * 创建订单
     */
    public function createGcAction()
    {
        $keys = ['goods', 'user_id', 'address_id'];
        $this->_required($keys);
        $goods = $this->getPost('goods');
        $userId = (int)$this->getPost('user_id');
        $addressId = (int)$this->getPost('address_id');
        $teamId = (int)$this->getPost('team_id', 0);
        $goodsService = new \Service\Goods();
        $goodsRelationService = new \Service\GoodsRelation();
        $goodsItemService = new \Service\GoodsItem();
        $goodsTypeService = new \Service\GoodsType();

        //商品详情，配送地址
        $address = (new \Service\Address())->detail(['address_id' => $addressId]);
        if (empty($address)) {
            throw new \Exception('收货地址不存在', 400);
        }
        //订单详情
        $orderDetail['receiver'] = $address['name'];
        $orderDetail['cellphone'] = $address['cellphone'];
        $orderDetail['province'] = $address['province'];
        $orderDetail['city'] = $address['city'];
        $orderDetail['county'] = $address['county'];
        $orderDetail['detailed_address'] = $address['detailed_address'];
        //订单属性
        $order['user_id'] = $userId;
        $order['order_no'] = time();
        $order['team_id'] = $teamId;
        $order['goods_amount'] = 0; //商品金额
        $order['order_amount'] = 0; //订单金额
        $order['pay_status'] = \Payment\Common::PAY_STATUS_WAIT; //支付状态
        $order['order_status'] = \GCL\Group\Order::STATUS_VALID; //订单状态
        $orderGoods = [];
        //查询商品是否存在
        foreach ($goods as $key => $value) {
            //商品基本信息
            $goodsInfo = $goodsService->detail(['goods_id' => $value['goods_id']]);
            if (empty($goodsInfo)) {
                throw new \Exception('商品不存在或者已下架', 400);
            }
            //商品型号
            $goodsItem = $goodsItemService->detail(['goods_item_id' => $value['goods_item_id']]);
            if (empty($goodsItem)) {
                throw new \Exception('商品型号不存在或者已下架', 400);
            }
            //商品规格
            $goodsType = $goodsTypeService->detail(['goods_type_id' => $value['goods_type_id']]);
            if (empty($goodsItem)) {
                throw new \Exception('商品规格不存在或者已下架', 400);
            }
            //商品价格
            $goodsRelation = $goodsRelationService->detail(['goods_id' => $value['goods_id'], 'goods_item_id' => $value['goods_item_id'], 'goods_type_id' => $value['goods_type_id']]);
            if (empty($goodsRelation)) {
                throw new \Exception('商品关系不存在或者已下架', 400);
            }
            //计算订单价格
            if (empty($teamId)) {
                //不参团
                $order['goods_amount'] += $goodsRelation['self_amount'] * $value['num'];
                $order['order_amount'] += $goodsRelation['self_amount'] * $value['num'];
                $price = $goodsRelation['self_amount'];
            } else {
                //参团
                $order['goods_amount'] += $goodsRelation['actual_amount'] * $value['num'];
                $order['order_amount'] += $goodsRelation['actual_amount'] * $value['num'];
                $price = $goodsRelation['actual_amount'];
            }
            //商品信息
            $orderGoods[$key]['goods_id'] = $goodsInfo['goods_id'];
            $orderGoods[$key]['goods_num'] = $value['num'];
            $orderGoods[$key]['goods_name'] = $goodsInfo['name'];
            $orderGoods[$key]['goods_cover'] = $goodsInfo['goods_cover'];
            $orderGoods[$key]['goods_item_id'] = $goodsItem['goods_item_id'];
            $orderGoods[$key]['goods_amount'] = $goodsRelation['amount'];
            $orderGoods[$key]['goods_current_amount'] = $price;
            $orderGoods[$key]['goods_type_id'] = $goodsType['goods_type_id'];
            $orderGoods[$key]['goods_attr'] = $goodsItem['name'] . '/' . $goodsType['name'];

        }
        $result = (new \Transactions\OrderTransaction())->create($order, $orderGoods, $orderDetail);
        $this->output($result);
    }

    //查询不带扩展信息的数据
    public function searchAction()
    {
        $keys = ['order_id', 'order_no', 'serial_no', 'user_id', 'team_id', 'pay_channel', 'pay_status', 'order_status', 'goods_name', 'shipping_status', 'delay_confirm', 'min_order_time', 'max_order_time', 'team_status', 'status', 'page', 'page_size'];
        $param = $this->getQuerys($keys, true);
        $svc = new \Service\Order();
        $list = $svc->search($param);
        $this->output($list);
    }
     /**
     * 创建订单
     * Create action.
     * @return void
     * @Route("/test", methods="GET", name="order")
     */
    public function testAction(){
        $this->app->order->api->Helper()->getOrderData(0);exit;
    }

}
