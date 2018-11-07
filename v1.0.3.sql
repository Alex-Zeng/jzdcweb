-- 修改企业认证审核数据表
CREATE TABLE `ent_company_audit` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '表的主键ID',
  `company_id` int(11) NOT NULL DEFAULT '0' COMMENT '关联已认证公司信息表的主键字段，提供第二次修改时的关联公司功能，如果用户第一次提交则为0。',
  `company_name` varchar(30) NOT NULL DEFAULT '' COMMENT '公司名称',
  `enterprise_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '企业性质，枚举值。未知（Unknown=0）；有限责任公司（Ltd=1）；中外合资企业（SinoForeign=2）；个体工商户（Privately=3）；合伙企业（Partnership=4）。',
  `reg_capital` varchar(20) NOT NULL DEFAULT '' COMMENT '公司的注册资金',
  `address` varchar(100) NOT NULL DEFAULT '' COMMENT '公司地址',
  `telephone` varchar(30) NOT NULL DEFAULT '' COMMENT '公司联系电话',
  `contacts` varchar(30) NOT NULL DEFAULT '' COMMENT '公司联系人',
  `contact_phone` varchar(15) NOT NULL DEFAULT '' COMMENT '公司联系人的电话或手机',
  `legal_representative` varchar(10) NOT NULL DEFAULT '' COMMENT '公司的法人代表',
  `organization_code_uri` varchar(200) NOT NULL DEFAULT '' COMMENT '组织机构代码的URI',
  `tax_registration_uri` varchar(200) NOT NULL DEFAULT '' COMMENT '税务登记许可证的URI',
  `business_licence_uri` varchar(200) NOT NULL DEFAULT '' COMMENT '保存公司营业执照的URI',
  `power_attorney_uri` varchar(200) NOT NULL DEFAULT '' COMMENT '保存公司授权委托书的URI',
  `agent_id_card_uri` varchar(200) NOT NULL DEFAULT '' COMMENT '保存代办人身份证的URI',
  `description` varchar(150) NOT NULL DEFAULT '' COMMENT '审核拒绝或成功通过的说明或备注。',
  `state` tinyint(1) NOT NULL DEFAULT '0' COMMENT '审核状态，枚举值。提交的（Submitted=1）；拒绝（Rejected=2）；通过（Passed=3）。',
  `audit_time` bigint(13) NOT NULL DEFAULT '0' COMMENT '审核时间',
  `created_user_id` int(11) NOT NULL DEFAULT '0' COMMENT '关联用户表的主键ID',
  `created_user` varchar(30) NOT NULL DEFAULT '' COMMENT '创建记录的用户名',
  `created_time` bigint(13) NOT NULL DEFAULT '0' COMMENT '创建记录的时间',
  `last_modified_user_id` int(11) NOT NULL DEFAULT '0' COMMENT '关联用户表的主键ID',
  `last_modified_user` varchar(30) NOT NULL DEFAULT '' COMMENT '最后修改人的用户名',
  `last_modified_time` bigint(13) NOT NULL DEFAULT '0' COMMENT '最后修改的时间',
  `is_deleted` bit(1) NOT NULL DEFAULT b'0' COMMENT '是否删除标识',
  `deleted_user` varchar(30) NOT NULL DEFAULT '' COMMENT '删除人的用户名',
  `deleted_time` bigint(13) NOT NULL DEFAULT '0' COMMENT '删除的时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='公司审核明细';

-- 企业信息数据表
CREATE TABLE `ent_company` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '表的主键ID',
  `tenant_id` int(11) NOT NULL DEFAULT '0' COMMENT '关联租户表的主键ID',
  `company_name` varchar(30) NOT NULL DEFAULT '' COMMENT '公司名称',
  `logo_uri` varchar(200) not null default '' comment '公司logo',
  `enterprise_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '企业性质，枚举值。未知（Unknown=0）；有限责任公司（Ltd=1）；中外合资企业（SinoForeign=2）；个体工商户（Privately=3）；合伙企业（Partnership=4）。',
  `reg_capital` varchar(20) NOT NULL DEFAULT '' COMMENT '公司的注册资金',
  `address` varchar(100) NOT NULL DEFAULT '' COMMENT '公司地址',
  `telephone` varchar(30) NOT NULL DEFAULT '' COMMENT '公司联系电话',
  `contacts` varchar(30) NOT NULL DEFAULT '' COMMENT '公司联系人',
  `contact_phone` varchar(15) NOT NULL DEFAULT '' COMMENT '公司联系人的电话或手机',
  `legal_representative` varchar(10) NOT NULL DEFAULT '' COMMENT '公司的法人代表',
  `organization_code_uri` varchar(200) NOT NULL DEFAULT '' COMMENT '组织机构的URI',
  `tax_registration_uri` varchar(200) NOT NULL DEFAULT '' COMMENT '税务登记许可证的URI',
  `business_licence_uri` varchar(200) NOT NULL DEFAULT '' COMMENT '保存公司营业执照的URI',
  `power_attorney_uri` varchar(200) NOT NULL DEFAULT '' COMMENT '保存公司授权委托书的URI',
  `agent_id_card_uri` varchar(200) NOT NULL DEFAULT '' COMMENT '保存代办人身份证的URI',
  `responsible_user_id` int(11) NOT NULL DEFAULT '0' COMMENT '关联用户表的主键ID',
  `audit_state` tinyint(1) NOT NULL DEFAULT '0' COMMENT '审核状态，枚举值。拒绝（Rejected=2）；通过（Passed=3）。',
  `remarks` varchar(100) NOT NULL DEFAULT '' COMMENT '备注。',
  `created_user_id` int(11) NOT NULL DEFAULT '0' COMMENT '关联用户表的主键ID',
  `created_user` varchar(30) NOT NULL DEFAULT '' COMMENT '创建记录的用户名',
  `created_time` bigint(13) NOT NULL DEFAULT '0' COMMENT '创建记录的时间',
  `last_modified_user_id` int(11) NOT NULL DEFAULT '0' COMMENT '关联用户表的主键ID',
  `last_modified_user` varchar(30) NOT NULL DEFAULT '' COMMENT '最后修改人的用户名',
  `last_modified_time` bigint(13) NOT NULL DEFAULT '0' COMMENT '最后修改的时间',
  `is_deleted` bit(1) NOT NULL DEFAULT b'0' COMMENT '是否删除标识',
  `deleted_user` varchar(30) NOT NULL DEFAULT '' COMMENT '删除人的用户名',
  `deleted_time` bigint(13) NOT NULL DEFAULT '0' COMMENT '删除的时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='公司信息';

