<?php
$module['jzdc_table_name']=self::$language['functions'][str_replace("::",".",$method)]['description'];
$module['module_name']=str_replace("::","_",$method);
$sql="select `phone`,`chip`,`id` from ".$pdo->index_pre."user where `id`=".$_SESSION['jzdc']['id'];
$r=$pdo->query($sql,2)->fetch(2);
if($r['chip']=='' && $r['phone']=''){
	$sql="update ".$pdo->index_pre."user set `chip`='".$r['id']."' where `id`=".$r['id'];
	$pdo->exec($sql);
	$r['chip']=$r['id'];
}
if($r['phone']!=''){$module['qr_text']=$r['phone'];}else{$module['qr_text']=$r['chip'];}


$t_path='./templates/'.$m_require_login.'/'.$class.'/'.self::$config['program']['template_'.$m_require_login].'/'.$_COOKIE['jzdc_device'].'/'.str_replace($class."::","",$method).'.php';
if(!is_file($t_path)){$t_path='./templates/'.$m_require_login.'/'.$class.'/'.self::$config['program']['template_'.$m_require_login].'/pc/'.str_replace($class."::","",$method).'.php';}
require($t_path);
