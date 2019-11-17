/*
 Navicat Premium Data Transfer

 Source Server         : local
 Source Server Type    : MySQL
 Source Server Version : 50722
 Source Host           : localhost:3306
 Source Schema         : gc_group

 Target Server Type    : MySQL
 Target Server Version : 50722
 File Encoding         : 65001

 Date: 18/11/2019 07:25:27
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for address
-- ----------------------------
DROP TABLE IF EXISTS `address`;
CREATE TABLE `address` (
  `address_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '账号ID',
  `name` varchar(45) NOT NULL DEFAULT '' COMMENT '姓名',
  `cellphone` varchar(16) NOT NULL DEFAULT '手机号码',
  `province` varchar(45) NOT NULL DEFAULT '' COMMENT '省',
  `city` varchar(45) NOT NULL DEFAULT '' COMMENT '城市',
  `county` varchar(45) NOT NULL DEFAULT '' COMMENT '区县',
  `detailed_address` varchar(256) NOT NULL DEFAULT '' COMMENT '详细地址',
  `is_default` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否为默认地址',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态',
  `add_time` int(11) NOT NULL,
  `update_time` int(11) NOT NULL,
  PRIMARY KEY (`address_id`)
) ENGINE=InnoDB AUTO_INCREMENT=76 DEFAULT CHARSET=utf8 COMMENT='地址管理';

-- ----------------------------
-- Table structure for favorite
-- ----------------------------
DROP TABLE IF EXISTS `favorite`;
CREATE TABLE `favorite` (
  `favorite_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '账号ID',
  `goods_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商品ID',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态',
  `add_time` int(11) NOT NULL,
  `update_time` int(11) NOT NULL,
  PRIMARY KEY (`favorite_id`)
) ENGINE=InnoDB AUTO_INCREMENT=122 DEFAULT CHARSET=utf8 COMMENT='收藏管理';

-- ----------------------------
-- Table structure for goods
-- ----------------------------
DROP TABLE IF EXISTS `goods`;
CREATE TABLE `goods` (
  `goods_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '商品ID',
  `goods_code` varchar(64) NOT NULL DEFAULT '' COMMENT '商品编码',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '商品名称',
  `item_name` varchar(50) NOT NULL DEFAULT '项目' COMMENT '项目名称（规格页面下面的名称，数量的上方）',
  `type_name` varchar(50) NOT NULL DEFAULT '种类' COMMENT '类型名称（规格页面上面的名称，数量上方的上方）',
  `description` blob NOT NULL COMMENT '商品描述',
  `goods_cover` varchar(256) NOT NULL DEFAULT '' COMMENT '封面图片地址',
  `amount` decimal(14,2) NOT NULL DEFAULT '0.00' COMMENT '原始金额',
  `self_amount` decimal(14,2) NOT NULL DEFAULT '0.00' COMMENT '单独购买金额',
  `actual_amount` decimal(14,2) NOT NULL DEFAULT '0.00' COMMENT '成团实际金额',
  `sort` int(11) NOT NULL DEFAULT '99' COMMENT '排序',
  `active_time` int(11) NOT NULL COMMENT '有效时间（秒）',
  `base_count` int(11) DEFAULT '0' COMMENT '基数（用于显示收藏数）',
  `base_order_count` int(11) unsigned NOT NULL DEFAULT '1500' COMMENT '基础销量数',
  `goods_status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1-在售,-1-下架',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '商品状态',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`goods_id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8 COMMENT='商品表';

-- ----------------------------
-- Table structure for goods_item
-- ----------------------------
DROP TABLE IF EXISTS `goods_item`;
CREATE TABLE `goods_item` (
  `goods_item_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `goods_item_code` varchar(64) NOT NULL DEFAULT '0' COMMENT '编码',
  `goods_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商品ID',
  `name` varchar(32) NOT NULL DEFAULT '' COMMENT '名称',
  `description` varchar(256) NOT NULL DEFAULT '' COMMENT '描述',
  `url` varchar(256) NOT NULL DEFAULT '' COMMENT '地址',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1:有效,-1无效',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`goods_item_id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8 COMMENT='商品项目';

-- ----------------------------
-- Table structure for goods_product
-- ----------------------------
DROP TABLE IF EXISTS `goods_product`;
CREATE TABLE `goods_product` (
  `goods_product_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '产品ID',
  `goods_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '所属商品ID',
  `title` varchar(50) NOT NULL DEFAULT '' COMMENT '产品标题',
  `description` varchar(200) NOT NULL DEFAULT '' COMMENT '产品描述',
  `url` varchar(256) NOT NULL DEFAULT '' COMMENT '地址',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1-有效',
  `add_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`goods_product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8 COMMENT='产品表';

-- ----------------------------
-- Table structure for goods_relation
-- ----------------------------
DROP TABLE IF EXISTS `goods_relation`;
CREATE TABLE `goods_relation` (
  `goods_relation_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `goods_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商品ID',
  `goods_item_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商品项目ID',
  `goods_type_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商品类型ID',
  `name` varchar(32) NOT NULL DEFAULT '' COMMENT '名称',
  `description` varchar(256) NOT NULL DEFAULT '' COMMENT '描述',
  `url` varchar(256) NOT NULL DEFAULT '' COMMENT '地址',
  `goods_cover` varchar(256) NOT NULL DEFAULT '' COMMENT '图片地址',
  `amount` decimal(14,2) DEFAULT '0.00' COMMENT '原始金额',
  `self_amount` decimal(14,2) DEFAULT '0.00' COMMENT '单独购买金额',
  `actual_amount` decimal(14,2) DEFAULT '0.00' COMMENT '成团实际金额',
  `goods_stock` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '库存',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1:有效,-1无效',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`goods_relation_id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8 COMMENT='商品关联';

-- ----------------------------
-- Table structure for goods_type
-- ----------------------------
DROP TABLE IF EXISTS `goods_type`;
CREATE TABLE `goods_type` (
  `goods_type_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `goods_type_code` varchar(64) NOT NULL DEFAULT '0' COMMENT '编码',
  `goods_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商品ID',
  `name` varchar(32) NOT NULL DEFAULT '' COMMENT '名称',
  `description` varchar(256) NOT NULL DEFAULT '' COMMENT '描述',
  `url` varchar(256) NOT NULL DEFAULT '' COMMENT '地址',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1:有效,-1无效',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`goods_type_id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8 COMMENT='商品类型';

-- ----------------------------
-- Table structure for goods_way
-- ----------------------------
DROP TABLE IF EXISTS `goods_way`;
CREATE TABLE `goods_way` (
  `goods_way_id` int(11) NOT NULL COMMENT '处理方法表ID',
  `relation_type` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '1-商品，2-产品',
  `relation_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商品ID或产品ID（依据relation_type）',
  `name` varchar(64) NOT NULL DEFAULT '' COMMENT '处理方法名称',
  `sub_name` varchar(64) NOT NULL DEFAULT '' COMMENT '处理方法副标题',
  `description` text NOT NULL COMMENT '处理方法详细描述',
  `image_url` varchar(256) NOT NULL DEFAULT '' COMMENT '图片地址',
  `url` varchar(256) NOT NULL DEFAULT '' COMMENT '详情地址',
  `sort` tinyint(4) NOT NULL DEFAULT '99' COMMENT '排序',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1-有效',
  `add_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`goods_way_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='处理方法表';

-- ----------------------------
-- Table structure for income
-- ----------------------------
DROP TABLE IF EXISTS `income`;
CREATE TABLE `income` (
  `income_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `order_id` int(11) NOT NULL DEFAULT '0',
  `source_level` tinyint(4) NOT NULL DEFAULT '0' COMMENT '来源等级',
  `snapshot` blob,
  `type` tinyint(4) unsigned NOT NULL DEFAULT '1' COMMENT '1增加(收入) 2减少(提现) /GCL/Group/Order.php',
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '收入佣金金额',
  `income_status` tinyint(4) unsigned NOT NULL DEFAULT '1' COMMENT '收入状态 /GCL/Group/Order.php',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态1正常、-1删除',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`income_id`)
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8 COMMENT='收入';

-- ----------------------------
-- Table structure for order
-- ----------------------------
DROP TABLE IF EXISTS `order`;
CREATE TABLE `order` (
  `order_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `order_no` varchar(50) NOT NULL DEFAULT '' COMMENT '订单编号',
  `serial_no` varchar(50) NOT NULL DEFAULT '' COMMENT '支付流水号',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户uid',
  `team_id` int(11) NOT NULL DEFAULT '0',
  `goods_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '原始总金额',
  `order_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '订单实付总金额',
  `pay_channel` int(6) NOT NULL DEFAULT '0' COMMENT '支付渠道',
  `pay_time` int(11) NOT NULL DEFAULT '0' COMMENT '付款时间',
  `pay_status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1:待付款,3已支付',
  `order_status` tinyint(4) NOT NULL DEFAULT '10' COMMENT '10:有效,20:失效,30:完成,40:退货',
  `order_invalid_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '订单失效时间',
  `form_id` varchar(50) NOT NULL DEFAULT '微信form_id',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '修改时间',
  PRIMARY KEY (`order_id`)
) ENGINE=InnoDB AUTO_INCREMENT=320 DEFAULT CHARSET=utf8 COMMENT='订单表';

-- ----------------------------
-- Table structure for order_detail
-- ----------------------------
DROP TABLE IF EXISTS `order_detail`;
CREATE TABLE `order_detail` (
  `order_detail_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `order_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'order_id',
  `receiver` varchar(10) NOT NULL DEFAULT '' COMMENT '收货人的姓名',
  `cellphone` varchar(11) NOT NULL DEFAULT '' COMMENT '收货人的手机',
  `province` varchar(10) NOT NULL DEFAULT '' COMMENT '收货人的省份',
  `city` varchar(10) NOT NULL DEFAULT '' COMMENT '收货人的城市',
  `county` varchar(10) NOT NULL DEFAULT '' COMMENT '收货人的地区',
  `detailed_address` varchar(256) NOT NULL DEFAULT '' COMMENT '详细地址',
  `shipping_type` varchar(20) NOT NULL DEFAULT '' COMMENT '商品配送类型/GCL/Group/Shipping',
  `shipping_sn` varchar(50) NOT NULL DEFAULT '' COMMENT '商品配送单号',
  `delay_confirm` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '48小时后自动确认收货开关',
  `shipping_status` tinyint(4) NOT NULL DEFAULT '10' COMMENT '商品配送状态/GCL/Group/Shipping',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态',
  `order_end_time` int(11) NOT NULL DEFAULT '0' COMMENT '订单确认收货截止时间',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '修改时间',
  PRIMARY KEY (`order_detail_id`)
) ENGINE=InnoDB AUTO_INCREMENT=306 DEFAULT CHARSET=utf8 COMMENT='订单详情表';

-- ----------------------------
-- Table structure for order_goods
-- ----------------------------
DROP TABLE IF EXISTS `order_goods`;
CREATE TABLE `order_goods` (
  `order_goods_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `order_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'order_id',
  `goods_id` int(11) NOT NULL DEFAULT '0' COMMENT '商品的的id',
  `goods_name` varchar(100) NOT NULL DEFAULT '' COMMENT '商品的名称',
  `goods_num` int(11) NOT NULL DEFAULT '0' COMMENT '商品数量',
  `goods_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '商品原价',
  `goods_current_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '商品现价',
  `goods_item_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商品项目ID',
  `goods_type_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商品类型ID',
  `goods_attr` varchar(200) NOT NULL DEFAULT '' COMMENT '商品规格',
  `goods_cover` varchar(200) NOT NULL DEFAULT '' COMMENT '商品图片',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '修改时间',
  PRIMARY KEY (`order_goods_id`)
) ENGINE=InnoDB AUTO_INCREMENT=311 DEFAULT CHARSET=utf8 COMMENT='订单商品表';

-- ----------------------------
-- Table structure for order_refund
-- ----------------------------
DROP TABLE IF EXISTS `order_refund`;
CREATE TABLE `order_refund` (
  `order_refund_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `order_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'order_id',
  `bill_no` varchar(50) NOT NULL DEFAULT '' COMMENT '账单编号',
  `reason` varchar(50) NOT NULL DEFAULT '' COMMENT '退款原因',
  `description` text NOT NULL COMMENT '退款描述',
  `refund_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '退款金额',
  `order_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '应付款金额',
  `refund_status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '退款状态',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `updated_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '修改时间',
  PRIMARY KEY (`order_refund_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='订单退款表';

-- ----------------------------
-- Table structure for resource
-- ----------------------------
DROP TABLE IF EXISTS `resource`;
CREATE TABLE `resource` (
  `resource_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '资源ID',
  `relation_type` tinyint(4) NOT NULL DEFAULT '1' COMMENT '关联类型：10-商品banner，11-商品详情，20-产品',
  `relation_id` int(11) NOT NULL DEFAULT '0' COMMENT '关联ID',
  `resource_type` tinyint(4) NOT NULL DEFAULT '2' COMMENT '资源类型：1-文本，2-图片，3-音频，4-视频',
  `resource_url` varchar(256) NOT NULL DEFAULT '' COMMENT '资源地址/内容',
  `sort` tinyint(4) NOT NULL DEFAULT '99' COMMENT '排序优先显示1',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1-有效',
  `add_time` int(10) NOT NULL DEFAULT '0' COMMENT '添加时间',
  `update_time` int(10) NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`resource_id`)
) ENGINE=InnoDB AUTO_INCREMENT=77 DEFAULT CHARSET=utf8 COMMENT='资源表';

-- ----------------------------
-- Table structure for share_relation
-- ----------------------------
DROP TABLE IF EXISTS `share_relation`;
CREATE TABLE `share_relation` (
  `share_relation_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '分享关联表ID',
  `share_user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '分享人ID',
  `click_user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '点击人ID',
  `goods_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商品ID',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态',
  `add_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`share_relation_id`)
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8 COMMENT='上下级关联关系表';

-- ----------------------------
-- Table structure for survey
-- ----------------------------
DROP TABLE IF EXISTS `survey`;
CREATE TABLE `survey` (
  `survey_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建人',
  `name` varchar(32) NOT NULL DEFAULT '' COMMENT '姓名',
  `cellphone` varchar(32) NOT NULL DEFAULT '' COMMENT '手机号码',
  `wx_code` varchar(128) NOT NULL DEFAULT '' COMMENT '微信号',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '修改时间',
  PRIMARY KEY (`survey_id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8 COMMENT='调查表';

-- ----------------------------
-- Table structure for team
-- ----------------------------
DROP TABLE IF EXISTS `team`;
CREATE TABLE `team` (
  `team_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建团的用户id',
  `goods_id` varchar(50) NOT NULL DEFAULT '0' COMMENT '商品id',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态',
  `team_end_time` int(10) NOT NULL DEFAULT '0' COMMENT '团结束时间戳（商品active_time+团创建时间）',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '修改时间',
  PRIMARY KEY (`team_id`)
) ENGINE=InnoDB AUTO_INCREMENT=226 DEFAULT CHARSET=utf8 COMMENT='成团表';

-- ----------------------------
-- Table structure for team_member
-- ----------------------------
DROP TABLE IF EXISTS `team_member`;
CREATE TABLE `team_member` (
  `team_member_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '团成员列表ID',
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户ID',
  `team_id` int(11) NOT NULL DEFAULT '0' COMMENT '团ID',
  `order_id` int(11) NOT NULL DEFAULT '0' COMMENT '参团时的订单',
  `goods_id` int(11) NOT NULL DEFAULT '0' COMMENT '商品ID',
  `team_end_time` int(10) NOT NULL DEFAULT '0' COMMENT '团结束时间',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1-有效',
  `add_time` int(10) NOT NULL DEFAULT '0' COMMENT '添加时间',
  `update_time` int(10) NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`team_member_id`)
) ENGINE=InnoDB AUTO_INCREMENT=89 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for user
-- ----------------------------
DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `user_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `openid` varchar(50) NOT NULL DEFAULT '' COMMENT '微信ID',
  `access_token` varchar(64) NOT NULL DEFAULT '' COMMENT 'access_token登录凭证',
  `cellphone` varchar(32) NOT NULL DEFAULT '' COMMENT '手机号码',
  `nickname` blob NOT NULL COMMENT '微信昵称',
  `gender` tinyint(4) NOT NULL DEFAULT '0' COMMENT '性别',
  `avatar` varchar(255) NOT NULL DEFAULT '' COMMENT '头像',
  `country` varchar(50) NOT NULL DEFAULT '' COMMENT '国家',
  `province` varchar(50) NOT NULL DEFAULT '' COMMENT '省份',
  `city` varchar(50) NOT NULL DEFAULT '' COMMENT '城市',
  `account_balance` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '账户余额',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '修改时间',
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `openid` (`openid`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8 COMMENT='用户表';

-- ----------------------------
-- Table structure for withdrawal_cash
-- ----------------------------
DROP TABLE IF EXISTS `withdrawal_cash`;
CREATE TABLE `withdrawal_cash` (
  `withdrawal_cash_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `wechat_sn` varchar(50) NOT NULL DEFAULT '' COMMENT '账单编号(来源于微信)',
  `amount` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '提现金额',
  `withdrawal_path` varchar(50) NOT NULL DEFAULT '' COMMENT '提现地址',
  `message` text NOT NULL COMMENT '提现描述',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1提现中、2提现失败、3提现成功',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`withdrawal_cash_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='提现';

SET FOREIGN_KEY_CHECKS = 1;