-- 企业组织信息数据表
CREATE TABLE `ent_organization` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '表的主键ID',
  `tenant_id` int(11) NOT NULL DEFAULT '0' COMMENT '关联租户表的主键ID',
  `company_id` int(11) NOT NULL DEFAULT '0' COMMENT '关联公司信息表的主键ID',
  `parent_id` int(11) NOT NULL DEFAULT '0' COMMENT '自联表的主键ID',
  `org_name` varchar(30) NOT NULL DEFAULT '' COMMENT '部门/机构的信息',
  `level` tinyint(4) NOT NULL DEFAULT '0' COMMENT '当前部门/机构所在层级，默认应该为1，第一级。',
  `depth_path` varchar(200) NOT NULL DEFAULT '' COMMENT '如当前部门/机构级别为4，即该部门/机构为第4层的分类。\r\n            该字段保存格式应为：/1/2/3/4',
  `remarks` varchar(200) NOT NULL DEFAULT '' COMMENT '备注。',
  `created_user_id` int(11) NOT NULL DEFAULT '0' COMMENT '关联用户表的主键ID',
  `created_user` varchar(30) NOT NULL DEFAULT '' COMMENT '创建记录的用户名',
  `created_time` bigint(13) NOT NULL DEFAULT '0' COMMENT '创建记录的时间',
  `last_modified_user_id` int(11) NOT NULL DEFAULT '0' COMMENT '关联用户表的主键ID',
  `last_modified_user` varchar(30) NOT NULL DEFAULT '' COMMENT '最后修改人的用户名',
  `last_modified_time` bigint(13) NOT NULL DEFAULT '0' COMMENT '最后修改的时间',
  `is_deleted` bit(1) NOT NULL DEFAULT b'0' COMMENT '是否删除标识',
  `deleted_user` varchar(30) NOT NULL DEFAULT '' COMMENT '删除人的用户名',
  `deleted_time` bigint(13) NOT NULL DEFAULT '0' COMMENT '删除的时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='公司组织架构';

-- 企业邀请码信息数据表
CREATE TABLE `ent_code` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '表的主键ID',
  `phone` varchar(15) NOT NULL DEFAULT '' COMMENT '用户手机',
  `company_id` int(11) NOT NULL DEFAULT '0' COMMENT '企业ID',
  `organization_id` int(11) NOT NULL DEFAULT '0' COMMENT '部门ID',
  `code` varchar(10) NOT NULL DEFAULT '' COMMENT '验证码',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `send_times` int(11) NOT NULL DEFAULT '0' COMMENT '发送次数',
  `used` bit(1) NOT NULL DEFAULT b'0' COMMENT '是否使用0否1是',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='企业邀请验证码';


-- 更新企业认证信息数据
INSERT INTO `ent_company_audit` ( `id`, `company_id`, `company_name`, `enterprise_type`, `reg_capital`, `address`, `telephone`, `contacts`, `contact_phone`, `legal_representative`, `tax_registration_uri`, `organization_code_uri`, `business_licence_uri`, `power_attorney_uri`, `agent_id_card_uri`, `description`, `state`, `created_user_id`, `created_user`, `created_time`, `last_modified_user_id`, `last_modified_time` )
SELECT B.id,CASE WHEN A.`status` = 2 THEN B.id ELSE 0 END, CASE WHEN A.company_name IS NULL THEN '' ELSE A.company_name END, CASE WHEN A.`ent_property` = '股份有限公司' THEN 1 WHEN A.`ent_property` = '中外合资企业' THEN 2 WHEN A.`ent_property` = '个体工商户' THEN 3 WHEN A.`ent_property` = '合伙企业' THEN 4 ELSE 0 END AS CCC, A.reg_capital, A.detail_address, A.ent_phone, CASE WHEN B.contact IS NULL THEN '' ELSE B.contact END, A.contact_mobile, A.legal_representative, A.tax_registration_cert, A.org_structure_code_permits, A.business_license, A.power_attorney, A.agent_identity_card, A.refuse_reason, CASE WHEN A.`status` = 2 THEN 3 WHEN A.`status` = 3 THEN 2 ELSE A.`status` END, A.writer, CASE WHEN B.username IS NULL THEN '' ELSE B.username END, A.write_time, A.editor, A.edit_time FROM `jzdc_form_user_cert` AS A LEFT JOIN `jzdc_index_user` AS B ON A.writer = B.id;

