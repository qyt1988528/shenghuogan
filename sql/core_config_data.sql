/*
 Navicat Premium Data Transfer

 Source Server         : local-mysql
 Source Server Type    : MySQL
 Source Server Version : 80012
 Source Host           : localhost:3306
 Source Schema         : soufeel_ai

 Target Server Type    : MySQL
 Target Server Version : 80012
 File Encoding         : 65001

 Date: 22/10/2019 10:01:53
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for core_config_data
-- ----------------------------
DROP TABLE IF EXISTS `core_config_data`;
CREATE TABLE `core_config_data`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `store` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `path` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `value` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '值',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '公共配置' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of core_config_data
-- ----------------------------
INSERT INTO `core_config_data` VALUES (1, NULL, 'qiniu', '{\"_url\":\"\\/admin\\/system\\/config\\/multiple\",\"key\":\"qiniu\",\"AccessKey\":\"o614SUzXUjQy-HP6LCalMo8yYUfdC6lHEJAmyG7F\",\"SecretKey\":\"9Ib0u1h1UP-WiseGny23dmLbrlFRNrOmpRfqkON3\",\"buket\":\"test\",\"domain\":\"http://qiniu.wanjunjiaoyu.com/\",\"lang\":\"en\"}');
INSERT INTO `core_config_data` VALUES (2, NULL, 'qiniu/token', '{\"token\":\"o614SUzXUjQy-HP6LCalMo8yYUfdC6lHEJAmyG7F:06xIJEP0nUJ29kP4MBgeakXc21Q=:eyJzY29wZSI6InRlc3QiLCJkZWFkbGluZSI6MTU3MDg3NDcyNn0=\",\"expire\":1570874626}');

SET FOREIGN_KEY_CHECKS = 1;
