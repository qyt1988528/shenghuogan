-- 2020-01-14 租房表 加是否正在出租的字段
ALTER `rent_house` add COLUMN `is_renting` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1-出租,-1已租出去' ;

TRUNCATE `order`;TRUNCATE `order_detail`;TRUNCATE `order_goods`;
