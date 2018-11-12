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
 * [getInvitationCode 邀请码]
 * @param  integer $length [邀请码的长度]
 * @return [string]          
 */
function getInvitationCode($length = 6){
    $flag = true;
    $code = '';
    while($flag){
        $string = str_shuffle('0123456789ABCDEFGHIJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz');
        $code = substr($string, 0,6);
        $EntCode = new app\common\model\EntCode();
        if($EntCode->where(['code'=>$code])->count()==0){
            $flag = false;    
            break;
        }
    }
    return $code;
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
 * @desc 过滤日期
 * @param $value
 * @return string
 */
function filterDate($value){
    if(date('Y-m-d',strtotime($value)) == $value){
        return $value;
    }
    return '';
}

/**
 * @desc 验证密码
 * @param $password
 * @return false|int
 */
function checkPassword($password){
    return preg_match("/^(?![0-9]+$)(?![a-zA-Z]+$)(?!([^(0-9a-zA-Z)]|[\(\)])+$)([^(0-9a-zA-Z)]|[\(\)]|[a-zA-Z]|[0-9]){6,20}$/",$password);
}

/**
 * @desc 验证长度
 * @param $str
 * @param $length
 * @param string $encode
 * @return bool
 */
function checkStrLength($str, $length, $encode = 'UTF8'){
    $newLen = mb_strlen($str,$encode);
    return $newLen > $length ? false : true;
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

function getCompanyPropertyNew($property = -1){
    $list = [
        1 => '有限责任公司',
        2 => '中外合资企业',
        3 => '个体工商户',
        4 => '合伙企业'
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
    $pregRule3 = "/href=\\\"\/web\/public\/static\/css\//";
    $content = preg_replace($pregRule3,"href=\"{$suffix}/web/public/static/css/",$content);
    return $content;
}

function getTypeMap(){
    $model = new \app\common\model\SmProductCategory();
    $rows = $model->where(['parent_id'=>0,'is_display'=>1,'is_deleted'=>0])->field(['id','name','parent_id'])->select();
    $map = [];
    foreach ($rows as $row){
        $map[$row->id][] = $row->id;
        $rows2 = $model->where(['parent_id'=>$row->id,'is_display'=>1,'is_deleted'=>0])->field(['id','name','parent_id'])->select();
        foreach ($rows2 as $row2){
            $map[$row->id][] = $row2->id;
            $rows3 = $model->where(['parent_id'=>$row2->id,'is_display'=>1,'is_deleted'=>0])->field(['id','name','parent_id'])->select();
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

    if(is_array($tomail)){
       $emailArr = $tomail;
    }else{
       $emailArr = explode(',',$tomail);
    }
    for ($i =0; $i < count($emailArr); $i++){
        $mail->AddAddress($emailArr[$i], $emailArr[$i]);
    }
    return $mail->Send() ? true : false;
}

/**
 * @desc 格式化价格  不够两位小数补零，超过两位末尾为零不显示
 * @param $price
 * @param int $length
 * @return float|string
 */
function getFormatPrice($price,$length = 2){
    $floatT = floatval($price);
    //判断是否有小数
    $floatArr = explode ( '.', $floatT );
    if(count($floatArr) > 1){
        if(strlen($floatArr[1]) <= 2){
            return number_format($floatT,$length,'.','');
        }else{
            return (string)$floatT;
        }

    }else{
        return  number_format($floatT,$length,'.','');
    }
}

/**
 * @desc 返回状态列表
 * @return array
 */
function getOrderShowStatus(){
    $list = [
        -1 => '全部',
        1 => '待确认',
        2 => '待付款',
        3 => '待发货',
        4 => '待收货',
        5 => '订单关闭',
        6 => '售后处理',
    ];
    return $list;
}


function getOrderStatusInfo($status = 0, $serviceType = 0){
    if($status == 4){
        return '订单关闭';
    }
    if($status == 3){
        return '待发货';
    }
    if($status == 0 || $status == 1){
        return '待确认';
    }
    if(in_array($status,[2,9,10])){
        if(in_array($serviceType,[0,2])){
            return '待付款';
        }
    }
    if($status == 6 && in_array($serviceType,[0,2])){
        return '待收货';
    }
    if(in_array($status,[11,13]) || (in_array($status,[6,9,10]) && in_array($serviceType,[1,2]))){
        return '售后处理';
    }
    return '';
}

//
function getOrderMsg($groupId,$status,$serviceType,$show = false){
    if($show == true){
		if($serviceType == 1){
           return '售后处理中';
        }
       if($serviceType == 2){
           return '售后完成';
        }
	}
	
    switch ($status){
        case -1:
            return '全部';
            break;
        case 0:
            return '待核价';
            break;
        case 1:
            return '待签约';
            break;
        case 2:
            return '待采购商打款';
            break;
        case 3:
            return'待发货';
            break;
        case 4:
            return '订单关闭';
            break;
        case 6:
            return '待收货';
            break;
        case 7:
            return '待质检';
            break;
        case 8:
            return '售后处理';
            break;
        case 9:
            return '账期中';
            break;
        case 10:
            return '逾期中';
            break;
        case 11:
            if($groupId == 4){
                return '交易完成';
            }else{
                return '待结算';
            }
            break;
        case 13:
            return'交易完成';
            break;
    }
}



/**
 * @desc 递归获取子类Id
 * param $array 包含子类的搜索的数组
 * param $id 父类ID用于查询其子类
 * @return array
 */
function getRecursionType($array,$id){
    $arr = [];
    foreach($array as $value){
        if($value['parent']==$id){
            $arr[] = $value['id'];
            $arr = array_merge($arr,getRecursionType($array,$value['id']));
        }
    }
    return $arr;
}


/**
 * [getDevice 获取扫码源]
 * @return [string] [iphoneWechat苹果微信iphoneNomal苹果普通androidNomal安卓普通androidWechat安卓微信]
 */
function getDevice(){
    //检查是来源客户端
    $userAgent = $_SERVER['HTTP_USER_AGENT'];
    if(stripos($userAgent, 'iPhone') && stripos($userAgent, 'MicroMessenger')){
        return 'iphoneWechat';
    }elseif(stripos($userAgent, 'iPhone')) {
        return 'iphoneNormal';
    }elseif((stripos($userAgent, 'MicroMessenger') === false)&&strpos($userAgent, 'QQ') === false) { //浏览器下载或版本更新
        return 'androidNormal';
    }else{ //微信下载
        return 'androidWechat';
    }
}

/**
 * @desc 前端显示价格
 * @param int $isDiscuss
 * @param $minPrice
 * @param $maxPrice
 * @return float|string
 */
function getShowPrice($isDiscuss = 0, $minPrice,$maxPrice){
    if($isDiscuss == 1){
        return '电议';
    }
    $minPrice = getFormatPrice($minPrice);
    $maxPrice = getFormatPrice($maxPrice);
    if($minPrice == $maxPrice){
        return '¥'.$maxPrice;
    }
    return '¥'.$minPrice.'-'.$maxPrice;
}

/**
 * @desc 显示价格
 * @param int $isDiscuss
 * @param $price
 * @return string
 */
function getSimplePrice($isDiscuss = 0, $price){
    if($isDiscuss == 1){
        return '电议';
    }
    return getFormatPrice($price);
}

/**
 * @desc 二进制转十进制
 * @param $binT
 * @return float|int
 */
function getBinDecimal($binT){
    return bindec($binT);
}

/**
 * @desc php不支持方法则自定义
 */
if (!function_exists('getallheaders'))
{
    function getallheaders(){
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}

/**
 * @desc 验证当前版本审核机制
 * @return bool
 */
function checkCurrentVersion(){
    //接收IOS参数
    $headers = getallheaders();
    $appVersion = '';
    $appType = '';
    foreach ($headers as $key => $header){
       if(strtolower($key) == 'app-version'){
           $appVersion = $header;
       }
       if(strtolower($key) == 'app-type'){
           $appType = $header;
       }
    }
    $audit = ($appVersion == config('JZDC_APP_VERSION')) && (strtolower($appType) == 'ios') &&  config('JZDC_APP_AUDIT');
    return $audit;
}