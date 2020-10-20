<?php
/**
 * 登录登出
 * @since   2017-11-02
 * @author  zhaoxiang <zhaoxiang051405@gmail.com>
 */

namespace app\intermediary\controller;

use app\admin\service\GoogleService;
use app\model\Intermediary;
use app\model\MerchantLoginLog;
use app\util\ReturnCode;
use app\util\Tools;
use think\Cache;

class Login extends Base {
    private $loginNum = 5;//登录错误次数限制
    private $waiteTime = 120; //登录错误达到限制后 n 分钟后才可以登录

    /**
     * 用户登录
     * @return array
     * @throws \think\Exception
     * @throws \think\exception\DbException
     * @author zhaoxiang <zhaoxiang051405@gmail.com>
     */
    public function index() {
        $username = $this->request->post('username/d');
        $password = $this->request->post('password/s');
        $code = $this->request->post('code/d','');

        if (!$username) {
            return $this->buildFailed(ReturnCode::LOGIN_ERROR, '缺少用户名!');
        }
        if (!$password) {
            return $this->buildFailed(ReturnCode::LOGIN_ERROR, '缺少密码!');
        }

        //当前请求ip地址
        $ip = $this->request->ip();
        $loginIpLock = 'intermediaryLogin:'.$username.':'.$ip;
        $loginNum = Cache::get($loginIpLock,0);
        if($loginNum > $this->loginNum){
            return $this->buildFailed(ReturnCode::LOGIN_ERROR, '密码输错'.$this->loginNum.'次,请稍后再试');
        }

        $userInfo = Intermediary::where('mobile', $username)->find();

        if(empty($userInfo)){
            return $this->buildFailed(ReturnCode::LOGIN_ERROR, '用户名或密码不正确');
        }

        if(empty($userInfo->status)){
            return $this->buildFailed(ReturnCode::LOGIN_ERROR, '用户已被封禁，请联系管理员');
        }

        do{
            $msg = '登录成功';
            //是否启用谷歌验证
            if($userInfo->used_google_code == 1){
                if (!$code) {
                    return $this->buildFailed(ReturnCode::LOGIN_ERROR, '请输入谷歌验证码!');
                }

                $check = GoogleService::check($userInfo->google_secret_key,$code);
                if(!$check){
                    $msg = '验证码错误';
                    break;
                }
            }

            $password = Tools::userMd5($password);
            $check = $userInfo->password == $password;
            if(!$check){
                $time = $this->waiteTime * 60;
                $loginNum++;
                Cache::set($loginIpLock,$loginNum,$time);
                $msg = '用户名密码不正确';
                break;
            }
        }while(false);

        $userId = $userInfo->id;
        //尝试验证登录,记录日志,预防非法登录情况
        $data = [];
        $data['merchant_id'] = $userId;
        $data['ip'] = $this->request->ip();
        $data['create_time'] = time();
        $data['type'] = $check ? 1 : 2 ;
        MerchantLoginLog::create($data);

        if(!$check){
            return $this->buildFailed(ReturnCode::LOGIN_ERROR, $msg);
        }

        //更新用户数据
        //登录成功,清理封禁次数
        Cache::rm($loginIpLock);
        $apiAuth = md5(uniqid() . time()).$userId;
        //更新用户数据
        $userInfo->last_login_ip = $this->request->ip();
        $userInfo->last_login_time = time();
        $userInfo->login_times++;
        $userInfo->user_token = $apiAuth;
        $userInfo->save();

        $return = [
            'id'=>$userInfo->id,
            'username'=>$userInfo->mobile,
            'nickname'=>$userInfo->nickname,
            'apiAuth'=>$apiAuth,
        ];

        return $this->buildSuccess($return, '登录成功');
    }

    public function logout() {
        $userId = $this->userInfo->id;
        $update = Intermediary::where('id',$userId)->update(['user_token'=>'']);
        if($update !== false){
            return $this->buildSuccess([], '登出成功');
        }

        return $this->buildFailed(ReturnCode::LOGIN_ERROR, '登出失败,请重试!');

    }

}
