<?php
return [
    'data_status' => [
        'valid' => 1,//有效
        'invalid' => -1,//无效 指删除 这里删除只改状态并非物理删除
    ],
    'specs_unit' => [
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
    'selling_status' => [
        'selling' => 1,//在售
        'unselling' => -1,//下架
    ],
    'is_recommend' => [
        'recommend' => 1,//推荐
        'normal' => -1,//正常
    ],
    'error_message' => [
        'invalid_input' => 'Invalid input!',
        'try_later' => 'Network Error. Please Try Again Later',
        'not_exist' => 'It does not exist!',
    ]

];
