-- Banner模块
-- jzdc_slider_img数据表新增type、status字段
ALTER TABLE `jzdc_slider_img` ADD COLUMN `type` tinyint(1) DEFAULT '1' COMMENT '1=pc 2= app 3=微信',ADD COLUMN `status`  tinyint(1) DEFAULT 0 COMMENT '状态显示',ADD COLUMN `path`  varchar(255) DEFAULT 0 COMMENT '';
-- 更新数据
UPDATE `jzdc_slider_img` SET path = CONCAT(id,'.jpg');

UPDATE `jzdc_slider_img` SET status = 1,type = 3 WHERE group_id = 27;

-- Notice模块
-- 新增 jzdc_notice数据表

CREATE TABLE `jzdc_notice` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL COMMENT '标题',
  `summary` text NOT NULL COMMENT '摘要',
  `content` text NOT NULL COMMENT '公告内容',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态 1=显示 0=屏蔽',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `release_time` int(11) NOT NULL DEFAULT '0' COMMENT '发布时间',
  `create_by` int(11) NOT NULL DEFAULT '0' COMMENT '创建人',
  `edit_by` int(11) unsigned DEFAULT '0' COMMENT '更新者',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='平台公告数据表';

-- 商品收藏模块
-- jzdc_mall_favorite新增user_id字段
    ALTER TABLE `jzdc_mall_favorite` ADD COLUMN `user_id` int(11) DEFAULT '0' COMMENT '用户编号';
-- 更新历史数据
   UPDATE `jzdc_mall_favorite` AS A LEFT JOIN jzdc_index_user AS B ON A.username =   B.username SET A.user_id = B.id WHERE B.id is not null;

-- 收货地址
-- jzdc_mall_receiver新增user_id字段
   ALTER TABLE `jzdc_mall_receiver` ADD COLUMN `user_id` int(11) DEFAULT '0' COMMENT '用户编号';
-- 更新历史数据
   UPDATE `jzdc_mall_receiver` AS A LEFT JOIN jzdc_index_user AS B ON A.username = B.username  SET A.user_id = B.id        WHERE B.id is not null;

-- 商品分类新增path字段
   ALTER TABLE `jzdc_mall_type` ADD COLUMN `path` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT '' COMMENT '图标路径';
-- 更新历史数据
   UPDATE jzdc_mall_type SET path = CONCAT(id,'.png');

