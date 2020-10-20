<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/12 0012
 * Time: 10:47
 */

namespace app\admin\controller;
use app\model\WithdrawOperationLog;
use app\util\lock\Lock;
use app\util\Tools;
use app\util\ReturnCode;
use think\Db;
use app\model\Merchant as MerchantModel;
use app\model\MerchantWithdrawAudit as MerchantWithdrawAuditModel;
use app\model\SettlementTask as SettlementTaskModel;
use app\model\PlatformWithdraw as PlatformWithdrawModel;
use app\model\MerchantWithdraw as MerchantWithdrawModel;


class Platform extends Base{
    /**
     * 平台提现信息
     * @return array
     * @author
     */
    public function index(){
        $info['money'] = db('platform')->where(['id'=>1])->value('money');
        $info['total'] = sprintf("%.2f",db('settlement_task')->where(['type'=>2])->sum('settlement_money')+ $info['money']);
        $info['date'] = db('agent')->field('id,nickname,uid,mobile,poundage_ratio,settlement_money')->where(['parent_id'=>0])->order('settlement_money desc')->select();
        foreach ($info['date'] as $key=>&$val){
            $val['value'] = 0;
        }
        $bank_card = db('bank_card')->where(['type'=>1,'status'=>1])->select();
        if(count($bank_card) == 1){
            $info['bank_card'] = $bank_card[0]['bank_name'].'('.$bank_card[0]['card'].')【'.$bank_card[0]['name'].'】';
        }else{
            $info['bank_card'] = '请先选择一个默认的银行卡';
        }
        return $this->buildSuccess($info, '操作成功', ReturnCode::SUCCESS);
    }

    /**
     * 平台一键提现
     * @return array
     * @author
     */
    public function autoAllot(){
        $info = $this->request->post();
        $infos = db('platform')->where(['id'=>1])->find();
        $time = time();
        $log['merchant_id'] = 0;
        $log['status'] = 2;
        $log['create_time'] = $time;
        $log['manage'] = $this->userInfo['username'];
        $log['ip'] = $this->request->ip();
        if($infos['money'] == 0){
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '暂无可提现佣金');
        }
        $settlement_sn = 'S'.rand_order();
        $log['withdraw_id'] = $settlement_sn;
        $data = [];


        $lock = new Lock('redis',['namespace'=>'admin']);
        $lockKey = 'adminAutoAllot:'.$this->userInfo->id;
        $lock->get($lockKey,15);

