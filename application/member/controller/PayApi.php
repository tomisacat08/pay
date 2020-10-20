<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/12 0012
 * Time: 10:47
 */

namespace app\member\controller;
use app\util\Tools;
use app\util\ReturnCode;
use app\model\member as memberModel;
use app\model\memberWithdrawAudit ;
use app\model\memberWithdraw ;

class PayApi extends Base{

    public function Index(){
        $userInfo = $this->userInfo;
        return $this->buildSuccess([
            'uid'  => $userInfo['uid'],
            'url'=>'http://'.$_SERVER['SERVER_NAME'].'/payapi/Index/payindex'
        ]);
    }
    public function lookApiKey(){
        $postData = $this->request->post();
        $userInfo = $this->userInfo;
        $password = db('member')->where(['id'=>$userInfo['id']])->value('pay_password');
        $pay_password = Tools::userMd5($postData['pay_password']);
        if($pay_password == $password){
            return $this->buildSuccess([
                'apikey' => $userInfo['apikey'],
            ]);
        }else{
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '支付密码错误');
        }
    }
}
