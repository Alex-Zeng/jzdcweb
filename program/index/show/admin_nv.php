<?php
$module['url']='';
$sql="select `id`,`page_power`,`map`,`map_update_token` from ".$pdo->index_pre."group where `id`='".$_SESSION['jzdc']['group_id']."'";
//echo $sql;
$v=$pdo->query($sql,2)->fetch(2);
if ($_SESSION['jzdc']['group_id'] == 3){
    $module['map'] = '<li><a href="index.php?jzdc=form.data_show_list&table_id=37"><img src="./templates/1/form/default/page_icon/form.index.png" /> <span>企业认证</span></a></li>';
//    $module['map'].= '<li><a href="index.php?jzdc=slider.img&id=27"><img src="./templates/1/image/default/page_icon/image.index.png" /> <span>首页广告</span></a></li>';
    $module['map'].= '<li><a href="admin/banner/index.html"><img src="./templates/1/image/default/page_icon/image.index.png" /> <span>首页广告</span></a></li>';
    $module['map'].= '<li><a href="admin/notice/index.html"><img src="./templates/1/image/default/page_icon/image.index.png" /> <span>公告管理</span></a></li>';
//    $module['map'].= '<li><a href="index.php?jzdc=form.data_show_list&table_id=38"><img src="./templates/1/index/default/page_icon/index.financial_center_admin.png" /> <span>金融服务</span></a></li>';
    $module['map'].= '<li><a href="admin/service/index.html"><img src="./templates/1/index/default/page_icon/index.financial_center_admin.png" /> <span>金融服务</span></a></li>';
//    $module['map'].= '<li><a href="index.php?jzdc=form.data_show_list&table_id=39"><img src="./templates/1/index/default/page_icon/index.admin_msg.png" /> <span>投诉建议</span></a></li>';
    $module['map'].= '<li><a href="admin/suggestion/index.html"><img src="./templates/1/index/default/page_icon/index.admin_msg.png" /> <span>投诉建议</span></a></li>';

    $module['map'].=de_safe_str($v['map']);

}else{
    $module['map']=de_safe_str($v['map']);

}

$module['jzdc_table_name']=self::$language['functions'][str_replace("::",".",$method)]['description'];
$module['module_name']=str_replace("::","_",$method);
$_SESSION['token'][$method]=get_random(8);$module['action_url']="receive.php?token=".$_SESSION['token'][$method]."&target=".$method;
$module['search_url']=self::$config['web']['search_url'];
$module['search_placeholder']=self::$config['web']['search_placeholder'];


		$json=array();
		$json['msg']=0;
		$json['nickname']=$_SESSION['jzdc']['nickname'];
		$json['group']=$_SESSION['jzdc']['group'];
		$json['icon']=$_SESSION['jzdc']['icon'];

		$sql="select count(id) as c from ".$pdo->index_pre."site_msg where `addressee_state`=1 and `addressee`='".$_SESSION['jzdc']['username']."'";
		$r=$pdo->query($sql,2)->fetch(2);
		if($r['c']>0){
			$json['msg']=$r['c'];
		}
			
		$module['user_json']=json_encode($json);

$t_path='./templates/'.$m_require_login.'/'.$class.'/'.self::$config['program']['template_'.$m_require_login].'/'.$_COOKIE['jzdc_device'].'/'.str_replace($class."::","",$method).'.php';
if(!is_file($t_path)){$t_path='./templates/'.$m_require_login.'/'.$class.'/'.self::$config['program']['template_'.$m_require_login].'/pc/'.str_replace($class."::","",$method).'.php';}
require($t_path);