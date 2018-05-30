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



}