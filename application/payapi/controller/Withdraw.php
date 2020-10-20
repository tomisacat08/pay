<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/12 0012
 * Time: 10:47
 */

namespace app\payapi\controller;
use app\api\service\MerchantCallbakService;
use app\model\Merchant;
use app\payapi\validate\WithdrawAudit;
use app\util\lock\Lock;
use app\util\Tools;
use app\util\ReturnCode;
use app\model\MerchantWithdrawAudit ;
use app\model\SettlementTask as SettlementTaskModel;
use app\model\MerchantWithdraw ;
use think\Db;


class Withdraw extends Base{

    /**
     * 提现申请
     * @return array
     * @author
     */
    public function  withdrawAudit(){

        $params = $this->request->post();
        $validate = new WithdrawAudit();
        $result = $validate->scene('add')->check($params);
        if ( $result !== true ) {
            return $this->buildFailed( ReturnCode::DB_SAVE_ERROR, $validate->getError() );
        }

        $merchantUid = $params['uid'];
        $merchantInfo = Merchant::where('uid', $merchantUid )->find();
        if (empty($merchantInfo)) {
            return $this->buildFailed(ReturnCode::INVALID, '商户UID异常,请核对商户!');
        }

        //ip白名单验证
        $iptables = $merchantInfo->withdraw_iptables;
        if(!empty($iptables)){
            $checkIp = explode(',',$iptables);
            $ip = $this->request->ip();
            if(!in_array($ip,$checkIp)){
                return $this->buildFailed(ReturnCode::INVALID, '请求IP异常,请联系管理员!');
            }
        }

        if ( $merchantInfo->status != 1 ) {
            return $this->buildFailed(ReturnCode::INVALID, '商户状态异常,请核对商户状态!');
        }

        $merchantId = $merchantInfo->id;

        $filter_checkSign = MerchantCallbakService::getSign($params,$merchantId,$signStr);
        $sign = $this->request->param('sign');

        if ( $sign != $filter_checkSign ) {
            return json( [ 'code' => ReturnCode::INVALID, 'msg' => 'sign验证失败,请核对signStr差异,严格按照文档操作', 'data' => ['signStr'=>$signStr] ] );
        }

        //获取银行参数
        $bankData['name']= trim($params['name']);
        $bankData['card'] = trim($params['card']);
        $bankData['bank_name'] = trim($params['bank_name']);
        $bankData['bank_address']= trim($params['bank_address']);
        $data['bank_card'] = $bankData['bank_name'].'-'.$bankData['bank_address'].'('.$bankData['card'].')【'.$bankData['name'].'】';

        $min = config('withdraw_min');
        $max = config('withdraw_max');
        if($min > $params['money']){//改为减完手续费的金额
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '金额请大于'.$min);
        }

        if($max < $params['money']){//改为减完手续费的金额
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '金额请小于'.$max);
        }



        if($merchantInfo->money < $params['money']){
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '可提现余额不足,当前余额:'.$merchantInfo->money);
        }

        $lock = new Lock('redis',['namespace'=>'api']);
        $lockKey = 'apiMerchantWithdraw:'.$merchantId;
        $lock->get($lockKey,15);

        $exists = MerchantWithdrawAudit::where('withdraw_sn',$params['sn'])->where('merchant_id',$merchantId)->find();
        if($exists){
            $lock->release($lockKey);
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '订单已被收录,请勿重复提交!');
        }
        $data['withdraw_sn'] = $params['sn'];
        $data['create_time'] = time();
        $data['merchant_id'] = $merchantId;
        $data['merchant_uid'] = $merchantUid;
        $data['money'] = $params['money'];//减完手续费的金额
        $data['remark'] = data_get($params,'remark','');//备注
        $data['callback'] = data_get($params,'callback','');//回调
        $data['withdraw_sn'] = $params['sn'];

        Db::startTrans();
        try{
            $res = MerchantWithdrawAudit::create($data);
            $id = $res->id;
            if ($res === false) {
                abort(500,'操作失败');
            }
            //操作成功，减去余额
            $merchant = Merchant::lock(true)->find($merchantId);
            $currentMoney = bcsub($merchant['money'],$data['money'],5);
            Merchant::where('id',$merchantId)->dec('money',$data['money'])->inc('frozen_money',$data['money'])->update();
            Db::name('merchant_money_log')->insert([
                'merchant_id'=>$merchantId,
                'order_id'=>$id,
                'type'=>2,
                'money'=>$data['money'],
                'current_money' => $currentMoney,
                'create_time'=>time(),
                'remark'=>'api-提现扣除余额'
            ]);
            Db::commit();
            $lock->release($lockKey);
            return $this->buildSuccess([]);
        }catch(\Exception $e){
            Db::rollback();
            $lock->release($lockKey);
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, $e->getMessage());
        }
    }


    public function getBalance()
    {

        $params = $this->request->post();

        if(empty($params['uid'])){
            return $this->buildFailed(ReturnCode::INVALID, '参数缺失UID!');
        }

        $merchantUid = $params['uid'];
        $merchantInfo = Merchant::where('uid', $merchantUid )->find();
        if (empty($merchantInfo)) {
            return $this->buildFailed(ReturnCode::INVALID, '商户UID异常,请核对商户!');
        }

        $merchantId = $merchantInfo->id;

        $filter_checkSign = MerchantCallbakService::getSign($params,$merchantId,$signStr);
        $sign = $this->request->param('sign');

        if ( $sign != $filter_checkSign ) {
            return json( [ 'code' => ReturnCode::INVALID, 'msg' => 'sign验证失败,请核对signStr差异,严格按照文档操作', 'data' => ['signStr'=>$signStr] ] );
        }

        $data = [
            'balance' => $merchantInfo->money,
            'date' => date('Y-m-d H:i:s')
        ];

        return $this->buildSuccess($data,'查询成功!');
    }
}
