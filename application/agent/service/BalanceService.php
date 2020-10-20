<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/7/1 0001
 * Time: 11:16
 */

namespace app\agent\service;

use app\agent\model\Config;

/**
 * 结算service层
 * Class BalanceService
 * @package app\agent\service
 */
class BalanceService
{
    /**
     * 错误信息
     * @var
     */
    protected $error;

    /**
     * 配置模型
     * @var
     */
    protected $configModel;

    /**
     * 结算任务实例对象模型
     * @var
     */
    protected $SettlementTask;

    /**
     * 结算分配最低金额
     * @var
     */
    protected $minMoney;

    /**
     * 结算分配最高金额
     * @var
     */
    protected $maxMoney;

    /**
     * 构造方法
     * BalanceService constructor.
     * @param $SettlementTask
     */
    public function __construct($SettlementTask)
    {
        $this->SettlementTask = $SettlementTask;
        $this->configModel = new Config();
        $this->setConfig();
    }


    /**
     * 设置配置信息
     */
    public function setConfig()
    {
        $this->minMoney = $this->configModel->where('varname', '=', 'allot_min_money')->value('value');
        $this->maxMoney = $this->configModel->where('varname', '=', 'allot_max_money')->value('value');
    }
}