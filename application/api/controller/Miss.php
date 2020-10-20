<?php

namespace app\api\controller;


use app\util\ReturnCode;
use app\util\StrRandom;

class Miss extends Base {
    public function index() {
        $this->debug([
            'TpVersion' => THINK_VERSION,
            'Float' => StrRandom::randomPhone()
        ]);
        return $this->buildFailed(ReturnCode::INVALID,'路由错误');
    }
}
