<?php
/**
 * @since   2017-11-02
 * @author  zhaoxiang <zhaoxiang051405@gmail.com>
 */

namespace app\model;


class BankCard extends Base
{
    protected $name = 'bank_card';
    protected $pk = 'id';
    /**
     * 更新或添加银行卡
     * @param $id
     * @param $uid
     * @param $type 1平台2代理商3商户
     * @param $param
     * @return bool
     * @throws \think\exception\DbException
     */
    public function add($id, $uid, $type, $param)
    {
        if (!$card = self::get(['id' => $id, 'type' => $type])) {
            $card = $this;
            $param['uid'] = $uid;
            $param['type'] = $type;
        }
        if (!$card->allowField(true)->save($param)) {
            $this->error = '更新/添加失败';
            return false;
        }
        return true;
    }

    /**
     * 修改银行卡状态
     * @param $id
     * @param $status
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function setStatus($id,$status)
    {
        //当前要修改的记录
        $model = $this->where('id','=',$id)->find();
        if($model['status'] == 1 && $status == 2) {//当前是默认卡 设为非默认
            //当前卡是唯一默认
            $count = $this->where(['uid'=>$model['uid'],'type'=>2,'status'=>1])->count();
            if($count <= 1){
                $this->error = '必须有一张卡是默认的';
                return false;
            }
            $model->save(['status' => $status]);
            return true;
        }
        //设为默认
        if($model['status'] == 2 && $status == 1) {
            //将其他卡设为非默认
            $this->where(['uid'=>$model['uid'],'type'=>2,'status'=>1])->setField('status',2);
            $model->save(['status' => $status]);
            return true;
        }
        return false;
    }
}
