<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/22 0022
 * Time: 17:18
 */

namespace app\model;


use think\Request;

class MerchantMoneyLog extends Base
{
    protected $name = 'merchant_money_log';
    protected $pk = 'id';

    /**
     * @param array $where
     * @param int $page
     * @param int $limit
     * @param string $order
     * @param string $field
     * @return \think\Paginator
     */
    public static function getMoneyLogList($where = [], $page = 1, $limit = 15, $order = 'create_time desc', $field = '*')
    {
        $model = new  static();
        return $model->where($where)
            ->order($order)
            ->field($field)
            ->paginate($limit, false, [
                'page' => $page,
                'query' => Request::instance()->query()
            ]);
    }
}