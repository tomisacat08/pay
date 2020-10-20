<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/12 0012
 * Time: 10:47
 */

namespace app\payapi\controller;

use app\model\AdminApp;

class AppDownload extends Base
{
    public function index()
    {
        $id = $this->request->get('id',1);
        $appInfo  = AdminApp::find($id);
        $downUrl = $this->request->domain() . $appInfo->app_update_url;
        return view('index')->assign(['app_update_url'=>$downUrl,'app_version'=>$appInfo->app_version]);
    }
}
