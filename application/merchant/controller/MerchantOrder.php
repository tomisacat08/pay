<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/12 0012
 * Time: 10:47
 */

namespace app\merchant\controller;

use app\admin\service\ExcelService;
use app\merchant\service\MerchantOrderService;
use app\api\service\MerchantCallbakService;
use app\model\Merchant as MerchantModel;
use app\model\MerchantOrder as MerchantOrderModel;
use app\model\MerchantOrderLog as MerchantOrderLogModel;
use app\payapi\validate\MerchantOrder as MerchantOrdervalidate;
use app\util\ReturnCode;

class MerchantOrder extends Base
{

    /**
     * 订单明细
     * @return array
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $limit = $this->request->get('size/d', config('apiAdmin.ADMIN_LIST_DEFAULT'));
        $start = $this->request->get('page/d', 1);
        $status = $this->request->get('status', '');
        $id = $this->request->get('id', '');//订单编号
        $merchant_sn = $this->request->get('merchant_sn', '');
        $daterange = $this->request->get('daterange/a', '');
        $confirmTimedaterange = $this->request->get('confirmTimedaterange/a', '');
        $isReplacement = $this->request->get('isReplacement/d', '');
        $excel = $this->request->get('excel/d', 0);//1导出excel表格
        $where = [];

        if ($status === '1') {
            $where['pay_status'] = 2;
        } elseif ($status === '0') {
            $where['pay_status'] = ['neq', 2];
        }

        if ($id) {
            $where['id'] = $id;
        }

        if (!empty($merchant_sn)) {
            $where['merchant_order_sn'] = ['like', '%' . $merchant_sn . '%'];
        }

        if (!empty($isReplacement)) {
            $where['replacement_order'] = $isReplacement;//1:平台内部补单 2:正常单
        }

        $merchantId = $this->merchantInfo[ 'id'];
        $fields = [
            'id',
            'merchant_order_sn',
            'merchant_order_date',
            'start_money',
            'create_time',
            'confirm_time',
            'pay_status',
            'merchant_order_callbak_confirm_duein',
            'remark',
            'ip'
        ];

        $MerchantOrderModel = MerchantOrderModel::field($fields);
        if (!empty($daterange)) {
            $where['create_time'] = ['between',[strtotime($daterange[0]),strtotime($daterange[1])]];
        }

        if (!empty($confirmTimedaterange)) {
            $where['confirm_time'] = ['between',[strtotime($confirmTimedaterange[0]),strtotime($confirmTimedaterange[1])]];
        }

        if( $excel == 1 ){
            if(empty($where)){
                return $this->buildFailed(ReturnCode::INVALID, '请携带搜索条件导出');
            }
            $list = $MerchantOrderModel->where('merchant_id',$merchantId)
                                       ->where($where)->order('create_time DESC')
                                       ->select();

            if(empty($list)){
                return $this->buildFailed(ReturnCode::PARAM_INVALID, '暂无数据');
            }
            $listInfo = MerchantOrderService::getOrderList($list);
            $excelService = new ExcelService();
            //设置表头：
            $head = ['订单编号', '商户单号', '订单金额', '下单时间', '确认时间', '收款状态','客户IP'];
            //数据中对应的字段，用于读取相应数据：
            $keys = ['id', 'merchant_order_sn', 'start_money', 'merchant_order_date', 'confirm_time', 'order_status_name','ip'];
            $excelService->exportExcel('商家订单明细', $listInfo, $head, $keys);
            return;
        }

        $order_num = 0 ;
        $order_money = 0.00;
        if( !empty($where) ){
            $totalWhere = $where;
            $totalWhere['pay_status'] = 2;
            $order_num = $MerchantOrderModel
                ->where('merchant_id',$merchantId)
                ->where($totalWhere)
                ->order('create_time DESC')
                ->count('id');
            $order_money = $MerchantOrderModel
                ->where('merchant_id',$merchantId)
                ->where($totalWhere)
                ->order('create_time DESC')
                ->sum('start_money');
        }

        $listObj = $MerchantOrderModel->where('merchant_id',$merchantId)
                                      ->where($where)->order('create_time DESC')
                                      ->paginate($limit, false, ['page' => $start])->toArray();

        $listInfo = MerchantOrderService::getOrderList($listObj['data']);

        return $this->buildSuccess([
            'list' => $listInfo,
            'count' => $listObj['total'],
            'order_num' => $order_num,
            'order_money' => $order_money,
        ]);
    }

    /**
     * 商户对账详情
     * @return array
     * @throws \think\exception\DbException
     */
    public function schedulingdetails()
    {
        $limit = $this->request->get('size', config('apiAdmin.ADMIN_LIST_DEFAULT'));
        $start = $this->request->get('page', 1);
        $daterange = $this->request->get('daterange/a', '');
        $where['merchant_id'] = $this->merchantInfo[ 'id'];
        if ($daterange) {
            $listObj = (new MerchantOrderLogModel())->where($where)->whereTime('create_time', 'between', [strtotime($daterange[0]), strtotime($daterange[1])])
                ->order('create_time DESC')
                ->paginate($limit, false, ['page' => $start])
                ->toArray();
        } else {
            $listObj = (new MerchantOrderLogModel())->where($where)
                ->order('create_time DESC')
                ->paginate($limit, false, ['page' => $start])
                ->toArray();
        }
        $listInfo = $listObj['data'];
        return $this->buildSuccess([
            'list' => $listInfo,
            'count' => $listObj['total'],
        ]);
    }

