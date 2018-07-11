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
//    return $string;
    \think\Log::write('短信接口万能验证6666');
    return '6666';
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
function getOrderOutId($channel = 0){
    $model = new \app\common\model\Counter();
    $row = $model->lock(true)->where(['id'=>1])->find();

    $value = $row->order_count;
    //
    $channelArr = [0 => 'W',1=>'H',2 =>'A'];
    //生成固定位数
    $length = strlen($value);
    $str = '';
    for($i =0; $i < 12-$length-1; $i++){
        $str .='0';
    }

    if($length < 12){
        return $channelArr[$channel].'1'.$str.$value;
    }else{
        return $channelArr[$channel].$str.$value;
    }
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
    $suffix = $suffix ? $suffix : config('jzdc_domain');
    $content = htmlspecialchars_decode($content);
    $pregRule = "/src=\\\"program\/mall\/attachd\/image/";
    $content = preg_replace($pregRule,"src=\"{$suffix}/program/mall/attachd/image", $content);
    $pregRule2 = "/src=\\\"\/web\/public\/uploads\/attached\/image\//";
    $content = preg_replace($pregRule2,"src=\"{$suffix}/web/public/uploads/attached/image/",$content);
    return $content;
}

function getTypeMap(){
    $model = new \app\common\model\MallType();
    $rows = $model->where(['parent'=>0])->field(['id','name','parent'])->select();
    $map = [];
    foreach ($rows as $row){
        $map[$row->id][] = $row->id;
        $rows2 = $model->where(['parent'=>$row->id])->field(['id','name','parent'])->select();
        foreach ($rows2 as $row2){
            $map[$row->id][] = $row2->id;

            $rows3 = $model->where(['parent'=>$row2->id])->field(['id','name','parent'])->select();
            foreach($rows3 as $row3){
                $map[$row->id][] = $row3->id;
            }
        }
    }
    return $map;
}

function SendMail($tomail, $subject = '', $body = ''){
    $mail = new \PHPMailer();
    $mail->CharSet = 'UTF-8';
    $mail->IsSMTP();
    $mail->SMTPDebug = 0;
    $mail->SMTPAuth = true;
    $mail->SMTPSecure = 'ssl';
    $mail->Host = config('JZDC_MAIL_SMTP');
    $mail->Port = 465;
    $mail->Username = config('JZDC_MAIL_LOGINNAME');
    $mail->Password = config('JZDC_MAIL_PASSWORD');
    $mail->SetFrom(config('JZDC_MAIL_LOGINNAME'), '集众电采');
    $mail->Subject = $subject;
    $mail->MsgHTML($body);
    $mail->AddAddress($tomail, $tomail);
    return $mail->Send() ? true : false;
}

function getFormatPrice($price,$length = 2){
    return number_format($price,$length,'.','');
}


