<?php
		$id=intval(@$_GET['id']);
		if($id>0){
			$sql="update ".$pdo->index_pre."site_msg set `addressee_state`='2' where `id`='$id' and `addressee`='".$_SESSION['jzdc']['username']."'";
			$pdo->exec($sql);
		}