        $bank_card = Db::name('bank_card')->where(['type'=>1,'status'=>1])->select();
        if(count($bank_card)!=1){
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '请先选择一个默认银行卡');
        }
        $bank = $bank_card[0]['bank_name'].'-'.$bank_card[0]['bank_address'].'('.$bank_card[0]['card'].')【'.$bank_card[0]['name'].'】';
        $log['bank_card'] = $bank;
        Db::startTrans();
        try{
            if(count($info['dataList'])==0){
                return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '请先选择代理商');
            }
            $total_money = array_sum(array_column($info['dataList'], 'value'));//分配金额之和
            if($total_money > $infos['money']){
                return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, $total_money.'分配的金额不能大于可提现的金额');
            }
            $withdraw_id = Db::name('platform_withdraw')->insertGetId(['withdraw_sn'=>$settlement_sn,'platform_uid'=>$infos['platform_uid'],'money'=>$total_money,'bank_card'=>$bank,'create_time'=>time()]);
            foreach ($info['dataList'] as $key=>$val){
                $settlement_money = Db::name('agent')->where(['id'=>$val['id']])->value('settlement_money');
                if($val['value'] > $settlement_money){
                    return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '代理商代还款金额不足');
                }
                if($val['value']<= 0){
                    return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '金额不能小于等于0');
                }
                $data[$key]['pm_uid'] = $infos['platform_uid'];
                $data[$key]['agent_id'] = $val['id'];
                $data[$key]['withdraw_id'] = $withdraw_id;
                $data[$key]['type'] = 2;
                $data[$key]['settlement_sn'] = $settlement_sn;
                $data[$key]['bank_card'] = $bank;
                $data[$key]['settlement_money'] = $val['value'];
                $data[$key]['create_time'] = time();
                Db::name('agent')->where(['id'=>$val['id']])->setDec('settlement_money',$val['value']);
            }
            Db::name('platform')->where(['id'=>1])->setDec('money',$total_money);
            Db::name('settlement_task')->insertAll($data);
            Db::name('platform_money_log')->insert(['money'=>$total_money,'type'=>2,'create_time'=>time(),'order_id'=>$withdraw_id,'remark'=>'平台提现扣除']);
            $log['money'] = $total_money;
            $log['remark'] = "平台分配";
            Db::name('withdraw_operation_log')->insert($log);
            // 提交事务
            Db::commit();
            $lock->release($lockKey);
            return $this->buildSuccess([]);
        }catch(\Exception $e){
            Db::rollback();
            $lock->release($lockKey);
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '手动分配失败');
        }
    }
    /**
     * 平台提现列表
     * @return array
     * @author
     */
    public function platformIndex(){
        $limit = $this->request->get('size', config('apiAdmin.ADMIN_LIST_DEFAULT'));
        $start = $this->request->get('page', 1);
        $status = $this->request->get('status', '');
        $where = [];
        if ($status === '1' || $status === '2') {
            $where['status'] = $status;
        }
        $listObj = (new PlatformWithdrawModel())->where($where)->order('status desc,create_time desc')
            ->paginate($limit, false, ['page' => $start])->toArray();
        $listInfo = $listObj['data'];
        foreach ($listInfo as $key=>&$val){
            if($val['update_time']){
                $val['update_time'] = date('Y-m-d H:i:s',$val['update_time']);
            }
            $val['confirm'] = (new SettlementTaskModel())->where(['withdraw_id'=>$listInfo[$key]['id'],'type'=>2,'merchant_confirm'=>2])->count('id');
        }
        return $this->buildSuccess([
            'list'  => $listInfo,
            'count' => $listObj['total']
        ]);
    }
    /**
     * 平台提现打款详情
     * @return array
     * @author
     */
    public function viewDetails(){
        $limit = $this->request->get('size', config('apiAdmin.ADMIN_LIST_DEFAULT'));
        $start = $this->request->get('page', 1);
        $id = $this->request->get('id', '');
        $where['a.id'] = $id;
        $where['t.type'] = 2;
        $listObj = (new PlatformWithdrawModel())->alias('a')->field('w.*')
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
            $member = db('member')->field('uid,nickname,mobile')->where(['id'=>$val['member_id']])->find();
            $val['member_id'] = $member['uid'].'-'.$member['nickname'].'-'.$member['mobile'];
            if($val['pay_time']){
                $val['pay_time'] = date('Y-m-d H:i:s',$val['pay_time']);
            }
        }
        if(count($listInfo)==0){
            $listObj = (new MerchantWithdrawAuditModel())->alias('a')->field('t.*')
                ->join('pay_settlement_task t','a.id=t.withdraw_id')
                ->where($where)->order('create_time DESC')
                ->paginate($limit, false, ['page' => $start])->toArray();
            $listInfo = $listObj['data'];
            foreach ($listInfo as $key=>&$val){
                $val['type'] = (new MerchantWithdrawModel())->getType(1);
                $val['status'] = '未分配';
                $agent = db('agent')->field('uid,nickname,mobile')->where(['id'=>$val['agent_id']])->find();
                $val['agent_id'] = $agent['uid'].'-'.$agent['nickname'].'-'.$agent['mobile'];
            }
        }
        return $this->buildSuccess([
            'list'  => $listInfo,
            'count' => $listObj['total']
        ]);
    }
    //平台提现日志列表
    public function withdrawLoglist(){
        $limit = $this->request->get('size', config('apiAdmin.ADMIN_LIST_DEFAULT'));
        $start = $this->request->get('page', 1);
        $where['status'] = 2;
        $listObj = (new WithdrawOperationLog())
            ->where($where)->order('create_time DESC')
            ->paginate($limit, false, ['page' => $start])->toArray();
        $listInfo = $listObj['data'];
        return $this->buildSuccess([
            'list'  => $listInfo,
            'count' => $listObj['total']
        ]);
    }
    /**
     * 平台确认收款
     * @return array
     * @author
     */
    public function confirm(){
        $id = $this->request->get('id');
        $status =(new PlatformWithdrawModel())->where(['id'=>$id])->value('status');
        if($status!=2){
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '结算未完成，无法确认收款');
        }
        $res = SettlementTaskModel::where(['withdraw_id' =>$id])->update([
            'merchant_confirm'      => 1,
        ]);
        if ($res === false) {
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '操作失败');
        } else {
            return $this->buildSuccess([]);
        }

    }

}
