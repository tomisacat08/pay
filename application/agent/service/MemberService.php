<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/16 0016
 * Time: 11:31
 */

namespace app\agent\service;


use app\agent\model\Agent;
use app\agent\model\AgentAccountLog;
use app\agent\model\AgentMoneyLog;
use app\agent\model\MemberGroup;
use app\agent\model\Member;
use app\agent\model\MemberMoneyLog;
use app\agent\model\Merchant;
use app\agent\model\MerchantMoneyLog;
use app\agent\model\MerchantOrder;
use app\agent\model\Platform;
use app\agent\model\PlatformMoneyLog;
use app\api\service\MerchantCallbakService;
use app\api\swoole\SwooleClientService;
use app\model\MemberImages;
use think\Db;
use think\Exception;

class MemberService
{
    private $error;
    //商户订单模型
    private $merchantOrderModel;
    //收款员模型
    private $memberModel;
    //商户模型
    private $merchantModel;
    //代理商模型
    private $agentModel;

    public function getError()
    {
        return $this->error;
    }

    /**
     * 设置错误信息
     * @param $error
     */
    private function setError($error)
    {
        empty($this->error) && $this->error = $error;
    }

    /**
     * 是否存在错误
     * @return bool
     */
    public function hasError()
    {
        return !empty($this->error);
    }

    /**
     * 修改组状态 同时修改 会员 接单 登录状态
     * @param $group_id
     * @param $status
     * @param $agent_id
     * @return bool
     * @throws \think\exception\DbException
     */
    public function changeStatusGroup($group_id, $status, $agent_id)
    {
        $memberGroup = MemberGroup::get(['agent_id' => $agent_id, 'id' => $group_id]);
        if (empty($memberGroup)) {
            $this->setError('参数错误数据不存在');
            return false;
        }
        //修改组状态
        Db::startTrans();
        try {
            $memberModel = new Member();
            //会员id
            $userIds = Member::getMemberId($agent_id,$group_id);
            if ($memberGroup['status'] == 1 && $status == 2) {//当前是开启 设为禁用
                $memberGroup->save(['status' => $status]);
                //设置组成员不能接单 不能登录
                $memberModel->whereIn('id', $userIds)->where('agent_id', '=', $agent_id)->setField('is_pass', 2);
                $memberModel->whereIn('id', $userIds)->where('agent_id', '=', $agent_id)->setField('status', 2);
                //清除user-token
                $userToken = $memberModel->whereIn('id', $userIds)
                    ->field('user_token')
                    ->where('agent_id', '=', $agent_id)
                    ->select();
                foreach ($userToken as $k=>$v)
                {
                    cache('user-token:' . $v['user_token'],null);
                }
            }
            //设为开启
            if ($memberGroup['status'] == 2 && $status == 1) {
                $memberGroup->save(['status' => $status]);
                $memberModel->whereIn('id', $userIds)->where('agent_id', '=', $agent_id)->setField('is_pass', 1);
                $memberModel->whereIn('id', $userIds)->where('agent_id', '=', $agent_id)->setField('status', 1);
            }
            Db::commit();
            return true;
        } catch(\Exception $e) {
            Db::rollback();
            $this->setError('操作失败' . $e->getMessage());
            return false;
        }
    }


    /**
     * 点击确认收款 记录各账户资金 日志
     * @param $orderId
     * @param $help -标记是代理商后台确认收款 则修改为补单记录
     * @return bool
     * @throws \think\exception\DbException
     */
    public function confirm($orderId,$help = false)
    {
        Db::startTrans();
        try {
            $this->merchantOrderModel = MerchantOrder::lock(true)->find($orderId);

            if (empty($this->merchantOrderModel)) {
                abort(500,'参数异常,请重试');
            }

            if($this->merchantOrderModel->status != 2){
                abort(500,'订单状态异常,请确认后再提交!');
            }

            if($this->merchantOrderModel->pay_status == 2){
                abort(500,'订单已收款成功,请勿重复提交!');
            }

            $this->merchantModel = Merchant::lock(true)->find($this->merchantOrderModel->merchant_id);
            $this->memberModel = Member::lock(true)->find($this->merchantOrderModel->member_id);
            $this->agentModel = Agent::lock(true)->find($this->merchantOrderModel->agent_id);
            $time = time();

            //普通会员
            $data = [
                'status' => 3,
                'pay_status' => 2,
                'confirm_time' => $time
            ];
            //帮助确认字段
            if($help === true){
                $data['is_help_confirm_order'] = 1;
                $confirmTimes = config( 'agent_confirm_time_out' );
            }else{
                //会员超时确认间隔
                $confirmTimes = config( 'confirm_time_out' );
            }

            if($confirmTimes && time() - $this->merchantOrderModel->getData('create_time') > $confirmTimes){
                abort(500,'订单确认时间已超时,请联系客服补单处理!');
            }

            //修改订单状态[status=>2,pay_status=1] ==> [status=>3,pay_status=2]
            $update = MerchantOrder::where('id',$this->merchantOrderModel->id)
                                ->where('status',2)
                                ->where(function($query){
                                    $query->where('pay_status',1);
                                    $query->whereOr('pay_status',3);
                                })->update($data);

            if(empty($update)){
               throw new Exception('确认失败,请稍后再试!',500);
            }

            //检查是否为固定金额通道, 将码定为使用过
            if($this->merchantOrderModel->merchant_order_channel == 'alipay_once'){
                MemberImages::where('id',$this->merchantOrderModel->get_money_qrcode_img_id)->update(['is_used'=>1,'order_id'=>$this->merchantOrderModel->id]);
            }


            //商户账户
            $this->merchant();
            //平台
            $this->platform();
            //记录用户未结款金额
            $this->memberAccount();
            //记录代理商
            $this->agentAccount();


            Db::commit();
            //推送并定时器检测回调是否成功
            if($this->merchantOrderModel->add_order_id){
                $client = new SwooleClientService();
                $params = [
                    'orderId'=>$this->merchantOrderModel->id,
                ];

                $package = $client->package('confirmDueIn',$params);

                $client->push($package);
            }
            return true;
        } catch(\Exception $e) {
            Db::rollback();
            $this->error = $e->getMessage();
            return false;
        }
    }


