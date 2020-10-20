<?php
/**
 * Created by PhpStorm.
 * User: 28127
 * Date: 2017/11/30 0030
 * Time: 10:09
 */

namespace app\util\lock;


use think\Db;

class SQLLock implements ILock
{

    public function getLock($key, $timeout=self::EXPIRE)
    {
        $sql = "SELECT GET_LOCK(':key', ':timeout')";
        $res = Db::query($sql,['key'=>$key,'timeout'=>$timeout]);
        return $res;
    }

    public function releaseLock($key)
    {
        $sql = "SELECT RELEASE_LOCK(':key')";
        return Db::query($sql,['key'=>$key]);
    }
}