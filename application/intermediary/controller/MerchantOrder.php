<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/12 0012
 * Time: 10:47
 */

namespace app\intermediary\controller;
use app\model\MerchantOrder as MerchantOrderModel;
use app\model\MerchantOrderLog as MerchantOrderLogModel;

class MerchantOrder extends Base{
    /**
     * 派单列表
     * @return array
     * @author
     */
    public function index(){
        $limit = $this->request->get('size', config('apiAdmin.ADMIN_LIST_DEFAULT'));
        $start = $this->request->get('page', 1);
        $status = $this->request->get('status', '');
        $id = $this->request->get('id', '');
        $merchant_uid = $this->request->get('merchant_uid', '');
        $ip = $this->request->get('ip', '');
        $daterange = $this->request->get('daterange/a', '');
        $where = [];

        $merchant_ids = db('merchant')->where(['intermediary_id'=>$this->userInfo['id']])->column('id');

        if ($status == 1) {
            $wheres['pay_status'] = 2;
        }elseif($status == 2){
            $wheres['pay_status'] = ['neq',2];
        }else{
            $wheres['pay_status'] = ['in','1,2,3'];
        }
        if (!empty($id)) {
            $where['id'] = $id;
        }
        if (!empty($ip)) {
            $where['ip'] = $ip;
        }
        if (!empty($merchant_uid)) {
            $where['merchant_id'] = db('merchant')->where(['intermediary_id'=>$this->userInfo['id'],'uid'=>$merchant_uid])->value('id');
        }else{
            $where['merchant_id'] =['in',$merchant_ids];
        }
        $MerchantOrderModel = new MerchantOrderModel();
        if($daterange){
            $listObj = $MerchantOrderModel->field('merchant_order_callbak_confirm_duein',true)
                ->whereTime('create_time','between',[strtotime($daterange[0]),strtotime($daterange[1])])
                ->where($where)->where($wheres)->order('create_time DESC')
                ->paginate($limit, false, ['page' => $start])->toArray();
            $complete_money = $MerchantOrderModel
                ->where($where)->where(['pay_status'=>2])
                ->whereTime('create_time','between',[strtotime($daterange[0]),strtotime($daterange[1])])
                ->sum('start_money');
            $complete_order_num = $MerchantOrderModel
                ->where($where)->where(['pay_status'=>2])
                ->whereTime('create_time','between',[strtotime($daterange[0]),strtotime($daterange[1])])
                ->count('id');
        }else{
            $listObj = $MerchantOrderModel->field('merchant_order_callbak_confirm_duein',true)
                ->where($where)->where($wheres)->order('create_time DESC')
                ->paginate($limit, false, ['page' => $start])->toArray();
            $complete_money = $MerchantOrderModel->where($where)->where(['pay_status'=>2])->sum('start_money');
            $complete_order_num = $MerchantOrderModel->where($where)->where(['pay_status'=>2])->count('id');
        }
        $listInfo = $listObj['data'];
        foreach ($listInfo as $key=>$val) {
            $merchant = db('merchant')->field('nickname,mobile,uid')->where(['id'=>$listInfo[$key]['merchant_id']])->find();
            $listInfo[$key]['merchant_id'] =$merchant['nickname'].'-'.$merchant['mobile'].'-'.$merchant['uid'];
            $listInfo[$key]['poundage'] = $val['start_money']-$val['money'];
            if($listInfo[$key]['pay_status']==2){
                $listInfo[$key]['status'] = "1";
            }else{
                $listInfo[$key]['status'] = "2";
            }
        }
        return $this->buildSuccess([
            'list'  => $listInfo,
            'count' => $listObj['total'],
            'complete_money'=>$complete_money,
            'complete_order_num'=>$complete_order_num,
        ]);
    }
}
