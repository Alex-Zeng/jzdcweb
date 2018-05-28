<?php
$module['module_name']=str_replace("::","_",$method);

$mall_cart=json_decode(@$_COOKIE['mall_cart'],true);
$module['cart']=count($mall_cart);


$sql="select count(`id`) as c from ".self::$table_pre."favorite where `username`='".$_SESSION['jzdc']['username']."'";
$r=$pdo->query($sql,2)->fetch(2);
if($r['c']==''){$r['c']=0;}
$module['favorite']=$r['c'];

$sql="select count(`id`) as c from ".self::$table_pre."visit where `username`='".$_SESSION['jzdc']['username']."'";
$r=$pdo->query($sql,2)->fetch(2);
if($r['c']==''){$r['c']=0;}
$module['visit']=$r['c'];

$sql="select count(`id`) as c from ".self::$table_pre."order where (`buyer`='".$_SESSION['jzdc']['username']."' or supplier = '".$_SESSION['jzdc']['id']."') and `state`=0";
$r=$pdo->query($sql,2)->fetch(2);
if($r['c']==''){$r['c']=0;}
$module['order_0']=$r['c'];

$sql="select count(`id`) as c from ".self::$table_pre."order where (`buyer`='".$_SESSION['jzdc']['username']."' or supplier = '".$_SESSION['jzdc']['id']."') and `state`=1";
$r=$pdo->query($sql,2)->fetch(2);
if($r['c']==''){$r['c']=0;}
$module['order_1']=$r['c'];

$sql="select count(`id`) as c from ".self::$table_pre."order where (`buyer`='".$_SESSION['jzdc']['username']."' or supplier = '".$_SESSION['jzdc']['id']."') and `state`=2";
$r=$pdo->query($sql,2)->fetch(2);
if($r['c']==''){$r['c']=0;}
$module['order_2']=$r['c'];

$sql="select count(`id`) as c from ".self::$table_pre."order where (`buyer`='".$_SESSION['jzdc']['username']."' or supplier = '".$_SESSION['jzdc']['id']."') and `state`=3";
$r=$pdo->query($sql,2)->fetch(2);
if($r['c']==''){$r['c']=0;}
$module['order_3']=$r['c'];

$sql="select count(`id`) as c from ".self::$table_pre."order where (`buyer`='".$_SESSION['jzdc']['username']."' or supplier = '".$_SESSION['jzdc']['id']."') and `state`=6";
$r=$pdo->query($sql,2)->fetch(2);
if($r['c']==''){$r['c']=0;}
$module['order_6']=$r['c'];

$sql="select count(`id`) as c from ".self::$table_pre."order where (`buyer`='".$_SESSION['jzdc']['username']."' or supplier = '".$_SESSION['jzdc']['id']."') and `state`=7";
$r=$pdo->query($sql,2)->fetch(2);
if($r['c']==''){$r['c']=0;}
$module['order_7']=$r['c'];

$sql="select count(`id`) as c from ".self::$table_pre."order where (`buyer`='".$_SESSION['jzdc']['username']."' or supplier = '".$_SESSION['jzdc']['id']."') and `state`=12";
$r=$pdo->query($sql,2)->fetch(2);
if($r['c']==''){$r['c']=0;}
$module['order_12']=$r['c'];

$sql="select count(`id`) as c from ".self::$table_pre."my_coupon where `username`='".$_SESSION['jzdc']['username']."' and `use_time`=0";
$r=$pdo->query($sql,2)->fetch(2);
if($r['c']==''){$r['c']=0;}
$module['coupon_usable']=$r['c'];

$sql="select `detail` from ".self::$table_pre."receiver where `username`='".$_SESSION['jzdc']['username']."' order by `sequence` desc limit 0,1";
$r=$pdo->query($sql,2)->fetch(2);
if($r['detail']!=''){
	$module['receiver']=de_safe_str($r['detail']);
}else{
	$module['receiver']='';
}

$module['s_top']='';
if ($_SESSION['jzdc']['group_id'] == 4){
    //采购商
    $module['s_top'] = "<div class=s_top>
        <a href=./index.php?jzdc=mall.my_collect& class=diy_favorites>
            <span class=name>".self::$language['favorites']."</span><span class=value>".$module['favorite']."</span>
        </a><a href=./index.php?jzdc=mall.my_order&state=1 class=order_state_1>
            <span class=name>".self::$language['order_state'][1]."</span><span class=value>".$module['order_1']."</span>
        </a><a href=./index.php?jzdc=mall.my_order&state=2 class=order_state_0>
            <span class=name>待打款</span><span class=value>".$module['order_2']."</span>
        </a><a href=./index.php?jzdc=mall.my_order&state=6 class=order_state_6>
            <span class=name>".self::$language['order_state'][6]."</span><span class=value>".$module['order_6']."</span>
        </a><a href=./index.php?jzdc=mall.my_order&state=7 class=order_state_7>
            <span class=name>".self::$language['order_state'][7]."</span><span class=value>".$module['order_7']."</span>
        </a>
        </div>";

}else if ($_SESSION['jzdc']['group_id'] == 5){
    //供货商
    $module['s_top'] = "<div class=s_top>
        <a href=./index.php?jzdc=mall.my_collect& class=diy_favorites>
            <span class=name>".self::$language['favorites']."</span><span class=value>".$module['favorite']."</span>
        </a><a href=./index.php?jzdc=mall.my_order&state=0 class=order_state_0>
            <span class=name>".self::$language['order_state'][0]."</span><span class=value>".$module['order_0']."</span>
        </a><a href=./index.php?jzdc=mall.my_order&state=1 class=order_state_1>
            <span class=name>".self::$language['order_state'][1]."</span><span class=value>".$module['order_1']."</span>
        </a><a href=./index.php?jzdc=mall.my_order&state=3 class=order_state_2>
            <span class=name>".self::$language['order_state'][3]."</span><span class=value>".$module['order_3']."</span>
        </a><a href=./index.php?jzdc=mall.my_order&state=12 class=order_state_4>
            <span class=name>待确认</span><span class=value>".$module['order_12']."</span>
        </a>
        </div>";
}


$t_path='./templates/'.$m_require_login.'/'.$class.'/'.self::$config['program']['template_'.$m_require_login].'/'.$_COOKIE['jzdc_device'].'/'.str_replace($class."::","",$method).'.php';
if(!is_file($t_path)){$t_path='./templates/'.$m_require_login.'/'.$class.'/'.self::$config['program']['template_'.$m_require_login].'/pc/'.str_replace($class."::","",$method).'.php';}
require($t_path);