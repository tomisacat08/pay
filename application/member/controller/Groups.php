<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/12 0012
 * Time: 10:47
 */

namespace app\member\controller;
use app\api\service\AppApiService;
use app\member\service\GroupsService;
use app\util\Tools;
use app\util\ReturnCode;
use app\model\member as memberModel;
use app\model\BankCard as BankCardModel;
use app\admin\validate\BankCard as BankCardvalidate;

class Groups extends Base{

    /**
     * 微信账号列表
     * @return \think\response\Json
     * @throws \think\exception\DbException
     * @author
     * @date   2019/3/28 9:37
     */
    public function index()
    {
        $num = $this->request->get('num', config('apiAdmin.ADMIN_LIST_DEFAULT'));
        $page = $this->request->get('page', 1);
        $title = $this->request->get('title', '');
        $offset = ($page-1)*$num;
        $userInfo = $this->userInfo;

        $where = [];
        $where['member_id'] = $userInfo['id'];
        if (!empty($title)) {
            $where['title'] = ['like', "%" . $title . "%"];
        }

        //分组列表
        $service = new GroupsService();
        $return = $service->getGroupList($where,$offset,$num);
        return $this->buildSuccess(['list'=>$return]);
    }
    /**
     * 添加微信账号
     * @return \think\response\Json
     * @throws \think\exception\DbException
     * @author
     * @date   2019/3/28 9:37
     */
    public function add()
    {
        $title= $this->request->post('title');
        $desc= $this->request->post('desc');
        $userInfo = $this->userInfo;
        $service = new AppApiService();
        $return = $service->createWechat($userInfo['id'],$title,$desc);
        return $this->json($return);
    }

    public function edit()
    {
        $title= $this->request->post('title');
        $desc= $this->request->post('desc');
        $id= $this->request->post('id');
        $userInfo = $this->userInfo;
        $service = new GroupsService();
        $return = $service->edit($userInfo['id'],$id,$title,$desc);
        return $this->json($return);
    }
    /**
     * 删除微信账号
     * @return \think\response\Json
     * @throws \think\exception\DbException
     * @author
     * @date   2019/3/28 9:37
     */
    public function  del(){
        $id = $this->request->get('id');
        $userInfo = $this->userInfo;
        $service = new AppApiService();
        $return = $service->getDelWechat($userInfo['id'],$id);
        return $this->json($return);
    }
    /**
     * 激活微信账号
     * @return \think\response\Json
     * @throws \think\exception\DbException
     * @author
     * @date   2019/3/28 9:37
     */
    public function  changeStatus(){
        $id = $this->request->post('id');
        $status = $this->request->post('status');
        if(empty($status)){
            return $this->json([
                'code' => -1,
                'msg'  => '请直接启用',
                'data' => ''
            ]);
        }

        $userInfo = $this->userInfo;
        $service = new AppApiService();
        $return = $service->getUsedWechat($userInfo['id'],$id);
        return $this->json($return);
    }

}