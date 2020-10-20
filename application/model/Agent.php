<?php
/**
 * @since   2017-11-02
 * @author  zhaoxiang <zhaoxiang051405@gmail.com>
 */

namespace app\model;

use think\Request;

class Agent extends Base
{
    protected $name = 'agent';
    protected $pk = 'id';

    /**
     * 关联银行卡账户表 代理商type=2 默认卡status=1
     * @return \think\model\relation\HasOne
     */
    public function bankCard()
    {
        return $this->hasOne('BankCard', 'uid', 'id')->where(['type' => 2,'status'=>1]);
    }

    public function bucketInfo()
    {
        return $this->belongsTo('Bucket','bucket_id','id');
    }

    /**
     * 代理商详情
     * @param array $where
     * @param string $field
     * @return array|false|\PDOStatement|string|\think\Model
     */
    public static function detail($where = [], $field = '*')
    {
        $model = new static();
        return $model->with('bankCard')
            ->where($where)
            ->field($field)
            ->find();
    }

    /**
     * 列表
     * @param array $where
     * @param string $field
     * @param int $page
     * @param int $limit
     * @return \think\Paginator
     */
    public static function getLists($where = [],$field = '*',$page = 1,$limit = 15)
    {
        $model = new static();
        return $model->where($where)
                     ->field($field)
                     ->paginate($limit,false, [
                         'page' => $page,
                         'query' => Request::instance()->query()
                     ]);
    }


    /**
     * 设置错误信息
     * @param $error
     */
    private function setError($error)
    {
        empty($this->error) && $this->error = $error;
    }

    /**
     * 是否存在错误
     * @return bool
     */
    public function hasError()
    {
        return !empty($this->error);
    }
}