    /**
     * 商户账户
     * @throws Exception
     */
    private function merchant()
    {
        //可提现金额
        $currentMoney = bcadd($this->merchantModel->money,$this->merchantOrderModel->merchant_money,2);

        //商户累积
        $this->merchantModel->where('id',$this->merchantModel->id)
                            ->inc('order_num',1)
                            ->inc('total_turnover',$this->merchantOrderModel->start_money)
                            ->inc('money',$this->merchantOrderModel->merchant_money)
                            ->update();

        //记录日志
        $log = [
            'merchant_id' => $this->merchantModel->id,
            'order_id' => $this->merchantOrderModel->id,
            'balance' => $this->merchantOrderModel->start_money,
            'money' => $this->merchantOrderModel->merchant_money,
            'current_money' => $currentMoney,
            'create_time' => time(),
            'remark' => '确认收款',
            'type' => 1
        ];
        MerchantMoneyLog::create($log);
    }

    /**
     * 平台资金账户
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function platform()
    {
        $platform_model = new Platform();
        $platform = $platform_model->find();
        $platform->setInc('money', $this->merchantOrderModel->platform_fee_money);
        $log = [
            'order_id' => $this->merchantOrderModel->id,
            'money' => $this->merchantOrderModel->platform_fee_money,
            'create_time' => time(),
            'remark' => '确认收款',
            'type' => 1
        ];
        PlatformMoneyLog::create($log);
    }

    /**
     * 收款员账户
     * @throws \think\Exception
     */
    private function memberAccount()
    {

        $memberUpdate = \app\model\Member::where('id',$this->memberModel->id);

        $memberUpdate->inc('money',$this->merchantOrderModel->return_money)
                     ->inc('rebate_money',$this->merchantOrderModel->return_money)
                     ->inc('usable_limit',-$this->merchantOrderModel->return_money);


        $memberUpdateData = [];
        $now = time();
        //确认收款时间是否超时, 5分钟视为掉单,掉单数+1
        $slowOrderTimes = config('slow_order_time') ?: 300;
        $create_time = $this->merchantOrderModel->getData('match_time');
        if( ($now - $create_time) > $slowOrderTimes ){
            $memberUpdate->inc('slow_order_num',1)
                         ->inc('current_slow_order_num',1);

            $currentSlowNum = $this->memberModel->getData('current_slow_order_num');
            $maxSlowTimes = config( 'max_slow_order_num' );
            if($maxSlowTimes < $currentSlowNum){
                $memberUpdateData['is_pass'] = 2;
                $memberUpdateData['last_remark'] = '掉单次数过多,系统自动关闭通道!';
            }
        }

        //手续费
        //$member = new Member();
        //手续费
        /*$member->where('id', '=', $this->memberModel->id)->setInc('money', $this->merchant_order->member_fee_money);
        //普通会员待返款金额
        $member->where('id', '=', $this->memberModel->id)->setInc('rebate_money', $this->merchant_order->return_money);
        //调整可用额度
        $member->where('id', '=', $this->memberModel['id'])->setDec('usable_limit', $this->merchant_order['return_money']);*/

        $currentMoney = bcadd($this->memberModel->rebate_money,$this->merchantOrderModel->return_money,2);

        //会员账户日志
        $log = [
            'member_id' => $this->memberModel->id,
            'order_sn' => $this->merchantOrderModel->order_sn,
            'order_id' => $this->merchantOrderModel->id,
            'current_money' => $currentMoney,
            'money' => $this->merchantOrderModel->return_money,
            'create_time' => $now,
            'update_time' => $now,
            'remark' => '确认收款',
            'type' => 1
        ];

        $memberUpdate->update();
        MemberMoneyLog::create($log);
    }

