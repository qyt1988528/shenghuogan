<?php
return [
    'data_status' => [
        'valid' => 1,//有效
        'invalid' => -1,//无效 指删除 这里删除只改状态并非物理删除
    ],
    'supermarket_specs_unit' => [
        //id=>unit_description
        1 => 'kg',
        2 => '瓶',
        3 => '盒',
        4 => '枚',
        5 => '包',
        6 => '袋',
        7 => '只',
        8 => '份',
        9 => '桶',
        10 => 'ML',
        11 => 'L',
    ],
    'supermarket_goods_type' => [
        1 => '精选水果',
        2 => '休闲食品',
        3 => '酒水饮品',
        4 => '生活用品',
        5 => '其他',
    ],
    'selling_status' => [
        'selling' => 1,//在售
        'unselling' => -1,//下架
    ],
    'renting_status' => [
        'renting' => 1,//在租
        'unrenting' => -1,//已租出去
    ],
    'is_recommend' => [
        'recommend' => 1,//推荐
        'normal' => -1,//正常
    ],
    'hiring_status' => [
        'hiring' => 1,//招
        'unhiring' => -1,//不招
    ],
    'error_message' => [
        'invalid_input' => 'Invalid input!',
        'try_later' => 'Network Error. Please Try Again Later',
        'not_exist' => 'It does not exist!',
        'unlogin' => 'Not logged in.Please Login!',
        'unmerchant' => 'Not Merchant.Please contact the platform!',
        'unplatform' => 'Not Platform User.Please contact the platform!',
        'cellphone' => 'Incorrect phone number!',
    ],
    'express_server' => [],
    'express_take_specs' => [],
    'express_take_optional_service' => [],
    'address_status' => [
        'default' => 1,
        'undefault' => -1,
    ],
    'region_level' => [
        'province' => 1,
        'city' => 2,
        'county' => 3,
    ],
    'goods_types' => [
        'catering' => [
            'type_id' => 1,
            'goods_type' => 'catering',
            'title' => '美食',
            'desc' => '该美食',
            'model' => 'Catering\Model\Catering'
        ],
        'driving_test' => [
            'type_id' => 2,
            'goods_type' => 'driving_test',
            'title' => '驾考',
            'desc' => '该驾校',
            'model' => 'Driver\Model\DrivingTest'
        ],
        'express_send' => [
            'type_id' => 3,
            'goods_type' => 'express_send',
            'title' => '代发快递',
            'desc' => '该代发快递',
            'model' => 'Express\Model\ExpressSend'
        ],
        'express_take' => [
            'type_id' => 4,
            'goods_type' => 'express_take',
            'title' => '代取快递',
            'desc' => '该代取快递',
            'model' => 'Express\Model\ExpressTake'
        ],
        'hotel' => [
            'type_id' => 5,
            'goods_type' => 'hotel',
            'title' => '酒店',
            'desc' => '该酒店',
            'model' => 'Hotel\Model\Hotel'
        ],
        'lostfound' => [
            'type_id' => 6,
            'goods_type' => 'lostfound',
            'title' => '失物招领',
            'desc' => '该失物招领',
            'model' => 'Lostfound\Model\Lostfound'
        ],
        'parttimejob' => [
            'type_id' => 7,
            'goods_type' => 'parttimejob',
            'title' => '兼职',
            'desc' => '该兼职',
            'model' => 'Parttimejob\Model\Parttimejob'
        ],
        'rent_car' => [
            'type_id' => 8,
            'goods_type' => 'rent_car',
            'title' => '租车',
            'desc' => '该租车',
            'model' => 'Rent\Model\RentCar'
        ],
        'rent_house' => [
            'type_id' => 9,
            'goods_type' => 'rent_house',
            'title' => '租房',
            'desc' => '该租房',
            'model' => 'Rent\Model\RentHouse'
        ],
        'second' => [
            'type_id' => 9,
            'goods_type' => 'second',
            'title' => '二手物品',
            'desc' => '该二手物品',
            'model' => 'Secondhand\Model\Second'
        ],
        'supermarket_goods' => [
            'type_id' => 10,
            'goods_type' => 'supermarket_goods',
            'title' => '超市',
            'desc' => '该超市商品',
            'model' => 'Supermarket\Model\SupermarketGoods'
        ],
        'ticket' => [
            'type_id' => 11,
            'goods_type' => 'ticket',
            'title' => '门票',
            'desc' => '该门票',
            'model' => 'Ticket\Model\Ticket'
        ],
        'school' => [
            'type_id' => 12,
            'goods_type' => 'school',
            'title' => '缴费',
            'desc' => '该缴费',
            'model' => 'School\Model\School'
        ],
    ],

];
