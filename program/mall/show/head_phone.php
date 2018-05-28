<?php
$module['jzdc_table_name']=self::$language['functions'][str_replace("::",".",$method)]['description'];
$module['module_name']=str_replace("::","_",$method);

$sql="select `id`,`head_".$_COOKIE['jzdc_device']."`,`position`,`name` from ".self::$table_pre."shop where `id`='".SHOP_ID."'";
$r=$pdo->query($sql,2)->fetch(2);
if($r['id']==''){echo 'no shop';return false;}
$module['shop_id']=SHOP_ID;
$module['module_content']=de_safe_str($r['head_'.$_COOKIE['jzdc_device']]);
$_POST['shop_name']=$r['name'];
$_POST['shop_position']=$r['position'];
$temp=explode(',',$r['position']);
$_POST['shop_latlng']=@$temp[1].','.@$temp[0];

$sql="select `goods` from ".self::$table_pre."shop where `id`='".SHOP_ID."'";
$r=$pdo->query($sql,2)->fetch(2);
$module['goods']=$r['goods'];

$sql="select `id` from ".self::$table_pre."diypage where `shop_id`='".SHOP_ID."' and `creater`='jzdc' order by `id` asc limit 0,1";
$r=$pdo->query($sql,2)->fetch(2);
$module['contact']='';
if($r['id']!=''){$module['contact']='<a href="./index.php?jzdc=mall.diypage_show&shop_id='.SHOP_ID.'&id='.$r['id'].'"><div class=contact></div><div>'.self::$language['contact'].'</div></a>';}

$sql="select `pay_ad_fees` from ".self::$table_pre."shop_order_set where `shop_id`='".SHOP_ID."' limit 0,1";
$r=$pdo->query($sql,2)->fetch(2);
if($r['pay_ad_fees']==1){self::$language['share']=self::$language['share_earn'];}

$sql="select `id`,`name` from ".self::$table_pre."shop_type where `shop_id`='".SHOP_ID."' and `parent`=0 and `visible`=1 order by `sequence` desc";
$r=$pdo->query($sql,2);
$list='<a href=./index.php?jzdc=mall.shop_goods_list&shop_id='.SHOP_ID.' d_id="" class=t_1>'.self::$language['all_type'].'</a>';
foreach($r as $v){
	$v['name']=de_safe_str($v['name']);
	$list.='<a href=./index.php?jzdc=mall.shop_goods_list&shop_id='.SHOP_ID.'&type='.$v['id'].'  d_id='.$v['id'].' class=t_1>'.$v['name'].'</a>';
	$sql="select `id`,`name` from ".self::$table_pre."shop_type where `shop_id`='".SHOP_ID."' and `parent`='".$v['id']."' and `visible`=1 order by `sequence` desc";
	$r2=$pdo->query($sql,2);
	foreach($r2 as $v2){
		$v2['name']=de_safe_str($v2['name']);
		$list.='<a href=./index.php?jzdc=mall.shop_goods_list&shop_id='.SHOP_ID.'&type='.$v2['id'].' d_id='.$v2['id'].' class=t_2>'.$v2['name'].'</a>';
		$sql="select `id`,`name` from ".self::$table_pre."shop_type where `shop_id`='".SHOP_ID."' and `parent`='".$v2['id']."' and `visible`=1 order by `sequence` desc";
		$r3=$pdo->query($sql,2);
		foreach($r3 as $v3){
			$v3['name']=de_safe_str($v3['name']);
			$list.='<a href=./index.php?jzdc=mall.shop_goods_list&shop_id='.SHOP_ID.'&type='.$v3['id'].' d_id='.$v3['id'].' class=t_3>'.$v3['name'].'</a>';
		}
		
	}
}
$module['type_list']=$list;

require('./templates/0/'.$class.'_shop/'.self::$config['shop_template'].'/'.$_COOKIE['jzdc_device'].'/'.str_replace($class."::","",$method).'.php');	
