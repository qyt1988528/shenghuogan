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

];
