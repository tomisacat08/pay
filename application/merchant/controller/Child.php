<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/13 0013
 * Time: 20:02
 */

namespace app\merchant\controller;

use app\model\Merchant;
use app\util\ReturnCode;
use app\util\Tools;
use think\Db;
use think\Exception;
use think\Request;

class Child extends Base
{
    /**
     * 新增子账户
     */
    public function add()
    {
        if(!$this->isParent){
            return $this->buildFailed(ReturnCode::PARAM_INVALID, '仅主号允许操作');
        }
        $postData = $this->request->post();
        $validate = new \app\merchant\validate\Child();
        $result = $validate->scene('add')->check($postData);
        if(true !== $result){
            return $this->buildFailed(ReturnCode::PARAM_INVALID, $validate->getError());
        }
        $createData['last_login_ip'] = '';
        $createData['uid'] = 0;
        $createData['last_login_time'] = '';
        $payPassword = $this->request->post('password','123456');
        $createData['pay_password'] = Tools::userMd5($payPassword);
        $createData['password'] = Tools::userMd5($postData['password']);
        $createData['parent_id'] = $this->merchantInfo->id;
        $createData['nickname'] = $postData['nickname'];
        $createData['mobile'] = $postData['mobile'];
        $createData['account_holder'] = $this->merchantInfo->nickname;
        Db::startTrans();
        try{
            Merchant::create($createData);
            Db::commit();
            return $this->buildSuccess([]);
        }catch(\Exception $e){
            Db::rollback();
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '操作失败 '.$e->getMessage());
        }
    }

    /**
     * 子账号列表
     * @return array
     * @throws \think\exception\DbException
     */
    public function index()
    {
        if(!$this->isParent){
            return $this->buildFailed(ReturnCode::PARAM_INVALID, '仅主号允许操作');
        }


        $limit = $this->request->get('size/d', config('apiAdmin.ADMIN_LIST_DEFAULT'));
        $page = $this->request->get('page/d', 1);
        $keywords = $this->request->get('keywords', '');
        $status = $this->request->get('status', '');
        //查询条件
        $where['parent_id'] = $this->merchantInfo->id;
        $where['delete_at'] = 0;
        if (!empty($status)) {
            $where['status'] = $status;
        }
        if (!empty($keywords)) {
            $where['mobile'] = ['like', "%{$keywords}%"];
        }
        //子账户

        $listObj = Merchant::where($where)
                     ->order('create_time desc')
                     ->field('id,parent_id,mobile,nickname,last_login_time,last_login_ip,status')
                     ->paginate($limit, false, [
                         'page' => $page,
                         "query" => Request::instance()->query()
                     ]);

        $listArray = $listObj->toArray();
        $listInfo = $listArray['data'];
        foreach ($listInfo as &$item){
            $item['last_login_time'] = $item['last_login_time'] ? date('Y-m-d H:i:s',$item['last_login_time']) : '';
        }

        return $this->buildSuccess([
            'list' => $listInfo,
            'count' => $listObj->total()
        ]);
    }

    /**
     * 子账号状态编辑
     * @return array
     * @throws \think\exception\DbException
     */
    public function changeStatus()
    {
        if(!$this->isParent){
            return $this->buildFailed(ReturnCode::PARAM_INVALID, '仅主号允许操作');
        }

        $id = $this->request->post('id/d');
        $status = $this->request->post('status/d',1);
        $merchantInfo = Merchant::where(['id'=>$id,'parent_id'=>$this->merchantInfo->id])->find();
        if (empty($merchantInfo)) {
            return $this->buildFailed(ReturnCode::PARAM_INVALID, '参数错误');
        }
        $merchantInfo->status = $status === 1 ? 1 : 2;
        $res = $merchantInfo->save();
        if ($res === false) {
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '操作失败');
        } else {
            return $this->buildSuccess([]);
        }
    }

    /**
     * 编辑子账户
     * @author zhaoxiang <zhaoxiang051405@gmail.com>
     * @return array
     * @throws \think\exception\DbException
     */
    public function edit()
    {

        if(!$this->isParent){
            return $this->buildFailed(ReturnCode::PARAM_INVALID, '仅主号允许操作');
        }

        $postData = $this->request->post();
        $validate = new \app\merchant\validate\Child();
        $result = $validate->scene('edit')->check($postData);
        if(true !== $result)
        {
            return $this->buildFailed(ReturnCode::PARAM_INVALID, $validate->getError());
        }

        $update = [];
        $id = $postData['id'];

        if ($postData['password'] !== '') {
            $update['password'] = Tools::userMd5($postData['password']);
        }

        if ($postData['pay_password'] !== '') {
            $update['pay_password'] = Tools::userMd5($postData['pay_password']);
        }

        if ($postData['nickname']) {
            $update['nickname'] = $postData['nickname'];
        }

        Db::startTrans();
        try{
            Merchant::where('id',$id)->update($update);
            Db::commit();
            return $this->buildSuccess([]);
        }catch(\Exception $e) {
            Db::rollback();
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR,'编辑失败');
        }
    }

    /**
     * 删除子账户
     * @return array
     * @author zhaoxiang <zhaoxiang051405@gmail.com>
     */
    public function del()
    {
        if(!$this->isParent){
            return $this->buildFailed(ReturnCode::PARAM_INVALID, '仅主号允许操作');
        }

        $id = $this->request->post('id');
        if (!$id) {
            return $this->buildFailed(ReturnCode::EMPTY_PARAMS, '缺少必要参数');
        }
        Db::startTrans();
        try{
            Merchant::where('id',$id)
                    ->where('parent_id',$this->merchantInfo->id)
                    ->update(['delete_at'=>time()]);
            Db::commit();
            return $this->buildSuccess([]);
        }catch(\Exception $e) {
            Db::rollback();
            return $this->buildFailed(ReturnCode::DELETE_FAILED,'删除失败');
        }
    }
}
