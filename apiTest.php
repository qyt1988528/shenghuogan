<?php
/**
 * Created by PhpStorm.
 * User: qiuyutao
 * Date: 2019/10/27
 * Time: 下午2:59
 */

$data = '{"header":{"serviceName":"\/home"},"error":0,"message":"ok","data":{"cover":"","icon":[{"title":"\u8d85\u5e02","img_url":"","id":1},{"title":"\u517c\u804c","img_url":"","id":2},{"title":"\u95e8\u7968","img_url":"","id":3},{"title":"\u4f4f\u5bbf","img_url":"","id":4},{"title":"\u9910\u996e","img_url":"","id":5},{"title":"\u6821\u56ed\u7f51","img_url":"","id":6},{"title":"\u79df\u623f","img_url":"","id":7},{"title":"\u79df\u8f66","img_url":"","id":8},{"title":"\u4e8c\u624b\u7269","img_url":"","id":9},{"title":"\u5feb\u9012","img_url":"","id":10}],"ad":[{"title":"\u5931\u7269\u62db\u9886","desc":"\u627e\u56de\u60a8\u5931\u53bb\u7684\u7231","id":1},{"title":"\u9a7e\u8003\u62a5\u540d","desc":"\u5168\u7ebf\u4f18\u8d28\u9a7e\u6821","id":2}],"recommend_list":[{"title":"\u5feb\u9012","img_url":"","id":10,"type":"","price":""},{"title":"\u5feb\u9012","img_url":"","id":10,"type":"","price":""},{"title":"\u5feb\u9012","img_url":"","id":10,"type":"","price":""}],"part_time_job_list":[{"title":"\u5feb\u9012","location":"","id":10,"publish_time":"","price":""},{"title":"\u5feb\u9012","location":"","id":10,"publish_time":"","price":""},{"title":"\u5feb\u9012","location":"","id":10,"publish_time":"","price":""}],"life_info_list":[{"title":"\u5feb\u9012","img_url":"","id":10,"type":"","price":"","publish_time":""},{"title":"\u5feb\u9012","img_url":"","id":10,"type":"","price":"","publish_time":""},{"title":"\u5feb\u9012","img_url":"","id":10,"type":"","price":"","publish_time":""}]}}';
$apiData = json_decode($data,true);
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
$cover = 'https://oss.mtlab.meitu.com/mtopen/wNKztUVuXEiHSNfD4A06SGwqXatzUvS0/MTU3MzA0MTYwMA==/4a7ac25d-2bc2-46db-8563-0e1a2907f66a.jpg';
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
echo "/home"."\n";
echo json_encode($apiData)."\n";
echo "\n"."\n"."\n"."\n";
//超市(ps:分页)
$supermaketImg = 'https://oss.mtlab.meitu.com/mtopen/wNKztUVuXEiHSNfD4A06SGwqXatzUvS0/MTU3MzA0ODgwMA==/7eb84e00-dc47-44d4-be86-74d017129daa.jpg';
//id=1,page=1
$supermaketData = [
    'type_list' => [
        [
            'title' => '全部',
            'id' => 1,
            'selected' => true,
        ],
        [
            'title' => '精选水果',
            'id' => 2,
            'selected' => false,
        ],
        [
            'title' => '休闲食品',
            'id' => 3,
            'selected' => false,
        ],
        [
            'title' => '酒水乳饮',
            'id' => 4,
            'selected' => false,
        ],
        [
            'title' => '生活用品',
            'id' => 5,
            'selected' => false,
        ],
    ],
    'recommend_list' => [
        [
            'title'=>'西红柿',
            'img_url'=>$supermaketImg,
            'base_uri' => '/supermarket/detail',
            'id'=>1,
            'original_price' => '¥15.89',
            'current_price' => '¥11.89',
        ],
        [
            'title'=>'茄子',
            'img_url'=>$supermaketImg,
            'base_uri' => '/supermarket/detail',
            'id'=>2,
            'original_price' => '¥10.70',
            'current_price' => '¥6.00',
        ],
        [
            'title'=>'鸡蛋',
            'img_url'=>$supermaketImg,
            'base_uri' => '/supermarket/detail',
            'id'=>3,
            'original_price' => '¥20.00',
            'current_price' => '¥14.99',
        ],
    ],
    'total_list' => [
        [
            'id' => 1,
            'img_url'=>$supermaketImg,
            'base_uri' => '/supermarket/detail',
            'title' => '西红柿',
            'specs' => '2kg',
            'current_price' => '¥11.89',
        ],
        [
            'id' => 2,
            'img_url'=>$supermaketImg,
            'base_uri' => '/supermarket/detail',
            'title' => '茄子',
            'specs' => '1kg',
            'current_price' => '¥6.00',
        ],
        [
            'id' => 1,
            'img_url'=>$supermaketImg,
            'title' => '西红柿',
            'specs' => '2kg',
            'current_price' => '¥11.89',
        ],
        [
            'id' => 2,
            'img_url'=>$supermaketImg,
            'base_uri' => '/supermarket/detail',
            'title' => '茄子',
            'specs' => '1kg',
            'current_price' => '¥6.00',
        ],
        [
            'id' => 1,
            'img_url'=>$supermaketImg,
            'title' => '西红柿',
            'specs' => '2kg',
            'current_price' => '¥11.89',
        ],
        [
            'id' => 2,
            'img_url'=>$supermaketImg,
            'base_uri' => '/supermarket/detail',
            'title' => '茄子',
            'specs' => '1kg',
            'current_price' => '¥6.00',
        ],
        [
            'id' => 1,
            'img_url'=>$supermaketImg,
            'base_uri' => '/supermarket/detail',
            'title' => '西红柿',
            'specs' => '2kg',
            'current_price' => '¥11.89',
        ],
        [
            'id' => 2,
            'img_url'=>$supermaketImg,
            'base_uri' => '/supermarket/detail',
            'title' => '茄子',
            'specs' => '1kg',
            'current_price' => '¥6.00',
        ],
    ],
];
echo "/supermarket?id=1&page=1"."\n";
$apiData['data'] = $supermaketData;
echo json_encode($apiData)."\n";
echo "\n"."\n"."\n"."\n";
//id=1,page=2
$supermaketData = [
    'type_list' => [
        [
            'title' => '全部',
            'id' => 1,
            'selected' => true,
        ],
        [
            'title' => '精选水果',
            'id' => 2,
            'selected' => false,
        ],
        [
            'title' => '休闲食品',
            'id' => 3,
            'selected' => false,
        ],
        [
            'title' => '酒水乳饮',
            'id' => 4,
            'selected' => false,
        ],
        [
            'title' => '生活用品',
            'id' => 5,
            'selected' => false,
        ],
    ],
    'total_list' => [
        [
            'id' => 1,
            'img_url'=>$supermaketImg,
            'base_uri' => '/supermarket/detail',
            'title' => '西红柿1',
            'specs' => '2kg',
            'current_price' => '¥11.89',
        ],
        [
            'id' => 2,
            'img_url'=>$supermaketImg,
            'base_uri' => '/supermarket/detail',
            'title' => '茄子2',
            'specs' => '1kg',
            'current_price' => '¥6.00',
        ],
        [
            'id' => 1,
            'img_url'=>$supermaketImg,
            'title' => '西红柿3',
            'specs' => '2kg',
            'current_price' => '¥11.89',
        ],
        [
            'id' => 2,
            'img_url'=>$supermaketImg,
            'base_uri' => '/supermarket/detail',
            'title' => '茄子4',
            'specs' => '1kg',
            'current_price' => '¥6.00',
        ],
        [
            'id' => 1,
            'img_url'=>$supermaketImg,
            'title' => '西红柿5',
            'specs' => '2kg',
            'current_price' => '¥11.89',
        ],
        [
            'id' => 2,
            'img_url'=>$supermaketImg,
            'base_uri' => '/supermarket/detail',
            'title' => '茄子6',
            'specs' => '1kg',
            'current_price' => '¥6.00',
        ],
        [
            'id' => 1,
            'img_url'=>$supermaketImg,
            'base_uri' => '/supermarket/detail',
            'title' => '西红柿7',
            'specs' => '2kg',
            'current_price' => '¥11.89',
        ],
        [
            'id' => 2,
            'img_url'=>$supermaketImg,
            'base_uri' => '/supermarket/detail',
            'title' => '茄子8',
            'specs' => '1kg',
            'current_price' => '¥6.00',
        ],
    ],
];
echo "/supermarket?id=1&page=2"."\n";
$apiData['data'] = $supermaketData;
echo json_encode($apiData)."\n";
echo "\n"."\n"."\n"."\n";
//id=2,page=1
$supermaketData = [
    'type_list' => [
        [
            'title' => '全部',
            'id' => 1,
            'selected' => false,
        ],
        [
            'title' => '精选水果',
            'id' => 2,
            'selected' => true,
        ],
        [
            'title' => '休闲食品',
            'id' => 3,
            'selected' => false,
        ],
        [
            'title' => '酒水乳饮',
            'id' => 4,
            'selected' => false,
        ],
        [
            'title' => '生活用品',
            'id' => 5,
            'selected' => false,
        ],
    ],
    'total_list' => [
        [
            'id' => 1,
            'img_url'=>$supermaketImg,
            'base_uri' => '/supermarket/detail',
            'title' => '猕猴桃1',
            'specs' => '2kg',
            'current_price' => '¥11.89',
        ],
        [
            'id' => 2,
            'img_url'=>$supermaketImg,
            'base_uri' => '/supermarket/detail',
            'title' => '香蕉2',
            'specs' => '1kg',
            'current_price' => '¥6.00',
        ],
        [
            'id' => 1,
            'img_url'=>$supermaketImg,
            'title' => '苹果3',
            'specs' => '2kg',
            'current_price' => '¥11.89',
        ],
        [
            'id' => 2,
            'img_url'=>$supermaketImg,
            'base_uri' => '/supermarket/detail',
            'title' => '橘子4',
            'specs' => '1kg',
            'current_price' => '¥6.00',
        ],
        [
            'id' => 1,
            'img_url'=>$supermaketImg,
            'title' => '橙子5',
            'specs' => '2kg',
            'current_price' => '¥11.89',
        ],
        [
            'id' => 2,
            'img_url'=>$supermaketImg,
            'base_uri' => '/supermarket/detail',
            'title' => '葡萄6',
            'specs' => '1kg',
            'current_price' => '¥6.00',
        ],
        [
            'id' => 1,
            'img_url'=>$supermaketImg,
            'base_uri' => '/supermarket/detail',
            'title' => '西瓜7',
            'specs' => '2kg',
            'current_price' => '¥11.89',
        ],
        [
            'id' => 2,
            'img_url'=>$supermaketImg,
            'base_uri' => '/supermarket/detail',
            'title' => '梨8',
            'specs' => '1kg',
            'current_price' => '¥6.00',
        ],
    ],
];
echo "/supermarket?id=2&page=1"."\n";
$apiData['data'] = $supermaketData;
echo json_encode($apiData)."\n";
echo "\n"."\n"."\n"."\n";
//商品详情
$supermaketDetailData = [
    'id' => 1,
    'img_url'=>$supermaketImg,
    'base_uri' => '/supermarket/detail',
    'title' => '西红柿1',
    'specs' => '2kg',
    'current_price' => '¥11.89',
    'description' => "第一段描述:abdalkfjadklfjadlfjadlkfjalkdfjaldkfjalkfjalkdfjla<br/>第二段描述:dfaldkfjakljfalkj<br/>",
];
echo "/supermarket/detail?id=1"."\n";
$apiData['data'] = $supermaketDetailData;
echo json_encode($apiData)."\n";
echo "\n"."\n"."\n"."\n";
//兼职
$parttimejobData = [
    [
        'title' => '华为体验店促销员[10:00~15:00]',
        'location' => '中央大街体验店',
        'id' => 1,
        'base_uri' => '/parttimejob/detail',
        'current_price' => '¥400',
        'publish_time' => '2019-11-03 23:08:04',
    ],
    [
        'title' => '家教[数学,2小时]',
        'location' => '松北区保利水韵长滩',
        'id' => 2,
        'base_uri' => '/parttimejob/detail',
        'current_price' => '¥100',
        'publish_time' => '2019-11-03 23:08:04',
    ],
];
echo "/parttimejob"."\n";
$apiData['data'] = $parttimejobData;
echo json_encode($apiData)."\n";
echo "\n"."\n"."\n"."\n";
$parttimejobDetailData = [
    'title' => '家教[数学,2小时]',
    'description' => '要求：女,15~50岁',
    'location' => '松北区保利水韵长滩',
    'id' => 2,
    'base_uri' => '/parttimejob/detail',
    'current_price' => '¥100',
    'publish_time' => '2019-11-03 23:08:04',
    'cellphone' => '18012345678',
    'wechat' => '18012345678',
    'QQ' => '123456',
];
echo "/parttimejob/detail?id=2"."\n";
$apiData['data'] = $parttimejobDetailData;
echo json_encode($apiData)."\n";
echo "\n"."\n"."\n"."\n";
//门票
$ticketImg = 'https://ss0.bdstatic.com/70cFvHSh_Q1YnxGkpoWK1HF6hhy/it/u=3261888677,3364596947&fm=26&gp=0.jpg';
$ticketData = [
    [
        'title' => '极地馆门票[9:30~16:00,成人]',
        'id' => 1,
        'img_url'=>$ticketImg,
        'base_uri' => '/ticket/detail',
        'current_price' => '¥400',
        'original_price' => '¥500',
    ],
    [
        'title' => '冰雪大世界门票[9:30~20:00,成人]',
        'id' => 2,
        'img_url'=>$ticketImg,
        'base_uri' => '/ticket/detail',
        'current_price' => '¥120',
        'original_price' => '¥200',
    ],
];
echo "/ticket"."\n";
$apiData['data'] = $ticketData;
echo json_encode($apiData)."\n";
echo "\n"."\n"."\n"."\n";
echo "/ticket/detail?id=1"."\n";
$ticketDetailData = [
    'id' => 1,
    'img_url'=>$ticketImg,
    'base_uri' => '/ticket/detail',
    'title' => '极地馆门票[9:30~16:00,成人]',
    'location'=>'松北区融创旅游城华园',
    'current_price' => '¥400',
    'original_price' => '¥500',
    'description' => "第一段描述:abdalkfjadklfjadlfjadlkfjalkdfjaldkfjalkfjalkdfjla<br/>公交地铁描述:dfadafadfaldkfjakljfalkj<br/>",
];
$apiData['data'] = $ticketDetailData;
echo json_encode($apiData)."\n";
echo "\n"."\n"."\n"."\n";
//住宿
$hotelImg = 'https://gss0.bdstatic.com/94o3dSag_xI4khGkpoWK1HF6hhy/baike/w%3D268%3Bg%3D0/sign=2c68ebb9c9ef76093c0b9e9916e6c4f1/78310a55b319ebc497d999bd8026cffc1e171674.jpg';
$hotelData = [
    [
        'title' => '香格里拉',
        'id' => 1,
        'img_url'=>$hotelImg,
        'base_uri' => '/hotel/detail',
        'current_price' => '¥400',
        'original_price' => '¥500',
    ],
    [
        'title' => '民宿',
        'id' => 2,
        'img_url'=>$hotelImg,
        'base_uri' => '/hotel/detail',
        'current_price' => '¥120',
        'original_price' => '¥200',
    ],
];
echo "/hotel"."\n";
$apiData['data'] = $hotelData;
echo json_encode($apiData)."\n";
echo "\n"."\n"."\n"."\n";
$hotelDetailData = [
    'id' => 1,
    'img_url'=>$ticketImg,
    'base_uri' => '/ticket/detail',
    'title' => '香格里拉',
    'location'=>'松北区融创旅游城华园',
    'current_price' => '¥400',
    'original_price' => '¥500',
    'description' => "第一段描述:abdalkfjadklfjadlfjadlkfjalkdfjaldkfjalkfjalkdfjla<br/>公交地铁描述:dfadafadfaldkfjakljfalkj<br/>",
];
echo "/hotel/detail?id=1"."\n";
$apiData['data'] = $hotelDetailData;
echo json_encode($apiData)."\n";
echo "\n"."\n"."\n"."\n";
//餐饮
$cateringImg = 'http://t11.baidu.com/it/u=2890804336,2337375951&fm=173&app=25&f=JPEG?w=640&h=418&s=830B9347C6EB0B0D7894F5730300D051';
$cateringData = [
    [
        'title' => '东北菜',
        'id' => 1,
        'img_url'=>$cateringImg,
        'base_uri' => '/catering/detail',
        'current_price' => '¥400',
        'original_price' => '¥500',
    ],
    [
        'title' => '铁锅炖',
        'id' => 2,
        'img_url'=>$cateringImg,
        'base_uri' => '/catering/detail',
        'current_price' => '¥120',
        'original_price' => '¥200',
    ],
];
echo "/catering"."\n";
$apiData['data'] = $cateringData;
echo json_encode($apiData)."\n";
echo "\n"."\n"."\n"."\n";
$cateringDetailData = [
    'id' => 1,
    'img_url'=>$cateringImg,
    'base_uri' => '/catering/detail',
    'title' => '香格里拉',
    'location'=>'松北区融创旅游城华园',
    'current_price' => '¥400',
    'original_price' => '¥500',
    'description' => "第一段描述:abdalkfjadklfjadlfjadlkfjalkdfjaldkfjalkfjalkdfjla<br/>公交地铁描述:dfadafadfaldkfjakljfalkj<br/>",
];
echo "/catering/detail?id=1"."\n";
$apiData['data'] = $cateringDetailData;
echo json_encode($apiData)."\n";
echo "\n"."\n"."\n"."\n";
//校园网
//租房
$renthouseData = [
    'condition' => [
        'search_room' => 0,
        'search_rental' => [
            'from' => 0,
            'to' => 0,
        ],
    ],
    'house_list' => [
        [
            'title'=>'融创',
            'img_url'=>$supermaketImg,
            'square' => '90m²',
            'description' => '2室1厅1卫',
            'orientations' => '南北通透',
            'location'=>'松北区融创旅游城华园',
            'base_uri' => '/renthouse/detail',
            'id'=>1,
            'current_price' => '¥1500/月',
            'publish_time' => '3小时前',
        ],
        [
            'title'=>'保利',
            'img_url'=>$supermaketImg,
            'square' => '90m²',
            'description' => '2室1厅1卫',
            'orientations' => '南北通透',
            'location'=>'松北区中源大道',
            'base_uri' => '/renthouse/detail',
            'id'=>2,
            'current_price' => '¥1500/月',
            'publish_time' => '3小时前',
        ],
        [
            'title'=>'世纪花园',
            'img_url'=>$supermaketImg,
            'square' => '90m²',
            'description' => '2室1厅1卫',
            'orientations' => '南北通透',
            'location'=>'松北区世茂大道',
            'base_uri' => '/renthouse/detail',
            'id'=>3,
            'current_price' => '¥1500/月',
            'publish_time' => '3小时前',
        ],
    ],
];
echo "/renthouse"."\n";
$apiData['data'] = $renthouseData;
echo json_encode($apiData)."\n";
echo "\n"."\n"."\n"."\n";
//发布租房信息post
//租车
//二手物
//快递
//失物招领
//驾考报名
//广告位
//今日推荐(更多)
//兼职推荐(更多)
//生活信息(更多)
//我的
//我的订单
//我的兼职
//我的租房
//二手物

//商户模式

//订单管理
//商品管理
//商家管理？
//快递管理
//财务管理 账单
//认证管理
exit;