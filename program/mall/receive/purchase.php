<?php
$act=@$_GET['act'];
if($act=='update'){
	$id=intval($_POST['id']);
	$v=floatval($_POST['v']);
	$sql="update ".self::$table_pre."goods_batch set `price`=".$v." where `id`=".$id." and `shop_id`=".SHOP_ID;
	if($pdo->exec($sql)){
		exit("{'state':'success','info':'<span class=success>".self::$language['success']."</span>'}");
	}else{
		exit("{'state':'fail','info':'<span class=fail>".self::$language['fail']."</span>'}");
	}
}
if($act=='update_payment'){
	$id=intval($_POST['id']);
	$v=floatval($_POST['v']);
	$sql="update ".self::$table_pre."goods_batch set `payment`=".$v." where `id`=".$id." and `shop_id`=".SHOP_ID;
	if($pdo->exec($sql)){
		exit("{'state':'success','info':'<span class=success>".self::$language['success']."</span>'}");
	}else{
		exit("{'state':'fail','info':'<span class=fail>".self::$language['fail']."</span>'}");
	}
}
