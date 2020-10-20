<?php
namespace app\merchant\controller;

use app\api\service\ChannelService;


class Channel extends Base {

    public function getChannelList()
    {
        $channelConfig = ChannelService::$channel_config;
        return $this->buildSuccess($channelConfig);
    }

}