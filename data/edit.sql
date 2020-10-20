# noinspection SqlNoDataSourceInspectionForFile

# 2019年4月23日10:22:56, 新增代理单点登录,踢出其他用户 - hmz
ALTER TABLE `pay`.`pay_agent`
ADD COLUMN `agent_token` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '登录token' AFTER `update_time`;
