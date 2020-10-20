<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/12 0012
 * Time: 10:47
 */

namespace app\admin\controller;
use app\admin\service\MerchantService;
use app\api\service\MerchantCallbakService;
use app\api\swoole\RedisService;
use app\model\MercantAddMoneyLog;
use app\payapi\validate\MerchantOrder as MerchantOrdervalidate;
use app\util\Tools;
use app\util\ReturnCode;
use app\model\Merchant as MerchantModel;
use app\model\MerchantWithdrawAudit;
use app\model\MerchantOrder as MerchantOrderModel;
use app\admin\validate\Merchant as Merchantvalidate;

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
        if ($type === '1' || $type === '2') {
            $where['type'] = $type;
        }
        if ($uid){
            $where['uid'] = $uid;
        }
        if ($keywords) {
            $where['mobile|nickname'] = ['like', "%{$keywords}%"];
        }

        if ($keywords) {
            $where['mobile|nickname'] = ['like', "%{$keywords}%"];
        }

        $fields = [
            'id',
            'sort',
            'uid',
            'nickname',
            'intermediary_id',
            'mobile',
            'money',
            'frozen_money',
            'total_turnover',
            'create_time',
            'used_google_code',
            'type',
            'status',
            'withdraw_iptables',//编辑显示
            'account_holder',//编辑显示
            'order_scope',//下单范围
        ];

        $listObj = MerchantModel::with(['intermediaryInfo'])
                                ->field($fields)
                                 ->where($where)
                                 ->order('sort','DESC')
                                 ->order('money','DESC')
                                 ->order('type','ASC')
                                 ->order('id','DESC')
                                 ->paginate($limit, false, ['page' => $start])
                                 ->toArray();

        $listInfo = $listObj['data'];
        $merchantService = new MerchantService();
        foreach ($listInfo as $key=>&$val){

            $val['last_login_time'] = empty($val['last_login_time']) ? '' : date('Y-m-d H:i:s', $val['last_login_time']);

            $interInfo = '';
            $val['intermediary_mobile'] = data_get($val,'intermediary_info.mobile','');
            if( $val['intermediary_info'] ){
                $interInfo = $val['intermediary_info']['nickname'];
            }
            $val['intermediary_info'] = $interInfo;

            $yesterdayRateData = $merchantService->getMerchantYesterdayTurnoverRate($val['id']);
            $todayRateData = $merchantService->getMerchantTodayTurnoverRate($val['id']);
            $allRateData = $merchantService->getMerchantTurnoverRate($val['id']);

            $val['yesterdayRate'] = $yesterdayRateData['successOrderNum'].'/'.$yesterdayRateData['allOrderNum'].':'.$yesterdayRateData['rate'];
            $val['todayRate'] = $todayRateData['successOrderNum'].'/'.$todayRateData['allOrderNum'].':'.$todayRateData['rate'];
            $val['allRate'] = $allRateData['successOrderNum'].'/'.$allRateData['allOrderNum'].':'.$allRateData['rate'];
        }
        return $this->buildSuccess([
            'list'  => $listInfo,
            'count' => $listObj['total']
        ]);
    }


    public function getMerchantInfo(){
        $id = $this->request->get('id', '');
        if (empty($id)) {
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '操作失败');
        }

        $fields = [
            'poundage_ratio',
            'intermediary_poundage_ratio',
            'order_scope',
        ];

        $agentInfo = MerchantModel::field($fields)
                               ->find($id);
        if (empty($agentInfo)) {
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '操作失败');
        }
        return $this->buildSuccess($agentInfo->toArray());
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

        $intermediary_id = 0;
        if($params['intermediary_mobile'] != ""){
            $intermediary_id = db('intermediary')->where(['mobile'=>$params['intermediary_mobile']])->value('id');
            if(empty($intermediary_id)){
                return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '商户代理账号不存在');
            }
        }

        $data['intermediary_id'] = $intermediary_id;
        $data['intermediary_poundage_ratio'] = $params['intermediary_poundage_ratio'];
        $data['nickname'] = $params['nickname'];
        $data['mobile'] = $params['mobile'];
        $data['poundage_ratio'] = $params['poundage_ratio'];
        $data['account_holder'] = $params['account_holder'];
        $data['last_login_ip'] = request()->ip();
        $data['last_login_time'] = $params['create_time']= time();
        $data['password'] = Tools::userMd5($params['password']);
        $data['pay_password'] = Tools::userMd5($params['pay_password']);
        $data['apikey'] = strtoupper(md5(uniqid()));
        $data['order_scope'] = $params['order_scope'];
        $data['withdraw_iptables'] = $params['withdraw_iptables'];
        $data['sort'] = $params['sort'];
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

        $id = $params['id'];

        $merchantInfo = MerchantModel::find($id);

        if($params['password']){
            $merchantInfo->password = Tools::userMd5($params['password']);
        }

        if($params['pay_password']){
            $merchantInfo->pay_password = Tools::userMd5($params['pay_password']);
        }

        if($params['nickname']){
            $merchantInfo->nickname = $params['nickname'];
        }

        if($params['poundage_ratio']){
            $merchantInfo->poundage_ratio = $params['poundage_ratio'];
        }

        if($params['intermediary_poundage_ratio']){
            $merchantInfo->intermediary_poundage_ratio = $params['intermediary_poundage_ratio'];
        }

        $merchantInfo->withdraw_iptables = $params['withdraw_iptables'];

        if($params['order_scope']){
            $merchantInfo->order_scope = $params['order_scope'];
        }

        if($params['sort']){
            $merchantInfo->sort = $params['sort'];
        }

        if($params['intermediary_mobile'] != ""){
            $intermediary_id = db('intermediary')->where(['mobile'=>$params['intermediary_mobile']])->value('id');
            if(empty($intermediary_id)){
                return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '商户代理账号不存在');
            }
            $merchantInfo->intermediary_id = $intermediary_id;
        }

        $save = $merchantInfo->save();
        if( $save === false ){
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '操作失败');
        }

        return $this->buildSuccess([]);
    }

    /**
     * 删除商户
     * @return array
     * @author
     */
    /*public function del() {
        $id = $this->request->get('id');
        if (!$id) {
            return $this->buildFailed(ReturnCode::EMPTY_PARAMS, '缺少必要参数');
        }
        MerchantModel::destroy($id);
        MerchantModel::destroy(['id' => $id]);

        return $this->buildSuccess([]);

    }*/
    /**
     * 商户登录开关
     * @return array
     * @author
     */
    public function changeStatus() {
        $id = $this->request->get('id');
        $status = $this->request->get('status');
        $merchantInfo = MerchantModel::field('mobile')->find($id);

        $merchantInfo->status = $status;
        $save = $merchantInfo->save();
        if ($save === false) {
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '操作失败');
        }

        $mobile = $merchantInfo->mobile;
        if($status == 1){
            $keys = RedisService::keys('pay_merchantLogin:'.$mobile.':*');
            RedisService::del($keys);
        }
        return $this->buildSuccess([]);
    }
    /**
     * 商户通道开关
     * @return array
     * @author
     */
    public function changeType() {
        $id = $this->request->get('id');
        $type = $this->request->get('type');
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
     * 商户测试桩
     * @return array
     * @author
     */
    public function merchantOrderTest(){
        $params = $this->request->post();
        //参数验证
        $validate = new MerchantOrdervalidate();
        $result   = $validate->scene( 'test' )->check( $params );
        if ( $result !== true ) {
            return $this->buildFailed( ReturnCode::DB_SAVE_ERROR, $validate->getError() );
        }
        $merchant_id = $this->request->post('merchant_id');//商户ID
        //签名验证，查询数据是否被篡改
        $merchant = MerchantModel::field('uid,apikey')->find($merchant_id);
        if(empty($merchant)){
            return json( [ 'code' => '501', 'msg' => '商户ID异常'] );
        }

        $postData['merchant_order_uid'] = $merchant->uid;
        $postData['merchant_order_money'] = $this->request->post('merchant_order_money');
        $postData['merchant_order_sn'] = 'Test_' . rand_order();//生成支付订单号
        $postData['merchant_order_channel'] = $this->request->post('merchant_order_channel','alipay_qrcode');//支付通道编码
        $postData['merchant_order_date'] = date('Y-m-d H:i:s',time());
        $postData['merchant_order_callbak_confirm_duein'] = $this->request->post('merchant_order_callbak_confirm_duein','') ?: 'http://'.$_SERVER['HTTP_HOST'].'/payapi/Index/testCallBak';
        $postData['merchant_order_callbak_redirect'] = $this->request->post('merchant_order_callbak_redirect','');
        $postData['merchant_order_name'] = $this->request->post('merchant_order_name','测试订单');
        $postData['merchant_order_count'] = $this->request->post('merchant_order_count','');
        $postData['merchant_order_extend'] = $this->request->post('merchant_order_extend','');
        $postData['merchant_order_desc'] = $this->request->post('merchant_order_desc','');
        $postData['merchant_order_callbak_confirm_create'] = $this->request->post('merchant_order_callbak_confirm_create','');

        //后台参数
        $postData['from_system'] = $this->request->post('from_system','2');
        $postData['from_system_user_id'] = $this->userInfo['id'];//操作人ID
        $sign = MerchantCallbakService::getSign($postData,$merchant_id);

        $postData["merchant_order_sign"] = $sign;//md5签名

        //显示获得的数据
        $addModel = new \app\payapi\controller\Index();
        return $addModel->order($postData);
    }
}
