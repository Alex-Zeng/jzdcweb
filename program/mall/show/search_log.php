<?php
//update data 
	if(date("Y",time())!=file_get_contents("./program/mall/search_txt/year.txt")){
		$sql="update ".self::$table_pre."search_log set `year`=0";
		$pdo->exec($sql);
		file_put_contents("./program/mall/search_txt/year.txt",date("Y",time()));	
	}
	if(date("m",time())!=file_get_contents("./program/mall/search_txt/month.txt")){
		$sql="update ".self::$table_pre."search_log set `month`=0";
		$pdo->exec($sql);
		file_put_contents("./program/mall/search_txt/month.txt",date("m",time()));	
	}
	if(date("w",time())!=file_get_contents("./program/mall/search_txt/week.txt")){
		$sql="update ".self::$table_pre."search_log set `week`=0";
		$pdo->exec($sql);
		file_put_contents("./program/mall/search_txt/week.txt",date("w",time()));	
	}
	if(date("d",time())!=file_get_contents("./program/mall/search_txt/day.txt")){
		$sql="update ".self::$table_pre."search_log set `day`=0";
		$pdo->exec($sql);
		file_put_contents("./program/mall/search_txt/day.txt",date("d",time()));	
	}



$module['jzdc_table_name']=self::$language['functions'][str_replace("::",".",$method)]['description'];
$module['module_name']=str_replace("::","_",$method);
$_GET['search']=safe_str(@$_GET['search']);
$_GET['search']=trim($_GET['search']);
$_GET['current_page']=(intval(@$_GET['current_page']))?intval(@$_GET['current_page']):1;
$page_size=self::$module_config[str_replace('::','.',$method)]['pagesize'];
$page_size=(intval(@$_GET['page_size']))?intval(@$_GET['page_size']):$page_size;
$page_size=min($page_size,100);

$sql="select * from ".self::$table_pre."search_log";

$where="";

if($_GET['search']!=''){$where=" and (`keyword` like '%".$_GET['search']."%')";}
$_GET['order']=safe_str(@$_GET['order']);
if($_GET['order']==''){
	$order=" order by `id` desc";
}else{
	$temp=safe_order_by($_GET['order']);
	if($temp[1]=='desc' || $temp[1]=='asc'){$order=" order by `".$temp[0]."` ".$temp[1];}else{$order='';}
		
}
$limit=" limit ".($_GET['current_page']-1)*$page_size.",".$page_size;
	$sum_sql=$sql.$where;
	$sum_sql=str_replace(" * "," count(id) as c ",$sum_sql);
	$sum_sql=str_replace("_search_log and","_search_log where",$sum_sql);
	$r=$pdo->query($sum_sql,2)->fetch(2);
	$sum=$r['c'];
$sql=$sql.$where.$order.$limit;
$sql=str_replace("_search_log and","_search_log where",$sql);
//echo($sql);
//exit();
$r=$pdo->query($sql,2);
$list='';


foreach($r as $v){
	$v=de_safe_str($v);
	$list.="<tr id='tr_".$v['id']."'>
	<td><input type='checkbox' name='".$v['id']."' id='".$v['id']."' class='id' /></td>
	<td><div class=keyword><a href='./index.php?jzdc=mall.goods_list&search=".urlencode($v['keyword'])."&click=true' target=_blank>".$v['keyword']."</a></div></td>
	<td>".$v['day']."</td>
	<td>".$v['week']."</td>
	<td>".$v['month']."</td>	
	<td>".$v['year']."</td>
	<td>".$v['sum']."</td>
  <td class=operation_td>
  <a href='#' onclick='return del(".$v['id'].")'  class='del'>".self::$language['del']."</a> <span id=state_".$v['id']." class='state'></span></td>
</tr>
";	
}
if($sum==0){$list='<tr><td colspan="30" class=no_related_content_td style="text-align:center;"><span class=no_related_content_span>'.self::$language['no_related_content'].'</span></td></tr>';}		
$_SESSION['token'][$method]=get_random(8);$module['action_url']="receive.php?token=".$_SESSION['token'][$method]."&target=".$method;
$module['list']=$list;
$module['page']=LansionDigitPage($sum,$_GET['current_page'],$page_size,'#'.$module['module_name'],self::$language['page_template']);

$module['hot_search']=self::$config['hot_search'];
		$t_path='./templates/'.$m_require_login.'/'.$class.'/'.self::$config['program']['template_'.$m_require_login].'/'.$_COOKIE['jzdc_device'].'/'.str_replace($class."::","",$method).'.php';
		if(!is_file($t_path)){$t_path='./templates/'.$m_require_login.'/'.$class.'/'.self::$config['program']['template_'.$m_require_login].'/pc/'.str_replace($class."::","",$method).'.php';}
		require($t_path);