<?php
/**
 * Created by PhpStorm.
 * User: 28127
 * Date: 2017/11/30 0030
 * Time: 10:07
 */

namespace app\util\lock;


interface ILock
{
    const EXPIRE = 5;
    public function getLock($key, $timeout=self::EXPIRE);
    public function releaseLock($key);
}