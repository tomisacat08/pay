<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/19 0019
 * Time: 19:43
 */

namespace app\agent\service;

use app\agent\model\Agent;
use app\agent\model\Member;
use app\agent\model\Merchant;

/**
 * 资金service类
 * Class MoneyService
 * @package app\agent\service
 */
class MoneyService
{
    //计算订单各项金额 、手续费
    public function reckonMoney( $money,$merchantId,$agentId,$memberId )
    {
        $merchantRatio = Merchant::where('id',$merchantId)->value('poundage_ratio');
        $agentRatio = Agent::where('id',$agentId)->value('poundage_ratio');
        $memberRatio = Member::where('id',$memberId)->value('poundage_ratio');

        //商户手续费比例
        $merchant_poundage_ratio = bcdiv($merchantRatio,100,5);
        //代理商手续费比例
        $agent_poundage_ratio = bcdiv($agentRatio,100,5);
        //收款员手续费比例
        $member_poundage_ratio = bcdiv($memberRatio,100,5);

        //返款金额
        $rebate_money = bcmul( $money,bcsub(1,$member_poundage_ratio,5),2);
        //会员手续费
        $member_fee_money = bcmul( $money, $member_poundage_ratio,2);
        //代理商手续费
        $agent_fee_money = bcmul( $money, bcsub($agent_poundage_ratio, $member_poundage_ratio,5),2);
        //平台收益
        $platform_fee_money = bcmul( $money,bcsub($merchant_poundage_ratio, $agent_poundage_ratio,5),2);
        //商户实际得到金额
        $merchant_money = bcmul( $money,bcsub(1,$merchant_poundage_ratio,5),2);

        return [
            'return_money' => $rebate_money,
            'member_fee_money' => $member_fee_money,
            'agent_fee_money' => $agent_fee_money,
            'platform_fee_money' => $platform_fee_money,
            'merchant_money' => $merchant_money,
        ];
    }

}
