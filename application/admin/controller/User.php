<?php
/**
 * 用户管理
 * @since   2018-02-06
 * @author  zhaoxiang <zhaoxiang051405@gmail.com>
 */

namespace app\admin\controller;


use app\admin\service\GoogleService;
use app\model\AdminAuthGroupAccess;
use app\model\AdminUser;
use app\model\AdminUserData;
use app\util\GoogleAuthenticator;
use app\util\ReturnCode;
use app\util\Tools;
use think\Db;

class User extends Base {

    /**
     * 获取用户列表
     * @return array
     * @throws \think\exception\DbException
     * @author zhaoxiang <zhaoxiang051405@gmail.com>
     */
    public function index() {

        $limit = $this->request->get('size', config('apiAdmin.ADMIN_LIST_DEFAULT'));
        $start = $this->request->get('page', 1);
        $type = $this->request->get('type', '');
        $keywords = $this->request->get('keywords', '');
        $status = $this->request->get('status', '');

        $where = [];
        if ($status === '1' || $status === '0') {
            $where['status'] = $status;
        }
        if ($type) {
            switch ($type) {
                case 1:
                    $where['username'] = ['like', "%{$keywords}%"];
                    break;
                case 2:
                    $where['nickname'] = ['like', "%{$keywords}%"];
                    break;
            }
        }
        $where['username'] = ['neq','root'];
        $listObj = (new AdminUser())->
            field('id,username,nickname,login_times,last_login_time,last_login_ip,used_google_code,status')
            ->where($where)
            ->order('id DESC')
            ->paginate($limit, false, ['page' => $start])
            ->toArray();
        $listInfo = $listObj['data'];
        $idArr = array_column($listInfo, 'id');

        $userGroup = AdminAuthGroupAccess::all(function($query) use ($idArr) {
            $query->whereIn('uid', $idArr);
        });
        $userGroup = Tools::buildArrFromObj($userGroup);
        $userGroup = Tools::buildArrByNewKey($userGroup, 'uid');

        foreach ($listInfo as $key => &$value) {
            $value['last_login_time'] = empty($value['last_login_time']) ? '' : date('Y-m-d H:i:s', $value['last_login_time']);
            if (isset($userGroup[$value['id']])) {
                $value['groupId'] = explode(',', $userGroup[$value['id']]['groupId']);
            } else {
                $value['groupId'] = [];
            }
        }

        return $this->buildSuccess([
            'list'  => $listInfo,
            'count' => $listObj['total']
        ]);
    }

