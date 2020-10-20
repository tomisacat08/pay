<?php

namespace app\merchant\controller;


use app\util\ReturnCode;

class Index extends Base {
    //首页
    public function index() {
        //未结算余额
        $userInfo = $this->merchantInfo;
        $data['uid'] = $userInfo['uid'];
        $data['nickname'] = $userInfo['nickname'];
        $userInfo = db('merchant')->field('id,uid,money')->where(['id'=>$userInfo['id']])->find();
        $data['money'] = $userInfo['money'];
        $data['today_order_num'] = db('merchant_order')->where(['merchant_id'=>$userInfo['id']])->whereTime('create_time', 'today')->count('id');
        $data['today_over_order_num'] = db('merchant_order')->where(['pay_status'=>2,'merchant_id'=>$userInfo['id']])->whereTime('create_time', 'today')->count('id');
        $data['today_order_money'] = sprintf("%.2f",db('merchant_order')->where(['merchant_id'=>$userInfo['id']])->whereTime('create_time', 'today')->sum('start_money'));
        $data['today_over_order_money'] = sprintf("%.2f",db('merchant_order')->where(['pay_status'=>2,'merchant_id'=>$userInfo['id']])->whereTime('create_time', 'today')->sum('start_money'));

        $data['order_num'] = db('merchant_order')->where(['merchant_id'=>$userInfo['id']])->count('id');
        $data['over_order_num'] = db('merchant_order')->where(['pay_status'=>2,'merchant_id'=>$userInfo['id']])->count('id');
        $data['order_money'] = sprintf("%.2f",db('merchant_order')->where(['merchant_id'=>$userInfo['id']])->sum('start_money'));
        $data['over_order_money'] = sprintf("%.2f",db('merchant_order')->where(['pay_status'=>2,'merchant_id'=>$userInfo['id']])->sum('start_money'));
        $data['notice'] = db('notice')->field('id,title,content,create_time')->where(['merchant_status'=>1])->order('is_top asc,create_time desc')->select();
        foreach ($data['notice'] as $key=>&$val){
            $val['create_time'] = date('Y-m-d H:i:s',$val['create_time']);
        }
        return json(['code' => '1', 'msg' => '请求成功,', 'data' => $data]);
    }
}
