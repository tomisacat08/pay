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
use app\model\Agent as AgentModel;
use app\model\BankCard as BankCardModel;
use app\model\Bank as BankModel;
use app\admin\validate\BankCard as BankCardvalidate;

class BankCard extends Base{
    /**
     * 银行列表
     * @return array
     * @author
     */
    public function payWay(){
        $listObj = db('bank')->select();
        return $this->buildSuccess(['data'=>$listObj]);
    }
    /**
     * 银行卡列表
     * @return array
     * @author
     */
    public function index(){
        $limit = $this->request->get('size', config('apiAdmin.ADMIN_LIST_DEFAULT'));
        $start = $this->request->get('page', 1);
        $listObj = (new BankCardModel())->where(['type'=>1])->order('create_time DESC')
            ->paginate($limit, false, ['page' => $start])->toArray();
        $listInfo = $listObj['data'];
        return $this->buildSuccess([
            'list'  => $listInfo,
            'count' => $listObj['total']
        ]);
    }
    /**
     * 银行列表
     * @return array
     * @author
     */
    public function indexBank(){
        $limit = $this->request->get('size', config('apiAdmin.ADMIN_LIST_DEFAULT'));
        $start = $this->request->get('page', 1);
        $listObj = (new BankModel())->paginate($limit, false, ['page' => $start])->toArray();
        $listInfo = $listObj['data'];
        return $this->buildSuccess([
            'list'  => $listInfo,
            'count' => $listObj['total']
        ]);
    }
    /**
     * 新增銀行
     * @return array
     * @author
     */
    public function addBank(){
        $postData = $this->request->post();
        if(!$postData['name']){
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '银行名称不能为空');
        }
        if(!$postData['code']){
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '银行编号不能为空');
        }
        $postData['create_time']= time();
        $postData['type']= 3;
        $postData['status']= 1;
        $res = BankModel::create($postData);
        if ($res === false) {
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '操作失败');
        } else {
            return $this->buildSuccess([]);
        }
    }
    /**
     * 新增銀行卡
     * @return array
     * @author
     */
    public function add(){
        $postData = $this->request->post();
        //参数验证
        $validate = new BankCardvalidate();
        $result = $validate->scene('add')->check($postData);
        if ($result !== true) {
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, $validate->getError());
        }
        $postData['create_time']= time();
        $postData['type']= 1;
        $postData['status']= 1;
        $userInfo = $this->userInfo;
        $postData['uid']= $userInfo['id'];
        $res = BankCardModel::create($postData);
        $id = $res->id;
        if ($res === false) {
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '操作失败');
        } else {
            db('bank_card')->where(['id'=>['neq',$id],'type'=>1])->update(['status'=>2]);
            return $this->buildSuccess([]);
        }
    }
    /**
     * 銀行编辑
     * @author
     * @return array
     */
    public function editBank() {
        $postData = $this->request->post();
        $res = BankModel::update($postData);
        if ($res === false) {
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '操作失败');
        } else {
            return $this->buildSuccess([]);
        }
    }
    /**
     * 銀行卡编辑
     * @author
     * @return array
     */
    public function edit() {
        $postData = $this->request->post();
        //参数验证
        $validate = new BankCardvalidate();
        $result = $validate->scene('edit')->check($postData);
        if ($result !== true) {
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, $validate->getError());
        }
        $res = BankCardModel::update($postData);
        if ($res === false) {
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '操作失败');
        } else {
            return $this->buildSuccess([]);
        }
    }
    /**
     * 删除銀行
     * @return array
     * @author
     */
    public function delBank() {
        $id = $this->request->get('id');
        if (!$id) {
            return $this->buildFailed(ReturnCode::EMPTY_PARAMS, '缺少必要参数');
        }
        BankModel::destroy($id);
        BankModel::destroy(['id' => $id]);

        return $this->buildSuccess([]);

    }/**
     * 删除銀行卡
     * @return array
     * @author
     */
    public function del() {
        $id = $this->request->get('id');
        if (!$id) {
            return $this->buildFailed(ReturnCode::EMPTY_PARAMS, '缺少必要参数');
        }
        BankCardModel::destroy($id);
        BankCardModel::destroy(['id' => $id]);

        return $this->buildSuccess([]);

    }
    /**
     * 銀行卡默认开关
     * @return array
     * @author
     */
    public function changeStatus() {
        $id = $this->request->get('id');
        $status = $this->request->get('status');
        if (!$id) {
            return $this->buildFailed(ReturnCode::EMPTY_PARAMS, '缺少必要参数');
        }
        $res = BankCardModel::update([
            'id'         => $id,
            'status'     => $status,
        ]);
        db('bank_card')->where(['id'=>['neq',$id],'type'=>1])->update(['status'=>2]);
        if ($res === false) {
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '操作失败');
        } else {
            return $this->buildSuccess([]);
        }
    }

}