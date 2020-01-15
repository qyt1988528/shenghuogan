<?php
namespace Home\Api;

use MDK\Api;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\Middleware;
use Psr\Http\Message\ResponseInterface;

class Helper extends Api
{
    public function getIndex(){
        //location
        //cover
        $cover = 'https://oss.mtlab.meitu.com/mtopen/wNKztUVuXEiHSNfD4A06SGwqXatzUvS0/MTU3MzA0MTYwMA==/4a7ac25d-2bc2-46db-8563-0e1a2907f66a.jpg';
        $data['cover'] = $cover;
        //icon
        $icon1 = 'https://oss.mtlab.meitu.com/mtopen/wNKztUVuXEiHSNfD4A06SGwqXatzUvS0/MTU3MzA0MTYwMA==/37c134e2-ea7f-468e-9f1a-2127daaf7d46.jpg';
        $icon2 = 'https://oss.mtlab.meitu.com/mtopen/wNKztUVuXEiHSNfD4A06SGwqXatzUvS0/MTU3MzA0MTYwMA==/40d252cd-0850-4904-9793-f19af72ac28a.jpg';
        $icon3 = 'https://oss.mtlab.meitu.com/mtopen/wNKztUVuXEiHSNfD4A06SGwqXatzUvS0/MTU3MzA0MTYwMA==/1ca95336-e91c-4d29-b4a7-8f16e0ac80d6.jpg';
        $icon4 = 'https://oss.mtlab.meitu.com/mtopen/wNKztUVuXEiHSNfD4A06SGwqXatzUvS0/MTU3MzA0MTYwMA==/65ced7d2-c235-4f85-8078-3b8a7fca0709.jpg';
        $icon5 = 'https://oss.mtlab.meitu.com/mtopen/wNKztUVuXEiHSNfD4A06SGwqXatzUvS0/MTU3MzA0MTYwMA==/3a8dda06-d652-4286-8038-8f3ecfcec2ea.jpg';
        $icon6 = 'https://oss.mtlab.meitu.com/mtopen/wNKztUVuXEiHSNfD4A06SGwqXatzUvS0/MTU3MzA0MTYwMA==/54242d73-9ba1-41b4-bfa5-b1f4038ce26e.jpg';
        $icon7 = 'https://oss.mtlab.meitu.com/mtopen/wNKztUVuXEiHSNfD4A06SGwqXatzUvS0/MTU3MzA0MTYwMA==/89e92fc1-0709-4bee-89af-d2553f83b6ac.jpg';
        $icon8 = 'https://oss.mtlab.meitu.com/mtopen/wNKztUVuXEiHSNfD4A06SGwqXatzUvS0/MTU3MzA0MTYwMA==/aaf4fe4e-a16f-42fb-9460-419d2b593597.jpg';
        $icon9 = 'https://oss.mtlab.meitu.com/mtopen/wNKztUVuXEiHSNfD4A06SGwqXatzUvS0/MTU3MzA0MTYwMA==/0eb0a14e-5a0e-494f-acb3-0022934c0bb2.jpg';
        $icon10 = 'https://oss.mtlab.meitu.com/mtopen/wNKztUVuXEiHSNfD4A06SGwqXatzUvS0/MTU3MzA0MTYwMA==/1a982f6c-df3b-47fc-9bb7-2d7805c3260d.jpg';
        $data['icon'] = [
            [
                'title'=>'超市',
                'img_url'=>$icon1,
                'base_uri' => '/supermarket',
                'sort'=>1,
            ],
            [
                'title'=>'兼职',
                'img_url'=>$icon2,
                'base_uri' => '/parttimejob',
                'sort'=>2,
            ],
            [
                'title'=>'门票',
                'img_url'=>$icon3,
                'base_uri' => '/ticket',
                'sort'=>3,
            ],
            [
                'title'=>'住宿',
                'img_url'=>$icon4,
                'base_uri' => '/hotel',
                'sort'=>4,
            ],
            [
                'title'=>'餐饮',
                'img_url'=>$icon5,
                'base_uri' => '/catering',
                'sort'=>5,
            ],
            [
                'title'=>'校园网',
                'img_url'=>$icon6,
                'base_uri' => '/school',
                'sort'=>6,
            ],
            [
                'title'=>'租房',
                'img_url'=>$icon7,
                'base_uri' => '/renthouse',
                'sort'=>7,
            ],
            [
                'title'=>'租车',
                'img_url'=>$icon8,
                'base_uri' => '/rentcar',
                'sort'=>8,
            ],
            [
                'title'=>'二手物',
                'img_url'=>$icon9,
                'base_uri' => '/secondhand',
                'sort'=>9,
            ],
            [
                'title'=>'快递',
                'img_url'=>$icon10,
                'base_uri' => '/express',
                'sort'=>10,
            ],
        ];
        //ad
        $data['ad'] = [
            [
                'title' => '失物招领',
                'desc' => '找回您失去的爱',
                'base_uri' => '/express',
                'sort' =>  1,
            ],
            [
                'title' => '驾考报名',
                'desc' => '全线优质驾校',
                'base_uri' => '/express',
                'sort' =>  2,
            ]
        ];
        //recommend
        //parttimejob
        //life

    }

    public function getMode($page = 1, $pageSize = 10){
        $start = ($page - 1) * $pageSize;
        $goods = $this->modelsManager->createBuilder()
            ->columns('*')
            // ->columns('id,stock,title,img_url,original_price,self_price,description,location,is_recommend,sort,base_fav_count,base_order_count')
            ->from(['sg' => 'Home\Model\OperationMode'])
            ->where('sg.is_show = :selling: ', ['selling' => $this->_config['selling_status']['selling']])
            ->andWhere('sg.status = :valid: ', ['valid' => $this->_config['data_status']['valid']])
            ->orderBy('sort')
            ->limit($start, $pageSize)
            ->getQuery()
            ->execute();
        return $goods;
    }

    public function getIndexData(){
        $data['cover'] = '';
        $data['icon_list'] = $this->getMode();
        //今日推荐
        $data['recommended'] = [];
        $ticket = $this->app->ticket->api->Helper()->getFirst();
        if(!empty($ticket)){
            $data['recommended'][] = $ticket;
        }
        $hotel = $this->app->hotel->api->Helper()->getFirst();
        if(!empty($hotel)){
            $data['recommended'][] = $hotel;
        }
        $catering = $this->app->catering->api->Helper()->getFirst();
        if(!empty($catering)){
            $data['recommended'][] = $catering;
        }
        //兼职
        $data['parttimejob_list'] = [];
        $parttimejobs = $this->app->parttimejob->api->Helper()->getList(1,3);
        if(!empty($parttimejobs)){
            $data['parttimejob_list'] = $parttimejobs;
        }
        //生活信息
        $data['life_information'] = [];
        $car = $this->app->rent->api->Car()->getFirst();
        if(!empty($car)){
            $data['life_information'][] = $car;
        }
        $house = $this->app->rent->api->House()->getFirst();
        if(!empty($house)){
            $data['life_information'][] = $house;
        }
        $second = $this->app->secondhand->api->Helper()->getFirst();
        if(!empty($second)){
            $data['life_information'][] = $second;
        }
        return (object)$data;
    }


}