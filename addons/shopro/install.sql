CREATE TABLE IF NOT EXISTS `__PREFIX__jobs`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED NULL DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '队列';


CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_activity` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NULL DEFAULT NULL COMMENT '活动名称',
  `classify` varchar(60) NULL DEFAULT NULL COMMENT '活动类目',
  `type` varchar(60) NULL DEFAULT NULL COMMENT '活动类别',
  `goods_ids` varchar(1200) NULL DEFAULT NULL COMMENT '商品组',
  `prehead_time` int(10) NULL DEFAULT NULL COMMENT '预热时间',
  `start_time` int(10) NULL DEFAULT NULL COMMENT '开始时间',
  `end_time` int(10) NULL DEFAULT NULL COMMENT '结束时间',
  `rules` text NULL COMMENT '规则',
  `richtext_id` int(11) NULL DEFAULT NULL COMMENT '活动说明',
  `richtext_title` varchar(255) NULL DEFAULT NULL COMMENT '说明标题',
  `createtime` bigint(16) NULL DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) NULL DEFAULT NULL COMMENT '更新时间',
  `deletetime` bigint(16) NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '营销活动';



CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_activity_gift_log`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `activity_id` int(11) NOT NULL DEFAULT 0 COMMENT '活动',
  `order_id` int(11) NOT NULL DEFAULT 0 COMMENT '订单',
  `user_id` int(11) NOT NULL DEFAULT 0 COMMENT '用户',
  `type` enum('coupon','score','money','goods') NULL DEFAULT NULL COMMENT '礼品类型:coupon=优惠券,score=积分,money=余额,goods=商品',
  `gift` varchar(60) NULL DEFAULT NULL COMMENT '礼品',
  `value` varchar(60) NULL DEFAULT NULL COMMENT '价值',
  `rules` varchar(2048) NULL DEFAULT NULL COMMENT '规则',
  `status` enum('waiting','finish','fail') NULL DEFAULT 'waiting' COMMENT '状态:waiting=等待赠送,finish=赠送完成,fail=赠送失败',
  `fail_msg` varchar(255) NULL DEFAULT NULL COMMENT '赠送失败原因',
  `errors` varchar(1024) NULL DEFAULT NULL COMMENT '具体原因',
  `createtime` bigint(16) NULL DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `order_id`(`order_id`) USING BTREE,
  INDEX `user_id`(`user_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '满赠记录';


CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_activity_groupon`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT 0 COMMENT '团长',
  `goods_id` int(11) NOT NULL DEFAULT 0 COMMENT '商品',
  `activity_id` int(11) NOT NULL DEFAULT 0 COMMENT '活动',
  `num` int(10) NOT NULL DEFAULT 0 COMMENT '成团人数',
  `current_num` int(10) NOT NULL DEFAULT 0 COMMENT '当前人数',
  `status` enum('invalid','ing','finish','finish_fictitious') NOT NULL DEFAULT 'ing' COMMENT '状态:invalid=已过期,ing=进行中,finish=已成团,finish_fictitious=虚拟成团',
  `expire_time` int(10) NULL DEFAULT NULL COMMENT '过期时间',
  `finish_time` int(10) NULL DEFAULT NULL COMMENT '成团时间',
  `createtime` bigint(16) NULL DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '拼团';


CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_activity_groupon_log`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT 0 COMMENT '用户',
  `nickname` varchar(255) NULL DEFAULT NULL COMMENT '用户昵称',
  `avatar` varchar(255) NULL DEFAULT NULL COMMENT '头像',
  `groupon_id` int(11) NOT NULL DEFAULT 0 COMMENT '团',
  `goods_id` int(11) NOT NULL DEFAULT 0 COMMENT '商品',
  `goods_sku_price_id` int(11) NOT NULL DEFAULT 0 COMMENT '商品规格',
  `activity_id` int(11) NOT NULL DEFAULT 0 COMMENT '活动',
  `is_leader` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否团长:0=不是,1=是',
  `is_fictitious` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否虚拟:0=不是,1=是',
  `order_id` int(11) NOT NULL DEFAULT 0 COMMENT '订单',
  `is_refund` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否退款:0=不是,1=是',
  `createtime` bigint(16) NULL DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `groupon_id`(`groupon_id`) USING BTREE,
  INDEX `user_id`(`user_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '参团记录';


CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_activity_order`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT 0 COMMENT '用户',
  `activity_id` int(11) NOT NULL DEFAULT 0 COMMENT '活动',
  `activity_title` varchar(255) NULL DEFAULT NULL COMMENT '活动标题',
  `activity_type` varchar(255) NULL DEFAULT NULL COMMENT '活动类型',
  `order_id` int(11) NOT NULL DEFAULT 0 COMMENT '订单',
  `pay_fee` decimal(10, 2) NOT NULL DEFAULT '0.00' COMMENT '金额',
  `discount_fee` decimal(10, 2) NOT NULL DEFAULT '0.00' COMMENT '优惠金额/赠送金额',
  `goods_amount` decimal(10, 2) NOT NULL DEFAULT '0.00' COMMENT '参与商品金额',
  `goods_ids` varchar(225) NULL DEFAULT NULL COMMENT '参与商品',
  `status` enum('unpaid','paid') NOT NULL DEFAULT 'unpaid' COMMENT '状态:unpaid=未支付,paid=已支付',
  `ext` varchar(2048) NULL DEFAULT NULL COMMENT '附加信息',
  `createtime` bigint(16) NULL DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `activity_id`(`activity_id`) USING BTREE,
  INDEX `order_id`(`order_id`) USING BTREE,
  INDEX `user_id`(`user_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '活动订单';


CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_activity_signin`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT 0 COMMENT '用户',
  `activity_id` int(11) NOT NULL DEFAULT 0 COMMENT '活动',
  `date` varchar(30) NOT NULL DEFAULT '' COMMENT '签到日期',
  `score` int(10) NOT NULL DEFAULT 0 COMMENT '所得积分',
  `is_replenish` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否补签:0=正常,1=补签',
  `rules` varchar(255) NULL DEFAULT NULL COMMENT '规则',
  `createtime` bigint(16) NULL DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `user_id`(`user_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '活动签到';


CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_activity_sku_price`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `activity_id` int(11) NOT NULL DEFAULT 0 COMMENT '活动',
  `goods_sku_price_id` int(11) NOT NULL DEFAULT 0 COMMENT '规格',
  `goods_id` int(11) NOT NULL DEFAULT 0 COMMENT '商品',
  `stock` int(10) NOT NULL DEFAULT 0 COMMENT '库存',
  `sales` int(10) NOT NULL DEFAULT 0 COMMENT '销量',
  `price` decimal(10, 2) NOT NULL DEFAULT '0.00' COMMENT '价格',
  `ext` varchar(1024) NULL DEFAULT NULL COMMENT '附加字段',
  `status` enum('up','down') NOT NULL DEFAULT 'up' COMMENT '规格状态:up=上架,down=下架',
  `createtime` bigint(16) NULL DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '活动规格价格';


CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_cart`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `goods_id` int(11) NOT NULL DEFAULT 0 COMMENT '商品',
  `user_id` int(11) NOT NULL DEFAULT 0 COMMENT '用户',
  `goods_sku_price_id` int(11) NOT NULL DEFAULT 0 COMMENT '规格',
  `goods_num` int(10) NOT NULL DEFAULT 0 COMMENT '数量',
  `snapshot_price` decimal(10, 2) NULL DEFAULT NULL COMMENT '快照价格',
  `createtime` bigint(16) NULL DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `goods_id`(`goods_id`) USING BTREE,
  INDEX `user_id`(`user_id`) USING BTREE,
  INDEX `goods_sku_price_id`(`goods_sku_price_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '购物车';


CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_category`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(60) NULL DEFAULT NULL COMMENT '分类名称',
  `parent_id` int(11) NOT NULL DEFAULT 0 COMMENT '所属分类',
  `style` varchar(30) NULL DEFAULT NULL COMMENT '样式',
  `image` varchar(255) NULL DEFAULT NULL COMMENT '图片',
  `description` varchar(255) NULL DEFAULT NULL COMMENT '描述',
  `status` enum('normal','hidden') NOT NULL DEFAULT 'normal' COMMENT '状态:normal=正常,hidden=隐藏',
  `weigh` int(8) NOT NULL DEFAULT 0 COMMENT '权重',
  `createtime` bigint(16) NULL DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '分类';


CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_chat_common_word`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `room_id` varchar(60) NOT NULL DEFAULT 'admin' COMMENT '房间号',
  `name` varchar(512) NOT NULL DEFAULT '' COMMENT '名称',
  `content` text NULL COMMENT '内容',
  `status` enum('normal','hidden') NOT NULL DEFAULT 'normal' COMMENT '状态',
  `weigh` int(8) NOT NULL DEFAULT 0 COMMENT '权重',
  `createtime` bigint(16) NULL DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `room_id`(`room_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '常用语';


CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_chat_customer_service`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(60) NULL DEFAULT NULL COMMENT '客服昵称',
  `avatar` varchar(255) NULL DEFAULT NULL COMMENT '客服头像',
  `room_id` varchar(60) NOT NULL DEFAULT 'admin' COMMENT '客服房间',
  `max_num` int(10) NOT NULL DEFAULT 10 COMMENT '最大接待人数',
  `last_time` int(10) NULL DEFAULT NULL COMMENT '上次服务时间',
  `status` enum('offline','online','busy') NOT NULL DEFAULT 'offline' COMMENT '状态',
  `createtime` bigint(16) NULL DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = 'chat客服';


CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_chat_customer_service_user`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_service_id` int(11) NOT NULL DEFAULT 0 COMMENT '客服',
  `auth` varchar(60) NULL DEFAULT NULL COMMENT '认证类型',
  `auth_id` int(11) NOT NULL DEFAULT 0 COMMENT '认证用户',
  `createtime` bigint(16) NULL DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `customer_service_id`(`customer_service_id`) USING BTREE,
  INDEX `auth`(`auth`, `auth_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '客服用户';


CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_chat_question`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `room_id` varchar(60) NOT NULL DEFAULT 'admin' COMMENT '房间号',
  `title` varchar(512) NOT NULL DEFAULT '' COMMENT '问题',
  `content` text NULL COMMENT '内容',
  `status` enum('normal','hidden') NOT NULL DEFAULT 'normal' COMMENT '状态',
  `weigh` int(8) NOT NULL DEFAULT 0 COMMENT '权重',
  `createtime` bigint(16) NULL DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `room_id`(`room_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '猜你想问';


CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_chat_record`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `chat_user_id` int(11) NOT NULL DEFAULT 0 COMMENT '顾客',
  `room_id` varchar(60) NOT NULL DEFAULT 'admin' COMMENT '房间号',
  `sender_identify` enum('customer_service','customer') NOT NULL DEFAULT 'customer' COMMENT '发送身份',
  `sender_id` int(11) NOT NULL DEFAULT 0 COMMENT '发送者',
  `message_type` enum('text','image','file','system','goods','order') NOT NULL DEFAULT 'text' COMMENT '消息类型',
  `message` text NULL COMMENT '消息',
  `read_time` int(10) NULL DEFAULT NULL COMMENT '读取时间',
  `createtime` bigint(16) NULL DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `chat_user_id`(`chat_user_id`) USING BTREE,
  INDEX `sender_identify`(`sender_identify`, `sender_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '聊天记录';


CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_chat_service_log`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `chat_user_id` int(11) NOT NULL DEFAULT 0 COMMENT 'chat user',
  `customer_service_id` int(11) NOT NULL DEFAULT 0 COMMENT '客服',
  `room_id` varchar(60) NOT NULL DEFAULT 'admin' COMMENT '房间号',
  `starttime` int(10) NULL DEFAULT NULL COMMENT '开始时间',
  `endtime` int(10) NULL DEFAULT NULL COMMENT '结束时间',
  `status` enum('waiting','ing','end') NOT NULL DEFAULT 'waiting' COMMENT '状态',
  `createtime` bigint(16) NULL DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `chat_user_id`(`chat_user_id`) USING BTREE,
  INDEX `customer_service_id`(`customer_service_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = 'chat服务记录';


CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_chat_user`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` varchar(60) NULL DEFAULT NULL COMMENT '标示',
  `auth` varchar(60) NULL DEFAULT NULL COMMENT '认证类型',
  `auth_id` int(11) NOT NULL DEFAULT 0 COMMENT '认证用户',
  `nickname` varchar(60) NULL DEFAULT NULL COMMENT '昵称',
  `avatar` varchar(225) NULL DEFAULT NULL COMMENT '头像',
  `customer_service_id` int(11) NOT NULL DEFAULT 0 COMMENT '最后接待客服',
  `last_time` int(10) NULL DEFAULT NULL COMMENT '上次在线时间',
  `createtime` bigint(16) NULL DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `session_id`(`session_id`) USING BTREE,
  INDEX `auth`(`auth`, `auth_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = 'chat用户';


CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_config`  (
  `code` varchar(100) NOT NULL COMMENT '配置标识',
  `parent_code` varchar(60) NULL DEFAULT NULL COMMENT '上级标识',
  `name` varchar(60) NOT NULL DEFAULT '' COMMENT '配置名称',
  `description` varchar(255) NULL DEFAULT NULL COMMENT '描述',
  `type` varchar(60) NULL DEFAULT NULL COMMENT '类型:group,string,text,int,radio,select,select_mult,bool,array,datetime,date,file',
  `value` varchar(512) NULL DEFAULT NULL COMMENT '配置内容',
  `store_range` varchar(512) NULL DEFAULT NULL COMMENT '配置选项',
  `rule` varchar(60) NULL DEFAULT NULL COMMENT '验证规则',
  `weigh` int(8) NOT NULL DEFAULT 50 COMMENT '权重',
  PRIMARY KEY (`code`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '系统配置';
-- shopro_config 数据
INSERT INTO `__PREFIX__shopro_config` (`code`, `parent_code`, `name`, `description`, `type`, `value`, `store_range`, `rule`, `weigh`) VALUES ('chat', NULL, '客服配置', NULL, 'group', NULL, NULL, NULL, 50), ('chat.application', 'chat', '客服应用设置', '客服应用设置', 'group', NULL, NULL, NULL, 50), ('chat.application.shop', 'chat.application', '商城客服', '商城客服配置', 'group', NULL, NULL, NULL, 50), ('chat.application.shop.room_id', 'chat.application.shop', '客服连接类型', '官网客服连接类型', 'string', 'admin', NULL, 'required', 50), ('chat.basic', 'chat', '基础配置', NULL, 'group', NULL, NULL, NULL, 50), ('chat.basic.allocate', 'chat.basic', '分配客服方式', NULL, 'string', 'busy', '[{\"name\":\"\\u5fd9\\u788c\\u7a0b\\u5ea6\",\"value\":\"busy\"},{\"name\":\"\\u8f6e\\u6d41\",\"value\":\"turns\"},{\"name\":\"\\u968f\\u673a\",\"value\":\"random\"}]', NULL, 50), ('chat.basic.auto_customer_service', 'chat.basic', '自动分配客服', NULL, 'boolean', '1', NULL, NULL, 50), ('chat.basic.last_customer_service', 'chat.basic', '默认上次客服', NULL, 'boolean', '1', NULL, 'required', 50), ('chat.system', 'chat', '系统配置', '请谨慎修改', 'group', NULL, NULL, NULL, 50), ('chat.system.inside_host', 'chat.system', '内部通讯地址', '无特殊需求不要改', 'string', '127.0.0.1', NULL, 'required', 50), ('chat.system.inside_port', 'chat.system', '内部通讯端口', '对内提供服务', 'string', '9292', NULL, 'required', 50), ('chat.system.port', 'chat.system', '端口(需要放行)', '对外服务端口', 'string', '2222', NULL, 'required', 50), ('chat.system.ssl', 'chat.system', '证书模式', NULL, 'string', 'reverse_proxy', NULL, 'required', 50), ('chat.system.ssl_cert', 'chat.system', 'ssl 证书', NULL, 'string', '', NULL, 'required', 50), ('chat.system.ssl_key', 'chat.system', 'ssl key', NULL, 'string', '', NULL, 'required', 50), ('shop', NULL, '商城配置', NULL, 'group', NULL, NULL, NULL, 50), ('shop.basic', 'shop', '基本信息', NULL, 'group', NULL, NULL, NULL, 50), ('shop.basic.about_us', 'shop.basic', '关于我们', NULL, 'array', '{\"title\":\"关于我们\",\"id\":\"3\"}', NULL, 'required', 50), ('shop.basic.copyright', 'shop.basic', '版权信息', '版权信息', 'string', '河南星品科技有限公司版权所有', NULL, 'required', 50), ('shop.basic.copytime', 'shop.basic', '版权时间', '版权时间', 'string', 'Copyright© 2018-2023', NULL, 'required', 50), ('shop.basic.domain', 'shop.basic', '商城域名', NULL, 'string', 'https://m.v3.shopro.top/#/', NULL, 'required', 50), ('shop.basic.logo', 'shop.basic', '商城logo', NULL, 'string', '/assets/addons/shopro/img/admin/default_logo.png', NULL, 'required', 50), ('shop.basic.name', 'shop.basic', '商城名称', NULL, 'string', 'Shopro商城', NULL, 'required', 50), ('shop.basic.privacy_protocol', 'shop.basic', '隐私协议', NULL, 'array', '{\"title\":\"隐私协议\",\"id\":\"2\"}', NULL, 'required', 50), ('shop.basic.user_protocol', 'shop.basic', '用户协议', NULL, 'array', '{\"title\":\"用户协议\",\"id\":\"1\"}', NULL, 'required', 50), ('shop.basic.version', 'shop.basic', '版本号', NULL, 'string', '3.0.0', NULL, 'required', 50), ('shop.commission', 'shop', '分销设置', NULL, 'group', NULL, NULL, NULL, 50), ('shop.commission.agent_check', 'shop.commission', '分销商审核', NULL, 'boolean', '0', NULL, NULL, 50), ('shop.commission.agent_form', 'shop.commission', '完善资料表单', NULL, 'array', '{\"status\":\"1\",\"background_image\":\"\\/assets\\/addons\\/shopro\\/img\\/admin\\/apply_agent.png\",\"content\":[{\"type\":\"text\",\"name\":\"姓名\"},{\"type\":\"text\",\"name\":\"身份证号\"},{\"type\":\"image\",\"name\":\"身份证照片\"}]}', NULL, NULL, 50), ('shop.commission.apply_protocol', 'shop.commission', '申请协议', NULL, 'array', '{\"status\":\"1\",\"id\":\"2\",\"title\":\"隐私协议\"}', NULL, NULL, 50), ('shop.commission.background_image', 'shop.commission', '分销中心背景', NULL, 'string', NULL, NULL, NULL, 50), ('shop.commission.become_agent', 'shop.commission', '成为分销商条件', NULL, 'array', '{\"type\":\"apply\",\"value\":\"\"}', NULL, NULL, 50), ('shop.commission.invite_lock', 'shop.commission', '锁定下级条件', NULL, 'string', 'share', NULL, NULL, 50), ('shop.commission.level', 'shop.commission', '分销层级', NULL, 'int', '2', NULL, NULL, 50), ('shop.commission.refund_commission_order', 'shop.commission', '退款扣除业绩', '', 'boolean', '1', '', '', 50), ('shop.commission.refund_commission_reward', 'shop.commission', '退款扣除佣金', '', 'boolean', '1', '', '', 50), ('shop.commission.reward_event', 'shop.commission', '佣金结算方式', '', 'string', 'paid', '', '', 50), ('shop.commission.reward_type', 'shop.commission', '佣金结算价', '', 'string', 'pay_price', '', '', 50), ('shop.commission.self_buy', 'shop.commission', '分销内购', NULL, 'boolean', '0', NULL, NULL, 50), ('shop.commission.upgrade_check', 'shop.commission', '升级审核', NULL, 'boolean', '0', NULL, NULL, 50), ('shop.commission.upgrade_jump', 'shop.commission', '越级升级', NULL, 'boolean', '1', NULL, NULL, 50), ('shop.dispatch', 'shop', '快递物流', NULL, 'group', NULL, NULL, NULL, 50), ('shop.dispatch.driver', 'shop.dispatch', '驱动类型', NULL, 'string', 'kdniao', NULL, 'required', 50), ('shop.dispatch.kdniao', 'shop.dispatch', '快递鸟配置', NULL, 'group', NULL, NULL, 'required', 50), ('shop.dispatch.kdniao.app_key', 'shop.dispatch.kdniao', 'AppKey', NULL, 'string', NULL, NULL, 'required', 50), ('shop.dispatch.kdniao.customer_name', 'shop.dispatch.kdniao', '客户号', NULL, 'string', NULL, NULL, 'required', 50), ('shop.dispatch.kdniao.customer_pwd', 'shop.dispatch.kdniao', '客户密码', NULL, 'string', NULL, NULL, 'required', 50), ('shop.dispatch.kdniao.ebusiness_id', 'shop.dispatch.kdniao', '用户ID', NULL, 'string', NULL, NULL, 'required', 50), ('shop.dispatch.kdniao.express', 'shop.dispatch.kdniao', '快递公司编码', NULL, 'array', '', NULL, 'required', 50), ('shop.dispatch.kdniao.exp_type', 'shop.dispatch.kdniao', '快递类型', NULL, 'string', '1', NULL, 'required', 50), ('shop.dispatch.kdniao.jd_code', 'shop.dispatch.kdniao', '京东青龙编号', NULL, 'string', NULL, NULL, 'required', 50), ('shop.dispatch.kdniao.pay_type', 'shop.dispatch.kdniao', '支付方式', NULL, 'string', '3', NULL, 'required', 50), ('shop.dispatch.kdniao.type', 'shop.dispatch.kdniao', '快递鸟套餐', NULL, 'string', 'vip', NULL, 'required', 50), ('shop.dispatch.sender', 'shop.dispatch', '发货人', NULL, 'array', '{\"name\":\"\",\"province_name\":\"\",\"city_name\":\"\",\"district_name\":\"\",\"mobile\":\"\",\"address\":\"\"}', NULL, 'required', 50), ('shop.dispatch.thinkapi', 'shop.dispatch', 'thinkapi', NULL, 'group', NULL, NULL, NULL, 50), ('shop.dispatch.thinkapi.app_code', 'shop.dispatch.thinkapi', 'thinkapi app_code', NULL, 'string', NULL, NULL, 'required', 50), ('shop.goods', 'shop', '商品配置', NULL, 'group', NULL, NULL, NULL, 50), ('shop.goods.stock_warning', 'shop.goods', '库存预警', NULL, 'int', '5', NULL, 'required', 50), ('shop.order', 'shop', '订单配置', NULL, 'group', NULL, NULL, NULL, 50), ('shop.order.auto_close', 'shop.order', '订单自动关闭(分钟)', NULL, 'string', '15', NULL, 'required', 50), ('shop.order.auto_comment', 'shop.order', '订单自动评价(天)', NULL, 'string', '7', NULL, 'required', 50), ('shop.order.auto_comment_content', 'shop.order', '自动评价内容', '自动评价内容', 'string', '客户默认给出好评~', NULL, 'required', 50), ('shop.order.auto_confirm', 'shop.order', '订单自动收货(天)', '订单自动确认收货', 'string', '10', NULL, 'required', 50), ('shop.order.auto_refund', 'shop.order', '订单自动退款', '未发货订单，用户申请退款', 'boolean', '0', NULL, 'required', 50), ('shop.order.comment_check', 'shop.order', '评价审核', '评价是否需要审核', 'boolean', '1', NULL, 'required', 50), ('shop.order.invoice', 'shop.order', '订单发票', NULL, 'group', NULL, NULL, NULL, 50), ('shop.order.invoice.amount_type', 'shop.order.invoice', '发票金额类型', NULL, 'string', 'pay_fee', NULL, 'required', 50), ('shop.order.invoice.status', 'shop.order.invoice', '可申请发票', NULL, 'boolean', '1', NULL, 'required', 50), ('shop.platform', 'shop', '平台配置', NULL, 'group', NULL, NULL, NULL, 50), ('shop.platform.App', 'shop.platform', 'App', NULL, 'group', NULL, NULL, NULL, 50), ('shop.platform.App.app_id', 'shop.platform.App', 'App AppId', NULL, 'string', NULL, NULL, 'required', 50), ('shop.platform.App.bind_mobile', 'shop.platform.App', '绑定手机号', NULL, 'boolean', '0', NULL, 'required', 50), ('shop.platform.App.download', 'shop.platform.App', '下载链接', NULL, 'array', '{\"android\":\"\",\"ios\":\"\",\"local\":\"\"}', NULL, 'required', 50), ('shop.platform.App.payment', 'shop.platform.App', '支付配置', NULL, 'group', NULL, NULL, NULL, 50), ('shop.platform.App.payment.alipay', 'shop.platform.App.payment', '支付宝支付配置', NULL, 'int', '0', NULL, 'required', 50), ('shop.platform.App.payment.methods', 'shop.platform.App.payment', '支付方式', NULL, 'array', '[\"money\"]', NULL, 'required', 50), ('shop.platform.App.payment.wechat', 'shop.platform.App.payment', '微信支付配置', NULL, 'int', '0', NULL, 'required', 50), ('shop.platform.App.secret', 'shop.platform.App', 'App 密钥', NULL, 'string', NULL, NULL, 'required', 50), ('shop.platform.App.share', 'shop.platform.App', '分享', NULL, 'array', '{\"methods\":[\"forward\",\"poster\"],\"forwardInfo\":{\"title\":\"\",\"subtitle\":\"\",\"image\":\"\\/assets\\/addons\\/shopro\\/img\\/admin\\/forward_image.png\"},\"linkAddress\":\"\",\"posterInfo\":{\"user_bg\":\"\\/assets\\/addons\\/shopro\\/img\\/admin\\/user_bg.png\",\"goods_bg\":\"\\/assets\\/addons\\/shopro\\/img\\/admin\\/goods_bg.png\",\"groupon_bg\":\"\\/assets\\/addons\\/shopro\\/img\\/admin\\/groupon_bg.png\"}}', NULL, 'required', 50), ('shop.platform.App.status', 'shop.platform.App', 'App开启状态', NULL, 'boolean', '1', NULL, 'required', 50), ('shop.platform.H5', 'shop.platform', 'H5', NULL, 'group', NULL, NULL, NULL, 50), ('shop.platform.H5.app_id', 'shop.platform.H5', 'H5 AppId', '公众号或小程序的 AppId', 'string', NULL, NULL, 'required', 50), ('shop.platform.H5.payment', 'shop.platform.H5', '支付配置', NULL, 'group', NULL, NULL, NULL, 50), ('shop.platform.H5.payment.alipay', 'shop.platform.H5.payment', '支付宝支付配置', NULL, 'int', '0', NULL, 'required', 50), ('shop.platform.H5.payment.methods', 'shop.platform.H5.payment', '支付方式', NULL, 'array', '[\"money\"]', NULL, 'required', 50), ('shop.platform.H5.payment.wechat', 'shop.platform.H5.payment', '微信支付配置', NULL, 'int', '0', NULL, 'required', 50), ('shop.platform.H5.secret', 'shop.platform.H5', 'H5 密钥', '公众号或小程序的密钥', 'string', NULL, NULL, 'required', 50), ('shop.platform.H5.share', 'shop.platform.H5', '分享设置', NULL, 'array', '{\"forwardInfo\":{\"title\":\"\",\"subtitle\":\"\",\"image\":\"\\/assets\\/addons\\/shopro\\/img\\/admin\\/forward_image.png\"},\"linkAddress\":\"\",\"posterInfo\":{\"user_bg\":\"\\/assets\\/addons\\/shopro\\/img\\/admin\\/user_bg.png\",\"goods_bg\":\"\\/assets\\/addons\\/shopro\\/img\\/admin\\/goods_bg.png\",\"groupon_bg\":\"\\/assets\\/addons\\/shopro\\/img\\/admin\\/groupon_bg.png\"},\"methods\":[\"poster\",\"link\"]}', NULL, 'required', 50), ('shop.platform.H5.status', 'shop.platform.H5', 'H5开启状态', NULL, 'boolean', '1', NULL, 'required', 50), ('shop.platform.WechatMiniProgram', 'shop.platform', '微信小程序', NULL, 'group', NULL, NULL, NULL, 50), ('shop.platform.WechatMiniProgram.app_id', 'shop.platform.WechatMiniProgram', '小程序AppId', NULL, 'string', NULL, NULL, 'required', 50), ('shop.platform.WechatMiniProgram.auto_login', 'shop.platform.WechatMiniProgram', '微信自动登录', NULL, 'boolean', '0', NULL, 'required', 50), ('shop.platform.WechatMiniProgram.bind_mobile', 'shop.platform.WechatMiniProgram', '绑定手机号', NULL, 'boolean', '0', NULL, 'required', 50), ('shop.platform.WechatMiniProgram.payment', 'shop.platform.WechatMiniProgram', '支付配置', NULL, 'group', NULL, NULL, NULL, 50), ('shop.platform.WechatMiniProgram.payment.alipay', 'shop.platform.WechatMiniProgram.payment', '支付宝支付配置', NULL, 'int', '0', NULL, 'required', 50), ('shop.platform.WechatMiniProgram.payment.methods', 'shop.platform.WechatMiniProgram.payment', '支付方式', NULL, 'array', '[\"money\"]', NULL, 'required', 50), ('shop.platform.WechatMiniProgram.payment.wechat', 'shop.platform.WechatMiniProgram.payment', '微信支付配置', NULL, 'int', '0', NULL, 'required', 50), ('shop.platform.WechatMiniProgram.secret', 'shop.platform.WechatMiniProgram', '小程序密钥', NULL, 'string', NULL, NULL, 'required', 50), ('shop.platform.WechatMiniProgram.share', 'shop.platform.WechatMiniProgram', '分享信息', NULL, 'array', '{\"methods\":[\"forward\",\"poster\"],\"forwardInfo\":{\"title\":\"\",\"subtitle\":\"\",\"image\":\"\\/assets\\/addons\\/shopro\\/img\\/admin\\/forward_image.png\"},\"linkAddress\":\"\",\"posterInfo\":{\"user_bg\":\"\\/assets\\/addons\\/shopro\\/img\\/admin\\/user_bg.png\",\"goods_bg\":\"\\/assets\\/addons\\/shopro\\/img\\/admin\\/goods_bg.png\",\"groupon_bg\":\"\\/assets\\/addons\\/shopro\\/img\\/admin\\/groupon_bg.png\"}}', NULL, 'required', 50), ('shop.platform.WechatMiniProgram.status', 'shop.platform.WechatMiniProgram', '小程序开启状态', NULL, 'boolean', '1', NULL, 'required', 50), ('shop.platform.WechatOfficialAccount', 'shop.platform', '微信公众号', NULL, 'group', NULL, NULL, NULL, 50), ('shop.platform.WechatOfficialAccount.app_id', 'shop.platform.WechatOfficialAccount', '公众号AppId', NULL, 'string', NULL, NULL, 'required', 50), ('shop.platform.WechatOfficialAccount.auto_login', 'shop.platform.WechatOfficialAccount', '微信自动登录', NULL, 'boolean', '0', NULL, 'required', 50), ('shop.platform.WechatOfficialAccount.bind_mobile', 'shop.platform.WechatOfficialAccount', '绑定手机号', NULL, 'boolean', '0', NULL, 'required', 50), ('shop.platform.WechatOfficialAccount.payment', 'shop.platform.WechatOfficialAccount', '支付配置', NULL, 'group', NULL, NULL, NULL, 50), ('shop.platform.WechatOfficialAccount.payment.alipay', 'shop.platform.WechatOfficialAccount.payment', '支付宝支付配置', NULL, 'int', '0', NULL, 'required', 50), ('shop.platform.WechatOfficialAccount.payment.methods', 'shop.platform.WechatOfficialAccount.payment', '支付方式', NULL, 'array', '[\"money\"]', NULL, 'required', 50), ('shop.platform.WechatOfficialAccount.payment.wechat', 'shop.platform.WechatOfficialAccount.payment', '微信支付配置', NULL, 'int', '0', NULL, 'required', 50), ('shop.platform.WechatOfficialAccount.secret', 'shop.platform.WechatOfficialAccount', '公众号密钥', NULL, 'string', NULL, NULL, 'required', 50), ('shop.platform.WechatOfficialAccount.share', 'shop.platform.WechatOfficialAccount', '分享图片', NULL, 'array', '{\"methods\":[\"forward\",\"poster\"],\"forwardInfo\":{\"title\":\"\",\"subtitle\":\"\",\"image\":\"\\/assets\\/addons\\/shopro\\/img\\/admin\\/forward_image.png\"},\"linkAddress\":\"\",\"posterInfo\":{\"user_bg\":\"\\/assets\\/addons\\/shopro\\/img\\/admin\\/user_bg.png\",\"goods_bg\":\"\\/assets\\/addons\\/shopro\\/img\\/admin\\/goods_bg.png\",\"groupon_bg\":\"\\/assets\\/addons\\/shopro\\/img\\/admin\\/groupon_bg.png\"}}', NULL, 'required', 50), ('shop.platform.WechatOfficialAccount.status', 'shop.platform.WechatOfficialAccount', '公众号开启状态', NULL, 'boolean', '1', NULL, 'required', 50), ('shop.recharge_withdraw', 'shop', '充值提现', NULL, 'group', NULL, NULL, NULL, 50), ('shop.recharge_withdraw.recharge', 'shop.recharge_withdraw', '充值配置', NULL, 'group', NULL, NULL, NULL, 50), ('shop.recharge_withdraw.recharge.custom_status', 'shop.recharge_withdraw.recharge', '自定义金额', NULL, 'boolean', '1', NULL, 'required', 50), ('shop.recharge_withdraw.recharge.gift_type', 'shop.recharge_withdraw.recharge', '赠送类型', NULL, 'string', 'money', NULL, 'required', 50), ('shop.recharge_withdraw.recharge.methods', 'shop.recharge_withdraw.recharge', '充值方式', NULL, 'array', '[\"alipay\",\"wechat\"]', NULL, 'required', 50), ('shop.recharge_withdraw.recharge.quick_amounts', 'shop.recharge_withdraw.recharge', '快捷充值金额', NULL, 'array', '[{\"money\":\"10\",\"gift\":\"1\"},{\"money\":\"50\",\"gift\":\"8\"},{\"money\":\"100\",\"gift\":\"20\"}]', NULL, 'required', 50), ('shop.recharge_withdraw.recharge.status', 'shop.recharge_withdraw.recharge', '开启充值', NULL, 'boolean', '1', NULL, 'required', 50), ('shop.recharge_withdraw.withdraw', 'shop.recharge_withdraw', '提现配置', NULL, 'group', NULL, NULL, NULL, 50), ('shop.recharge_withdraw.withdraw.auto_arrival', 'shop.recharge_withdraw.withdraw', '自动到账', NULL, 'boolean', '0', NULL, 'required', 50), ('shop.recharge_withdraw.withdraw.charge_rate', 'shop.recharge_withdraw.withdraw', '提现手续费', NULL, 'float', '1', NULL, 'required', 50), ('shop.recharge_withdraw.withdraw.max_amount', 'shop.recharge_withdraw.withdraw', '单次最大提现金额', NULL, 'float', '1000', NULL, 'required', 50), ('shop.recharge_withdraw.withdraw.max_num', 'shop.recharge_withdraw.withdraw', '最多提现次数', NULL, 'int', '10', NULL, 'required', 50), ('shop.recharge_withdraw.withdraw.methods', 'shop.recharge_withdraw.withdraw', '提现方式', NULL, 'array', '[\"bank\",\"wechat\",\"alipay\"]', NULL, 'required', 50), ('shop.recharge_withdraw.withdraw.min_amount', 'shop.recharge_withdraw.withdraw', '单次最小提现金额', NULL, 'float', '100', NULL, 'required', 50), ('shop.recharge_withdraw.withdraw.num_unit', 'shop.recharge_withdraw.withdraw', '提现次数单位', NULL, 'string', 'month', NULL, 'required', 50), ('shop.user', 'shop', '用户配置', '用户默认值配置', 'group', NULL, NULL, NULL, 50), ('shop.user.avatar', 'shop.user', '默认头像', '用户默认头像', 'string', '/assets/addons/shopro/img/admin/default_logo.png', NULL, 'required', 50), ('shop.user.group_id', 'shop.user', '默认分组', '用户默认分组', 'string', '1', NULL, 'required', 50), ('shop.user.nickname', 'shop.user', '默认昵称', '用户默认昵称', 'string', '用户', NULL, 'required', 50), ('wechat', NULL, '微信配置', NULL, 'group', NULL, NULL, NULL, 50), ('wechat.officialAccount', 'wechat', '微信公众号配置', NULL, 'group', NULL, NULL, NULL, 50), ('wechat.officialAccount.aes_key', 'wechat.officialAccount', '消息加解密秘钥', NULL, 'string', NULL, NULL, NULL, 50), ('wechat.officialAccount.app_id', 'wechat.officialAccount', '开发者ID', NULL, 'string', NULL, NULL, NULL, 50), ('wechat.officialAccount.logo', 'wechat.officialAccount', '公众号头像', NULL, 'string', NULL, NULL, NULL, 50), ('wechat.officialAccount.name', 'wechat.officialAccount', '公众号名称', NULL, 'string', '', NULL, NULL, 50), ('wechat.officialAccount.qrcode', 'wechat.officialAccount', '公众号二维码', NULL, 'string', NULL, NULL, NULL, 50), ('wechat.officialAccount.secret', 'wechat.officialAccount', '开发者秘钥', NULL, 'string', NULL, NULL, NULL, 50), ('wechat.officialAccount.status', 'wechat.officialAccount', '公众号对接状态', NULL, 'boolean', '0', NULL, NULL, 50), ('wechat.officialAccount.token', 'wechat.officialAccount', '令牌Token', NULL, 'string', NULL, NULL, NULL, 50), ('wechat.officialAccount.type', 'wechat.officialAccount', '公众号类型', NULL, 'int', '4', NULL, NULL, 50);


CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_coupon`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '名称',
  `type` enum('reduce','discount') NOT NULL DEFAULT 'reduce' COMMENT '类型:reduce=满减券,discount=折扣券',
  `use_scope` enum('all_use','goods','disabled_goods','category') NOT NULL DEFAULT 'all_use' COMMENT '可用范围:all_use=全场通用,goods=指定商品可用,disabled_goods=指定商品不可用,category=指定分类可用',
  `items` varchar(255) NULL DEFAULT NULL COMMENT '可用范围值',
  `amount` decimal(10, 2) NOT NULL DEFAULT '0.00' COMMENT '券面额',
  `max_amount` decimal(10, 2) NOT NULL DEFAULT '0.00' COMMENT '最大抵扣(折扣券)',
  `enough` decimal(10, 2) NOT NULL DEFAULT '0.00' COMMENT '消费门槛',
  `stock` int(10) NOT NULL DEFAULT 0 COMMENT '库存',
  `limit_num` int(10) NOT NULL DEFAULT 0 COMMENT '每人限领',
  `get_start_time` int(10) NOT NULL DEFAULT 0 COMMENT '领取开始时间',
  `get_end_time` int(10) NOT NULL DEFAULT 0 COMMENT '领取结束时间',
  `use_time_type` enum('range','days') NOT NULL DEFAULT 'range' COMMENT '使用时间类型:range=固定区间,days=相对天数',
  `use_start_time` int(10) NOT NULL DEFAULT 0 COMMENT '使用开始时间',
  `use_end_time` int(10) NOT NULL DEFAULT 0 COMMENT '使用结束时间',
  `start_days` int(10) NOT NULL DEFAULT 0 COMMENT '开始有效天数',
  `days` int(10) NOT NULL DEFAULT 0 COMMENT '有效天数',
  `is_double_discount` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '优惠叠加:0=不可叠加,1=可叠加',
  `description` varchar(60) NULL DEFAULT NULL COMMENT '描述',
  `status` enum('normal','hidden','disabled') NOT NULL DEFAULT 'normal' COMMENT '状态:normal=公开,hidden=后台发放,disabled=禁用',
  `createtime` bigint(16) NULL DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) NULL DEFAULT NULL COMMENT '更新时间',
  `deletetime` bigint(16) NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '优惠券';


CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_data_area`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL DEFAULT 0 COMMENT '上级',
  `name` varchar(60) NOT NULL COMMENT '行政区名称',
  `level` varchar(60) NULL DEFAULT NULL COMMENT '行政区级别',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '地区';
-- shopro_data_area 数据
INSERT INTO `__PREFIX__shopro_data_area` (`id`, `pid`, `name`, `level`) VALUES (11, 0, '北京市', 'province'), (12, 0, '天津市', 'province'), (13, 0, '河北省', 'province'), (14, 0, '山西省', 'province'), (15, 0, '内蒙古自治区', 'province'), (21, 0, '辽宁省', 'province'), (22, 0, '吉林省', 'province'), (23, 0, '黑龙江省', 'province'), (31, 0, '上海市', 'province'), (32, 0, '江苏省', 'province'), (33, 0, '浙江省', 'province'), (34, 0, '安徽省', 'province'), (35, 0, '福建省', 'province'), (36, 0, '江西省', 'province'), (37, 0, '山东省', 'province'), (41, 0, '河南省', 'province'), (42, 0, '湖北省', 'province'), (43, 0, '湖南省', 'province'), (44, 0, '广东省', 'province'), (45, 0, '广西壮族自治区', 'province'), (46, 0, '海南省', 'province'), (50, 0, '重庆市', 'province'), (51, 0, '四川省', 'province'), (52, 0, '贵州省', 'province'), (53, 0, '云南省', 'province'), (54, 0, '西藏自治区', 'province'), (61, 0, '陕西省', 'province'), (62, 0, '甘肃省', 'province'), (63, 0, '青海省', 'province'), (64, 0, '宁夏回族自治区', 'province'), (65, 0, '新疆维吾尔自治区', 'province'), (71, 0, '台湾省', 'province'), (81, 0, '香港特别行政区', 'province'), (82, 0, '澳门特别行政区', 'province'), (1101, 11, '市辖区', 'city'), (1201, 12, '市辖区', 'city'), (1301, 13, '石家庄市', 'city'), (1302, 13, '唐山市', 'city'), (1303, 13, '秦皇岛市', 'city'), (1304, 13, '邯郸市', 'city'), (1305, 13, '邢台市', 'city'), (1306, 13, '保定市', 'city'), (1307, 13, '张家口市', 'city'), (1308, 13, '承德市', 'city'), (1309, 13, '沧州市', 'city'), (1310, 13, '廊坊市', 'city'), (1311, 13, '衡水市', 'city'), (1401, 14, '太原市', 'city'), (1402, 14, '大同市', 'city'), (1403, 14, '阳泉市', 'city'), (1404, 14, '长治市', 'city'), (1405, 14, '晋城市', 'city'), (1406, 14, '朔州市', 'city'), (1407, 14, '晋中市', 'city'), (1408, 14, '运城市', 'city'), (1409, 14, '忻州市', 'city'), (1410, 14, '临汾市', 'city'), (1411, 14, '吕梁市', 'city'), (1501, 15, '呼和浩特市', 'city'), (1502, 15, '包头市', 'city'), (1503, 15, '乌海市', 'city'), (1504, 15, '赤峰市', 'city'), (1505, 15, '通辽市', 'city'), (1506, 15, '鄂尔多斯市', 'city'), (1507, 15, '呼伦贝尔市', 'city'), (1508, 15, '巴彦淖尔市', 'city'), (1509, 15, '乌兰察布市', 'city'), (1522, 15, '兴安盟', 'city'), (1525, 15, '锡林郭勒盟', 'city'), (1529, 15, '阿拉善盟', 'city'), (2101, 21, '沈阳市', 'city'), (2102, 21, '大连市', 'city'), (2103, 21, '鞍山市', 'city'), (2104, 21, '抚顺市', 'city'), (2105, 21, '本溪市', 'city'), (2106, 21, '丹东市', 'city'), (2107, 21, '锦州市', 'city'), (2108, 21, '营口市', 'city'), (2109, 21, '阜新市', 'city'), (2110, 21, '辽阳市', 'city'), (2111, 21, '盘锦市', 'city'), (2112, 21, '铁岭市', 'city'), (2113, 21, '朝阳市', 'city'), (2114, 21, '葫芦岛市', 'city'), (2201, 22, '长春市', 'city'), (2202, 22, '吉林市', 'city'), (2203, 22, '四平市', 'city'), (2204, 22, '辽源市', 'city'), (2205, 22, '通化市', 'city'), (2206, 22, '白山市', 'city'), (2207, 22, '松原市', 'city'), (2208, 22, '白城市', 'city'), (2224, 22, '延边朝鲜族自治州', 'city'), (2301, 23, '哈尔滨市', 'city'), (2302, 23, '齐齐哈尔市', 'city'), (2303, 23, '鸡西市', 'city'), (2304, 23, '鹤岗市', 'city'), (2305, 23, '双鸭山市', 'city'), (2306, 23, '大庆市', 'city'), (2307, 23, '伊春市', 'city'), (2308, 23, '佳木斯市', 'city'), (2309, 23, '七台河市', 'city'), (2310, 23, '牡丹江市', 'city'), (2311, 23, '黑河市', 'city'), (2312, 23, '绥化市', 'city'), (2327, 23, '大兴安岭地区', 'city'), (3101, 31, '市辖区', 'city'), (3201, 32, '南京市', 'city'), (3202, 32, '无锡市', 'city'), (3203, 32, '徐州市', 'city'), (3204, 32, '常州市', 'city'), (3205, 32, '苏州市', 'city'), (3206, 32, '南通市', 'city'), (3207, 32, '连云港市', 'city'), (3208, 32, '淮安市', 'city'), (3209, 32, '盐城市', 'city'), (3210, 32, '扬州市', 'city'), (3211, 32, '镇江市', 'city'), (3212, 32, '泰州市', 'city'), (3213, 32, '宿迁市', 'city'), (3301, 33, '杭州市', 'city'), (3302, 33, '宁波市', 'city'), (3303, 33, '温州市', 'city'), (3304, 33, '嘉兴市', 'city'), (3305, 33, '湖州市', 'city'), (3306, 33, '绍兴市', 'city'), (3307, 33, '金华市', 'city'), (3308, 33, '衢州市', 'city'), (3309, 33, '舟山市', 'city'), (3310, 33, '台州市', 'city'), (3311, 33, '丽水市', 'city'), (3401, 34, '合肥市', 'city'), (3402, 34, '芜湖市', 'city'), (3403, 34, '蚌埠市', 'city'), (3404, 34, '淮南市', 'city'), (3405, 34, '马鞍山市', 'city'), (3406, 34, '淮北市', 'city'), (3407, 34, '铜陵市', 'city'), (3408, 34, '安庆市', 'city'), (3410, 34, '黄山市', 'city'), (3411, 34, '滁州市', 'city'), (3412, 34, '阜阳市', 'city'), (3413, 34, '宿州市', 'city'), (3415, 34, '六安市', 'city'), (3416, 34, '亳州市', 'city'), (3417, 34, '池州市', 'city'), (3418, 34, '宣城市', 'city'), (3501, 35, '福州市', 'city'), (3502, 35, '厦门市', 'city'), (3503, 35, '莆田市', 'city'), (3504, 35, '三明市', 'city'), (3505, 35, '泉州市', 'city'), (3506, 35, '漳州市', 'city'), (3507, 35, '南平市', 'city'), (3508, 35, '龙岩市', 'city'), (3509, 35, '宁德市', 'city'), (3601, 36, '南昌市', 'city'), (3602, 36, '景德镇市', 'city'), (3603, 36, '萍乡市', 'city'), (3604, 36, '九江市', 'city'), (3605, 36, '新余市', 'city'), (3606, 36, '鹰潭市', 'city'), (3607, 36, '赣州市', 'city'), (3608, 36, '吉安市', 'city'), (3609, 36, '宜春市', 'city'), (3610, 36, '抚州市', 'city'), (3611, 36, '上饶市', 'city'), (3701, 37, '济南市', 'city'), (3702, 37, '青岛市', 'city'), (3703, 37, '淄博市', 'city'), (3704, 37, '枣庄市', 'city'), (3705, 37, '东营市', 'city'), (3706, 37, '烟台市', 'city'), (3707, 37, '潍坊市', 'city'), (3708, 37, '济宁市', 'city'), (3709, 37, '泰安市', 'city'), (3710, 37, '威海市', 'city'), (3711, 37, '日照市', 'city'), (3713, 37, '临沂市', 'city'), (3714, 37, '德州市', 'city'), (3715, 37, '聊城市', 'city'), (3716, 37, '滨州市', 'city'), (3717, 37, '菏泽市', 'city'), (4101, 41, '郑州市', 'city'), (4102, 41, '开封市', 'city'), (4103, 41, '洛阳市', 'city'), (4104, 41, '平顶山市', 'city'), (4105, 41, '安阳市', 'city'), (4106, 41, '鹤壁市', 'city'), (4107, 41, '新乡市', 'city'), (4108, 41, '焦作市', 'city'), (4109, 41, '濮阳市', 'city'), (4110, 41, '许昌市', 'city'), (4111, 41, '漯河市', 'city'), (4112, 41, '三门峡市', 'city'), (4113, 41, '南阳市', 'city'), (4114, 41, '商丘市', 'city'), (4115, 41, '信阳市', 'city'), (4116, 41, '周口市', 'city'), (4117, 41, '驻马店市', 'city'), (4190, 41, '省直辖县级行政区划', 'city'), (4201, 42, '武汉市', 'city'), (4202, 42, '黄石市', 'city'), (4203, 42, '十堰市', 'city'), (4205, 42, '宜昌市', 'city'), (4206, 42, '襄阳市', 'city'), (4207, 42, '鄂州市', 'city'), (4208, 42, '荆门市', 'city'), (4209, 42, '孝感市', 'city'), (4210, 42, '荆州市', 'city'), (4211, 42, '黄冈市', 'city'), (4212, 42, '咸宁市', 'city'), (4213, 42, '随州市', 'city'), (4228, 42, '恩施土家族苗族自治州', 'city'), (4290, 42, '省直辖县级行政区划', 'city'), (4301, 43, '长沙市', 'city'), (4302, 43, '株洲市', 'city'), (4303, 43, '湘潭市', 'city'), (4304, 43, '衡阳市', 'city'), (4305, 43, '邵阳市', 'city'), (4306, 43, '岳阳市', 'city'), (4307, 43, '常德市', 'city'), (4308, 43, '张家界市', 'city'), (4309, 43, '益阳市', 'city'), (4310, 43, '郴州市', 'city'), (4311, 43, '永州市', 'city'), (4312, 43, '怀化市', 'city'), (4313, 43, '娄底市', 'city'), (4331, 43, '湘西土家族苗族自治州', 'city'), (4401, 44, '广州市', 'city'), (4402, 44, '韶关市', 'city'), (4403, 44, '深圳市', 'city'), (4404, 44, '珠海市', 'city'), (4405, 44, '汕头市', 'city'), (4406, 44, '佛山市', 'city'), (4407, 44, '江门市', 'city'), (4408, 44, '湛江市', 'city'), (4409, 44, '茂名市', 'city'), (4412, 44, '肇庆市', 'city'), (4413, 44, '惠州市', 'city'), (4414, 44, '梅州市', 'city'), (4415, 44, '汕尾市', 'city'), (4416, 44, '河源市', 'city'), (4417, 44, '阳江市', 'city'), (4418, 44, '清远市', 'city'), (4419, 44, '东莞市', 'city'), (4420, 44, '中山市', 'city'), (4451, 44, '潮州市', 'city'), (4452, 44, '揭阳市', 'city'), (4453, 44, '云浮市', 'city'), (4501, 45, '南宁市', 'city'), (4502, 45, '柳州市', 'city'), (4503, 45, '桂林市', 'city'), (4504, 45, '梧州市', 'city'), (4505, 45, '北海市', 'city'), (4506, 45, '防城港市', 'city'), (4507, 45, '钦州市', 'city'), (4508, 45, '贵港市', 'city'), (4509, 45, '玉林市', 'city'), (4510, 45, '百色市', 'city'), (4511, 45, '贺州市', 'city'), (4512, 45, '河池市', 'city'), (4513, 45, '来宾市', 'city'), (4514, 45, '崇左市', 'city'), (4601, 46, '海口市', 'city'), (4602, 46, '三亚市', 'city'), (4603, 46, '三沙市', 'city'), (4604, 46, '儋州市', 'city'), (4690, 46, '省直辖县级行政区划', 'city'), (5001, 50, '市辖区', 'city'), (5002, 50, '县', 'city'), (5101, 51, '成都市', 'city'), (5103, 51, '自贡市', 'city'), (5104, 51, '攀枝花市', 'city'), (5105, 51, '泸州市', 'city'), (5106, 51, '德阳市', 'city'), (5107, 51, '绵阳市', 'city'), (5108, 51, '广元市', 'city'), (5109, 51, '遂宁市', 'city'), (5110, 51, '内江市', 'city'), (5111, 51, '乐山市', 'city'), (5113, 51, '南充市', 'city'), (5114, 51, '眉山市', 'city'), (5115, 51, '宜宾市', 'city'), (5116, 51, '广安市', 'city'), (5117, 51, '达州市', 'city'), (5118, 51, '雅安市', 'city'), (5119, 51, '巴中市', 'city'), (5120, 51, '资阳市', 'city'), (5132, 51, '阿坝藏族羌族自治州', 'city'), (5133, 51, '甘孜藏族自治州', 'city'), (5134, 51, '凉山彝族自治州', 'city'), (5201, 52, '贵阳市', 'city'), (5202, 52, '六盘水市', 'city'), (5203, 52, '遵义市', 'city'), (5204, 52, '安顺市', 'city'), (5205, 52, '毕节市', 'city'), (5206, 52, '铜仁市', 'city'), (5223, 52, '黔西南布依族苗族自治州', 'city'), (5226, 52, '黔东南苗族侗族自治州', 'city'), (5227, 52, '黔南布依族苗族自治州', 'city'), (5301, 53, '昆明市', 'city'), (5303, 53, '曲靖市', 'city'), (5304, 53, '玉溪市', 'city'), (5305, 53, '保山市', 'city'), (5306, 53, '昭通市', 'city'), (5307, 53, '丽江市', 'city'), (5308, 53, '普洱市', 'city'), (5309, 53, '临沧市', 'city'), (5323, 53, '楚雄彝族自治州', 'city'), (5325, 53, '红河哈尼族彝族自治州', 'city'), (5326, 53, '文山壮族苗族自治州', 'city'), (5328, 53, '西双版纳傣族自治州', 'city'), (5329, 53, '大理白族自治州', 'city'), (5331, 53, '德宏傣族景颇族自治州', 'city'), (5333, 53, '怒江傈僳族自治州', 'city'), (5334, 53, '迪庆藏族自治州', 'city'), (5401, 54, '拉萨市', 'city'), (5402, 54, '日喀则市', 'city'), (5403, 54, '昌都市', 'city'), (5404, 54, '林芝市', 'city'), (5405, 54, '山南市', 'city'), (5406, 54, '那曲市', 'city'), (5425, 54, '阿里地区', 'city'), (6101, 61, '西安市', 'city'), (6102, 61, '铜川市', 'city'), (6103, 61, '宝鸡市', 'city'), (6104, 61, '咸阳市', 'city'), (6105, 61, '渭南市', 'city'), (6106, 61, '延安市', 'city'), (6107, 61, '汉中市', 'city'), (6108, 61, '榆林市', 'city'), (6109, 61, '安康市', 'city'), (6110, 61, '商洛市', 'city'), (6201, 62, '兰州市', 'city'), (6202, 62, '嘉峪关市', 'city'), (6203, 62, '金昌市', 'city'), (6204, 62, '白银市', 'city'), (6205, 62, '天水市', 'city'), (6206, 62, '武威市', 'city'), (6207, 62, '张掖市', 'city'), (6208, 62, '平凉市', 'city'), (6209, 62, '酒泉市', 'city'), (6210, 62, '庆阳市', 'city'), (6211, 62, '定西市', 'city'), (6212, 62, '陇南市', 'city'), (6229, 62, '临夏回族自治州', 'city'), (6230, 62, '甘南藏族自治州', 'city'), (6301, 63, '西宁市', 'city'), (6302, 63, '海东市', 'city'), (6322, 63, '海北藏族自治州', 'city'), (6323, 63, '黄南藏族自治州', 'city'), (6325, 63, '海南藏族自治州', 'city'), (6326, 63, '果洛藏族自治州', 'city'), (6327, 63, '玉树藏族自治州', 'city'), (6328, 63, '海西蒙古族藏族自治州', 'city'), (6401, 64, '银川市', 'city'), (6402, 64, '石嘴山市', 'city'), (6403, 64, '吴忠市', 'city'), (6404, 64, '固原市', 'city'), (6405, 64, '中卫市', 'city'), (6501, 65, '乌鲁木齐市', 'city'), (6502, 65, '克拉玛依市', 'city'), (6504, 65, '吐鲁番市', 'city'), (6505, 65, '哈密市', 'city'), (6523, 65, '昌吉回族自治州', 'city'), (6527, 65, '博尔塔拉蒙古自治州', 'city'), (6528, 65, '巴音郭楞蒙古自治州', 'city'), (6529, 65, '阿克苏地区', 'city'), (6530, 65, '克孜勒苏柯尔克孜自治州', 'city'), (6531, 65, '喀什地区', 'city'), (6532, 65, '和田地区', 'city'), (6540, 65, '伊犁哈萨克自治州', 'city'), (6542, 65, '塔城地区', 'city'), (6543, 65, '阿勒泰地区', 'city'), (6590, 65, '自治区直辖县级行政区划', 'city'), (7110, 71, '台北市', 'city'), (7111, 71, '台中市', 'city'), (7112, 71, '基隆市', 'city'), (7113, 71, '台南市', 'city'), (7114, 71, '高雄市', 'city'), (7115, 71, '新北市', 'city'), (7116, 71, '宜兰县', 'city'), (7117, 71, '桃园市', 'city'), (7118, 71, '嘉义市', 'city'), (7119, 71, '新竹县', 'city'), (7120, 71, '苗栗县', 'city'), (7122, 71, '南投县', 'city'), (7123, 71, '彰化县', 'city'), (7124, 71, '新竹市', 'city'), (7125, 71, '云林县', 'city'), (7126, 71, '嘉义县', 'city'), (7129, 71, '屏东县', 'city'), (7130, 71, '花莲县', 'city'), (7131, 71, '台东县', 'city'), (7132, 71, '澎湖县', 'city'), (8100, 81, '香港', 'city'), (8111, 8100, '中西区', 'district'), (8112, 8100, '湾仔区', 'district'), (8113, 8100, '东区', 'district'), (8114, 8100, '南区', 'district'), (8121, 8100, '油尖旺区', 'district'), (8122, 8100, '深水埗区', 'district'), (8123, 8100, '九龙城区', 'district'), (8124, 8100, '黄大仙区', 'district'), (8125, 8100, '观塘区', 'district'), (8131, 8100, '葵青区', 'district'), (8132, 8100, '荃湾区', 'district'), (8133, 8100, '屯门区', 'district'), (8134, 8100, '元朗区', 'district'), (8135, 8100, '北区', 'district'), (8136, 8100, '大埔区', 'district'), (8137, 8100, '沙田区', 'district'), (8138, 8100, '西贡区', 'district'), (8139, 8100, '离岛区', 'district'), (8200, 82, '澳门', 'city'), (8201, 8200, '澳门半岛', 'district'), (8202, 8200, '氹仔岛', 'district'), (8203, 8200, '路环岛', 'district'), (8204, 8200, '路氹城', 'district'), (110101, 1101, '东城区', 'district'), (110102, 1101, '西城区', 'district'), (110105, 1101, '朝阳区', 'district'), (110106, 1101, '丰台区', 'district'), (110107, 1101, '石景山区', 'district'), (110108, 1101, '海淀区', 'district'), (110109, 1101, '门头沟区', 'district'), (110111, 1101, '房山区', 'district'), (110112, 1101, '通州区', 'district'), (110113, 1101, '顺义区', 'district'), (110114, 1101, '昌平区', 'district'), (110115, 1101, '大兴区', 'district'), (110116, 1101, '怀柔区', 'district'), (110117, 1101, '平谷区', 'district'), (110118, 1101, '密云区', 'district'), (110119, 1101, '延庆区', 'district'), (120101, 1201, '和平区', 'district'), (120102, 1201, '河东区', 'district'), (120103, 1201, '河西区', 'district'), (120104, 1201, '南开区', 'district'), (120105, 1201, '河北区', 'district'), (120106, 1201, '红桥区', 'district'), (120110, 1201, '东丽区', 'district'), (120111, 1201, '西青区', 'district'), (120112, 1201, '津南区', 'district'), (120113, 1201, '北辰区', 'district'), (120114, 1201, '武清区', 'district'), (120115, 1201, '宝坻区', 'district'), (120116, 1201, '滨海新区', 'district'), (120117, 1201, '宁河区', 'district'), (120118, 1201, '静海区', 'district'), (120119, 1201, '蓟州区', 'district'), (130102, 1301, '长安区', 'district'), (130104, 1301, '桥西区', 'district'), (130105, 1301, '新华区', 'district'), (130107, 1301, '井陉矿区', 'district'), (130108, 1301, '裕华区', 'district'), (130109, 1301, '藁城区', 'district'), (130110, 1301, '鹿泉区', 'district'), (130111, 1301, '栾城区', 'district'), (130121, 1301, '井陉县', 'district'), (130123, 1301, '正定县', 'district'), (130125, 1301, '行唐县', 'district'), (130126, 1301, '灵寿县', 'district'), (130127, 1301, '高邑县', 'district'), (130128, 1301, '深泽县', 'district'), (130129, 1301, '赞皇县', 'district'), (130130, 1301, '无极县', 'district'), (130131, 1301, '平山县', 'district'), (130132, 1301, '元氏县', 'district'), (130133, 1301, '赵县', 'district'), (130171, 1301, '石家庄高新技术产业开发区', 'district'), (130172, 1301, '石家庄循环化工园区', 'district'), (130181, 1301, '辛集市', 'district'), (130183, 1301, '晋州市', 'district'), (130184, 1301, '新乐市', 'district'), (130202, 1302, '路南区', 'district'), (130203, 1302, '路北区', 'district'), (130204, 1302, '古冶区', 'district'), (130205, 1302, '开平区', 'district'), (130207, 1302, '丰南区', 'district'), (130208, 1302, '丰润区', 'district'), (130209, 1302, '曹妃甸区', 'district'), (130224, 1302, '滦南县', 'district'), (130225, 1302, '乐亭县', 'district'), (130227, 1302, '迁西县', 'district'), (130229, 1302, '玉田县', 'district'), (130271, 1302, '河北唐山芦台经济开发区', 'district'), (130272, 1302, '唐山市汉沽管理区', 'district'), (130273, 1302, '唐山高新技术产业开发区', 'district'), (130274, 1302, '河北唐山海港经济开发区', 'district'), (130281, 1302, '遵化市', 'district'), (130283, 1302, '迁安市', 'district'), (130284, 1302, '滦州市', 'district'), (130302, 1303, '海港区', 'district'), (130303, 1303, '山海关区', 'district'), (130304, 1303, '北戴河区', 'district'), (130306, 1303, '抚宁区', 'district'), (130321, 1303, '青龙满族自治县', 'district'), (130322, 1303, '昌黎县', 'district'), (130324, 1303, '卢龙县', 'district'), (130371, 1303, '秦皇岛市经济技术开发区', 'district'), (130372, 1303, '北戴河新区', 'district'), (130402, 1304, '邯山区', 'district'), (130403, 1304, '丛台区', 'district'), (130404, 1304, '复兴区', 'district'), (130406, 1304, '峰峰矿区', 'district'), (130407, 1304, '肥乡区', 'district'), (130408, 1304, '永年区', 'district'), (130423, 1304, '临漳县', 'district'), (130424, 1304, '成安县', 'district'), (130425, 1304, '大名县', 'district'), (130426, 1304, '涉县', 'district'), (130427, 1304, '磁县', 'district'), (130430, 1304, '邱县', 'district'), (130431, 1304, '鸡泽县', 'district'), (130432, 1304, '广平县', 'district'), (130433, 1304, '馆陶县', 'district'), (130434, 1304, '魏县', 'district'), (130435, 1304, '曲周县', 'district'), (130471, 1304, '邯郸经济技术开发区', 'district'), (130473, 1304, '邯郸冀南新区', 'district'), (130481, 1304, '武安市', 'district'), (130502, 1305, '襄都区', 'district'), (130503, 1305, '信都区', 'district'), (130505, 1305, '任泽区', 'district'), (130506, 1305, '南和区', 'district'), (130522, 1305, '临城县', 'district'), (130523, 1305, '内丘县', 'district'), (130524, 1305, '柏乡县', 'district'), (130525, 1305, '隆尧县', 'district'), (130528, 1305, '宁晋县', 'district'), (130529, 1305, '巨鹿县', 'district'), (130530, 1305, '新河县', 'district'), (130531, 1305, '广宗县', 'district'), (130532, 1305, '平乡县', 'district'), (130533, 1305, '威县', 'district'), (130534, 1305, '清河县', 'district'), (130535, 1305, '临西县', 'district'), (130571, 1305, '河北邢台经济开发区', 'district'), (130581, 1305, '南宫市', 'district'), (130582, 1305, '沙河市', 'district'), (130602, 1306, '竞秀区', 'district'), (130606, 1306, '莲池区', 'district'), (130607, 1306, '满城区', 'district'), (130608, 1306, '清苑区', 'district'), (130609, 1306, '徐水区', 'district'), (130623, 1306, '涞水县', 'district'), (130624, 1306, '阜平县', 'district'), (130626, 1306, '定兴县', 'district'), (130627, 1306, '唐县', 'district'), (130628, 1306, '高阳县', 'district'), (130629, 1306, '容城县', 'district'), (130630, 1306, '涞源县', 'district'), (130631, 1306, '望都县', 'district'), (130632, 1306, '安新县', 'district'), (130633, 1306, '易县', 'district'), (130634, 1306, '曲阳县', 'district'), (130635, 1306, '蠡县', 'district'), (130636, 1306, '顺平县', 'district'), (130637, 1306, '博野县', 'district'), (130638, 1306, '雄县', 'district'), (130671, 1306, '保定高新技术产业开发区', 'district'), (130672, 1306, '保定白沟新城', 'district'), (130681, 1306, '涿州市', 'district'), (130682, 1306, '定州市', 'district'), (130683, 1306, '安国市', 'district'), (130684, 1306, '高碑店市', 'district'), (130702, 1307, '桥东区', 'district'), (130703, 1307, '桥西区', 'district'), (130705, 1307, '宣化区', 'district'), (130706, 1307, '下花园区', 'district'), (130708, 1307, '万全区', 'district'), (130709, 1307, '崇礼区', 'district'), (130722, 1307, '张北县', 'district'), (130723, 1307, '康保县', 'district'), (130724, 1307, '沽源县', 'district'), (130725, 1307, '尚义县', 'district'), (130726, 1307, '蔚县', 'district'), (130727, 1307, '阳原县', 'district'), (130728, 1307, '怀安县', 'district'), (130730, 1307, '怀来县', 'district'), (130731, 1307, '涿鹿县', 'district'), (130732, 1307, '赤城县', 'district'), (130771, 1307, '张家口经济开发区', 'district'), (130772, 1307, '张家口市察北管理区', 'district'), (130773, 1307, '张家口市塞北管理区', 'district'), (130802, 1308, '双桥区', 'district'), (130803, 1308, '双滦区', 'district'), (130804, 1308, '鹰手营子矿区', 'district'), (130821, 1308, '承德县', 'district'), (130822, 1308, '兴隆县', 'district'), (130824, 1308, '滦平县', 'district'), (130825, 1308, '隆化县', 'district'), (130826, 1308, '丰宁满族自治县', 'district'), (130827, 1308, '宽城满族自治县', 'district'), (130828, 1308, '围场满族蒙古族自治县', 'district'), (130871, 1308, '承德高新技术产业开发区', 'district'), (130881, 1308, '平泉市', 'district'), (130902, 1309, '新华区', 'district'), (130903, 1309, '运河区', 'district'), (130921, 1309, '沧县', 'district'), (130922, 1309, '青县', 'district'), (130923, 1309, '东光县', 'district'), (130924, 1309, '海兴县', 'district'), (130925, 1309, '盐山县', 'district'), (130926, 1309, '肃宁县', 'district'), (130927, 1309, '南皮县', 'district'), (130928, 1309, '吴桥县', 'district'), (130929, 1309, '献县', 'district'), (130930, 1309, '孟村回族自治县', 'district'), (130971, 1309, '河北沧州经济开发区', 'district'), (130972, 1309, '沧州高新技术产业开发区', 'district'), (130973, 1309, '沧州渤海新区', 'district'), (130981, 1309, '泊头市', 'district'), (130982, 1309, '任丘市', 'district'), (130983, 1309, '黄骅市', 'district'), (130984, 1309, '河间市', 'district'), (131002, 1310, '安次区', 'district'), (131003, 1310, '广阳区', 'district'), (131022, 1310, '固安县', 'district'), (131023, 1310, '永清县', 'district'), (131024, 1310, '香河县', 'district'), (131025, 1310, '大城县', 'district'), (131026, 1310, '文安县', 'district'), (131028, 1310, '大厂回族自治县', 'district'), (131071, 1310, '廊坊经济技术开发区', 'district'), (131081, 1310, '霸州市', 'district'), (131082, 1310, '三河市', 'district'), (131102, 1311, '桃城区', 'district'), (131103, 1311, '冀州区', 'district'), (131121, 1311, '枣强县', 'district'), (131122, 1311, '武邑县', 'district'), (131123, 1311, '武强县', 'district'), (131124, 1311, '饶阳县', 'district'), (131125, 1311, '安平县', 'district'), (131126, 1311, '故城县', 'district'), (131127, 1311, '景县', 'district'), (131128, 1311, '阜城县', 'district'), (131171, 1311, '河北衡水高新技术产业开发区', 'district'), (131172, 1311, '衡水滨湖新区', 'district'), (131182, 1311, '深州市', 'district'), (140105, 1401, '小店区', 'district'), (140106, 1401, '迎泽区', 'district'), (140107, 1401, '杏花岭区', 'district'), (140108, 1401, '尖草坪区', 'district'), (140109, 1401, '万柏林区', 'district'), (140110, 1401, '晋源区', 'district'), (140121, 1401, '清徐县', 'district'), (140122, 1401, '阳曲县', 'district'), (140123, 1401, '娄烦县', 'district'), (140171, 1401, '山西转型综合改革示范区', 'district'), (140181, 1401, '古交市', 'district'), (140212, 1402, '新荣区', 'district'), (140213, 1402, '平城区', 'district'), (140214, 1402, '云冈区', 'district'), (140215, 1402, '云州区', 'district'), (140221, 1402, '阳高县', 'district'), (140222, 1402, '天镇县', 'district'), (140223, 1402, '广灵县', 'district'), (140224, 1402, '灵丘县', 'district'), (140225, 1402, '浑源县', 'district'), (140226, 1402, '左云县', 'district'), (140271, 1402, '山西大同经济开发区', 'district'), (140302, 1403, '城区', 'district'), (140303, 1403, '矿区', 'district'), (140311, 1403, '郊区', 'district'), (140321, 1403, '平定县', 'district'), (140322, 1403, '盂县', 'district'), (140403, 1404, '潞州区', 'district'), (140404, 1404, '上党区', 'district'), (140405, 1404, '屯留区', 'district'), (140406, 1404, '潞城区', 'district'), (140423, 1404, '襄垣县', 'district'), (140425, 1404, '平顺县', 'district'), (140426, 1404, '黎城县', 'district'), (140427, 1404, '壶关县', 'district'), (140428, 1404, '长子县', 'district'), (140429, 1404, '武乡县', 'district'), (140430, 1404, '沁县', 'district'), (140431, 1404, '沁源县', 'district'), (140471, 1404, '山西长治高新技术产业园区', 'district'), (140502, 1405, '城区', 'district'), (140521, 1405, '沁水县', 'district'), (140522, 1405, '阳城县', 'district'), (140524, 1405, '陵川县', 'district'), (140525, 1405, '泽州县', 'district'), (140581, 1405, '高平市', 'district'), (140602, 1406, '朔城区', 'district'), (140603, 1406, '平鲁区', 'district'), (140621, 1406, '山阴县', 'district'), (140622, 1406, '应县', 'district'), (140623, 1406, '右玉县', 'district'), (140671, 1406, '山西朔州经济开发区', 'district'), (140681, 1406, '怀仁市', 'district'), (140702, 1407, '榆次区', 'district'), (140703, 1407, '太谷区', 'district'), (140721, 1407, '榆社县', 'district'), (140722, 1407, '左权县', 'district'), (140723, 1407, '和顺县', 'district'), (140724, 1407, '昔阳县', 'district'), (140725, 1407, '寿阳县', 'district'), (140727, 1407, '祁县', 'district'), (140728, 1407, '平遥县', 'district'), (140729, 1407, '灵石县', 'district'), (140781, 1407, '介休市', 'district'), (140802, 1408, '盐湖区', 'district'), (140821, 1408, '临猗县', 'district'), (140822, 1408, '万荣县', 'district'), (140823, 1408, '闻喜县', 'district'), (140824, 1408, '稷山县', 'district'), (140825, 1408, '新绛县', 'district'), (140826, 1408, '绛县', 'district'), (140827, 1408, '垣曲县', 'district'), (140828, 1408, '夏县', 'district'), (140829, 1408, '平陆县', 'district'), (140830, 1408, '芮城县', 'district'), (140881, 1408, '永济市', 'district'), (140882, 1408, '河津市', 'district'), (140902, 1409, '忻府区', 'district'), (140921, 1409, '定襄县', 'district'), (140922, 1409, '五台县', 'district'), (140923, 1409, '代县', 'district'), (140924, 1409, '繁峙县', 'district'), (140925, 1409, '宁武县', 'district'), (140926, 1409, '静乐县', 'district'), (140927, 1409, '神池县', 'district'), (140928, 1409, '五寨县', 'district'), (140929, 1409, '岢岚县', 'district'), (140930, 1409, '河曲县', 'district'), (140931, 1409, '保德县', 'district'), (140932, 1409, '偏关县', 'district'), (140971, 1409, '五台山风景名胜区', 'district'), (140981, 1409, '原平市', 'district'), (141002, 1410, '尧都区', 'district'), (141021, 1410, '曲沃县', 'district'), (141022, 1410, '翼城县', 'district'), (141023, 1410, '襄汾县', 'district'), (141024, 1410, '洪洞县', 'district'), (141025, 1410, '古县', 'district'), (141026, 1410, '安泽县', 'district'), (141027, 1410, '浮山县', 'district'), (141028, 1410, '吉县', 'district'), (141029, 1410, '乡宁县', 'district'), (141030, 1410, '大宁县', 'district'), (141031, 1410, '隰县', 'district'), (141032, 1410, '永和县', 'district'), (141033, 1410, '蒲县', 'district'), (141034, 1410, '汾西县', 'district'), (141081, 1410, '侯马市', 'district'), (141082, 1410, '霍州市', 'district'), (141102, 1411, '离石区', 'district'), (141121, 1411, '文水县', 'district'), (141122, 1411, '交城县', 'district'), (141123, 1411, '兴县', 'district'), (141124, 1411, '临县', 'district'), (141125, 1411, '柳林县', 'district'), (141126, 1411, '石楼县', 'district'), (141127, 1411, '岚县', 'district'), (141128, 1411, '方山县', 'district'), (141129, 1411, '中阳县', 'district'), (141130, 1411, '交口县', 'district'), (141181, 1411, '孝义市', 'district'), (141182, 1411, '汾阳市', 'district'), (150102, 1501, '新城区', 'district'), (150103, 1501, '回民区', 'district'), (150104, 1501, '玉泉区', 'district'), (150105, 1501, '赛罕区', 'district'), (150121, 1501, '土默特左旗', 'district'), (150122, 1501, '托克托县', 'district'), (150123, 1501, '和林格尔县', 'district'), (150124, 1501, '清水河县', 'district'), (150125, 1501, '武川县', 'district'), (150172, 1501, '呼和浩特经济技术开发区', 'district'), (150202, 1502, '东河区', 'district'), (150203, 1502, '昆都仑区', 'district'), (150204, 1502, '青山区', 'district'), (150205, 1502, '石拐区', 'district'), (150206, 1502, '白云鄂博矿区', 'district'), (150207, 1502, '九原区', 'district'), (150221, 1502, '土默特右旗', 'district'), (150222, 1502, '固阳县', 'district'), (150223, 1502, '达尔罕茂明安联合旗', 'district'), (150271, 1502, '包头稀土高新技术产业开发区', 'district'), (150302, 1503, '海勃湾区', 'district'), (150303, 1503, '海南区', 'district'), (150304, 1503, '乌达区', 'district'), (150402, 1504, '红山区', 'district'), (150403, 1504, '元宝山区', 'district'), (150404, 1504, '松山区', 'district'), (150421, 1504, '阿鲁科尔沁旗', 'district'), (150422, 1504, '巴林左旗', 'district'), (150423, 1504, '巴林右旗', 'district'), (150424, 1504, '林西县', 'district'), (150425, 1504, '克什克腾旗', 'district'), (150426, 1504, '翁牛特旗', 'district'), (150428, 1504, '喀喇沁旗', 'district'), (150429, 1504, '宁城县', 'district'), (150430, 1504, '敖汉旗', 'district'), (150502, 1505, '科尔沁区', 'district'), (150521, 1505, '科尔沁左翼中旗', 'district'), (150522, 1505, '科尔沁左翼后旗', 'district'), (150523, 1505, '开鲁县', 'district'), (150524, 1505, '库伦旗', 'district'), (150525, 1505, '奈曼旗', 'district'), (150526, 1505, '扎鲁特旗', 'district'), (150571, 1505, '通辽经济技术开发区', 'district'), (150581, 1505, '霍林郭勒市', 'district'), (150602, 1506, '东胜区', 'district'), (150603, 1506, '康巴什区', 'district'), (150621, 1506, '达拉特旗', 'district'), (150622, 1506, '准格尔旗', 'district'), (150623, 1506, '鄂托克前旗', 'district'), (150624, 1506, '鄂托克旗', 'district'), (150625, 1506, '杭锦旗', 'district'), (150626, 1506, '乌审旗', 'district'), (150627, 1506, '伊金霍洛旗', 'district'), (150702, 1507, '海拉尔区', 'district'), (150703, 1507, '扎赉诺尔区', 'district'), (150721, 1507, '阿荣旗', 'district'), (150722, 1507, '莫力达瓦达斡尔族自治旗', 'district'), (150723, 1507, '鄂伦春自治旗', 'district'), (150724, 1507, '鄂温克族自治旗', 'district'), (150725, 1507, '陈巴尔虎旗', 'district'), (150726, 1507, '新巴尔虎左旗', 'district'), (150727, 1507, '新巴尔虎右旗', 'district'), (150781, 1507, '满洲里市', 'district'), (150782, 1507, '牙克石市', 'district'), (150783, 1507, '扎兰屯市', 'district'), (150784, 1507, '额尔古纳市', 'district'), (150785, 1507, '根河市', 'district'), (150802, 1508, '临河区', 'district'), (150821, 1508, '五原县', 'district'), (150822, 1508, '磴口县', 'district'), (150823, 1508, '乌拉特前旗', 'district'), (150824, 1508, '乌拉特中旗', 'district'), (150825, 1508, '乌拉特后旗', 'district'), (150826, 1508, '杭锦后旗', 'district'), (150902, 1509, '集宁区', 'district'), (150921, 1509, '卓资县', 'district'), (150922, 1509, '化德县', 'district'), (150923, 1509, '商都县', 'district'), (150924, 1509, '兴和县', 'district'), (150925, 1509, '凉城县', 'district'), (150926, 1509, '察哈尔右翼前旗', 'district'), (150927, 1509, '察哈尔右翼中旗', 'district'), (150928, 1509, '察哈尔右翼后旗', 'district'), (150929, 1509, '四子王旗', 'district'), (150981, 1509, '丰镇市', 'district'), (152201, 1522, '乌兰浩特市', 'district'), (152202, 1522, '阿尔山市', 'district'), (152221, 1522, '科尔沁右翼前旗', 'district'), (152222, 1522, '科尔沁右翼中旗', 'district'), (152223, 1522, '扎赉特旗', 'district'), (152224, 1522, '突泉县', 'district'), (152501, 1525, '二连浩特市', 'district'), (152502, 1525, '锡林浩特市', 'district'), (152522, 1525, '阿巴嘎旗', 'district'), (152523, 1525, '苏尼特左旗', 'district'), (152524, 1525, '苏尼特右旗', 'district'), (152525, 1525, '东乌珠穆沁旗', 'district'), (152526, 1525, '西乌珠穆沁旗', 'district'), (152527, 1525, '太仆寺旗', 'district'), (152528, 1525, '镶黄旗', 'district'), (152529, 1525, '正镶白旗', 'district'), (152530, 1525, '正蓝旗', 'district'), (152531, 1525, '多伦县', 'district'), (152571, 1525, '乌拉盖管委会', 'district'), (152921, 1529, '阿拉善左旗', 'district'), (152922, 1529, '阿拉善右旗', 'district'), (152923, 1529, '额济纳旗', 'district'), (152971, 1529, '内蒙古阿拉善高新技术产业开发区', 'district'), (210102, 2101, '和平区', 'district'), (210103, 2101, '沈河区', 'district'), (210104, 2101, '大东区', 'district'), (210105, 2101, '皇姑区', 'district'), (210106, 2101, '铁西区', 'district'), (210111, 2101, '苏家屯区', 'district'), (210112, 2101, '浑南区', 'district'), (210113, 2101, '沈北新区', 'district'), (210114, 2101, '于洪区', 'district'), (210115, 2101, '辽中区', 'district'), (210123, 2101, '康平县', 'district'), (210124, 2101, '法库县', 'district'), (210181, 2101, '新民市', 'district'), (210202, 2102, '中山区', 'district'), (210203, 2102, '西岗区', 'district'), (210204, 2102, '沙河口区', 'district'), (210211, 2102, '甘井子区', 'district'), (210212, 2102, '旅顺口区', 'district'), (210213, 2102, '金州区', 'district'), (210214, 2102, '普兰店区', 'district'), (210224, 2102, '长海县', 'district'), (210281, 2102, '瓦房店市', 'district'), (210283, 2102, '庄河市', 'district'), (210302, 2103, '铁东区', 'district'), (210303, 2103, '铁西区', 'district'), (210304, 2103, '立山区', 'district'), (210311, 2103, '千山区', 'district'), (210321, 2103, '台安县', 'district'), (210323, 2103, '岫岩满族自治县', 'district'), (210381, 2103, '海城市', 'district'), (210402, 2104, '新抚区', 'district'), (210403, 2104, '东洲区', 'district'), (210404, 2104, '望花区', 'district'), (210411, 2104, '顺城区', 'district'), (210421, 2104, '抚顺县', 'district'), (210422, 2104, '新宾满族自治县', 'district'), (210423, 2104, '清原满族自治县', 'district'), (210502, 2105, '平山区', 'district'), (210503, 2105, '溪湖区', 'district'), (210504, 2105, '明山区', 'district'), (210505, 2105, '南芬区', 'district'), (210521, 2105, '本溪满族自治县', 'district'), (210522, 2105, '桓仁满族自治县', 'district'), (210602, 2106, '元宝区', 'district'), (210603, 2106, '振兴区', 'district'), (210604, 2106, '振安区', 'district'), (210624, 2106, '宽甸满族自治县', 'district'), (210681, 2106, '东港市', 'district'), (210682, 2106, '凤城市', 'district'), (210702, 2107, '古塔区', 'district'), (210703, 2107, '凌河区', 'district'), (210711, 2107, '太和区', 'district'), (210726, 2107, '黑山县', 'district'), (210727, 2107, '义县', 'district'), (210781, 2107, '凌海市', 'district'), (210782, 2107, '北镇市', 'district'), (210802, 2108, '站前区', 'district'), (210803, 2108, '西市区', 'district'), (210804, 2108, '鲅鱼圈区', 'district'), (210811, 2108, '老边区', 'district'), (210881, 2108, '盖州市', 'district'), (210882, 2108, '大石桥市', 'district'), (210902, 2109, '海州区', 'district'), (210903, 2109, '新邱区', 'district'), (210904, 2109, '太平区', 'district'), (210905, 2109, '清河门区', 'district'), (210911, 2109, '细河区', 'district'), (210921, 2109, '阜新蒙古族自治县', 'district'), (210922, 2109, '彰武县', 'district'), (211002, 2110, '白塔区', 'district'), (211003, 2110, '文圣区', 'district'), (211004, 2110, '宏伟区', 'district'), (211005, 2110, '弓长岭区', 'district'), (211011, 2110, '太子河区', 'district'), (211021, 2110, '辽阳县', 'district'), (211081, 2110, '灯塔市', 'district'), (211102, 2111, '双台子区', 'district'), (211103, 2111, '兴隆台区', 'district'), (211104, 2111, '大洼区', 'district'), (211122, 2111, '盘山县', 'district'), (211202, 2112, '银州区', 'district'), (211204, 2112, '清河区', 'district'), (211221, 2112, '铁岭县', 'district'), (211223, 2112, '西丰县', 'district'), (211224, 2112, '昌图县', 'district'), (211281, 2112, '调兵山市', 'district'), (211282, 2112, '开原市', 'district'), (211302, 2113, '双塔区', 'district'), (211303, 2113, '龙城区', 'district'), (211321, 2113, '朝阳县', 'district'), (211322, 2113, '建平县', 'district'), (211324, 2113, '喀喇沁左翼蒙古族自治县', 'district'), (211381, 2113, '北票市', 'district'), (211382, 2113, '凌源市', 'district'), (211402, 2114, '连山区', 'district'), (211403, 2114, '龙港区', 'district'), (211404, 2114, '南票区', 'district'), (211421, 2114, '绥中县', 'district'), (211422, 2114, '建昌县', 'district'), (211481, 2114, '兴城市', 'district'), (220102, 2201, '南关区', 'district'), (220103, 2201, '宽城区', 'district'), (220104, 2201, '朝阳区', 'district'), (220105, 2201, '二道区', 'district'), (220106, 2201, '绿园区', 'district'), (220112, 2201, '双阳区', 'district'), (220113, 2201, '九台区', 'district'), (220122, 2201, '农安县', 'district'), (220171, 2201, '长春经济技术开发区', 'district'), (220172, 2201, '长春净月高新技术产业开发区', 'district'), (220173, 2201, '长春高新技术产业开发区', 'district'), (220174, 2201, '长春汽车经济技术开发区', 'district'), (220182, 2201, '榆树市', 'district'), (220183, 2201, '德惠市', 'district'), (220184, 2201, '公主岭市', 'district'), (220202, 2202, '昌邑区', 'district'), (220203, 2202, '龙潭区', 'district'), (220204, 2202, '船营区', 'district'), (220211, 2202, '丰满区', 'district'), (220221, 2202, '永吉县', 'district'), (220271, 2202, '吉林经济开发区', 'district'), (220272, 2202, '吉林高新技术产业开发区', 'district'), (220273, 2202, '吉林中国新加坡食品区', 'district'), (220281, 2202, '蛟河市', 'district'), (220282, 2202, '桦甸市', 'district'), (220283, 2202, '舒兰市', 'district'), (220284, 2202, '磐石市', 'district'), (220302, 2203, '铁西区', 'district'), (220303, 2203, '铁东区', 'district'), (220322, 2203, '梨树县', 'district'), (220323, 2203, '伊通满族自治县', 'district'), (220382, 2203, '双辽市', 'district'), (220402, 2204, '龙山区', 'district'), (220403, 2204, '西安区', 'district'), (220421, 2204, '东丰县', 'district'), (220422, 2204, '东辽县', 'district'), (220502, 2205, '东昌区', 'district'), (220503, 2205, '二道江区', 'district'), (220521, 2205, '通化县', 'district'), (220523, 2205, '辉南县', 'district'), (220524, 2205, '柳河县', 'district'), (220581, 2205, '梅河口市', 'district'), (220582, 2205, '集安市', 'district'), (220602, 2206, '浑江区', 'district'), (220605, 2206, '江源区', 'district'), (220621, 2206, '抚松县', 'district'), (220622, 2206, '靖宇县', 'district'), (220623, 2206, '长白朝鲜族自治县', 'district'), (220681, 2206, '临江市', 'district'), (220702, 2207, '宁江区', 'district'), (220721, 2207, '前郭尔罗斯蒙古族自治县', 'district'), (220722, 2207, '长岭县', 'district'), (220723, 2207, '乾安县', 'district'), (220771, 2207, '吉林松原经济开发区', 'district'), (220781, 2207, '扶余市', 'district'), (220802, 2208, '洮北区', 'district'), (220821, 2208, '镇赉县', 'district'), (220822, 2208, '通榆县', 'district'), (220871, 2208, '吉林白城经济开发区', 'district'), (220881, 2208, '洮南市', 'district'), (220882, 2208, '大安市', 'district'), (222401, 2224, '延吉市', 'district'), (222402, 2224, '图们市', 'district'), (222403, 2224, '敦化市', 'district'), (222404, 2224, '珲春市', 'district'), (222405, 2224, '龙井市', 'district'), (222406, 2224, '和龙市', 'district'), (222424, 2224, '汪清县', 'district'), (222426, 2224, '安图县', 'district'), (230102, 2301, '道里区', 'district'), (230103, 2301, '南岗区', 'district'), (230104, 2301, '道外区', 'district'), (230108, 2301, '平房区', 'district'), (230109, 2301, '松北区', 'district'), (230110, 2301, '香坊区', 'district'), (230111, 2301, '呼兰区', 'district'), (230112, 2301, '阿城区', 'district'), (230113, 2301, '双城区', 'district'), (230123, 2301, '依兰县', 'district'), (230124, 2301, '方正县', 'district'), (230125, 2301, '宾县', 'district'), (230126, 2301, '巴彦县', 'district'), (230127, 2301, '木兰县', 'district'), (230128, 2301, '通河县', 'district'), (230129, 2301, '延寿县', 'district'), (230183, 2301, '尚志市', 'district'), (230184, 2301, '五常市', 'district'), (230202, 2302, '龙沙区', 'district'), (230203, 2302, '建华区', 'district'), (230204, 2302, '铁锋区', 'district'), (230205, 2302, '昂昂溪区', 'district'), (230206, 2302, '富拉尔基区', 'district'), (230207, 2302, '碾子山区', 'district'), (230208, 2302, '梅里斯达斡尔族区', 'district'), (230221, 2302, '龙江县', 'district'), (230223, 2302, '依安县', 'district'), (230224, 2302, '泰来县', 'district'), (230225, 2302, '甘南县', 'district'), (230227, 2302, '富裕县', 'district'), (230229, 2302, '克山县', 'district'), (230230, 2302, '克东县', 'district'), (230231, 2302, '拜泉县', 'district'), (230281, 2302, '讷河市', 'district'), (230302, 2303, '鸡冠区', 'district'), (230303, 2303, '恒山区', 'district'), (230304, 2303, '滴道区', 'district'), (230305, 2303, '梨树区', 'district'), (230306, 2303, '城子河区', 'district'), (230307, 2303, '麻山区', 'district'), (230321, 2303, '鸡东县', 'district'), (230381, 2303, '虎林市', 'district'), (230382, 2303, '密山市', 'district'), (230402, 2304, '向阳区', 'district'), (230403, 2304, '工农区', 'district'), (230404, 2304, '南山区', 'district'), (230405, 2304, '兴安区', 'district'), (230406, 2304, '东山区', 'district'), (230407, 2304, '兴山区', 'district'), (230421, 2304, '萝北县', 'district'), (230422, 2304, '绥滨县', 'district'), (230502, 2305, '尖山区', 'district'), (230503, 2305, '岭东区', 'district'), (230505, 2305, '四方台区', 'district'), (230506, 2305, '宝山区', 'district'), (230521, 2305, '集贤县', 'district'), (230522, 2305, '友谊县', 'district'), (230523, 2305, '宝清县', 'district'), (230524, 2305, '饶河县', 'district'), (230602, 2306, '萨尔图区', 'district'), (230603, 2306, '龙凤区', 'district'), (230604, 2306, '让胡路区', 'district'), (230605, 2306, '红岗区', 'district'), (230606, 2306, '大同区', 'district'), (230621, 2306, '肇州县', 'district'), (230622, 2306, '肇源县', 'district'), (230623, 2306, '林甸县', 'district'), (230624, 2306, '杜尔伯特蒙古族自治县', 'district'), (230671, 2306, '大庆高新技术产业开发区', 'district'), (230717, 2307, '伊美区', 'district'), (230718, 2307, '乌翠区', 'district'), (230719, 2307, '友好区', 'district'), (230722, 2307, '嘉荫县', 'district'), (230723, 2307, '汤旺县', 'district'), (230724, 2307, '丰林县', 'district'), (230725, 2307, '大箐山县', 'district'), (230726, 2307, '南岔县', 'district'), (230751, 2307, '金林区', 'district'), (230781, 2307, '铁力市', 'district'), (230803, 2308, '向阳区', 'district'), (230804, 2308, '前进区', 'district'), (230805, 2308, '东风区', 'district'), (230811, 2308, '郊区', 'district'), (230822, 2308, '桦南县', 'district'), (230826, 2308, '桦川县', 'district'), (230828, 2308, '汤原县', 'district'), (230881, 2308, '同江市', 'district'), (230882, 2308, '富锦市', 'district'), (230883, 2308, '抚远市', 'district'), (230902, 2309, '新兴区', 'district'), (230903, 2309, '桃山区', 'district'), (230904, 2309, '茄子河区', 'district'), (230921, 2309, '勃利县', 'district'), (231002, 2310, '东安区', 'district'), (231003, 2310, '阳明区', 'district'), (231004, 2310, '爱民区', 'district'), (231005, 2310, '西安区', 'district'), (231025, 2310, '林口县', 'district'), (231071, 2310, '牡丹江经济技术开发区', 'district'), (231081, 2310, '绥芬河市', 'district'), (231083, 2310, '海林市', 'district'), (231084, 2310, '宁安市', 'district'), (231085, 2310, '穆棱市', 'district'), (231086, 2310, '东宁市', 'district'), (231102, 2311, '爱辉区', 'district'), (231123, 2311, '逊克县', 'district'), (231124, 2311, '孙吴县', 'district'), (231181, 2311, '北安市', 'district'), (231182, 2311, '五大连池市', 'district'), (231183, 2311, '嫩江市', 'district'), (231202, 2312, '北林区', 'district'), (231221, 2312, '望奎县', 'district'), (231222, 2312, '兰西县', 'district'), (231223, 2312, '青冈县', 'district'), (231224, 2312, '庆安县', 'district'), (231225, 2312, '明水县', 'district'), (231226, 2312, '绥棱县', 'district'), (231281, 2312, '安达市', 'district'), (231282, 2312, '肇东市', 'district'), (231283, 2312, '海伦市', 'district'), (232701, 2327, '漠河市', 'district'), (232721, 2327, '呼玛县', 'district'), (232722, 2327, '塔河县', 'district'), (232761, 2327, '加格达奇区', 'district'), (232762, 2327, '松岭区', 'district'), (232763, 2327, '新林区', 'district'), (232764, 2327, '呼中区', 'district'), (310101, 3101, '黄浦区', 'district'), (310104, 3101, '徐汇区', 'district'), (310105, 3101, '长宁区', 'district'), (310106, 3101, '静安区', 'district'), (310107, 3101, '普陀区', 'district'), (310109, 3101, '虹口区', 'district'), (310110, 3101, '杨浦区', 'district'), (310112, 3101, '闵行区', 'district'), (310113, 3101, '宝山区', 'district'), (310114, 3101, '嘉定区', 'district'), (310115, 3101, '浦东新区', 'district'), (310116, 3101, '金山区', 'district'), (310117, 3101, '松江区', 'district'), (310118, 3101, '青浦区', 'district'), (310120, 3101, '奉贤区', 'district'), (310151, 3101, '崇明区', 'district'), (320102, 3201, '玄武区', 'district'), (320104, 3201, '秦淮区', 'district'), (320105, 3201, '建邺区', 'district'), (320106, 3201, '鼓楼区', 'district'), (320111, 3201, '浦口区', 'district'), (320113, 3201, '栖霞区', 'district'), (320114, 3201, '雨花台区', 'district'), (320115, 3201, '江宁区', 'district'), (320116, 3201, '六合区', 'district'), (320117, 3201, '溧水区', 'district'), (320118, 3201, '高淳区', 'district'), (320205, 3202, '锡山区', 'district'), (320206, 3202, '惠山区', 'district'), (320211, 3202, '滨湖区', 'district'), (320213, 3202, '梁溪区', 'district'), (320214, 3202, '新吴区', 'district'), (320281, 3202, '江阴市', 'district'), (320282, 3202, '宜兴市', 'district'), (320302, 3203, '鼓楼区', 'district'), (320303, 3203, '云龙区', 'district'), (320305, 3203, '贾汪区', 'district'), (320311, 3203, '泉山区', 'district'), (320312, 3203, '铜山区', 'district'), (320321, 3203, '丰县', 'district'), (320322, 3203, '沛县', 'district'), (320324, 3203, '睢宁县', 'district'), (320371, 3203, '徐州经济技术开发区', 'district'), (320381, 3203, '新沂市', 'district'), (320382, 3203, '邳州市', 'district'), (320402, 3204, '天宁区', 'district'), (320404, 3204, '钟楼区', 'district'), (320411, 3204, '新北区', 'district'), (320412, 3204, '武进区', 'district'), (320413, 3204, '金坛区', 'district'), (320481, 3204, '溧阳市', 'district'), (320505, 3205, '虎丘区', 'district'), (320506, 3205, '吴中区', 'district'), (320507, 3205, '相城区', 'district'), (320508, 3205, '姑苏区', 'district'), (320509, 3205, '吴江区', 'district'), (320571, 3205, '苏州工业园区', 'district'), (320581, 3205, '常熟市', 'district'), (320582, 3205, '张家港市', 'district'), (320583, 3205, '昆山市', 'district'), (320585, 3205, '太仓市', 'district'), (320612, 3206, '通州区', 'district'), (320613, 3206, '崇川区', 'district'), (320614, 3206, '海门区', 'district'), (320623, 3206, '如东县', 'district'), (320671, 3206, '南通经济技术开发区', 'district'), (320681, 3206, '启东市', 'district'), (320682, 3206, '如皋市', 'district'), (320685, 3206, '海安市', 'district'), (320703, 3207, '连云区', 'district'), (320706, 3207, '海州区', 'district'), (320707, 3207, '赣榆区', 'district'), (320722, 3207, '东海县', 'district'), (320723, 3207, '灌云县', 'district'), (320724, 3207, '灌南县', 'district'), (320771, 3207, '连云港经济技术开发区', 'district'), (320772, 3207, '连云港高新技术产业开发区', 'district'), (320803, 3208, '淮安区', 'district'), (320804, 3208, '淮阴区', 'district'), (320812, 3208, '清江浦区', 'district'), (320813, 3208, '洪泽区', 'district'), (320826, 3208, '涟水县', 'district'), (320830, 3208, '盱眙县', 'district'), (320831, 3208, '金湖县', 'district'), (320871, 3208, '淮安经济技术开发区', 'district'), (320902, 3209, '亭湖区', 'district'), (320903, 3209, '盐都区', 'district'), (320904, 3209, '大丰区', 'district'), (320921, 3209, '响水县', 'district'), (320922, 3209, '滨海县', 'district'), (320923, 3209, '阜宁县', 'district'), (320924, 3209, '射阳县', 'district'), (320925, 3209, '建湖县', 'district'), (320971, 3209, '盐城经济技术开发区', 'district'), (320981, 3209, '东台市', 'district'), (321002, 3210, '广陵区', 'district'), (321003, 3210, '邗江区', 'district'), (321012, 3210, '江都区', 'district'), (321023, 3210, '宝应县', 'district'), (321071, 3210, '扬州经济技术开发区', 'district'), (321081, 3210, '仪征市', 'district'), (321084, 3210, '高邮市', 'district'), (321102, 3211, '京口区', 'district'), (321111, 3211, '润州区', 'district'), (321112, 3211, '丹徒区', 'district'), (321171, 3211, '镇江新区', 'district'), (321181, 3211, '丹阳市', 'district'), (321182, 3211, '扬中市', 'district'), (321183, 3211, '句容市', 'district'), (321202, 3212, '海陵区', 'district'), (321203, 3212, '高港区', 'district'), (321204, 3212, '姜堰区', 'district'), (321271, 3212, '泰州医药高新技术产业开发区', 'district'), (321281, 3212, '兴化市', 'district'), (321282, 3212, '靖江市', 'district'), (321283, 3212, '泰兴市', 'district'), (321302, 3213, '宿城区', 'district'), (321311, 3213, '宿豫区', 'district'), (321322, 3213, '沭阳县', 'district'), (321323, 3213, '泗阳县', 'district'), (321324, 3213, '泗洪县', 'district'), (321371, 3213, '宿迁经济技术开发区', 'district'), (330102, 3301, '上城区', 'district'), (330105, 3301, '拱墅区', 'district'), (330106, 3301, '西湖区', 'district'), (330108, 3301, '滨江区', 'district'), (330109, 3301, '萧山区', 'district'), (330110, 3301, '余杭区', 'district'), (330111, 3301, '富阳区', 'district'), (330112, 3301, '临安区', 'district'), (330113, 3301, '临平区', 'district'), (330114, 3301, '钱塘区', 'district'), (330122, 3301, '桐庐县', 'district'), (330127, 3301, '淳安县', 'district'), (330182, 3301, '建德市', 'district'), (330203, 3302, '海曙区', 'district'), (330205, 3302, '江北区', 'district'), (330206, 3302, '北仑区', 'district'), (330211, 3302, '镇海区', 'district'), (330212, 3302, '鄞州区', 'district'), (330213, 3302, '奉化区', 'district'), (330225, 3302, '象山县', 'district'), (330226, 3302, '宁海县', 'district'), (330281, 3302, '余姚市', 'district'), (330282, 3302, '慈溪市', 'district'), (330302, 3303, '鹿城区', 'district'), (330303, 3303, '龙湾区', 'district'), (330304, 3303, '瓯海区', 'district'), (330305, 3303, '洞头区', 'district'), (330324, 3303, '永嘉县', 'district'), (330326, 3303, '平阳县', 'district'), (330327, 3303, '苍南县', 'district'), (330328, 3303, '文成县', 'district'), (330329, 3303, '泰顺县', 'district'), (330371, 3303, '温州经济技术开发区', 'district'), (330381, 3303, '瑞安市', 'district'), (330382, 3303, '乐清市', 'district'), (330383, 3303, '龙港市', 'district'), (330402, 3304, '南湖区', 'district'), (330411, 3304, '秀洲区', 'district'), (330421, 3304, '嘉善县', 'district'), (330424, 3304, '海盐县', 'district'), (330481, 3304, '海宁市', 'district'), (330482, 3304, '平湖市', 'district'), (330483, 3304, '桐乡市', 'district'), (330502, 3305, '吴兴区', 'district'), (330503, 3305, '南浔区', 'district'), (330521, 3305, '德清县', 'district'), (330522, 3305, '长兴县', 'district'), (330523, 3305, '安吉县', 'district'), (330602, 3306, '越城区', 'district'), (330603, 3306, '柯桥区', 'district'), (330604, 3306, '上虞区', 'district'), (330624, 3306, '新昌县', 'district'), (330681, 3306, '诸暨市', 'district'), (330683, 3306, '嵊州市', 'district'), (330702, 3307, '婺城区', 'district'), (330703, 3307, '金东区', 'district'), (330723, 3307, '武义县', 'district'), (330726, 3307, '浦江县', 'district'), (330727, 3307, '磐安县', 'district'), (330781, 3307, '兰溪市', 'district'), (330782, 3307, '义乌市', 'district'), (330783, 3307, '东阳市', 'district'), (330784, 3307, '永康市', 'district'), (330802, 3308, '柯城区', 'district'), (330803, 3308, '衢江区', 'district'), (330822, 3308, '常山县', 'district'), (330824, 3308, '开化县', 'district'), (330825, 3308, '龙游县', 'district'), (330881, 3308, '江山市', 'district'), (330902, 3309, '定海区', 'district'), (330903, 3309, '普陀区', 'district'), (330921, 3309, '岱山县', 'district'), (330922, 3309, '嵊泗县', 'district'), (331002, 3310, '椒江区', 'district'), (331003, 3310, '黄岩区', 'district'), (331004, 3310, '路桥区', 'district'), (331022, 3310, '三门县', 'district'), (331023, 3310, '天台县', 'district'), (331024, 3310, '仙居县', 'district'), (331081, 3310, '温岭市', 'district'), (331082, 3310, '临海市', 'district'), (331083, 3310, '玉环市', 'district'), (331102, 3311, '莲都区', 'district'), (331121, 3311, '青田县', 'district'), (331122, 3311, '缙云县', 'district'), (331123, 3311, '遂昌县', 'district'), (331124, 3311, '松阳县', 'district'), (331125, 3311, '云和县', 'district'), (331126, 3311, '庆元县', 'district'), (331127, 3311, '景宁畲族自治县', 'district'), (331181, 3311, '龙泉市', 'district'), (340102, 3401, '瑶海区', 'district'), (340103, 3401, '庐阳区', 'district'), (340104, 3401, '蜀山区', 'district'), (340111, 3401, '包河区', 'district'), (340121, 3401, '长丰县', 'district'), (340122, 3401, '肥东县', 'district'), (340123, 3401, '肥西县', 'district'), (340124, 3401, '庐江县', 'district'), (340171, 3401, '合肥高新技术产业开发区', 'district'), (340172, 3401, '合肥经济技术开发区', 'district'), (340173, 3401, '合肥新站高新技术产业开发区', 'district'), (340181, 3401, '巢湖市', 'district'), (340202, 3402, '镜湖区', 'district'), (340207, 3402, '鸠江区', 'district'), (340209, 3402, '弋江区', 'district'), (340210, 3402, '湾沚区', 'district'), (340212, 3402, '繁昌区', 'district'), (340223, 3402, '南陵县', 'district'), (340271, 3402, '芜湖经济技术开发区', 'district'), (340272, 3402, '安徽芜湖三山经济开发区', 'district'), (340281, 3402, '无为市', 'district'), (340302, 3403, '龙子湖区', 'district'), (340303, 3403, '蚌山区', 'district'), (340304, 3403, '禹会区', 'district'), (340311, 3403, '淮上区', 'district'), (340321, 3403, '怀远县', 'district'), (340322, 3403, '五河县', 'district'), (340323, 3403, '固镇县', 'district'), (340371, 3403, '蚌埠市高新技术开发区', 'district'), (340372, 3403, '蚌埠市经济开发区', 'district'), (340402, 3404, '大通区', 'district'), (340403, 3404, '田家庵区', 'district'), (340404, 3404, '谢家集区', 'district'), (340405, 3404, '八公山区', 'district'), (340406, 3404, '潘集区', 'district'), (340421, 3404, '凤台县', 'district'), (340422, 3404, '寿县', 'district'), (340503, 3405, '花山区', 'district'), (340504, 3405, '雨山区', 'district'), (340506, 3405, '博望区', 'district'), (340521, 3405, '当涂县', 'district'), (340522, 3405, '含山县', 'district'), (340523, 3405, '和县', 'district'), (340602, 3406, '杜集区', 'district'), (340603, 3406, '相山区', 'district'), (340604, 3406, '烈山区', 'district'), (340621, 3406, '濉溪县', 'district'), (340705, 3407, '铜官区', 'district'), (340706, 3407, '义安区', 'district'), (340711, 3407, '郊区', 'district'), (340722, 3407, '枞阳县', 'district'), (340802, 3408, '迎江区', 'district'), (340803, 3408, '大观区', 'district'), (340811, 3408, '宜秀区', 'district'), (340822, 3408, '怀宁县', 'district'), (340825, 3408, '太湖县', 'district'), (340826, 3408, '宿松县', 'district'), (340827, 3408, '望江县', 'district'), (340828, 3408, '岳西县', 'district'), (340871, 3408, '安徽安庆经济开发区', 'district'), (340881, 3408, '桐城市', 'district'), (340882, 3408, '潜山市', 'district'), (341002, 3410, '屯溪区', 'district'), (341003, 3410, '黄山区', 'district'), (341004, 3410, '徽州区', 'district'), (341021, 3410, '歙县', 'district'), (341022, 3410, '休宁县', 'district'), (341023, 3410, '黟县', 'district'), (341024, 3410, '祁门县', 'district'), (341102, 3411, '琅琊区', 'district'), (341103, 3411, '南谯区', 'district'), (341122, 3411, '来安县', 'district'), (341124, 3411, '全椒县', 'district'), (341125, 3411, '定远县', 'district'), (341126, 3411, '凤阳县', 'district'), (341171, 3411, '中新苏滁高新技术产业开发区', 'district'), (341172, 3411, '滁州经济技术开发区', 'district'), (341181, 3411, '天长市', 'district'), (341182, 3411, '明光市', 'district'), (341202, 3412, '颍州区', 'district'), (341203, 3412, '颍东区', 'district'), (341204, 3412, '颍泉区', 'district'), (341221, 3412, '临泉县', 'district'), (341222, 3412, '太和县', 'district'), (341225, 3412, '阜南县', 'district'), (341226, 3412, '颍上县', 'district'), (341271, 3412, '阜阳合肥现代产业园区', 'district'), (341272, 3412, '阜阳经济技术开发区', 'district'), (341282, 3412, '界首市', 'district'), (341302, 3413, '埇桥区', 'district'), (341321, 3413, '砀山县', 'district'), (341322, 3413, '萧县', 'district'), (341323, 3413, '灵璧县', 'district'), (341324, 3413, '泗县', 'district'), (341371, 3413, '宿州马鞍山现代产业园区', 'district'), (341372, 3413, '宿州经济技术开发区', 'district'), (341502, 3415, '金安区', 'district'), (341503, 3415, '裕安区', 'district'), (341504, 3415, '叶集区', 'district'), (341522, 3415, '霍邱县', 'district'), (341523, 3415, '舒城县', 'district'), (341524, 3415, '金寨县', 'district'), (341525, 3415, '霍山县', 'district'), (341602, 3416, '谯城区', 'district'), (341621, 3416, '涡阳县', 'district'), (341622, 3416, '蒙城县', 'district'), (341623, 3416, '利辛县', 'district'), (341702, 3417, '贵池区', 'district'), (341721, 3417, '东至县', 'district'), (341722, 3417, '石台县', 'district'), (341723, 3417, '青阳县', 'district'), (341802, 3418, '宣州区', 'district'), (341821, 3418, '郎溪县', 'district'), (341823, 3418, '泾县', 'district'), (341824, 3418, '绩溪县', 'district'), (341825, 3418, '旌德县', 'district'), (341871, 3418, '宣城市经济开发区', 'district'), (341881, 3418, '宁国市', 'district'), (341882, 3418, '广德市', 'district'), (350102, 3501, '鼓楼区', 'district'), (350103, 3501, '台江区', 'district'), (350104, 3501, '仓山区', 'district'), (350105, 3501, '马尾区', 'district'), (350111, 3501, '晋安区', 'district'), (350112, 3501, '长乐区', 'district'), (350121, 3501, '闽侯县', 'district'), (350122, 3501, '连江县', 'district'), (350123, 3501, '罗源县', 'district'), (350124, 3501, '闽清县', 'district'), (350125, 3501, '永泰县', 'district'), (350128, 3501, '平潭县', 'district'), (350181, 3501, '福清市', 'district'), (350203, 3502, '思明区', 'district'), (350205, 3502, '海沧区', 'district'), (350206, 3502, '湖里区', 'district'), (350211, 3502, '集美区', 'district'), (350212, 3502, '同安区', 'district'), (350213, 3502, '翔安区', 'district'), (350302, 3503, '城厢区', 'district'), (350303, 3503, '涵江区', 'district'), (350304, 3503, '荔城区', 'district'), (350305, 3503, '秀屿区', 'district'), (350322, 3503, '仙游县', 'district'), (350404, 3504, '三元区', 'district'), (350405, 3504, '沙县区', 'district'), (350421, 3504, '明溪县', 'district'), (350423, 3504, '清流县', 'district'), (350424, 3504, '宁化县', 'district'), (350425, 3504, '大田县', 'district'), (350426, 3504, '尤溪县', 'district'), (350428, 3504, '将乐县', 'district'), (350429, 3504, '泰宁县', 'district'), (350430, 3504, '建宁县', 'district'), (350481, 3504, '永安市', 'district'), (350502, 3505, '鲤城区', 'district'), (350503, 3505, '丰泽区', 'district'), (350504, 3505, '洛江区', 'district'), (350505, 3505, '泉港区', 'district'), (350521, 3505, '惠安县', 'district'), (350524, 3505, '安溪县', 'district'), (350525, 3505, '永春县', 'district'), (350526, 3505, '德化县', 'district'), (350527, 3505, '金门县', 'district'), (350581, 3505, '石狮市', 'district'), (350582, 3505, '晋江市', 'district'), (350583, 3505, '南安市', 'district'), (350602, 3506, '芗城区', 'district'), (350603, 3506, '龙文区', 'district'), (350604, 3506, '龙海区', 'district'), (350605, 3506, '长泰区', 'district'), (350622, 3506, '云霄县', 'district'), (350623, 3506, '漳浦县', 'district'), (350624, 3506, '诏安县', 'district'), (350626, 3506, '东山县', 'district'), (350627, 3506, '南靖县', 'district'), (350628, 3506, '平和县', 'district'), (350629, 3506, '华安县', 'district'), (350702, 3507, '延平区', 'district'), (350703, 3507, '建阳区', 'district'), (350721, 3507, '顺昌县', 'district'), (350722, 3507, '浦城县', 'district'), (350723, 3507, '光泽县', 'district'), (350724, 3507, '松溪县', 'district'), (350725, 3507, '政和县', 'district'), (350781, 3507, '邵武市', 'district'), (350782, 3507, '武夷山市', 'district'), (350783, 3507, '建瓯市', 'district'), (350802, 3508, '新罗区', 'district'), (350803, 3508, '永定区', 'district'), (350821, 3508, '长汀县', 'district'), (350823, 3508, '上杭县', 'district'), (350824, 3508, '武平县', 'district'), (350825, 3508, '连城县', 'district'), (350881, 3508, '漳平市', 'district'), (350902, 3509, '蕉城区', 'district'), (350921, 3509, '霞浦县', 'district'), (350922, 3509, '古田县', 'district'), (350923, 3509, '屏南县', 'district'), (350924, 3509, '寿宁县', 'district'), (350925, 3509, '周宁县', 'district'), (350926, 3509, '柘荣县', 'district'), (350981, 3509, '福安市', 'district'), (350982, 3509, '福鼎市', 'district'), (360102, 3601, '东湖区', 'district'), (360103, 3601, '西湖区', 'district'), (360104, 3601, '青云谱区', 'district'), (360111, 3601, '青山湖区', 'district'), (360112, 3601, '新建区', 'district'), (360113, 3601, '红谷滩区', 'district'), (360121, 3601, '南昌县', 'district'), (360123, 3601, '安义县', 'district'), (360124, 3601, '进贤县', 'district'), (360202, 3602, '昌江区', 'district'), (360203, 3602, '珠山区', 'district'), (360222, 3602, '浮梁县', 'district'), (360281, 3602, '乐平市', 'district'), (360302, 3603, '安源区', 'district'), (360313, 3603, '湘东区', 'district'), (360321, 3603, '莲花县', 'district'), (360322, 3603, '上栗县', 'district'), (360323, 3603, '芦溪县', 'district'), (360402, 3604, '濂溪区', 'district'), (360403, 3604, '浔阳区', 'district'), (360404, 3604, '柴桑区', 'district');
INSERT INTO `__PREFIX__shopro_data_area` (`id`, `pid`, `name`, `level`) VALUES (360423, 3604, '武宁县', 'district'), (360424, 3604, '修水县', 'district'), (360425, 3604, '永修县', 'district'), (360426, 3604, '德安县', 'district'), (360428, 3604, '都昌县', 'district'), (360429, 3604, '湖口县', 'district'), (360430, 3604, '彭泽县', 'district'), (360481, 3604, '瑞昌市', 'district'), (360482, 3604, '共青城市', 'district'), (360483, 3604, '庐山市', 'district'), (360502, 3605, '渝水区', 'district'), (360521, 3605, '分宜县', 'district'), (360602, 3606, '月湖区', 'district'), (360603, 3606, '余江区', 'district'), (360681, 3606, '贵溪市', 'district'), (360702, 3607, '章贡区', 'district'), (360703, 3607, '南康区', 'district'), (360704, 3607, '赣县区', 'district'), (360722, 3607, '信丰县', 'district'), (360723, 3607, '大余县', 'district'), (360724, 3607, '上犹县', 'district'), (360725, 3607, '崇义县', 'district'), (360726, 3607, '安远县', 'district'), (360728, 3607, '定南县', 'district'), (360729, 3607, '全南县', 'district'), (360730, 3607, '宁都县', 'district'), (360731, 3607, '于都县', 'district'), (360732, 3607, '兴国县', 'district'), (360733, 3607, '会昌县', 'district'), (360734, 3607, '寻乌县', 'district'), (360735, 3607, '石城县', 'district'), (360781, 3607, '瑞金市', 'district'), (360783, 3607, '龙南市', 'district'), (360802, 3608, '吉州区', 'district'), (360803, 3608, '青原区', 'district'), (360821, 3608, '吉安县', 'district'), (360822, 3608, '吉水县', 'district'), (360823, 3608, '峡江县', 'district'), (360824, 3608, '新干县', 'district'), (360825, 3608, '永丰县', 'district'), (360826, 3608, '泰和县', 'district'), (360827, 3608, '遂川县', 'district'), (360828, 3608, '万安县', 'district'), (360829, 3608, '安福县', 'district'), (360830, 3608, '永新县', 'district'), (360881, 3608, '井冈山市', 'district'), (360902, 3609, '袁州区', 'district'), (360921, 3609, '奉新县', 'district'), (360922, 3609, '万载县', 'district'), (360923, 3609, '上高县', 'district'), (360924, 3609, '宜丰县', 'district'), (360925, 3609, '靖安县', 'district'), (360926, 3609, '铜鼓县', 'district'), (360981, 3609, '丰城市', 'district'), (360982, 3609, '樟树市', 'district'), (360983, 3609, '高安市', 'district'), (361002, 3610, '临川区', 'district'), (361003, 3610, '东乡区', 'district'), (361021, 3610, '南城县', 'district'), (361022, 3610, '黎川县', 'district'), (361023, 3610, '南丰县', 'district'), (361024, 3610, '崇仁县', 'district'), (361025, 3610, '乐安县', 'district'), (361026, 3610, '宜黄县', 'district'), (361027, 3610, '金溪县', 'district'), (361028, 3610, '资溪县', 'district'), (361030, 3610, '广昌县', 'district'), (361102, 3611, '信州区', 'district'), (361103, 3611, '广丰区', 'district'), (361104, 3611, '广信区', 'district'), (361123, 3611, '玉山县', 'district'), (361124, 3611, '铅山县', 'district'), (361125, 3611, '横峰县', 'district'), (361126, 3611, '弋阳县', 'district'), (361127, 3611, '余干县', 'district'), (361128, 3611, '鄱阳县', 'district'), (361129, 3611, '万年县', 'district'), (361130, 3611, '婺源县', 'district'), (361181, 3611, '德兴市', 'district'), (370102, 3701, '历下区', 'district'), (370103, 3701, '市中区', 'district'), (370104, 3701, '槐荫区', 'district'), (370105, 3701, '天桥区', 'district'), (370112, 3701, '历城区', 'district'), (370113, 3701, '长清区', 'district'), (370114, 3701, '章丘区', 'district'), (370115, 3701, '济阳区', 'district'), (370116, 3701, '莱芜区', 'district'), (370117, 3701, '钢城区', 'district'), (370124, 3701, '平阴县', 'district'), (370126, 3701, '商河县', 'district'), (370171, 3701, '济南高新技术产业开发区', 'district'), (370202, 3702, '市南区', 'district'), (370203, 3702, '市北区', 'district'), (370211, 3702, '黄岛区', 'district'), (370212, 3702, '崂山区', 'district'), (370213, 3702, '李沧区', 'district'), (370214, 3702, '城阳区', 'district'), (370215, 3702, '即墨区', 'district'), (370271, 3702, '青岛高新技术产业开发区', 'district'), (370281, 3702, '胶州市', 'district'), (370283, 3702, '平度市', 'district'), (370285, 3702, '莱西市', 'district'), (370302, 3703, '淄川区', 'district'), (370303, 3703, '张店区', 'district'), (370304, 3703, '博山区', 'district'), (370305, 3703, '临淄区', 'district'), (370306, 3703, '周村区', 'district'), (370321, 3703, '桓台县', 'district'), (370322, 3703, '高青县', 'district'), (370323, 3703, '沂源县', 'district'), (370402, 3704, '市中区', 'district'), (370403, 3704, '薛城区', 'district'), (370404, 3704, '峄城区', 'district'), (370405, 3704, '台儿庄区', 'district'), (370406, 3704, '山亭区', 'district'), (370481, 3704, '滕州市', 'district'), (370502, 3705, '东营区', 'district'), (370503, 3705, '河口区', 'district'), (370505, 3705, '垦利区', 'district'), (370522, 3705, '利津县', 'district'), (370523, 3705, '广饶县', 'district'), (370571, 3705, '东营经济技术开发区', 'district'), (370572, 3705, '东营港经济开发区', 'district'), (370602, 3706, '芝罘区', 'district'), (370611, 3706, '福山区', 'district'), (370612, 3706, '牟平区', 'district'), (370613, 3706, '莱山区', 'district'), (370614, 3706, '蓬莱区', 'district'), (370671, 3706, '烟台高新技术产业开发区', 'district'), (370672, 3706, '烟台经济技术开发区', 'district'), (370681, 3706, '龙口市', 'district'), (370682, 3706, '莱阳市', 'district'), (370683, 3706, '莱州市', 'district'), (370685, 3706, '招远市', 'district'), (370686, 3706, '栖霞市', 'district'), (370687, 3706, '海阳市', 'district'), (370702, 3707, '潍城区', 'district'), (370703, 3707, '寒亭区', 'district'), (370704, 3707, '坊子区', 'district'), (370705, 3707, '奎文区', 'district'), (370724, 3707, '临朐县', 'district'), (370725, 3707, '昌乐县', 'district'), (370772, 3707, '潍坊滨海经济技术开发区', 'district'), (370781, 3707, '青州市', 'district'), (370782, 3707, '诸城市', 'district'), (370783, 3707, '寿光市', 'district'), (370784, 3707, '安丘市', 'district'), (370785, 3707, '高密市', 'district'), (370786, 3707, '昌邑市', 'district'), (370811, 3708, '任城区', 'district'), (370812, 3708, '兖州区', 'district'), (370826, 3708, '微山县', 'district'), (370827, 3708, '鱼台县', 'district'), (370828, 3708, '金乡县', 'district'), (370829, 3708, '嘉祥县', 'district'), (370830, 3708, '汶上县', 'district'), (370831, 3708, '泗水县', 'district'), (370832, 3708, '梁山县', 'district'), (370871, 3708, '济宁高新技术产业开发区', 'district'), (370881, 3708, '曲阜市', 'district'), (370883, 3708, '邹城市', 'district'), (370902, 3709, '泰山区', 'district'), (370911, 3709, '岱岳区', 'district'), (370921, 3709, '宁阳县', 'district'), (370923, 3709, '东平县', 'district'), (370982, 3709, '新泰市', 'district'), (370983, 3709, '肥城市', 'district'), (371002, 3710, '环翠区', 'district'), (371003, 3710, '文登区', 'district'), (371071, 3710, '威海火炬高技术产业开发区', 'district'), (371072, 3710, '威海经济技术开发区', 'district'), (371073, 3710, '威海临港经济技术开发区', 'district'), (371082, 3710, '荣成市', 'district'), (371083, 3710, '乳山市', 'district'), (371102, 3711, '东港区', 'district'), (371103, 3711, '岚山区', 'district'), (371121, 3711, '五莲县', 'district'), (371122, 3711, '莒县', 'district'), (371171, 3711, '日照经济技术开发区', 'district'), (371302, 3713, '兰山区', 'district'), (371311, 3713, '罗庄区', 'district'), (371312, 3713, '河东区', 'district'), (371321, 3713, '沂南县', 'district'), (371322, 3713, '郯城县', 'district'), (371323, 3713, '沂水县', 'district'), (371324, 3713, '兰陵县', 'district'), (371325, 3713, '费县', 'district'), (371326, 3713, '平邑县', 'district'), (371327, 3713, '莒南县', 'district'), (371328, 3713, '蒙阴县', 'district'), (371329, 3713, '临沭县', 'district'), (371371, 3713, '临沂高新技术产业开发区', 'district'), (371402, 3714, '德城区', 'district'), (371403, 3714, '陵城区', 'district'), (371422, 3714, '宁津县', 'district'), (371423, 3714, '庆云县', 'district'), (371424, 3714, '临邑县', 'district'), (371425, 3714, '齐河县', 'district'), (371426, 3714, '平原县', 'district'), (371427, 3714, '夏津县', 'district'), (371428, 3714, '武城县', 'district'), (371471, 3714, '德州经济技术开发区', 'district'), (371472, 3714, '德州运河经济开发区', 'district'), (371481, 3714, '乐陵市', 'district'), (371482, 3714, '禹城市', 'district'), (371502, 3715, '东昌府区', 'district'), (371503, 3715, '茌平区', 'district'), (371521, 3715, '阳谷县', 'district'), (371522, 3715, '莘县', 'district'), (371524, 3715, '东阿县', 'district'), (371525, 3715, '冠县', 'district'), (371526, 3715, '高唐县', 'district'), (371581, 3715, '临清市', 'district'), (371602, 3716, '滨城区', 'district'), (371603, 3716, '沾化区', 'district'), (371621, 3716, '惠民县', 'district'), (371622, 3716, '阳信县', 'district'), (371623, 3716, '无棣县', 'district'), (371625, 3716, '博兴县', 'district'), (371681, 3716, '邹平市', 'district'), (371702, 3717, '牡丹区', 'district'), (371703, 3717, '定陶区', 'district'), (371721, 3717, '曹县', 'district'), (371722, 3717, '单县', 'district'), (371723, 3717, '成武县', 'district'), (371724, 3717, '巨野县', 'district'), (371725, 3717, '郓城县', 'district'), (371726, 3717, '鄄城县', 'district'), (371728, 3717, '东明县', 'district'), (371771, 3717, '菏泽经济技术开发区', 'district'), (371772, 3717, '菏泽高新技术开发区', 'district'), (410102, 4101, '中原区', 'district'), (410103, 4101, '二七区', 'district'), (410104, 4101, '管城回族区', 'district'), (410105, 4101, '金水区', 'district'), (410106, 4101, '上街区', 'district'), (410108, 4101, '惠济区', 'district'), (410122, 4101, '中牟县', 'district'), (410170, 4101, '郑东新区', 'district'), (410171, 4101, '郑州经济技术开发区', 'district'), (410172, 4101, '郑州高新技术产业开发区', 'district'), (410173, 4101, '郑州航空港经济综合实验区', 'district'), (410181, 4101, '巩义市', 'district'), (410182, 4101, '荥阳市', 'district'), (410183, 4101, '新密市', 'district'), (410184, 4101, '新郑市', 'district'), (410185, 4101, '登封市', 'district'), (410202, 4102, '龙亭区', 'district'), (410203, 4102, '顺河回族区', 'district'), (410204, 4102, '鼓楼区', 'district'), (410205, 4102, '禹王台区', 'district'), (410212, 4102, '祥符区', 'district'), (410221, 4102, '杞县', 'district'), (410222, 4102, '通许县', 'district'), (410223, 4102, '尉氏县', 'district'), (410225, 4102, '兰考县', 'district'), (410302, 4103, '老城区', 'district'), (410303, 4103, '西工区', 'district'), (410304, 4103, '瀍河回族区', 'district'), (410305, 4103, '涧西区', 'district'), (410307, 4103, '偃师区', 'district'), (410308, 4103, '孟津区', 'district'), (410311, 4103, '洛龙区', 'district'), (410323, 4103, '新安县', 'district'), (410324, 4103, '栾川县', 'district'), (410325, 4103, '嵩县', 'district'), (410326, 4103, '汝阳县', 'district'), (410327, 4103, '宜阳县', 'district'), (410328, 4103, '洛宁县', 'district'), (410329, 4103, '伊川县', 'district'), (410371, 4103, '洛阳高新技术产业开发区', 'district'), (410402, 4104, '新华区', 'district'), (410403, 4104, '卫东区', 'district'), (410404, 4104, '石龙区', 'district'), (410411, 4104, '湛河区', 'district'), (410421, 4104, '宝丰县', 'district'), (410422, 4104, '叶县', 'district'), (410423, 4104, '鲁山县', 'district'), (410425, 4104, '郏县', 'district'), (410471, 4104, '平顶山高新技术产业开发区', 'district'), (410472, 4104, '平顶山市城乡一体化示范区', 'district'), (410481, 4104, '舞钢市', 'district'), (410482, 4104, '汝州市', 'district'), (410502, 4105, '文峰区', 'district'), (410503, 4105, '北关区', 'district'), (410505, 4105, '殷都区', 'district'), (410506, 4105, '龙安区', 'district'), (410522, 4105, '安阳县', 'district'), (410523, 4105, '汤阴县', 'district'), (410526, 4105, '滑县', 'district'), (410527, 4105, '内黄县', 'district'), (410571, 4105, '安阳高新技术产业开发区', 'district'), (410581, 4105, '林州市', 'district'), (410602, 4106, '鹤山区', 'district'), (410603, 4106, '山城区', 'district'), (410611, 4106, '淇滨区', 'district'), (410621, 4106, '浚县', 'district'), (410622, 4106, '淇县', 'district'), (410671, 4106, '鹤壁经济技术开发区', 'district'), (410702, 4107, '红旗区', 'district'), (410703, 4107, '卫滨区', 'district'), (410704, 4107, '凤泉区', 'district'), (410711, 4107, '牧野区', 'district'), (410721, 4107, '新乡县', 'district'), (410724, 4107, '获嘉县', 'district'), (410725, 4107, '原阳县', 'district'), (410726, 4107, '延津县', 'district'), (410727, 4107, '封丘县', 'district'), (410771, 4107, '新乡高新技术产业开发区', 'district'), (410772, 4107, '新乡经济技术开发区', 'district'), (410773, 4107, '新乡市平原城乡一体化示范区', 'district'), (410781, 4107, '卫辉市', 'district'), (410782, 4107, '辉县市', 'district'), (410783, 4107, '长垣市', 'district'), (410802, 4108, '解放区', 'district'), (410803, 4108, '中站区', 'district'), (410804, 4108, '马村区', 'district'), (410811, 4108, '山阳区', 'district'), (410821, 4108, '修武县', 'district'), (410822, 4108, '博爱县', 'district'), (410823, 4108, '武陟县', 'district'), (410825, 4108, '温县', 'district'), (410871, 4108, '焦作城乡一体化示范区', 'district'), (410882, 4108, '沁阳市', 'district'), (410883, 4108, '孟州市', 'district'), (410902, 4109, '华龙区', 'district'), (410922, 4109, '清丰县', 'district'), (410923, 4109, '南乐县', 'district'), (410926, 4109, '范县', 'district'), (410927, 4109, '台前县', 'district'), (410928, 4109, '濮阳县', 'district'), (410971, 4109, '河南濮阳工业园区', 'district'), (410972, 4109, '濮阳经济技术开发区', 'district'), (411002, 4110, '魏都区', 'district'), (411003, 4110, '建安区', 'district'), (411024, 4110, '鄢陵县', 'district'), (411025, 4110, '襄城县', 'district'), (411071, 4110, '许昌经济技术开发区', 'district'), (411081, 4110, '禹州市', 'district'), (411082, 4110, '长葛市', 'district'), (411102, 4111, '源汇区', 'district'), (411103, 4111, '郾城区', 'district'), (411104, 4111, '召陵区', 'district'), (411121, 4111, '舞阳县', 'district'), (411122, 4111, '临颍县', 'district'), (411171, 4111, '漯河经济技术开发区', 'district'), (411202, 4112, '湖滨区', 'district'), (411203, 4112, '陕州区', 'district'), (411221, 4112, '渑池县', 'district'), (411224, 4112, '卢氏县', 'district'), (411271, 4112, '河南三门峡经济开发区', 'district'), (411281, 4112, '义马市', 'district'), (411282, 4112, '灵宝市', 'district'), (411302, 4113, '宛城区', 'district'), (411303, 4113, '卧龙区', 'district'), (411321, 4113, '南召县', 'district'), (411322, 4113, '方城县', 'district'), (411323, 4113, '西峡县', 'district'), (411324, 4113, '镇平县', 'district'), (411325, 4113, '内乡县', 'district'), (411326, 4113, '淅川县', 'district'), (411327, 4113, '社旗县', 'district'), (411328, 4113, '唐河县', 'district'), (411329, 4113, '新野县', 'district'), (411330, 4113, '桐柏县', 'district'), (411371, 4113, '南阳高新技术产业开发区', 'district'), (411372, 4113, '南阳市城乡一体化示范区', 'district'), (411381, 4113, '邓州市', 'district'), (411402, 4114, '梁园区', 'district'), (411403, 4114, '睢阳区', 'district'), (411421, 4114, '民权县', 'district'), (411422, 4114, '睢县', 'district'), (411423, 4114, '宁陵县', 'district'), (411424, 4114, '柘城县', 'district'), (411425, 4114, '虞城县', 'district'), (411426, 4114, '夏邑县', 'district'), (411471, 4114, '豫东综合物流产业聚集区', 'district'), (411472, 4114, '河南商丘经济开发区', 'district'), (411481, 4114, '永城市', 'district'), (411502, 4115, '浉河区', 'district'), (411503, 4115, '平桥区', 'district'), (411521, 4115, '罗山县', 'district'), (411522, 4115, '光山县', 'district'), (411523, 4115, '新县', 'district'), (411524, 4115, '商城县', 'district'), (411525, 4115, '固始县', 'district'), (411526, 4115, '潢川县', 'district'), (411527, 4115, '淮滨县', 'district'), (411528, 4115, '息县', 'district'), (411571, 4115, '信阳高新技术产业开发区', 'district'), (411602, 4116, '川汇区', 'district'), (411603, 4116, '淮阳区', 'district'), (411621, 4116, '扶沟县', 'district'), (411622, 4116, '西华县', 'district'), (411623, 4116, '商水县', 'district'), (411624, 4116, '沈丘县', 'district'), (411625, 4116, '郸城县', 'district'), (411627, 4116, '太康县', 'district'), (411628, 4116, '鹿邑县', 'district'), (411671, 4116, '河南周口经济开发区', 'district'), (411681, 4116, '项城市', 'district'), (411702, 4117, '驿城区', 'district'), (411721, 4117, '西平县', 'district'), (411722, 4117, '上蔡县', 'district'), (411723, 4117, '平舆县', 'district'), (411724, 4117, '正阳县', 'district'), (411725, 4117, '确山县', 'district'), (411726, 4117, '泌阳县', 'district'), (411727, 4117, '汝南县', 'district'), (411728, 4117, '遂平县', 'district'), (411729, 4117, '新蔡县', 'district'), (411771, 4117, '河南驻马店经济开发区', 'district'), (419001, 4190, '济源市', 'district'), (420102, 4201, '江岸区', 'district'), (420103, 4201, '江汉区', 'district'), (420104, 4201, '硚口区', 'district'), (420105, 4201, '汉阳区', 'district'), (420106, 4201, '武昌区', 'district'), (420107, 4201, '青山区', 'district'), (420111, 4201, '洪山区', 'district'), (420112, 4201, '东西湖区', 'district'), (420113, 4201, '汉南区', 'district'), (420114, 4201, '蔡甸区', 'district'), (420115, 4201, '江夏区', 'district'), (420116, 4201, '黄陂区', 'district'), (420117, 4201, '新洲区', 'district'), (420202, 4202, '黄石港区', 'district'), (420203, 4202, '西塞山区', 'district'), (420204, 4202, '下陆区', 'district'), (420205, 4202, '铁山区', 'district'), (420222, 4202, '阳新县', 'district'), (420281, 4202, '大冶市', 'district'), (420302, 4203, '茅箭区', 'district'), (420303, 4203, '张湾区', 'district'), (420304, 4203, '郧阳区', 'district'), (420322, 4203, '郧西县', 'district'), (420323, 4203, '竹山县', 'district'), (420324, 4203, '竹溪县', 'district'), (420325, 4203, '房县', 'district'), (420381, 4203, '丹江口市', 'district'), (420502, 4205, '西陵区', 'district'), (420503, 4205, '伍家岗区', 'district'), (420504, 4205, '点军区', 'district'), (420505, 4205, '猇亭区', 'district'), (420506, 4205, '夷陵区', 'district'), (420525, 4205, '远安县', 'district'), (420526, 4205, '兴山县', 'district'), (420527, 4205, '秭归县', 'district'), (420528, 4205, '长阳土家族自治县', 'district'), (420529, 4205, '五峰土家族自治县', 'district'), (420581, 4205, '宜都市', 'district'), (420582, 4205, '当阳市', 'district'), (420583, 4205, '枝江市', 'district'), (420602, 4206, '襄城区', 'district'), (420606, 4206, '樊城区', 'district'), (420607, 4206, '襄州区', 'district'), (420624, 4206, '南漳县', 'district'), (420625, 4206, '谷城县', 'district'), (420626, 4206, '保康县', 'district'), (420682, 4206, '老河口市', 'district'), (420683, 4206, '枣阳市', 'district'), (420684, 4206, '宜城市', 'district'), (420702, 4207, '梁子湖区', 'district'), (420703, 4207, '华容区', 'district'), (420704, 4207, '鄂城区', 'district'), (420802, 4208, '东宝区', 'district'), (420804, 4208, '掇刀区', 'district'), (420822, 4208, '沙洋县', 'district'), (420881, 4208, '钟祥市', 'district'), (420882, 4208, '京山市', 'district'), (420902, 4209, '孝南区', 'district'), (420921, 4209, '孝昌县', 'district'), (420922, 4209, '大悟县', 'district'), (420923, 4209, '云梦县', 'district'), (420981, 4209, '应城市', 'district'), (420982, 4209, '安陆市', 'district'), (420984, 4209, '汉川市', 'district'), (421002, 4210, '沙市区', 'district'), (421003, 4210, '荆州区', 'district'), (421022, 4210, '公安县', 'district'), (421024, 4210, '江陵县', 'district'), (421071, 4210, '荆州经济技术开发区', 'district'), (421081, 4210, '石首市', 'district'), (421083, 4210, '洪湖市', 'district'), (421087, 4210, '松滋市', 'district'), (421088, 4210, '监利市', 'district'), (421102, 4211, '黄州区', 'district'), (421121, 4211, '团风县', 'district'), (421122, 4211, '红安县', 'district'), (421123, 4211, '罗田县', 'district'), (421124, 4211, '英山县', 'district'), (421125, 4211, '浠水县', 'district'), (421126, 4211, '蕲春县', 'district'), (421127, 4211, '黄梅县', 'district'), (421171, 4211, '龙感湖管理区', 'district'), (421181, 4211, '麻城市', 'district'), (421182, 4211, '武穴市', 'district'), (421202, 4212, '咸安区', 'district'), (421221, 4212, '嘉鱼县', 'district'), (421222, 4212, '通城县', 'district'), (421223, 4212, '崇阳县', 'district'), (421224, 4212, '通山县', 'district'), (421281, 4212, '赤壁市', 'district'), (421303, 4213, '曾都区', 'district'), (421321, 4213, '随县', 'district'), (421381, 4213, '广水市', 'district'), (422801, 4228, '恩施市', 'district'), (422802, 4228, '利川市', 'district'), (422822, 4228, '建始县', 'district'), (422823, 4228, '巴东县', 'district'), (422825, 4228, '宣恩县', 'district'), (422826, 4228, '咸丰县', 'district'), (422827, 4228, '来凤县', 'district'), (422828, 4228, '鹤峰县', 'district'), (429004, 4290, '仙桃市', 'district'), (429005, 4290, '潜江市', 'district'), (429006, 4290, '天门市', 'district'), (429021, 4290, '神农架林区', 'district'), (430102, 4301, '芙蓉区', 'district'), (430103, 4301, '天心区', 'district'), (430104, 4301, '岳麓区', 'district'), (430105, 4301, '开福区', 'district'), (430111, 4301, '雨花区', 'district'), (430112, 4301, '望城区', 'district'), (430121, 4301, '长沙县', 'district'), (430181, 4301, '浏阳市', 'district'), (430182, 4301, '宁乡市', 'district'), (430202, 4302, '荷塘区', 'district'), (430203, 4302, '芦淞区', 'district'), (430204, 4302, '石峰区', 'district'), (430211, 4302, '天元区', 'district'), (430212, 4302, '渌口区', 'district'), (430223, 4302, '攸县', 'district'), (430224, 4302, '茶陵县', 'district'), (430225, 4302, '炎陵县', 'district'), (430271, 4302, '云龙示范区', 'district'), (430281, 4302, '醴陵市', 'district'), (430302, 4303, '雨湖区', 'district'), (430304, 4303, '岳塘区', 'district'), (430321, 4303, '湘潭县', 'district'), (430371, 4303, '湖南湘潭高新技术产业园区', 'district'), (430372, 4303, '湘潭昭山示范区', 'district'), (430373, 4303, '湘潭九华示范区', 'district'), (430381, 4303, '湘乡市', 'district'), (430382, 4303, '韶山市', 'district'), (430405, 4304, '珠晖区', 'district'), (430406, 4304, '雁峰区', 'district'), (430407, 4304, '石鼓区', 'district'), (430408, 4304, '蒸湘区', 'district'), (430412, 4304, '南岳区', 'district'), (430421, 4304, '衡阳县', 'district'), (430422, 4304, '衡南县', 'district'), (430423, 4304, '衡山县', 'district'), (430424, 4304, '衡东县', 'district'), (430426, 4304, '祁东县', 'district'), (430471, 4304, '衡阳综合保税区', 'district'), (430472, 4304, '湖南衡阳高新技术产业园区', 'district'), (430473, 4304, '湖南衡阳松木经济开发区', 'district'), (430481, 4304, '耒阳市', 'district'), (430482, 4304, '常宁市', 'district'), (430502, 4305, '双清区', 'district'), (430503, 4305, '大祥区', 'district'), (430511, 4305, '北塔区', 'district'), (430522, 4305, '新邵县', 'district'), (430523, 4305, '邵阳县', 'district'), (430524, 4305, '隆回县', 'district'), (430525, 4305, '洞口县', 'district'), (430527, 4305, '绥宁县', 'district'), (430528, 4305, '新宁县', 'district'), (430529, 4305, '城步苗族自治县', 'district'), (430581, 4305, '武冈市', 'district'), (430582, 4305, '邵东市', 'district'), (430602, 4306, '岳阳楼区', 'district'), (430603, 4306, '云溪区', 'district'), (430611, 4306, '君山区', 'district'), (430621, 4306, '岳阳县', 'district'), (430623, 4306, '华容县', 'district'), (430624, 4306, '湘阴县', 'district'), (430626, 4306, '平江县', 'district'), (430671, 4306, '岳阳市屈原管理区', 'district'), (430681, 4306, '汨罗市', 'district'), (430682, 4306, '临湘市', 'district'), (430702, 4307, '武陵区', 'district'), (430703, 4307, '鼎城区', 'district'), (430721, 4307, '安乡县', 'district'), (430722, 4307, '汉寿县', 'district'), (430723, 4307, '澧县', 'district'), (430724, 4307, '临澧县', 'district'), (430725, 4307, '桃源县', 'district'), (430726, 4307, '石门县', 'district'), (430771, 4307, '常德市西洞庭管理区', 'district'), (430781, 4307, '津市市', 'district'), (430802, 4308, '永定区', 'district'), (430811, 4308, '武陵源区', 'district'), (430821, 4308, '慈利县', 'district'), (430822, 4308, '桑植县', 'district'), (430902, 4309, '资阳区', 'district'), (430903, 4309, '赫山区', 'district'), (430921, 4309, '南县', 'district'), (430922, 4309, '桃江县', 'district'), (430923, 4309, '安化县', 'district'), (430971, 4309, '益阳市大通湖管理区', 'district'), (430972, 4309, '湖南益阳高新技术产业园区', 'district'), (430981, 4309, '沅江市', 'district'), (431002, 4310, '北湖区', 'district'), (431003, 4310, '苏仙区', 'district'), (431021, 4310, '桂阳县', 'district'), (431022, 4310, '宜章县', 'district'), (431023, 4310, '永兴县', 'district'), (431024, 4310, '嘉禾县', 'district'), (431025, 4310, '临武县', 'district'), (431026, 4310, '汝城县', 'district'), (431027, 4310, '桂东县', 'district'), (431028, 4310, '安仁县', 'district'), (431081, 4310, '资兴市', 'district'), (431102, 4311, '零陵区', 'district'), (431103, 4311, '冷水滩区', 'district'), (431122, 4311, '东安县', 'district'), (431123, 4311, '双牌县', 'district'), (431124, 4311, '道县', 'district'), (431125, 4311, '江永县', 'district'), (431126, 4311, '宁远县', 'district'), (431127, 4311, '蓝山县', 'district'), (431128, 4311, '新田县', 'district'), (431129, 4311, '江华瑶族自治县', 'district'), (431171, 4311, '永州经济技术开发区', 'district'), (431173, 4311, '永州市回龙圩管理区', 'district'), (431181, 4311, '祁阳市', 'district'), (431202, 4312, '鹤城区', 'district'), (431221, 4312, '中方县', 'district'), (431222, 4312, '沅陵县', 'district'), (431223, 4312, '辰溪县', 'district'), (431224, 4312, '溆浦县', 'district'), (431225, 4312, '会同县', 'district'), (431226, 4312, '麻阳苗族自治县', 'district'), (431227, 4312, '新晃侗族自治县', 'district'), (431228, 4312, '芷江侗族自治县', 'district'), (431229, 4312, '靖州苗族侗族自治县', 'district'), (431230, 4312, '通道侗族自治县', 'district'), (431271, 4312, '怀化市洪江管理区', 'district'), (431281, 4312, '洪江市', 'district'), (431302, 4313, '娄星区', 'district'), (431321, 4313, '双峰县', 'district'), (431322, 4313, '新化县', 'district'), (431381, 4313, '冷水江市', 'district'), (431382, 4313, '涟源市', 'district'), (433101, 4331, '吉首市', 'district'), (433122, 4331, '泸溪县', 'district'), (433123, 4331, '凤凰县', 'district'), (433124, 4331, '花垣县', 'district'), (433125, 4331, '保靖县', 'district'), (433126, 4331, '古丈县', 'district'), (433127, 4331, '永顺县', 'district'), (433130, 4331, '龙山县', 'district'), (440103, 4401, '荔湾区', 'district'), (440104, 4401, '越秀区', 'district'), (440105, 4401, '海珠区', 'district'), (440106, 4401, '天河区', 'district'), (440111, 4401, '白云区', 'district'), (440112, 4401, '黄埔区', 'district'), (440113, 4401, '番禺区', 'district'), (440114, 4401, '花都区', 'district'), (440115, 4401, '南沙区', 'district'), (440117, 4401, '从化区', 'district'), (440118, 4401, '增城区', 'district'), (440203, 4402, '武江区', 'district'), (440204, 4402, '浈江区', 'district'), (440205, 4402, '曲江区', 'district'), (440222, 4402, '始兴县', 'district'), (440224, 4402, '仁化县', 'district'), (440229, 4402, '翁源县', 'district'), (440232, 4402, '乳源瑶族自治县', 'district'), (440233, 4402, '新丰县', 'district'), (440281, 4402, '乐昌市', 'district'), (440282, 4402, '南雄市', 'district'), (440303, 4403, '罗湖区', 'district'), (440304, 4403, '福田区', 'district'), (440305, 4403, '南山区', 'district'), (440306, 4403, '宝安区', 'district'), (440307, 4403, '龙岗区', 'district'), (440308, 4403, '盐田区', 'district'), (440309, 4403, '龙华区', 'district'), (440310, 4403, '坪山区', 'district'), (440311, 4403, '光明区', 'district'), (440402, 4404, '香洲区', 'district'), (440403, 4404, '斗门区', 'district'), (440404, 4404, '金湾区', 'district'), (440507, 4405, '龙湖区', 'district'), (440511, 4405, '金平区', 'district'), (440512, 4405, '濠江区', 'district'), (440513, 4405, '潮阳区', 'district'), (440514, 4405, '潮南区', 'district'), (440515, 4405, '澄海区', 'district'), (440523, 4405, '南澳县', 'district'), (440604, 4406, '禅城区', 'district'), (440605, 4406, '南海区', 'district'), (440606, 4406, '顺德区', 'district'), (440607, 4406, '三水区', 'district'), (440608, 4406, '高明区', 'district'), (440703, 4407, '蓬江区', 'district'), (440704, 4407, '江海区', 'district'), (440705, 4407, '新会区', 'district'), (440781, 4407, '台山市', 'district'), (440783, 4407, '开平市', 'district'), (440784, 4407, '鹤山市', 'district'), (440785, 4407, '恩平市', 'district'), (440802, 4408, '赤坎区', 'district'), (440803, 4408, '霞山区', 'district'), (440804, 4408, '坡头区', 'district'), (440811, 4408, '麻章区', 'district'), (440823, 4408, '遂溪县', 'district'), (440825, 4408, '徐闻县', 'district'), (440881, 4408, '廉江市', 'district'), (440882, 4408, '雷州市', 'district'), (440883, 4408, '吴川市', 'district'), (440902, 4409, '茂南区', 'district'), (440904, 4409, '电白区', 'district'), (440981, 4409, '高州市', 'district'), (440982, 4409, '化州市', 'district'), (440983, 4409, '信宜市', 'district'), (441202, 4412, '端州区', 'district'), (441203, 4412, '鼎湖区', 'district'), (441204, 4412, '高要区', 'district'), (441223, 4412, '广宁县', 'district'), (441224, 4412, '怀集县', 'district'), (441225, 4412, '封开县', 'district'), (441226, 4412, '德庆县', 'district'), (441284, 4412, '四会市', 'district'), (441302, 4413, '惠城区', 'district'), (441303, 4413, '惠阳区', 'district'), (441322, 4413, '博罗县', 'district'), (441323, 4413, '惠东县', 'district'), (441324, 4413, '龙门县', 'district'), (441402, 4414, '梅江区', 'district'), (441403, 4414, '梅县区', 'district'), (441422, 4414, '大埔县', 'district'), (441423, 4414, '丰顺县', 'district'), (441424, 4414, '五华县', 'district'), (441426, 4414, '平远县', 'district'), (441427, 4414, '蕉岭县', 'district'), (441481, 4414, '兴宁市', 'district'), (441502, 4415, '城区', 'district'), (441521, 4415, '海丰县', 'district'), (441523, 4415, '陆河县', 'district'), (441581, 4415, '陆丰市', 'district'), (441602, 4416, '源城区', 'district'), (441621, 4416, '紫金县', 'district'), (441622, 4416, '龙川县', 'district'), (441623, 4416, '连平县', 'district'), (441624, 4416, '和平县', 'district'), (441625, 4416, '东源县', 'district'), (441702, 4417, '江城区', 'district'), (441704, 4417, '阳东区', 'district'), (441721, 4417, '阳西县', 'district'), (441781, 4417, '阳春市', 'district'), (441802, 4418, '清城区', 'district'), (441803, 4418, '清新区', 'district'), (441821, 4418, '佛冈县', 'district'), (441823, 4418, '阳山县', 'district'), (441825, 4418, '连山壮族瑶族自治县', 'district'), (441826, 4418, '连南瑶族自治县', 'district'), (441881, 4418, '英德市', 'district'), (441882, 4418, '连州市', 'district'), (441900, 4419, '东莞市', 'district'), (442000, 4420, '中山市', 'district'), (445102, 4451, '湘桥区', 'district'), (445103, 4451, '潮安区', 'district'), (445122, 4451, '饶平县', 'district'), (445202, 4452, '榕城区', 'district'), (445203, 4452, '揭东区', 'district'), (445222, 4452, '揭西县', 'district'), (445224, 4452, '惠来县', 'district'), (445281, 4452, '普宁市', 'district'), (445302, 4453, '云城区', 'district'), (445303, 4453, '云安区', 'district'), (445321, 4453, '新兴县', 'district'), (445322, 4453, '郁南县', 'district'), (445381, 4453, '罗定市', 'district'), (450102, 4501, '兴宁区', 'district'), (450103, 4501, '青秀区', 'district'), (450105, 4501, '江南区', 'district'), (450107, 4501, '西乡塘区', 'district'), (450108, 4501, '良庆区', 'district'), (450109, 4501, '邕宁区', 'district'), (450110, 4501, '武鸣区', 'district'), (450123, 4501, '隆安县', 'district'), (450124, 4501, '马山县', 'district'), (450125, 4501, '上林县', 'district'), (450126, 4501, '宾阳县', 'district'), (450181, 4501, '横州市', 'district'), (450202, 4502, '城中区', 'district'), (450203, 4502, '鱼峰区', 'district'), (450204, 4502, '柳南区', 'district'), (450205, 4502, '柳北区', 'district'), (450206, 4502, '柳江区', 'district'), (450222, 4502, '柳城县', 'district'), (450223, 4502, '鹿寨县', 'district'), (450224, 4502, '融安县', 'district'), (450225, 4502, '融水苗族自治县', 'district'), (450226, 4502, '三江侗族自治县', 'district'), (450302, 4503, '秀峰区', 'district'), (450303, 4503, '叠彩区', 'district'), (450304, 4503, '象山区', 'district'), (450305, 4503, '七星区', 'district'), (450311, 4503, '雁山区', 'district'), (450312, 4503, '临桂区', 'district'), (450321, 4503, '阳朔县', 'district'), (450323, 4503, '灵川县', 'district'), (450324, 4503, '全州县', 'district'), (450325, 4503, '兴安县', 'district'), (450326, 4503, '永福县', 'district'), (450327, 4503, '灌阳县', 'district'), (450328, 4503, '龙胜各族自治县', 'district'), (450329, 4503, '资源县', 'district'), (450330, 4503, '平乐县', 'district'), (450332, 4503, '恭城瑶族自治县', 'district'), (450381, 4503, '荔浦市', 'district'), (450403, 4504, '万秀区', 'district'), (450405, 4504, '长洲区', 'district'), (450406, 4504, '龙圩区', 'district'), (450421, 4504, '苍梧县', 'district'), (450422, 4504, '藤县', 'district'), (450423, 4504, '蒙山县', 'district'), (450481, 4504, '岑溪市', 'district'), (450502, 4505, '海城区', 'district'), (450503, 4505, '银海区', 'district'), (450512, 4505, '铁山港区', 'district'), (450521, 4505, '合浦县', 'district'), (450602, 4506, '港口区', 'district'), (450603, 4506, '防城区', 'district'), (450621, 4506, '上思县', 'district'), (450681, 4506, '东兴市', 'district'), (450702, 4507, '钦南区', 'district'), (450703, 4507, '钦北区', 'district'), (450721, 4507, '灵山县', 'district'), (450722, 4507, '浦北县', 'district'), (450802, 4508, '港北区', 'district'), (450803, 4508, '港南区', 'district'), (450804, 4508, '覃塘区', 'district'), (450821, 4508, '平南县', 'district'), (450881, 4508, '桂平市', 'district'), (450902, 4509, '玉州区', 'district'), (450903, 4509, '福绵区', 'district'), (450921, 4509, '容县', 'district'), (450922, 4509, '陆川县', 'district'), (450923, 4509, '博白县', 'district'), (450924, 4509, '兴业县', 'district'), (450981, 4509, '北流市', 'district'), (451002, 4510, '右江区', 'district'), (451003, 4510, '田阳区', 'district'), (451022, 4510, '田东县', 'district'), (451024, 4510, '德保县', 'district'), (451026, 4510, '那坡县', 'district'), (451027, 4510, '凌云县', 'district'), (451028, 4510, '乐业县', 'district'), (451029, 4510, '田林县', 'district'), (451030, 4510, '西林县', 'district'), (451031, 4510, '隆林各族自治县', 'district'), (451081, 4510, '靖西市', 'district'), (451082, 4510, '平果市', 'district'), (451102, 4511, '八步区', 'district'), (451103, 4511, '平桂区', 'district'), (451121, 4511, '昭平县', 'district'), (451122, 4511, '钟山县', 'district'), (451123, 4511, '富川瑶族自治县', 'district'), (451202, 4512, '金城江区', 'district'), (451203, 4512, '宜州区', 'district'), (451221, 4512, '南丹县', 'district'), (451222, 4512, '天峨县', 'district'), (451223, 4512, '凤山县', 'district'), (451224, 4512, '东兰县', 'district'), (451225, 4512, '罗城仫佬族自治县', 'district'), (451226, 4512, '环江毛南族自治县', 'district'), (451227, 4512, '巴马瑶族自治县', 'district'), (451228, 4512, '都安瑶族自治县', 'district'), (451229, 4512, '大化瑶族自治县', 'district'), (451302, 4513, '兴宾区', 'district'), (451321, 4513, '忻城县', 'district'), (451322, 4513, '象州县', 'district'), (451323, 4513, '武宣县', 'district'), (451324, 4513, '金秀瑶族自治县', 'district'), (451381, 4513, '合山市', 'district'), (451402, 4514, '江州区', 'district'), (451421, 4514, '扶绥县', 'district'), (451422, 4514, '宁明县', 'district'), (451423, 4514, '龙州县', 'district'), (451424, 4514, '大新县', 'district'), (451425, 4514, '天等县', 'district'), (451481, 4514, '凭祥市', 'district'), (460105, 4601, '秀英区', 'district'), (460106, 4601, '龙华区', 'district'), (460107, 4601, '琼山区', 'district'), (460108, 4601, '美兰区', 'district'), (460202, 4602, '海棠区', 'district'), (460203, 4602, '吉阳区', 'district'), (460204, 4602, '天涯区', 'district'), (460205, 4602, '崖州区', 'district'), (460321, 4603, '西沙群岛', 'district'), (460322, 4603, '南沙群岛', 'district'), (460323, 4603, '中沙群岛的岛礁及其海域', 'district'), (460400, 4604, '儋州市', 'district'), (469001, 4690, '五指山市', 'district'), (469002, 4690, '琼海市', 'district'), (469005, 4690, '文昌市', 'district'), (469006, 4690, '万宁市', 'district'), (469007, 4690, '东方市', 'district'), (469021, 4690, '定安县', 'district'), (469022, 4690, '屯昌县', 'district'), (469023, 4690, '澄迈县', 'district'), (469024, 4690, '临高县', 'district'), (469025, 4690, '白沙黎族自治县', 'district'), (469026, 4690, '昌江黎族自治县', 'district'), (469027, 4690, '乐东黎族自治县', 'district'), (469028, 4690, '陵水黎族自治县', 'district'), (469029, 4690, '保亭黎族苗族自治县', 'district'), (469030, 4690, '琼中黎族苗族自治县', 'district'), (500101, 5001, '万州区', 'district'), (500102, 5001, '涪陵区', 'district'), (500103, 5001, '渝中区', 'district'), (500104, 5001, '大渡口区', 'district'), (500105, 5001, '江北区', 'district'), (500106, 5001, '沙坪坝区', 'district'), (500107, 5001, '九龙坡区', 'district'), (500108, 5001, '南岸区', 'district'), (500109, 5001, '北碚区', 'district'), (500110, 5001, '綦江区', 'district'), (500111, 5001, '大足区', 'district'), (500112, 5001, '渝北区', 'district'), (500113, 5001, '巴南区', 'district'), (500114, 5001, '黔江区', 'district'), (500115, 5001, '长寿区', 'district'), (500116, 5001, '江津区', 'district'), (500117, 5001, '合川区', 'district'), (500118, 5001, '永川区', 'district'), (500119, 5001, '南川区', 'district'), (500120, 5001, '璧山区', 'district'), (500151, 5001, '铜梁区', 'district'), (500152, 5001, '潼南区', 'district'), (500153, 5001, '荣昌区', 'district'), (500154, 5001, '开州区', 'district'), (500155, 5001, '梁平区', 'district'), (500156, 5001, '武隆区', 'district'), (500229, 5002, '城口县', 'district'), (500230, 5002, '丰都县', 'district'), (500231, 5002, '垫江县', 'district'), (500233, 5002, '忠县', 'district'), (500235, 5002, '云阳县', 'district'), (500236, 5002, '奉节县', 'district'), (500237, 5002, '巫山县', 'district'), (500238, 5002, '巫溪县', 'district'), (500240, 5002, '石柱土家族自治县', 'district'), (500241, 5002, '秀山土家族苗族自治县', 'district'), (500242, 5002, '酉阳土家族苗族自治县', 'district'), (500243, 5002, '彭水苗族土家族自治县', 'district'), (510104, 5101, '锦江区', 'district'), (510105, 5101, '青羊区', 'district'), (510106, 5101, '金牛区', 'district'), (510107, 5101, '武侯区', 'district'), (510108, 5101, '成华区', 'district'), (510112, 5101, '龙泉驿区', 'district'), (510113, 5101, '青白江区', 'district'), (510114, 5101, '新都区', 'district'), (510115, 5101, '温江区', 'district'), (510116, 5101, '双流区', 'district'), (510117, 5101, '郫都区', 'district'), (510118, 5101, '新津区', 'district'), (510121, 5101, '金堂县', 'district'), (510129, 5101, '大邑县', 'district'), (510131, 5101, '蒲江县', 'district'), (510181, 5101, '都江堰市', 'district'), (510182, 5101, '彭州市', 'district'), (510183, 5101, '邛崃市', 'district'), (510184, 5101, '崇州市', 'district'), (510185, 5101, '简阳市', 'district'), (510302, 5103, '自流井区', 'district'), (510303, 5103, '贡井区', 'district'), (510304, 5103, '大安区', 'district'), (510311, 5103, '沿滩区', 'district'), (510321, 5103, '荣县', 'district'), (510322, 5103, '富顺县', 'district'), (510402, 5104, '东区', 'district'), (510403, 5104, '西区', 'district'), (510411, 5104, '仁和区', 'district'), (510421, 5104, '米易县', 'district'), (510422, 5104, '盐边县', 'district'), (510502, 5105, '江阳区', 'district'), (510503, 5105, '纳溪区', 'district'), (510504, 5105, '龙马潭区', 'district'), (510521, 5105, '泸县', 'district'), (510522, 5105, '合江县', 'district'), (510524, 5105, '叙永县', 'district'), (510525, 5105, '古蔺县', 'district'), (510603, 5106, '旌阳区', 'district'), (510604, 5106, '罗江区', 'district'), (510623, 5106, '中江县', 'district'), (510681, 5106, '广汉市', 'district'), (510682, 5106, '什邡市', 'district'), (510683, 5106, '绵竹市', 'district'), (510703, 5107, '涪城区', 'district'), (510704, 5107, '游仙区', 'district'), (510705, 5107, '安州区', 'district'), (510722, 5107, '三台县', 'district'), (510723, 5107, '盐亭县', 'district'), (510725, 5107, '梓潼县', 'district'), (510726, 5107, '北川羌族自治县', 'district'), (510727, 5107, '平武县', 'district'), (510781, 5107, '江油市', 'district'), (510802, 5108, '利州区', 'district'), (510811, 5108, '昭化区', 'district'), (510812, 5108, '朝天区', 'district'), (510821, 5108, '旺苍县', 'district'), (510822, 5108, '青川县', 'district'), (510823, 5108, '剑阁县', 'district'), (510824, 5108, '苍溪县', 'district'), (510903, 5109, '船山区', 'district'), (510904, 5109, '安居区', 'district'), (510921, 5109, '蓬溪县', 'district'), (510923, 5109, '大英县', 'district'), (510981, 5109, '射洪市', 'district'), (511002, 5110, '市中区', 'district'), (511011, 5110, '东兴区', 'district'), (511024, 5110, '威远县', 'district'), (511025, 5110, '资中县', 'district'), (511071, 5110, '内江经济开发区', 'district'), (511083, 5110, '隆昌市', 'district'), (511102, 5111, '市中区', 'district'), (511111, 5111, '沙湾区', 'district'), (511112, 5111, '五通桥区', 'district'), (511113, 5111, '金口河区', 'district'), (511123, 5111, '犍为县', 'district'), (511124, 5111, '井研县', 'district'), (511126, 5111, '夹江县', 'district'), (511129, 5111, '沐川县', 'district'), (511132, 5111, '峨边彝族自治县', 'district'), (511133, 5111, '马边彝族自治县', 'district'), (511181, 5111, '峨眉山市', 'district'), (511302, 5113, '顺庆区', 'district'), (511303, 5113, '高坪区', 'district'), (511304, 5113, '嘉陵区', 'district'), (511321, 5113, '南部县', 'district'), (511322, 5113, '营山县', 'district'), (511323, 5113, '蓬安县', 'district'), (511324, 5113, '仪陇县', 'district'), (511325, 5113, '西充县', 'district'), (511381, 5113, '阆中市', 'district'), (511402, 5114, '东坡区', 'district'), (511403, 5114, '彭山区', 'district'), (511421, 5114, '仁寿县', 'district'), (511423, 5114, '洪雅县', 'district'), (511424, 5114, '丹棱县', 'district'), (511425, 5114, '青神县', 'district'), (511502, 5115, '翠屏区', 'district'), (511503, 5115, '南溪区', 'district'), (511504, 5115, '叙州区', 'district'), (511523, 5115, '江安县', 'district'), (511524, 5115, '长宁县', 'district'), (511525, 5115, '高县', 'district'), (511526, 5115, '珙县', 'district'), (511527, 5115, '筠连县', 'district'), (511528, 5115, '兴文县', 'district'), (511529, 5115, '屏山县', 'district'), (511602, 5116, '广安区', 'district'), (511603, 5116, '前锋区', 'district'), (511621, 5116, '岳池县', 'district'), (511622, 5116, '武胜县', 'district'), (511623, 5116, '邻水县', 'district'), (511681, 5116, '华蓥市', 'district'), (511702, 5117, '通川区', 'district'), (511703, 5117, '达川区', 'district'), (511722, 5117, '宣汉县', 'district'), (511723, 5117, '开江县', 'district'), (511724, 5117, '大竹县', 'district'), (511725, 5117, '渠县', 'district'), (511771, 5117, '达州经济开发区', 'district'), (511781, 5117, '万源市', 'district'), (511802, 5118, '雨城区', 'district'), (511803, 5118, '名山区', 'district'), (511822, 5118, '荥经县', 'district'), (511823, 5118, '汉源县', 'district'), (511824, 5118, '石棉县', 'district'), (511825, 5118, '天全县', 'district'), (511826, 5118, '芦山县', 'district'), (511827, 5118, '宝兴县', 'district'), (511902, 5119, '巴州区', 'district'), (511903, 5119, '恩阳区', 'district'), (511921, 5119, '通江县', 'district'), (511922, 5119, '南江县', 'district'), (511923, 5119, '平昌县', 'district'), (511971, 5119, '巴中经济开发区', 'district'), (512002, 5120, '雁江区', 'district'), (512021, 5120, '安岳县', 'district'), (512022, 5120, '乐至县', 'district'), (513201, 5132, '马尔康市', 'district'), (513221, 5132, '汶川县', 'district'), (513222, 5132, '理县', 'district'), (513223, 5132, '茂县', 'district'), (513224, 5132, '松潘县', 'district'), (513225, 5132, '九寨沟县', 'district'), (513226, 5132, '金川县', 'district'), (513227, 5132, '小金县', 'district'), (513228, 5132, '黑水县', 'district'), (513230, 5132, '壤塘县', 'district'), (513231, 5132, '阿坝县', 'district'), (513232, 5132, '若尔盖县', 'district'), (513233, 5132, '红原县', 'district'), (513301, 5133, '康定市', 'district'), (513322, 5133, '泸定县', 'district'), (513323, 5133, '丹巴县', 'district'), (513324, 5133, '九龙县', 'district'), (513325, 5133, '雅江县', 'district'), (513326, 5133, '道孚县', 'district'), (513327, 5133, '炉霍县', 'district'), (513328, 5133, '甘孜县', 'district'), (513329, 5133, '新龙县', 'district'), (513330, 5133, '德格县', 'district'), (513331, 5133, '白玉县', 'district'), (513332, 5133, '石渠县', 'district'), (513333, 5133, '色达县', 'district'), (513334, 5133, '理塘县', 'district'), (513335, 5133, '巴塘县', 'district'), (513336, 5133, '乡城县', 'district'), (513337, 5133, '稻城县', 'district'), (513338, 5133, '得荣县', 'district'), (513401, 5134, '西昌市', 'district'), (513402, 5134, '会理市', 'district'), (513422, 5134, '木里藏族自治县', 'district'), (513423, 5134, '盐源县', 'district'), (513424, 5134, '德昌县', 'district'), (513426, 5134, '会东县', 'district'), (513427, 5134, '宁南县', 'district'), (513428, 5134, '普格县', 'district'), (513429, 5134, '布拖县', 'district'), (513430, 5134, '金阳县', 'district'), (513431, 5134, '昭觉县', 'district'), (513432, 5134, '喜德县', 'district'), (513433, 5134, '冕宁县', 'district'), (513434, 5134, '越西县', 'district'), (513435, 5134, '甘洛县', 'district'), (513436, 5134, '美姑县', 'district'), (513437, 5134, '雷波县', 'district'), (520102, 5201, '南明区', 'district'), (520103, 5201, '云岩区', 'district'), (520111, 5201, '花溪区', 'district'), (520112, 5201, '乌当区', 'district'), (520113, 5201, '白云区', 'district'), (520115, 5201, '观山湖区', 'district'), (520121, 5201, '开阳县', 'district'), (520122, 5201, '息烽县', 'district'), (520123, 5201, '修文县', 'district'), (520181, 5201, '清镇市', 'district'), (520201, 5202, '钟山区', 'district'), (520203, 5202, '六枝特区', 'district'), (520204, 5202, '水城区', 'district'), (520281, 5202, '盘州市', 'district'), (520302, 5203, '红花岗区', 'district'), (520303, 5203, '汇川区', 'district'), (520304, 5203, '播州区', 'district'), (520322, 5203, '桐梓县', 'district'), (520323, 5203, '绥阳县', 'district'), (520324, 5203, '正安县', 'district'), (520325, 5203, '道真仡佬族苗族自治县', 'district'), (520326, 5203, '务川仡佬族苗族自治县', 'district'), (520327, 5203, '凤冈县', 'district'), (520328, 5203, '湄潭县', 'district'), (520329, 5203, '余庆县', 'district'), (520330, 5203, '习水县', 'district'), (520381, 5203, '赤水市', 'district'), (520382, 5203, '仁怀市', 'district'), (520402, 5204, '西秀区', 'district'), (520403, 5204, '平坝区', 'district'), (520422, 5204, '普定县', 'district'), (520423, 5204, '镇宁布依族苗族自治县', 'district'), (520424, 5204, '关岭布依族苗族自治县', 'district'), (520425, 5204, '紫云苗族布依族自治县', 'district'), (520502, 5205, '七星关区', 'district'), (520521, 5205, '大方县', 'district'), (520523, 5205, '金沙县', 'district'), (520524, 5205, '织金县', 'district'), (520525, 5205, '纳雍县', 'district'), (520526, 5205, '威宁彝族回族苗族自治县', 'district'), (520527, 5205, '赫章县', 'district'), (520581, 5205, '黔西市', 'district'), (520602, 5206, '碧江区', 'district'), (520603, 5206, '万山区', 'district'), (520621, 5206, '江口县', 'district'), (520622, 5206, '玉屏侗族自治县', 'district'), (520623, 5206, '石阡县', 'district'), (520624, 5206, '思南县', 'district'), (520625, 5206, '印江土家族苗族自治县', 'district'), (520626, 5206, '德江县', 'district'), (520627, 5206, '沿河土家族自治县', 'district'), (520628, 5206, '松桃苗族自治县', 'district'), (522301, 5223, '兴义市', 'district'), (522302, 5223, '兴仁市', 'district'), (522323, 5223, '普安县', 'district'), (522324, 5223, '晴隆县', 'district'), (522325, 5223, '贞丰县', 'district'), (522326, 5223, '望谟县', 'district'), (522327, 5223, '册亨县', 'district'), (522328, 5223, '安龙县', 'district'), (522601, 5226, '凯里市', 'district'), (522622, 5226, '黄平县', 'district'), (522623, 5226, '施秉县', 'district'), (522624, 5226, '三穗县', 'district'), (522625, 5226, '镇远县', 'district'), (522626, 5226, '岑巩县', 'district'), (522627, 5226, '天柱县', 'district'), (522628, 5226, '锦屏县', 'district'), (522629, 5226, '剑河县', 'district'), (522630, 5226, '台江县', 'district'), (522631, 5226, '黎平县', 'district'), (522632, 5226, '榕江县', 'district'), (522633, 5226, '从江县', 'district'), (522634, 5226, '雷山县', 'district'), (522635, 5226, '麻江县', 'district'), (522636, 5226, '丹寨县', 'district'), (522701, 5227, '都匀市', 'district'), (522702, 5227, '福泉市', 'district'), (522722, 5227, '荔波县', 'district'), (522723, 5227, '贵定县', 'district'), (522725, 5227, '瓮安县', 'district'), (522726, 5227, '独山县', 'district'), (522727, 5227, '平塘县', 'district'), (522728, 5227, '罗甸县', 'district'), (522729, 5227, '长顺县', 'district'), (522730, 5227, '龙里县', 'district'), (522731, 5227, '惠水县', 'district'), (522732, 5227, '三都水族自治县', 'district'), (530102, 5301, '五华区', 'district'), (530103, 5301, '盘龙区', 'district'), (530111, 5301, '官渡区', 'district'), (530112, 5301, '西山区', 'district'), (530113, 5301, '东川区', 'district'), (530114, 5301, '呈贡区', 'district'), (530115, 5301, '晋宁区', 'district'), (530124, 5301, '富民县', 'district'), (530125, 5301, '宜良县', 'district'), (530126, 5301, '石林彝族自治县', 'district'), (530127, 5301, '嵩明县', 'district'), (530128, 5301, '禄劝彝族苗族自治县', 'district'), (530129, 5301, '寻甸回族彝族自治县', 'district'), (530181, 5301, '安宁市', 'district'), (530302, 5303, '麒麟区', 'district'), (530303, 5303, '沾益区', 'district'), (530304, 5303, '马龙区', 'district'), (530322, 5303, '陆良县', 'district'), (530323, 5303, '师宗县', 'district'), (530324, 5303, '罗平县', 'district'), (530325, 5303, '富源县', 'district'), (530326, 5303, '会泽县', 'district'), (530381, 5303, '宣威市', 'district'), (530402, 5304, '红塔区', 'district'), (530403, 5304, '江川区', 'district'), (530423, 5304, '通海县', 'district'), (530424, 5304, '华宁县', 'district'), (530425, 5304, '易门县', 'district'), (530426, 5304, '峨山彝族自治县', 'district'), (530427, 5304, '新平彝族傣族自治县', 'district'), (530428, 5304, '元江哈尼族彝族傣族自治县', 'district'), (530481, 5304, '澄江市', 'district'), (530502, 5305, '隆阳区', 'district'), (530521, 5305, '施甸县', 'district'), (530523, 5305, '龙陵县', 'district'), (530524, 5305, '昌宁县', 'district'), (530581, 5305, '腾冲市', 'district'), (530602, 5306, '昭阳区', 'district'), (530621, 5306, '鲁甸县', 'district'), (530622, 5306, '巧家县', 'district'), (530623, 5306, '盐津县', 'district'), (530624, 5306, '大关县', 'district'), (530625, 5306, '永善县', 'district'), (530626, 5306, '绥江县', 'district'), (530627, 5306, '镇雄县', 'district'), (530628, 5306, '彝良县', 'district'), (530629, 5306, '威信县', 'district'), (530681, 5306, '水富市', 'district'), (530702, 5307, '古城区', 'district'), (530721, 5307, '玉龙纳西族自治县', 'district'), (530722, 5307, '永胜县', 'district'), (530723, 5307, '华坪县', 'district'), (530724, 5307, '宁蒗彝族自治县', 'district'), (530802, 5308, '思茅区', 'district'), (530821, 5308, '宁洱哈尼族彝族自治县', 'district'), (530822, 5308, '墨江哈尼族自治县', 'district'), (530823, 5308, '景东彝族自治县', 'district'), (530824, 5308, '景谷傣族彝族自治县', 'district'), (530825, 5308, '镇沅彝族哈尼族拉祜族自治县', 'district'), (530826, 5308, '江城哈尼族彝族自治县', 'district'), (530827, 5308, '孟连傣族拉祜族佤族自治县', 'district'), (530828, 5308, '澜沧拉祜族自治县', 'district'), (530829, 5308, '西盟佤族自治县', 'district'), (530902, 5309, '临翔区', 'district'), (530921, 5309, '凤庆县', 'district'), (530922, 5309, '云县', 'district'), (530923, 5309, '永德县', 'district'), (530924, 5309, '镇康县', 'district'), (530925, 5309, '双江拉祜族佤族布朗族傣族自治县', 'district'), (530926, 5309, '耿马傣族佤族自治县', 'district'), (530927, 5309, '沧源佤族自治县', 'district'), (532301, 5323, '楚雄市', 'district'), (532302, 5323, '禄丰市', 'district'), (532322, 5323, '双柏县', 'district'), (532323, 5323, '牟定县', 'district'), (532324, 5323, '南华县', 'district'), (532325, 5323, '姚安县', 'district'), (532326, 5323, '大姚县', 'district'), (532327, 5323, '永仁县', 'district'), (532328, 5323, '元谋县', 'district'), (532329, 5323, '武定县', 'district'), (532501, 5325, '个旧市', 'district'), (532502, 5325, '开远市', 'district'), (532503, 5325, '蒙自市', 'district'), (532504, 5325, '弥勒市', 'district'), (532523, 5325, '屏边苗族自治县', 'district'), (532524, 5325, '建水县', 'district'), (532525, 5325, '石屏县', 'district'), (532527, 5325, '泸西县', 'district'), (532528, 5325, '元阳县', 'district'), (532529, 5325, '红河县', 'district'), (532530, 5325, '金平苗族瑶族傣族自治县', 'district'), (532531, 5325, '绿春县', 'district'), (532532, 5325, '河口瑶族自治县', 'district'), (532601, 5326, '文山市', 'district'), (532622, 5326, '砚山县', 'district'), (532623, 5326, '西畴县', 'district'), (532624, 5326, '麻栗坡县', 'district'), (532625, 5326, '马关县', 'district'), (532626, 5326, '丘北县', 'district'), (532627, 5326, '广南县', 'district'), (532628, 5326, '富宁县', 'district'), (532801, 5328, '景洪市', 'district'), (532822, 5328, '勐海县', 'district'), (532823, 5328, '勐腊县', 'district'), (532901, 5329, '大理市', 'district'), (532922, 5329, '漾濞彝族自治县', 'district'), (532923, 5329, '祥云县', 'district'), (532924, 5329, '宾川县', 'district'), (532925, 5329, '弥渡县', 'district'), (532926, 5329, '南涧彝族自治县', 'district'), (532927, 5329, '巍山彝族回族自治县', 'district'), (532928, 5329, '永平县', 'district'), (532929, 5329, '云龙县', 'district'), (532930, 5329, '洱源县', 'district'), (532931, 5329, '剑川县', 'district'), (532932, 5329, '鹤庆县', 'district'), (533102, 5331, '瑞丽市', 'district'), (533103, 5331, '芒市', 'district'), (533122, 5331, '梁河县', 'district'), (533123, 5331, '盈江县', 'district'), (533124, 5331, '陇川县', 'district'), (533301, 5333, '泸水市', 'district'), (533323, 5333, '福贡县', 'district'), (533324, 5333, '贡山独龙族怒族自治县', 'district'), (533325, 5333, '兰坪白族普米族自治县', 'district'), (533401, 5334, '香格里拉市', 'district'), (533422, 5334, '德钦县', 'district'), (533423, 5334, '维西傈僳族自治县', 'district'), (540102, 5401, '城关区', 'district'), (540103, 5401, '堆龙德庆区', 'district'), (540104, 5401, '达孜区', 'district'), (540121, 5401, '林周县', 'district'), (540122, 5401, '当雄县', 'district'), (540123, 5401, '尼木县', 'district'), (540124, 5401, '曲水县', 'district'), (540127, 5401, '墨竹工卡县', 'district'), (540171, 5401, '格尔木藏青工业园区', 'district'), (540172, 5401, '拉萨经济技术开发区', 'district'), (540173, 5401, '西藏文化旅游创意园区', 'district'), (540174, 5401, '达孜工业园区', 'district'), (540202, 5402, '桑珠孜区', 'district'), (540221, 5402, '南木林县', 'district'), (540222, 5402, '江孜县', 'district'), (540223, 5402, '定日县', 'district'), (540224, 5402, '萨迦县', 'district'), (540225, 5402, '拉孜县', 'district'), (540226, 5402, '昂仁县', 'district'), (540227, 5402, '谢通门县', 'district'), (540228, 5402, '白朗县', 'district'), (540229, 5402, '仁布县', 'district'), (540230, 5402, '康马县', 'district'), (540231, 5402, '定结县', 'district'), (540232, 5402, '仲巴县', 'district'), (540233, 5402, '亚东县', 'district'), (540234, 5402, '吉隆县', 'district'), (540235, 5402, '聂拉木县', 'district'), (540236, 5402, '萨嘎县', 'district'), (540237, 5402, '岗巴县', 'district'), (540302, 5403, '卡若区', 'district'), (540321, 5403, '江达县', 'district'), (540322, 5403, '贡觉县', 'district'), (540323, 5403, '类乌齐县', 'district'), (540324, 5403, '丁青县', 'district'), (540325, 5403, '察雅县', 'district'), (540326, 5403, '八宿县', 'district'), (540327, 5403, '左贡县', 'district'), (540328, 5403, '芒康县', 'district'), (540329, 5403, '洛隆县', 'district'), (540330, 5403, '边坝县', 'district'), (540402, 5404, '巴宜区', 'district'), (540421, 5404, '工布江达县', 'district'), (540422, 5404, '米林县', 'district'), (540423, 5404, '墨脱县', 'district'), (540424, 5404, '波密县', 'district'), (540425, 5404, '察隅县', 'district'), (540426, 5404, '朗县', 'district'), (540502, 5405, '乃东区', 'district'), (540521, 5405, '扎囊县', 'district'), (540522, 5405, '贡嘎县', 'district'), (540523, 5405, '桑日县', 'district'), (540524, 5405, '琼结县', 'district'), (540525, 5405, '曲松县', 'district'), (540526, 5405, '措美县', 'district'), (540527, 5405, '洛扎县', 'district'), (540528, 5405, '加查县', 'district'), (540529, 5405, '隆子县', 'district'), (540530, 5405, '错那县', 'district'), (540531, 5405, '浪卡子县', 'district'), (540602, 5406, '色尼区', 'district'), (540621, 5406, '嘉黎县', 'district'), (540622, 5406, '比如县', 'district'), (540623, 5406, '聂荣县', 'district'), (540624, 5406, '安多县', 'district'), (540625, 5406, '申扎县', 'district'), (540626, 5406, '索县', 'district'), (540627, 5406, '班戈县', 'district'), (540628, 5406, '巴青县', 'district'), (540629, 5406, '尼玛县', 'district'), (540630, 5406, '双湖县', 'district'), (542521, 5425, '普兰县', 'district'), (542522, 5425, '札达县', 'district'), (542523, 5425, '噶尔县', 'district'), (542524, 5425, '日土县', 'district'), (542525, 5425, '革吉县', 'district'), (542526, 5425, '改则县', 'district'), (542527, 5425, '措勤县', 'district'), (610102, 6101, '新城区', 'district'), (610103, 6101, '碑林区', 'district'), (610104, 6101, '莲湖区', 'district'), (610111, 6101, '灞桥区', 'district'), (610112, 6101, '未央区', 'district'), (610113, 6101, '雁塔区', 'district'), (610114, 6101, '阎良区', 'district'), (610115, 6101, '临潼区', 'district'), (610116, 6101, '长安区', 'district'), (610117, 6101, '高陵区', 'district'), (610118, 6101, '鄠邑区', 'district'), (610122, 6101, '蓝田县', 'district'), (610124, 6101, '周至县', 'district'), (610202, 6102, '王益区', 'district'), (610203, 6102, '印台区', 'district'), (610204, 6102, '耀州区', 'district'), (610222, 6102, '宜君县', 'district'), (610302, 6103, '渭滨区', 'district'), (610303, 6103, '金台区', 'district'), (610304, 6103, '陈仓区', 'district'), (610305, 6103, '凤翔区', 'district'), (610323, 6103, '岐山县', 'district'), (610324, 6103, '扶风县', 'district'), (610326, 6103, '眉县', 'district'), (610327, 6103, '陇县', 'district'), (610328, 6103, '千阳县', 'district'), (610329, 6103, '麟游县', 'district'), (610330, 6103, '凤县', 'district'), (610331, 6103, '太白县', 'district'), (610402, 6104, '秦都区', 'district'), (610403, 6104, '杨陵区', 'district'), (610404, 6104, '渭城区', 'district'), (610422, 6104, '三原县', 'district'), (610423, 6104, '泾阳县', 'district'), (610424, 6104, '乾县', 'district'), (610425, 6104, '礼泉县', 'district'), (610426, 6104, '永寿县', 'district'), (610428, 6104, '长武县', 'district'), (610429, 6104, '旬邑县', 'district'), (610430, 6104, '淳化县', 'district'), (610431, 6104, '武功县', 'district'), (610481, 6104, '兴平市', 'district'), (610482, 6104, '彬州市', 'district'), (610502, 6105, '临渭区', 'district'), (610503, 6105, '华州区', 'district'), (610522, 6105, '潼关县', 'district'), (610523, 6105, '大荔县', 'district'), (610524, 6105, '合阳县', 'district'), (610525, 6105, '澄城县', 'district'), (610526, 6105, '蒲城县', 'district'), (610527, 6105, '白水县', 'district'), (610528, 6105, '富平县', 'district'), (610581, 6105, '韩城市', 'district'), (610582, 6105, '华阴市', 'district'), (610602, 6106, '宝塔区', 'district'), (610603, 6106, '安塞区', 'district'), (610621, 6106, '延长县', 'district'), (610622, 6106, '延川县', 'district'), (610625, 6106, '志丹县', 'district'), (610626, 6106, '吴起县', 'district'), (610627, 6106, '甘泉县', 'district'), (610628, 6106, '富县', 'district'), (610629, 6106, '洛川县', 'district'), (610630, 6106, '宜川县', 'district'), (610631, 6106, '黄龙县', 'district'), (610632, 6106, '黄陵县', 'district'), (610681, 6106, '子长市', 'district'), (610702, 6107, '汉台区', 'district'), (610703, 6107, '南郑区', 'district'), (610722, 6107, '城固县', 'district'), (610723, 6107, '洋县', 'district'), (610724, 6107, '西乡县', 'district'), (610725, 6107, '勉县', 'district'), (610726, 6107, '宁强县', 'district'), (610727, 6107, '略阳县', 'district'), (610728, 6107, '镇巴县', 'district'), (610729, 6107, '留坝县', 'district'), (610730, 6107, '佛坪县', 'district'), (610802, 6108, '榆阳区', 'district'), (610803, 6108, '横山区', 'district'), (610822, 6108, '府谷县', 'district'), (610824, 6108, '靖边县', 'district'), (610825, 6108, '定边县', 'district'), (610826, 6108, '绥德县', 'district'), (610827, 6108, '米脂县', 'district'), (610828, 6108, '佳县', 'district'), (610829, 6108, '吴堡县', 'district'), (610830, 6108, '清涧县', 'district'), (610831, 6108, '子洲县', 'district'), (610881, 6108, '神木市', 'district'), (610902, 6109, '汉滨区', 'district'), (610921, 6109, '汉阴县', 'district'), (610922, 6109, '石泉县', 'district'), (610923, 6109, '宁陕县', 'district'), (610924, 6109, '紫阳县', 'district'), (610925, 6109, '岚皋县', 'district'), (610926, 6109, '平利县', 'district'), (610927, 6109, '镇坪县', 'district'), (610929, 6109, '白河县', 'district'), (610981, 6109, '旬阳市', 'district');
INSERT INTO `__PREFIX__shopro_data_area` (`id`, `pid`, `name`, `level`) VALUES (611002, 6110, '商州区', 'district'), (611021, 6110, '洛南县', 'district'), (611022, 6110, '丹凤县', 'district'), (611023, 6110, '商南县', 'district'), (611024, 6110, '山阳县', 'district'), (611025, 6110, '镇安县', 'district'), (611026, 6110, '柞水县', 'district'), (620102, 6201, '城关区', 'district'), (620103, 6201, '七里河区', 'district'), (620104, 6201, '西固区', 'district'), (620105, 6201, '安宁区', 'district'), (620111, 6201, '红古区', 'district'), (620121, 6201, '永登县', 'district'), (620122, 6201, '皋兰县', 'district'), (620123, 6201, '榆中县', 'district'), (620171, 6201, '兰州新区', 'district'), (620201, 6202, '嘉峪关市', 'district'), (620302, 6203, '金川区', 'district'), (620321, 6203, '永昌县', 'district'), (620402, 6204, '白银区', 'district'), (620403, 6204, '平川区', 'district'), (620421, 6204, '靖远县', 'district'), (620422, 6204, '会宁县', 'district'), (620423, 6204, '景泰县', 'district'), (620502, 6205, '秦州区', 'district'), (620503, 6205, '麦积区', 'district'), (620521, 6205, '清水县', 'district'), (620522, 6205, '秦安县', 'district'), (620523, 6205, '甘谷县', 'district'), (620524, 6205, '武山县', 'district'), (620525, 6205, '张家川回族自治县', 'district'), (620602, 6206, '凉州区', 'district'), (620621, 6206, '民勤县', 'district'), (620622, 6206, '古浪县', 'district'), (620623, 6206, '天祝藏族自治县', 'district'), (620702, 6207, '甘州区', 'district'), (620721, 6207, '肃南裕固族自治县', 'district'), (620722, 6207, '民乐县', 'district'), (620723, 6207, '临泽县', 'district'), (620724, 6207, '高台县', 'district'), (620725, 6207, '山丹县', 'district'), (620802, 6208, '崆峒区', 'district'), (620821, 6208, '泾川县', 'district'), (620822, 6208, '灵台县', 'district'), (620823, 6208, '崇信县', 'district'), (620825, 6208, '庄浪县', 'district'), (620826, 6208, '静宁县', 'district'), (620881, 6208, '华亭市', 'district'), (620902, 6209, '肃州区', 'district'), (620921, 6209, '金塔县', 'district'), (620922, 6209, '瓜州县', 'district'), (620923, 6209, '肃北蒙古族自治县', 'district'), (620924, 6209, '阿克塞哈萨克族自治县', 'district'), (620981, 6209, '玉门市', 'district'), (620982, 6209, '敦煌市', 'district'), (621002, 6210, '西峰区', 'district'), (621021, 6210, '庆城县', 'district'), (621022, 6210, '环县', 'district'), (621023, 6210, '华池县', 'district'), (621024, 6210, '合水县', 'district'), (621025, 6210, '正宁县', 'district'), (621026, 6210, '宁县', 'district'), (621027, 6210, '镇原县', 'district'), (621102, 6211, '安定区', 'district'), (621121, 6211, '通渭县', 'district'), (621122, 6211, '陇西县', 'district'), (621123, 6211, '渭源县', 'district'), (621124, 6211, '临洮县', 'district'), (621125, 6211, '漳县', 'district'), (621126, 6211, '岷县', 'district'), (621202, 6212, '武都区', 'district'), (621221, 6212, '成县', 'district'), (621222, 6212, '文县', 'district'), (621223, 6212, '宕昌县', 'district'), (621224, 6212, '康县', 'district'), (621225, 6212, '西和县', 'district'), (621226, 6212, '礼县', 'district'), (621227, 6212, '徽县', 'district'), (621228, 6212, '两当县', 'district'), (622901, 6229, '临夏市', 'district'), (622921, 6229, '临夏县', 'district'), (622922, 6229, '康乐县', 'district'), (622923, 6229, '永靖县', 'district'), (622924, 6229, '广河县', 'district'), (622925, 6229, '和政县', 'district'), (622926, 6229, '东乡族自治县', 'district'), (622927, 6229, '积石山保安族东乡族撒拉族自治县', 'district'), (623001, 6230, '合作市', 'district'), (623021, 6230, '临潭县', 'district'), (623022, 6230, '卓尼县', 'district'), (623023, 6230, '舟曲县', 'district'), (623024, 6230, '迭部县', 'district'), (623025, 6230, '玛曲县', 'district'), (623026, 6230, '碌曲县', 'district'), (623027, 6230, '夏河县', 'district'), (630102, 6301, '城东区', 'district'), (630103, 6301, '城中区', 'district'), (630104, 6301, '城西区', 'district'), (630105, 6301, '城北区', 'district'), (630106, 6301, '湟中区', 'district'), (630121, 6301, '大通回族土族自治县', 'district'), (630123, 6301, '湟源县', 'district'), (630202, 6302, '乐都区', 'district'), (630203, 6302, '平安区', 'district'), (630222, 6302, '民和回族土族自治县', 'district'), (630223, 6302, '互助土族自治县', 'district'), (630224, 6302, '化隆回族自治县', 'district'), (630225, 6302, '循化撒拉族自治县', 'district'), (632221, 6322, '门源回族自治县', 'district'), (632222, 6322, '祁连县', 'district'), (632223, 6322, '海晏县', 'district'), (632224, 6322, '刚察县', 'district'), (632301, 6323, '同仁市', 'district'), (632322, 6323, '尖扎县', 'district'), (632323, 6323, '泽库县', 'district'), (632324, 6323, '河南蒙古族自治县', 'district'), (632521, 6325, '共和县', 'district'), (632522, 6325, '同德县', 'district'), (632523, 6325, '贵德县', 'district'), (632524, 6325, '兴海县', 'district'), (632525, 6325, '贵南县', 'district'), (632621, 6326, '玛沁县', 'district'), (632622, 6326, '班玛县', 'district'), (632623, 6326, '甘德县', 'district'), (632624, 6326, '达日县', 'district'), (632625, 6326, '久治县', 'district'), (632626, 6326, '玛多县', 'district'), (632701, 6327, '玉树市', 'district'), (632722, 6327, '杂多县', 'district'), (632723, 6327, '称多县', 'district'), (632724, 6327, '治多县', 'district'), (632725, 6327, '囊谦县', 'district'), (632726, 6327, '曲麻莱县', 'district'), (632801, 6328, '格尔木市', 'district'), (632802, 6328, '德令哈市', 'district'), (632803, 6328, '茫崖市', 'district'), (632821, 6328, '乌兰县', 'district'), (632822, 6328, '都兰县', 'district'), (632823, 6328, '天峻县', 'district'), (632857, 6328, '大柴旦行政委员会', 'district'), (640104, 6401, '兴庆区', 'district'), (640105, 6401, '西夏区', 'district'), (640106, 6401, '金凤区', 'district'), (640121, 6401, '永宁县', 'district'), (640122, 6401, '贺兰县', 'district'), (640181, 6401, '灵武市', 'district'), (640202, 6402, '大武口区', 'district'), (640205, 6402, '惠农区', 'district'), (640221, 6402, '平罗县', 'district'), (640302, 6403, '利通区', 'district'), (640303, 6403, '红寺堡区', 'district'), (640323, 6403, '盐池县', 'district'), (640324, 6403, '同心县', 'district'), (640381, 6403, '青铜峡市', 'district'), (640402, 6404, '原州区', 'district'), (640422, 6404, '西吉县', 'district'), (640423, 6404, '隆德县', 'district'), (640424, 6404, '泾源县', 'district'), (640425, 6404, '彭阳县', 'district'), (640502, 6405, '沙坡头区', 'district'), (640521, 6405, '中宁县', 'district'), (640522, 6405, '海原县', 'district'), (650102, 6501, '天山区', 'district'), (650103, 6501, '沙依巴克区', 'district'), (650104, 6501, '新市区', 'district'), (650105, 6501, '水磨沟区', 'district'), (650106, 6501, '头屯河区', 'district'), (650107, 6501, '达坂城区', 'district'), (650109, 6501, '米东区', 'district'), (650121, 6501, '乌鲁木齐县', 'district'), (650202, 6502, '独山子区', 'district'), (650203, 6502, '克拉玛依区', 'district'), (650204, 6502, '白碱滩区', 'district'), (650205, 6502, '乌尔禾区', 'district'), (650402, 6504, '高昌区', 'district'), (650421, 6504, '鄯善县', 'district'), (650422, 6504, '托克逊县', 'district'), (650502, 6505, '伊州区', 'district'), (650521, 6505, '巴里坤哈萨克自治县', 'district'), (650522, 6505, '伊吾县', 'district'), (652301, 6523, '昌吉市', 'district'), (652302, 6523, '阜康市', 'district'), (652323, 6523, '呼图壁县', 'district'), (652324, 6523, '玛纳斯县', 'district'), (652325, 6523, '奇台县', 'district'), (652327, 6523, '吉木萨尔县', 'district'), (652328, 6523, '木垒哈萨克自治县', 'district'), (652701, 6527, '博乐市', 'district'), (652702, 6527, '阿拉山口市', 'district'), (652722, 6527, '精河县', 'district'), (652723, 6527, '温泉县', 'district'), (652801, 6528, '库尔勒市', 'district'), (652822, 6528, '轮台县', 'district'), (652823, 6528, '尉犁县', 'district'), (652824, 6528, '若羌县', 'district'), (652825, 6528, '且末县', 'district'), (652826, 6528, '焉耆回族自治县', 'district'), (652827, 6528, '和静县', 'district'), (652828, 6528, '和硕县', 'district'), (652829, 6528, '博湖县', 'district'), (652871, 6528, '库尔勒经济技术开发区', 'district'), (652901, 6529, '阿克苏市', 'district'), (652902, 6529, '库车市', 'district'), (652922, 6529, '温宿县', 'district'), (652924, 6529, '沙雅县', 'district'), (652925, 6529, '新和县', 'district'), (652926, 6529, '拜城县', 'district'), (652927, 6529, '乌什县', 'district'), (652928, 6529, '阿瓦提县', 'district'), (652929, 6529, '柯坪县', 'district'), (653001, 6530, '阿图什市', 'district'), (653022, 6530, '阿克陶县', 'district'), (653023, 6530, '阿合奇县', 'district'), (653024, 6530, '乌恰县', 'district'), (653101, 6531, '喀什市', 'district'), (653121, 6531, '疏附县', 'district'), (653122, 6531, '疏勒县', 'district'), (653123, 6531, '英吉沙县', 'district'), (653124, 6531, '泽普县', 'district'), (653125, 6531, '莎车县', 'district'), (653126, 6531, '叶城县', 'district'), (653127, 6531, '麦盖提县', 'district'), (653128, 6531, '岳普湖县', 'district'), (653129, 6531, '伽师县', 'district'), (653130, 6531, '巴楚县', 'district'), (653131, 6531, '塔什库尔干塔吉克自治县', 'district'), (653201, 6532, '和田市', 'district'), (653221, 6532, '和田县', 'district'), (653222, 6532, '墨玉县', 'district'), (653223, 6532, '皮山县', 'district'), (653224, 6532, '洛浦县', 'district'), (653225, 6532, '策勒县', 'district'), (653226, 6532, '于田县', 'district'), (653227, 6532, '民丰县', 'district'), (654002, 6540, '伊宁市', 'district'), (654003, 6540, '奎屯市', 'district'), (654004, 6540, '霍尔果斯市', 'district'), (654021, 6540, '伊宁县', 'district'), (654022, 6540, '察布查尔锡伯自治县', 'district'), (654023, 6540, '霍城县', 'district'), (654024, 6540, '巩留县', 'district'), (654025, 6540, '新源县', 'district'), (654026, 6540, '昭苏县', 'district'), (654027, 6540, '特克斯县', 'district'), (654028, 6540, '尼勒克县', 'district'), (654201, 6542, '塔城市', 'district'), (654202, 6542, '乌苏市', 'district'), (654203, 6542, '沙湾市', 'district'), (654221, 6542, '额敏县', 'district'), (654224, 6542, '托里县', 'district'), (654225, 6542, '裕民县', 'district'), (654226, 6542, '和布克赛尔蒙古自治县', 'district'), (654301, 6543, '阿勒泰市', 'district'), (654321, 6543, '布尔津县', 'district'), (654322, 6543, '富蕴县', 'district'), (654323, 6543, '福海县', 'district'), (654324, 6543, '哈巴河县', 'district'), (654325, 6543, '青河县', 'district'), (654326, 6543, '吉木乃县', 'district'), (659001, 6590, '石河子市', 'district'), (659002, 6590, '阿拉尔市', 'district'), (659003, 6590, '图木舒克市', 'district'), (659004, 6590, '五家渠市', 'district'), (659005, 6590, '北屯市', 'district'), (659006, 6590, '铁门关市', 'district'), (659007, 6590, '双河市', 'district'), (659008, 6590, '可克达拉市', 'district'), (659009, 6590, '昆玉市', 'district'), (659010, 6590, '胡杨河市', 'district'), (659011, 6590, '新星市', 'district'), (711001, 7110, '松山区', 'district'), (711002, 7110, '大安区', 'district'), (711003, 7110, '中正区', 'district'), (711005, 7110, '万华区', 'district'), (711009, 7110, '大同区', 'district'), (711010, 7110, '中山区', 'district'), (711011, 7110, '文山区', 'district'), (711013, 7110, '南港区', 'district'), (711014, 7110, '内湖区', 'district'), (711015, 7110, '士林区', 'district'), (711016, 7110, '北投区', 'district'), (711017, 7110, '信义区', 'district'), (711101, 7111, '中区', 'district'), (711102, 7111, '东区', 'district'), (711103, 7111, '西区', 'district'), (711104, 7111, '南区', 'district'), (711105, 7111, '北区', 'district'), (711106, 7111, '西屯区', 'district'), (711107, 7111, '南屯区', 'district'), (711108, 7111, '北屯区', 'district'), (711141, 7111, '丰原区', 'district'), (711142, 7111, '东势区', 'district'), (711143, 7111, '大甲区', 'district'), (711144, 7111, '清水区', 'district'), (711145, 7111, '沙鹿区', 'district'), (711146, 7111, '梧栖区', 'district'), (711147, 7111, '神冈区', 'district'), (711148, 7111, '后里区', 'district'), (711149, 7111, '大雅区', 'district'), (711150, 7111, '潭子区', 'district'), (711151, 7111, '新社区', 'district'), (711152, 7111, '石冈区', 'district'), (711153, 7111, '外埔区', 'district'), (711154, 7111, '大安区', 'district'), (711155, 7111, '乌日区', 'district'), (711156, 7111, '大肚区', 'district'), (711157, 7111, '龙井区', 'district'), (711158, 7111, '雾峰区', 'district'), (711159, 7111, '太平区', 'district'), (711160, 7111, '大里区', 'district'), (711161, 7111, '和平区', 'district'), (711201, 7112, '中正区', 'district'), (711202, 7112, '七堵区', 'district'), (711203, 7112, '暖暖区', 'district'), (711204, 7112, '仁爱区', 'district'), (711205, 7112, '中山区', 'district'), (711206, 7112, '安乐区', 'district'), (711207, 7112, '信义区', 'district'), (711301, 7113, '东区', 'district'), (711302, 7113, '南区', 'district'), (711304, 7113, '北区', 'district'), (711306, 7113, '安南区', 'district'), (711307, 7113, '安平区', 'district'), (711308, 7113, '中西区', 'district'), (711341, 7113, '新营区', 'district'), (711342, 7113, '盐水区', 'district'), (711343, 7113, '白河区', 'district'), (711344, 7113, '柳营区', 'district'), (711345, 7113, '后壁区', 'district'), (711346, 7113, '东山区', 'district'), (711347, 7113, '麻豆区', 'district'), (711348, 7113, '下营区', 'district'), (711349, 7113, '六甲区', 'district'), (711350, 7113, '官田区', 'district'), (711351, 7113, '大内区', 'district'), (711352, 7113, '佳里区', 'district'), (711353, 7113, '西港区', 'district'), (711354, 7113, '七股区', 'district'), (711355, 7113, '将军区', 'district'), (711356, 7113, '北门区', 'district'), (711357, 7113, '学甲区', 'district'), (711358, 7113, '新化区', 'district'), (711359, 7113, '善化区', 'district'), (711360, 7113, '新市区', 'district'), (711361, 7113, '安定区', 'district'), (711362, 7113, '山上区', 'district'), (711363, 7113, '玉井区', 'district'), (711364, 7113, '楠西区', 'district'), (711365, 7113, '南化区', 'district'), (711366, 7113, '左镇区', 'district'), (711367, 7113, '仁德区', 'district'), (711368, 7113, '归仁区', 'district'), (711369, 7113, '关庙区', 'district'), (711370, 7113, '龙崎区', 'district'), (711371, 7113, '永康区', 'district'), (711401, 7114, '盐埕区', 'district'), (711402, 7114, '鼓山区', 'district'), (711403, 7114, '左营区', 'district'), (711404, 7114, '楠梓区', 'district'), (711405, 7114, '三民区', 'district'), (711406, 7114, '新兴区', 'district'), (711407, 7114, '前金区', 'district'), (711408, 7114, '苓雅区', 'district'), (711409, 7114, '前镇区', 'district'), (711410, 7114, '旗津区', 'district'), (711411, 7114, '小港区', 'district'), (711441, 7114, '凤山区', 'district'), (711442, 7114, '鸟松区', 'district'), (711443, 7114, '仁武区', 'district'), (711444, 7114, '大社区', 'district'), (711445, 7114, '大树区', 'district'), (711446, 7114, '大寮区', 'district'), (711448, 7114, '林园区', 'district'), (711449, 7114, '冈山区', 'district'), (711450, 7114, '茄萣区', 'district'), (711451, 7114, '永安区', 'district'), (711452, 7114, '桥头区', 'district'), (711453, 7114, '梓官区', 'district'), (711454, 7114, '田寮区', 'district'), (711455, 7114, '阿莲区', 'district'), (711456, 7114, '路竹区', 'district'), (711457, 7114, '燕巢区', 'district'), (711458, 7114, '弥陀区', 'district'), (711459, 7114, '湖内区', 'district'), (711460, 7114, '旗山区', 'district'), (711461, 7114, '六龟区', 'district'), (711462, 7114, '内门区', 'district'), (711463, 7114, '美浓区', 'district'), (711464, 7114, '杉林区', 'district'), (711465, 7114, '甲仙区', 'district'), (711466, 7114, '茂林区', 'district'), (711467, 7114, '桃源区', 'district'), (711468, 7114, '那玛夏区', 'district'), (711501, 7115, '新庄区', 'district'), (711502, 7115, '林口区', 'district'), (711503, 7115, '五股区', 'district'), (711504, 7115, '芦洲区', 'district'), (711505, 7115, '三重区', 'district'), (711506, 7115, '泰山区', 'district'), (711507, 7115, '新店区', 'district'), (711508, 7115, '石碇区', 'district'), (711509, 7115, '深坑区', 'district'), (711510, 7115, '坪林区', 'district'), (711511, 7115, '乌来区', 'district'), (711514, 7115, '板桥区', 'district'), (711515, 7115, '三峡区', 'district'), (711516, 7115, '莺歌区', 'district'), (711517, 7115, '树林区', 'district'), (711518, 7115, '中和区', 'district'), (711519, 7115, '土城区', 'district'), (711521, 7115, '瑞芳区', 'district'), (711522, 7115, '平溪区', 'district'), (711523, 7115, '双溪区', 'district'), (711524, 7115, '贡寮区', 'district'), (711525, 7115, '金山区', 'district'), (711526, 7115, '万里区', 'district'), (711527, 7115, '淡水区', 'district'), (711528, 7115, '汐止区', 'district'), (711530, 7115, '三芝区', 'district'), (711531, 7115, '石门区', 'district'), (711532, 7115, '八里区', 'district'), (711533, 7115, '永和区', 'district'), (711601, 7116, '宜兰市', 'district'), (711602, 7116, '头城镇', 'district'), (711603, 7116, '礁溪乡', 'district'), (711604, 7116, '壮围乡', 'district'), (711605, 7116, '员山乡', 'district'), (711606, 7116, '罗东镇', 'district'), (711607, 7116, '五结乡', 'district'), (711608, 7116, '冬山乡', 'district'), (711609, 7116, '苏澳镇', 'district'), (711610, 7116, '三星乡', 'district'), (711611, 7116, '大同乡', 'district'), (711612, 7116, '南澳乡', 'district'), (711701, 7117, '桃园区', 'district'), (711702, 7117, '大溪区', 'district'), (711703, 7117, '中坜区', 'district'), (711704, 7117, '杨梅区', 'district'), (711705, 7117, '芦竹区', 'district'), (711706, 7117, '大园区', 'district'), (711707, 7117, '龟山区', 'district'), (711708, 7117, '八德区', 'district'), (711709, 7117, '龙潭区', 'district'), (711710, 7117, '平镇区', 'district'), (711711, 7117, '新屋区', 'district'), (711712, 7117, '观音区', 'district'), (711713, 7117, '复兴区', 'district'), (711801, 7118, '东区', 'district'), (711802, 7118, '西区', 'district'), (711902, 7119, '竹东镇', 'district'), (711903, 7119, '关西镇', 'district'), (711904, 7119, '新埔镇', 'district'), (711905, 7119, '竹北市', 'district'), (711906, 7119, '湖口乡', 'district'), (711908, 7119, '横山乡', 'district'), (711909, 7119, '新丰乡', 'district'), (711910, 7119, '芎林乡', 'district'), (711911, 7119, '宝山乡', 'district'), (711912, 7119, '北埔乡', 'district'), (711913, 7119, '峨眉乡', 'district'), (711914, 7119, '尖石乡', 'district'), (711915, 7119, '五峰乡', 'district'), (712001, 7120, '苗栗市', 'district'), (712002, 7120, '苑里镇', 'district'), (712003, 7120, '通霄镇', 'district'), (712004, 7120, '公馆乡', 'district'), (712005, 7120, '铜锣乡', 'district'), (712006, 7120, '三义乡', 'district'), (712007, 7120, '西湖乡', 'district'), (712008, 7120, '头屋乡', 'district'), (712009, 7120, '竹南镇', 'district'), (712010, 7120, '头份市', 'district'), (712011, 7120, '造桥乡', 'district'), (712012, 7120, '后龙镇', 'district'), (712013, 7120, '三湾乡', 'district'), (712014, 7120, '南庄乡', 'district'), (712015, 7120, '大湖乡', 'district'), (712016, 7120, '卓兰镇', 'district'), (712017, 7120, '狮潭乡', 'district'), (712018, 7120, '泰安乡', 'district'), (712201, 7122, '南投市', 'district'), (712202, 7122, '埔里镇', 'district'), (712203, 7122, '草屯镇', 'district'), (712204, 7122, '竹山镇', 'district'), (712205, 7122, '集集镇', 'district'), (712206, 7122, '名间乡', 'district'), (712207, 7122, '鹿谷乡', 'district'), (712208, 7122, '中寮乡', 'district'), (712209, 7122, '鱼池乡', 'district'), (712210, 7122, '国姓乡', 'district'), (712211, 7122, '水里乡', 'district'), (712212, 7122, '信义乡', 'district'), (712213, 7122, '仁爱乡', 'district'), (712301, 7123, '彰化市', 'district'), (712302, 7123, '鹿港镇', 'district'), (712303, 7123, '和美镇', 'district'), (712304, 7123, '北斗镇', 'district'), (712305, 7123, '员林市', 'district'), (712306, 7123, '溪湖镇', 'district'), (712307, 7123, '田中镇', 'district'), (712308, 7123, '二林镇', 'district'), (712309, 7123, '线西乡', 'district'), (712310, 7123, '伸港乡', 'district'), (712311, 7123, '福兴乡', 'district'), (712312, 7123, '秀水乡', 'district'), (712313, 7123, '花坛乡', 'district'), (712314, 7123, '芬园乡', 'district'), (712315, 7123, '大村乡', 'district'), (712316, 7123, '埔盐乡', 'district'), (712317, 7123, '埔心乡', 'district'), (712318, 7123, '永靖乡', 'district'), (712319, 7123, '社头乡', 'district'), (712320, 7123, '二水乡', 'district'), (712321, 7123, '田尾乡', 'district'), (712322, 7123, '埤头乡', 'district'), (712323, 7123, '芳苑乡', 'district'), (712324, 7123, '大城乡', 'district'), (712325, 7123, '竹塘乡', 'district'), (712326, 7123, '溪州乡', 'district'), (712401, 7124, '东区', 'district'), (712402, 7124, '香山区', 'district'), (712403, 7124, '北区', 'district'), (712501, 7125, '斗六市', 'district'), (712502, 7125, '斗南镇', 'district'), (712503, 7125, '虎尾镇', 'district'), (712504, 7125, '西螺镇', 'district'), (712505, 7125, '土库镇', 'district'), (712506, 7125, '北港镇', 'district'), (712507, 7125, '古坑乡', 'district'), (712508, 7125, '大埤乡', 'district'), (712509, 7125, '莿桐乡', 'district'), (712510, 7125, '林内乡', 'district'), (712511, 7125, '二仑乡', 'district'), (712512, 7125, '仑背乡', 'district'), (712513, 7125, '麦寮乡', 'district'), (712514, 7125, '东势乡', 'district'), (712515, 7125, '褒忠乡', 'district'), (712516, 7125, '台西乡', 'district'), (712517, 7125, '元长乡', 'district'), (712518, 7125, '四湖乡', 'district'), (712519, 7125, '口湖乡', 'district'), (712520, 7125, '水林乡', 'district'), (712602, 7126, '朴子市', 'district'), (712603, 7126, '布袋镇', 'district'), (712604, 7126, '大林镇', 'district'), (712605, 7126, '民雄乡', 'district'), (712606, 7126, '溪口乡', 'district'), (712607, 7126, '新港乡', 'district'), (712608, 7126, '六脚乡', 'district'), (712609, 7126, '东石乡', 'district'), (712610, 7126, '义竹乡', 'district'), (712611, 7126, '鹿草乡', 'district'), (712612, 7126, '太保市', 'district'), (712613, 7126, '水上乡', 'district'), (712614, 7126, '中埔乡', 'district'), (712615, 7126, '竹崎乡', 'district'), (712616, 7126, '梅山乡', 'district'), (712617, 7126, '番路乡', 'district'), (712618, 7126, '大埔乡', 'district'), (712620, 7126, '阿里山乡', 'district'), (712901, 7129, '屏东市', 'district'), (712902, 7129, '潮州镇', 'district'), (712903, 7129, '东港镇', 'district'), (712904, 7129, '恒春镇', 'district'), (712905, 7129, '万丹乡', 'district'), (712906, 7129, '长治乡', 'district'), (712907, 7129, '麟洛乡', 'district'), (712908, 7129, '九如乡', 'district'), (712909, 7129, '里港乡', 'district'), (712910, 7129, '盐埔乡', 'district'), (712911, 7129, '高树乡', 'district'), (712912, 7129, '万峦乡', 'district'), (712913, 7129, '内埔乡', 'district'), (712914, 7129, '竹田乡', 'district'), (712915, 7129, '新埤乡', 'district'), (712916, 7129, '枋寮乡', 'district'), (712917, 7129, '新园乡', 'district'), (712918, 7129, '崁顶乡', 'district'), (712919, 7129, '林边乡', 'district'), (712920, 7129, '南州乡', 'district'), (712921, 7129, '佳冬乡', 'district'), (712922, 7129, '琉球乡', 'district'), (712923, 7129, '车城乡', 'district'), (712924, 7129, '满州乡', 'district'), (712925, 7129, '枋山乡', 'district'), (712926, 7129, '三地门乡', 'district'), (712927, 7129, '雾台乡', 'district'), (712928, 7129, '玛家乡', 'district'), (712929, 7129, '泰武乡', 'district'), (712930, 7129, '来义乡', 'district'), (712931, 7129, '春日乡', 'district'), (712932, 7129, '狮子乡', 'district'), (712933, 7129, '牡丹乡', 'district'), (713001, 7130, '花莲市', 'district'), (713002, 7130, '光复乡', 'district'), (713003, 7130, '玉里镇', 'district'), (713004, 7130, '新城乡', 'district'), (713005, 7130, '吉安乡', 'district'), (713006, 7130, '寿丰乡', 'district'), (713007, 7130, '凤林镇', 'district'), (713008, 7130, '丰滨乡', 'district'), (713009, 7130, '瑞穗乡', 'district'), (713010, 7130, '富里乡', 'district'), (713011, 7130, '卓溪乡', 'district'), (713012, 7130, '万荣乡', 'district'), (713013, 7130, '秀林乡', 'district'), (713101, 7131, '台东市', 'district'), (713102, 7131, '成功镇', 'district'), (713103, 7131, '关山镇', 'district'), (713104, 7131, '卑南乡', 'district'), (713105, 7131, '大武乡', 'district'), (713106, 7131, '太麻里乡', 'district'), (713107, 7131, '东河乡', 'district'), (713108, 7131, '长滨乡', 'district'), (713109, 7131, '鹿野乡', 'district'), (713110, 7131, '池上乡', 'district'), (713111, 7131, '绿岛乡', 'district'), (713112, 7131, '延平乡', 'district'), (713113, 7131, '海端乡', 'district'), (713114, 7131, '达仁乡', 'district'), (713115, 7131, '金峰乡', 'district'), (713116, 7131, '兰屿乡', 'district'), (713201, 7132, '马公市', 'district'), (713202, 7132, '湖西乡', 'district'), (713203, 7132, '白沙乡', 'district'), (713204, 7132, '西屿乡', 'district'), (713205, 7132, '望安乡', 'district'), (713206, 7132, '七美乡', 'district');

CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_data_express`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '快递公司',
  `code` varchar(60) NULL DEFAULT NULL COMMENT '公司编号',
  `weigh` int(8) NOT NULL DEFAULT 0 COMMENT '权重',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `code`(`code`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '快递公司';
-- shopro_data_express 数据
INSERT INTO `__PREFIX__shopro_data_express` (`id`, `name`, `code`, `weigh`) VALUES (1, '顺丰速运', 'SF', 0), (2, '百世快递', 'HTKY', 0), (3, '中通快递', 'ZTO', 0), (4, '申通快递', 'STO', 0), (5, '圆通速递', 'YTO', 0), (6, '韵达速递', 'YD', 0), (7, '邮政快递包裹', 'YZPY', 0), (8, 'EMS', 'EMS', 0), (9, '天天快递', 'HHTT', 0), (10, '京东快递', 'JD', 0), (11, '优速快递', 'UC', 0), (12, '德邦快递', 'DBL', 0), (13, '宅急送', 'ZJS', 0), (14, '安捷快递', 'AJ', 0), (15, '阿里跨境电商物流', 'ALKJWL', 0), (16, '安迅物流', 'AX', 0), (17, '安邮美国', 'AYUS', 0), (18, '亚马逊物流', 'AMAZON', 0), (19, '澳门邮政', 'AOMENYZ', 0), (20, '安能物流', 'ANE', 0), (21, '澳多多', 'ADD', 0), (22, '澳邮专线', 'AYCA', 0), (23, '安鲜达', 'AXD', 0), (24, '安能快运', 'ANEKY', 0), (25, '澳邦国际', 'ABGJ', 0), (26, '安得物流', 'ANNTO', 0), (27, '八达通  ', 'BDT', 0), (28, '百腾物流', 'BETWL', 0), (29, '北极星快运', 'BJXKY', 0), (30, '奔腾物流', 'BNTWL', 0), (31, '百福东方', 'BFDF', 0), (32, '贝海国际 ', 'BHGJ', 0), (33, '八方安运', 'BFAY', 0), (34, '百世快运', 'BTWL', 0), (35, '帮帮发转运', 'BBFZY', 0), (36, '百城通物流', 'BCTWL', 0), (37, '春风物流', 'CFWL', 0), (38, '诚通物流', 'CHTWL', 0), (39, '传喜物流', 'CXHY', 0), (40, '城市100', 'CITY100', 0), (41, '城际快递', 'CJKD', 0), (42, 'CNPEX中邮快递', 'CNPEX', 0), (43, 'COE东方快递', 'COE', 0), (44, '长沙创一', 'CSCY', 0), (45, '成都善途速运', 'CDSTKY', 0), (46, '联合运通', 'CTG', 0), (47, '疯狂快递', 'CRAZY', 0), (48, 'CBO钏博物流', 'CBO', 0), (49, '佳吉快运', 'CNEX', 0), (50, '承诺达', 'CND', 0), (51, '畅顺通达', 'CSTD', 0), (52, 'D速物流', 'DSWL', 0), (53, '到了港', 'DLG ', 0), (54, '大田物流', 'DTWL', 0), (55, '东骏快捷物流', 'DJKJWL', 0), (56, '德坤', 'DEKUN', 0), (57, '德邦快运', 'DBLKY', 0), (58, '大马鹿', 'DML', 0), (59, '丹鸟物流', 'DNWL', 0), (60, '东方汇', 'EST365', 0), (61, 'E特快', 'ETK', 0), (62, 'EMS国内', 'EMS2', 0), (63, 'EWE', 'EWE', 0), (64, '飞康达', 'FKD', 0), (65, '富腾达  ', 'FTD', 0), (66, '凡宇货的', 'FYKD', 0), (67, '速派快递', 'FASTGO', 0), (68, '飞豹快递', 'FBKD', 0), (69, '丰巢', 'FBOX', 0), (70, '飞狐快递', 'FHKD', 0), (71, '复融供应链', 'FRGYL', 0), (72, '飞远配送', 'FYPS', 0), (73, '凡宇速递', 'FYSD', 0), (74, '丰通快运', 'FT', 0), (75, '冠达   ', 'GD', 0), (76, '广东邮政', 'GDEMS', 0), (77, '共速达', 'GSD', 0), (78, '广通       ', 'GTONG', 0), (79, '冠达快递', 'GDKD', 0), (80, '挂号信', 'GHX', 0), (81, '广通速递', 'GTKD', 0), (82, '高铁快运', 'GTKY', 0), (83, '迦递快递', 'GAI', 0), (84, '港快速递', 'GKSD', 0), (85, '高铁速递', 'GTSD', 0), (86, '黑狗冷链', 'HGLL', 0), (87, '恒路物流', 'HLWL', 0), (88, '天地华宇', 'HOAU', 0), (89, '鸿桥供应链', 'HOTSCM', 0), (90, '海派通物流公司', 'HPTEX', 0), (91, '华强物流', 'hq568', 0), (92, '环球速运  ', 'HQSY', 0), (93, '华夏龙物流', 'HXLWL', 0), (94, '河北建华', 'HBJH', 0), (95, '汇丰物流', 'HF', 0), (96, '华航快递', 'HHKD', 0), (97, '华翰物流', 'HHWL', 0), (98, '黄马甲快递', 'HMJKD', 0), (99, '海盟速递', 'HMSD', 0), (100, '华企快运', 'HQKY', 0), (101, '昊盛物流', 'HSWL', 0), (102, '鸿泰物流', 'HTWL', 0), (103, '豪翔物流 ', 'HXWL', 0), (104, '合肥汇文', 'HFHW', 0), (105, '辉隆物流', 'HLONGWL', 0), (106, '华企快递', 'HQKD', 0), (107, '韩润物流', 'HRWL', 0), (108, '青岛恒通快递', 'HTKD', 0), (109, '货运皇物流', 'HYH', 0), (110, '好来运快递', 'HLYSD', 0), (111, '皇家物流', 'HJWL', 0), (112, '海信物流', 'HISENSE', 0), (113, '捷安达  ', 'JAD', 0), (114, '京广速递', 'JGSD', 0), (115, '九曳供应链', 'JIUYE', 0), (116, '急先达', 'JXD', 0), (117, '晋越快递', 'JYKD', 0), (118, '佳成国际', 'JCEX', 0), (119, '捷特快递', 'JTKD', 0), (120, '精英速运', 'JYSY', 0), (121, '加运美', 'JYM', 0), (122, '景光物流', 'JGWL', 0), (123, '佳怡物流', 'JYWL', 0), (124, '京东快运', 'JDKY', 0), (125, '金大物流', 'JDWL', 0), (126, '极兔速递', 'JTSD', 0), (127, '跨越速运', 'KYSY', 0), (128, '快服务', 'KFW', 0), (129, '快速递物流', 'KSDWL', 0), (130, '康力物流', 'KLWL', 0), (131, '快淘快递', 'KTKD', 0), (132, '快优达速递', 'KYDSD', 0), (133, '跨越物流', 'KYWL', 0), (134, '快8速运', 'KBSY', 0), (135, '龙邦快递', 'LB', 0), (136, '蓝弧快递', 'LHKD', 0), (137, '乐捷递', 'LJD', 0), (138, '立即送', 'LJS', 0), (139, '联昊通速递', 'LHT', 0), (140, '民邦快递', 'MB', 0), (141, '民航快递', 'MHKD', 0), (142, '美快    ', 'MK', 0), (143, '门对门快递', 'MDM', 0), (144, '迈达', 'MD', 0), (145, '闽盛快递', 'MSKD', 0), (146, '迈隆递运', 'MRDY', 0), (147, '明亮物流', 'MLWL', 0), (148, '南方传媒物流', 'NFCM', 0), (149, '南京晟邦物流', 'NJSBWL', 0), (150, '能达速递', 'NEDA', 0), (151, '平安达腾飞快递', 'PADTF', 0), (152, '泛捷快递', 'PANEX', 0), (153, '品骏快递', 'PJ', 0), (154, '陪行物流', 'PXWL', 0), (155, 'PCA Express', 'PCA', 0), (156, '全晨快递', 'QCKD', 0), (157, '全日通快递', 'QRT', 0), (158, '快客快递', 'QUICK', 0), (159, '全信通', 'QXT', 0), (160, '七曜中邮', 'QYZY', 0), (161, '如风达', 'RFD', 0), (162, '荣庆物流', 'RQ', 0), (163, '日日顺物流', 'RRS', 0), (164, '日昱物流', 'RLWL', 0), (165, '瑞丰速递', 'RFEX', 0), (166, '赛澳递', 'SAD', 0), (167, '苏宁物流', 'SNWL', 0), (168, '圣安物流', 'SAWL', 0), (169, '晟邦物流', 'SBWL', 0), (170, '上大物流', 'SDWL', 0), (171, '盛丰物流', 'SFWL', 0), (172, '速通物流', 'ST', 0), (173, '速腾快递', 'STWL', 0), (174, '速必达物流', 'SUBIDA', 0), (175, '速递e站', 'SDEZ', 0), (176, '速呈宅配', 'SCZPDS', 0), (177, '速尔快递', 'SURE', 0), (178, '山东海红', 'SDHH', 0), (179, '顺丰国际', 'SFGJ', 0), (180, '盛辉物流', 'SHWL', 0), (181, '穗佳物流', 'SJWL', 0), (182, '三态速递', 'STSD', 0), (183, '山西红马甲', 'SXHMJ', 0), (184, '世运快递', 'SYKD', 0), (185, '闪送', 'SS', 0), (186, '盛通快递', 'STKD', 0), (187, '郑州速捷', 'SJ', 0), (188, '顺心捷达', 'SX', 0), (189, '商桥物流', 'SQWL', 0), (190, '佳旺达物流', 'SYJWDX', 0), (191, '台湾邮政', 'TAIWANYZ', 0), (192, '唐山申通', 'TSSTO', 0), (193, '特急送', 'TJS', 0), (194, '通用物流', 'TYWL', 0), (195, '华宇物流', 'TDHY', 0), (196, '通和天下', 'THTX', 0), (197, '腾林物流', 'TLWL', 0), (198, '全一快递', 'UAPEX', 0), (199, 'UBI', 'UBI', 0), (200, 'UEQ Express', 'UEQ', 0), (201, '万家康  ', 'WJK', 0), (202, '万家物流', 'WJWL', 0), (203, '武汉同舟行', 'WHTZX', 0), (204, '维普恩', 'WPE', 0), (205, '中粮我买网', 'WM', 0), (206, '万象物流', 'WXWL', 0), (207, '微特派', 'WTP', 0), (208, '温通物流', 'WTWL', 0), (209, '迅驰物流  ', 'XCWL', 0), (210, '信丰物流', 'XFEX', 0), (211, '希优特', 'XYT', 0), (212, '新邦物流', 'XBWL', 0), (213, '祥龙运通', 'XLYT', 0), (214, '新杰物流', 'XJ', 0), (215, '源安达快递', 'YADEX', 0), (216, '远成物流', 'YCWL', 0), (217, '远成快运', 'YCSY', 0), (218, '义达国际物流', 'YDH', 0), (219, '易达通  ', 'YDT', 0), (220, '原飞航物流', 'YFHEX', 0), (221, '亚风快递', 'YFSD', 0), (222, '运通快递', 'YTKD', 0), (223, '亿翔快递', 'YXKD', 0), (224, '运东西网', 'YUNDX', 0), (225, '壹米滴答', 'YMDD', 0), (226, '邮政国内标快', 'YZBK', 0), (227, '一站通速运', 'YZTSY', 0), (228, '驭丰速运', 'YFSUYUN', 0), (229, '余氏东风', 'YSDF', 0), (230, '耀飞快递', 'YF', 0), (231, '韵达快运', 'YDKY', 0), (232, '云路', 'YL', 0), (233, '邮必佳', 'YBJ', 0), (234, '越丰物流', 'YFEX', 0), (235, '银捷速递', 'YJSD', 0), (236, '优联吉运', 'YLJY', 0), (237, '亿领速运', 'YLSY', 0), (238, '英脉物流', 'YMWL', 0), (239, '亿顺航', 'YSH', 0), (240, '音素快运', 'YSKY', 0), (241, '易通达', 'YTD', 0), (242, '一统飞鸿', 'YTFH', 0), (243, '圆通国际', 'YTOGJ', 0), (244, '宇鑫物流', 'YXWL', 0), (245, '包裹/平邮/挂号信', 'YZGN', 0), (246, '一智通', 'YZT', 0), (247, '优拜物流', 'YBWL', 0), (248, '增益快递', 'ZENY', 0), (249, '中睿速递', 'ZRSD', 0), (250, '中铁快运', 'ZTKY', 0), (251, '中天万运', 'ZTWY', 0), (252, '中外运速递', 'ZWYSD', 0), (253, '澳转运', 'ZY_AZY', 0), (254, '八达网', 'ZY_BDA', 0), (255, '贝易购', 'ZY_BYECO', 0), (256, '赤兔马转运', 'ZY_CTM', 0), (257, 'CUL中美速递', 'ZY_CUL', 0), (258, 'ETD', 'ZY_ETD', 0), (259, '风驰快递', 'ZY_FCKD', 0), (260, '风雷速递', 'ZY_FLSD', 0), (261, '皓晨优递', 'ZY_HCYD', 0), (262, '海带宝', 'ZY_HDB', 0), (263, '汇丰美中速递', 'ZY_HFMZ', 0), (264, '豪杰速递', 'ZY_HJSD', 0), (265, '华美快递', 'ZY_HMKD', 0), (266, '360hitao转运', 'ZY_HTAO', 0), (267, '海淘村', 'ZY_HTCUN', 0), (268, '365海淘客', 'ZY_HTKE', 0), (269, '华通快运', 'ZY_HTONG', 0), (270, '海星桥快递', 'ZY_HXKD', 0), (271, '华兴速运', 'ZY_HXSY', 0), (272, 'LogisticsY', 'ZY_IHERB', 0), (273, '领跑者快递', 'ZY_LPZ', 0), (274, '量子物流', 'ZY_LZWL', 0), (275, '明邦转运', 'ZY_MBZY', 0), (276, '美嘉快递', 'ZY_MJ', 0), (277, '168 美中快递', 'ZY_MZ', 0), (278, '欧e捷', 'ZY_OEJ', 0), (279, '欧洲疯', 'ZY_OZF', 0), (280, '欧洲GO', 'ZY_OZGO', 0), (281, '全美通', 'ZY_QMT', 0), (282, 'SCS国际物流', 'ZY_SCS', 0), (283, 'SOHO苏豪国际', 'ZY_SOHO', 0), (284, 'Sonic-Ex速递', 'ZY_SONIC', 0), (285, '通诚美中快递', 'ZY_TCM', 0), (286, 'TrakPak', 'ZY_TPAK', 0), (287, '天天海淘', 'ZY_TTHT', 0), (288, '天泽快递', 'ZY_TZKD', 0), (289, '迅达快递', 'ZY_XDKD', 0), (290, '信达速运', 'ZY_XDSY', 0), (291, '新干线快递', 'ZY_XGX', 0), (292, '信捷转运', 'ZY_XJ', 0), (293, '优购快递', 'ZY_YGKD', 0), (294, '友家速递(UCS)', 'ZY_YJSD', 0), (295, '云畔网', 'ZY_YPW', 0), (296, '易送网', 'ZY_YSW', 0), (297, '中运全速', 'ZYQS', 0), (298, '中邮物流', 'ZYWL', 0), (299, '汇强快递', 'ZHQKD', 0), (300, '众通快递', 'ZTE', 0), (301, '中通快运', 'ZTOKY', 0), (302, '中邮快递', 'ZYKD', 0), (303, '芝麻开门', 'ZMKM', 0), (304, '中骅物流', 'ZHWL', 0), (305, '中铁物流', 'ZTWL', 0), (306, '智汇鸟', 'ZHN', 0), (307, '众邮快递', 'ZYE', 0);

CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_data_fake_user`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(60) NULL DEFAULT NULL COMMENT '用户名',
  `nickname` varchar(60) NULL DEFAULT NULL COMMENT '昵称',
  `mobile` varchar(20) NULL DEFAULT NULL COMMENT '手机号',
  `password` varchar(60) NOT NULL DEFAULT '' COMMENT '密码',
  `avatar` varchar(255) NULL DEFAULT NULL COMMENT '头像',
  `gender` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '性别',
  `email` varchar(60) NULL DEFAULT NULL COMMENT '邮箱',
  `createtime` bigint(16) NULL DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '虚拟用户';


CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_data_faq`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL COMMENT '问题',
  `content` text NOT NULL COMMENT '内容',
  `status` enum('normal','hidden') NOT NULL DEFAULT 'normal' COMMENT '状态:normal=显示,hidden=隐藏',
  `createtime` bigint(16) NULL DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '常见问题';


CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_data_page`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(20) NOT NULL COMMENT '名称',
  `path` varchar(255) NOT NULL COMMENT '路径',
  `group` varchar(20) NOT NULL COMMENT '分组',
  `createtime` bigint(16) NULL DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '前端路由';
-- shopro_data_page 数据
INSERT INTO `__PREFIX__shopro_data_page` (`id`, `name`, `path`, `group`, `createtime`, `updatetime`) VALUES (1, '首页', '/pages/index/index', '商城', 1664350756, 1664350756), (2, '个人中心', '/pages/index/user', '商城', 1664350756, 1664350756), (3, '商品分类', '/pages/index/category', '商城', 1664350756, 1664350756), (4, '购物车', '/pages/index/cart', '商城', 1664350756, 1664350756), (5, '搜索', '/pages/index/search', '商城', 1664350756, 1664350756), (6, '自定义页面', '/pages/index/page', '商城', 1664350756, 1664350756), (7, '普通商品', '/pages/goods/index', '商品', 1664350756, 1664350756), (8, '拼团商品', '/pages/goods/groupon', '商品', 1664350756, 1664350756), (9, '秒杀商品', '/pages/goods/seckill', '商品', 1664350756, 1664350756), (10, '积分商品', '/pages/goods/score', '商品', 1664350756, 1664350756), (11, '商品列表', '/pages/goods/list', '商品', 1664350756, 1664350756), (12, '用户订单', '/pages/order/list', '订单中心', 1664350756, 1664350756), (13, '售后订单', '/pages/order/aftersale/list', '订单中心', 1664350756, 1664350756), (14, '用户信息', '/pages/user/info', '用户中心', 1664350756, 1664350756), (15, '商品收藏', '/pages/user/goods-collect', '用户中心', 1664350756, 1664350756), (16, '浏览记录', '/pages/user/goods-log', '用户中心', 1664350756, 1664350756), (17, '地址管理', '/pages/user/address/list', '用户中心', 1664350756, 1664350756), (18, '发票管理', '/pages/user/invoice/list', '用户中心', 1664350756, 1664350756), (19, '用户余额', '/pages/user/wallet/money', '用户中心', 1664350756, 1664350756), (20, '用户佣金', '/pages/user/wallet/commission', '分销中心', 1664350756, 1664350756), (21, '用户积分', '/pages/user/wallet/score', '用户中心', 1664350756, 1664350756), (22, '分销中心', '/pages/commission/index', '分销商城', 1664350756, 1664350756), (23, '申请分销商', '/pages/commission/apply', '分销商城', 1664350756, 1664350756), (24, '推广商品', '/pages/commission/goods', '分销商城', 1664350756, 1664350756), (25, '分销订单', '/pages/commission/order', '分销商城', 1664350756, 1664350756), (26, '分享记录', '/pages/commission/share-log', '分销商城', 1664350756, 1664350756), (27, '我的团队', '/pages/commission/team', '分销商城', 1664350756, 1664350756), (28, '签到中心', '/pages/app/sign', '应用', 1664350756, 1664350756), (29, '积分商城', '/pages/app/score-shop', '应用', 1664350756, 1664350756), (30, '系统设置', '/pages/public/setting', '通用', 1664350756, 1664350756), (31, '问题反馈', '/pages/public/feedback', '通用', 1664350756, 1664350756), (32, '富文本', '/pages/public/richtext', '通用', 1664350756, 1664350756), (33, '常见问题', '/pages/public/faq', '通用', 1664350756, 1664350756), (34, '领券中心', '/pages/coupon/list', '优惠券', 1664350756, 1664350756), (35, '优惠券详情', '/pages/coupon/detail', '优惠券', 1664350756, 1664350756), (36, '客服', '/pages/chat/index', '客服', 1664350756, 1664350756), (37, '充值余额', '/pages/pay/recharge', '支付', 1664350756, 1664350756), (38, '充值记录', '/pages/pay/recharge-log', '支付', 1664350756, 1664350756), (39, '申请提现', '/pages/pay/withdraw', '支付', 1664350756, 1664350756), (40, '提现记录', '/pages/pay/withdraw-log', '支付', 1664350756, 1664350756), (41, '拼团订单', '/pages/activity/groupon/order', '营销活动', 1664350756, 1664350756), (42, '营销商品', '/pages/activity/index', '营销活动', 1664350756, 1664350756), (43, '拼团活动', '/pages/activity/groupon/list', '营销活动', 1664350756, 1664350756), (44, '秒杀活动', '/pages/activity/seckill/list', '营销活动', 1664350756, 1664350756), (45, '阶梯拼团', '/pages/goods/groupon_ladder', '活动', 1660202947, 1660202947), (46, '分销排行', '/pages/commission/rank', '分销商城', 1660202947, 1660202947), (47, '优惠券中心', '/pages/app/coupon/index', '应用', 1663153046, 1663153046), (48, '优惠券详情', '/pages/app/coupon/detail', '应用', 1663153046, 1663153046), (49, '营销活动商品', '/pages/goods/activity', '商品', 1663311458, 1663311458);


CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_data_richtext`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL COMMENT '标题',
  `content` longtext NOT NULL COMMENT '内容',
  `createtime` bigint(16) NULL DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '富文本';
-- shopro_data_richtext 数据
INSERT INTO `__PREFIX__shopro_data_richtext` (`id`, `title`, `content`, `createtime`, `updatetime`) VALUES (1, '用户协议', '<p><br></p><p><span style=\"font-family: 微软雅黑;\">本软件许可及服务协议（以下称\"本协议\"）由您与河南星品科技有限公司（以下称“我们”）共同签署。</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">在使用shopro商城软件（以下称许可软件）之前，请您仔细阅读本协议，特别是免除或者限制责任的条款、法律适用和争议解决条款。</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">免除或者限制责任的条款将以粗体标识，您需要重点阅读。如您对协议有任何疑问，可向客服咨询。</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">如果您同意接受本协议条款和条件的约束，您可下载安装使用许可软件。</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">我们如修改本协议或其补充协议，协议条款修改后，请您仔细阅读并接受修改后的协议后再继续使用许可软件。</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">一、 定义</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; &nbsp; &nbsp; </span></p><p><span style=\"font-family: 微软雅黑;\">1. 本协议是您与我们之间关于您下载、安装、使用、登录本软件，以及使用本软件服务所订立的协议。</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">二、授权范围</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; &nbsp; &nbsp;</span></p><p><span style=\"font-family: 微软雅黑;\">1. 由于软件适配平台及终端限制，您理解您仅可在获授权的系统平台及终端使用许可软件，如您将许可软件安装在其他终端设备上（包括台式电脑、手提电脑、或授权终端外的其他手持移动终端、电视机及机顶盒等），可能会对您硬件或软件功能造成损害。</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; &nbsp; &nbsp;</span></p><p><span style=\"font-family: 微软雅黑;\">2. 您应该理解许可软件仅可用于非商业目的，您不可为商业运营目的安装、使用、运行许可软件。</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; &nbsp; </span></p><p><span style=\"font-family: 微软雅黑;\">3. 我们会对许可软件及其相关功能不时进行变更、升级、修改或转移，并会在许可软件系统中开发新的功能或其它服务。上述新的功能、软件服务如无独立协议的，您仍可取得相应功能或服务的授权，并可适用本协议。</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">三、使用规范</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; &nbsp; &nbsp;</span></p><p><span style=\"font-family: 微软雅黑;\">1. 您应该规范使用许可软件，以下方式是违反使用规范的：</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; &nbsp; </span></p><p><span style=\"font-family: 微软雅黑;\">（1）从事违反法律法规政策、破坏公序良俗、损害公共利益的行为。</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; &nbsp; &nbsp;</span></p><p><span style=\"font-family: 微软雅黑;\">（2）对许可软件及其中的相关信息擅自出租、出借、复制、修改、链接、转载、汇编、发表、出版、建立镜像站点，借助许可软件发展与之有关的衍生产品、作品、服务、插件、外挂、兼容、互联等。</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; &nbsp; &nbsp;</span></p><p><span style=\"font-family: 微软雅黑;\">（3）通过非由我们及其关联公司开发、授权或认可的第三方兼容软件、系统登录或使用许可软件，或针对许可软件使用非我们及其关联公司开发、授权或认证的插件和外挂。</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; &nbsp; &nbsp;</span></p><p><span style=\"font-family: 微软雅黑;\">（4）删除许可软件及其他副本上关于版权的信息、内容。修改、删除或避开应用产品中我们为保护知识产权而设置的任何技术措施。</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; &nbsp; &nbsp;</span></p><p><span style=\"font-family: 微软雅黑;\">（5）未经我们的书面同意，擅自将许可软件出租、出借或再许可给第三方使用，或在获得许可软件的升级版本的许可使用后，同时使用多个版本的许可使用版本，或分开转让。</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; &nbsp; &nbsp;</span></p><p><span style=\"font-family: 微软雅黑;\">（6）复制、反汇编、修改许可软件或其任何部分或制造其衍生作品；对许可软件或者许可软件运行过程中释放在终端中的任何数据及许可软件运行过程中终端与服务器端的交互数据进行复制、修改、挂接运行或创作任何衍生作品，包括使用插件、外挂或非经授权的第三方工具/服务接入许可软件和相关系统等形式。</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; &nbsp; &nbsp;</span></p><p><span style=\"font-family: 微软雅黑;\">（7） 进行任何危害信息网络安全的行为，包括使用许可软件时以任何方式损坏或破坏许可软件或使其不能运行或超负荷或干扰第三方对许可软件的使用；未经允许进入他人计算机系统并删除、修改、增加存储信息；故意传播恶意程序或病毒以及其他破坏、干扰正常网络信息服务的行为。</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; &nbsp; </span></p><p><span style=\"font-family: 微软雅黑;\">（8）利用许可软件发表、传送、传播、储存侵害他人知识产权、商业秘密权等合法权利的内容，或从事欺诈、盗用他人账户、资金等违法犯罪活动。</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; &nbsp; </span></p><p><span style=\"font-family: 微软雅黑;\">（9）通过修改或伪造许可软件运行中的指令、数据、数据包，增加、删减、变动许可软件的功能或运行效果，及/或将具有上述用途的软件通过信息网络向公众传播或者运营。</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; &nbsp;</span></p><p><span style=\"font-family: 微软雅黑;\">（10）其他以任何不合法的方式、为任何不合法的目的、或以任何与本协议不一致的方式使用许可软件。</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; </span></p><p><span style=\"font-family: 微软雅黑;\">2. 您理解并同意</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; &nbsp; &nbsp;</span></p><p><span style=\"font-family: 微软雅黑;\">（1）我们会对您是否涉嫌违反上述使用规范做出认定，并根据认定结果中止、终止对您的使用许可或采取其他依约可采取的限制措施。</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; &nbsp; &nbsp;</span></p><p><span style=\"font-family: 微软雅黑;\">（2）对于您使用许可软件时发布的涉嫌违法或涉嫌侵犯他人合法权利或违反本协议的信息，我们会直接予以删除。</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; &nbsp; &nbsp;</span></p><p><span style=\"font-family: 微软雅黑;\">（3）对于您违反上述使用规范的行为对任意第三方造成损害的，您需要以自己的名义独立承担法律责任，并应确保我们免于因此产生损失或增加费用。</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; &nbsp; </span></p><p><span style=\"font-family: 微软雅黑;\">（4）如您违反有关法律或者本协议之规定，使我们遭受任何损失，或受到任何第三方的索赔，或受到任何行政管理部门的处罚，您应当赔偿我们因此造成的损失及（或）发生的费用，包括合理的律师费用、调查取证费用。</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\"> 四、第三方软件或服务</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; &nbsp; &nbsp; &nbsp;</span></p><p><span style=\"font-family: 微软雅黑;\">1. 许可软件可能使用或包含了由第三方提供的软件或服务（以下称该等服务），该等服务是为了向您提供便利而设置，是取得该第三方的合法授权的。</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; &nbsp; &nbsp; </span></p><p><span style=\"font-family: 微软雅黑;\"> 2. 由于第三方为其软件或服务的提供者，您使用该等服务时，应另行与该第三方达成服务协议，支付相应费用并承担可能的风险。您应理解我们并无权在本协议中授予您使用该等服务的任何权利，也无权对该等服务提供任何形式的保证。我们无法对该等服务提供客户支持，如果您需要获取支持，您可直接与该第三方联系。因您使用该等服务引发的任何纠纷，您可直接与该第三方协商解决。</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; &nbsp; &nbsp; &nbsp;</span></p><p><span style=\"font-family: 微软雅黑;\"> 3. 您理解许可软件仅在当前使用或包含该等服务，我们无法保证许可软件将会永久地使用或包含该等服务，也无法保证将来不会使用或包含该第三方的同类型或不同类型的软件或服务或其他第三方的软件或服务，一旦我们在许可软件中使用或包含前述软件或服务，相应的软件或服务同样适用本条约定。</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; &nbsp; &nbsp; </span></p><p><span style=\"font-family: 微软雅黑;\"> 4. 您理解第三方需要与我们进行您的信息交互以便更好地为您提供服务，您同意在使用许可软件时如使用该等服务的，您授权我们依据《隐私协议》您使用许可软件的信息传递给该第三方，或从该第三方获取您注册或使用该等服务时提供或产生的信息。如果您不希望第三方获取您的信息的，您可停止使用该等服务，我们将停止向第三方传递您的信息。</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; &nbsp; &nbsp;</span></p><p><span style=\"font-family: 微软雅黑;\"> 5. 您同意，如果该第三方确认您违反了您与其之间关于使用该等服务的协议约定停止对您提供该等服务并要求我们处理的，由于停止该等服务可能会影响您继续使用许可软件，我们可能会中止、终止对你的使用许可或采取其他我们可对您采取的限制措施。</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; </span></p><p><span style=\"font-family: 微软雅黑;\">五、隐私政策与数据</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; &nbsp; &nbsp; &nbsp;</span></p><p><span style=\"font-family: 微软雅黑;\"> 保护您的个人信息对我们很重要。我们制定了《隐私协议》，您的信息收集、使用、共享、存储、保护等方面关系您切身利益的内容进行了重要披露。我们建议您完整地阅读《隐私协议》，以帮助您更好的保护您的个人信息。</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; </span></p><p><span style=\"font-family: 微软雅黑;\">六、特别授权</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; &nbsp; &nbsp; &nbsp;</span></p><p><span style=\"font-family: 微软雅黑;\"> 您对您的个人信息依法拥有权利，并且可以通过查阅《隐私协议》了解我们对您的个人信息的保护及处理方式。对您提供的除个人信息外的信息，为了向您提供您使用的各项服务，并维护、改进这些服务，及优化我们的服务质量等用途，对于您提交的文字、图片和视频等受知识产权保护的内容，您同意授予我们排他的、可转让、可分发次级许可、无使用费的全球性许可，用于我们及我们关联公司使用、复制、修订、改写、发布、翻译、分发、执行和展示您提交的资料数据或制作派生作品。</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp;</span></p><p><span style=\"font-family: 微软雅黑;\">七、 无担保和责任限制</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; </span></p><p><span style=\"font-family: 微软雅黑;\">1. 除法律法规有明确规定外，我们将尽最大努力确保许可软件及其所涉及的技术及信息安全、有效、准确、可靠，但受限于现有技术，您理解我们不能对此进行担保。</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; </span></p><p><span style=\"font-family: 微软雅黑;\">2. 您理解，对于不可抗力及第三方原因导致的您的直接或间接损失，我们无法承担责任。</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; </span></p><p><span style=\"font-family: 微软雅黑;\">3. 由于您因下述任一情况所引起或与此有关的人身伤害或附带的、间接的损害赔偿，包括但不限于利润损失、资料损失、业务中断的损害赔偿或其它商业损害赔偿或损失，需由您自行承担：</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; </span></p><p><span style=\"font-family: 微软雅黑;\">（1）使用或未能使用许可软件；</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;</span></p><p><span style=\"font-family: 微软雅黑;\"> （2）第三方未经批准的使用许可软件或更改您的数据；</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; </span></p><p><span style=\"font-family: 微软雅黑;\">（3）使用许可软件进行的行为产生的费用及损失；</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; </span></p><p><span style=\"font-family: 微软雅黑;\">（4）您对许可软件的误解；</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; </span></p><p><span style=\"font-family: 微软雅黑;\">（5）非因我们的原因而引起的与许可软件有关的其它损失。</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;</span></p><p><span style=\"font-family: 微软雅黑;\">4. 非经我们或我们授权开发并正式发布的其它任何由许可软件衍生的软件均属非法，下载、安装、使用此类软件，可能导致不可预知的风险，由此产生的法律责任与纠纷与我们无关，我们有权中止、终止使用许可和/或其他一切服务。</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;</span></p><p><span style=\"font-family: 微软雅黑;\">5. 您与其他使用许可软件的用户之间通过许可软件进行时，因您受误导或欺骗而导致或可能导致的任何心理、生理上的伤害以及经济上的损失，均应由过错方依法承担所有责任。</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; </span></p><p><span style=\"font-family: 微软雅黑;\">八、知识产权</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; &nbsp; &nbsp; &nbsp;</span></p><p><span style=\"font-family: 微软雅黑;\"> 1. 我们拥有许可软件的著作权、商业秘密以及其他相关的知识产权，包括与许可软件有关的各种文档资料。</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; &nbsp; &nbsp; &nbsp;</span></p><p><span style=\"font-family: 微软雅黑;\"> 2. 许可软件的相关标识属于我们及我们的关联公司的知识产权，并受到相关法律法规的保护。未经我们明确授权，您不得复制、模仿、使用或发布上述标识，也不得修改或删除应用产品中体现我们及其关联公司的任何标识或身份信息。</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; &nbsp; &nbsp; &nbsp;</span></p><p><span style=\"font-family: 微软雅黑;\">3. 未经我们及我们的关联公司事先书面同意，您不得为任何营利性或非营利性的目的自行实施、利用、转让或许可任何第三方实施、利用、转让上述知识产权。</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; &nbsp;</span></p><p><span style=\"font-family: 微软雅黑;\">九、协议终止和违约责任</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; </span></p><p><span style=\"font-family: 微软雅黑;\">1. 您应理解按授权范围使用许可软件、尊重软件及软件包含内容的知识产权、按规范使用软件、按本协议约定履行义务是您获取我们授权使用软件的前提，如您严重违反本协议，我们将终止使用许可。</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;</span></p><p><span style=\"font-family: 微软雅黑;\"> 2. 您对软件的使用有赖于我们关联公司为您提供的配套服务，您违反与我们或我们关联公司的条款、协议、规则、通告等相关规定，而被上述任一网站终止提供服务的，可能导致您无法正常使用许可软件，我们有权终止使用许可。</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;</span></p><p><span style=\"font-family: 微软雅黑;\"> 3. 您理解出于维护平台秩序及保护消费者权益的目的，如果您向我们及（或）我们的关联公司作出任何形式的承诺，且相关公司已确认您违反了该承诺并通知我们依据您与其相关约定进行处理的，则我们可按您的承诺或协议约定的方式对您的使用许可及其他我们可控制的权益采取限制措施，包括中止或终止对您的使用许可。</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; </span></p><p><span style=\"font-family: 微软雅黑;\">4. 一旦您违反本协议或与我们签订的其他协议的约定，我们可通知我们关联公司，要求其对您的权益采取限制措施，包括要求关联公司中止、终止对您提供部分或全部服务，且在其经营或实际控制的网站依法公示您的违约情况。</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;</span></p><p><span style=\"font-family: 微软雅黑;\">5. 许可软件由您自下载平台下载取得，您需要遵守下载平台、系统平台、终端厂商对您使用许可软件方式与限制的约定，如果上述第三方确认您违反该约定需要我们处理的，我们可能会因第三方要求终止对您的使用许可。</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; </span></p><p><span style=\"font-family: 微软雅黑;\"> 6. 在本使用许可终止时，您应停止对许可软件的使用行为，并销毁许可软件的全部副本。</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;</span></p><p><span style=\"font-family: 微软雅黑;\">7. 如您违反本协议规定的条款，给我们或其他用户造成损失，您必须承担全部的赔偿责任。如我们承担了上述责任，则您同意赔偿我们的相关支出和损失，包括合理的律师费用。</span></p><p><span style=\"font-family: 微软雅黑;\"> </span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">十、管辖法律和可分割性</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; &nbsp; &nbsp; </span></p><p><span style=\"font-family: 微软雅黑;\">1. 本协议之效力、解释、变更、执行与争议解决均适用中华人民共和国法律，如无相关法律规定的，则应参照通用国际商业惯例和（或）行业惯例。</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; &nbsp; &nbsp;</span></p><p><span style=\"font-family: 微软雅黑;\"> 2. 本协议由您与我们在郑州市金水区签署。因本协议产生或与本协议有关的争议，您可与我们以友好协商的方式予以解决或提交有管辖权的人民法院予以裁决。</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; &nbsp; &nbsp;</span></p><p><span style=\"font-family: 微软雅黑;\"> 3. 本协议任何条款被有管辖权的人民法院裁定为无效，不应影响其他条款或其任何部分的效力，您与我们仍应善意履行。</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp;</span></p><p><span style=\"font-family: 微软雅黑;\">十一、其他</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; &nbsp; </span></p><p><span style=\"font-family: 微软雅黑;\">1. 我们可能根据业务调整而变更向您提供软件服务的主体，变更后的主体与您共同履行本协议并向您提供服务，以上变更不会影响您本协议项下的权益。发生争议时，您可根据您具体使用的服务及对您权益产生影响的具体行为对象确定与您履约的主体及争议相对方。</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; &nbsp; &nbsp;</span></p><p><span style=\"font-family: 微软雅黑;\">2. 本协议的所有标题仅仅是为了醒目及阅读方便，本身并没有实际涵义，不能作为解释本协议涵义的依据。</span></p>', 1669008708, 1673018894), (2, '隐私协议', '<p><span style=\"font-family: 微软雅黑;\">河南星品科技有限公司（以下简称“我们”）深知个人信息对您的重要性，并会尽全力保护您的个人信息安全可靠。</span></p><p><span style=\"font-family: 微软雅黑;\">我们致力于维持您对我们的信任，恪守以下原则，保护您的个人信息：权责一致原则、目的明确原则、选择同意原则、最少够用原则、确保安全原则、主体参与原则、公开透明原则等。</span></p><p><span style=\"font-family: 微软雅黑;\">同时，我们承诺，我们将按业界成熟的安全标准，采取相应的安全保护措施来保护您的个人信息。</span></p><p><span style=\"font-family: 微软雅黑;\">请在使用我们的shopro商城前，仔细阅读并了解本《隐私协议》。</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">一、我们如何收集和使用您的个人信息</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">个人信息是指以电子或者其他方式记录的能够单独或者与其他信息结合识别特定自然人身份或者反映特定自然人活动情况的各种信息。</span></p><p><span style=\"font-family: 微软雅黑;\">我们仅会出于本政策所述的以下目的，收集和使用您的个人信息：</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">（一）为您提供网上购物服务 &nbsp; </span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">1、业务功能一：注册成为用户为完成创建账号，您需提供以下信息：您的姓名、手机号、创建的密码。</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">在注册过程中，如果您提供以下额外信息，将有助于我们给您提供更好的服务和体验：邮箱号、昵称、头像、银行卡、身份证。但如果您不提供这些信息，将不会影响使用本服务的基本功能。</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">您提供的上述信息，将在您使用本服务期间持续授权我们使用。在您注销账号时，我们将停止使用并删除上述信息。</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">上述信息将存储于中华人民共和国境内。如需跨境传输，我们将会单独征得您的授权同意。</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">2、业务功能二：商品展示、个性化推荐、发送促销营销信息。</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">（二）开展内部数据分析和研究，第三方SDK统计服务，改善我们的产品或服务</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">我们收集数据是根据您与我们的互动和您所做出的选择，包括您的隐私设置以及您使用的产品和功能。</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">我们收集的数据可能包括SDK/API/JS代码版本、浏览器、互联网服务提供商、IP地址、平台、时间戳、应用标识符、应用程序版本、应用分发渠道、独立设备标识符、iOS广告标识符（IDFA)、安卓广告主标识符、网卡（MAC）地址、国际移动设备识别码（IMEI）、设备型号、终端制造厂商、终端设备操作系统版本、会话启动/停止时间、语言所在地、时区和网络状态（WiFi等）、硬盘、CPU和电池使用情况等。</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">（三）其他使用情况</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">当我们要将信息用于本策略未载明的其它用途时，会事先征求您的同意。</span></p><p><span style=\"font-family: 微软雅黑;\">当我们要将基于特定目的收集而来的信息用于其他目的时，会事先征求您的同意。</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">二、我们如何使用 Cookie 和同类技术</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">（一）Cookie为确保网站正常运转，我们会在您的计算机或移动设备上存储名为 Cookie 的小数据文件。Cookie 通常包含标识符、站点名称以及一些号码和字符。借助于 Cookie，网站能够存储您的偏好或购物篮内的商品等数据。</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">我们不会将 Cookie 用于本政策所述目的之外的任何用途。您可根据自己的偏好管理或删除 Cookie。您可以清除计算机上保存的所有 Cookie，大部分网络浏览器都设有阻止 Cookie 的功能。</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">（二）网站信标和像素标签除 Cookie 外，我们还会在网站上使用网站信标和像素标签等其他同类技术。</span></p><p><span style=\"font-family: 微软雅黑;\">例如，我们向您发送的电子邮件可能含有链接至我们网站内容的点击 URL。如果您点击该链接，我们则会跟踪此次点击，帮助我们了解您的产品或服务偏好并改善客户服务。网站信标通常是一种嵌入到网站或电子邮件中的透明图像。借助于电子邮件中的像素标签，我们能够获知电子邮件是否被打开。如果您不希望自己的活动以这种方式被追踪，则可以随时从我们的寄信名单中退订。</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">（三）Do Not Track（请勿追踪）</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">很多网络浏览器均设有 Do Not Track 功能，该功能可向网站发布 Do Not Track 请求。目前，主要互联网标准组织尚未设立相关政策来规定网站应如何应对此类请求。但如果您的浏览器启用了 Do Not Track，那么我们的所有网站都会尊重您的选择。</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">三、我们如何共享、转让、公开披露您的个人信息</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">（一）共享</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">我们不会向其他任何公司、组织和个人分享您的个人信息，但以下情况除外：</span></p><p><br></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">1、在获取明确同意的情况下共享：获得您的明确同意后，我们会与其他方共享您的个人信息。</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">2、我们可能会根据法律法规规定，或按政府主管部门的强制性要求，对外共享您的个人信息。</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">3、与我们的关联公司共享：您的个人信息可能会与我们关联公司共享。我们只会共享必要的个人信息，且受本隐私协议中所声明目的的约束。</span></p><p><span style=\"font-family: 微软雅黑;\">关联公司如要改变个人信息的处理目的，将再次征求您的授权同意。</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">4、与授权合作伙伴共享：仅为实现本隐私协议中声明的目的，我们的某些服务将由授权合作伙伴提供。我们可能会与合作伙伴共享您的某些个人信息，以提供更好的客户服务和用户体验。</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">例如，我们聘请来提供第三方数据统计和分析服务的公司可能需要采集和访问个人数据以进行数据统计和分析。在这种情况下，这些公司 必须遵守我们的数据隐私和安全要求。</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">我们仅会出于合法、正当、必要、特定、明确的目的共享您的个人信息，并且只会共享提供服务所必要的个人信息。</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">对我们与之共享个人信息的公司、组织和个人，我们会与其签署严格的保密协定，要求他们按照我们的说明、本隐私协议以及其他任何相关的保密和安全措施来处理个人信息。</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">（二）转让</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">我们不会将您的个人信息转让给任何公司、组织和个人，但以下情况除外：</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">1、在获取明确同意的情况下转让：获得您的明确同意后，我们会向其他方转让您的个人信息；</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">2、在涉及合并、收购或破产清算时，如涉及到个人信息转让，我们会在要求新的持有您个人信息的公司、组织继续受此隐私政策的约束，否则我们将要求该公司、组织重新向您征求授权同意。</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">（三）公开披露</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">我们仅会在以下情况下，公开披露您的个人信息：</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">1、获得您明确同意后；</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">2、基于法律的披露：在法律、法律程序、诉讼或政府主管部门强制性要求的情况下，我们可能会公开披露您的个人信息。</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">四、我们如何保护您的个人信息</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">（一）我们已使用符合业界标准的安全防护措施保护您提供的个人信息，防止数据遭到未经授权访问、公开披露、使用、修改、损坏或丢失。我们会采取一切合理可行的措施，保护您的个人信息。</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">例如，在您的浏览器与“服务”之间交换数据（如信用卡信息）时受 SSL 加密保护；我们同时对我们网站提供 https 安全浏览方式；我们会使用加密技术确保数据的保密性；我们会使用受信赖的保护机制防止数据遭到恶意攻击；我们会部署访问控制机制，确保只有授权人员才可访问个人信息；以及我们会举办安全和隐私保护培训课程，加强员工对于保护个人信息重要性的认识。</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">（二）我们会采取一切合理可行的措施，确保未收集无关的个人信息。我们只会在达成本政策所述目的所需的期限内保留您的个人信息，除非需要延长保留期或受到法律的允许。</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">（三）互联网并非绝对安全的环境，而且电子邮件、即时通讯、及与其他我们用户的交流方式并未加密，我们强烈建议您不要通过此类方式发送个人信息。请使用复杂密码，协助我们保证您的账号安全。</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">（四）互联网环境并非百分之百安全，我们将尽力确保或担保您发送给我们的任何信息的安全性。如果我们的物理、技术、或管理防护设施遭到破坏，导致信息被非授权访问、公开披露、篡改、或毁坏，导致您的合法权益受损，我们将承担相应的法律责任。</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">（五）在不幸发生个人信息安全事件后，我们将按照法律法规的要求，及时向您告知：安全事件的基本情况和可能的影响、我们已采取或将要采取的处置措施、您可自主防范和降低风险的建议、对您的补救措施等。我们将及时将事件相关情况以邮件、信函、电话、推送通知等方式告知您，难以逐一告知个人信息主体时，我们会采取合理、有效的方式发布公告。</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">同时，我们还将按照监管部门要求，主动上报个人信息安全事件的处置情况。</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">五、您的权利</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">按照中国相关的法律、法规、标准，以及其他国家、地区的通行做法，我们保障您对自己的个人信息行使以下权利：</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">（一）访问您的个人信息</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">您有权访问您的个人信息，法律法规规定的例外情况除外。如果您想行使数据访问权，可以通过以下方式自行访问：</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">账户信息——如果您希望访问或编辑您的账户中的个人资料信息和支付信息、更改您的密码、添加安全信息或关闭您的账户等，您可以通过打开个人中心在设置里找到账户安全执行此类操作。</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">搜索信息——您可以在“我的”中访问或清除您的填写的历史记录、查看和修改以及管理其他数据。</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">对于您在使用我们的产品或服务过程中产生的其他个人信息，只要我们不需要过多投入，我们会向您提供。如果您想行使数据访问权，请联系shopro客服。</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">（二）更正您的个人信息</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">当您发现我们处理的关于您的个人信息有错误时，您有权要求我们做出更正。</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">您可以通过“（一）访问您的个人信息”中罗列的方式提出更正申请。</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">（三）删除您的个人信息</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">在以下情形中，您可以向我们提出删除个人信息的请求：</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">1、如果我们处理个人信息的行为违反法律法规； </span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">2、如果我们收集、使用您的个人信息，却未征得您的同意；</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">3、如果我们处理个人信息的行为违反了与您的约定；</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">4、如果您不再使用我们的产品或服务，或您注销了账号；</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">5、如果我们不再为您提供产品或服务。</span></p><p><br></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">若我们决定响应您的删除请求，我们还将同时通知从我们获得您的个人信息的实体，要求其及时删除，除非法律法规另有规定，或这些实体获得您的独立授权。</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">当您从我们的服务中删除信息后，我们可能不会立即备份系统中删除相应的信息，但会在备份更新时删除这些信息。</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; </span></p><p><span style=\"font-family: 微软雅黑;\">（四）改变您授权同意的范围</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\"> 每个业务功能需要一些基本的个人信息才能得以完成（见本协议“第一部分”）。对于额外收集的个人信息的收集和使用，您可以随时给予或收回您的授权同意。</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\"> 您可以通过以下方式自行操作： 打开shopro商城-我的-设置-账户安全</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">当您收回同意后，我们将不再处理相应的个人信息。但您收回同意的决定，不会影响此前基于您的授权而开展的个人信息处理。</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; </span></p><p><span style=\"font-family: 微软雅黑;\">（五）个人信息主体注销账户</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; </span></p><p><span style=\"font-family: 微软雅黑;\"> 您随时可注销此前注册的账户，您可以通过以下方式自行操作： 联系shopro客服</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;</span></p><p><span style=\"font-family: 微软雅黑;\">在注销账户之后，我们将停止为您提供产品或服务，并依据您的要求，删除您的个人信息，法律法规另有规定的除外。</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp;</span></p><p><span style=\"font-family: 微软雅黑;\">（六）约束信息系统自动决策</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;</span></p><p><span style=\"font-family: 微软雅黑;\">在某些业务功能中，我们可能仅依据信息系统、算法等在内的非人工自动决策机制做出决定。如果这些决定显著影响您的合法权益，您有权要求我们做出解释，我们也将提供适当的救济方式。</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp;</span></p><p><span style=\"font-family: 微软雅黑;\">（七）响应您的上述请求</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; </span></p><p><span style=\"font-family: 微软雅黑;\">为保障安全，您可能需要提供书面请求，或以其他方式证明您的身份。我们可能会先要求您验证自己的身份，然后再处理您的请求。</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; </span></p><p><span style=\"font-family: 微软雅黑;\">我们将在三十天内做出答复。如您不满意，还可以通过以下途径投诉：</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; </span></p><p><span style=\"font-family: 微软雅黑;\">个人中心-投诉建议</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; </span></p><p><span style=\"font-family: 微软雅黑;\">对于您合理的请求，我们原则上不收取费用，但对多次重复、超出合理限度的请求，我们将视情收取一定成本费用。对于那些无端重复、需要过多技术手段（例如，需要开发新系统或从根本上改变现行惯例）、给他人合法权益带来风险或者非常不切实际（例如，涉及备份磁带上存放的信息）的请求，我们可能会予以拒绝。</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;</span></p><p><span style=\"font-family: 微软雅黑;\">在以下情形中，按照法律法规要求，我们将无法响应您的请求：</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; </span></p><p><span style=\"font-family: 微软雅黑;\"> 1、与国家安全、国防安全有关的；</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; </span></p><p><span style=\"font-family: 微软雅黑;\"> 2、与公共安全、公共卫生、重大公共利益有关的；</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; </span></p><p><span style=\"font-family: 微软雅黑;\"> 3、与犯罪侦查、起诉和审判等有关的；</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; </span></p><p><span style=\"font-family: 微软雅黑;\"> 4、有充分证据表明您存在主观恶意或滥用权利的；</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;</span></p><p><span style=\"font-family: 微软雅黑;\">5、响应您的请求将导致您或其他个人、组织的合法权益受到严重损害的。</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp;</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">六、我们如何处理儿童的个人信息</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;</span></p><p><span style=\"font-family: 微软雅黑;\">我们的产品、网站和服务主要面向成人。如果没有父母或监护人的同意，儿童不得创建自己的用户账户。</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;</span></p><p><span style=\"font-family: 微软雅黑;\">对于经父母同意而收集儿童个人信息的情况，我们只会在受到法律允许、父母或监护人明确同意或者保护儿童所必要的情况下使用或公开披露此信息。</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;</span></p><p><span style=\"font-family: 微软雅黑;\">尽管当地法律和习俗对儿童的定义不同，但我们将不满14周岁的任何人均视为儿童。</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;</span></p><p><span style=\"font-family: 微软雅黑;\">如果我们发现自己在未事先获得可证实的父母同意的情况下收集了儿童的个人信息，则会设法尽快删除相关数据。</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\"> 七、您的个人信息如何在全球范围转移</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; &nbsp; &nbsp; &nbsp;</span></p><p><span style=\"font-family: 微软雅黑;\">原则上，我们在中华人民共和国境内收集和产生的个人信息，将存储在中华人民共和国境内。</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; &nbsp; &nbsp; &nbsp;</span></p><p><span style=\"font-family: 微软雅黑;\">由于我们通过遍布全球的资源和服务器提供产品或服务，这意味着，在获得您的授权同意后，您的个人信息可能会被转移到您使用产品或服务所在国家/地区的境外管辖区，或者受到来自这些管辖区的访问。</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; &nbsp; &nbsp; &nbsp;</span></p><p><span style=\"font-family: 微软雅黑;\">此类管辖区可能设有不同的数据保护法，甚至未设立相关法律。在此类情况下，我们会确保您的个人信息得到在中华人民共和国境内足够同等的保护。例如，我们会请求您对跨境转移个人信息的同意，或者在跨境数据转移之前实施数据去标识化等安全举措。</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; </span></p><p><span style=\"font-family: 微软雅黑;\">八、本隐私协议如何更新</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;</span></p><p><span style=\"font-family: 微软雅黑;\">我们可能适时会对本隐私协议进行调整或变更，本隐私协议的任何更新将以标注更新时间的方式公布在我们网站上，除法律法规或监管规定另有强制性规定外，经调整或变更的内容一经通知或公布后的7日后生效。如您在隐私协议调整或变更后继续使用我们提供的任一服务或访问我们相关网站的，我们相信这代表您已充分阅读、理解并接受修改后的隐私协议并受其约束。</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp; </span></p><p><span style=\"font-family: 微软雅黑;\">九、第三方SDK以及说明</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp;</span></p><p><span style=\"font-family: 微软雅黑;\">应用所集成的第三方SDK：com.igexin.sdk(个推;个推推送;)、com.g.gysdk(个推);、com.xiaomi.mipush(小米;小米推送)、com.getui(个推;个数应用统计;个像)、com.huawei.hms(华为;华为推送)、com.amap.api(高德地图;高德导航;高德定位;阿里高德地图;高德)、com.huawei.agconnect(华为;华为联运应用)、com.alipay(支付宝;阿里乘车码;阿里芝麻信用实名认证;芝麻认证)、com.xiaomi.push(小米;小米推送)。</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp;</span></p><p><span style=\"font-family: 微软雅黑;\">我们的产品基于DCloud;uni-app;App/Wap2App)开发，应用运行期间需要收集您的设备唯一识别码（IMEI/android;ID/DEVICE_ID/IDFA、SIM卡;IMSI;信息）以提供统计分析服务，并通过应用启动数据及异常错误日志分析改进性能和用户体验，为用户提供更好的服务。</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp;</span></p><p><span style=\"font-family: 微软雅黑;\">消息推送服务供应商（自启动）</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">我们可能会将您的设备平台、设备厂商、设备品牌、设备识别码等设备信息，应用列表信息、网络信息以及位置相关信息提供给合作伙伴，用于为您提供消息推送技术服务。我们在向您推送消息时，我们可能会授权合作伙伴进行链路调节，相互促活被关闭的SDK推送进程，保障您可以及时接收到我们向您推送的消息。</span></p><p><span style=\"font-family: 微软雅黑;\"> &nbsp;</span></p><p><span style=\"font-family: 微软雅黑;\">使用场景：推送订单付款、发货、售后以及余额提现、支付等系统类消息</span></p><p><span style=\"font-family: 微软雅黑;\"> </span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">微信一键登录/微信分享/微信支付（关联启动）</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">可能获取信息类型;存储的个人文件 </span></p><p><span style=\"font-family: 微软雅黑;\"> </span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">设备权限：读取外置存储器、写入外置存储器</span></p><p><span style=\"font-family: 微软雅黑;\"> </span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">使用场景：注册登录账号、微信分享、朋友圈分享、微信小程序分享、用户下单付款、订单申请售后</span></p><p><span style=\"font-family: 微软雅黑;\"> </span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">支付宝支付（关联启动）</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">设备权限：读取网络状态 &nbsp; &nbsp;</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">使用场景：用户下单付款、订单申请售后</span></p><p><span style=\"font-family: 微软雅黑;\"> </span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">隐私政策链接：https://render.alipay.com/p/c/k2cx0tg8</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">高德地图（自启动）可能获取信息类型 IMEI、openid、位置信息</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">设备权限：获取网络状态、访问Wi-Fi状态、位置信息、访问粗略位置、访问精准定位、读取手机状态和身份</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">使用场景：同城配送获取地址信息、同城自取自动推荐附近的门店</span></p><p><span style=\"font-family: 微软雅黑;\"> </span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">隐私政策：https://lbs.amap.com/agreement/compliance</span></p><p><br></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">十、如何联系我们</span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\"> </span></p><p><span style=\"font-family: 微软雅黑;\">如果您对本隐私政策有任何疑问、意见或建议，通过以下方式与我们联系：</span></p><p><span style=\"font-family: 微软雅黑;\"> </span></p><p><br></p><p><span style=\"font-family: 微软雅黑;\">邮箱：xptech@qq.com</span></p><p><span style=\"font-family: 微软雅黑;\"> </span></p><p><span style=\"font-family: 微软雅黑;\">一般情况下，我们将在三十天内回复。</span></p><p><br></p>', 1669008708, 1673018886), (3, '关于我们', '<p><span style=\"font-family: 微软雅黑;\">河南星品科技有限公司成立于2017年5月，注册资金1000万，位于河南省郑州市,主要经营范围为计算机软硬件领域、网络科技的技术开发、技术转让、技术咨询、技术服务；网络工程；计算机系统集成；计算机数据处理；商务咨询；电子商务等。</span></p><p><br></p><p><span style=\"color: rgb(44, 62, 80); font-family: 微软雅黑;\">公司员工数量超过20人，具备稳定、完善的产品设计研发体系。其中55%以上员工为技术研发工程师，核心工程师均具备5年以上大型项目研发经验，产品研发实力雄厚。</span></p><p><br></p><p><span style=\"color: rgb(44, 62, 80); font-family: 微软雅黑;\">通过“去中心化”的新零售解决方案，助力企业实现公域流量向私域流量的转化；以性能稳定、高并发、云部署的核心技术赋能开发者，提速企业数字化；通过开源核心产品、核心技术，吸引第三方开发者加入并打造出sheepjs产品生态，为广大商家提供更多应用选择与更优质的服务！</span></p><p><br></p><p><br></p><p><br></p>', 1669008708, 1673018876);


CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_decorate`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT '名称',
  `type` enum('template','diypage','designer') NOT NULL DEFAULT 'template' COMMENT '模板类型:template=店铺模板,diypage=自定义页面,designer=设计师模板',
  `memo` varchar(255) NULL DEFAULT NULL COMMENT '备注',
  `platform` varchar(255) NULL DEFAULT NULL COMMENT '支持平台',
  `status` enum('enable','disabled') NOT NULL DEFAULT 'disabled' COMMENT '状态:normal=启用,disabled=禁用',
  `createtime` bigint(16) NULL DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) NULL DEFAULT NULL COMMENT '更新时间',
  `deletetime` bigint(16) NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '装修模板';


CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_decorate_page`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `decorate_id` int(11) NOT NULL DEFAULT 0 COMMENT '模板',
  `type` varchar(10) NOT NULL COMMENT '类型',
  `page` longtext NOT NULL COMMENT '数据',
  `image` longtext NULL COMMENT '截图',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '模板数据';


CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_dispatch`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(60) NULL DEFAULT NULL COMMENT '模板名称',
  `type` enum('express', 'autosend') NOT NULL DEFAULT 'express' COMMENT '发货方式:express=快递物流,autosend=自动发货',
  `status` enum('normal','disabled') NOT NULL DEFAULT 'normal' COMMENT '状态:normal=正常,disabled=禁用',
  `createtime` bigint(16) NULL DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '发货模板';

CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_dispatch_autosend`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dispatch_id` int(11) NOT NULL COMMENT '配送模板',
  `type` enum('text','params') NOT NULL DEFAULT 'text' COMMENT '自动发货类型:text=固定内容,params=自定义内容',
  `content` varchar(1200) NULL DEFAULT NULL COMMENT '发货内容',
  `createtime` bigint(16) NULL DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '自动发货';

CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_dispatch_express`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dispatch_id` int(11) NOT NULL DEFAULT 0 COMMENT '配送模板',
  `type` enum('number','weight') NOT NULL DEFAULT 'number' COMMENT '计费方式:number=件数,weight=重量',
  `first_num` int(10) NOT NULL DEFAULT 0 COMMENT '首(重/件)数',
  `first_price` decimal(10, 2) NULL DEFAULT NULL COMMENT '首(重/件)',
  `additional_num` int(10) NOT NULL DEFAULT 0 COMMENT '续(重/件)数',
  `additional_price` decimal(10, 2) NULL DEFAULT NULL COMMENT '续(重/件)',
  `province_ids` varchar(255) NULL DEFAULT NULL COMMENT '省份',
  `city_ids` varchar(255) NULL DEFAULT NULL COMMENT '城市',
  `district_ids` varchar(255) NULL DEFAULT NULL COMMENT '地区',
  `weigh` int(8) NOT NULL DEFAULT 0 COMMENT '权重',
  `createtime` bigint(16) NULL DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '运费模板';


CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_feedback`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT 0 COMMENT '反馈用户',
  `type` varchar(30) NOT NULL COMMENT '反馈类型',
  `content` varchar(1024) NULL DEFAULT NULL COMMENT '反馈内容',
  `images` varchar(1024) NULL DEFAULT NULL COMMENT '截图',
  `phone` varchar(30) NULL DEFAULT NULL COMMENT '联系电话',
  `status` enum('0','1') NOT NULL DEFAULT '0' COMMENT '处理状态',
  `remark` varchar(255) NULL DEFAULT NULL COMMENT '系统备注',
  `createtime` bigint(16) NULL DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '意见反馈';


CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_goods`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` enum('normal','virtual','card') NOT NULL DEFAULT 'normal' COMMENT '商品类型:normal=实体商品,virtual=虚拟商品,card=电子卡密',
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT '标题',
  `subtitle` varchar(255) NULL DEFAULT NULL COMMENT '副标题',
  `category_ids` varchar(120) NULL DEFAULT NULL COMMENT '所属分类',
  `image` varchar(255) NULL DEFAULT NULL COMMENT '商品主图',
  `images` varchar(2500) NULL DEFAULT NULL COMMENT '轮播图',
  `params` varchar(2500) NULL DEFAULT NULL COMMENT '参数详情',
  `content` text NULL COMMENT '图文详情',
  `original_price` decimal(10, 2) NULL DEFAULT NULL COMMENT '原价',
  `price` decimal(10, 2) NULL DEFAULT NULL COMMENT '价格',
  `is_sku` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否多规格',
  `limit_type` enum('none','daily','all') NOT NULL DEFAULT 'none' COMMENT '限购类型:none=不限购,daily=每日,all=累计',
  `limit_num` int(10) NOT NULL DEFAULT 0 COMMENT '限购数量',
  `likes` int(10) NOT NULL DEFAULT 0 COMMENT '收藏人数',
  `views` int(10) NOT NULL DEFAULT 0 COMMENT '浏览人数',
  `sales` int(10) NOT NULL DEFAULT 0 COMMENT '销量',
  `sales_show_type` enum('exact','sketchy') NOT NULL DEFAULT 'exact' COMMENT '销量显示类型:exact=精确的,sketchy=粗略的',
  `stock_show_type` enum('exact','sketchy') NOT NULL DEFAULT 'exact' COMMENT '库存显示类型:exact=精确的,sketchy=粗略的',
  `show_sales` int(10) NOT NULL DEFAULT 0 COMMENT '显示销量',
  `service_ids` varchar(120) NULL DEFAULT NULL COMMENT '服务标签',
  `dispatch_type` varchar(120) NULL DEFAULT NULL COMMENT '发货方式',
  `dispatch_id` int(11) NOT NULL DEFAULT 0 COMMENT '发货模板',
  `is_offline` tinyint(3) NOT NULL DEFAULT 0 COMMENT '线下付款:0=否,1=是',
  `status` enum('up','hidden','down') NOT NULL DEFAULT 'up' COMMENT '商品状态:up=上架,hidden=隐藏,down=下架',
  `weigh` int(8) NOT NULL DEFAULT 0 COMMENT '权重',
  `createtime` bigint(16) NULL DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) NULL DEFAULT NULL COMMENT '更新时间',
  `deletetime` bigint(16) NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '商品';


CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_goods_comment`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `goods_id` int(11) NOT NULL DEFAULT 0 COMMENT '商品',
  `order_id` int(11) NOT NULL DEFAULT 0 COMMENT '订单',
  `order_item_id` int(11) NOT NULL DEFAULT 0 COMMENT '订单商品',
  `user_id` int(11) NOT NULL DEFAULT 0 COMMENT '用户',
  `user_type` enum('user','fake_user') NOT NULL DEFAULT 'user' COMMENT '用户类型:user=用户,fake_user=虚拟用户',
  `user_nickname` varchar(255) NULL DEFAULT NULL COMMENT '用户昵称',
  `user_avatar` varchar(255) NULL DEFAULT NULL COMMENT '用户头像',
  `level` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '评价星级',
  `content` varchar(512) NULL DEFAULT NULL COMMENT '评价内容',
  `images` varchar(2500) NULL DEFAULT NULL COMMENT '评价图片',
  `status` enum('normal','hidden') NOT NULL DEFAULT 'normal' COMMENT '显示状态:normal=正常,hidden=隐藏',
  `admin_id` int(11) NOT NULL DEFAULT 0 COMMENT '管理员',
  `reply_content` varchar(512) NULL DEFAULT NULL COMMENT '回复内容',
  `reply_time` int(10) NULL DEFAULT NULL COMMENT '回复时间',
  `createtime` bigint(16) NULL DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) NULL DEFAULT NULL COMMENT '更新时间',
  `deletetime` bigint(16) NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '商品评价';


CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_goods_service`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NULL DEFAULT NULL COMMENT '名称',
  `image` varchar(255) NULL DEFAULT NULL COMMENT '服务标志',
  `description` varchar(255) NULL DEFAULT NULL COMMENT '描述',
  `createtime` int(10) NULL DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(10) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '商品服务标签';


CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_goods_sku`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '名称',
  `parent_id` int(11) NOT NULL DEFAULT 0 COMMENT '所属规格',
  `goods_id` int(11) NOT NULL DEFAULT 0 COMMENT '商品',
  `weigh` int(8) NOT NULL DEFAULT 0 COMMENT '权重',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '商品规格';


CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_goods_sku_price`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `goods_sku_ids` varchar(120) NULL DEFAULT NULL COMMENT '规格',
  `goods_sku_text` varchar(255) NULL DEFAULT NULL COMMENT '规格中文',
  `goods_id` int(11) NOT NULL DEFAULT 0 COMMENT '商品',
  `image` varchar(255) NULL DEFAULT NULL COMMENT '缩略图',
  `stock` int(10) NOT NULL DEFAULT 0 COMMENT '库存',
  `stock_warning` int(10) NULL DEFAULT NULL COMMENT '库存预警',
  `sales` int(10) NOT NULL DEFAULT 0 COMMENT '销量',
  `sn` varchar(50) NULL DEFAULT NULL COMMENT '货号',
  `weight` decimal(10, 2) NOT NULL DEFAULT '0.00' COMMENT '重量(KG)',
  `cost_price` decimal(10, 2) NULL DEFAULT NULL COMMENT '成本价',
  `original_price` decimal(10, 2) NULL DEFAULT NULL COMMENT '原价',
  `price` decimal(10, 2) NULL DEFAULT NULL COMMENT '价格',
  `status` enum('up','down') NOT NULL DEFAULT 'up' COMMENT '商品状态:up=上架,down=下架',
  `weigh` int(8) NOT NULL DEFAULT 0 COMMENT '权重',
  `createtime` bigint(16) NULL DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '商品规格价格';


CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_goods_stock_log`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `goods_id` int(11) NOT NULL DEFAULT 0 COMMENT '商品',
  `admin_id` int(11) NOT NULL DEFAULT 0 COMMENT '操作人',
  `goods_sku_price_id` int(11) NOT NULL DEFAULT 0 COMMENT '规格',
  `goods_sku_text` varchar(255) NULL DEFAULT NULL COMMENT '规格名',
  `before` int(10) NOT NULL DEFAULT 0 COMMENT '补货前',
  `stock` int(10) NOT NULL DEFAULT 0 COMMENT '补货库存',
  `msg` varchar(255) NULL DEFAULT NULL COMMENT '补货备注',
  `createtime` bigint(16) NULL DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '商品库存记录';


CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_goods_stock_warning`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `goods_id` int(11) NOT NULL DEFAULT 0 COMMENT '商品',
  `goods_sku_price_id` int(11) NOT NULL DEFAULT 0 COMMENT '规格',
  `goods_sku_text` varchar(255) NULL DEFAULT NULL COMMENT '规格名',
  `stock_warning` int(10) NOT NULL DEFAULT 0 COMMENT '预警值',
  `createtime` bigint(16) NULL DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) NULL DEFAULT NULL COMMENT '更新时间',
  `deletetime` bigint(16) NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '商品库存预警';

CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_mplive_goods`  (
  `id` int(11) NOT NULL,
  `type` tinyint(4) NULL DEFAULT NULL COMMENT '商品来源',
  `audit_id` int(11) NULL DEFAULT NULL COMMENT '审核单ID',
  `goods_id` int(11) NULL DEFAULT NULL COMMENT '商城商品ID',
  `name` varchar(255) NULL DEFAULT NULL COMMENT '商品名称',
  `cover_img_url` varchar(255) NULL DEFAULT NULL COMMENT '封面图',
  `price_type` tinyint(4) NULL DEFAULT NULL COMMENT '价格类型',
  `price` decimal(10, 2) NULL DEFAULT NULL COMMENT '价格',
  `price2` decimal(10, 2) NULL DEFAULT NULL COMMENT '价格2',
  `third_party_tag` tinyint(4) NULL DEFAULT NULL COMMENT '添加商品标识',
  `third_party_appid` varchar(255) NULL DEFAULT NULL COMMENT '第三方小程序APPID',
  `on_shelves` tinyint(4) NULL DEFAULT NULL COMMENT '上架状态',
  `audit_status` tinyint(4) NULL DEFAULT NULL COMMENT '审核状态',
  `url` varchar(255) NULL DEFAULT NULL COMMENT '商品链接',
  `createtime` bigint(16) NULL DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '直播间商品';

CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_mplive_room`  (
  `roomid` int(10) NOT NULL COMMENT '房间号',
  `name` varchar(255) NULL DEFAULT NULL COMMENT '直播间名称',
  `type` tinyint(4) NULL DEFAULT NULL COMMENT '直播方式',
  `status` int(10) NULL DEFAULT NULL COMMENT '状态',
  `is_feeds_public` tinyint(4) NULL DEFAULT NULL COMMENT '官方推荐',
  `goods` varchar(255) NULL DEFAULT NULL COMMENT '商品',
  `anchor_name` varchar(50) NULL DEFAULT NULL COMMENT '主播名称',
  `share_img` varchar(255) NULL DEFAULT NULL COMMENT '分享图',
  `cover_img` varchar(255) NULL DEFAULT NULL COMMENT '封面图',
  `feeds_img` varchar(255) NULL DEFAULT NULL COMMENT '官方封面图',
  `close_replay` tinyint(4) NULL DEFAULT NULL COMMENT '关闭回放',
  `close_like` tinyint(4) NULL DEFAULT NULL COMMENT '关闭点赞',
  `close_kf` tinyint(4) NULL DEFAULT NULL COMMENT '关闭客服',
  `close_goods` tinyint(4) NULL DEFAULT NULL COMMENT '关闭商品橱窗',
  `close_comment` tinyint(4) NULL DEFAULT NULL COMMENT '关闭评论',
  `creater_openid` varchar(255) NULL DEFAULT NULL COMMENT '创建用户',
  `start_time` int(10) NULL DEFAULT NULL COMMENT '开始时间',
  `end_time` int(10) NULL DEFAULT NULL COMMENT '结束时间',
  PRIMARY KEY (`roomid`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '直播间';

CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_notification`  (
  `id` char(36) NOT NULL,
  `notification_type` varchar(60) NOT NULL DEFAULT '' COMMENT '通知类型',
  `type` varchar(60) NOT NULL DEFAULT '' COMMENT '消息类型',
  `notifiable_id` int(11) NOT NULL DEFAULT 0 COMMENT '通知人',
  `notifiable_type` varchar(60) NOT NULL COMMENT '通知人类型',
  `data` text NULL COMMENT '内容',
  `read_time` int(10) NULL DEFAULT NULL COMMENT '读取时间',
  `createtime` bigint(16) NULL DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '通知';


CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_notification_config`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event` varchar(60) NULL DEFAULT NULL COMMENT '消息事件',
  `channel` enum('Sms','Email','Websocket','WechatOfficialAccount','WechatMiniProgram','WechatOfficialAccountBizsend') NULL DEFAULT NULL COMMENT '发送渠道:Sms=短信,Email=邮件,Websocket=Websocket,WechatOfficialAccount=微信模板消息,WechatMiniProgram=小程序订阅消息,WechatOfficialAccountBizsend=公众号订阅消息',
  `type` enum('default','custom') NULL DEFAULT NULL COMMENT '类型:default=默认,custom=自定义',
  `content` text NULL COMMENT '配置内容',
  `status` enum('enable','disabled') NOT NULL DEFAULT 'enable' COMMENT '状态:enable=开启,disabled=关闭',
  `send_num` int(10) NOT NULL DEFAULT 0 COMMENT '发送次数',
  `createtime` bigint(16) NULL DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '消息通知配置';


CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_order`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` enum('goods','score') NOT NULL DEFAULT 'goods' COMMENT '订单类型:goods=商城订单,score=积分商城订单',
  `order_sn` varchar(60) NOT NULL DEFAULT '' COMMENT '订单号',
  `user_id` int(11) NOT NULL DEFAULT 0 COMMENT '用户',
  `activity_id` int(11) NOT NULL DEFAULT 0 COMMENT '活动',
  `activity_type` varchar(255) NULL DEFAULT NULL COMMENT '活动类型',
  `promo_types` varchar(255) NULL DEFAULT NULL COMMENT '营销类型',
  `goods_original_amount` decimal(10, 2) NOT NULL DEFAULT '0.00' COMMENT '商品原价',
  `goods_amount` decimal(10, 2) NOT NULL DEFAULT '0.00' COMMENT '商品总价',
  `dispatch_amount` decimal(10, 2) NOT NULL DEFAULT '0.00' COMMENT '运费',
  `remark` varchar(255) NULL DEFAULT NULL COMMENT '用户备注',
  `memo` varchar(255) NULL DEFAULT NULL COMMENT '商家备注',
  `status` enum('closed','cancel','unpaid','paid','completed','pending') NOT NULL DEFAULT 'unpaid' COMMENT '订单状态:closed=交易关闭,cancel=已取消,unpaid=未支付,paid=已支付,completed=已完成,pending=待定',
  `order_amount` decimal(10, 2) NOT NULL DEFAULT '0.00' COMMENT '订单总金额',
  `score_amount` int(10) NOT NULL DEFAULT 0 COMMENT '积分总数',
  `pay_fee` decimal(10, 2) NOT NULL DEFAULT '0.00' COMMENT '支付总金额',
  `original_pay_fee` decimal(10, 2) NULL DEFAULT NULL COMMENT '原始支付总金额',
  `remain_pay_fee` decimal(10, 2) NOT NULL DEFAULT '0.00' COMMENT '剩余支付金额',
  `paid_time` int(10) NULL DEFAULT NULL COMMENT '支付成功时间',
  `pay_mode` enum('online','offline') NOT NULL DEFAULT 'online' COMMENT '支付模式:online=线上支付,offline=线下支付',
  `apply_refund_status` tinyint(4) NOT NULL DEFAULT 0 COMMENT '申请退款状态:0=未申请,1=用户申请退款,-1=拒绝申请',
  `total_discount_fee` decimal(10, 2) NOT NULL DEFAULT '0.00' COMMENT '优惠总金额',
  `coupon_discount_fee` decimal(10, 2) NOT NULL DEFAULT '0.00' COMMENT '优惠券抵扣金额',
  `promo_discount_fee` decimal(10, 2) NOT NULL DEFAULT '0.00' COMMENT '营销优惠金额',
  `coupon_id` int(11) NOT NULL DEFAULT 0 COMMENT '优惠券',
  `invoice_status` tinyint(4) NOT NULL DEFAULT 0 COMMENT '发票开具状态:-1=不可开具,0=未申请,1=已申请',
  `ext` varchar(2048) NULL DEFAULT NULL COMMENT '附加信息',
  `platform` enum('H5','App','WechatOfficialAccount','WechatMiniProgram') NULL DEFAULT NULL COMMENT '平台:H5=H5,WechatOfficialAccount=微信公众号,WechatMiniProgram=微信小程序,App=App',
  `createtime` bigint(16) NULL DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) NULL DEFAULT NULL COMMENT '更新时间',
  `deletetime` bigint(16) NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `order_sn`(`order_sn`) USING BTREE,
  INDEX `user_id`(`user_id`) USING BTREE,
  INDEX `status`(`status`) USING BTREE,
  INDEX `createtime`(`createtime`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '订单';


CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_order_action`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL DEFAULT 0 COMMENT '订单',
  `order_item_id` int(11) NOT NULL DEFAULT 0 COMMENT '订单商品',
  `oper_type` varchar(60) NULL DEFAULT NULL COMMENT '操作人类型',
  `oper_id` int(11) NOT NULL DEFAULT 0 COMMENT '操作人',
  `order_status` enum('closed','cancel','unpaid','paid','completed','pending') NOT NULL DEFAULT 'unpaid' COMMENT '订单状态:closed=交易关闭,cancel=已取消,unpaid=未支付,paid=已支付,completed=已完成,pending=待定',
  `dispatch_status` tinyint(4) NOT NULL DEFAULT 0 COMMENT '发货状态:-1=拒收,0=未发货,1=已发货,2=已收货',
  `aftersale_status` tinyint(4) NOT NULL DEFAULT 0 COMMENT '售后状态:-1=拒绝,0=未申请,1=申请售后,2=售后完成',
  `refund_status` tinyint(4) NOT NULL DEFAULT 0 COMMENT '退款状态:0=未退款,1=已同意,2=已完成',
  `comment_status` tinyint(4) NOT NULL DEFAULT 0 COMMENT '评价状态:0=未评价,1=已评价',
  `remark` varchar(255) NULL DEFAULT NULL COMMENT '用户备注',
  `createtime` bigint(16) NULL DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `order_id`(`order_id`) USING BTREE,
  INDEX `order_item_id`(`order_item_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '订单操作';


CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_order_address`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL DEFAULT 0 COMMENT '订单',
  `user_id` int(11) NOT NULL DEFAULT 0 COMMENT '用户',
  `consignee` varchar(60) NULL DEFAULT NULL COMMENT '收货人',
  `mobile` varchar(20) NULL DEFAULT NULL COMMENT '收货手机',
  `province_name` varchar(60) NULL DEFAULT NULL COMMENT '省份',
  `city_name` varchar(60) NULL DEFAULT NULL COMMENT '城市',
  `district_name` varchar(60) NULL DEFAULT NULL COMMENT '地区',
  `address` varchar(255) NULL DEFAULT NULL COMMENT '详细地址',
  `province_id` int(11) NOT NULL DEFAULT 0 COMMENT '省Id',
  `city_id` int(11) NOT NULL DEFAULT 0 COMMENT '市Id',
  `district_id` int(11) NOT NULL DEFAULT 0 COMMENT '区Id',
  `createtime` bigint(16) NULL DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `order_id`(`order_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '订单收货信息';


CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_order_aftersale`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aftersale_sn` varchar(60) NOT NULL DEFAULT '' COMMENT '售后单号',
  `user_id` int(11) NOT NULL DEFAULT 0 COMMENT '用户',
  `type` varchar(60) NULL DEFAULT NULL COMMENT '类型:refund=退款,return=退货,other=其他',
  `mobile` varchar(20) NULL DEFAULT NULL COMMENT '联系方式',
  `activity_id` int(11) NOT NULL DEFAULT 0 COMMENT '活动',
  `activity_type` varchar(255) NULL DEFAULT NULL COMMENT '活动类型',
  `order_id` int(11) NOT NULL DEFAULT 0 COMMENT '订单',
  `order_item_id` int(11) NOT NULL DEFAULT 0 COMMENT '订单商品',
  `goods_id` int(11) NOT NULL DEFAULT 0 COMMENT '商品',
  `goods_sku_price_id` int(11) NOT NULL DEFAULT 0 COMMENT '规格',
  `goods_sku_text` varchar(60) NULL DEFAULT NULL COMMENT '规格名',
  `goods_title` varchar(255) NOT NULL DEFAULT '' COMMENT '商品名称',
  `goods_image` varchar(255) NOT NULL DEFAULT '' COMMENT '商品图片',
  `goods_original_price` decimal(10, 2) NOT NULL DEFAULT '0.00' COMMENT '商品原价',
  `goods_price` decimal(10, 2) NOT NULL DEFAULT '0.00' COMMENT '商品价格',
  `discount_fee` decimal(10, 2) NOT NULL DEFAULT '0.00' COMMENT '优惠费用',
  `goods_num` int(10) NOT NULL DEFAULT 0 COMMENT '购买数量',
  `dispatch_status` tinyint(4) NOT NULL DEFAULT 0 COMMENT '发货状态:-1=拒收,0=未发货,1=已发货,2=已收货',
  `dispatch_fee` decimal(10, 2) NOT NULL DEFAULT '0.00' COMMENT '发货费用',
  `aftersale_status` tinyint(4) NOT NULL DEFAULT 0 COMMENT '售后状态:-2=已取消,-1=拒绝,0=未处理,1=申请售后,2=售后完成',
  `refund_status` tinyint(4) NOT NULL DEFAULT 0 COMMENT '退款状态:0=未退款,1=已同意',
  `refund_fee` decimal(10, 2) NOT NULL DEFAULT '0.00' COMMENT '退款金额',
  `reason` varchar(255) NULL DEFAULT NULL COMMENT '申请原因',
  `content` varchar(1024) NULL DEFAULT NULL COMMENT '相关描述',
  `createtime` bigint(16) NULL DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) NULL DEFAULT NULL COMMENT '更新时间',
  `deletetime` bigint(16) NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `aftersale_sn`(`aftersale_sn`) USING BTREE,
  INDEX `user_id`(`user_id`) USING BTREE,
  INDEX `order_id`(`order_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '订单售后';


CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_order_aftersale_log`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL DEFAULT 0 COMMENT '订单',
  `order_aftersale_id` int(11) NOT NULL DEFAULT 0 COMMENT '售后单',
  `oper_type` varchar(60) NULL DEFAULT NULL COMMENT '操作人类型',
  `oper_id` int(11) NOT NULL DEFAULT 0 COMMENT '操作人',
  `dispatch_status` tinyint(4) NOT NULL DEFAULT 0 COMMENT '发货状态:-1=拒收,0=未发货,1=已发货,2=已收货',
  `aftersale_status` tinyint(4) NOT NULL DEFAULT 0 COMMENT '售后状态:-1=拒绝,0=未申请,1=申请售后,2=售后完成',
  `refund_status` tinyint(4) NOT NULL DEFAULT 0 COMMENT '退款状态:0=未退款,1=已同意,2=已完成',
  `log_type` varchar(255) NULL DEFAULT NULL COMMENT '日志类型',
  `content` varchar(255) NULL DEFAULT NULL COMMENT '操作内容',
  `images` varchar(2500) NULL DEFAULT NULL COMMENT '图片',
  `createtime` bigint(16) NULL DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `order_id`(`order_id`) USING BTREE,
  INDEX `order_aftersale_id`(`order_aftersale_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '订单售后记录';


CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_order_express`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT 0 COMMENT '用户',
  `order_id` int(11) NOT NULL DEFAULT 0 COMMENT '订单',
  `method` enum('input','api','upload') NOT NULL DEFAULT 'input' COMMENT '发货方式:input=手动发货,api=推送运单,upload=上传发货单',
  `driver` varchar(30) NULL DEFAULT NULL COMMENT '当前物流驱动',
  `express_name` varchar(60) NULL DEFAULT NULL COMMENT '快递公司',
  `express_code` varchar(60) NULL DEFAULT NULL COMMENT '公司编号',
  `express_no` varchar(60) NULL DEFAULT NULL COMMENT '快递单号',
  `status` enum('noinfo','collect','transport','delivery','signfor','refuse','difficulty','invalid','timeout','fail','back') NOT NULL DEFAULT 'noinfo' COMMENT '订单状态:noinfo=暂无信息,collect=已揽件,transport=运输中,delivery=派送中,signfor=已签收,refuse=用户拒收,difficulty=问题件,invalid=无效件,timeout=超时单,fail=签收失败,back=退回',
  `ext` varchar(2048) NULL DEFAULT NULL COMMENT '附加信息',
  `createtime` bigint(16) NULL DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `user_id`(`user_id`) USING BTREE,
  INDEX `order_id`(`order_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '快递包裹';


CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_order_express_log`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT 0 COMMENT '用户',
  `order_id` int(11) NOT NULL DEFAULT 0 COMMENT '订单',
  `order_express_id` int(11) NOT NULL DEFAULT 0 COMMENT '快递包裹',
  `content` varchar(512) NULL DEFAULT NULL COMMENT '内容',
  `change_date` datetime(0) NULL DEFAULT NULL COMMENT '变动时间',
  `status` enum('noinfo','collect','transport','delivery','signfor','refuse','difficulty','invalid','timeout','fail','back') NOT NULL DEFAULT 'noinfo' COMMENT '订单状态:noinfo=暂无信息,collect=已揽件,transport=运输中,delivery=派送中,signfor=已签收,refuse=用户拒收,difficulty=问题件,invalid=无效件,timeout=超时单,fail=签收失败,back=退回',
  `createtime` bigint(16) NULL DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `order_id`(`order_id`) USING BTREE,
  INDEX `order_express_id`(`order_express_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '物流信息';


CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_order_invoice`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` enum('person','company') NULL DEFAULT NULL COMMENT '发票类型:person=个人,company=企事业单位',
  `order_id` int(11) NOT NULL DEFAULT 0 COMMENT '订单',
  `user_id` int(11) NOT NULL DEFAULT 0 COMMENT '用户',
  `name` varchar(255) NULL DEFAULT NULL COMMENT '名称',
  `tax_no` varchar(255) NULL DEFAULT NULL COMMENT '税号',
  `address` varchar(255) NULL DEFAULT NULL COMMENT '单位地址',
  `mobile` varchar(20) NULL DEFAULT NULL COMMENT '手机号码',
  `bank_name` varchar(255) NULL DEFAULT NULL COMMENT '开户银行',
  `bank_no` varchar(255) NULL DEFAULT NULL COMMENT '银行账户',
  `amount` decimal(10, 2) NOT NULL DEFAULT '0.00' COMMENT '金额',
  `download_urls` varchar(2500) NULL DEFAULT NULL COMMENT '发票地址',
  `invoice_amount` decimal(10, 2) NULL DEFAULT NULL COMMENT '开票金额',
  `status` enum('cancel','unpaid','waiting','finish') NOT NULL DEFAULT 'unpaid' COMMENT '状态:cancel=已取消,unpaid=未支付,waiting=等待处理,finish=已开具',
  `finish_time` int(10) NOT NULL DEFAULT 0 COMMENT '开具时间',
  `createtime` bigint(16) NULL DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `user_id`(`user_id`) USING BTREE,
  INDEX `order_id`(`order_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '发票';


CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_order_item`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL DEFAULT 0 COMMENT '订单',
  `user_id` int(11) NOT NULL DEFAULT 0 COMMENT '用户',
  `goods_id` int(11) NOT NULL DEFAULT 0 COMMENT '商品',
  `goods_type` enum('normal','virtual','card') NOT NULL DEFAULT 'normal' COMMENT '商品类型:normal=实体商品,virtual=虚拟商品,card=电子卡密',
  `goods_sku_price_id` int(11) NOT NULL DEFAULT 0 COMMENT '规格',
  `activity_id` int(11) NOT NULL DEFAULT 0 COMMENT '活动',
  `activity_type` varchar(255) NULL DEFAULT NULL COMMENT '活动类型',
  `promo_types` varchar(255) NULL DEFAULT NULL COMMENT '营销类型',
  `item_goods_sku_price_id` int(11) NOT NULL DEFAULT 0 COMMENT '活动规格|积分商城规格',
  `goods_sku_text` varchar(60) NULL DEFAULT NULL COMMENT '规格名',
  `goods_title` varchar(255) NULL DEFAULT NULL COMMENT '商品名称',
  `goods_image` varchar(255) NULL DEFAULT NULL COMMENT '商品图片',
  `goods_original_price` decimal(10, 2) NOT NULL DEFAULT '0.00' COMMENT '商品原价',
  `goods_price` decimal(10, 2) NOT NULL DEFAULT '0.00' COMMENT '商品价格',
  `goods_num` int(10) NOT NULL DEFAULT 0 COMMENT '购买数量',
  `goods_weight` decimal(10, 2) NOT NULL DEFAULT '0.00' COMMENT '商品重量(KG)',
  `discount_fee` decimal(10, 2) NOT NULL DEFAULT '0.00' COMMENT '优惠费用',
  `pay_fee` decimal(10, 2) NOT NULL DEFAULT '0.00' COMMENT '支付金额(不含运费)',
  `dispatch_status` tinyint(3) NOT NULL DEFAULT 0 COMMENT '发货状态:-1=拒收,0=未发货,1=已发货,2=已收货',
  `dispatch_fee` decimal(10, 2) NOT NULL DEFAULT '0.00' COMMENT '发货费用',
  `dispatch_type` varchar(60) NULL DEFAULT NULL COMMENT '发货方式',
  `dispatch_id` int(11) NOT NULL DEFAULT 0 COMMENT '发货模板',
  `aftersale_status` tinyint(4) NOT NULL DEFAULT 0 COMMENT '售后状态:-1=拒绝,0=未申请,1=申请售后,2=售后完成',
  `comment_status` tinyint(4) NOT NULL DEFAULT 0 COMMENT '评价状态:0=未评价,1=已评价',
  `refund_status` tinyint(4) NOT NULL DEFAULT 0 COMMENT '退款状态:0=未退款,1=已同意,2=已完成',
  `refund_fee` decimal(10, 2) NOT NULL DEFAULT '0.00' COMMENT '退款金额',
  `refund_msg` varchar(255) NULL DEFAULT NULL COMMENT '退款原因',
  `order_express_id` int(11) NOT NULL DEFAULT 0 COMMENT '快递包裹',
  `ext` varchar(2048) NULL DEFAULT NULL COMMENT '附加信息',
  `createtime` bigint(16) NULL DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `order_id`(`order_id`) USING BTREE,
  INDEX `user_id`(`user_id`) USING BTREE,
  INDEX `goods_id`(`goods_id`) USING BTREE,
  INDEX `activity_id`(`activity_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '订单商品';


CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_pay`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_type` varchar(60) NOT NULL DEFAULT '' COMMENT '订单类型',
  `order_id` int(11) NOT NULL DEFAULT 0 COMMENT '订单',
  `pay_sn` varchar(60) NOT NULL DEFAULT '' COMMENT '支付单号',
  `user_id` int(11) NOT NULL DEFAULT 0 COMMENT '用户',
  `pay_type` enum('wechat','alipay','money','score','offline') NULL DEFAULT NULL COMMENT '支付方式:wechat=微信支付,alipay=支付宝,money=钱包支付,score=积分支付,offline=线下支付',
  `pay_fee` decimal(10, 2) NOT NULL DEFAULT '0.00' COMMENT '支付金额',
  `real_fee` decimal(10, 2) NOT NULL DEFAULT '0.00' COMMENT '实际金额',
  `transaction_id` varchar(60) NULL DEFAULT NULL COMMENT '交易单号',
  `payment_json` varchar(2048) NULL DEFAULT NULL COMMENT '交易原始数据',
  `paid_time` int(10) NULL DEFAULT NULL COMMENT '交易时间',
  `status` enum('unpaid','paid','refund') NULL DEFAULT NULL COMMENT '支付状态:unpaid=未支付,paid=已支付,refund=已退款',
  `refund_fee` decimal(10, 2) NOT NULL DEFAULT '0.00' COMMENT '已退款金额',
  `createtime` bigint(16) NULL DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `pay_sn`(`pay_sn`) USING BTREE,
  INDEX `order_id`(`order_id`) USING BTREE,
  INDEX `user_id`(`user_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '支付';


CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_pay_config`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NULL DEFAULT NULL COMMENT '名称',
  `type` enum('wechat','alipay') NULL DEFAULT NULL COMMENT '类型:wechat=微信,alipay=支付宝',
  `params` varchar(2500) NULL DEFAULT NULL COMMENT '参数',
  `status` enum('normal','disabled') NOT NULL DEFAULT 'normal' COMMENT '状态:normal=正常,disabled=禁用',
  `createtime` bigint(16) NULL DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) NULL DEFAULT NULL COMMENT '更新时间',
  `deletetime` bigint(16) NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `type`(`type`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '支付配置';


CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_refund`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `refund_sn` varchar(60) NOT NULL DEFAULT '' COMMENT '退款单号',
  `order_id` int(11) NOT NULL DEFAULT 0 COMMENT '订单',
  `pay_id` int(11) NOT NULL DEFAULT 0 COMMENT '支付',
  `pay_type` enum('wechat','alipay','money','score','offline') NULL DEFAULT NULL COMMENT '支付方式:wechat=微信支付,alipay=支付宝,money=钱包支付,score=积分支付,offline=线下支付',
  `refund_fee` decimal(10, 2) NOT NULL DEFAULT '0.00' COMMENT '退款金额',
  `refund_type` varchar(60) NULL DEFAULT NULL COMMENT '退款类型',
  `refund_method` varchar(60) NULL DEFAULT NULL COMMENT '退款方式',
  `status` enum('ing','completed','fail') NULL DEFAULT 'ing' COMMENT '退款状态:ing=退款中,completed=退款完成,fail=退款失败',
  `remark` varchar(255) NULL DEFAULT NULL COMMENT '备注',
  `platform` varchar(60) NULL DEFAULT NULL COMMENT '下单平台',
  `payment_json` varchar(2048) NULL DEFAULT NULL COMMENT '交易原始数据',
  `createtime` bigint(16) NULL DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `refund_sn`(`refund_sn`) USING BTREE,
  INDEX `order_id`(`order_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '退款';


CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_score_sku_price`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `goods_id` int(11) NOT NULL DEFAULT 0 COMMENT '商品',
  `goods_sku_price_id` int(11) NOT NULL DEFAULT 0 COMMENT '规格',
  `stock` int(10) NOT NULL DEFAULT 0 COMMENT '库存',
  `sales` int(10) NOT NULL DEFAULT 0 COMMENT '销量',
  `price` decimal(10, 2) NULL DEFAULT NULL COMMENT '价格',
  `score` int(10) NOT NULL DEFAULT 0 COMMENT '积分',
  `status` enum('up','down') NOT NULL DEFAULT 'up' COMMENT '商品状态:up=上架,down=下架',
  `createtime` bigint(16) NULL DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) NULL DEFAULT NULL COMMENT '更新时间',
  `deletetime` bigint(16) NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `goods_id`(`goods_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '积分商城';


CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_search_history`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT 0 COMMENT '用户',
  `keyword` varchar(255) NOT NULL COMMENT '关键词',
  `createtime` bigint(16) NULL DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '搜索历史';


CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_share`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `spm` varchar(255) NULL DEFAULT NULL COMMENT '原始spm',
  `user_id` int(11) NOT NULL DEFAULT 0 COMMENT '用户',
  `share_id` int(11) NOT NULL DEFAULT 0 COMMENT '分享人',
  `page` varchar(50) NULL DEFAULT NULL COMMENT '分享页面',
  `query` varchar(255) NULL DEFAULT NULL COMMENT '分享页面参数',
  `platform` varchar(50) NULL DEFAULT NULL COMMENT '分享平台',
  `from` varchar(50) NULL DEFAULT NULL COMMENT '分享方式',
  `ext` text NULL COMMENT '附加信息',
  `createtime` bigint(16) NULL DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `user_id`(`user_id`) USING BTREE,
  INDEX `share_id`(`share_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '用户分享记录';


CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_third_oauth`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL DEFAULT 0 COMMENT '管理员ID',
  `user_id` int(11) NOT NULL DEFAULT 0 COMMENT '会员ID',
  `provider` varchar(20) NOT NULL COMMENT '厂商',
  `platform` varchar(20) NOT NULL COMMENT '平台',
  `openid` varchar(50) NOT NULL COMMENT '平台唯一标识',
  `unionid` varchar(50) NULL DEFAULT NULL COMMENT '主体唯一标识',
  `nickname` varchar(255) NULL DEFAULT NULL COMMENT '昵称',
  `avatar` varchar(255) NULL DEFAULT NULL COMMENT '头像',
  `login_num` int(10) NOT NULL DEFAULT 0 COMMENT '使用登录次数',
  `createtime` bigint(16) NULL DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`, `openid`) USING BTREE,
  INDEX `user_id`(`user_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '第三方用户';


CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_trade_order`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` enum('recharge') NULL DEFAULT NULL COMMENT '订单类型:recharge=余额充值',
  `order_sn` varchar(60) NOT NULL DEFAULT '' COMMENT '订单号',
  `user_id` int(11) NOT NULL DEFAULT 0 COMMENT '用户',
  `status` enum('closed','cancel','unpaid','paid','completed') NOT NULL DEFAULT 'unpaid' COMMENT '订单状态:closed=交易关闭,cancel=已取消,unpaid=未支付,paid=已支付,completed=已完成',
  `remark` varchar(255) NULL DEFAULT NULL COMMENT '用户备注',
  `order_amount` decimal(10, 2) NOT NULL DEFAULT '0.00' COMMENT '订单总金额',
  `pay_fee` decimal(10, 2) NOT NULL DEFAULT '0.00' COMMENT '支付总金额',
  `remain_pay_fee` decimal(10, 2) NOT NULL DEFAULT '0.00' COMMENT '剩余支付金额',
  `paid_time` int(10) NULL DEFAULT NULL COMMENT '支付成功时间',
  `ext` varchar(2048) NULL DEFAULT NULL COMMENT '附加信息',
  `platform` enum('H5','App','WechatOfficialAccount','WechatMiniProgram') NULL DEFAULT NULL COMMENT '平台:H5=H5,WechatOfficialAccount=微信公众号,WechatMiniProgram=微信小程序,App=App',
  `createtime` bigint(16) NULL DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) NULL DEFAULT NULL COMMENT '更新时间',
  `deletetime` bigint(16) NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `order_sn`(`order_sn`) USING BTREE,
  INDEX `user_id`(`user_id`) USING BTREE,
  INDEX `status`(`status`) USING BTREE,
  INDEX `createtime`(`createtime`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '交易订单';


CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_user_account`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT 0 COMMENT '用户',
  `type` varchar(20) NOT NULL COMMENT '账户类型:wechat=微信,alipay=支付宝,bank=银行账户',
  `account_name` varchar(255) NULL DEFAULT NULL COMMENT '真实姓名',
  `account_header` varchar(255) NULL DEFAULT NULL COMMENT '账户名',
  `account_no` varchar(255) NULL DEFAULT NULL COMMENT '账号',
  `createtime` bigint(16) NULL DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '提现账户';


CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_user_address`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT 0 COMMENT '用户',
  `is_default` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '默认:0=否,1=是',
  `consignee` varchar(60) NULL DEFAULT NULL COMMENT '收货人',
  `mobile` varchar(20) NULL DEFAULT NULL COMMENT '收货手机',
  `province_name` varchar(60) NULL DEFAULT NULL COMMENT '省份',
  `city_name` varchar(60) NULL DEFAULT NULL COMMENT '城市',
  `district_name` varchar(60) NULL DEFAULT NULL COMMENT '地区',
  `address` varchar(255) NULL DEFAULT NULL COMMENT '详细地址',
  `province_id` int(11) NOT NULL DEFAULT 0 COMMENT '省Id',
  `city_id` int(11) NOT NULL DEFAULT 0 COMMENT '市Id',
  `district_id` int(11) NOT NULL DEFAULT 0 COMMENT '区Id',
  `createtime` bigint(16) NULL DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `user_id`(`user_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '用户收货地址';


CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_user_coupon`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT 0 COMMENT '用户',
  `coupon_id` int(11) NOT NULL DEFAULT 0 COMMENT '优惠券',
  `use_order_id` int(11) NOT NULL DEFAULT 0 COMMENT '使用订单',
  `use_time` int(10) NULL DEFAULT NULL COMMENT '使用时间',
  `createtime` bigint(16) NULL DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `user_id`(`user_id`) USING BTREE,
  INDEX `coupon_id`(`coupon_id`) USING BTREE,
  INDEX `use_time`(`use_time`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '用户优惠券';


CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_user_goods_log`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `goods_id` int(11) NOT NULL DEFAULT 0 COMMENT '商品',
  `user_id` int(11) NOT NULL DEFAULT 0 COMMENT '用户',
  `type` enum('favorite','views') NULL DEFAULT NULL COMMENT '类型:favorite=收藏,views=浏览记录',
  `createtime` bigint(16) NULL DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `goods_id`(`goods_id`) USING BTREE,
  INDEX `user_id`(`user_id`) USING BTREE,
  INDEX `type`(`type`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '用户商品收藏';


CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_user_invoice`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT 0 COMMENT '用户',
  `type` enum('person','company') NULL DEFAULT NULL COMMENT '发票类型:person=个人,company=单位',
  `name` varchar(225) NULL DEFAULT NULL COMMENT '名称',
  `tax_no` varchar(225) NULL DEFAULT NULL COMMENT '税号',
  `address` varchar(225) NULL DEFAULT NULL COMMENT '单位地址',
  `mobile` varchar(20) NULL DEFAULT NULL COMMENT '手机号码',
  `bank_name` varchar(225) NULL DEFAULT NULL COMMENT '开户银行',
  `bank_no` varchar(225) NULL DEFAULT NULL COMMENT '银行账户',
  `createtime` bigint(16) NULL DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `user_id`(`user_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '我的发票';


CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_user_wallet_log`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT 0 COMMENT '用户',
  `type` varchar(10) NOT NULL COMMENT '类型:money=余额,commission=佣金,score=积分',
  `event` varchar(255) NOT NULL COMMENT '事件:money_recharge=充值,money_consume=余额消费,commission_withdraw=提现,commission_transfer=佣金转余额,commission_reward=佣金奖励,score_consume=积分消费,score_sign=积分签到,activity=活动赠送',
  `amount` decimal(10, 2) NOT NULL DEFAULT '0.00' COMMENT '数量',
  `before` decimal(10, 2) NOT NULL DEFAULT '0.00' COMMENT '变动前',
  `after` decimal(10, 2) NOT NULL DEFAULT '0.00' COMMENT '变动后',
  `memo` varchar(255) NULL DEFAULT NULL COMMENT '备注',
  `ext` varchar(2500) NULL DEFAULT NULL COMMENT '扩展信息',
  `oper_type` varchar(20) NOT NULL COMMENT '操作人类型',
  `oper_id` int(11) NOT NULL DEFAULT 0 COMMENT '操作人',
  `createtime` bigint(16) NULL DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '用户资金日志';


CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_wechat_material`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` enum('text','link') NOT NULL COMMENT '类型:text=文字,link=链接',
  `content` varchar(2500) NOT NULL COMMENT '内容',
  `createtime` int(10) NULL DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(10) NULL DEFAULT NULL COMMENT '更新时间',
  `deletetime` int(10) NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '素材管理';


CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_wechat_menu`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NULL DEFAULT NULL COMMENT '菜单名称',
  `rules` text NULL COMMENT '菜单规则',
  `status` int(1) NOT NULL DEFAULT 0 COMMENT '状态:0=未发布,1=已发布',
  `publishtime` int(10) NULL DEFAULT NULL COMMENT '发布时间',
  `createtime` int(10) NULL DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(10) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '微信公众号菜单';


CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_wechat_reply`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `group` enum('keywords','subscribe','default') NOT NULL COMMENT '类型:keywords=关键字回复,subscribe=关注回复,default=默认回复',
  `type` enum('text','link','video','voice','image','news') NULL DEFAULT NULL COMMENT '类型:text=文本,link=链接,video=视频,audio=音频,image=图像,media=图文消息',
  `status` enum('enable','disabled') NOT NULL COMMENT '状态:enable=启用,disabled=禁用',
  `keywords` varchar(255) NULL DEFAULT NULL COMMENT '关键字',
  `content` text NULL COMMENT '回复内容',
  `createtime` int(10) NULL DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(10) NULL DEFAULT NULL COMMENT '更新时间',
  `deletetime` int(10) NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '自动回复';


CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_withdraw`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT 0 COMMENT '提现用户',
  `amount` decimal(10, 2) NOT NULL DEFAULT '0.00' COMMENT '提现金额',
  `paid_fee` decimal(10, 2) NOT NULL DEFAULT '0.00' COMMENT '实际到账',
  `charge_fee` decimal(10, 2) NOT NULL DEFAULT '0.00' COMMENT '手续费',
  `charge_rate` decimal(10, 3) NOT NULL DEFAULT '0.000' COMMENT '手续费率',
  `withdraw_sn` varchar(191) NOT NULL COMMENT '提现单号',
  `withdraw_type` enum('bank','wechat','alipay') NOT NULL COMMENT '提现类型:bank=银行卡,wechat=微信零钱,alipay=支付宝账户',
  `withdraw_info` varchar(255) NULL DEFAULT NULL COMMENT '提现信息',
  `status` tinyint(4) NOT NULL DEFAULT 0 COMMENT '提现状态:-1=已拒绝,0=待审核,1=处理中,2=已处理',
  `platform` enum('H5','App','WechatOfficialAccount','WechatMiniProgram') NULL DEFAULT NULL COMMENT '平台:H5=H5,WechatOfficialAccount=微信公众号,WechatMiniProgram=微信小程序,App=App',
  `payment_json` varchar(2500) NULL DEFAULT NULL COMMENT '交易原始数据',
  `createtime` bigint(16) NULL DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `withdraw_sn`(`withdraw_sn`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '用户提现';


CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_withdraw_log`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `withdraw_id` int(11) NOT NULL DEFAULT 0 COMMENT '提现ID',
  `content` varchar(255) NULL DEFAULT NULL COMMENT '日志内容',
  `oper_type` varchar(50) NULL DEFAULT NULL COMMENT '操作人类型',
  `oper_id` int(11) NOT NULL DEFAULT 0 COMMENT '操作人',
  `createtime` int(10) NULL DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '提现日志';

-- 用户佣金
ALTER TABLE `__PREFIX__user` ADD COLUMN `commission` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '佣金' AFTER `money`;
-- parent_id
ALTER TABLE `__PREFIX__user` ADD COLUMN `parent_user_id` int(11) NULL DEFAULT NULL COMMENT '上级用户' AFTER `verification`;
-- 用户消费累计
ALTER TABLE `__PREFIX__user` ADD COLUMN `total_consume` decimal(10, 2) NULL DEFAULT 0.00 COMMENT '累计消费' AFTER `parent_user_id`;
ALTER TABLE `__PREFIX__user` ADD INDEX `parent_user_id`(`parent_user_id`) USING BTREE;

-- // -- commission code start --
-- 2.0.0 高级版更新 ↓
CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_commission_agent`  (
  `user_id` int(11) NOT NULL COMMENT '用户',
  `level` int(11) NOT NULL COMMENT '分销商等级',
  `apply_info` text NULL COMMENT '申请信息',
  `total_income` decimal(10, 2) NOT NULL DEFAULT '0.00' COMMENT '总收益',
  `child_order_money_0` decimal(10, 2) NOT NULL DEFAULT '0.00' COMMENT '自购/直推分销订单金额',
  `child_order_money_1` decimal(10, 2) NOT NULL DEFAULT '0.00' COMMENT '一级分销订单总金额',
  `child_order_money_2` decimal(10, 2) NOT NULL DEFAULT '0.00' COMMENT '二级分销订单总金额',
  `child_order_money_all` decimal(10, 2) NOT NULL DEFAULT '0.00' COMMENT '团队分销订单总金额',
  `child_order_count_0` int(11) NOT NULL DEFAULT 0 COMMENT '自购/直推分销订单数量',
  `child_order_count_1` int(11) NOT NULL DEFAULT 0 COMMENT '一级分销订单数量',
  `child_order_count_2` int(11) NOT NULL DEFAULT 0 COMMENT '二级分销订单数量',
  `child_order_count_all` int(11) NOT NULL DEFAULT 0 COMMENT '团队分销订单数量',
  `child_agent_count_1` int(11) NOT NULL DEFAULT 0 COMMENT '直推分销商人数',
  `child_agent_count_2` int(11) NOT NULL DEFAULT 0 COMMENT '二级分销商人数',
  `child_agent_count_all` int(11) NOT NULL DEFAULT 0 COMMENT '团队分销商人数',
  `child_agent_level_1` varchar(255) NULL DEFAULT '' COMMENT '一级分销商等级统计',
  `child_agent_level_all` varchar(255) NULL DEFAULT '' COMMENT '团队分销商等级统计',
  `child_user_count_1` int(11) NOT NULL DEFAULT 0 COMMENT '一级用户人数',
  `child_user_count_2` int(11) NOT NULL DEFAULT 0 COMMENT '二级用户人数',
  `child_user_count_all` int(11) NOT NULL DEFAULT 0 COMMENT '团队用户人数',
  `upgrade_lock` tinyint(4) NOT NULL DEFAULT 0 COMMENT '升级锁定:0=不锁定,1=锁定',
  `apply_num` int(11) NOT NULL DEFAULT 0 COMMENT '提交申请次数',
  `level_status` tinyint(4) NOT NULL DEFAULT 0 COMMENT '升级状态:0=不升级,>1=待升级等级',
  `status` varchar(20) NOT NULL DEFAULT 'normal' COMMENT '分销商状态:forbidden=禁用,pending=审核中,freeze=冻结,normal=正常,reject=拒绝',
  `become_time` int(10) NULL DEFAULT NULL COMMENT '成为分销商时间',
  `createtime` bigint(16) NULL DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`user_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '分销商';


CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_commission_goods`  (
  `goods_id` int(11) NOT NULL DEFAULT 0 COMMENT '分销商品',
  `self_rules` tinyint(4) NULL DEFAULT 0 COMMENT '独立设置佣金:0=否,1=是',
  `commission_rules` text NULL COMMENT '佣金设置',
  `status` tinyint(4) NOT NULL COMMENT '状态:0=不参与分销,1=参与分销',
  `commission_config` text NULL COMMENT '独立佣金规则',
  `commission_order_status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '是否计入业绩:0=否,1=是',
  PRIMARY KEY (`goods_id`) USING BTREE,
  UNIQUE INDEX `goods_id`(`goods_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '分销商品';


CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_commission_level`  (
  `level` int(11) NOT NULL COMMENT '权重等级',
  `name` varchar(50) NOT NULL COMMENT '等级名称',
  `image` varchar(255) NULL DEFAULT NULL COMMENT '等级徽章',
  `commission_rules` text NOT NULL COMMENT '佣金比例设置',
  `upgrade_type` tinyint(4) NOT NULL DEFAULT 0 COMMENT '升级方式',
  `upgrade_rules` text NULL COMMENT '升级规则',
  PRIMARY KEY (`level`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '分销商等级';


CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_commission_log`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `agent_id` int(11) NOT NULL DEFAULT 0 COMMENT '分销商',
  `event` varchar(255) NULL DEFAULT NULL COMMENT '事件标识:agent=分销商日志,level=等级变动日志,order=分销业绩,team=团队日志,reward=佣金日志,share=分享日志',
  `remark` varchar(255) NULL DEFAULT NULL COMMENT '备注',
  `oper_type` varchar(60) NULL DEFAULT NULL COMMENT '操作人:admin=管理员,system=系统,user=用户',
  `oper_id` int(11) NOT NULL DEFAULT 0 COMMENT '操作人ID',
  `createtime` bigint(16) NULL DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `agent_id`(`agent_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '分销动态日志';


CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_commission_order`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL DEFAULT 0 COMMENT '订单',
  `self_buy` tinyint(4) NOT NULL DEFAULT 0 COMMENT '是否分销内购:0=分销订单,1=内购订单',
  `order_item_id` int(11) NOT NULL DEFAULT 0 COMMENT '订单商品',
  `buyer_id` int(11) NOT NULL DEFAULT 0 COMMENT '购买人',
  `goods_id` int(11) NOT NULL DEFAULT 0 COMMENT '商品',
  `agent_id` int(11) NOT NULL DEFAULT 0 COMMENT '分销商',
  `amount` decimal(10, 2) NOT NULL DEFAULT '0.00' COMMENT '商品结算金额',
  `reward_type` varchar(20) NOT NULL COMMENT '商品结算方式',
  `reward_event` varchar(20) NOT NULL COMMENT '佣金结算事件',
  `commission_order_status` tinyint(4) NOT NULL DEFAULT 0 COMMENT '分销商业绩:-2=已扣除,-1=已取消,0=不计入,1=已计入',
  `commission_reward_status` tinyint(4) NOT NULL DEFAULT 0 COMMENT '佣金处理状态:-2=已退回,-1=已取消,0=未结算,1=已结算',
  `commission_rules` text NULL COMMENT '执行佣金结算规则',
  `commission_time` int(10) NULL DEFAULT NULL COMMENT '结算时间',
  `createtime` bigint(16) NULL DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `order_id`(`order_id`) USING BTREE,
  INDEX `order_item_id`(`order_item_id`) USING BTREE,
  INDEX `buyer_id`(`buyer_id`) USING BTREE,
  INDEX `goods_id`(`goods_id`) USING BTREE,
  INDEX `agent_id`(`agent_id`) USING BTREE,
  INDEX `createtime`(`createtime`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '分销订单';


CREATE TABLE IF NOT EXISTS `__PREFIX__shopro_commission_reward`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `agent_id` int(11) NOT NULL DEFAULT 0 COMMENT '分销商',
  `buyer_id` int(11) NOT NULL DEFAULT 0 COMMENT '购买人',
  `order_id` int(11) NOT NULL DEFAULT 0 COMMENT '订单',
  `order_item_id` int(11) NOT NULL DEFAULT 0 COMMENT '订单商品',
  `commission_order_id` int(11) NOT NULL DEFAULT 0 COMMENT '分销订单',
  `type` enum('money','score','change','bank','commission') NOT NULL DEFAULT 'commission' COMMENT '打款方式:commission=佣金钱包,money=余额钱包,score=积分,cash=现金(手动打款),change=企业付款到零钱,bank=企业付款到银行卡',
  `commission` decimal(10, 2) NOT NULL DEFAULT '0.00' COMMENT '佣金',
  `original_commission` decimal(10, 2) NOT NULL DEFAULT '0.00' COMMENT '原始佣金',
  `commission_level` tinyint(4) NOT NULL DEFAULT 0 COMMENT '执行层级',
  `agent_level` int(10) NOT NULL DEFAULT 0 COMMENT '执行等级',
  `commission_rules` varchar(255) NULL DEFAULT NULL COMMENT '执行规则',
  `status` tinyint(4) NOT NULL DEFAULT 0 COMMENT '状态:-2=已退回,-1=已取消,0=待入账,1=已入账',
  `commission_time` int(10) NULL DEFAULT NULL COMMENT '结算时间',
  `createtime` int(10) NULL DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(10) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `commission_order_id`(`commission_order_id`) USING BTREE,
  INDEX `agent_id`(`agent_id`) USING BTREE,
  INDEX `buyer_id`(`buyer_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '分销佣金';

-- 2.0.0 高级版更新 ↑
-- // -- commission code end --


-- 3.0.1 更新 ↓
-- 
ALTER TABLE `__PREFIX__shopro_goods` ADD COLUMN `image_wh` varchar(255) NULL DEFAULT NULL COMMENT '主图宽高' AFTER `image`;
-- 3.0.1 更新 ↑

-- 3.0.2 更新 ↓
-- 
ALTER TABLE `__PREFIX__shopro_pay` ADD COLUMN `buyer_info` varchar(255) NULL DEFAULT NULL COMMENT '交易用户' AFTER `transaction_id`;
-- 3.0.2 更新 ↑