-- 新增用户搜索记录表
   CREATE TABLE `jzdc_user_search_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT '0',
  `keyword` varchar(255) DEFAULT '' COMMENT '关键词',
  `type` tinyint(1) DEFAULT NULL COMMENT '0=商品 1=供应商',
  `times` int(11) DEFAULT '1' COMMENT '次数',
  `create_time` int(11) DEFAULT '0',
  `update_time` int(11) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

-- jzdc_mall_order新增buyer_id字段
  ALTER TABLE `jzdc_mall_order` ADD COLUMN `buyer_id` int(11) DEFAULT 0 COMMENT '购买用户ID' AFTER `buyer_comment`;
-- 更新历史数据
  UPDATE `jzdc_mall_order` AS A LEFT JOIN jzdc_index_user AS B ON A.buyer = B.username SET A.buyer_id = B.id  WHERE B.id IS NOT NULL;

-- jzdc_mall_order_goods新增buyer_id字段
  ALTER TABLE `jzdc_mall_order_goods` ADD COLUMN `buyer_id` int(11) DEFAULT 0 COMMENT '购买用户编号' AFTER `cost_price`;
-- 更新历史数据
  UPDATE `jzdc_mall_order_goods` AS A LEFT JOIN jzdc_index_user AS B ON A.buyer   =B.username  SET A.buyer_id = B.id  WHERE B.id IS NOT NULL;

-- jzdc_menu_menu新增path字段
  ALTER TABLE `jzdc_menu_menu` ADD COLUMN `path` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT '' COMMENT '图标路径' AFTER `visible`;
-- 更新历史数据
  UPDATE jzdc_menu_menu  SET path = CONCAT(id,'.png');

-- jzdc_mall_cart数据表新增user_id字段
  ALTER TABLE `jzdc_mall_cart` ADD COLUMN `user_id` int(11) DEFAULT '0' COMMENT '用户编号';
-- 更新历史数据
 UPDATE `jzdc_mall_cart` AS A LEFT JOIN jzdc_index_user AS B ON A.username =  B.username SET A.user_id = B.id  WHERE B.id IS NOT NULL;

-- 用户订单消息数据表
DROP TABLE IF EXISTS `jzdc_order_msg`;
CREATE TABLE `jzdc_order_msg`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT '',
  `content` text CHARACTER SET utf8 COLLATE utf8_general_ci,
  `order_no` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT '',
  `order_id` int(11) DEFAULT 0,
  `user_id` int(11) UNSIGNED  DEFAULT '0',
  `create_time` int(11) DEFAULT 0,
  `is_delete` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- 验证码数据表
DROP TABLE IF EXISTS `jzdc_captcha`;
CREATE TABLE `jzdc_captcha`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT '',
  `verify_code` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT '',
  `verify_time` int(11) DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 7 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- 短信验证码
DROP TABLE IF EXISTS `jzdc_code`;
CREATE TABLE `jzdc_code`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `phone` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
  `type` tinyint(1) NOT NULL DEFAULT 0,
  `code` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
  `create_time` int(11) UNSIGNED NOT NULL,
  `expire_time` int(11) UNSIGNED DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '短信验证码数据表' ROW_FORMAT = Compact;

-- 购物清单数据表新增goods_specifications_id,goods_id字段
  ALTER TABLE `jzdc_mall_cart` ADD COLUMN `goods_specifications_id` int(11) NOT NULL DEFAULT 0,ADD COLUMN `goods_id` int(11) NOT NULL DEFAULT 0 COMMENT '商品编号';

-- 新增用户规格数据表
  CREATE TABLE `jzdc_user_goods_specifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT '0',
  `goods_id` int(11) DEFAULT '0',
  `specifications_id` int(11) DEFAULT '0',
  `specifications_name` varchar(255) DEFAULT '',
  `specifications_no` varchar(255) DEFAULT '',
  `create_time` int(11) DEFAULT '0',
  `update_time` int(11) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- order_goods新增字段
ALTER TABLE `jzdc_mall_order_goods` ADD COLUMN `specifications_name` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT '',ADD COLUMN `specifications_no` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT '';

-- jzdc_form_user_cert新增字段 power_attorney
ALTER TABLE `jzdc_form_user_cert` ADD COLUMN `power_attorney` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT '' COMMENT '委托书';

-- jzdc_mall_receiver新增is_default字段
ALTER TABLE `jzdc_mall_receiver` ADD COLUMN `is_default` tinyint(1) NOT NULL DEFAULT '0';

-- 邮箱验证码数据表
CREATE TABLE `jzdc_email_code` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(50) NOT NULL DEFAULT '',
  `type` tinyint(1) NOT NULL DEFAULT '0',
  `code` varchar(10) NOT NULL DEFAULT '',
  `create_time` int(11) unsigned NOT NULL,
  `expire_time` int(11) unsigned DEFAULT '0',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;

-- 收货地址标签数据表
CREATE TABLE `jzdc_mall_receiver_tag` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `time` bigint(12) NOT NULL DEFAULT '0' COMMENT '最后编辑时间',
  `tag` varchar(100) DEFAULT NULL COMMENT '标签',
  `user_id` int(11) DEFAULT '0' COMMENT '用户编号',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='买家收货地址标签';

