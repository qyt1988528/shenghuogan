CREATE TABLE `nwdn_create_task_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `image_link` varchar(255) CHARACTER SET utf8 DEFAULT '',
  `image_md5` varchar(255) CHARACTER SET utf8 DEFAULT '',
  `host` varchar(255) DEFAULT NULL,
  `path` varchar(255) DEFAULT NULL,
  `source_name` varchar(255) CHARACTER SET utf8 DEFAULT '' COMMENT '调用方',
  `task_id` varchar(255) CHARACTER SET utf8 DEFAULT NULL COMMENT '任务ID',
  `state` tinyint(4) DEFAULT '-2',
  `create_task_data` text CHARACTER SET utf8,
  `update_task_data` text CHARACTER SET utf8,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `status` tinyint(4) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='你我当年创建任务记录表';


CREATE TABLE `tencent_filter_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `host` varchar(255) DEFAULT NULL,
  `path` varchar(255) DEFAULT NULL,
  `filter` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '滤镜效果编码',
  `filter_all_data` text,
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
  `status` tinyint(4) DEFAULT '1' COMMENT '1：有效，-1：无效',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='腾讯滤镜调用日志';