<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/12 0012
 * Time: 10:47
 */

namespace app\admin\controller;

use app\util\Tools;
use app\util\ReturnCode;
use app\model\Merchant as MerchantModel;
use app\model\MerchantWithdrawAudit;
use app\model\Intermediary as IntermediaryModel;
use app\model\MerchantOrder as MerchantOrderModel;
use app\admin\validate\Intermediary as Intermediaryvalidate;

class Intermediary extends Base{
    /**
     * 中间人列表
     * @return array
     * @author
     */
    public function index(){
        $limit = $this->request->get('size', config('apiAdmin.ADMIN_LIST_DEFAULT'));
        $start = $this->request->get('page', 1);
        $keywords = $this->request->get('keywords', '');
        $where = [];
        if ($keywords) {
            $where['mobile|nickname'] = ['like', "%{$keywords}%"];
        }
        $listObj = (new IntermediaryModel())->where($where)->order('create_time DESC')
            ->paginate($limit, false, ['page' => $start])->toArray();
        $listInfo = $listObj['data'];
        foreach ($listInfo as $key=>&$val){
            $val['last_login_time'] = date('Y-m-d H:i:s',$val['last_login_time']);
        }
        return $this->buildSuccess([
            'list'  => $listInfo,
            'count' => $listObj['total']
        ]);
    }

    /**
     * 新增中间人
     * @return array
     * @author
     */
    public function add(){
        $postData['nickname'] = $this->request->post('nickname');
        $postData['mobile'] = $this->request->post('mobile');
        $postData['password'] = $this->request->post('password');
        $postData['account_holder'] = $this->request->post('account_holder');
        //参数验证
        $validate = new Intermediaryvalidate();
        $result = $validate->scene('add')->check($postData);
        if ($result !== true) {
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, $validate->getError());
        }
        $postData['last_login_ip'] = request()->ip();
        $postData['last_login_time'] =$postData['create_time']= time();
        $postData['password'] = Tools::userMd5($postData['password']);
        $res = IntermediaryModel::create($postData);
        if ($res === false) {
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '操作失败');
        } else {
            return $this->buildSuccess([]);
        }
    }
    /**
     * 中间人编辑
     * @author
     * @return array
     */
    public function edit() {
        $postData = $this->request->post();
        //参数验证
        $validate = new Intermediaryvalidate();
        $result = $validate->scene('edit')->check($postData);
        if ($result !== true) {
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, $validate->getError());
        }
        $updateData = [];
        if($postData['password']){
            $updateData['password'] = Tools::userMd5($postData['password']);
        }
        if($postData['nickname']){
            $updateData['nickname'] = $postData['nickname'];
        }

        if(empty($updateData)){
            return $this->buildSuccess([]);
        }
        $id = $postData['id'];
        $res = IntermediaryModel::where('id',$id)->update($updateData);
        if ($res === false) {
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '操作失败');
        }

        return $this->buildSuccess([]);

    }
    /**
     * 商户登录开关
     * @return array
     * @author
     */
    public function changeStatus() {
        $id = $this->request->get('id');
        $status = $this->request->get('status');
        $res = IntermediaryModel::update([
            'id'         => $id,
            'status'     => $status,
        ]);
        if ($res === false) {
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '操作失败');
        } else {
            return $this->buildSuccess([]);
        }
    }

    /**
     * TODO
     * 商户代理提现
     * @return array
     */
    public function withdrawal()
    {

    }


    /**
     * TODO
     * 商户代理资金明细
     * @return array
     */
    public function balanceDetail()
    {
        $page = $this->request->get('page/d', 1);
        $limit = $this->request->get('size/d', 15);
    }
}
