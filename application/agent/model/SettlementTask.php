<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/21 0021
 * Time: 15:19
 */

namespace app\agent\model;

use app\model\SettlementTask as SettlementTaskModel;
use think\Request;

class SettlementTask extends SettlementTaskModel
{

    /**
     * 关联商户提现申请
     * @return \think\model\relation\BelongsTo
     */
    public function merchantWithdraw()
    {
        return $this->belongsTo("MerchantWithdrawAudit", 'withdraw_id', 'id');
    }

    /**
     * 关联平台提现申请
     * @return \think\model\relation\BelongsTo
     */
    public function platformWithdraw()
    {
        return $this->belongsTo('PlatformWithdraw', 'withdraw_id', 'id');
    }

    /**
     * 列表
     * @param array $where
     * @param string $order
     * @param string $field
     * @param int $page
     * @param int $limitRows
     * @return \think\Paginator
     */
    public static function getTaskList($where = [], $order = 'id desc', $field = '*', $page = 1, $limitRows = 15)
    {
        $model = new static();
        return $model->with(['platformWithdraw', 'merchantWithdraw'])
            ->where($where)
            ->field($field)
            ->order($order)
            ->paginate($limitRows, false, [
                'page' => $page,
                'query' => Request::instance()->query()
            ]);
    }
}