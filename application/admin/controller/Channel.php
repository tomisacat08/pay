<?php
namespace app\admin\controller;

use app\api\service\ChannelService;
use app\api\swoole\PayService;
use app\model\Merchant;
use app\model\Agent;
use app\util\ReturnCode;


class Channel extends Base {

    public function getChannelList()
    {
        $channelConfig = ChannelService::$channel_config;
        return $this->buildSuccess($channelConfig);
    }

}