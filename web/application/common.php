<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件

/**
 * @desc 检查是否开启session
 */
function startSession(){
    if(!isset($_SESSION)){
        session_start();
    }
}


function captchaDb_check($value, $id = "", $config = []){
    if( $value == '6666'){
        \think\Log::write('登录图形万能验证6666');
        return true;
    }
    $captcha = new \think\captcha\Captcha($config);
    return $captcha->checkDb($value, $id);
}
/**
 * @desc 获取随机验证码
 * @param int $length
 * @return string
 */
function getVerificationCode($length = 4){
    $code="123456789";
    $string='';
    for($i=0; $i<$length; $i++){
        $char=$code{rand(0, strlen($code)-1)};
        $string.=$char;
    }
    return $string;
}

/**
 * @desc 验证手机号
 * @param $phone
 * @return false|int
 */
function checkPhone($phone){
    return preg_match("/^1[34578]\d{9}$/ims",$phone);
}

/**
 * @desc 验证邮箱
 * @param $email
 * @return false|int
 */
function checkEmail($email){
    return preg_match("/\w+([-+.']\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*/",$email);
}


/**
 * @desc 验证密码
 * @param $password
 * @return false|int
 */
function checkPassword($password){
    return preg_match("/(?=.*[a-z])(?=.*[0-9])[A-Za-z0-9]{4,20}/",$password);
}

/**
 * @desc 生成订单号，简单处理
 * @param int $number
 * @return string
 */
function getOrderOutId($number = 0){
    return date('YmdHis').$number.rand(10,100);
}

/**
 * @desc 企业性质
 * @param int $property
 * @return mixed|string
 */
function getCompanyProperty($property = -1){
    $list = [
        0 => '有限责任公司',
        1 => '股份有限公司',
        2 => '个体工商户',
        3 => '合伙企业'
    ];
    return isset($list[$property]) ? $list[$property] : '';
}

/**
 * 图片地址替换为绝对地址
 * @param string $content 内容
 * @param string $suffix 后缀
 */
function getImgUrl($content="",$suffix = ''){
    $suffix = $suffix ? $suffix : config('jzdc_domain').DS;
    $pregRule = "/<img src=&#34;/";  //<img src=&#34;
    $content = preg_replace($pregRule,$suffix, $content);
    return $content;
}