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

 Date: 25/10/2019 07:11:43
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for merchant
-- ----------------------------
DROP TABLE IF EXISTS `merchant`;
CREATE TABLE `merchant` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '商户ID',
  `name` varchar(100) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '商户名称',
  `image` varchar(255) CHARACTER SET utf8 NOT NULL COMMENT '商户封面',
  `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1:有效,-1无效',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='商户表';

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
  `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1:有效,-1:无效',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COMMENT='经营模式';

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
  `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1:有效,-1:无效',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='经营模式下的类别表';

-- ----------------------------
-- Table structure for parttimejob
-- ----------------------------
DROP TABLE IF EXISTS `parttimejob`;
CREATE TABLE `parttimejob` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '兼职标题',
  `description` text CHARACTER SET utf8 NOT NULL COMMENT '兼职描述',
  `location` varchar(255) NOT NULL COMMENT '地点',
  `commission` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '佣金',
  `is_hiring` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1:在招人,-1:不招人',
  `publish_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '发布时间',
  `end_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '截止时间',
  `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1:有效,-1:无效',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='兼职表';

-- ----------------------------
-- Table structure for position
-- ----------------------------
DROP TABLE IF EXISTS `position`;
CREATE TABLE `position` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '位置ID',
  `name` varchar(50) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '位置名称',
  `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1:有效,-1:无效',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='显示位置表';

-- ----------------------------
-- Table structure for supermarket_goods
-- ----------------------------
DROP TABLE IF EXISTS `supermarket_goods`;
CREATE TABLE `supermarket_goods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1:有效,-1:无效',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='超市商品表';

-- ----------------------------
-- Table structure for user
-- ----------------------------
DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '用户ID',
  `name` varchar(0) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '用户名',
  `password` varchar(0) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '密码',
  `merchant_id` int(11) NOT NULL DEFAULT '0' COMMENT '商户ID',
  `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1:有效,-1:无效',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户表';

SET FOREIGN_KEY_CHECKS = 1;
