<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/12 0012
 * Time: 10:47
 */

namespace app\merchant\controller;
use app\util\Tools;
use app\util\ReturnCode;
use app\model\Merchant as MerchantModel;
use app\model\BankCard as BankCardModel;
use app\admin\validate\BankCard as BankCardvalidate;

class Merchant extends Base{

    /**
     * 银行列表
     * @return array
     * @author
     */
    public function indexBank(){
        $listObj = db('bank')->select();
        return $this->buildSuccess(['data'=>$listObj]);
    }
    /**
     * 银行卡列表
     * @return array
     * @author
     */
    public function indexCard(){
        $limit = $this->request->get('size', config('apiAdmin.ADMIN_LIST_DEFAULT'));
        $start = $this->request->get('page', 1);
        $listObj = (new BankCardModel())->where(['type'=>3,'uid'=> $this->merchantInfo[ 'id']])->order('create_time DESC')
                                        ->paginate($limit, false, ['page' => $start])->toArray();
        $listInfo = $listObj['data'];
        return $this->buildSuccess([
            'list'  => $listInfo,
            'count' => $listObj['total']
        ]);
    }
    /**
     * 新增銀行卡
     * @return array
     * @author
     */
    public function addCard(){
        $postData = $this->request->post();
        //参数验证
        $validate = new BankCardvalidate();
        $result = $validate->scene('add')->check($postData);
        if ($result !== true) {
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, $validate->getError());
        }

        $userInfo = $this->merchantInfo;
        $existsBankInfo = db('bank_card')->where(['uid'=>$userInfo['id'],'type'=>3,'card'=>$postData['card']])->find();
        if($existsBankInfo){
            $existsBankInfo->status = 1;
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '此银行卡已存在在您卡列表中,已帮您启用此卡!');
        }
        $create['bank_address']= trim($postData['bank_address']);
        $create['bank_name']= trim($postData['bank_name']);
        $create['card']= trim($postData['card']);
        $create['name']= trim($postData['name']);
        $create['create_time']= time();
        $create['type']= 3;
        $create['status']= 1;
        $create['uid']= $userInfo['id'];
        $create['audit_type']= 1;
        $res = BankCardModel::create($create);
        if ($res === false) {
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '操作失败');
        } else {
            //$id = $res->id;
            //db('bank_card')->where(['uid'=>$userInfo['id'],'id'=>['neq',$id],'type'=>3])->update(['status'=>2]);
            return $this->buildSuccess([]);
        }
    }
    /**
     * 銀行卡编辑
     * @author
     * @return array
     */
    public function editCard() {
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
     * 删除銀行卡
     * @return array
     * @author
     */
    public function delCard() {
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
    public function changeCardStatus() {
        $id = $this->request->get('id');
        $status = $this->request->get('status');
        $res = BankCardModel::update([
            'id'         => $id,
            'status'     => $status,
        ]);

        if ($res === false) {
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '操作失败');
        } else {
            return $this->buildSuccess([]);
        }
    }

}