<?php
/**
 * 错误码统一维护
 * @since   2017/02/28 创建
 * @author  zhaoxiang <zhaoxiang051405@gmail.com>
 */

namespace app\util;

class ReturnCode {

    const SUCCESS = 1;
    const INVALID = -1;
    const DB_SAVE_ERROR = -2;//数据库写入错误
    const DB_READ_ERROR = -3;//数据库读取错误
    const CACHE_SAVE_ERROR = -4;//缓存写入
    const CACHE_READ_ERROR = -5;//缓存读取
    const FILE_SAVE_ERROR = -6;//文件写入
    const LOGIN_ERROR = -7;//登录错误
    const NOT_EXISTS = -8;//
    const JSON_PARSE_FAIL = -9;
    const TYPE_ERROR = -10;//类型错误
    const NUMBER_MATCH_ERROR = -11;
    const EMPTY_PARAMS = -12;//参数为空
    const DATA_EXISTS = -13;
    const AUTH_ERROR = -14;//TOKEN 认证错误

    const OTHER_LOGIN = -16;
    const VERSION_INVALID = -17;//版本不存在

    const CURL_ERROR = -18;

    const RECORD_NOT_FOUND = -19; // 记录未找到
    const DELETE_FAILED = -20; // 删除失败
    const ADD_FAILED = -21; // 添加记录失败
    const UPDATE_FAILED = -22; // 更新失败

    const PARAM_INVALID = -995; // 参数无效
    const ACCESS_TOKEN_TIMEOUT = -996;//TOKEN超时
    const SESSION_TIMEOUT = -997;
    const UNKNOWN = -998;
    const EXCEPTION = -999;

    static public function getConstants() {
        $oClass = new \ReflectionClass(__CLASS__);
        return $oClass->getConstants();
    }

}