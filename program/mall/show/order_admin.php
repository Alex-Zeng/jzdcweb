<?php
//send_im_msg(self::$config,self::$language,$pdo,$_SESSION['jzdc']['username'],'lansion.cn','auto_msg');

self::update_order_pay_method_remark($pdo);
self::update_expire_order($pdo,self::$table_pre,self::$config['pay_time_limit']);
$_SESSION['token'][$method]=get_random(8);$module['action_url']="receive.php?token=".$_SESSION['token'][$method]."&target=".$method;
$module['jzdc_table_name']=self::$language['functions'][str_replace("::",".",$method)]['description'];
$module['module_name']=str_replace("::","_",$method);
$_GET['visible']=@$_GET['visible'];
$_GET['search']=safe_str(@$_GET['search']);
$_GET['search']=trim($_GET['search']);
$_GET['current_page']=(intval(@$_GET['current_page']))?intval(@$_GET['current_page']):1;
$page_size=self::$module_config[str_replace('::','.',$method)]['pagesize'];
$page_size=(intval(@$_GET['page_size']))?intval(@$_GET['page_size']):$page_size;
$page_size=min($page_size,100);
$shop_master=self::get_shop_master($pdo,SHOP_ID);

$sql="select * from ".self::$table_pre."order where `shop_id`=".SHOP_ID." and `seller_del`=0";

$where="";
if(intval(@$_GET['id'])!=0){
	$where=" and `id`=".intval($_GET['id']);
	echo '<div  style="display:none;" id="user_position_append"><a href="./index.php?jzdc=mall.order_admin">'.self::$language['pages']['mall.order_admin']['name'].'</a><span class=text>'.$_GET['id'].'</span></div>';
}

if($_GET['search']!=''){$where=" and (`id` ='".$_GET['search']."' or `goods_names` like '%".$_GET['search']."%' or `buyer` like '%".$_GET['search']."%' or `express_code` like '%".$_GET['search']."%' or `preferential_code` like '%".$_GET['search']."%' or `receiver_name` like '%".$_GET['search']."%' or `receiver_phone` like '%".$_GET['search']."%' or `receiver_detail` like '%".$_GET['search']."%' or `receiver_post_code` like '%".$_GET['search']."%' or `receiver_area_name` like '%".$_GET['search']."%' or `share` like '%".$_GET['search']."%' or `check_code` ='".$_GET['search']."' or `out_id` like '%".$_GET['search']."%')";}

if(@$_GET['state']!=''){$where.=" and `state`='".intval($_GET['state'])."'";}
if(@$_GET['pay_method']!=''){$where.=" and `pay_method`='".safe_str($_GET['pay_method'])."'";}
if(@$_GET['buy_method']!=''){
	if($_GET['buy_method']=='jzdc'){$where.=" and `cashier`='jzdc'";}
	if($_GET['buy_method']=='cashier'){$where.=" and `cashier`!='jzdc'";}
}
if(@$_GET['preferential_way']!=''){$where.=" and `preferential_way`='".intval($_GET['preferential_way'])."'";}
if(@$_GET['express']!=''){$where.=" and `express`='".intval($_GET['express'])."'";}
if(@$_GET['start_time']!=''){
	$start_time=get_unixtime($_GET['start_time'],self::$config['other']['date_style']);
	$where.=" and `add_time`>$start_time";	
}
if(@$_GET['end_time']!=''){
	$end_time=get_unixtime($_GET['end_time'],self::$config['other']['date_style'])+86400;
	$where.=" and `add_time`<$end_time";	
}

$time=time();
$today=get_date($time,'Y-m-d',self::$config['other']['timeoffset']);
$time=get_unixtime($today,'y-m-d')-86400;
$yesterday=get_date($time,'Y-m-d',self::$config['other']['timeoffset']);

$time=get_unixtime($today,'y-m-d')-(86400*6);
$days_7=get_date($time,'Y-m-d',self::$config['other']['timeoffset']);

$time=get_unixtime($today,'y-m-d')-(86400*29);
$days_30=get_date($time,'Y-m-d',self::$config['other']['timeoffset']);

$module['days']='<a href="./index.php?jzdc=mall.order_admin&start_time='.$today.'&end_time='.$today.'" se="'.$today.'-'.$today.'">'.self::$language['today'].'</a>';
$module['days'].='<a href="./index.php?jzdc=mall.order_admin&start_time='.$yesterday.'&end_time='.$yesterday.'" se="'.$yesterday.'-'.$yesterday.'">'.self::$language['yesterday'].'</a>';
$module['days'].='<a href="./index.php?jzdc=mall.order_admin&start_time='.$days_7.'&end_time='.$today.'" se="'.$days_7.'-'.$today.'">'.self::$language['days_7'].'</a>';
$module['days'].='<a href="./index.php?jzdc=mall.order_admin&start_time='.$days_30.'&end_time='.$today.'" se="'.$days_30.'-'.$today.'">'.self::$language['days_30'].'</a>';