-- 更新时间为毫秒
UPDATE `ent_company_audit` SET audit_time = audit_time*1000,created_time = created_time*1000,last_modified_time = last_modified_time*1000;



-- 更新企业数据信息表

INSERT INTO `ent_company` ( `id`,`logo_uri`, `company_name`, `enterprise_type`, `reg_capital`, `address`, `telephone`, `contacts`, `contact_phone`, `legal_representative`, `tax_registration_uri`, `organization_code_uri`, `business_licence_uri`, `power_attorney_uri`, `agent_id_card_uri`, `responsible_user_id`, `audit_state`, `remarks`, `created_user_id`, `created_user`, `created_time`, `last_modified_user_id`, `last_modified_time` )
SELECT B.id,B.icon, CASE WHEN B.real_name IS NULL THEN '' ELSE B.real_name END, CASE WHEN A.`ent_property` = '股份有限公司' THEN 1 WHEN A.`ent_property` = '中外合资企业' THEN 2 WHEN A.`ent_property` = '个体工商户' THEN 3 WHEN A.`ent_property` = '合伙企业' THEN 4 ELSE 0 END AS CCC, A.reg_capital, A.detail_address, A.ent_phone, CASE WHEN B.contact IS NULL THEN '' ELSE B.contact END, A.contact_mobile, A.legal_representative, A.tax_registration_cert, A.org_structure_code_permits, A.business_license, A.power_attorney, A.agent_identity_card, A.writer, CASE WHEN A.`status` = 2 THEN 3 WHEN A.`status` = 3 THEN 2 ELSE A.`status` END, A.refuse_reason, A.writer, CASE WHEN B.username IS NULL THEN '' ELSE B.username END, A.write_time, A.editor, A.edit_time FROM `jzdc_form_user_cert` AS A LEFT JOIN `jzdc_index_user` AS B ON A.writer = B.id;
-- 更新时间为毫秒
UPDATE `ent_company` SET created_time = created_time * 1000,last_modified_time = last_modified_time * 1000;



-- index_user表新增字段  tenant_id,company_id,organization_id
ALTER TABLE `jzdc_index_user` ADD COLUMN `tenant_id` int(11) NOT NULL DEFAULT 0 COMMENT '关联租户表的主键ID',
       ADD COLUMN `company_id` int(11) NOT NULL DEFAULT 0 COMMENT '关联公司信息表的主键ID',
	   ADD COLUMN `organization_id` int(11) NOT NULL DEFAULT 0 COMMENT '关联组织架构信息表的主键',
	   ADD COLUMN `remarks` varchar(100) NOT NULL DEFAULT '' COMMENT '用户备注';

-- 组织机构数据更新
INSERT INTO `ent_organization`(id,company_id,org_name) SELECT id,id,'未设置' FROM jzdc_index_user;

-- 更新user表company_id
UPDATE `jzdc_index_user` AS A LEFT JOIN `jzdc_form_user_cert` AS B ON A.id = B.writer SET A.company_id = A.id WHERE (A.`group` = 4 OR A.`group` = 5) AND B. STATUS = 2;
-- 更新user表organization_id
UPDATE `jzdc_index_user` SET organization_id = company_id;


-- 订单表添加字段
ALTER TABLE `jzdc_mall_order` ADD COLUMN `created_user_id`  int(11) NOT NULL DEFAULT 0 COMMENT '用户ID',ADD COLUMN `created_user`  varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '用户名称';
-- 更新数据
UPDATE `jzdc_mall_order` SET created_user_id = buyer_id,created_user=buyer;

-- 商品数据表新增审核时间
ALTER TABLE `sm_product` ADD COLUMN `audit_time`  int(11) NOT NULL DEFAULT 0 COMMENT '审核时间';
UPDATE `sm_product` SET audit_time = created_time;



alter table jzdc_factoring add company_id int not null default 0 comment '企业ID';
UPDATE jzdc_factoring set company_id = user_id;

-- 新增招商信息数据表
CREATE TABLE `fb_merchant` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '企业名称',
  `contacts` varchar(50) NOT NULL DEFAULT '' COMMENT '联系人',
  `contact_num` varchar(15) NOT NULL DEFAULT '' COMMENT '联系电话',
  `created_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='企业招商信息数据表';