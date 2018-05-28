<?php
clear_temp_file();

$script='';	
//$password=md5(@$_POST['password']);
$password=base64_decode(@$_POST['password']);
$password=md5($password);
$username=safe_str(@$_POST['username']);
$authcode=@$_POST['authcode'];

if(intval(@$_SESSION['jzdc']['login_count'])>3){
	if(strtolower($authcode)!=strtolower($_SESSION["authCode"])){
		$errType='authcode';
		$errInfo=self::$language['authcode_err'];
		exit ("{'errType':'$errType','errInfo':'$errInfo'}|".$script);			
	}
}
	$sql="select {$pdo->index_pre}user.id as userid,{$pdo->index_pre}group.id as group_id,`nickname`,`username`,`name`,`page_power`,`function_power`,`state`,`icon`,`recommendation`,`introducer` from ".$pdo->index_pre."user,".$pdo->index_pre."group where (`username`='$username' or `phone`='$username' or `email`='$username') and `password`='$password' and `group`=".$pdo->index_pre."group.id";
	//exit($sql);
	$stmt=$pdo->query($sql,2);
	$v=$stmt->fetch(2);	
	//var_dump($v);
	
	if($v){
		if(intval(@$_GET['oauth'])==1 && @$_SESSION['oauth']['open_id']!=''){
			$_GET['backurl']=$_SESSION['oauth']['backurl'];
			oauth_bind($pdo,$v['userid']);			
		}
		
		if($v['state']!=1){
			$errType='submit';
			$errInfo=self::$language['user_state'][$v['state']];
		}else{
			login_credits($pdo,self::$config,self::$language,$v['userid'],$v['username'],self::$config['credits_set']['login'],self::$language['login_credits'],self::$config['other']['timeoffset']);
			

			if($v['recommendation']==''){
				$recommendation=$v['userid'].get_random_str(8-strlen($v['userid']));
				$sql="update ".$pdo->index_pre."user set `recommendation`='".$recommendation."' where `id`=".$v['userid'];
				$pdo->exec($sql);	
			}
			if(!is_url($v['icon'])){
				if($v['icon']==''){$v['icon']='default.png';}
				$v['icon']="./program/index/user_icon/".$v['icon'];
			}
			$_SESSION['jzdc']['id']=$v['userid'];
			$_SESSION['jzdc']['introducer']=$v['introducer'];
			$_SESSION['jzdc']['username']=$v['username'];
			$_SESSION['jzdc']['nickname']=$v['nickname'];
			$_SESSION['jzdc']['icon']=$v['icon'];
			//if($v['icon']==''){$_SESSION['jzdc']['icon']='default.png';}
			$_SESSION['jzdc']['group']=$v['name'];
			$_SESSION['jzdc']['group_id']=$v['group_id'];
			$_SESSION['jzdc']['page']=explode(",",$v['page_power']);
			$_SESSION['jzdc']['function']=explode(",",$v['function_power']);
            @setcookie("jzdc_group_id",$v['group_id']);
			@setcookie("jzdc_id",$v['userid']);
			@setcookie("jzdc_nickname",$v['nickname']);
			@setcookie("jzdc_icon",$_SESSION['jzdc']['icon']);
			if(in_array('index.edit_page_layout',$_SESSION['jzdc']['function'])){
				@setcookie("edit_page_layout",'true');	
			}
			//user_set cookie					
			send_user_set_cookie($pdo);
			$backurl=@$_GET['backurl'];
			
			$backurl=str_replace('|||','&',$backurl);
			if(!strpos($backurl,'?')){$backurl.='?refresh='.time();}else{$backurl.='&refresh='.time();}
			$errType='none';
			$errInfo='none';
			$backurl=explode('index.php',$backurl);
			$backurl=(isset($backurl[1]))?$backurl[1]:'./index.php?jzdc=index.user';
			$script= "<script>window.location.href='$backurl';</script>";
			$time=time();
			$ip=get_ip();
			$sql="update ".$pdo->index_pre."user set `last_time`='$time',`last_ip`='$ip' where `id`='".$_SESSION['jzdc']['id']."'";
			$pdo->exec($sql);
			$sql="select count(id) as c from ".$pdo->index_pre."user_login where `userid`='".$_SESSION['jzdc']['id']."'";
			$stmt=$pdo->query($sql,2);
			$v=$stmt->fetch(2);
			if(self::$config['web']['login_position']){
				$login_position=get_ip_position($ip,self::$config['web']['map_secret']);	
			}else{
				$login_position='';
			}
			
			if($v['c']<self::$config['other']['user_login_log']){
				
				$sql="insert into ".$pdo->index_pre."user_login (`userid`,`ip`,`time`,`position`) values ('".$_SESSION['jzdc']['id']."','$ip','$time','".$login_position."')";
				
			}else{
				$sql="select `id` from ".$pdo->index_pre."user_login where `userid`='".$_SESSION['jzdc']['id']."' order by time asc limit 0,1";
				$stmt=$pdo->query($sql,2);
				$v=$stmt->fetch(2);
				$sql="update ".$pdo->index_pre."user_login set `ip`='$ip',`time`='$time',`position`='".$login_position."' where `id`='".$v['id']."'";
			}
			$pdo->exec($sql);
			$sql="update ".$pdo->index_pre."user set `login_num`=login_num+1 where `id`='".$_SESSION['jzdc']['id']."'";
			$pdo->exec($sql);
			
			$_SESSION["authCode"]=rand(-9999999999,9999999999999999);
			if(intval(@$_GET['oauth'])==1 && @$_SESSION['oauth']['open_id']!=''){
				if($_COOKIE['jzdc_device']=='phone'){
					exit('<script>window.location.href="'.str_replace('|||','&',$_SESSION['oauth']['backurl']).'";</script>');
				}						
				exit("<script>window.close();</script>");
			}

		}

		
		
	}else{
		@$_SESSION['jzdc']['login_count']++;
		$sql="select count(id) as c from ".$pdo->index_pre."user where `username`='$username' or `phone`='$username' or `email`='$username'";
		$stmt=$pdo->query($sql,2);
		$v=$stmt->fetch(2);
		if($v['c']==0){
			$errType='username';
			$errInfo=self::$language['username_err'];	
		}else{
			$errType='password';
			$errInfo=self::$language['password_err'];	
		}	
        $errInfo = "用户名或密码错误";
		
	}
echo "{'errType':'$errType','errInfo':'$errInfo'}|".$script;			