    /**
     * 代理商账户
     * @throws \think\Exception
     */
    private function agentAccount()
    {

        //会员返款-代理佣金 = 代理返款总额
        $settlement_money = bcsub($this->merchantOrderModel->return_money, $this->merchantOrderModel->agent_fee_money,2);
        /*$agent = new Agent();
        //可用额度
        //修改可用额度
        //总业绩
        $agent->where('id', '=', $this->agentModel->id)->setInc('total_per_money', $this->merchant_order->start_money);
        //代理商手续费
        $agent->where('id', '=', $this->agentModel->id)->setInc('balance', $this->merchant_order->agent_fee_money);
        //待结算金额 = 普通会员 - 代理商手续费
        $agent->where('id', '=', $this->agentModel->id)->setInc('settlement_money', $settlement_money);
        $agent->where('id', '=', $this->agentModel->id)->setDec('usable_limit', $settlement_money);*/

        $agentInfo = \app\model\Agent::lock(true)->find($this->agentModel->id);

        $agentUpdate = \app\model\Agent::where('id',$this->agentModel->id)
                           ->inc('total_per_money',$this->merchantOrderModel->start_money)
                           ->inc('balance',$this->merchantOrderModel->agent_fee_money)
                           ->inc('settlement_money',$settlement_money)//代理当前未结
                           ->inc('return_money',$settlement_money)//代理总持有未结
                           ->inc('usable_limit',-$settlement_money);

        $agentUpdateData = [];
        $currentUsableLimit = bcsub($agentInfo->usable_limit, $settlement_money,2);
        $currentMoney = bcadd($agentInfo->return_money, $settlement_money,2);
        //额度小于500 ,关通道
        if($currentUsableLimit < 500){
            $agentUpdateData['type'] = 2;
        }


        //记录日志
        $now = time();
        $log = [
            'agent_id' => $this->agentModel['id'],
            'order_sn' => $this->merchantOrderModel[ 'order_sn'],
            'order_id' => $this->merchantOrderModel[ 'id'],
            'money' => $settlement_money,//代理商待返金额
            'current_money' => $currentMoney,//当前待返总额度(包含本单)
            'create_time' => $now,
            'update_time' => $now,
            'remark' => '确认收款',
            'type' => 1
        ];

        $agentUpdate->update($agentUpdateData);
        AgentMoneyLog::create($log);
    }


    /**
     * 普通会员对账 确认已返款
     * @param $order
     * @param $subInfo
     * @return bool
     * @throws \think\exception\DbException
     */
    public function settleRefund($order)
    {
        $this->merchantOrderModel = $order;
        $this->memberModel        = Member::find($order['member_id']);
        $this->agentModel         = Agent::find($order['agent_id']);
        Db::startTrans();
        try {
            $time = time();
            //修改订单状态[status=>4,pay_status=2,is_clear=2] ==> [status=>4,pay_status=2,is_clear=1]
            $this->merchantOrderModel->is_clear   = 1;
            $this->merchantOrderModel->clear_time = $time;
            $this->merchantOrderModel->save();
            //记录对账操作日志
            $account_log = [
                'order_id' => $this->merchantOrderModel->id,
                'merchant_sn' => $this->merchantOrderModel->merchant_order_sn,
                'get_money' => $this->merchantOrderModel->get_money,
                'refund_money' => $this->merchantOrderModel->return_money,
                'member_id' => $this->memberModel->id,
                'member' => $this->memberModel->mobile,
                'agent_id' => $this->agentModel->id,
                'action_id' => $this->agentModel->id,
                'action_agent' => $this->agentModel->mobile,
                'create_time' => $time
            ];
            AgentAccountLog::create($account_log);
            //修改普通收款员账户
            /*$member = new Member();
            $member->where('id', '=', $this->memberModel['id'])->setDec('rebate_money', $this->merchant_order['return_money']);
            //恢复普通会员相应的可用额度
            $member->where('id', '=', $this->memberModel['id'])->setInc('usable_limit', $this->merchant_order['return_money']);*/

            \app\model\Member::where('id',$this->memberModel->id)
                           ->inc('usable_limit',$this->merchantOrderModel->return_money)
                           ->update();

            $currentMoney = bcsub($this->memberModel->rebate_money, $this->merchantOrderModel->return_money,2);


            $log = [
                'member_id' => $this->memberModel['id'],
                'order_sn' => $this->merchantOrderModel[ 'order_sn'],
                'order_id' => $this->merchantOrderModel[ 'id'],
                'money' => $this->merchantOrderModel[ 'return_money'],
                'current_money' => $currentMoney,
                'create_time' => $time,
                'update_time' => $time,
                'remark' => '代理商确认已返款',
                'type' => 2
            ];
            MemberMoneyLog::create($log);
            Db::commit();
            return true;
        } catch(\Exception $e) {
            Db::rollback();
            $this->error = $e->getMessage();
            return false;
        }
    }
}
