<?php
if(is_url($_SESSION['jzdc']['icon']) && strlen($_SESSION['jzdc']['icon'])>100){
	echo '<img style="display:none;" src="./receive.php?target=index::visitor_position&act=load_wx_icon" />';	
}

if(@$_GET['act']=='update_page'){
	$sql="select * from ".$pdo->index_pre."page";
	$r=$pdo->query($sql,2);
	foreach($r as $v){
		if($v['url']=='index.index'){continue;}
		if($v['head']==''){continue;}
		if($v['bottom']==''){continue;}
		
		if($v['require_login']==1){
			$head='index.admin_nv,index.user_position,';
		}else{
			$head='index.top_bar,diymodule.show_121,mall.search,mall.cart,index.navigation,index.visitor_position';
		}
		if(strpos($v['url'],'ci.')!==false){
			if($v['require_login']==1){
				//$head='index.top_bar,diymodule.show_121,diymodule.show_123,ci.search,diymodule.show_119,index.user_position';
			}else{
				//$head='index.top_bar,diymodule.show_121,diymodule.show_123,ci.search,diymodule.show_119,index.navigation,index.visitor_position';
			}
		}
		if($v['require_login']==1){
				$bottom='index.foot,index.device';	
		}else{
			if($v['bottom']=='index.foot,index.device'){$bottom='diypage.foot_six_grid,index.device';	}else{$bottom=$v['bottom'];	}
			
		}
	
		$sql="update ".$pdo->index_pre."page set `head`='".$head."',`bottom`='".$bottom."' where `id`=".$v['id'];
		$sql="update ".$pdo->index_pre."page set `bottom`='".$bottom."' where `id`=".$v['id'];
		$pdo->exec($sql);
	}
}


$sql="select * from ".$pdo->index_pre."user where `id`=".$_SESSION['jzdc']['id'];
$r=$pdo->query($sql,2)->fetch(2);

if($r['money']<0){
	echo '<style>.page-header{display:none;}</style><div style=" line-height:10rem; text-align:center;">'.self::$language['liabilities'].' <a href="./index.php?jzdc=index.recharge"><b>'.self::$language['click'].self::$language['recharge'].'</b></a> <a href="./index.php?jzdc=index.money_log"><b>'.self::$language['view'].self::$language['pages']['index.money_log']['name'].'</b></a></div>';
	exit();
	return false;	
}
$module['user']=$r;
if($module['user']['banner']!=''){$module['banner_path']='./program/index/user_banner/'.$module['user']['banner'];}else{$module['banner_path']='./program/index/user_banner/default.png';}


$sql="select count(id) as c from ".$pdo->index_pre."site_msg where `addressee_state`=1 and `addressee`='".$_SESSION['jzdc']['username']."'";
$r=$pdo->query($sql,2)->fetch(2);
if($r['c']>0){
	$module['msg']=$r['c'];
}else{
	$module['msg']=0;
}


$sql="select `require_info` from ".$pdo->index_pre."group where `name`='".$_SESSION['jzdc']['group']."'";
//echo $sql;
$v=$pdo->query($sql,2)->fetch(2);
$v=explode(",",$v['require_info']);
$v=array_filter($v);
$fields='';
foreach($v as $t){
	$fields.="`$t`,";	
}
$fields=trim($fields,",");
if($fields!=''){
	$sql="select $fields from ".$pdo->index_pre."user where `id`='".$_SESSION['jzdc']['id']."'";
	//echo $sql;
	$v=$pdo->query($sql,2)->fetch(2);
	$field='';
	if(!$v){
		@session_destroy();
		setcookie(session_name(),'',time()-3600);
		$_SESSION = array();
		setcookie("jzdc_nickname",'',time()-3600);
		setcookie("jzdc_icon",'',time()-3600);
		setcookie("edit_page_layout",'',time()-3600);
		header('location:./index.php?jzdc=index.login');exit();
	}
	
	foreach($v as $key=>$value){
		//echo $key;
		if(!$v[$key]){
			
			if($key=='phone'){header('location:./index.php?jzdc=index.edit_phone');exit;}
			if($key=='email'){header('location:./index.php?jzdc=index.edit_email');exit;}
			if($key=='openid'){header('location:./index.php?jzdc=index.openid');exit;}
			$field.=$key."|";
		}	
	}
	$field=trim($field,"|");
	//echo $field;
	
	if($field!=''){header("location:./index.php?jzdc=index.edit_user&field=$field");}
}

$module['unlogin_url']="receive.php?target=".$method."&act=unlogin&callback=unlogin";
$module['jzdc_table_name']=self::$language['functions'][str_replace("::",".",$method)]['description'];
$module['module_name']=str_replace("::","_",$method);
if(self::$config['web']['weixin_auto_login']){$module['weixin_auto_login']=1;}else{$module['weixin_auto_login']=0;}

