<?php
$table_id=intval(@$_GET['table_id']);
if($table_id==0){exit('table_id err');}
$id=intval(@$_GET['id']);
if($id==0){exit('id err');}

$module['jzdc_table_name']=self::$language['functions'][str_replace("::",".",$method)]['description'];
$module['module_name']=str_replace("::","_",$method);
$_SESSION['token'][$method]=get_random(8);$module['action_url']="receive.php?token=".$_SESSION['token'][$method]."&target=".$method."&table_id=".$table_id."&id=".$id;
$module['count_url']="receive.php?target=".$method."&table_id=".$table_id."&id=".$id.'&act=count';

$sql="select `name`,`description`,`read_power`,`read_state` from ".self::$table_pre."table where `id`=$table_id";
$r=$pdo->query($sql,2)->fetch(2);
$table_name=$r['name'];
$table_description=$r['description'];
$table_read_power=explode('|',$r['read_power']);
if($r['read_state']!=1){echo $r['description'].self::$language['read_able_is_off'];return false;}
if(!in_array('0',$table_read_power)){
	if(!isset($_SESSION['jzdc']['group_id'])){
	    echo self::$language['without'].self::$language['view'].self::$language['power'];
        return false;
	}
	if(!in_array($_SESSION['jzdc']['group_id'],$table_read_power)){

	    echo self::$language['without'].self::$language['view'].self::$language['power'];
        return false;
	}
}

$sql="select * from ".self::$table_pre.$table_name." where `id`=".$id;
$module['data']=$pdo->query($sql,2)->fetch(2);
if($module['data']['id']==''){return  not_find();}
$module['data']=de_safe_str($module['data']);

$sql="select * from ".self::$table_pre."field where `table_id`=$table_id order by `sequence` desc,`id` asc";
$r=$pdo->query($sql,2);

$module['fields']='';
foreach($r as $v){
	//echo $v['description'].'<br />';
	if(in_array($v['name'],self::$config['sys_field'])){
	    continue;
		/*switch ($v['name']){
			case 'write_time':
				$v['input_type']='time';
				break;
			case 'writer':
				$v['input_type']='user';
				break;
			case 'edit_time':
				$v['input_type']='time';
				break;
			case 'editor':
				$v['input_type']='user';
				break;
			case 'publish':
				$v['input_type']='bool';
				break;
			case 'visit':
				$module['data'][$v['name']]++;
				$v['input_type']='text';
				break;
			default:
				$v['input_type']='text';	
		}*/
	}
	//echo $v['input_type'].'<br />';
    $content = $this->get_input_html3($pdo,self::$language,$v,$module['data'][$v['name']]);

    //申请状态
    if ($v['name']=='status'){
        switch ($module['data'][$v['name']]) {
            case 1:
                $content = str_replace('1','待审核',$content);
                $module['buttons']="<span class=input_span>
        <a href=\"#\" id='submit_pass' class=\"submit\"><span class=b_start> </span><span class=b_middle>".self::$language['pass']."</span><span class=b_end> </span></a>
        <a href=\"#\" id='submit_refuse' class=\"submit\"><span class=b_start> </span><span class=b_middle>".self::$language['refuse']."</span><span class=b_end> </span></a>
        <span></span></span></div>";
                break;
            case 3:
                $content = str_replace('3','已拒绝',$content);
                break;
            case 2:
                $content = str_replace('2','已通过',$content);
                break;
            case 0:
                $content = str_replace('0','未申请',$content);
                break;
        }
    }

    $module['fields'].=$content;
}

if($module['data']['publish']==0){
    //查看权限:本人或者管理人员
    if($module['data']['writer'] <> $_SESSION['jzdc']['id'] && $_SESSION['jzdc']['group_id'] <>3){
        echo self::$language['publish_0'];return false;
    }
}

$t_path='./templates/'.$m_require_login.'/'.$class.'/'.self::$config['program']['template_'.$m_require_login].'/'.$_COOKIE['jzdc_device'].'/'.str_replace($class."::","",$method).'.php';
if(!is_file($t_path)){$t_path='./templates/'.$m_require_login.'/'.$class.'/'.self::$config['program']['template_'.$m_require_login].'/pc/'.str_replace($class."::","",$method).'.php';}
require($t_path);
echo '<div id="visitor_position_reset" style="display:none;"><span id=current_position_text>'.self::$language['current_position'].'</span><a href="./index.php"><span id=visitor_position_icon>&nbsp;</span>'.self::$config['web']['name'].'</a><a href="./index.php?jzdc=form.data_show_list&table_id='.$table_id.'">'.$table_description.'</a>'.self::$language['detail'].'</div>';
		