<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/12 0012
 * Time: 10:47
 */

namespace app\merchant\controller;

use app\admin\service\ExcelService;
use app\admin\service\GoogleService;
use app\admin\service\MerchantOrderService;
use app\model\BankCard;
use app\util\lock\Lock;
use app\util\Tools;
use app\util\ReturnCode;
use app\model\Merchant as MerchantModel;
use app\model\MerchantWithdrawAudit ;
use app\model\SettlementTask as SettlementTaskModel;
use app\model\MerchantWithdraw ;
use think\Db;


class Withdraw extends Base{

    public function withdrawIndex(){
        $userInfo = $this->merchantInfo;
        $bank = db('bank_card')->where(['uid'=>$userInfo['id'],'type'=>3,'status'=>1])->select();
        if(count($bank)>0){
            foreach ($bank as $key =>&$val){
                $bank[$key]['bank'] = $val['bank_name'].'-'.$val['bank_address'].'('.$val['card'].')【'.$val['name'].'】';
            }
        }
        $userInfo = db('merchant')->field('poundage_ratio,money,frozen_money')->where(['id'=>$userInfo['id']])->find();
        return $this->buildSuccess([
            'poundage_ratio'  => $userInfo['poundage_ratio'],
            'money' => $userInfo['money'],
            'frozen_money' => $userInfo['frozen_money'],
            'bank_card'=> $bank,
            'withdraw_min'=> config('withdraw_min'),
            'withdraw_max'=> config('withdraw_max'),
        ]);
    }

