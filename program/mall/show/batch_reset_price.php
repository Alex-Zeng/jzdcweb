<?php
$module['jzdc_table_name']=self::$language['functions'][str_replace("::",".",$method)]['description'];
$module['module_name']=str_replace("::","_",$method);
$_SESSION['token'][$method]=get_random(8);$module['action_url']="receive.php?token=".$_SESSION['token'][$method]."&target=".$method;

$sql="select `name`,`id`,`discount` from ".self::$table_pre."shop_buyer_group where `shop_id`='".SHOP_ID."' order by `discount` desc";
$r=$pdo->query($sql,2);

$group_field_a='';
$group_field_b='';
foreach($r as $v){
	$v['name']=de_safe_str($v['name']);
	$group_field_a.=','.$v['name'].self::$language['price2'];
	$group_field_b.=','.$v['name'].self::$language['discount_rate'];
}

self::$language['batch_reset_price_field'].=$group_field_a.$group_field_b.','.self::$language['introducer'].self::$language['introducer_rate']."(%)";

require "./plugin/html5Upfile/createHtml5.class.php";
$html5Upfile=new createHtml5();
$html5Upfile->echo_input(self::$language,"import_file",'100%','','./temp/','true','false','csv|txt',1024*10,'0');
//echo_input(语言数组,"house_model",'控件宽度(百分比或像素)','multiple','保存到文件夹','文件夹是否附加日期','是否原名保存','允许文件类型','文件最大值','文件最小值');

$t_path='./templates/'.$m_require_login.'/'.$class.'/'.self::$config['program']['template_'.$m_require_login].'/'.$_COOKIE['jzdc_device'].'/'.str_replace($class."::","",$method).'.php';
if(!is_file($t_path)){$t_path='./templates/'.$m_require_login.'/'.$class.'/'.self::$config['program']['template_'.$m_require_login].'/pc/'.str_replace($class."::","",$method).'.php';}
require($t_path);

