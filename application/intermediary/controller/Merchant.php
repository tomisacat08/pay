<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/12 0012
 * Time: 10:47
 */

namespace app\intermediary\controller;
use app\admin\validate\Merchant as Merchantvalidate;
use app\model\MercantAddMoneyLog;
use app\model\Merchant as MerchantModel;
use app\model\MerchantMoneyLog;
use app\model\MerchantOrder as MerchantOrderModel;
use app\model\MerchantWithdrawAudit;
use app\util\ReturnCode;
use app\util\Tools;

class Merchant extends Base{
    /**
     * 商户列表
     * @return array
     * @author
     */
    public function index(){
        $limit = $this->request->get('size', config('apiAdmin.ADMIN_LIST_DEFAULT'));
        $start = $this->request->get('page', 1);
        $type = $this->request->get('type', '');
        $keywords = $this->request->get('keywords', '');
        $uid = $this->request->get('uid', '');
        $where = [];
        $merchant_ids = db('merchant')->where(['intermediary_id'=>$this->userInfo['id']])->column('id');
        $where['id'] =['in',$merchant_ids];

        if ($type === '1' || $type === '2') {
            $where['type'] = $type;
        }

        if ($uid){
            $where['uid'] = $uid;
        }

        if ($keywords) {
            $where['mobile|nickname'] = ['like', "%{$keywords}%"];
        }


        $listObj = MerchantModel::with(['intermediaryInfo'])->field('apikey,password,pay_password,user_token',true)->where($where)->order('create_time DESC')
            ->paginate($limit, false, ['page' => $start])->toArray();

        $listInfo = $listObj['data'];
        foreach ($listInfo as $key=>&$val){
            $val['last_login_time'] = date('Y-m-d H:i:s',$val['last_login_time']);
            $val['intermediary_mobile'] = data_get($val,'intermediary_info.mobile','');
        }
        return $this->buildSuccess([
            'list'  => $listInfo,
            'count' => $listObj['total']
        ]);
    }


    /**
     * 新增商户
     * @return array
     * @author
     */
    public function add(){
        $params = $this->request->post();
        //参数验证
        $validate = new Merchantvalidate();
        $result = $validate->scene('add')->check($params);
        if ($result !== true) {
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, $validate->getError());
        }

        $data['intermediary_id'] = $this->userInfo['id'];
        $data['nickname'] = $params['nickname'];
        $data['mobile'] = $params['mobile'];
        $data['poundage_ratio'] = $params['poundage_ratio'];
        $data['account_holder'] = $params['account_holder'];
        $data['last_login_ip'] = request()->ip();
        $data['last_login_time'] = $params['create_time']= time();
        $data['password'] = Tools::userMd5($params['password']);
        $data['pay_password'] = Tools::userMd5($params['pay_password']);
        $data['apikey'] = strtoupper(md5(uniqid()));
        $data['type'] = 2;//默认通道禁止
        $data['order_scope'] = $params['order_scope'];
        $data['withdraw_iptables'] = $params['withdraw_iptables'];
        //UID随机增长
        $max_uid = MerchantModel::max('uid');
        if(empty($max_uid)){
            $max_uid = 1000;
        }
        $data['uid'] = $max_uid + mt_rand(66, 255);
        $res = MerchantModel::create($data);

