CREATE TABLE `nwdn_create_face_task` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `ethnicity` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT '' COMMENT '肤色',
  `sku` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT '' COMMENT 'sku',
  `emid` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT '' COMMENT 'emid',
  `background_url` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT '' COMMENT '模板图',
  `input_url` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT '' COMMENT '头像图片url',
  `output_url` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT '' COMMENT '换脸后图片的url',
  `taskid` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT '' COMMENT '你我当年换脸任务ID',
  `phase` int(5) DEFAULT '-1' COMMENT '任务状态',
  `host` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT '',
  `path` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT '',
  `create_task_data` text CHARACTER SET utf8 COLLATE utf8_general_ci COMMENT '创建你我当年换脸任务时的返回数据',
  `update_task_data` text CHARACTER SET utf8 COLLATE utf8_general_ci COMMENT '查询换脸任务数据时的返回数据',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `status` tinyint(4) DEFAULT '1' COMMENT '状态1有效，-1无效',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8