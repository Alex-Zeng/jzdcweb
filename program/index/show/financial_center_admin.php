<?php
		$menu=require("./program/".$class."/menu.php");
		$pages=require("./program/".$class."/pages.php");
		$list='';
		foreach($menu[str_replace("::",".",$method)] as $key=>$v){
			if(is_array($v)){$v=$key;}
			$t=explode(".",$pages[$v]['url']);
				if(!in_array($pages[$v]['url'],$_SESSION['jzdc']['page'])){continue;}
	$v2['icon_path']="./templates/1/".$class."/".self::$config['program']['template_1']."/page_icon/".$pages[$v]['url'].".png";


			$list.="<li><a href=index.php?jzdc={$pages[$v]['url']} target='".$pages[$v]['target']."'><img src='".$v2['icon_path']."' /><br><span>".self::$language['pages'][$v]['name']."</span></a></li>";
			
		}
		$module['list']="<ul id=icons>{$list}<div style='clear:both;'></div></ul>";
		$module['jzdc_table_name']=self::$language['functions'][str_replace("::",".",$method)]['description'];
$module['module_name']=str_replace("::","_",$method);
		
		$sql="select sum(money) as c from ".$pdo->index_pre."recharge where `state`=4";
		$r=$pdo->query($sql,2)->fetch(2);
		$module['recharge']=$r['c']==''?0:$r['c'];
		
		$sql="select sum(money) as c from ".$pdo->index_pre."withdraw where `state`=3";
		$r=$pdo->query($sql,2)->fetch(2);
		$module['withdraw']=$r['c']==''?0:$r['c'];
		
		$sql="select sum(money) as c from ".$pdo->index_pre."user";
		$r=$pdo->query($sql,2)->fetch(2);
		$module['user_money']=$r['c']==''?0:$r['c'];
		
		
		$t_path='./templates/'.$m_require_login.'/'.$class.'/'.self::$config['program']['template_'.$m_require_login].'/'.$_COOKIE['jzdc_device'].'/'.str_replace($class."::","",$method).'.php';
		if(!is_file($t_path)){$t_path='./templates/'.$m_require_login.'/'.$class.'/'.self::$config['program']['template_'.$m_require_login].'/pc/'.str_replace($class."::","",$method).'.php';}
		require($t_path);