        if ($res === false) {
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '操作失败');
        }

        return $this->buildSuccess([]);
    }

    /**
     * 商户编辑
     * @author
     * @return array
     */
    public function edit() {
        $params = $this->request->post();
        //参数验证
        $validate = new Merchantvalidate();
        $result = $validate->scene('edit')->check($params);
        if ($result !== true) {
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, $validate->getError());
        }

        return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '权限禁止,请联系管理员操作!');

        $id = $params['id'];

        $merchantInfo = MerchantModel::find($id);

        /*if($params['password']){
            $merchantInfo->password = Tools::userMd5($params['password']);
        }

        if($params['pay_password']){
            $merchantInfo->pay_password = Tools::userMd5($params['pay_password']);
        }*/

        if($params['nickname']){
            $merchantInfo->nickname = $params['nickname'];
        }

        /*if($params['poundage_ratio']){
            $merchantInfo->poundage_ratio = $params['poundage_ratio'];
        }*/

        $merchantInfo->withdraw_iptables = $params['withdraw_iptables'];

        if($params['order_scope']){
            $merchantInfo->order_scope = $params['order_scope'];
        }

        $save = $merchantInfo->save();
        if( $save === false ){
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '操作失败');
        }

        return $this->buildSuccess([]);
    }


    /**
     * 商户登录开关
     * @return array
     * @author
     */
    public function changeStatus() {
        $id = $this->request->get('id');
        $status = $this->request->get('status');

        $merchant_ids = db('merchant')->where(['intermediary_id'=>$this->userInfo['id']])->column('id');
        if( !in_array($id,$merchant_ids) ){
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '参数异常');
        }

        $res = MerchantModel::update([
            'id'         => $id,
            'status'     => $status,
        ]);
        if ($res === false) {
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '操作失败');
        } else {
            return $this->buildSuccess([]);
        }
    }
    /**
     * 商户通道开关
     * @return array
     * @author
     */
    public function changeType() {
        $id = $this->request->get('id');
        $type = $this->request->get('type');

        $merchant_ids = db('merchant')->where(['intermediary_id'=>$this->userInfo['id']])->column('id');
        if( !in_array($id,$merchant_ids) ){
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '参数异常');
        }

        return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '权限禁止,请联系管理员操作!');


        $res = MerchantModel::update([
            'id'         => $id,
            'type'     => $type,
        ]);
        if ($res === false) {
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '操作失败');
        } else {
            return $this->buildSuccess([]);
        }
    }
    /**
     * 商户结算查看
     * @return array
     * @author
     */
    public function checkWithdraw() {

        $limit = $this->request->get('size', config('apiAdmin.ADMIN_LIST_DEFAULT'));
        $start = $this->request->get('page', 1);
        $id = $this->request->get('id');

        $merchant_ids = db('merchant')->where(['intermediary_id'=>$this->userInfo['id']])->column('id');
        if( !in_array($id,$merchant_ids) ){
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '参数异常');
        }

        $where = [];
        $where['merchant_id'] = $id;
        $listObj = (new MerchantWithdrawAudit())->where($where)
                                                ->order('type asc,create_time DESC')
                                                ->paginate($limit, false, ['page' => $start])
                                                ->toArray();
        $listInfo = $listObj['data'];
        foreach ($listInfo as $key=>$val) {
            $listInfo[$key]['type'] = (new MerchantWithdrawAudit())->audit_status($listInfo[$key]['type']);

        }
        return $this->buildSuccess([
            'list'  => $listInfo,
            'count' => $listObj['total'],
        ]);
    }
    /**
     * 商户派单查看
     * @return array
     * @author
     */
    public function checkDispatch(){
        $limit = $this->request->get('size', config('apiAdmin.ADMIN_LIST_DEFAULT'));
        $start = $this->request->get('page', 1);
        $id = $this->request->get('id', '');

        $merchant_ids = db('merchant')->where(['intermediary_id'=>$this->userInfo['id']])->column('id');
        if( !in_array($id,$merchant_ids) ){
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '参数异常');
        }

        $where = [];
        $where['merchant_id'] = $id;
        $listObj = (new MerchantOrderModel())->where($where)->order('create_time DESC')
                                             ->paginate($limit, false, ['page' => $start])->toArray();
        $listInfo = $listObj['data'];

        foreach ($listInfo as $key=>$val) {
            $listInfo[$key]['poundage'] = $val['start_money']-$val['money'];
            $listInfo[$key]['status'] = (new MerchantOrderModel())->getOrderStatusAttr('',$listInfo[$key]);
        }
        return $this->buildSuccess([
            'list'  => $listInfo,
            'count' => $listObj['total'],
        ]);
    }

    /**
     * 商户资金变动明细
     * @return array
     */
    public function moneyLog()
    {
        $merchantId = $this->request->get('id/d', 0);
        $page = $this->request->get('page/d', 1);
        $limit = $this->request->get('size/d', 15);
        $daterange = $this->request->get('daterange/a', '');//日期
        $type = $this->request->get('type/d', 0);//类型 1:收入 2:支出

        $merchant_ids = db('merchant')->where(['intermediary_id'=>$this->userInfo['id']])->column('id');
        if( !in_array($merchantId,$merchant_ids) ){
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '参数异常');
        }

        $where['merchant_id'] = $merchantId;
        //日期
        if (!empty($daterange)) {
            $start_time = strtotime($daterange[0]);
            $end_time = strtotime($daterange[1]);
            $where['create_time'] = ['between', [$start_time, $end_time]];
        }
        if (!empty($type)) {
            $where['type'] = $type;
        }
        $list = MerchantMoneyLog::getMoneyLogList($where, $page, $limit);

        return $this->buildSuccess(['list' => $list->items(), 'count' => $list->total()]);
    }

}
