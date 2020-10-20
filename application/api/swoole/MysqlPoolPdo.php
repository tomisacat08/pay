<?php

namespace app\api\swoole;

/**
 * 数据库连接池PDO方式
 */
class MysqlPoolPdo extends AbstractPool
{
    protected $dbConfig = array(
        'host' => 'mysql:host=10.0.2.2:3306;dbname=test',
        'port' => 3306,
        'user' => 'root',
        'password' => 'root',
        'database' => 'test',
        'charset' => 'utf8',
        'timeout' => 2,
    );
    public static $instance;

    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new MysqlPoolPdo();
        }
        return self::$instance;
    }

    protected function createDb()
    {
        return new PDO($this->dbConfig['host'], $this->dbConfig['user'], $this->dbConfig['password']);
    }
}