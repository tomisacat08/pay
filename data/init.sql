UPDATE `pay`.`pay_agent` SET `settlement_money` = 0 , `return_money` = 0 , balance = 0 , total_per_money = 0 , `usable_limit` = total_limit;
UPDATE `pay`.`pay_member` SET `rebate_money` = 0, `rebate_fee_money` = 0, `return_money` = 0, `money` = 0, `usable_limit` = total_limit;
UPDATE `pay`.`pay_merchant` SET `balance` = 0, `frozen_money` = 0, `order_num` = 0, `total_turnover` = 0;
UPDATE `pay`.`pay_platform` SET `money` = 0 WHERE `id` = 1;
DELETE FROM `pay`.`pay_member_images` WHERE `order_id` != 0;
truncate table `pay_admin_user_action`;
truncate table `pay_agent_account_log`;
truncate table `pay_agent_allot_log`;
truncate table `pay_agent_gold_log`;
truncate table `pay_agent_money_log`;
truncate table `pay_agent_user_action`;
truncate table `pay_agent_withdraw`;
truncate table `pay_member_money_log`;
truncate table `pay_merchant_money_log`;
truncate table `pay_merchant_order`;
truncate table `pay_merchant_order_log`;
truncate table `pay_merchant_withdraw`;
truncate table `pay_merchant_withdraw_audit`;
truncate table `pay_notice`;
truncate table `pay_platform_money_log`;
truncate table `pay_platform_withdraw`;
truncate table `pay_settlement_task`;
truncate table `pay_withdraw_operation_log`;