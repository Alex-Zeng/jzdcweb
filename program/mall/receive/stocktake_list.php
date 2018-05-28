<?php
$act=@$_GET['act'];
if($act=='update'){
	$id=intval(@$_POST['id']);
	$stocktake=intval(@$_POST['stocktake']);
	$stocktake_id=intval(@$_GET['stocktake_id']);
	if($id<1){exit("{'state':'fail','info':'<span class=fail>id err</span>'}");}
	if($stocktake_id<1){exit("{'state':'fail','info':'<span class=fail>stocktake_id err</span>'}");}
	
	$sql="select * from ".self::$table_pre."stocktake_goods where `id`=".$id." and `stocktake_id`='".$stocktake_id."' limit 0,1";
	$s=$pdo->query($sql,2)->fetch(2);
	if($s['id']==''){exit("{'state':'fail','info':'<span class=fail>id err</span>'}");}
	if($s['state']==1){exit("{'state':'fail','info':'<span class=fail>".self::$language['stocktake_end'].self::$language['inoperable']."</span>'}");}

	if($s['s_id']==0){
		$sql="select `inventory` from ".self::$table_pre."goods where `id`=".$s['goods_id'];
		$r=$pdo->query($sql,2)->fetch(2);
		$r['quantity']=$r['inventory'];	
	}else{
		$sql="select `quantity` from ".self::$table_pre."goods_specifications where `id`=".$s['s_id'];
		$r=$pdo->query($sql,2)->fetch(2);
	}
	$stocktake=min($stocktake,$r['quantity']);
	
	$sql="update ".self::$table_pre."stocktake_goods set `stocktake`='".$stocktake."',`time`='".time()."' where `id`='$id' and `stocktake_id`='".$stocktake_id."'";
	if($pdo->exec($sql)){
		exit("{'state':'success','info':'<span class=success>".self::$language['success']."</span>'}");
	}else{
		exit("{'state':'fail','info':'<span class=fail>".self::$language['fail']."</span>'}");
	}

}

if($act=='loss'){
	$id=intval(@$_POST['id']);
	$stocktake_id=intval(@$_GET['stocktake_id']);
	$stocktake=intval(@$_POST['stocktake']);

	if($id<1){exit("{'state':'fail','info':'<span class=fail>id err</span>'}");}
	if($stocktake_id<1){exit("{'state':'fail','info':'<span class=fail>stocktake_id err</span>'}");}
	
	$sql="select * from ".self::$table_pre."stocktake_goods where `id`=".$id." and `stocktake_id`='".$stocktake_id."' limit 0,1";
	$s=$pdo->query($sql,2)->fetch(2);
	if($s['id']==''){exit("{'state':'fail','info':'<span class=fail>id err</span>'}");}
	if($s['state']==1){exit("{'state':'fail','info':'<span class=fail>".self::$language['stocktake_end'].self::$language['inoperable']."</span>'}");}
	if($s['s_id']==0){
		$sql="select `inventory` from ".self::$table_pre."goods where `id`=".$s['goods_id'];
		$r=$pdo->query($sql,2)->fetch(2);
		$r['quantity']=$r['inventory'];	
	}else{
		$sql="select `quantity` from ".self::$table_pre."goods_specifications where `id`=".$s['s_id'];
		$r=$pdo->query($sql,2)->fetch(2);
	}
	$stocktake=min($stocktake,$r['quantity']);
	
	$sql="update ".self::$table_pre."stocktake_goods set `stocktake`='".$stocktake."',`time`='".time()."' where `id`='$id' and `stocktake_id`='".$stocktake_id."'";
	$pdo->exec($sql);
	
	$sql="select * from ".self::$table_pre."stocktake_goods where `id`=".$id." and `stocktake_id`='".$stocktake_id."' and `shop_id`='".SHOP_ID."' limit 0,1";
	$s=$pdo->query($sql,2)->fetch(2);
	if($s['id']==''){exit("{'state':'fail','info':'<span class=fail>id err</span>'}");}
	if($s['state']!=0){exit("{'state':'fail','info':'<span class=fail>".self::$language['executed']."</span>'}");}
		
	if($s['s_id']==0){
		$sql="select `inventory` from ".self::$table_pre."goods where `id`=".$s['goods_id'];
		$r=$pdo->query($sql,2)->fetch(2);
		$r['quantity']=$r['inventory'];	
	}else{
		$sql="select `quantity` from ".self::$table_pre."goods_specifications where `id`=".$s['s_id'];
		$r=$pdo->query($sql,2)->fetch(2);
	}
	$quantity=$r['quantity']-$s['stocktake'];
	$quantity=max(0,$quantity);
	
	
	
	$sql="update ".self::$table_pre."stocktake_goods set `loss`='".$quantity."',`time`='".time()."',`state`='1' where `id`='$id' and `stocktake_id`='".$stocktake_id."' and `shop_id`=".SHOP_ID;
	if($pdo->exec($sql)){
		
		if($quantity==0 || $quantity<0){exit("{'state':'success','info':'<span class=success>".self::$language['success']."</span>'}");}
		if(self::stocktake_loss($pdo,$s['goods_id'],$s['s_id'],$quantity)){
			$sql="select `id` from ".self::$table_pre."stocktake_goods where `stocktake_id`='".$stocktake_id."' and `state`=0 limit 0,1";
			$r=$pdo->query($sql,2)->fetch(2);
			if($r['id']==''){
				$sql="update ".self::$table_pre."stocktake_goods set `state`=1 where `stocktake_id`='".$stocktake_id."' and `shop_id`='".SHOP_ID."'";
				$pdo->exec($sql);
				self::update_stocktake_loss($pdo,$stocktake_id);
			}
			exit("{'state':'success','info':'<span class=success>".self::$language['success']."</span>'}");
		}else{
			exit("{'state':'fail','info':'<span class=fail>".self::$language['fail']."</span>'}");
		}
		
	}else{
		exit("{'state':'fail','info':'<span class=fail>".self::$language['fail']."</span>'}");
	}

}

