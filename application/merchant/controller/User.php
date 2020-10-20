<?php
/**
 * 用户管理
 * @since   2018-02-06
 * @author  zhaoxiang <zhaoxiang051405@gmail.com>
 */

namespace app\merchant\controller;

use app\admin\service\GoogleService;
use app\model\Merchant;
use app\util\GoogleAuthenticator;
use app\util\ReturnCode;
use app\util\Tools;

class User extends Base {
    /**
     * 修改自己的信息
     * @author zhaoxiang <zhaoxiang051405@gmail.com>
     * @return array
     * @throws \think\exception\DbException
     */
    public function own() {
        $postData = $this->request->post();
        $userInfo = Merchant::field('nickname,password')->find($this->userInfo->id);
        $update = [];
        if ($postData['password'] && $postData['oldPassword']) {
            $oldPass = Tools::userMd5($postData['oldPassword']);
            if ($oldPass === $userInfo->password) {
                $update['password'] = Tools::userMd5($postData['password']);
            } else {
                return $this->buildFailed(ReturnCode::INVALID, '原始密码不正确');
            }
        }

        if($postData['nickname'] !== $userInfo->nickname){
            $update['nickname'] = $postData['nickname'];
        }

        if(empty($update)){
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '操作失败,请修改后再提交');
        }

        $res = Merchant::where('id',$this->userInfo->id)->update($update);
        if ($res === false) {
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '操作失败');
        } else {
            return $this->buildSuccess([]);
        }
    }

    /**
     * 商户支付密码修改
     * @return array
     * @author
     */
    public function payPasswordEdit() {
        $postData = $this->request->post();
        $update = [];

        if(empty($postData['pay_password'])){
            return $this->buildFailed(ReturnCode::EMPTY_PARAMS, '请填写修改后的密码!');
        }

        if(empty($postData['oldPassword'])){
            return $this->buildFailed(ReturnCode::EMPTY_PARAMS, '请填写原始密码!');
        }

        $userInfo = Merchant::field('pay_password')->find($this->userInfo->id);

        $oldPass = Tools::userMd5($postData['oldPassword']);
        if ($oldPass !== $userInfo->pay_password) {
            return $this->buildFailed(ReturnCode::INVALID, '原始密码不正确');
        }

        $update['pay_password'] = Tools::userMd5($postData['pay_password']);

        if(empty($update)){
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '操作失败,请修改后再提交');
        }

        $res = Merchant::where('id',$this->userInfo->id)->update($update);
        if ($res === false) {
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '操作失败');
        } else {
            return $this->buildSuccess([]);
        }
    }

    //获取谷歌验证码
    public function getGoogleQrcode()
    {
        $merchantId = $this->userInfo->id;
        $password = $this->request->post('password','');
        $merchantInfo = Merchant::field('mobile,google_secret_key,password')->find($merchantId);
        $secretKey = $merchantInfo->google_secret_key;

        if(empty($password)){
            return $this->buildFailed(ReturnCode::INVALID, '请输入密码');
        }

        $password = Tools::userMd5($password);
        if ($password === $merchantInfo->password) {
            //第一次生成key,保存到账户信息中
            if(!$secretKey){
                $ga = new GoogleAuthenticator();
                $secretKey = $ga->createSecret();
                $merchantInfo->google_secret_key = $secretKey;
                $merchantInfo->save();
            }
            $accountName = $merchantInfo->mobile;
            $title = env('systemName','').'商家中心';
            //返回谷歌验证图片
            $qrcode = GoogleService::getGoogleQrcode($accountName,$secretKey,$title);
            return $this->buildSuccess(['qrcodeUrl'=>$qrcode]);
        } else {
            return $this->buildFailed(ReturnCode::INVALID, '密码不正确');
        }
    }

    public function addGoogleAuth()
    {
        $code = $this->request->post('code');
        if(empty($code)){
            return $this->buildFailed(ReturnCode::INVALID, '请输入验证码');
        }

        $merchantId = $this->userInfo->id;
        $merchantInfo = Merchant::field('google_secret_key,password')->find($merchantId);
        $secretKey = $merchantInfo->google_secret_key;

        //返回谷歌验证图片
        $getCode = GoogleService::getGoogleCode( $secretKey );

        if($code !== $getCode){
            return $this->buildFailed(ReturnCode::INVALID, '验证失败,请重新输入!');
        }

        $merchantInfo->used_google_code = 1;
        $merchantInfo->save();

        return $this->buildSuccess([],'验证成功!');
    }


}
