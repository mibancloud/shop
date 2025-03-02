CREATE TABLE IF NOT EXISTS `__PREFIX__mobilediy_page` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `page_name` varchar(255) NOT NULL DEFAULT '' COMMENT '页面名称',
  `status` enum('home','custom') DEFAULT 'custom' COMMENT '状态',
  `page_data` longtext NOT NULL COMMENT '页面数据',
  `weigh` int(10) DEFAULT '100' COMMENT '权重',
  `createtime` bigint(16) DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) DEFAULT NULL COMMENT '更新时间',
  `deletetime` bigint(16) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=100 DEFAULT CHARSET=utf8mb4 COMMENT='前端diy页面表';

