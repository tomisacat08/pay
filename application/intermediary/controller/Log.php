<?php
/**
 * 商户台账管理
 * @since   2018-02-06
 * @author  zhaoxiang <zhaoxiang051405@gmail.com>
 */

namespace app\intermediary\controller;

use app\model\AdminUserAction;
use app\model\MerchantMoneyLog;
use app\model\Merchant;
use app\util\ReturnCode;

class Log extends Base {

    /**
     * 商户资金变动明细
     * @return array
     */
    public function merchantLog()
    {
        $page = $this->request->get('page/d', 1);
        $limit = $this->request->get('size/d', 15);
        $orderId = $this->request->get('daterange/a', '');//日期
        $daterange = $this->request->get('order_id/d', 0);//订单id
        $keywords = $this->request->get('keywords', 0);//
        $type = $this->request->get('type/d', 0);//类型 1:收入 2:支出


        $where['merchant_id'] = $this->userInfo['id'];
        //日期
        if (!empty($daterange)) {
            $start_time = strtotime($daterange[0]);
            $end_time = strtotime($daterange[1]);
            $where['create_time'] = ['between', [$start_time, $end_time]];
        }
        if (!empty($type)) {
            $where['type'] = $type;
        }
        if (!empty($orderId)) {
            $where['order_id'] = $orderId;
        }
        if(!empty($keywords)){
            $merchant_where['uid|nickname|mobile'] = ['like','%'.$keywords.'%'];
        }
        $merchant_where['intermediary_id'] = $this->userInfo['id'];
        $merchants = Merchant::where($merchant_where)->column('id');
        if(count($merchants) > 0){
            $where['merchant_id'] = ['in',$merchants];
        }
        $list = MerchantMoneyLog::getMoneyLogList($where, $page, $limit);
        foreach ($list as $k => $v) {
            $merchant = db('Merchant')->field('uid,nickname,mobile')->where(['id'=>$v['merchant_id']])->find();
            $list[$k]['merchant_id'] = $merchant['uid'].'-'. $merchant['nickname'].'-'. $merchant['mobile'];
        }

        return $this->buildSuccess(['list' => $list->items(), 'count' => $list->total()]);
    }

}
