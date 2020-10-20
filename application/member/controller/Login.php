<?php
/**
 * 登录登出
 * @since   2017-11-02
 * @author  zhaoxiang <zhaoxiang051405@gmail.com>
 */

namespace app\member\controller;

use app\model\Member;
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
        $username = $this->request->post('username');
        $password = $this->request->post('password');
        //当前请求ip地址
        $ip = $this->request->ip();
        $loginNum = Cache::get('member_'.$ip);
        $loginNum = empty($loginNum) ? 0 : $loginNum;
        if($loginNum > $this->loginNum){
            return $this->buildFailed(ReturnCode::LOGIN_ERROR, '密码输错'.$this->loginNum.'次,请稍后再试');
        }
        if (!$username) {
            return $this->buildFailed(ReturnCode::LOGIN_ERROR, '缺少用户名!');
        }
        if (!$password) {
            return $this->buildFailed(ReturnCode::LOGIN_ERROR, '缺少密码!');
        } else {
            $password = Tools::userMd5($password);
        }
        $userInfo = Member::get(['mobile' => $username, 'password' => $password]);
        $apiAuth = md5(uniqid() . time());
        if (!empty($userInfo)) {
            if ($userInfo['status']==1) {
                //更新用户数据
                $data['id'] = $userInfo['id'];
                $data['last_login_ip'] = $this->request->ip();
                $data['last_login_time'] = time();
                $data['user_token'] = $apiAuth;
                Member::update($data);
            } else {
                return $this->buildFailed(ReturnCode::LOGIN_ERROR, '用户已被封禁，请联系管理员');
            }
        } else {
            $time = $this->waiteTime * 60;
            if(Cache::has('member_'.$ip)){
                $num = Cache::get('member_'.$ip);
                $num++;
                Cache::set('member_'.$ip,$num,$time);
            }else{
                Cache::set('member_'.$ip,1,$time);
            }
            return $this->buildFailed(ReturnCode::LOGIN_ERROR, '用户名密码不正确');
        }

        cache('member:' . $apiAuth, json_encode($userInfo), config('apiAdmin.ONLINE_TIME'));
        cache('member:' . $userInfo['id'], $apiAuth, config('apiAdmin.ONLINE_TIME'));

        /*$return['access'] = [];
        $isSupper = Tools::isAdministrator($userInfo['id']);
        if ($isSupper) {
            $access = AdminMenu::all(['hide' => 0]);
            $access = Tools::buildArrFromObj($access);
            $return['access'] = array_values(array_filter(array_column($access, 'url')));
        } else {
            $groups = AdminAuthGroupAccess::get(['uid' => $userInfo['id']]);
            if (isset($groups) || $groups->groupId) {
                $access = (new AdminAuthRule())->whereIn('groupId', $groups->groupId)->select();
                $access = Tools::buildArrFromObj($access);
                $return['access'] = array_values(array_unique(array_column($access, 'url')));
            }
        }*/
        $return['id'] = $userInfo['id'];
        $return['username'] = $userInfo['mobile'];
        $return['nickname'] = $userInfo['nickname'];

        $return['apiAuth'] = $apiAuth;

        return $this->buildSuccess($return, '登录成功');
    }

    public function logout() {
        $ApiAuth = $this->request->header('ApiAuth');
        cache('member:' . $ApiAuth, null);
        cache('member:' . $this->userInfo['id'], null);

        return $this->buildSuccess([], '登出成功');
    }

}
