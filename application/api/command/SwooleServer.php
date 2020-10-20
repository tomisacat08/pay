<?php

namespace app\api\command;

use app\api\behavior\Init;
use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use app\api\handle\Swoole;

class SwooleServer extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'swoole后台服务端';
    protected $commandName = 'swooleServer';
    protected $option_name = 'opt';

    protected function configure()
    {
        $this->addOption($this->option_name, 'm', Option::VALUE_OPTIONAL, 'start'); //选项值必填
        $this->setName($this->commandName)->setDescription($this->description);
    }

    protected function execute(Input $input, Output $output)
    {
        $handle = new Swoole();

        $options = $input->getOptions();
        if(isset($options[$this->option_name])) {
            switch ($options[$this->option_name]) {
                case "start"    : $handle->start();break;
                case "stop"     : $handle->stop();break;
                default : die("Usage:{start|stop}");
            }
        } else {
            die("缺少必要参数");
        }
        $output->writeln("swoole_server: start");
    }


    /**
     * 初始化
     * @param Input  $input  An InputInterface instance
     * @param Output $output An OutputInterface instance
     */
    protected function initialize(Input $input, Output $output)
    {

    }

}
