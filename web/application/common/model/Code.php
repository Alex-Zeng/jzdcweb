<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/5/21
 * Time: 10:38
 */

namespace app\common\model;

use think\Model;

/**
 * @desc 验证码
 * Class Code
 * @package app\common\model
 */
class Code extends Model{

    const TYPE_PHONE_REGISTER = 1;  //手机号注册发送短信类型
    const TYPE_PHONE_LOGIN = 2; //手机号登录发送短信类型
    const TYPE_PHONE_FORGET_PASSWORD = 3; //忘记密码发送短信
    const TYPE_PHONE_BIND_OLD = 4; //原手机号发送短信
    const TYPE_PHONE_BIND_NEW = 5; //新手机号发送短信
    const TYPE_PHONE_ADMIN_OLD = 6; //后台原手机号发送短信
    const TYPE_PHONE_ADMIN_NEW = 7; //后台新手机号发送短信



}