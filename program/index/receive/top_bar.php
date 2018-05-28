<?php
$module['module_name']="index_top_bar";
$module['data']='';
$module['nickname']='';
$json=array();
$json['msg']=0;


if(!isset($_SESSION['jzdc']['id'])){ 
	$module['data'].="<a id=login href='index.php?jzdc=index.login'>".self::$language['login']."</a><a href='index.php?jzdc=index.reg_user&group_id=".self::$config['reg_set']['default_group_id']."' id=reg_user>".self::$language['reg_user']."</a>"; 
}else{
	$module['data'].='<span id=hello>'.self::$language['user_welcome'].'</span>';
	$module['data'].="<a href='index.php?jzdc=index.user' id=icon_a><img id=icon_img src='".$_SESSION['jzdc']['icon']."' border=0></a>";
	$module['data'].="<a id=nickname href='index.php?jzdc=index.user'>".$_SESSION['jzdc']['nickname']."<span>/".$_SESSION['jzdc']['group'].'</span></a>';
	$module['icon']="<a href='index.php?jzdc=index.user' id=iocn_a><img id=icon_img src='".$_SESSION['jzdc']['icon']."' border=0></a>";
	$module['nickname']="<a id=nickname href='index.php?jzdc=index.user'>".$_SESSION['jzdc']['nickname'].'</a>';
	$module['group']=$_SESSION['jzdc']['group'];
	
	$module['msg']='';
	$sql="select count(id) as c from ".$pdo->index_pre."site_msg where `addressee_state`=1 and `addressee`='".$_SESSION['jzdc']['username']."'";
	$r=$pdo->query($sql,2)->fetch(2);
	if($r['c']>0){
		$module['msg']="<a id=msg_show href='index.php?jzdc=index.site_msg_addressee' class='fadeIn animated infinite'>".$r['c']."</a>";
		$json['msg']=$r['c'];
	}
	$module['data'].=$module['msg'];
	
	$module['data'].=" <a id=unlogin href='receive.php?target=index::user&act=unlogin&callback=unlogin&backurl=index.php?jzdc=index.login'  class='ajax'>".self::$language['unlogin']."</a>"; 
	$module['unlogin']=" <a id=unlogin href='receive.php?target=index::user&act=unlogin&callback=unlogin&backurl=index.php?jzdc=index.login'  class='ajax'>".self::$language['unlogin']."</a>"; 
	
	$json['nickname']=$_SESSION['jzdc']['nickname'];
	$json['group']=$_SESSION['jzdc']['group'];
	$json['icon']="".$_SESSION['jzdc']['icon'];

	
}
$module['top_welcome_info']=@self::$config['web']['top_welcome_info'];
//$module['data'].="<a href=# id='print_a' target='_blank'>".self::$language['print']."</a>"; 
//$module['print_a']="<a href=# id='print_a' target='_blank'>".self::$language['print']."</a>"; 
$$module['data']=str_replace("\r\n","",$module['data']);
$module['data']='"'.$module['data'].'"';	

$module['json']=json_encode($json);

$m_require_login=0;	
$t_path='./templates/'.$m_require_login.'/index/'.self::$config['program']['template_'.$m_require_login].'/'.$_COOKIE['jzdc_device'].'/'.str_replace($class."::","",$method).'.php';
if(!is_file($t_path)){$t_path='./templates/'.$m_require_login.'/index/'.self::$config['program']['template_'.$m_require_login].'/pc/'.str_replace($class."::","",$method).'.php';}
require($t_path);