<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/14 0014
 * Time: 10:22
 */

namespace app\member\service;


class LogService
{
    public static function getLogList( $list)
    {
        $listInfo = [];
        foreach ( $list as $val) {
            $data = [];
            $data['id'] = $val['id'];
            $data['money'] = $val['money'];
            $data['order_id'] = $val['order_id'];
            $data['current_money'] = $val['current_money'];

            $data['create_time'] = $val['create_time'];
            $data['remark'] = $val['remark'];
            $data['type_name'] = $val['type'] == 1 ? '支出' : '收入';
            $listInfo[] = $data;
        }
        return $listInfo;
    }
}