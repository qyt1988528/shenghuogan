<?php
namespace Maiyuan\Service\Magento\Modules;


class Paypalrest extends Module
{
    public function getstatus($orderId,$payerId)
    {
        $condition = [
            'payerId' => $payerId,
            'orderId' => $orderId
        ];
        return $this->client->get('payment/paypalrest',$condition);
    }
}