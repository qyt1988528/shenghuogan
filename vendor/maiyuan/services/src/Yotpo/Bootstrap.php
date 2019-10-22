<?php
namespace Maiyuan\Service\Yotpo;

use Maiyuan\Service\Standard;
use Maiyuan\Tool\Http\Curl;
use Maiyuan\Service\Exception;
//$this->vendor->service->yotpo->getName()
//$this->vendor->service->yotpo()
class Bootstrap extends Standard
{
    private $_appkey,$_secret;
    public function __construct($appkey,$secret)
    {
        $this->_appkey = $appkey;// $appkey;
        $this->_secret = $secret; // $secret;
        $curl = new class() extends Curl{
            public function _process($response) {
                $response = parent::_process($response);
                if(is_array($response)) {
                    if (isset($response["status"]['error_type'])) {
                        throw new Exception($response["status"]['code'].' '.$response["status"]['message'],$response["status"]['code']);
                    }
                    $response = isset($response['response'])?$response['response']:[];
                }
                return $response;
            }

        };
        
        $curl->setBaseUrl('https://api.yotpo.com/');
        $this->client = $curl;


    }

    /**
     * remove star 1 ,2 reviews
     */
    public function process($response,$page,$limit){
        $total = $response['bottomline']['total_review'] = $response['bottomline']['total_review'] - $response['bottomline']['star_distribution']['1'] - $response['bottomline']['star_distribution']['2'];
        $currentCount = $page*$limit;
        $filterCount = $currentCount - $total;
        if($filterCount > 0){
            $count = $limit - $filterCount;
            $response['reviews'] = array_slice($response['reviews'],0,$count);
        }
        return $response;
    }

    /**
     * get sku review
     * @param $sku
     * @param $page
     * @param $limit
     * @return mixed
     */
    public function getReview($sku,$page,$limit)
    {
        $query['page'] = $page;
        $query['per_page'] = $limit;
        $path = 'v1/widget/'.$this->_appkey.'/products/'.$sku.'/reviews.json';
        $response = $this->client->get($path,$query);
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
            "appkey"=> /*"6meiHQVnuPsIsUPukn1S9iq4KVU86lOiIRnk0nN6"*/$this->_appkey,
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