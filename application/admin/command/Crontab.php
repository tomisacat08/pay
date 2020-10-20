<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/29 0029
 * Time: 14:24
 */

namespace app\admin\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Log;

class Crontab extends Command
{
    protected function configure()
    {
        $this->setName('Crontab')->setDescription("计划任务 Test");
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('Date Crontab job start...');
        /*** 这里写计划任务列表集 START ***/

        $this->merchant_count();

        /*** 这里写计划任务列表集 END ***/
        $output->writeln('Date Crontab job end...');
    }

    /**
     * 商户每日对账 12点过后统计
     * @return array
     * @author
     */
    private function merchant_count()
    {
        set_time_limit(3600);
        $ids = db('merchant')->field('id')->select();
        if(strtotime(date('Y-m-d 00:00:00', time() - 86400)) == db('merchant_order_log')->max('create_time')){
            echo '已执行过';
            $logs = '已执行过';
        }else{
            $counts = count($ids);
            $log = [];
            foreach ($ids as $key => $val) {
                $order = db('merchant_order')
                    ->field('start_money,status')
                    ->where(['merchant_id' => $val['id']])
                    ->whereTime('create_time', 'yesterday')
                    ->select();
                $log[$key]['time'] = date('m月d日', time() - 86400);
                $log[$key]['order_num'] = count($order);
                $over_order_num = 0;
                $over_order_money = 0.00;
                $order_money = 0.00;
                foreach ($order as $k => $v) {
                    $order_money = $order_money + $v['start_money'];
                    if ($v['status'] > 2) {
                        $over_order_num = $over_order_num + 1;
                        $over_order_money = $over_order_money + $v['start_money'];
                    }
                }
                $log[$key]['over_order_num'] = $over_order_num;
                $log[$key]['order_money'] = $order_money;
                $log[$key]['over_order_money'] = $over_order_money;
                $log[$key]['create_time'] = strtotime(date('Y-m-d 00:00:00', time() - 86400));
                $log[$key]['merchant_id'] = $val['id'];
            }

            $count = db('merchant_order_log')->insertAll($log);
            $logs = '总共'.$counts.'个商户，每日对账执行了' . $count . '条';
        }
        //记录日志
        $config = [
            // 日志记录方式，内置 file socket 支持扩展
            'type'  => 'File',
            // 日志保存目录
            'path'  => LOG_PATH.'/crontab/',
            //单个日志文件的大小限制，超过后会自动记录到第二个文件
            'file_size'     =>2097152,
            // 日志记录级别
            'level' => ['info'],
        ];
        Log::init($config);
        Log::write($logs);
        echo $logs;
    }
}