require "./plugin/html4Upfile/createHtml4.class.php";
$html4Upfile=new createHtml4();


if(@$_GET['pay_method_remark']!=''){
	$where.=" and (`pay_method_remark`='".safe_str($_GET['pay_method_remark'])."' or `pay_method_remark`='".safe_str($_GET['pay_method_remark'])."_wap')";
}


$_GET['order']=safe_str(@$_GET['order']);
if($_GET['order']==''){
	$order=" order by `id` desc";
}else{
	$temp=safe_order_by($_GET['order']);
	if($temp[1]=='desc' || $temp[1]=='asc'){$order=" order by `".$temp[0]."` ".$temp[1];}else{$order='';}
		
}
if(isset($_GET['credit_state'])){$where.=" and `credit_state`=".intval($_GET['credit_state']);}

$limit=" limit ".($_GET['current_page']-1)*$page_size.",".$page_size;
	$sum_sql=$sql.$where;
	$sum_sql=str_replace(" * "," count(id) as c ",$sum_sql);
	$sum_sql=str_replace("_order and","_order where",$sum_sql);
	$r=$pdo->query($sum_sql,2)->fetch(2);
	$sum=$r['c'];
$sql=$sql.$where.$order.$limit;
$sql=str_replace("_order and","_order where",$sql);
//echo($sql);
//exit();
$r=$pdo->query($sql,2);
$list='';


