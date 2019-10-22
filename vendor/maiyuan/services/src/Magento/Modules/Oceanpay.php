<?php
namespace Maiyuan\Service\Magento\Modules;

class Oceanpay extends Module
{
    /**
     * oceanpay order
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
                "method" => "oceanpayment_csecreditcard",
                'card_info' => [
                    "card_secureCode" => $cvv,
                    "card_year" => $date['year'],
                    "card_month" => $date['month'],
                    "card_number" => $cardNumber
                ] 
            ]
        ];
        return $this->client->post('payment/oceanpay',$condition);
    }
}