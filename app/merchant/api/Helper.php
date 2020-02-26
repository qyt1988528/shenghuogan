<?php

namespace Merchant\Api;

use MDK\Api;

class Helper extends Api
{
    private $_config;
    private $_order;

    public function __construct()
    {
        $this->_config = $this->app->core->config->config->toArray();
        $this->_order = $this->app->core->config->order->toArray();
    }

    //根据商品类型和商户ID查询所有未删除的商品
    public function getDataByGoodsType($goodsType, $merchantId)
    {
        $goodsTypes = $this->_config['goods_types'];
        $goods = $this->modelsManager->createBuilder()
            ->columns('*')
            ->from(['sg' => $goodsTypes[$goodsType]['model']])
            ->where('sg.merchant_id = :merchant_id: ', ['merchant_id' => $merchantId])
            ->andWhere('sg.status = :valid: ', ['valid' => $this->_config['data_status']['valid']])
            ->getQuery()
            ->execute();
        return $goods;
    }



}