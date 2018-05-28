<?php
$module['module_name']=str_replace("::","_",$method);

$sql="select `id`,`name` from ".$pdo->index_pre."circle where `parent_id`=0 and `visible`=1 order by `sequence` desc,`id` asc";
$r=$pdo->query($sql,2);
$module['list']='<div class=circle_div><a href="./index.php?circle=0"  circle=0><img src="./phone_menu_icon.png" /><span>'.self::$language['unlimited'].'</span></a></div>';
foreach($r as $v){
	$sub='';
	$sql="select `id`,`name` from ".$pdo->index_pre."circle where `parent_id`='".$v['id']."' and `visible`=1 order by `sequence` desc,`id` asc";
	$r2=$pdo->query($sql,2);
	foreach($r2 as $v2){
		$sub.='<a href="./index.php?circle='.$v2['id'].'" circle='.$v2['id'].'><img src="./program/index/circle_icon/'.$v2['id'].'.png" /><span>'.de_safe_str($v2['name']).'</span></a>';	
	}
	if($sub!=''){$sub='<div class=sub_div>'.$sub.'</div>';}
	$module['list'].='<div class=circle_div><a href="./index.php?circle='.$v['id'].'"  circle='.$v['id'].'><img src="./program/index/circle_icon/'.$v['id'].'.png" /><span>'.de_safe_str($v['name']).'</span></a>'.$sub.'</div>';
}
$module['list'].='<div class="circle_div more"><a href="./index.php?jzdc=index.circle" ><img src="./phone_menu_icon.png" /><span>'.self::$language['more'].'..</span></a></div>';


$t_path='./templates/'.$m_require_login.'/'.$class.'/'.self::$config['program']['template_'.$m_require_login].'/'.$_COOKIE['jzdc_device'].'/'.str_replace($class."::","",$method).'.php';
if(!is_file($t_path)){$t_path='./templates/'.$m_require_login.'/'.$class.'/'.self::$config['program']['template_'.$m_require_login].'/pc/'.str_replace($class."::","",$method).'.php';}
require($t_path);
