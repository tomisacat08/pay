<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/12 0012
 * Time: 10:47
 */

namespace app\intermediary\controller;
use app\util\Tools;
use app\util\ReturnCode;
use app\model\Merchant as MerchantModel;
use app\model\MerchantWithdrawAudit ;
use app\model\SettlementTask as SettlementTaskModel;
use app\model\MerchantWithdraw ;
use think\Db;


class Withdraw extends Base{
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
        $daterange = $this->request->get('daterange/a', '');
        $merchant_uid = $this->request->get('merchant_uid', '');
        $merchant_ids = db('merchant')->where(['intermediary_id'=>$this->userInfo['id']])->column('id');
        $where = [];
        if($type ) {
            $where['type'] = $type;
        }
        if($withdraw_sn) {
            $where['withdraw_sn'] = $withdraw_sn;
        }
        if ($merchant_uid) {
            $where['merchant_id'] = db('merchant')->where(['intermediary_id'=>$this->userInfo['id'],'uid'=>$merchant_uid])->value('id');
        }else{
            $where['merchant_id'] =['in',$merchant_ids];
        }
        if($daterange) {
            $listObj = (new MerchantWithdrawAudit())
                ->where($where)
                ->whereTime('create_time','between',[strtotime($daterange[0]),strtotime($daterange[1])])
                ->order('type asc,create_time DESC')
                ->paginate($limit, false, ['page' => $start])
                ->toArray();
        }else{
            $listObj = (new MerchantWithdrawAudit())->where($where)
                ->order('type asc,create_time DESC')
                ->paginate($limit, false, ['page' => $start])
                ->toArray();
        }
        $listInfo = $listObj['data'];
        foreach ($listInfo as $key=>$val) {
            $merchant = db('merchant')->field('nickname,mobile,uid')->where(['id'=>$listInfo[$key]['merchant_id']])->find();
            $listInfo[$key]['merchant_id'] =$merchant['nickname'].'-'.$merchant['mobile'].'-'.$merchant['uid'];
            $listInfo[$key]['confirm'] = (new SettlementTaskModel())->where(['withdraw_id'=>$listInfo[$key]['id'],'type'=>1,'merchant_confirm'=>2])->count('id');

        }
        return $this->buildSuccess([
            'list'  => $listInfo,
            'count' => $listObj['total'],
        ]);
    }
    /**
     * 商户提现详情
     * @return array
     * @author
     */
    public function indexDetails(){
        $limit = $this->request->get('size', config('apiAdmin.ADMIN_LIST_DEFAULT'));
        $start = $this->request->get('page', 1);
        $id = $this->request->get('id', '');
        $where['a.id'] = $id;
        $listObj = (new MerchantWithdrawAudit())->alias('a')->field('w.*')
            ->join('pay_settlement_task t','a.id=t.withdraw_id')
            ->join('pay_merchant_withdraw w','w.settlement_id=t.id')
            ->where($where)->order('create_time DESC')
            ->paginate($limit, false, ['page' => $start])->toArray();
        $listInfo = $listObj['data'];
        foreach ($listInfo as $key=>&$val){
            $val['type'] = (new MerchantWithdraw())->getType($val['type']);
            $val['status'] = (new MerchantWithdraw())->getStatus($val['status']);
            $agent = db('agent')->field('uid,nickname,mobile')->where(['id'=>$val['agent_id']])->find();
            $val['agent_id'] = $agent['uid'].'-'.$agent['nickname'].'-'.$agent['mobile'];
            $member = db('member')->field('uid,nickname,mobile')->where(['id'=>$val['member_id']])->find();
            $val['member_id'] = $member['uid'].'-'.$member['nickname'].'-'.$member['mobile'];
            if($val['pay_time']){
                $val['pay_time'] = date('Y-m-d H:i:s',$val['pay_time']);
            }
        }
        return $this->buildSuccess([
            'list'  => $listInfo,
            'count' => $listObj['total']
        ]);
    }
}