foreach($r as $v){
	
	if($v['shop_credits_money']>0){$v['credits_remark']='<div class=credits_remark>'.self::$language['use_shop_credits'].':'.$v['shop_credits'].','.self::$language['deduction'].self::$language['money_symbol'].$v['shop_credits_money'].'</div>';}else{$v['credits_remark']='';}

	$goods_money=0;
	$v=de_safe_str($v);
	$sql="select * from ".self::$table_pre."order_goods where `order_id`='".$v['id']."' order by `id` asc";
	$r2=$pdo->query($sql,2);
	$temp='';
	$phone_temp='';
	if($v['state']==0 || ($v['state']==1 && $v['pay_method']=='cash_on_delivery')){$edit_quantity="<a href=# class=edit_a d_id act='edit_quantity'></a>";}else{$edit_quantity='';}
    $priceHtml = '';
	
	foreach($r2 as $v2){
		if($v['preferential_way']==5){$o_price='<span class=o_price>'.$v2['price'].'</span>';$v2['price']=$v2['transaction_price'];}else{$o_price='';}
		$refund='';
		if($v2['refund']>0){$refund='<span class=refund>'.self::$language['refunded'].':<span class=refund_v>'.self::format_quantity($v2['refund']).'</span>'.$v2['unit'].'</span>';}
		
		if($_COOKIE['jzdc_device']=='pc'){
			$temp.='<div class=goods id=g_'.$v2['id'].'><a href="./index.php?jzdc=mall.goods&id='.$v2['goods_id'].'" target=_blank class=icon><img src="./program/mall/order_icon/'.$v2['icon'].'" /></a><div class=title_price><div class=title><a href="./index.php?jzdc=mall.goods&id='.$v2['goods_id'].'" target=_blank >'.$v2['title'].'</a></div><div class=price><a href="./index.php?jzdc=mall.goods_snapshot&id='.$v2['snapshot_id'].'" target=_balnk>'.self::$language['goods'].self::$language['snapshot'].'</a>原价:<span class=g_price>'.$o_price.str_replace('.00','',$v2['price'])."</span>*<span class=g_quantity>".self::format_quantity($v2['quantity']).'</span>'.$v2['unit']."=<span class=g_sum_money>".str_replace('.00','',$v2['price']*$v2['quantity']).'</span>'.self::$language['yuan'].$refund.$edit_quantity.' <input type="checkbox" name='.$v2['id'].' id='.$v2['id'].' class=goods_id /></div></div></div>';
		}else{
			$phone_temp.="<div class=goods_div  id=g_".$v2['id'].">
        	<span class=icon>
            	<a ><img src=./program/mall/order_icon/".$v2['icon']." /></a>
            </span><span class=other>
            	<div class=title>".$v2['title']."</div>
                <div class=price>".self::$language['price'].":".$o_price.self::$language['money_symbol'].str_replace('.00','',$v2['price'])." &nbsp; ".self::$language['quantity'].":".self::format_quantity($v2['quantity']).$v2['unit'].$refund.str_replace('d_id','d_id='.$v2['id'],$edit_quantity)." <a href=./index.php?jzdc=mall.goods_snapshot&id=".$v2['snapshot_id']." target=_balnk>".self::$language['snapshot']."</a></div>
            </span>
        </div>";
		}
		
		$goods_money+=$v2['transaction_price']*$v2['quantity'];

		//计算单价 以及数量
        $priceHtml.='<div class=line><span class=m_label>单价</span><span class=value><input type="text"  value="'.$v2['price'].'" id="p_unit_price_'.$v['id'].'" /> <span class=state>元</span></span></div>';
        $priceHtml.='<div class=line><span class=m_label>数量</span><span class=value><input style="height: 31px" type="number" min="1"  value="'.self::format_quantity($v2['quantity']).'" id="p_amount_'.$v['id'].'" /></span></div>';

	}
	if($v['buyer_remark']!=''){$v['buyer_remark']="<div class=buyer_remark>".self::$language['buyer_remark'].': '.$v['buyer_remark'].'</div>';}
    if($v['buyer_order_code']!=''){$v['buyer_remark']=$v['buyer_remark']."<div class=buyer_remark>".self::$language['buyer_order_code'].': '.$v['buyer_order_code'].'</div>';}

    if($v['state']<1){$actual="<div class=actual_money>".self::$language['actual_pay'].": <span class=value>".$v['actual_money']."</span> <a href=# class=edit_a d_id=".$v['id']." act='actual_money'></a></div>";}else{$actual="<div class=actual_money>".self::$language['actual_pay'].": <span class=value>".$v['actual_money']."</span></div>";}
	if($v['change_price_reason']!=''){$v['change_price_reason']="<div class=change_price_reason>".$v['change_price_reason']."</div>";}
	if($v['preferential_code']!=''){$v['preferential_code']='<div class=preferential_code>'.$v['preferential_code'].'</div>';}
	$act='';
	$cancel_reason='';
	$state_remark='';
	$edit_express_cost_buyer='';
	$edit_express_cost_seller='';
	if($v['pay_method']=='credit' && $v['credit_state']==1){$end_credit="<br /><i>".self::$language['have_pay'].'</i>';	}else{$end_credit='';}
	if($v['state']>0){
		//$state_remark='<div class=pay_method>'.self::$language['pay_method_str'].'<br />'.@self::$language['pay_method'][$v['pay_method']].$end_credit.'<span pay_method_remark='.$v['pay_method_remark'].'></span></div>';
	}
	//if($v['state']>1 && $v['express_code']!=''){$state_remark.='<a class=view_logistics  target=_blank order_id='.$v['id'].'>'.self::$language['view_logistics'].$end_credit.'</a>';}
	$automatically_confirm_receipt='';
	switch($v['state']){
		case 0: //待核价
            $act='<div class=line><select id="sign_type" class="form-control" onchange="signTypeChange(this,'.$v['id'].')"><option value="1" selected>已签定合同</option><option value="2">未签定合同</option></select></div>';

            $act.='<div id="sign1_'.$v['id'].'" >';
            $act.=$priceHtml;
            $act.='<div class=line><span class=m_label>核实总价</span><span class=value><input type="text" id="price_'.$v['id'].'" /> <span class=state>元</span></span></div>';
            $act.='<div class=line><span class=m_label>合同编号</span><span class=value><input type="text" id="contract_number_'.$v['id'].'" /> <span class=state></span></span></div>';
            $act.='<div class=line><span class=m_label>账期截止</span><span class=value><input type="text" id="pay_date_'.$v['id'].'" value="" onclick="show_datePicker(this.id,\'date\')" onblur="hide_datePicker()"/> <span class=state>(如果没有账期,请留空)</span></span></div>';
            $act.="<a href='#' class='pending_price2 operation_btn' d_id=".$v['id']." >确认</a>";
            $act.='</div>';

            $act.='<div id="sign2_'.$v['id'].'" style="display:none" >';
            $act.='<div class=line><span class=m_label>核实总价</span><span class=value><input type="text" id="p_price_'.$v['id'].'" /> <span class=state>元</span></span></div>';
            $act.="<a href='#' class='pending_price operation_btn' d_id=".$v['id']." >".self::$language['order_state'][1]."</a>";
            $act.='</div>';
            $act.="<a href='#' class='cancel operation_btn' d_id=".$v['id']." >取消订单</a>";

            //$state_remark='<div class=pay_time_limit>'.self::$language['pay_time_limit'].'<br />'.self::get_pay_time_limit(self::$language,self::$config['pay_time_limit'],$v['add_time']).'</div>';
			break;
		case 1:
		    //待签约
			//$edit_express_cost_seller="<a href=# class=edit_b d_id=".$v['id']." act='express_cost_seller'></a>";
            $act='<div><div class=line><span class=m_label>合同编号</span><span class=value><input type="text" id="contract_number_'.$v['id'].'" /> <span class=state></span></span></div>';
            $act.='<div class=line><span class=m_label>账期截止</span><span class=value><input type="text" id="pay_date_'.$v['id'].'" value="" onclick="show_datePicker(this.id,\'date\')" onblur="hide_datePicker()"/> <span class=state>(如果没有账期,请留空)</span></span></div>';
            $act.="<a href='#' class='pending_sign operation_btn' d_id=".$v['id']." >确认签约</a></div>";
            $act.="<a href='#' class='cancel operation_btn' d_id=".$v['id']." >取消订单</a>";
			break;
		case 2:
		    //待供应商打款
            //$edit_express_cost_seller="<a href=# class=edit_b d_id=".$v['id']." act='express_cost_seller'></a>";
            $icon_id = "icon".$v['id'];
            echo '<div style="display:none;">';
            $html4Upfile->echo_input($icon_id,'100%','./temp/','true','false','jpg|gif|png|jpeg',1024*10,'1');
            echo '</div>';
            $module['script'].='$(\'#'.$icon_id.'_ele\').insertBefore($(\'#'.$icon_id.'_state\'));';

            $act='<select id="pay_type" class="form-control" onchange="payTypeChange(this,'.$v['id'].')"><option value="1" selected>转账</option><option value="2">汇票</option></select>';
            $act.='<div id="type1_'.$v['id'].'" ><div class=line><span class=m_label>流水号</span><span class=value><input type="text" id="pay_number_'.$v['id'].'" /> <span class=state></span></span></div>';
            $act.='<div class=line><span class=m_label>到账时间</span><span class=value><input type="text" id="pay_date_'.$v['id'].'" value="" onclick="show_datePicker(this.id,\'date\')" onblur="hide_datePicker()"/> <span class=state></span></span></div></div>';
            $act.='<div id="type2_'.$v['id'].'" style="display:none" ><div class=line><span class=m_label>票号</span><span class=value><input type="text" id="bill_number_'.$v['id'].'" /> <span class=state></span></span></div>';
            $act.='<div class=line><span class=m_label>承兑时间</span><span class=value><input type="text" id="bill_time_'.$v['id'].'" value="" onclick="show_datePicker(this.id,\'date\')" onblur="hide_datePicker()"/> <span class=state></span></span></div></div>';
            $act.='<div class=line><span class=m_label>回执图片</span><span class=input_span><span class=state id='.$icon_id.'_state></span></div>';
            $act.="<a href='#' class='pending_pay operation_btn' d_id=".$v['id']." >确认收款</a>";
            $act.="<a href='#' class='cancel operation_btn' d_id=".$v['id']." >取消订单</a>";
            break;
		case 3:
			//$act="<a href='#' class=order_state_4>".self::$language['set_to'].self::$language['order_state'][4]."</a><br /><a href='#' class=order_state_8>".self::$language['set_to'].self::$language['order_state'][8]."</a><br />";
			break;
		case 4:
			$cancel_reason='<div class=cancel_reason>'.self::$language['cancel_reason'].":".$v['cancel_reason'].'</div>';
			break;
		case 5:
			$cancel_reason='<div class=cancel_reason>'.$v['cancel_reason'].'</div>';
			break;
		case 6:
			break;
		case 7:
			break;
		case 8:
		    //问题确认中
            $act.="<a href='#' class='question_fix operation_btn' d_id=".$v['id']." >问题已解决</a><br /><br />";
            break;
		case 9:
            //账期中,待采购商打款
            //$edit_express_cost_seller="<a href=# class=edit_b d_id=".$v['id']." act='express_cost_seller'></a>";
            $icon_id = "icon".$v['id'];
            echo '<div style="display:none;">';
            $html4Upfile->echo_input($icon_id,'100%','./temp/','true','false','jpg|gif|png|jpeg',1024*10,'1');
            echo '</div>';
            $module['script'].='$(\'#'.$icon_id.'_ele\').insertBefore($(\'#'.$icon_id.'_state\'));';

            $act='<select id="pay_type" class="form-control" onchange="payTypeChange(this,'.$v['id'].')"><option value="1" selected>转账</option><option value="2">汇票</option></select>';
            $act.='<div id="type1_'.$v['id'].'" ><div class=line><span class=m_label>流水号</span><span class=value><input type="text" id="pay_number_'.$v['id'].'" /> <span class=state></span></span></div>';
            $act.='<div class=line><span class=m_label>到账时间</span><span class=value><input type="text" id="pay_date_'.$v['id'].'" value="" onclick="show_datePicker(this.id,\'date\')" onblur="hide_datePicker()"/> <span class=state></span></span></div></div>';
            $act.='<div id="type2_'.$v['id'].'" style="display:none"><div class=line><span class=m_label>票号</span><span class=value><input type="text" id="bill_number_'.$v['id'].'" /> <span class=state></span></span></div>';
            $act.='<div class=line><span class=m_label>承兑时间</span><span class=value><input type="text" id="bill_time_'.$v['id'].'" value="" onclick="show_datePicker(this.id,\'date\')" onblur="hide_datePicker()"/> <span class=state></span></span></div></div>';
            $act.='<div class=line><span class=m_label>回执图片</span><span class=input_span><span class=state id='.$icon_id.'_state></span></div>';
            $act.="<a href='#' class='pending_pay operation_btn' d_id=".$v['id']." >确认收款</a>";
            break;
		case 10:
            //逾期中, 待采购打款
            //$edit_express_cost_seller="<a href=# class=edit_b d_id=".$v['id']." act='express_cost_seller'></a>";
            $icon_id = "icon".$v['id'];
            echo '<div style="display:none;">';
            $html4Upfile->echo_input($icon_id,'100%','./temp/','true','false','jpg|gif|png|jpeg',1024*10,'1');
            echo '</div>';
            $module['script'].='$(\'#'.$icon_id.'_ele\').insertBefore($(\'#'.$icon_id.'_state\'));';

            $act='<select id="pay_type" class="form-control" onchange="payTypeChange(this,'.$v['id'].')"><option value="1" selected>转账</option><option value="2">汇票</option></select>';
            $act.='<div id="type1_'.$v['id'].'" ><div class=line><span class=m_label>流水号</span><span class=value><input type="text" id="pay_number_'.$v['id'].'" /> <span class=state></span></span></div>';
            $act.='<div class=line><span class=m_label>到账时间</span><span class=value><input type="text" id="pay_date_'.$v['id'].'" value="" onclick="show_datePicker(this.id,\'date\')" onblur="hide_datePicker()"/> <span class=state></span></span></div></div>';
            $act.='<div id="type2_'.$v['id'].'" style="display:none"><div class=line><span class=m_label>票号</span><span class=value><input type="text" id="bill_number_'.$v['id'].'" /> <span class=state></span></span></div>';
            $act.='<div class=line><span class=m_label>承兑时间</span><span class=value><input type="text" id="bill_time_'.$v['id'].'" value="" onclick="show_datePicker(this.id,\'date\')" onblur="hide_datePicker()"/> <span class=state></span></span></div></div>';
            $act.='<div class=line><span class=m_label>回执图片</span><span class=input_span><span class=state id=icon'.$v['id'].'_state></span></div>';
            $act.="<a href='#' class='pending_pay operation_btn' d_id=".$v['id']." >确认收款</a>";
            break;
		case 11:
            //待打款给供应商
            //$edit_express_cost_seller="<a href=# class=edit_b d_id=".$v['id']." act='express_cost_seller'></a>";
            $icon_id = "icon".$v['id'];
            echo '<div style="display:none;">';
            $html4Upfile->echo_input($icon_id,'100%','./temp/','true','false','jpg|gif|png|jpeg',1024*10,'1');
            echo '</div>';
            $module['script'].='$(\'#'.$icon_id.'_ele\').insertBefore($(\'#'.$icon_id.'_state\'));';

            $act='<select id="pay_type" class="form-control" onchange="payTypeChange(this,'.$v['id'].')"><option value="1" selected>转账</option><option value="2">汇票</option></select>';
            $act.='<div id="type1_'.$v['id'].'" ><div class=line><span class=m_label>流水号</span><span class=value><input type="text" id="pay_number_'.$v['id'].'" /> <span class=state></span></span></div>';
            $act.='<div class=line><span class=m_label>到账时间</span><span class=value><input type="text" id="pay_date_'.$v['id'].'" value="" onclick="show_datePicker(this.id,\'date\')" onblur="hide_datePicker()"/> <span class=state></span></span></div></div>';
            $act.='<div id="type2_'.$v['id'].'" style="display:none"><div class=line><span class=m_label>票号</span><span class=value><input type="text" id="bill_number_'.$v['id'].'" /> <span class=state></span></span></div>';
            $act.='<div class=line><span class=m_label>承兑时间</span><span class=value><input type="text" id="bill_time_'.$v['id'].'" value="" onclick="show_datePicker(this.id,\'date\')" onblur="hide_datePicker()"/> <span class=state></span></span></div></div>';
            $act.='<div class=line><span class=m_label>回执图片</span><span class=input_span><span class=state id='.$icon_id.'_state></span></div>';
            $act.="<a href='#' class='pending_pay operation_btn' d_id=".$v['id']." >确认转账至供应商</a>";
            break;
		    break;
		case 12:
			//$act="<a href='#' class=cancel   d_id=".$v['id'].">".self::$language['cancel'].self::$language['order_id']."</a><br />";
			break;
		case 13:
			//$act="<a href='#' class=cancel   d_id=".$v['id'].">".self::$language['cancel'].self::$language['order_id']."</a><br />";
			break;

	}

	//if(in_array($v['state'],self::$config['order_del_able_seller'])){$act.="<a href='#' onclick='return del(".$v['id'].")'  class='del'>".self::$language['del']."</a><br />";}
	
	
	if($v['express_code']!=''){
		$temp3=explode(',',$v['express_code']);
		if(count($temp3)>1){
			$temp2='';
			foreach($temp3 as $v3){
				$temp2.='<a href=./'.$module['action_url'].'&act=go_express&id='.$v['express'].'&code='.$v3.' target=_blank>'.$v3.'</a> , ';		
			}
			$v['express_code']=trim($temp2,' , ');
		}else{
			$v['express_code']='<a href=./'.$module['action_url'].'&act=go_express&id='.$v['express'].'&code='.$v['express_code'].' target=_blank>'.$v['express_code'].'</a>';	
		}	
	}
	if($v['share']!=''){
		$share='<span class=share>'.self::$language['promotion'].':<span>'.get_username($pdo,$v['share']).'</span></span>';
	}else{
		$share='';
	}
	if($v['check_code']!=''){
		$check_code='<span class=check_code>'.self::$language['check_code'].':<span>'.$v['check_code'].'</span></span>';
	}else{
		$check_code='';
	}
	//$money_info="<div class=money_div><div class=sum_money>".self::$language['need_pay'].": <span class=value>".$v['sum_money']."</span></div>".$actual."</div>";
    $money_info="<div class=money_div><div class=sum_money>".self::$language['need_pay'].": <span class=value>".$v['sum_money']."</span></div></div>";
	if($v['pre_sale']==0){
		$preferential_way="<div class=preferential_way>".$v['preferential_code'].self::$language['preferential_way_option'][$v['preferential_way']].": -". sprintf('%.2f',$v['goods_money']+$v['express_cost_buyer']-$v['sum_money']).self::$language['yuan']."</div>";
		
	}else{
		$sql="select `goods_id` from ".self::$table_pre."order_goods where `order_id`=".$v['id']." limit 0,1";
		$og=$pdo->query($sql,2)->fetch(2);
		$sql="select * from ".self::$table_pre."pre_sale where `goods_id`=".$og['goods_id']." limit 0,1";
		$pre=$pdo->query($sql,2)->fetch(2);
		$sql="select `pre_discount` from ".self::$table_pre."goods where `id`=".$og['goods_id']." limit 0,1";
		$pre_discount=$pdo->query($sql,2)->fetch(2);
		$pre_discount=$pre_discount['pre_discount'];
		$preferential_way="<div class=preferential_way>".self::$language['pre_price'].':'.trim(trim($pre_discount,'0'),'.').self::$language['discount'].'<br />'.self::$language['deposit2'].':'.$pre['deposit'].'<br />'.self::$language['deduction'].':'.$pre['reduction'].'<br />'.self::$language['end_pay'].':'.($v['goods_money']-$pre['reduction']+$v['express_cost_buyer'])."</div><br /><br />";
		$money_info='';
	}

	$buyer_address='';
	if($v['receiver_id']==-1){$buyer_address=self::$language['no_delivery'].'  '.self::$language['take_self'];}
	if($v['receiver_id']==0){$buyer_address=self::$language['offline_purchase'];}
	//if($v['receiver_id']>0){$buyer_address=$v['receiver_name'].' '.$v['receiver_phone'].' '.$v['receiver_area_name'].' '.$v['receiver_detail'].' '.$v['receiver_post_code'].' '.' <span class=delivery_time>'.@self::$language['delivery_time_info'][$v['delivery_time']]."</span><span class=express>".self::get_express_name($pdo,self::$table_pre,$v['express'])."</span><span class=express_code>".$v['express_code']."</span>";}
    if($v['receiver_id']>0){$buyer_address=$v['receiver_name'].' '.$v['receiver_phone'].' '.$v['receiver_area_name'].' '.$v['receiver_detail'].' '.$v['receiver_post_code'].' ';}

    if($_COOKIE['jzdc_device']=='pc'){
		/*$list.="<div class='mall_order  portlet light'>
		<div class=order_head id=head_".$v['id']."><div class=title_tr><div class=buyer_info><span class=add_time>".get_time(self::$config['other']['date_style'],self::$config['other']['timeoffset'],self::$language,$v['add_time'])."</span><span class=order_id>".self::$language['order_number'].": <span class=value>".$v['out_id']."</span></span><span  class=buyer><a talk='".$v['buyer']."'>".$v['buyer']."</a></span><span class=express_cost_buyer>".self::$language['express_cost_buyer'].":<span class=value>".str_replace('.00','',$v['express_cost_buyer'])."</span>  ".$edit_express_cost_buyer."</span><span class=express_cost_seller>".self::$language['express_cost_seller'].":<span class=value>".str_replace('.00','',$v['express_cost_seller'])."</span>  ".$edit_express_cost_seller."</span><span class=invoice>".$v['invoice']."</span>".$share."</div><div class=buyer_address>".$buyer_address.$v['credits_remark']."</div></div></div>
            <div class=order_tr id='tr_".$v['id']."'>
                <div class=checkbox_td><input type='checkbox' name='".$v['id']."' id='".$v['id']."' class='id' /></div>
                <div class=goods_td><div class=goods_info>".$temp."</div><div class=remark>".$v['buyer_remark']."<div class=seller_remark>".self::$language['seller'].self::$language['remark'].': <span class=value>'.$v['seller_remark']."</span> <a href=# class=edit_a d_id=".$v['id']." act='seller_remark'></a></div></div></div>
                <div class=preferential_td>".$preferential_way.$money_info.$v['change_price_reason']."</div>
                <div class=state_td><div class=order_state value='".self::$language['order_state'][$v['state']]."'>".self::$language['order_state'][$v['state']].$automatically_confirm_receipt.$cancel_reason."</div><div class=state_remark>".$state_remark."</div></div>
                <div class=operation_td>".$act." <span id=state_".$v['id']." class='state'></span></div>
            </div>
        </div>
        ";*/
		if (!empty($v['supplier']) && $v['supplier']>0 ){
            $supplier_name = self::get_supplier_name($pdo, $v['supplier']);
        }else{
            $supplier_name = "";
        }
        if (!empty($v['pay_date'])){
            $pay_date = $v['pay_date'];
        }else{
            $pay_date = "无";
        }

        //$supplier_name = "";
        $list.="<div class='mall_order'>
		<div class=order_head id=head_".$v['id']."><div class=title_tr><div class=buyer_info><span class=add_time>".get_time(self::$config['other']['date_style'],self::$config['other']['timeoffset'],self::$language,$v['add_time'])."</span><span class=order_id>".self::$language['order_number'].": <span class=value>".$v['out_id']."</span></span><span  class=buyer>采购商:".$v['buyer']."</span><span  class=buyer>供应商:".$supplier_name."</span><span  class=buyer>账期截止日:".$pay_date."</span></div><div class=buyer_address>".$buyer_address.$v['credits_remark']."</div></div></div>
            <div class=order_tr id='tr_".$v['id']."'>
                <div class=checkbox_td><input type='checkbox' name='".$v['id']."' id='".$v['id']."' class='id' /></div>
                <div class=goods_td>
                      <div class=goods_info>".$temp."</div>
                      <div class=remark>".$v['buyer_remark']."
                            <div class=seller_remark>采购商支付: <span class=value>".self::get_buyer_pay_info($pdo, $v['id'])."</span> </div>
                            <div class=seller_remark>支付至供应商: <span class=value>".self::get_supplier_pay_info($pdo, $v['id'])."</span> </div>
                            <div class=seller_remark>买家留言: <span class=value>".$v['buyer_comment']."</span> </div>
                      </div>
                </div>
                <div class=preferential_td>".$money_info.$v['change_price_reason']."</div>
                <div class=state_td><div class=order_state value='".self::$language['order_state'][$v['state']]."'>".self::$language['order_state'][$v['state']].$automatically_confirm_receipt."</div>".$cancel_reason."<div class=state_remark>".$state_remark."</div></div>
                <div class=operation_td>".$act."<br/> <span id=state_".$v['id']." class='state'></span></div>
            </div>
        </div>
        ";
	}else{
       /* $list.="<div class=mall_order  id='tr_".$v['id']."'>
        	<div class=order_head><div class=shop_name>".get_time(self::$config['other']['date_style'],self::$config['other']['timeoffset'],self::$language,$v['add_time'])." <a  talk='".$v['buyer']."'>".$v['buyer']."</a></div><div class=order_state value='".self::$language['order_state'][$v['state']]."'>".self::$language['order_state'][$v['state']].$cancel_reason."</div>".$v['credits_remark']."<span class=express_code>".$v['express_code']."</span></div>
            <div class=goods_td><div class=goods_info>".$phone_temp."</div><div class=remark>".$v['buyer_remark']."</div></div>
           	<div class=preferential_td>".$preferential_way.$money_info.$v['change_price_reason']."</div>
            <div class=operation_td>".$act." <span id=state_".$v['id']." class='state'></span></div>
			<div class=state_remark>".$state_remark."</div>
        </div>";

		$list.="<div class=mall_order  id='tr_".$v['id']."'>
        	<div class=order_head><div class=shop_name>".get_time(self::$config['other']['date_style'],self::$config['other']['timeoffset'],self::$language,$v['add_time'])." <a  talk='".$v['buyer']."'>".$v['buyer']."</a></div><div class=order_state value='".self::$language['order_state'][$v['state']]."'>".self::$language['order_state'][$v['state']].$cancel_reason."</div>".$v['credits_remark']."<span class=express_code>".$v['express_code']."</span></div>
            <div class=goods_td><div class=goods_info>".$phone_temp."</div><div class=remark>".$v['buyer_remark']."</div></div>
           	<div class=preferential_td>".$money_info.$v['change_price_reason']."</div>
            <div class=operation_td>".$act." <span id=state_".$v['id']." class='state'></span></div>
			<div class=state_remark>".$state_remark."</div>
        </div>
";		*/
	}
	
}
if($sum==0){
	$list='<tr><td colspan="30" class=no_related_content_td style="text-align:center;"><span class=no_related_content_span>'.self::$language['no_related_content'].'</span></td></tr>';
	if($_COOKIE['jzdc_device']=='phone'){$list=self::$language['no_related_content'];}
}		
$module['list']=$list;
$module['page']=LansionDigitPage($sum,$_GET['current_page'],$page_size,'#'.$module['module_name'],self::$language['page_template']);


