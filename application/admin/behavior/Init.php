<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/16 0016
 * Time: 下午 10:43
 */
namespace app\admin\behavior;
class Init {
	public function run(&$params) {
		//加载配置

		$config_list = db('config')->select();

		$config_list = html_decode($config_list);

		foreach ($config_list as $k => $v) {

            config($v['varname'], $v['value']);

		}
	}
    public function appEnd(&$params) {
    }
}