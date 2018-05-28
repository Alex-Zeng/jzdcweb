<?php
$module['jzdc_table_name']=self::$language['functions'][str_replace("::",".",$method)]['description'];
$module['module_name']=str_replace("::","_",$method);
$sql="select `wx_qr` from ".$pdo->index_pre."user where `id`=".$_SESSION['jzdc']['id'];
$r=$pdo->query($sql,2)->fetch(2);
if(($r['wx_qr']==''  && self::$config['web']['wid']!='') || self::$config['web']['wid']=='gh_fd92a75504d8' ){
	get_weixin_info(self::$config['web']['wid'],$pdo); 
	$data='{
			"action_name": "QR_LIMIT_STR_SCENE", 
			"action_info": {
				"scene": {
					"scene_str": "new_user__'.$_SESSION['jzdc']['id'].'"
				}
			}
		}';	
	$r= https_post('https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token='.$_POST['jzdc_weixin'][self::$config['web']['wid']]['token'],$data);
	$r=json_decode($r,1);
	if(isset($r['url'])){
		$sql="update ".$pdo->index_pre."user set `wx_qr`='".safe_str($r['url'])."' where `id`='".$_SESSION['jzdc']['id']."'";
		$pdo->exec($sql);
		$r['wx_qr']=safe_str($r['url']);
	}	
}
$r['wx_qr']=de_safe_str(@$r['wx_qr']);


$module['icon']=$_SESSION['jzdc']['icon'];
$module['share']=$_SESSION['jzdc']['id'];
$module['reg_url']='http://'.self::$config['web']['domain'].'/index.php?jzdc=index.reg_user&group_id='.self::$config['reg_set']['default_group_id'].'&share='.$module['share'];
if($r['wx_qr']==''){
	$module['qr_text']='http://'.self::$config['web']['domain'].'/index.php?jzdc=index.reg_user|||group_id='.self::$config['reg_set']['default_group_id'].'|||share='.$module['share'].'|||jzdc_device=phone';
}else{
	$module['qr_text']=str_replace('&','|||',$r['wx_qr']);
}


$t_path='./templates/'.$m_require_login.'/'.$class.'/'.self::$config['program']['template_'.$m_require_login].'/'.$_COOKIE['jzdc_device'].'/'.str_replace($class."::","",$method).'.php';
if(!is_file($t_path)){$t_path='./templates/'.$m_require_login.'/'.$class.'/'.self::$config['program']['template_'.$m_require_login].'/pc/'.str_replace($class."::","",$method).'.php';}
require($t_path);
