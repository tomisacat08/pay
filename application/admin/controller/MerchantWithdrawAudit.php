<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/12 0012
 * Time: 10:47
 */

namespace app\admin\controller;
use app\agent\model\AgentMoneyLog;
use app\agent\model\MemberMoneyLog;
use app\model\MerchantWithdraw;
use app\model\SettlementTask;
use app\model\WithdrawOperationLog;
use app\util\lock\Lock;
use app\util\Tools;
use Think\Db;
use app\util\ReturnCode;
use app\model\Merchant;
use app\model\Member;
use app\model\Agent;
use app\model\MerchantWithdrawAudit as MerchantWithdrawAuditModel;
use app\model\MerchantWithdraw as MerchantWithdrawModel;

class MerchantWithdrawAudit extends Base{
    /**
     * 商户提现申请列表
     * @return array
     * @author
     */
    public function index(){
        $limit = $this->request->get('size', config('apiAdmin.ADMIN_LIST_DEFAULT'));
        $start = $this->request->get('page', 1);
        $type = $this->request->get('type', '');
        $withdraw_sn = $this->request->get('withdraw_sn', '');
        $merchant_uid = $this->request->get('merchant_uid', '');
        $where = [];
        if ($withdraw_sn) {
            $where['withdraw_sn'] = $withdraw_sn;
        }
        if ($merchant_uid) {
            $where['merchant_uid'] = $merchant_uid;
        }
        if ($type === '1' || $type === '2'|| $type==='3'|| $type==='4') {
            $where['type'] = $type;
        }
        $listObj = (new MerchantWithdrawAuditModel())->where($where)->order('type asc,create_time DESC')
            ->paginate($limit, false, ['page' => $start])->toArray();
        $listInfo = $listObj['data'];
        foreach ($listInfo as $key=>&$val){
            $val['status'] = $val['type'];
            $val['type'] = (new MerchantWithdrawAuditModel())->audit_status($val['type']);
        }
        return $this->buildSuccess([
            'list'  => $listInfo,
            'count' => $listObj['total']
        ]);
    }
    /**
     * 商户提现驳回操作
     * @return array
     * @author
     */
    public function notPass() {

        $id = $this->request->get('id');
        $remark = $this->request->get('remark');

        $lockKey = 'notPass:'.$id;
        $lock = new Lock('redis',['namespace'=>'merchantWithdraw']);
        $lock->get($lockKey);

        try{
            $withdrawMode = MerchantWithdrawAuditModel::field('merchant_id,merchant_uid,withdraw_sn,money,type,bank_card')->find($id);
            if(empty($withdrawMode)){
                abort(500,'参数异常!');
            }

            $withdraw = $withdrawMode->toArray();
            $type = $withdraw['type'];

            if($type>2){
                abort(ReturnCode::DB_SAVE_ERROR,'操作失败,请稍后再试');
            }

            if(!$remark){
                abort(500,'请填写拒绝理由');
            }

            $now = time();
            $log = [];

            Db::startTrans();

            $withdrawMode->remark = $remark;
            $withdrawMode->type = 4;
            $save = $withdrawMode->save();

            if ($save === false) {
                abort(ReturnCode::DB_SAVE_ERROR,'操作失败');
            }

            //驳回申请冻结金额除去，退回余额
            $merchant = Merchant::lock(true)->find($withdraw['merchant_id']);
            $currentMoney = bcadd($merchant['money'],$withdraw['money'],2);
            Merchant::where('id',$withdraw['merchant_id'])
                    ->dec('frozen_money',$withdraw['money'])
                    ->inc('money',$withdraw['money'])
                    ->update();


            //添加商户余额日志
            Db::name('merchant_money_log')->insert([
                'order_id'=>$id,
                'merchant_id'=>$withdraw['merchant_id'],
                'type'=>1,
                'money'=>$withdraw['money'],
                'current_money'=> $currentMoney,
                'create_time'=>$now,
                'remark'=>'提现被驳回余额增加'
            ]);


            switch ($type){
                case 1: //申请中
                    $log['remark'] = "申请中被驳回";
                    break;
                case 2://打款中的驳回
                    //代理商分配修改
                    $log['remark'] = "打款中未分配被驳回";
                    $settlement_task = SettlementTask::field( 'id,agent_id,settlement_money,status' )
                                                     ->where( [ 'withdraw_id' => $id, 'pm_uid' => $withdraw[ 'merchant_uid' ] ] )
                                                     ->select();
                    foreach ($settlement_task as $val) {
                        // 已打款,传图, 不允许驳回

                        $status = $val->getData('status');
                        if($status == 5){
                            abort(ReturnCode::DB_SAVE_ERROR,'已存在打款,无法驳回!');
                        }
                        //返回代理商的待提现金额
                        Agent::where('id',$val->agent_id)->setInc('settlement_money', $val->settlement_money);

                        // 已分配,退回
                        if($status == 1){
                            $log['remark'] = "打款中被驳回";
                            $merchantWithdrawInfo = MerchantWithdraw::where('settlement_id',$val->id)
                                                                    ->where('agent_id',$val->agent_id)
                                                                    ->find();

                            $withdrawStatus = $merchantWithdrawInfo->getData('status');
                            switch ($withdrawStatus){
                                case 1:
                                    $nextStatus = 3;
                                    break;
                                case 2:
                                    $nextStatus = 4;
                                    break;
                                default:
                                    abort(ReturnCode::DB_SAVE_ERROR,'分配表状态异常,请联系客服核对');
                            }
                            Db::name('merchant_withdraw')->where(['id'=>$val['id']])->update(['status'=>$nextStatus]);
                        }

                        //置为 未完成驳回状态
                        SettlementTask::where('id',$val->id)->update(['status'=>4]);
                    }
            }

            $log['withdraw_id'] = $withdraw['withdraw_sn'];
            $log['merchant_id'] = $withdraw['merchant_id'];
            $log['bank_card'] = $withdraw['bank_card'];
            $log['status'] = 1;
            $log['money'] = $withdraw['money'];
            $log['create_time'] = $now;
            $log['manage'] = $this->userInfo['username'];
            $log['ip'] = $this->request->ip();

            // 提交事务
            Db::name('withdraw_operation_log')->insert($log);
            Db::commit();
            $lock->release($lockKey);
            return $this->buildSuccess([]);
        }catch(\Exception $e){
            Db::rollback();
            $lock->release($lockKey);
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, $e->getMessage());
        }

    }
    /**
     * 商户提款自动分配
     * @return array
     * @author
     */
    public function autoAllot(){
        $id = $this->request->get('id');
        $info = db('merchant_withdraw_audit')->where(['id'=>$id])->find();
        if($info['type'] >1){
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '您已处理过，请勿重复提交');
        }
        $time = time();
        $log['withdraw_id'] = $info['withdraw_sn'];
        $log['merchant_id'] = $info['merchant_id'];
        $log['bank_card'] = $info['bank_card'];
        $log['status'] = 1;
        $log['money'] = $info['money'];
        $log['create_time'] = $time;
        $log['manage'] = $this->userInfo['username'];
        $log['ip'] = $this->request->ip();
        //查询代还款排名前几的用户

        $lock = new Lock('redis',['namespace'=>'admin']);
        $lockKey = 'merchantAutoAllot:'.$info['merchant_id'];
        $lock->get($lockKey,15);

        $agents = db('agent')->field('id,settlement_money,poundage_ratio')->where(['parent_id'=>0])->order('settlement_money desc')->select();
        $money = $info['money'];
        $data['create_time'] = $time;
        $data['type'] = 1;//商户提现
        $data['withdraw_id'] = $id;
        $data['pm_uid'] = $info['merchant_uid'];
        $data['bank_card'] = $info['bank_card'];
        $data['settlement_sn'] = 'S'.rand_order();
        Db::startTrans();
        try{
            foreach($agents as $key=>$val){
                $data['agent_id'] = $val['id'];
                if($val['settlement_money'] >= $money){
                    Db::name('agent')->where(['id'=>$val['id']])->setDec('settlement_money',$money);
                    $data['settlement_money'] = $money;
                    Db::name('settlement_task')->insert($data);
                    break;
                }else{
                    Db::name('agent')->where(['id'=>$val['id']])->setDec('settlement_money',$val['settlement_money']);
                    $data['settlement_money'] = $val['settlement_money'];
                    $money = $money - $val['settlement_money'];
                    Db::name('settlement_task')->insert($data);
                }
            }
            Db::name('merchant_withdraw_audit')->where(['id'=>$id])->update(['type'=>2]);
            $log['remark'] = "自动分配";
            Db::name('withdraw_operation_log')->insert($log);
            // 提交事务
            Db::commit();
            $lock->release($lockKey);
            return $this->buildSuccess([]);
        }catch(\Exception $e){
            Db::rollback();
            $lock->release($lockKey);
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '自动分配失败');
        }

    }
    /**
     * 代理商手动转账信息
     * @return array
     * @author
     */
    public function manualInfo(){
        $info = db('agent')->field('id,nickname,uid,mobile,settlement_money,poundage_ratio')->where(['parent_id'=>0])->order('settlement_money desc')->select();
        foreach ($info as $key=>&$val){
            $val['value'] = 0;
        }
        return $this->buildSuccess($info, '操作成功', ReturnCode::SUCCESS);
    }
    /**
     * 代理商手动分配
     * @return array
     * @author
     */
    public function manualAllot(){

        $info = $this->request->post();
        $lockKey = 'merchantWithdraw:'.$info['id'];
        $lock = new Lock('redis',['namespace'=>'merchantWithdraw']);

        $lock->get($lockKey);

        $now = time();
        $total = 0;
        $settlement_sn = 'S'.rand_order();
        $data = [];


        Db::startTrans();
        try{
            $withdrawInfo = \app\model\MerchantWithdrawAudit::lock(true)->find($info['id']);
            if($withdrawInfo->type >1){
                abort(ReturnCode::DB_SAVE_ERROR, '您已处理过，请勿重复提交');
            }

            //已分配
            $withdrawInfo->type = 2;
            $withdrawInfo->save();

            foreach ($info['dataList'] as $key=>$val){

                if($val['value'] <= 0){//清除金额等于小于0的分配信息
                    abort(ReturnCode::DB_SAVE_ERROR, '填写的金额不能小于等于0');
                }

                $agentInfo = \app\model\Agent::lock(true)->field('settlement_money,return_money')->where(['id'=>$val['id']])->find();
                if($val['value'] > $agentInfo->settlement_money){
                    abort(ReturnCode::DB_SAVE_ERROR, '代理商待还款金额不足');
                }

                $data[$key]['pm_uid'] = $withdrawInfo->merchant_uid;
                $data[$key]['agent_id'] = $val['id'];
                $data[$key]['withdraw_id'] = $info['id'];
                $data[$key]['type'] = 1;
                $data[$key]['settlement_sn'] = $settlement_sn;
                $data[$key]['bank_card'] = $withdrawInfo->bank_card;
                $data[$key]['settlement_money'] = $val['value'];
                $data[$key]['create_time'] = $now;
                $total = $total + $val['value'];

                // 代理账变
                Agent::where(['id'=>$val['id']])->setDec('settlement_money',$val['value']);
                $agentMoneyLog = [
                    'agent_id' => $val['id'],
                    'order_sn' => $settlement_sn,
                    'order_id' => $info['id'],
                    'money' => $val['value'],//代理商待返金额
                    'current_money' => $agentInfo->return_money,//当前待返总额度(包含本单)
                    'create_time' => $now,
                    'update_time' => $now,
                    'remark' => '下发任务分配',
                    'type' => 2
                ];
                AgentMoneyLog::create($agentMoneyLog);
            }

            if($total != $withdrawInfo->money){
                abort(500,'请一次分配完所有下发金额!');
            }

            Db::name('settlement_task')->insertAll($data);

            $log['withdraw_id'] = $withdrawInfo->withdraw_sn;
            $log['merchant_id'] = $withdrawInfo->merchant_id;
            $log['bank_card'] = $withdrawInfo->bank_card;
            $log['status'] = 1;
            $log['money'] = $withdrawInfo->money;
            $log['create_time'] = $now;
            $log['manage'] = $this->userInfo['username'];
            $log['ip'] = $this->request->ip();
            $log['remark'] = "手动分配";
            Db::name('withdraw_operation_log')->insert($log);
            // 提交事务
            Db::commit();
            $lock->release($lockKey);
            return $this->buildSuccess([]);
        }catch(\Exception $e){
            Db::rollback();
            $lock->release($lockKey);
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, $e->getMessage());
        }
    }
    //查看详情
    public function viewDetails(){
        $limit = $this->request->get('size', config('apiAdmin.ADMIN_LIST_DEFAULT'));
        $start = $this->request->get('page', 1);
        $id = $this->request->get('id', '');
        $where['a.id'] = $id;
        $where['t.type'] = 1;
        $listObj = (new MerchantWithdrawAuditModel())->alias('a')->field('w.*')
            ->join('pay_settlement_task t','a.id=t.withdraw_id')
            ->join('pay_merchant_withdraw w','w.settlement_id=t.id')
            ->where($where)->order('create_time DESC')
            ->paginate($limit, false, ['page' => $start])->toArray();
        $listInfo = $listObj['data'];
        foreach ($listInfo as $key=>&$val){
            $val['type'] = (new MerchantWithdrawModel())->getType($val['type']);
            $val['status'] = (new MerchantWithdrawModel())->getStatus($val['status']);
            $agent = db('agent')->field('uid,nickname,mobile')->where(['id'=>$val['agent_id']])->find();
            $val['agent_id'] = $agent['uid'].'-'.$agent['nickname'].'-'.$agent['mobile'];
            if($val['pay_time']){
                $val['pay_time'] = date('Y-m-d H:i:s',$val['pay_time']);
            }
        }
        $listWithdraw = (new MerchantWithdrawAuditModel())->alias('a')->field('t.*')
                ->join('pay_settlement_task t','a.id=t.withdraw_id')
                ->where($where)->order('create_time DESC')
                ->select();
        foreach ($listWithdraw as $key=>&$val){
            $val['type'] = (new MerchantWithdrawModel())->getType(1);
            switch ($val['status']){
                case 1:
                    $statusName = '已分配';
                    break;
                case 2:
                    $statusName = '未分配';
                    break;
                case 3:
                    $statusName = '已完成被驳回';
                    break;
                case 4:
                    $statusName = '未完成被驳回';
                    break;
                case 5:
                    $statusName = '已完成';
                    break;
                default:
                    $statusName = '状态异常';
            }
            $val['status'] = $statusName;
            $agent = db('agent')->field('uid,nickname,mobile')->where(['id'=>$val['agent_id']])->find();
            $val['agent_id'] = $agent['uid'].'-'.$agent['nickname'].'-'.$agent['mobile'];
        }
        return $this->buildSuccess([
            'list'  => $listInfo,
            'listWithdraw'  => $listWithdraw,
            'count' => $listObj['total']
        ]);
    }
    //商户提现日志列表
    public function withdrawLoglist(){

        $limit = $this->request->get('size', config('apiAdmin.ADMIN_LIST_DEFAULT'));
        $start = $this->request->get('page', 1);
        $where['status'] = 1;
        $listObj = (new WithdrawOperationLog())
            ->where($where)->order('create_time DESC')
            ->paginate($limit, false, ['page' => $start])->toArray();
        $listInfo = $listObj['data'];
        foreach ($listInfo as $key =>&$val){
            $val['merchant_id'] = db('merchant')->where(['id'=>$val['merchant_id']])->value('uid');
        }
        return $this->buildSuccess([
            'list'  => $listInfo,
            'count' => $listObj['total']
        ]);
    }

}
