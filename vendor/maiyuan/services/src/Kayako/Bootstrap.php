<?php
namespace Maiyuan\Service\Kayako;

use Maiyuan\Service\Standard;
use Maiyuan\Tool\Http\Curl;
use Maiyuan\Service\Exception;

class Bootstrap extends Standard
{
    private $_username,$_password;
    public function __construct()
    {
        $this->_username = 'luosha_li@imaiyuan.com';// $appkey;
        $this->_password = 'Soufeel9527~'; // $secret;
        $curl = new class() extends Curl{
            public function _process($response) {
                $response = parent::_process($response);
                if(is_array($response)) {
                    if($response['status']!=200){
                        throw new Exception($response['message'],$response['status']);
                    }
                }
                return $response['data'];
            }

        };
        $curl->setOptions([
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_USERPWD => "$this->_username:$this->_password",
            CURLOPT_ENCODING => "gzip,deflate",
            CURLOPT_TIMEOUT => 30
        ]);
        $curl->setBaseUrl('https://soufeeljewelry.kayako.com/api/v1/');
        $this->client = $curl;


    }

    /**
     * get all users
     * @param $page
     * @param $limit
     * @return mixed
     */
    public function getAllUsers($page=1,$limit=10)
    {
        $query['offset'] = ($page-1)*$limit;
        $query['limit'] = $limit;
        $path = 'users.json';
        $response = $this->client->get($path,$query);
        var_dump($path,$query,$response);die;
        return $response;
    }

    /**
     * get sku question and answer
     * @param $sku
     * @return mixed
     */
    public function getQuestions($sku)
    {
        $path = '/products/'.$this->_appkey.'/'.$sku.'/questions';
        $response = $this->client->get($path,[]);
        return $response;
    }

    /**
     * post a new review
     * @param $data
     * @return mixed
     */
    public function createReview($data)
    {
        $path = '/v1/widget/reviews';
        $request= [
            "appkey"=> "6meiHQVnuPsIsUPukn1S9iq4KVU86lOiIRnk0nN6",//$this->_appkey
            "domain"=> $data['baseUrl'],
            "sku"=> $data['sku'],
            "product_title"=> $data['name'],
            "product_description"=> $data['desc'],
            "product_url"=> $data['productUrl'],
            "product_image_url"=> $data['imageUrl'],
            "display_name"=> $data['displayName'],
            "email"=> $data['email'],
            "review_content"=> $data['reviewContent'],
            "review_title"=> $data['reviewTitle'],
            "review_score"=> $data['reviewScore']
        ];
        $response = $this->client->post($path,$request);
        return $response;
    }
}