    /**
     * 新增用户
     * @return array
     * @author zhaoxiang <zhaoxiang051405@gmail.com>
     */
    public function add(){
        $groups = '';
        $postData = $this->request->post();
        $postData['regIp'] = request()->ip();
        $postData['regTime'] = time();
        $postData['password'] = Tools::userMd5($postData['password']);
        $userExist = AdminUser::where('username',$postData['username'])->count();
        if($userExist>0){
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '用户名已存在');
        }
        $nickExist = AdminUser::where('nickname',$postData['nickname'])->count();
        if($nickExist>0){
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '昵称已存在');
        }
        if ($postData['groupId']) {
            $groups = trim(implode(',', $postData['groupId']), ',');
        }
        unset($postData['groupId']);
        $res = AdminUser::create($postData);
        if ($res === false) {
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '操作失败');
        } else {
            AdminAuthGroupAccess::create([
                'uid'     => $res->id,
                'groupId' => $groups
            ]);

            return $this->buildSuccess([]);
        }
    }

    /**
     * 获取当前组的全部用户
     * @return array
     * @throws \think\Exception
     * @throws \think\exception\DbException
     * @author zhaoxiang <zhaoxiang051405@gmail.com>
     */
    public function getUsers() {
        $limit = $this->request->get('size/d', config('apiAdmin.ADMIN_LIST_DEFAULT'));
        $page = $this->request->get('page/d', 1);
        $gid = $this->request->get('gid/d', 0);
        if (!$gid) {
            return $this->buildFailed(ReturnCode::PARAM_INVALID, '非法操作');
        }

        $totalNum = (new AdminAuthGroupAccess())->where('find_in_set("' . $gid . '", `groupId`)')->count();
        $start = $limit * ($page - 1);
        $sql = "SELECT au.* FROM pay_admin_user as au LEFT JOIN pay_admin_auth_group_access as aaga " .
            " ON aaga.`uid` = au.`id` WHERE find_in_set('{$gid}', aaga.`groupId`) " .
            " ORDER BY au.regTime DESC LIMIT {$start}, {$limit}";
        $userInfo = Db::query($sql);

        $uidArr = array_column($userInfo, 'id');
        $userData = (new AdminUserData())->whereIn('uid', $uidArr)->select();
        $userData = Tools::buildArrByNewKey($userData, 'uid');

        foreach ($userInfo as $key => $value) {
            if (isset($userData[$value['id']])) {
                $userInfo[$key]['lastLoginIp'] = $userData[$value['id']]['lastLoginIp'];
                $userInfo[$key]['loginTimes'] = $userData[$value['id']]['loginTimes'];
                $userInfo[$key]['lastLoginTime'] = date('Y-m-d H:i:s', $userData[$value['id']]['lastLoginTime']);
            }
            $userInfo[$key]['regIp'] = $userInfo[$key]['regIp'];
        }

        return $this->buildSuccess([
            'list'  => $userInfo,
            'count' => $totalNum
        ]);
    }

    /**
     * 用户状态编辑
     * @return array
     * @author zhaoxiang <zhaoxiang051405@gmail.com>
     */
    public function changeStatus() {
        $id = $this->request->get('id');
        $status = $this->request->get('status');
        $res = AdminUser::update([
            'id'         => $id,
            'status'     => $status,
            'updateTime' => time()
        ]);
        if ($res === false) {
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '操作失败');
        } else {
            return $this->buildSuccess([]);
        }
    }

    /**
     * 编辑用户
     * @author zhaoxiang <zhaoxiang051405@gmail.com>
     * @return array
     * @throws \think\exception\DbException
     */
    public function edit() {
        $groups = '';
        $params = $this->request->post();

        if (empty($params['nickname'])) {
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '昵称不能为空');
        }

        $nickExist = AdminUser::where('id','neq',$params['id'])->where('nickname',$params['nickname'])->count('id');
        if($nickExist>1){
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '昵称已存在');
        }
        $adminUserInfo = AdminUser::find($params['id']);
        $adminUserInfo->nickname = $params['nickname'];
        $adminUserInfo->updateTime = time();

        if (!empty($params['password'])) {
            $adminUserInfo->password = Tools::userMd5($params['password']);
        }

        $res = $adminUserInfo->save();
        if ($res === false) {
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '操作失败');
        }

        if ($params['groupId']) {
            $groups = implode(',', $params['groupId']);
        }

        $accessInfo = AdminAuthGroupAccess::get(['uid' => $params['id']]);
        if ($accessInfo) {
            $accessInfo->groupId = $groups;
            $accessInfo->save();
        } else {
            AdminAuthGroupAccess::create([
                'uid'     => $params['id'],
                'groupId' => $groups
            ]);
        }

        return $this->buildSuccess([]);

    }

    /**
     * 修改自己的信息
     * @author zhaoxiang <zhaoxiang051405@gmail.com>
     * @return array
     * @throws \think\exception\DbException
     */
    public function own() {
        $password = $this->request->post('password/s','');
        $oldPassword = $this->request->post('oldPassword/s','');
        $nickname = $this->request->post('nickname/s','');

        if ($password && $oldPassword) {
            $oldPass = Tools::userMd5($oldPassword);
            if ($oldPass !== $this->userInfo->password) {
                return $this->buildFailed(ReturnCode::INVALID, '原始密码不正确');
            }

            $this->userInfo->password = Tools::userMd5($password);
        }

        $this->userInfo->nickname = $nickname;
        $res = $this->userInfo->save();
        if ($res === false) {
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '操作失败');
        }
        return $this->buildSuccess([]);
    }

    /**
     * 删除用户
     * @return array
     * @author zhaoxiang <zhaoxiang051405@gmail.com>
     */
    public function del() {
        $id = $this->request->get('id');
        if (!$id) {
            return $this->buildFailed(ReturnCode::EMPTY_PARAMS, '缺少必要参数');
        }
        AdminUser::destroy($id);
        AdminAuthGroupAccess::destroy(['uid' => $id]);

        return $this->buildSuccess([]);

    }

    //获取谷歌验证码
    public function getGoogleQrcode()
    {
        $adminId = $this->userInfo['id'];
        $password = $this->request->post('password','');
        $adminInfo = AdminUser::field('username,google_secret_key,password')->find($adminId);
        $secretKey = $adminInfo->google_secret_key;

        if(empty($password)){
            return $this->buildFailed(ReturnCode::INVALID, '请输入密码');
        }

        $password = Tools::userMd5($password);
        if ($password === $adminInfo->password) {
            //第一次生成key,保存到账户信息中
            if(!$secretKey){
                $ga = new GoogleAuthenticator();
                $secretKey = $ga->createSecret();
                $adminInfo->google_secret_key = $secretKey;
                $adminInfo->save();
            }
            $accountName = $adminInfo->username;
            $title = env('systemName','').'平台中心';
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

        $adminId = $this->userInfo['id'];
        $adminInfo = AdminUser::field('username,google_secret_key,password')->find($adminId);
        $secretKey = $adminInfo->google_secret_key;

        //返回谷歌验证图片
        $getCode = GoogleService::getGoogleCode( $secretKey );

        if($code !== $getCode){
            return $this->buildFailed(ReturnCode::INVALID, '验证失败,请重新输入!');
        }

        $adminInfo->used_google_code = 1;
        $adminInfo->save();

        return $this->buildSuccess([],'验证成功!');
    }

}