    /**
     * 提现申请
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function  withdrawAudit(){
        $money = $this->request->post('money/f');
        $cardId = $this->request->post('bank_card_id/d');
        $payPassword = $this->request->post('pay_password/s');
        $code = $this->request->post('code/d','');
        $userInfo = $this->merchantInfo;

        //ip白名单验证
        $iptables = $userInfo->withdraw_iptables;
        if(!empty($iptables)){
            $checkIp = explode(',',$iptables);
            $ip = $this->request->ip();
            if(!in_array($ip,$checkIp)){
                return $this->buildFailed(ReturnCode::INVALID, '请求IP异常,请联系管理员!');
            }
        }

        $Data['create_time'] = time();
        $min = config('withdraw_min');
        $max = config('withdraw_max');
        if($min>$money || $max<$money){//改为减完手续费的金额
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '金额错误');
        }
        if($userInfo->money < $money){
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '可提现余额不足');
        }

        $bank_card = BankCard::where(['id'=>$cardId,'uid'=>$userInfo->id,'type'=>3,'status'=>1])->find();
        if(!$bank_card){
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '请选择银行卡');
        }
        $Data['bank_card'] = $bank_card['bank_name'].'-'.$bank_card['bank_address'].'('.$bank_card['card'].')【'.$bank_card['name'].'】';
        $Data['merchant_id'] = $userInfo->id;
        $Data['merchant_uid'] = $userInfo->uid;

        $Data['money'] = $money;//减完手续费的金额
        $Data['withdraw_sn'] = 'T'.rand_order();

        //是否启用谷歌验证
        if($this->userInfo->used_google_code == 1){

            if (!$code) {
                return $this->buildFailed(ReturnCode::EMPTY_PARAMS, '请输入谷歌验证码!');
            }

            $check = GoogleService::check($this->userInfo->google_secret_key,$code);
            if(!$check){
                return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '验证码错误');
            }
        }

        $payPassword = Tools::userMd5($payPassword);
        $check = $this->userInfo->pay_password == $payPassword;
        if(!$check){
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '支付密码不正确');
        }

        $lock = new Lock('redis',['namespace'=>'merchant']);
        $lockKey = 'withdrawAudit:'.$userInfo->id;
        $lock->get($lockKey,15);

        Db::startTrans();
        try{
            $res = MerchantWithdrawAudit::create($Data);
            $id = $res->id;
            if ($res === false) {
                abort(500,'操作失败');
            }
            //操作成功，减去余额
            $merchant = \app\model\Merchant::lock(true)->find($userInfo['id']);
            $currentMoney = bcsub($merchant['money'],$Data['money'],5);
            $merchant->setDec('money',$Data['money']);
            $merchant->setInc('frozen_money',$Data['money']);
            $remark = '提现扣除余额';
            if(!$this->isParent){
                $remark = '子账号[ '. $this->userInfo->nickname .' ]:提现扣除余额';
            }
            Db::name('merchant_money_log')->insert([
                'merchant_id'=>$userInfo['id'],
                'order_id'=>$id,
                'type'=>2,
                'money'=>$Data['money'],
                'current_money' => $currentMoney,
                'create_time'=>time(),
                'remark'=>$remark
            ]);

            $lock->release($lockKey);
            Db::commit();
            return $this->buildSuccess([]);
        }catch(\Exception $e){
            $lock->release($lockKey);
            Db::rollback();
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, $e->getMessage());
        }
    }
    /**
     * 商户提现申请列表
     * @return array
     * @author
     */
    public function index(){
        $limit = $this->request->get('size', config('apiAdmin.ADMIN_LIST_DEFAULT'));
        $start = $this->request->get('page', 1);
        $type = $this->request->get('type', '');
        $daterange = $this->request->get('daterange/a', '');
        $excel = $this->request->get('excel', '');
        $where = [];
        if ($type === '1' || $type === '2' || $type === '3') {
            $where['type'] = $type;
        }else{
            $where['type'] = ['lt',4];
        }
        $where['merchant_id'] = $this->merchantInfo[ 'id'];
        if($daterange){
            $where['create_time'] = ['between',[strtotime($daterange[0]),strtotime($daterange[1])]];
        }

        if( $excel == 1 ){
            // TODO 导出下发列表
            if(empty($where)){
                return $this->buildFailed(ReturnCode::INVALID, '请携带搜索条件导出');
            }
            $list = \app\model\MerchantOrder::with(['merchantInfo','agentInfo','memberInfo'])
                                       ->where($where)->order('create_time DESC')
                                       ->select();

            if(empty($list)){
                return $this->buildFailed(ReturnCode::PARAM_INVALID, '暂无数据');
            }
            $listInfo = MerchantOrderService::getOrderList($list);
            $excelService = new ExcelService();
            //设置表头：
            $head = ['订单编号', '商户编号', '商户单号', '订单金额',  '收款金额', '收款人', '代理', '下单时间','确认耗时', '收款状态'];
            //数据中对应的字段，用于读取相应数据：
            $keys = ['id', 'merchantInfo', 'merchant_order_sn', 'start_money','get_money', 'memberInfo', 'agentInfo', 'create_time','confirmInterval', 'order_status_name'];
            $excelService->exportExcel('派单记录', $listInfo, $head, $keys);
            return;
        }





        $MerchantWithdrawAudit = (new MerchantWithdrawAudit());

        $listObj = $MerchantWithdrawAudit
            ->where($where)
            ->order('type asc,create_time DESC')
            ->paginate($limit, false, ['page' => $start])
            ->toArray();
        $listInfo = $listObj['data'];
        foreach ($listInfo as $key=>$val) {
            $listInfo[$key]['type'] = (new MerchantWithdrawAudit())->audit_status($listInfo[$key]['type']);
            $listInfo[$key]['confirm'] = (new SettlementTaskModel())->where(['withdraw_id'=>$listInfo[$key]['id'],'type'=>1,'merchant_confirm'=>2])->count('id');
        }
        $withdrawMoney = $MerchantWithdrawAudit->where($where)->sum('money');//提现金额
        return $this->buildSuccess([
            'list'  => $listInfo,
            'count' => $listObj['total'],
            'withdrawMoney' => $withdrawMoney,
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
        $where['t.type'] = 1;
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
    /**
     * 被拒绝的申请详情
     * @return array
     * @author
     */
    public function no_index(){
        $limit = $this->request->get('size', config('apiAdmin.ADMIN_LIST_DEFAULT'));
        $start = $this->request->get('page', 1);
        $daterange = $this->request->get('daterange/a', '');
        $withdraw_sn = $this->request->get('withdraw_sn', '');
        $where = [];
        $where['type'] = 4;
        $where['merchant_id'] = $this->merchantInfo[ 'id'];
        if(!empty($withdraw_sn)){
            $where['withdraw_sn'] = $withdraw_sn;
        }
        if($daterange){
            $listObj = (new MerchantWithdrawAudit())->where($where)->whereTime('create_time','between',[strtotime($daterange[0]),strtotime($daterange[1])])
                ->order('create_time DESC')
                ->paginate($limit, false, ['page' => $start])
                ->toArray();
        }else{
            $listObj = (new MerchantWithdrawAudit())->where($where)
                ->order('create_time DESC')
                ->paginate($limit, false, ['page' => $start])
                ->toArray();
        }
        $listInfo = $listObj['data'];
        foreach($listInfo as $key=>$val){
            $listInfo[$key]['type'] = (new MerchantWithdrawAudit())->audit_status($listInfo[$key]['type']);
        }
        return $this->buildSuccess([
            'list'  => $listInfo,
            'count' => $listObj['total'],
        ]);
    }
    /**
     * 商户确认收款
     * @return array
     * @author
     */
    public function confirm(){
        $id = $this->request->get('id');
        $status =(new MerchantWithdrawAudit())->where(['id'=>$id])->value('type');
        if($status!=3){
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '结算未完成，无法确认收款');
        }
        $res = SettlementTaskModel::where(['withdraw_id' =>$id])->update([
            'type'                  => 1,
            'merchant_confirm'     => 1,
        ]);
        if ($res === false) {
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '操作失败');
        } else {
            return $this->buildSuccess([]);
        }

    }


}
