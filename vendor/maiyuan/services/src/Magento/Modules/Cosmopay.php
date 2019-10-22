<?php
namespace Maiyuan\Service\Magento\Modules;

class Cosmopay extends Module
{
    /**
     * cosmopay order
     * @param $orderId          order id
     * @param $cardNumber       card number
     * @param $date             data [month,year]
     * @param $cardHolderName   card holder name
     * @param $cvv              card cvv
     * @return mixed
     */
    public function order($orderId,$cardNumber,$date,$cardHolderName,$cvv)
    {
        $condition = [
            "order_id" => $orderId,
            'payment' => [
                "method" => "cosmo_cosmoPay",
                "cc_number" => $cardNumber,
                "cc_exp_year" => $date['year'],
                "cc_exp_month" => $date['month'],
                "resolution" => $cardHolderName,
                "cc_cid" => $cvv
            ]
        ];
        return $this->client->post('payment/cosmo',$condition);
    }
}