<?php
if(@$_GET['args']==''){echo 'need args';return false;}
if(@$_GET['url']==''){echo 'need url';return false;}
$module['jzdc_table_name']=self::$language['functions'][str_replace("::",".",$method)]['description'];
$module['module_name']=str_replace("::","_",$method);
$attribute=format_attribute($_GET['args']);
$module=array_merge($module,$attribute);

$module['action_url']="receive.php?target=index::edit_page_layout&act=update_attribute&old_module=mall.goods_module".$_GET['args'].'&url='.$_GET['url'].'&new_module=mall.goods_module';
$module['action_url_img']="receive.php?target=".$method;


$module['tags']=self::get_tag_html($pdo);
$module['store_tags']=self::get_store_tag_html($pdo);
$module['circle_option']=get_circle_option($pdo);
$module['sequence_field_option']='
<option value="bidding_show">'.self::$language['bidding_show'].'</option>
<option value="sequence">'.self::$language['sequence_value'].'</option>
<option value="id">'.self::$language['add_time2'].'</option>
<option value="visit">'.self::$language['visit_count'].'</option>
<option value="sold">'.self::$language['sold'].'</option>
<option value="monthly">'.self::$language['monthly_sold'].'</option>
<option value="deposit">'.self::$language['deposit'].'</option>
';
if(field_exist($pdo,self::$table_pre.'img','discuss_msg')){
	$module['sequence_field_option'].='<option value="discuss_msg">'.self::$language['discuss_msg_count'].'</option>';
}
if(field_exist($pdo,self::$table_pre.'img','discuss_high')){
	$module['sequence_field_option'].='<option value="discuss_high">'.self::$language['discuss_high_count'].'</option>';	
}
if(field_exist($pdo,self::$table_pre.'img','discuss_medium')){
	$module['sequence_field_option'].='<option value="discuss_medium">'.self::$language['discuss_medium_count'].'</option>';	
}
if(field_exist($pdo,self::$table_pre.'img','discuss_low')){
	$module['sequence_field_option'].='<option value="discuss_low">'.self::$language['discuss_low_count'].'</option>';	
}

$module['sequence_field_type']='<option value="desc">'.self::$language['max_to_min'].'</option>
<option value="asc">'.self::$language['min_to_max'].'</option>';
$module['data_src_option']=$this->get_parent($pdo);

$module['target_option']=get_select_value($pdo,'target',$attribute['target']);
$module['img_link']=str_replace('*','.',@$module['img_link']);
$module['img_link']=str_replace('!',':',$module['img_link']);
$module['img_link']=str_replace('andand','&',$module['img_link']);

if(@$module['img']!=''){
	$module['img']=str_replace('*','.',$module['img']);
	$module['img_show']='<a href="./program/mall/img/'.$module['img'].'" target="_blank" class=show_img><img src="./program/mall/img/'.$module['img'].'" ></a> <a href=# class=del>'.self::$language['del'].'</a>';	
}else{
	$module['img_show']='';
}
$all=array('unit'=>self::$language['goods_unit'],'normal_price'=>self::$language['original_price'],'multi_angle_img'=>self::$language['multi_angle_img']);
$module['field_checkbox']=get_field_checkbox(@$attribute['field'],$all);

$sql="select `id`,`title` from ".$pdo->sys_pre."diymodule_module order by `sequence` desc ,`id` desc";
$r=$pdo->query($sql,2);
$module['first_option']='';
foreach($r as $v){
	$module['first_option'].='<option value="'.$v['id'].'">'.de_safe_str($v['title']).'</option>';	
}
$module['first']=@$module['first'];

$t_path='./templates/'.$m_require_login.'/'.$class.'/'.self::$config['program']['template_'.$m_require_login].'/'.$_COOKIE['jzdc_device'].'/'.str_replace($class."::","",$method).'.php';
if(!is_file($t_path)){$t_path='./templates/'.$m_require_login.'/'.$class.'/'.self::$config['program']['template_'.$m_require_login].'/pc/'.str_replace($class."::","",$method).'.php';}
require($t_path);

require "./plugin/html4Upfile/createHtml4.class.php";
$html4Upfile=new createHtml4();
$html4Upfile->echo_input("img_file",'500px','./temp/','true','false','jpg|gif|png|jpeg','1024','1');
//echo_input("控件名称",'控件宽度(百分比或像素)','保存目录','文件夹是否附加日期','是否原名保存','允许文件后缀','文件大小 上限','文件大小 下限','指定保存名');
//指定保存名时，要先设置权限 $_SESSION['replace_file']=true;  ，否则将无效
