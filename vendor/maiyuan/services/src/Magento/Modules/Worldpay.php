<?php
namespace Maiyuan\Service\Magento\Modules;


class Worldpay extends Module
{
    /**
     * worldpay order
     * @param $orderId          order id
     * @param $cardNumber       card number
     * @param $date             date (month,year)
     * @param $cardHolderName   card holder name
     * @param $cvv              card cvv
     * @return mixed
     */
    public function order($orderId,$cardNumber,$date,$cardHolderName,$cvv)
    {
        $condition = [
            "order_id" => $orderId,
            'payment' => [
                "method" => "worldpay_cc",
                "type_worldpay_cc" => "VISA-SSL",
                "VISA-SSL" => [
                    "cardNumber" => $cardNumber,
                    "expiryDate" => $date,
                    "cardHolderName" => $cardHolderName,
                    "cvv" => $cvv,
                ],
                "worldpay_type" => "VISA-SSL"
            ]
        ];
        return $this->client->post('payment/worldpay',$condition);
    }

    /**
     * 3ds auth response
     * 验证3ds响应结果
     * @param $firstMap    session digital extension first request map(SessionDigital_WorldPay_Model_XML_Objects_Order)
     * @param $RequestData  session digital extension first request response Data (echo_data)事务
     * @param $cookie       session digital extension first request response header setCookie
     * @param $PaRes        worldpay form redirct form data PaRes
     */
    public function threedsecureAuthresponse($firstMap,$RequestData,$cookie,$PaRes)
    {
        $condition = [
            'firstMap' => $firstMap,
            '3dRequestData' => $RequestData,
            'cookie' => $cookie,
            'PaRes' => $PaRes,
        ];
        return $this->client->post('payment/worldpay/3dsecure/authresponse',$condition);
    }
}