<?php
namespace Home\Controller;
use MDK\Controller;


/**
 * Face controller.
 * @RoutePrefix("/home", name="home")
 */
class IndexController extends Controller
{

    /**
     * Index action.
     * @return void
     * @Route("/", methods="GET", name="home")
     */
    public function indexAction() {
        try{
            $data['data'] = $this->app->home->api->Helper()->getIndexData();
        }catch (\Exception $e){
            $this->resultSet->error($e->getCode(),$e->getMessage());
        }
        $this->resultSet->success()->setData($data);
        $this->response->success($this->resultSet->toObject());

    }

    public function index1Action(){
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
        $cover = '
        https://oss.mtlab.meitu.com/mtopen/wNKztUVuXEiHSNfD4A06SGwqXatzUvS0/MTU3MzA0MTYwMA==/4a7ac25d-2bc2-46db-8563-0e1a2907f66a.jpg
        ';
//首页
        $indexData = [
            'cover' => $cover,
            'icon' => [
                [
                    'title'=>'超市',
                    'img_url'=>$icon1,
                    'base_uri' => '/supermarket',
                    'id'=>1,
                    'sort'=>1,
                ],
                [
                    'title'=>'兼职',
                    'img_url'=>$icon2,
                    'base_uri' => '/parttimejob',
                    'id'=>2,
                    'sort'=>2,
                ],
                [
                    'title'=>'门票',
                    'img_url'=>$icon3,
                    'base_uri' => '/ticket',
                    'id'=>3,
                    'sort'=>3,
                ],
                [
                    'title'=>'住宿',
                    'img_url'=>$icon4,
                    'base_uri' => '/hotel',
                    'id'=>4,
                    'sort'=>4,
                ],
                [
                    'title'=>'餐饮',
                    'img_url'=>$icon5,
                    'base_uri' => '/catering',
                    'id'=>5,
                    'sort'=>5,
                ],
                [
                    'title'=>'校园网',
                    'img_url'=>$icon6,
                    'base_uri' => '/school',
                    'id'=>6,
                    'sort'=>6,
                ],
                [
                    'title'=>'租房',
                    'img_url'=>$icon7,
                    'base_uri' => '/renthouse',
                    'id'=>7,
                    'sort'=>7,
                ],
                [
                    'title'=>'租车',
                    'img_url'=>$icon8,
                    'base_uri' => '/rentcar',
                    'id'=>8,
                    'sort'=>8,
                ],
                [
                    'title'=>'二手物',
                    'img_url'=>$icon9,
                    'base_uri' => '/secondhand',
                    'id'=>9,
                    'sort'=>9,
                ],
                [
                    'title'=>'快递',
                    'img_url'=>$icon10,
                    'base_uri' => '/express',
                    'id'=>10,
                    'sort'=>10,
                ],
            ],
            'ad' => [
                [
                    'title' => '失物招领',
                    'desc' => '找回您失去的爱',
                    'base_uri' => '/express',
                    'id' =>  1,
                ],
                [
                    'title' => '驾考报名',
                    'desc' => '全线优质驾校',
                    'base_uri' => '/express',
                    'id' =>  2,
                ]
            ],
            'recommend_list' => [
                [
                    'title'=>'冰雪大世界门票[成人票]',
                    'img_url'=>$cover,
                    'base_uri' => '/express',
                    'id'=>10,
                    'base_uri' => '/ticket/detail',
                    'type' => '3',
                    'current_price' => '¥165.50',
                ],
                [
                    'title'=>'香格里拉大酒店1晚',
                    'img_url'=>$cover,
                    'id'=>10,
                    'base_uri' => '/hotel/detail',
                    'type' => '4',
                    'current_price' => '¥888.00',

                ],
                [
                    'title'=>'宴宾楼老菜馆[100元代金券]',
                    'img_url'=>$cover,
                    'id'=>10,
                    'type' => '5',
                    'base_uri' => '/catering/detail',
                    'current_price' => '¥66.60',
                ],
            ],
            'part_time_job_list' => [
                [
                    'title'=>'家教[数学,2小时]',
                    'location'=>'松北区融创旅游城华园',
                    'id'=>10,
                    'base_uri' => '/parttimejob/detail',
                    'publish_time' => '2019-11-06 22:00:00',
                    'current_price' => '¥100.00',
                ],
                [
                    'title'=>'传单发放员',
                    'location'=>'哈尔滨中央大街',
                    'id'=>10,
                    'base_uri' => '/parttimejob/detail',
                    'publish_time' => '2019-11-06 21:00:00',
                    'current_price' => '¥60.00',
                ],
            ],
            'life_info_list' => [
                [

                    'title'=>'出租保利水韵长滩三室一厅一卫[精装修,可月付]',
                    'img_url'=>$cover,
                    'id'=>10,
                    'type' => '',
                    'base_uri' => '/renthouse/detail',
                    'current_price' => '¥1500',
                    'publish_time' => '2019-11-05 10:00:00',
                ],
                [
                    'title'=>'货车租赁[车+司机,8小时]',
                    'img_url'=>$cover,
                    'id'=>10,
                    'type' => '',
                    'base_uri' => '/rentcar/detail',
                    'current_price' => '¥400',
                    'publish_time' => '2019-11-04 09:41:50',
                ],
                [
                    'title'=>'二手iphoneX出售',
                    'img_url'=>$cover,
                    'id'=>10,
                    'type' => '',
                    'base_uri' => '/secondhand/detail',
                    'current_price' => '¥4000',
                    'publish_time' => '2019-11-03 23:08:04',
                ],
            ],
        ];
        $apiData['data'] = $indexData;
    }



}