function get_mall_array_option($array){
	$list='';
	foreach($array as $k=>$v){
		//if($k=='bank_transfer'){continue;}
		$list.='<option value='.$k.'>'.$v.'</option>';
	}
	return $list;	
}
function get_mall_invoice_option($pdo,$table_pre){
	$sql="select * from ".$table_pre."invoice order by `sequence` desc";
	$r=$pdo->query($sql,2);
	$list='';
	foreach($r as $v){
		$list.='<option value="'.$v['name'].'">'.$v['name'].'</option>';
	}
	return $list;
}
function get_mall_express_option($pdo,$table_pre){
	$sql="select * from ".$table_pre."express order by `sequence` desc";
	$r=$pdo->query($sql,2);
	$list='';
	foreach($r as $v){
		$list.='<option value="'.$v['id'].'">'.$v['name'].'</option>';
	}
	return $list;
}

function get_pay_api_option($pdo,$language){
	$list='';
	foreach($language['pay_api_option'] as $k=>$v){
		$list.='<option value="'.$k.'">'.$v.'</option>';
	}
	return $list;
}


$module['filter']="<select id='state' name='state'><option value='' selected>".self::$language['all'].self::$language['state']."</option>".get_mall_array_option(self::$language['order_state'])."</select>";
/*$module['filter'].="<select id='pay_method' name='pay_method'><option value='-1'>".self::$language['pay_method_str']."</option><option value='' selected>".self::$language['all'].self::$language['pay_method_str']."</option>".get_mall_array_option(self::$language['pay_method'])."</select>";
$module['filter'].="<select id='buy_method' name='buy_method'><option value='-1'>".self::$language['buy_method']."</option><option value='' selected>".self::$language['all'].self::$language['buy_method']."</option><option value='jzdc'>".self::$language['buy_method_option']['jzdc']."</option><option value='cashier'>".self::$language['buy_method_option']['cashier']."</option></select>";

$module['filter'].="<select id='preferential_way' name='preferential_way'><option value='-1'>".self::$language['use_method']."</option><option value='' selected>".self::$language['all'].self::$language['use_method']."</option>".get_mall_array_option(self::$language['preferential_way_option'])."</select>";
$module['filter'].="<select id='express' name='express'><option value='-1'>".self::$language['express']."</option><option value='' selected>".self::$language['all'].self::$language['express']."</option>".get_mall_express_option($pdo,self::$table_pre)."</select>";
$module['filter'].="<select id='pay_method_remark' name='pay_method_remark'><option value='-1'>".self::$language['pay_api']."</option><option value='' selected>".self::$language['all'].self::$language['pay_api']."</option>".get_pay_api_option($pdo,self::$language)."</select>";

*/
//===============================================================================================================================【获取统计信息】
if(@$_GET['state']==''){$module['module_state_name']=self::$language['all'].self::$language['state'];}else{$module['module_state_name']=self::$language['order_state'][intval($_GET['state'])];}

$where=" where `shop_id`=".SHOP_ID."  and `seller_del`=0 ".$where;
$where=rtrim($where);
$where=str_replace('  ',' ',$where);

$sql="select sum(`actual_money`) as c from ".self::$table_pre."order".$where;
//echo $sql;
$sql=str_replace("_order and","_order where",$sql);
$r=$pdo->query($sql,2)->fetch(2);
$module['sum']['sum']=floatval($r['c']);

$t_path='./templates/'.$m_require_login.'/'.$class.'/'.self::$config['program']['template_'.$m_require_login].'/'.$_COOKIE['jzdc_device'].'/'.str_replace($class."::","",$method).'.php';
if(!is_file($t_path)){$t_path='./templates/'.$m_require_login.'/'.$class.'/'.self::$config['program']['template_'.$m_require_login].'/pc/'.str_replace($class."::","",$method).'.php';}
require($t_path);