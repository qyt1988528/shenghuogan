/*
 Navicat Premium Data Transfer

 Source Server         : local
 Source Server Type    : MySQL
 Source Server Version : 50722
 Source Host           : localhost:3306
 Source Schema         : shenghuogang

 Target Server Type    : MySQL
 Target Server Version : 50722
 File Encoding         : 65001

 Date: 09/11/2019 23:31:19
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for catering
-- ----------------------------
DROP TABLE IF EXISTS `catering`;
CREATE TABLE `catering` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 ROW_FORMAT=FIXED COMMENT='餐饮表';

-- ----------------------------
-- Table structure for core_config_data
-- ----------------------------
DROP TABLE IF EXISTS `core_config_data`;
CREATE TABLE `core_config_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `store` varchar(50) DEFAULT NULL,
  `path` varchar(255) DEFAULT NULL,
  `value` text COMMENT '值',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='公共配置';

-- ----------------------------
-- Records of core_config_data
-- ----------------------------
BEGIN;
INSERT INTO `core_config_data` VALUES (1, NULL, 'qiniu', '{\"_url\":\"\\/admin\\/system\\/config\\/multiple\",\"key\":\"qiniu\",\"AccessKey\":\"o614SUzXUjQy-HP6LCalMo8yYUfdC6lHEJAmyG7F\",\"SecretKey\":\"9Ib0u1h1UP-WiseGny23dmLbrlFRNrOmpRfqkON3\",\"buket\":\"test\",\"domain\":\"http://qiniu.wanjunjiaoyu.com/\",\"lang\":\"en\"}');
INSERT INTO `core_config_data` VALUES (2, NULL, 'qiniu/token', '{\"token\":\"o614SUzXUjQy-HP6LCalMo8yYUfdC6lHEJAmyG7F:06xIJEP0nUJ29kP4MBgeakXc21Q=:eyJzY29wZSI6InRlc3QiLCJkZWFkbGluZSI6MTU3MDg3NDcyNn0=\",\"expire\":1570874626}');
COMMIT;

-- ----------------------------
-- Table structure for express
-- ----------------------------
DROP TABLE IF EXISTS `express`;
CREATE TABLE `express` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 ROW_FORMAT=FIXED COMMENT='快递表';

-- ----------------------------
-- Table structure for hotel
-- ----------------------------
DROP TABLE IF EXISTS `hotel`;
CREATE TABLE `hotel` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `merchant_id` int(11) NOT NULL DEFAULT '0' COMMENT '商户ID',
  `img_url` varchar(100) NOT NULL DEFAULT '' COMMENT '封面',
  `original_price` decimal(14,2) NOT NULL DEFAULT '0.00' COMMENT '初始价格',
  `self_price` decimal(14,2) NOT NULL DEFAULT '0.00' COMMENT '单独购买价格',
  `together_price` decimal(14,2) NOT NULL DEFAULT '0.00' COMMENT '拼单价格',
  `location` varchar(100) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '位置',
  `description` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '描述',
  `create_time` datetime NOT NULL DEFAULT '2000-01-01 00:00:00' COMMENT '创建时间',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1:有效,-1:无效',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 ROW_FORMAT=FIXED COMMENT='酒店表';

-- ----------------------------
-- Table structure for merchant
-- ----------------------------
DROP TABLE IF EXISTS `merchant`;
CREATE TABLE `merchant` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '商户ID',
  `name` varchar(100) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '商户名称',
  `image` varchar(255) CHARACTER SET utf8 NOT NULL COMMENT '商户封面',
  `create_time` datetime NOT NULL DEFAULT '2000-01-01 00:00:00' COMMENT '创建时间',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1:有效,-1无效',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='商户表';

-- ----------------------------
-- Table structure for merchant_operation_log
-- ----------------------------
DROP TABLE IF EXISTS `merchant_operation_log`;
CREATE TABLE `merchant_operation_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `merchant_id` int(11) NOT NULL DEFAULT '0' COMMENT '商户ID',
  `content` text CHARACTER SET utf8 NOT NULL COMMENT '操作内容',
  `post_data` text CHARACTER SET utf8mb4 NOT NULL COMMENT '操作内容',
  `create_time` datetime NOT NULL DEFAULT '2000-01-01 00:00:00' COMMENT '创建时间',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1:有效,-1:无效',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='商户操作日志表';

-- ----------------------------
-- Table structure for operation_mode
-- ----------------------------
DROP TABLE IF EXISTS `operation_mode`;
CREATE TABLE `operation_mode` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '经营模式ID',
  `name` varchar(100) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '经营模式',
  `image` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '模式图标url',
  `is_show` tinyint(4) NOT NULL DEFAULT '1' COMMENT '是否展示',
  `sort` int(5) NOT NULL DEFAULT '99' COMMENT '排序',
  `create_time` datetime NOT NULL DEFAULT '2000-01-01 00:00:00' COMMENT '创建时间',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1:有效,-1:无效',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='经营模式';

-- ----------------------------
-- Records of operation_mode
-- ----------------------------
BEGIN;
INSERT INTO `operation_mode` VALUES (1, '超市', '/image/index_icon1.png', 1, 1, '2019-10-13 10:02:44', '2019-10-24 22:23:22', 1);
INSERT INTO `operation_mode` VALUES (2, '兼职', '/image/index_icon2.png', 1, 2, '2019-10-13 10:02:54', '2019-10-24 22:23:28', 1);
INSERT INTO `operation_mode` VALUES (3, '门票', '/image/index_icon3.png', 1, 3, '2019-10-13 10:03:08', '2019-10-24 22:23:32', 1);
INSERT INTO `operation_mode` VALUES (4, '住宿', '/image/index_icon4.png', 1, 4, '2019-10-13 10:03:20', '2019-10-24 22:23:36', 1);
INSERT INTO `operation_mode` VALUES (5, '餐饮', '/image/index_icon5.png', 1, 5, '2019-10-13 10:03:27', '2019-10-24 22:23:40', 1);
INSERT INTO `operation_mode` VALUES (6, '校园网', '/image/index_icon6.png', 1, 6, '2019-10-13 10:03:54', '2019-10-24 22:23:45', 1);
INSERT INTO `operation_mode` VALUES (7, '租房', '/image/index_icon7.png', 1, 7, '2019-10-13 10:04:01', '2019-10-24 22:23:49', 1);
INSERT INTO `operation_mode` VALUES (8, '租车', '/image/index_icon8.png', 1, 8, '2019-10-13 10:04:10', '2019-10-24 22:23:54', 1);
INSERT INTO `operation_mode` VALUES (9, '二手物', '/image/index_icon9.png', 1, 9, '2019-10-13 10:04:20', '2019-10-24 22:23:58', 1);
INSERT INTO `operation_mode` VALUES (10, '快递', '/image/index_icon10.png', 1, 10, '2019-10-13 10:04:33', '2019-10-24 22:24:04', 1);
COMMIT;

-- ----------------------------
-- Table structure for operation_mode_type
-- ----------------------------
DROP TABLE IF EXISTS `operation_mode_type`;
CREATE TABLE `operation_mode_type` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '经营模式类别id',
  `operation_mode_id` int(11) NOT NULL DEFAULT '0' COMMENT '经营模式id',
  `name` varchar(100) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '类别名称',
  `create_time` datetime NOT NULL DEFAULT '2000-01-01 00:00:00' COMMENT '创建时间',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1:有效,-1:无效',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='经营模式下的类别表';

-- ----------------------------
-- Table structure for parttimejob
-- ----------------------------
DROP TABLE IF EXISTS `parttimejob`;
CREATE TABLE `parttimejob` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '发布者用户ID',
  `merchant_id` int(11) NOT NULL DEFAULT '0' COMMENT '发布者商户ID',
  `title` varchar(100) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '兼职标题',
  `description` text CHARACTER SET utf8 NOT NULL COMMENT '兼职描述',
  `location` varchar(255) NOT NULL COMMENT '地点',
  `commission` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '佣金',
  `is_hiring` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1:在招人,-1:不招人',
  `publish_time` datetime NOT NULL DEFAULT '2000-01-01 00:00:00' COMMENT '发布时间',
  `end_time` datetime NOT NULL DEFAULT '2000-01-01 00:00:00' COMMENT '截止时间',
  `create_time` datetime NOT NULL DEFAULT '2000-01-01 00:00:00' COMMENT '创建时间',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1:有效,-1:无效',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='兼职表';

-- ----------------------------
-- Table structure for position
-- ----------------------------
DROP TABLE IF EXISTS `position`;
CREATE TABLE `position` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '位置ID',
  `name` varchar(50) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '位置名称',
  `create_time` datetime NOT NULL DEFAULT '2000-01-01 00:00:00' COMMENT '创建时间',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1:有效,-1:无效',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='显示位置表';

-- ----------------------------
-- Table structure for rent_car
-- ----------------------------
DROP TABLE IF EXISTS `rent_car`;
CREATE TABLE `rent_car` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 ROW_FORMAT=FIXED COMMENT='租车表';

-- ----------------------------
-- Table structure for rent_house
-- ----------------------------
DROP TABLE IF EXISTS `rent_house`;
CREATE TABLE `rent_house` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titile` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '租房标题',
  `square` decimal(14,2) NOT NULL DEFAULT '0.00' COMMENT '面积',
  `room` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '室',
  `parlour` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '厅',
  `toilet` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '卫',
  `orientations` varchar(20) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '朝向',
  `location` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '位置',
  `rental` decimal(14,2) NOT NULL DEFAULT '0.00' COMMENT '租金',
  `publish_time` datetime NOT NULL DEFAULT '2000-01-01 00:00:00' COMMENT '发布时间',
  `description` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '详情',
  `img_url` text CHARACTER SET utf8 NOT NULL COMMENT '图片(json)',
  `create_time` datetime NOT NULL DEFAULT '2000-01-01 00:00:00' COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='对外出租房源表';

-- ----------------------------
-- Table structure for school
-- ----------------------------
DROP TABLE IF EXISTS `school`;
CREATE TABLE `school` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 ROW_FORMAT=FIXED COMMENT='校园表';

-- ----------------------------
-- Table structure for second
-- ----------------------------
DROP TABLE IF EXISTS `second`;
CREATE TABLE `second` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 ROW_FORMAT=FIXED COMMENT='二手物品表';

-- ----------------------------
-- Table structure for supermarket_goods
-- ----------------------------
DROP TABLE IF EXISTS `supermarket_goods`;
CREATE TABLE `supermarket_goods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `merchant_id` int(11) NOT NULL DEFAULT '0' COMMENT '商户ID',
  `title` varchar(100) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '超市商品名称',
  `img_url` varchar(255) NOT NULL DEFAULT '' COMMENT '商品图',
  `type_id` int(11) NOT NULL DEFAULT '0' COMMENT '商品类别',
  `original_price` decimal(14,2) NOT NULL DEFAULT '0.00' COMMENT '初始价格',
  `self_price` decimal(14,2) NOT NULL DEFAULT '0.00' COMMENT '单独购买价格',
  `together_price` decimal(14,2) NOT NULL DEFAULT '0.00' COMMENT '拼团价格',
  `description` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '商品描述',
  `specs` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '商品规格',
  `stock` int(10) NOT NULL DEFAULT '0' COMMENT '库存',
  `is_selling` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1:在售,-1:下架',
  `is_recommend` tinyint(4) NOT NULL DEFAULT '-1' COMMENT '1:推荐,-1:正常',
  `sort` int(10) NOT NULL DEFAULT '999' COMMENT '排序',
  `base_fav_count` int(10) NOT NULL DEFAULT '16' COMMENT '基础点赞人数',
  `base_order_count` int(10) NOT NULL DEFAULT '7' COMMENT '基础购买人数',
  `create_time` datetime NOT NULL DEFAULT '2000-01-01 00:00:00' COMMENT '创建时间',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1:有效,-1:无效',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='超市商品表';

-- ----------------------------
-- Table structure for supermarket_goods_type
-- ----------------------------
DROP TABLE IF EXISTS `supermarket_goods_type`;
CREATE TABLE `supermarket_goods_type` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='超市商品分类表';

-- ----------------------------
-- Table structure for ticket
-- ----------------------------
DROP TABLE IF EXISTS `ticket`;
CREATE TABLE `ticket` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `merchant_id` int(11) NOT NULL COMMENT '商户ID',
  `title` varchar(100) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '标题',
  `img_url` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '封面',
  `original_price` decimal(14,2) NOT NULL DEFAULT '0.00' COMMENT '初始价格',
  `self_price` decimal(14,2) NOT NULL DEFAULT '0.00' COMMENT '单独购买价格',
  `together_price` decimal(14,2) NOT NULL DEFAULT '0.00' COMMENT '拼团价格',
  `description` varchar(255) NOT NULL DEFAULT '' COMMENT '门票详情',
  `location` varchar(100) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '位置',
  `stock` int(10) NOT NULL DEFAULT '0' COMMENT '库存',
  `is_selling` tinyint(4) NOT NULL COMMENT '1:在售,-1:下架',
  `create_time` datetime NOT NULL DEFAULT '2000-01-01 00:00:00' COMMENT '创建时间',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1:有效,-1:无效',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 ROW_FORMAT=FIXED COMMENT='门票表';

-- ----------------------------
-- Table structure for user
-- ----------------------------
DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '用户ID',
  `name` varchar(0) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '用户名',
  `password` varchar(0) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '密码',
  `merchant_id` int(11) NOT NULL DEFAULT '0' COMMENT '商户ID',
  `create_time` datetime NOT NULL DEFAULT '2000-01-01 00:00:00' COMMENT '创建时间',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1:有效,-1:无效',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='用户表';

SET FOREIGN_KEY_CHECKS = 1;