$module['user']['nickname']=$_SESSION['jzdc']['nickname'];
$module['user']['group']=$_SESSION['jzdc']['group'];
	$sql="select `item_value` from ".$pdo->index_pre."user_set where `user_id`='".$_SESSION['jzdc']['id']."' and `item_variable`='user_set_background_mode' limit 0,1";
	$user_set=$pdo->query($sql,2)->fetch(2);
	if($user_set['item_value']!=''){
		$user_set_background_mode=$user_set['item_value'];
	}else{
		$user_set_background_mode=@$_COOKIE['user_set_background_mode'];
	}
$user_set_background_mode=1;	
if($user_set_background_mode==1){
//========================================================================================================================================== icon start
	$sql="select `id` from ".$pdo->index_pre."group where `name`='".$_SESSION['jzdc']['group']."'";
	$v=$pdo->query($sql,2)->fetch(2);
	$sql="select * from ".$pdo->index_pre."group_menu where `group_id`='".$v['id']."' and `visible`=1 order by sequence desc";

    //echo $sql;
	$stmt=$pdo->query($sql,2);
	$module['list']='';
	$a=array();
	$index=0;

    //企业验证
    if ($_SESSION['jzdc']['group_id']==4 || $_SESSION['jzdc']['group_id']==5 || $_SESSION['jzdc']['group_id']==6) {
        $sql = "select `id`,`status` from jzdc_form_user_cert where `writer`='" . $_SESSION['jzdc']['id'] . "'";
        //echo $sql;
        $cert = $pdo->query($sql, 2)->fetch(2);
        if ($cert) {
            if ($cert['status'] == 2 || $cert['status'] == 3) { //审核通过和拒绝
                $module['edit_url'] = "index.php?jzdc=form.data_edit&table_id=37&id=" . $cert['id'];
            } else {
                $module['edit_url'] = "index.php?jzdc=form.data_show_detail&table_id=37&id=" . $cert['id'];
            }

        } else {
            $module['edit_url'] = "index.php?jzdc=form.data_add&table_id=37";
        }

        if ($_SESSION['jzdc']['group_id']==6){
            //企业验证, 注册会员才能看到
            $a[$index]['url'] = $module['edit_url'];
            $a[$index]['icon_path']='./templates/0/form/default/credit.index.png';
            $a[$index]['name']="企业认证";
            $a[$index]['open_target']='_self';
            $index++;
        }

    }else{
        $module['edit_url'] = "index.php?jzdc=index.edit_user";
    }


    foreach($stmt as $v){
		$t=explode(".",$v['url']);

		if(!(file_exists("program/".$t[0]."/config.php"))){continue;}
		$program_config=require("program/".$t[0]."/config.php");
		$language=require("program/".$t[0]."/language/".$program_config['program']['language'].".php");
		$v['icon_path']="./templates/1/".$t[0]."/".$program_config['program']['template_1']."/page_icon/".$v['url'].".png";
		
		$a[$index]['icon_path']=$v['icon_path'];
		if(!isset($language['pages'][$v['url']]['name'])){continue;}
		if(!in_array($v['url'],$_SESSION['jzdc']['page'])){continue;}
		$a[$index]['name']=$language['pages'][$v['url']]['name'];	
		$a[$index]['url']="index.php?jzdc={$v['url']}";	
		$a[$index]['open_target']='_self';
		$index++;	
	}

    //投诉于建议
    $a[$index]['url'] = 'index.php?jzdc=form.data_add&table_id=39';
    $a[$index]['icon_path']='./templates/1/index/default/page_icon/index.admin_msg.png';
    $a[$index]['name']="投诉建议";
    $a[$index]['open_target']='_self';
    $index++;

	if($_SESSION['jzdc']['group_id']==1){
		$a[$index]['icon_path']='./program/index/more.png';
		$a[$index]['name']=self::$language['add'].self::$language['more'].'..';	
		$a[$index]['url']="http://www.lansion.cn";	
		$a[$index]['open_target']='_blank';
	}


	
	$module['data']=json_encode($a);
	$module['template']='';
//========================================================================================================================================== icon end
}else{
	$module['data']='';
}









echo '<script src="./receive.php?target=mall::auto_receipt_expire_order"></script>';

$t_path='./templates/'.$m_require_login.'/'.$class.'/'.self::$config['program']['template_'.$m_require_login].'/'.$_COOKIE['jzdc_device'].'/'.str_replace($class."::","",$method).'.php';
if(!is_file($t_path)){$t_path='./templates/'.$m_require_login.'/'.$class.'/'.self::$config['program']['template_'.$m_require_login].'/pc/'.str_replace($class."::","",$method).'.php';}
require($t_path);