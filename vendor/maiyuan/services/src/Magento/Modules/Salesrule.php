<?php
namespace Maiyuan\Service\Magento\Modules;


class Salesrule extends Module
{
    public function getCoupons($codes)
    {
        if(!is_array($codes)){
            throw new \Maiyuan\Service\Exception('need array or int,'.gettype($codes).' gived');
        }
        $result = $this->client->get('salesrule/coupon', ['code' => $codes]);
        return $result;
    }
}