    public function confirmDueIn(){
        $orderId = $this->request->get('id/d', 1);
        $where['id'] = $orderId;
        $where['merchant_id'] = $this->merchantInfo[ 'id'];

        $orderInfo = \app\model\MerchantOrder::where($where)->find();
        if( $orderInfo->pay_status != 2 && $orderInfo->status != 2 ){
            return $this->buildFailed(ReturnCode::INVALID, '发起回调失败,订单状态异常!');
        }

        if(empty($orderInfo->merchant_order_callbak_confirm_duein)){
            return $this->buildFailed(ReturnCode::INVALID, '回调地址未设置!');
        }

        $postReturn = MerchantCallbakService::confirmDueIn($orderId);
        $msg = '回调地址 : [ '.$orderInfo->merchant_order_callbak_confirm_duein.' ] 返回:  '.$postReturn;

        return $this->buildSuccess([],$msg);
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
        $merchant = $this->merchantInfo;//商户ID
        //签名验证，查询数据是否被篡改
        if(empty($merchant)){
            return json( [ 'code' => '501', 'msg' => '商户ID异常'] );
        }
        $merchantId = $this->merchantInfo->id;

        $postData['merchant_order_uid'] = $merchant->uid;
        $postData['merchant_order_money'] = $this->request->post('merchant_order_money');
        $postData['merchant_order_sn'] = 'SN_' . rand_order();//生成支付订单号
        $postData['merchant_order_channel'] = $this->request->post('merchant_order_channel','alipay_qrcode');//支付通道编码
        $postData['merchant_order_date'] = date('Y-m-d H:i:s',time());
        $postData['merchant_order_callbak_confirm_duein'] = $this->request->post('merchant_order_callbak_confirm_duein','') ?: '';
        $postData['merchant_order_callbak_redirect'] = $this->request->post('merchant_order_callbak_redirect','');
        $postData['merchant_order_name'] = $this->request->post('merchant_order_name','手动订单');
        $postData['merchant_order_count'] = $this->request->post('merchant_order_count','');
        $postData['merchant_order_extend'] = $this->request->post('merchant_order_extend','');
        $postData['merchant_order_desc'] = $this->request->post('merchant_order_desc','');
        $postData['merchant_order_callbak_confirm_create'] = $this->request->post('merchant_order_callbak_confirm_create','');

        //后台参数
        $postData['from_system'] = $this->request->post('from_system',3);
        $postData['from_system_user_id'] = $this->merchantInfo->id;//操作人ID
        $sign = MerchantCallbakService::getSign($postData,$merchantId);

        $postData["merchant_order_sign"] = $sign;//md5签名

        $host = env('host','http://'.$_SERVER['SERVER_NAME'].':'.$_SERVER["SERVER_PORT"]);

        //显示获得的数据
        $url = $host.'/payapi/Index/order';
        $data = curl_post_json($url,$postData,200);

        //显示获得的数据
        return response($data);
    }

}