if($act=='bulk_loss'){
	$time=time();
	$stocktake_id=intval(@$_GET['stocktake_id']);
	if($stocktake_id<1){exit("{'state':'fail','info':'<span class=fail>stocktake_id err</span>'}");}
	$sql="select * from ".self::$table_pre."stocktake_goods where `stocktake_id`='".$stocktake_id."' and `state`=0 and `shop_id`='".SHOP_ID."'";
	$r=$pdo->query($sql,2);
	foreach($r as $v){
		if($v['time']==0){continue;}
		if($v['s_id']==0){
			$sql="select `inventory` from ".self::$table_pre."goods where `id`=".$v['goods_id'];
			$r=$pdo->query($sql,2)->fetch(2);
			$r['quantity']=$r['inventory'];	
		}else{
			$sql="select `quantity` from ".self::$table_pre."goods_specifications where `id`=".$v['s_id'];
			$r=$pdo->query($sql,2)->fetch(2);
		}
		$quantity=$r['quantity']-$v['stocktake'];
		$quantity=max(0,$quantity);
		$sql="update ".self::$table_pre."stocktake_goods set `loss`='".$quantity."',`time`='".$time."',`state`='1' where `id`='".$v['id']."' and `stocktake_id`='".$stocktake_id."' and `shop_id`=".SHOP_ID;
		
		if($pdo->exec($sql)){
			if($quantity==0 || $quantity<0){continue;}
			self::stocktake_loss($pdo,$v['goods_id'],$v['s_id'],$quantity);
			
		}
	}
	$sql="update ".self::$table_pre."stocktake set `state`=1 where `id`='".$stocktake_id."' and `shop_id`='".SHOP_ID."'";
	if($pdo->exec($sql)){
		$sql="update ".self::$table_pre."stocktake_goods set `state`=1 where `stocktake_id`='".$stocktake_id."' and `shop_id`='".SHOP_ID."'";
		$pdo->exec($sql);
		self::update_stocktake_loss($pdo,$stocktake_id);
		exit("{'state':'success','info':'<span class=success>".self::$language['success']."</span>'}");
	}else{
		exit("{'state':'fail','info':'<span class=fail>".self::$language['fail']."</span>'}");
	}
}