-- 未读消息字段
ALTER TABLE `jzdc_index_user` ADD COLUMN `unread` int(11) DEFAULT 0 COMMENT '未读消息',ADD COLUMN `contact` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT '' COMMENT '联系人';
-- 修改订单号字段类型
ALTER TABLE `jzdc_mall_order` MODIFY COLUMN `out_id` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '订单外部编号';


-- 更新收货人地址detail字段
UPDATE jzdc_mall_receiver AS A LEFT JOIN jzdc_index_area AS B ON A.area_id = B.id  SET A.detail = CONCAT( B.NAME, A.detail ) WHERE B.id IS NOT NULL AND B.level=5;
-- 更新收货人地址area_id字段
UPDATE jzdc_mall_receiver AS A LEFT JOIN jzdc_index_area AS B ON A.area_id = B.id SET A.area_id = B.upid WHERE B.id IS NOT NULL AND B.level=5;

-- menu_menu表新增flag,type_id字段
ALTER TABLE `jzdc_menu_menu` ADD COLUMN `type_id` int(11) DEFAULT 0 COMMENT '分类ID',ADD COLUMN `flag` tinyint(1) DEFAULT 0 COMMENT '标识';
-- 初始化数据操作？？

-- 新增计数器
CREATE TABLE `jzdc_counter` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_count` int(11) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
INSERT INTO `jzdc_counter`(`id`,`order_count`) VALUES(1,1);

-- 添加订单预计到达时间
ALTER TABLE `jzdc_mall_order` ADD COLUMN `estimated_time` int(11) DEFAULT 0 COMMENT '预计到达时间',ADD COLUMN `service_type` tinyint(1) DEFAULT 0 COMMENT '服务类型 1=退货 2=换货 3=维修';
-- 添加商品售后状态
ALTER TABLE `jzdc_mall_order_goods` ADD COLUMN `service_type` tinyint(1) DEFAULT 0 COMMENT '售后1=退货 2=换货 3=维修​';

-- 线上操作 已执行
-- ALTER TABLE `jzdcprd`.`jzdc_form_fin_service` ADD COLUMN `type` tinyint(1) DEFAULT 0;

-- jzdc_mall_order_goods新增buyer_id字段
ALTER TABLE `jzdc_mall_order_goods` ADD COLUMN `s_info` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT '' COMMENT '商品规格';

-- 添加订单确认发货时间
ALTER TABLE `jzdc_mall_order` ADD COLUMN `confirm_delivery_time` int(11) DEFAULT 0 COMMENT '确认发货时间';

-- jzdc_menu_menu type_id和flag字段更新



alter table jzdc_mall_type add push tinyint not null default 0 comment '推荐，数值越高推荐级别越高';
alter table jzdc_mall_goods add push tinyint not null default 0 comment '推荐，数值越高推荐级别越高';


-- 更新联系人字段
UPDATE `jzdc_index_user` AS A LEFT JOIN jzdc_form_user_cert AS B ON A.id =   B.writer SET A.contact = B.contact_point WHERE B.id is not null;


-- 收藏夹分类增加字段以及对已加入收藏夹数据进行一个更新操作
alter table jzdc_mall_favorite add `type_id` int not null default 0 comment '分类Id';
update jzdc_mall_favorite as f left join jzdc_mall_goods as g on f.goods_id=g.id set f.type_id=g.type where g.type>0


-- 版本发布
create table `jzdc_version` (
  `version_id` int primary key auto_increment comment '日志id',
  `title` varchar(20) not null default '' comment '标题',
  `app_name` varchar(30) not null default '' comment 'app包名称',
  `force_version` varchar(10) not null default '' comment '小于该版本号必须强制更新',
  `content` varchar(2000) not null default '' comment '更新内容',
  `up_time` int not null default '0' comment '上线时间',
  `add_time` int not null default '0' comment '添加时间',
  `is_del` tinyint(4) not null default '1' comment '是否删除1不删除2已删除'
) engine=innodb default charset=utf8 comment='版本日志表';