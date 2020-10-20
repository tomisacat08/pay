<?php
/**
 * 登录登出
 * @since   2017-11-02
 * @author  zhaoxiang <zhaoxiang051405@gmail.com>
 */

namespace app\agent\controller;


use app\admin\service\GoogleService;
use app\agent\model\AgentAuthGroupAccess;
use app\agent\model\AgentAuthRule;
use app\agent\model\AgentMenu;
use app\agent\model\Agent;
use app\model\AgentLoginLog;
use app\util\ReturnCode;
use app\util\Tools;
use think\Cache;

class Login extends Base
{

    private $loginNum = 5;//登录错误次数限制
    private $waiteTime = 120; //登录错误达到限制后 n 分钟后才可以登录
    /**
     * 用户登录
     * @return array
     * @throws \think\Exception
     * @throws \think\exception\DbException
     * @author zhaoxiang <zhaoxiang051405@gmail.com>
     */
    public function index()
    {

        $mobile = $this->request->post('mobile/d');
        $password = $this->request->post('password/s');
        $code = $this->request->post('code/d','');

        if (!$mobile) {
            return $this->buildFailed(ReturnCode::LOGIN_ERROR, '缺少用户名!');
        }
        if (!$password) {
            return $this->buildFailed(ReturnCode::LOGIN_ERROR, '缺少密码!');
        }

        //当前请求ip地址
        $ip = $this->request->ip();
        $loginIpLock = 'agentLogin:'.$mobile.':'.$ip;
        $loginNum = Cache::get($loginIpLock,0);
        if($loginNum > $this->loginNum){
            return $this->buildFailed(ReturnCode::LOGIN_ERROR, '密码输错'.$this->loginNum.'次,请稍后再试');
        }

        $agentInfo = Agent::where('mobile', $mobile)->find();

        if(empty($agentInfo)){
            return $this->buildFailed(ReturnCode::LOGIN_ERROR, '用户名或密码不正确');
        }

        if(empty($agentInfo->status)){
            return $this->buildFailed(ReturnCode::LOGIN_ERROR, '用户已被封禁，请联系管理员');
        }

        do{
            $msg = '登录成功';
            //是否启用谷歌验证
            if($agentInfo->used_google_code == 1){
                if (!$code) {
                    return $this->buildFailed(ReturnCode::LOGIN_ERROR, '请输入谷歌验证码!');
                }

                $check = GoogleService::check($agentInfo->google_secret_key,$code);
                if(!$check){
                    $msg = '验证码错误';
                    break;
                }
            }

            $password = Tools::userMd5($password);
            $check = $agentInfo->password == $password;
            if(!$check){
                $time = $this->waiteTime * 60;
                $loginNum++;
                Cache::set($loginIpLock,$loginNum,$time);
                $msg = '用户名密码不正确';
                break;
            }
        }while(false);

        $userId = $agentInfo->id;
        //尝试验证登录,记录日志,预防非法登录情况
        $data = [];
        $data['agent_id'] = $userId;
        $data['ip'] = $ip;
        $data['create_time'] = time();
        $data['type'] = $check ? 1 : 2 ;
        AgentLoginLog::create($data);

        if(!$check){
            return $this->buildFailed(ReturnCode::LOGIN_ERROR, $msg);
        }

        //更新用户数据
        //登录成功,清理封禁次数
        Cache::rm($loginIpLock);
        $apiAuth = md5(uniqid() . time()).$userId;
        //更新用户数据
        $agentInfo->last_login_ip = $this->request->ip();
        $agentInfo->last_login_time = time();
        $agentInfo->login_times++;
        $agentInfo->user_token = $apiAuth;
        $agentInfo->save();


        //判断是否是子账号登录
        $subInfo = [];
        $isSub = false;
        if ($agentInfo['parent_id'] != 0) {
            $isSub = true;
            //是子账号则重新获取主账号信息
            $subInfo = $agentInfo;//子账号信息
            $agentInfo = Agent::get(['id' => $subInfo['parent_id']]);//获取主账号信息
        }


        //分配权限
        $return['access'] = [];
//        $isSupper = Tools::isAdministrator($agentInfo['id']);

        if (!$isSub) {//主账号
            $access = AgentMenu::all(['hide' => 0]);
            $access = Tools::buildArrFromObj($access);
            $return['access'] = array_values(array_filter(array_column($access, 'url')));
        } else {
            $groups = AgentAuthGroupAccess::get(['uid' => $subInfo['id']]);
            if (isset($groups)) {
                $access = (new AgentAuthRule())->whereIn('groupId', $groups->groupId)->select();
                $access = Tools::buildArrFromObj($access);
                $return['access'] = array_values(array_unique(array_column($access, 'url')));
            }
        }

        $return['id'] = $agentInfo['id'];
        $return['mobile'] = $agentInfo['mobile'];
        $return['nickname'] = $agentInfo['nickname'];
        $return['agentToken'] = $apiAuth;
        $return['isSub'] = $isSub;
        $return['subInfo'] = $subInfo;

        return $this->buildSuccess($return, '登录成功');
    }

    /**
     * @return array
     */
    public function logout()
    {
        $userId = $this->agentInfo->id;
        $update = Agent::where('id',$userId)->update(['user_token'=>'']);
        if($update === false){
            return $this->buildFailed(ReturnCode::LOGIN_ERROR, '登出失败,请重试');
        }

        return $this->buildSuccess([], '登出成功');
    }

}
