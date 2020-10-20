<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/12 0012
 * Time: 10:47
 */

namespace app\member\controller;

use app\api\service\AppApiService;
use app\member\service\QrcodeService;
use app\model\MemberImages;
use app\model\MemberWechat;

class Qrcode extends Base{

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
        $money = $this->request->get('money', '');
        $groupName = $this->request->get('group_name', '');
        $isUsed = $this->request->get('is_used', '');
        $offset = ($page-1)*$num;
        $userInfo = $this->userInfo;

        $where = [];
        $where['member_id'] = $userInfo['id'];
        if (!empty($money)) {
            $where['money'] =  $money;
        }

        if (!empty($isUsed)) {
            $where['is_used'] =  $isUsed;
        }
        if (!empty($groupName)) {
            $wechatIdArr = MemberWechat::whereLike('title','%'.$groupName.'%')->column('id');
            $where['wechat_id'] = ['in',$wechatIdArr];
        }

        //分组列表
        $service = new QrcodeService();
        $return = $service->getQrcodeList($where,$offset,$num);
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
        $groupId= $this->request->post('group_id');
        $imgId= $this->request->post('img_id');
        $money= $this->request->post('money');
        if(!is_numeric($money) || $money < 0){
            return $this->json(
                [
                    'code'=>-1,
                    'msg'=>'请输入正确金额',
                    'data'=>[],
                ]
            );
        }

        $userInfo = $this->userInfo;
        $update = MemberImages::where(['id'=>$imgId,'member_id'=>$userInfo['id']])->update(['wechat_id'=>$groupId,'money'=>$money]);
        return $this->json($update);
    }

    /**
     * 添加微信账号
     * @return \think\response\Json
     * @throws \think\exception\DbException
     * @author
     * @date   2019/3/28 9:37
     */
    public function del()
    {
        $imgId= $this->request->post('id');
        if(!is_numeric($imgId) || $imgId < 0){
            return $this->json(
                [
                    'code'=>-1,
                    'msg'=>'参数异常',
                    'data'=>[],
                ]
            );
        }

        $userInfo = $this->userInfo;
        MemberImages::where('id',$imgId)->where('member_id',$userInfo['id'])->update(['delete_at'=>time()]);
        return $this->buildSuccess([]);
    }


    /**
     * 添加微信账号
     * @return \think\response\Json
     * @throws \think\exception\DbException
     * @author
     * @date   2019/3/28 9:37
     */
    public function edit()
    {
        $groupId= $this->request->post('group_id');
        $imgId= $this->request->post('img_id');
        $money= $this->request->post('money');
        if(!is_numeric($money) || $money < 0){
            return $this->json(
                [
                    'code'=>-1,
                    'msg'=>'请输入正确金额',
                    'data'=>[],
                ]
            );
        }

        $money= $this->request->post('money');
        $userInfo = $this->userInfo;
        $update = MemberImages::where(['id'=>$imgId,'member_id'=>$userInfo['id']])->update(['wechat_id'=>$groupId,'money'=>$money]);
        return $this->json($update);
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
        $isUsed = $this->request->post('is_used');
        $userInfo = $this->userInfo;
        $service = new QrcodeService();
        $return = $service->changeStatus($userInfo['id'],$id,$isUsed);
        return $this->json($return);
    }

    /**
     * 上传自动收款码
     * @return \think\response\Json
     * @throws \think\exception\DbException
     * @author
     * @date   2019/04/15 0015 16:25
     */
    public function uploadOnceQrCode() {
        $service = new AppApiService();
        $userInfo = $this->userInfo;

        $return = $service->uploadOnceQrCode($userInfo);
        return $this->json($return);
    }

}