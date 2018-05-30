<?php

if ($_SESSION["jzdc"]["group_id"] <> 3 && $_SESSION["jzdc"]["group_id"] <> 2){
    exit("{'state':'fail','info':'id err'}");
}

$act=@$_GET['act'];
$id=intval(@$_GET['id']);
if($act!='export' && $id==0){exit("{'state':'fail','info':'id err'}");}

//==================================================================================================================================【查看物流】
if($act=='go_express'){
	$sql="select `url` from ".self::$table_pre."express where `id`='".$id."'";
	$r=$pdo->query($sql,2)->fetch(2);
	if($r['url']==''){exit(sef::$language['query_url'].self::$language['is_null']);}
	header("location:".$r['url'].@$_GET['code']);	
}


if($id!=0){
	$sql="select * from ".self::$table_pre."order where `id`='".$id."' limit 0,1";
	$r=$pdo->query($sql,2)->fetch(2);
	if($r['id']==''){exit("{'state':'fail','info':'id err'}");}	
	if($r['shop_id']!=SHOP_ID){exit("{'state':'fail','info':'id err'}");}	
}
switch ($act){
	case 'del'://================================================================================================================【删除单个订单】
		if(!in_array($r['state'],self::$config['order_del_able_seller'])){exit("{'state':'fail','info':'<span class=fail>".self::$language['state'].':'.self::$language['order_state'][$r['state']]." ".self::$language['inoperable']."</span>'}");}
		$sql="update ".self::$table_pre."order set `seller_del`='1' where `id`=".$id." and `shop_id`='".SHOP_ID."'";
		if($pdo->exec($sql)){
			exit("{'state':'success','info':'<span class=success>".self::$language['success']."</span>'}");
		}else{
			exit("{'state':'fail','info':'<span class=fail>".self::$language['fail']."</span>'}");
		}
		break;	
		
	/*case 'del_select'://================================================================================================================【删除选中订单】
		$ids=@$_GET['ids'];
		if($ids==''){exit("{'state':'fail','info':'<span class=fail>&nbsp;</span>".self::$language['select_null']."'}");}
		$ids=explode("|",$ids);
		$ids=array_filter($ids);
		$success='';
		foreach($ids as $id){
			$id=intval($id);
			$sql="select `state` from ".self::$table_pre."order where `id`='$id'";
			$r=$pdo->query($sql,2)->fetch(2);
			if(in_array($r['state'],self::$config['order_del_able_seller'])){
				$sql="update ".self::$table_pre."order set `seller_del`='1' where `id`=".$id." and `shop_id`='".SHOP_ID."'";
				if($pdo->exec($sql)){
					$success.=$id."|";
				}
			}
		}
		$success=trim($success,"|");			
		exit("{'state':'success','info':'<span class=success>".self::$language['executed']."</span> <a href=javascript:window.location.reload();>".self::$language['refresh']."</a>','ids':'".$success."'}");
		break;	*/

    case 'pending_price'://================================================================================================================
        //待核价 --> 待签约
        $price  =@$_GET['price'];
        if($price<=0){exit("{'state':'fail','info':'<span class=fail>核实价格不正确</span>'}");}

        if($r['state']!=0){exit("{'state':'fail','info':'<span class=fail>".self::$language['state'].':'.self::$language['order_state'][$r['state']]." ".self::$language['inoperable']."</span>'}");}
        $sql="update ".self::$table_pre."order set `state`='1',`goods_money`=".$price.", `received_money`=".$price.",`sum_money`=".$price.",`actual_money`=".$price."  where `id`=".$id;
        if($pdo->exec($sql)){
            $r['state']=1;
            exit("{'state':'success','info':'<span class=success>".self::$language['success']."</span>'}");
        }else{
            exit("{'state':'fail','info':'<span class=fail>".self::$language['fail']."</span>'}");
        }
        break;

    case 'pending_price2'://================================================================================================================
        //待核价 --> 待打款/待发货
        $contract_number  =@$_GET['number'];
        $pay_date =@$_GET['date'];
        $price  =@$_GET['price'];
        $product_amount = isset($_GET['product_amount']) ? $_GET['product_amount'] : 0;
        $unit_price = isset($_GET['unit_price']) ? $_GET['unit_price'] : 0;

        if($price<=0){exit("{'state':'fail','info':'<span class=fail>核实价格不正确</span>'}");}
        if(empty($contract_number)){exit("{'state':'fail','info':'<span class=fail>合同编号不能为空</span>'}");}
        if($unit_price<=0){exit("{'state':'fail','info':'<span class=fail>单价不正确</span>'}");}
        if($product_amount<=0){exit("{'state':'fail','info':'<span class=fail>产品数量不正确</span>'}");}
        if($r['state']<>0 ){exit("{'state':'fail','info':'<span class=fail>".self::$language['state'].':'.self::$language['order_state'][$r['state']]." ".self::$language['inoperable']."</span>'}");}

        if(empty($pay_date)){
            $status = 2; //无账期-》待打款
            $sql="update ".self::$table_pre."order set `state`='".$status."',`pay_date`=null,`contract_number`='".$contract_number."' ,`goods_money`=".$price.", `received_money`=".$price.",`sum_money`=".$price.",`actual_money`=".$price.",`goods_count`=".$product_amount." where `id`=".$id;
        }else{
            $pay_date .=" 23:59:59";
            $status = 3; //有账期-》待发货
            $sql="update ".self::$table_pre."order set `state`='".$status."',`pay_date`='".$pay_date."' ,`contract_number`='".$contract_number."' ,`goods_money`=".$price.", `received_money`=".$price.",`sum_money`=".$price.",`actual_money`=".$price.",`goods_count`=".$product_amount." where `id`=".$id;
        }

        if($pdo->exec($sql)){
            $r['state']=$status;
            //更新商品单价
            $priceSql = "update ".self::$table_pre."order_goods set `price`=".$unit_price.",`quantity`=".$product_amount." where order_id=".$id;
            $pdo->exec($priceSql);
            if ($status == '3'){
                //发短信:通知供应商发货
                $sql="select `phone` from ".$pdo->index_pre."user where `id`='".$r['supplier']."'";
                $r2=$pdo->query($sql,2)->fetch(2);
                if ($r2){
                    $param = array (
                        'order_id' => $r['out_id'],
                    );
                    $msg = send_notice_sms(self::$config, $pdo, $r2["phone"], 'order_pending_send', $param);
                }
            }
            exit("{'state':'success','info':'<span class=success>".self::$language['success']."</span>'}");
        }else{
            exit("{'state':'fail','info':'<span class=fail>".self::$language['fail']."</span>'}");
        }
        break;

    case 'pending_sign'://================================================================================================================
    //待签约 ->待打款/待发货

    $contract_number  =@$_GET['number'];
    $pay_date =@$_GET['date'];

    if(empty($contract_number)){exit("{'state':'fail','info':'<span class=fail>合同编号不能为空</span>'}");}

    if($r['state']<>1){exit("{'state':'fail','info':'<span class=fail>".self::$language['state'].':'.self::$language['order_state'][$r['state']]." ".self::$language['inoperable']."</span>'}");}
    if(empty($pay_date)){
        $status = 2; //无账期-》待打款
        $sql="update ".self::$table_pre."order set `state`='".$status."',`pay_date`=null,`contract_number`='".$contract_number."' where `id`=".$id;
    }else{
        $pay_date .=" 23:59:59";
        $status = 3; //有账期-》待发货
        $sql="update ".self::$table_pre."order set `state`='".$status."',`pay_date`='".$pay_date."' ,`contract_number`='".$contract_number."' where `id`=".$id;
    }


    if($pdo->exec($sql)){
        $r['state']=$status;
        //self::decrease_goods_quantity($pdo,self::$table_pre,$r);
        /*if(self::$config['agency']){
            require('./program/agency/agency.class.php');
            $agency=new agency($pdo);
            $agency->order_complete_pay($pdo,$r['id']);
        }*/

        if ($status == '3'){
            //发短信:通知供应商发货
            $sql="select `phone` from ".$pdo->index_pre."user where `id`='".$r['supplier']."'";
            $r2=$pdo->query($sql,2)->fetch(2);
            if ($r2){
                $param = array (
                    'order_id' => $r['out_id'],
                );
                $msg = send_notice_sms(self::$config, $pdo, $r2["phone"], 'order_pending_send', $param);
            }
        }

        exit("{'state':'success','info':'<span class=success>".self::$language['success']."</span>'}");
    }else{
        exit("{'state':'fail','info':'<span class=fail>".self::$language['fail']."</span>'}");
    }
    break;

    case 'pending_pay'://================================================================================================================

    $number =@$_GET['number'];
    $date =@$_GET['date'];
    $pay_type =@$_GET['type'];
    $pay_picture =@$_GET['picture'];

    if(empty($pay_type)){exit("{'state':'fail','info':'<span class=fail>err</span>'}");}
    //支持三种状态: 待采购商打款, 账期中, 逾期中, 待打款至供应商
    if($r['state']<>2 && $r['state']<>9 && $r['state']<>10 && $r['state']<>11){exit("{'state':'fail','info':'<span class=fail>".self::$language['state'].':'.self::$language['order_state'][$r['state']]." ".self::$language['inoperable']."</span>'}");}

    //process picture
    if(!is_file('./temp/'.$pay_picture)){exit("{'state':'fail','info':'<span class=fail>回执照片".self::$language['upload_failed']."</span>','id':'icon'}");}
    $path='./program/mall/img/'.$pay_picture;
    get_date_dir('./program/mall/img/');
    get_date_dir('./program/mall/img_thumb/');
    if(safe_rename('./temp/'.$pay_picture,$path)==false){
        exit("{'state':'fail','info':'<span class=fail>回执照片".self::$language['upload_failed']."</span>'}");
    }
    $image=new image();
    $image->thumb($path,'./program/mall/img_thumb/'.$_POST['icon'],self::$config['icon_thumb']['width'],self::$config['icon_thumb']['height']);
    if(self::$config['program']['imageMark']){$image->addMark($path);}

    $status = "3";
    if($r['state'] == 9 || $r['state'] == 10){
        $status = "11"; // 待打款至供应商
    }else if($r['state'] == 11){
        //$status = "12"; // 待供应商确认收款
        $status = "13"; // 订单完成
    }

    if($pay_type == 1){
        if($r['state'] == 11){
            $pay_type = 3;
        }
        //转账
        $sql1="insert into jzdc_mall_order_pay (`order_id`, `pay_type`, `number`,`picture`,`pay_time`,`create_time`) values ('".$id."', '".$pay_type."','".$number."','".$pay_picture."','".$date."','".date("Y-m-d")."') ";
    }else{
        if($r['state'] == 11){
            $pay_type = 4;
        }
        //商票
        $sql1="insert into jzdc_mall_order_pay (`order_id`, `pay_type`, `number`,`picture`,`accept_time`,`create_time`) values ('".$id."', '".$pay_type."','".$number."','".$pay_picture."','".$date."','".date("Y-m-d")."') ";
    }

    $sql="update ".self::$table_pre."order set `state`='".$status."' where `id`=".$id;

    if($pdo->exec($sql)){
        $r['state']=$status;
        $pdo->exec($sql1);

        if ($status == '13'){
            //交易完成
            $sql="select `username` from ".self::$table_pre."shop where `id`=".$r['shop_id'];
            $r2=$pdo->query($sql,2)->fetch(2);
            $seller=$r2['username'];

            $reason=str_replace('{order_id}','<a href=./index.php?jzdc=mall.order_admin&search='.$r['out_id'].' target=_blank>'.$r['out_id'].'</a>',self::$language['add_order_money_template']);
            $reason=str_replace('{sum_money}',$r['actual_money'],$reason);
            self::operation_shop_finance(self::$language,$pdo,self::$table_pre,$r['shop_id'],$r['actual_money'],9,$reason);
            self::add_goods_sold($pdo,self::$table_pre,$r);
            self::update_shop_buyer($pdo,self::$table_pre,$r);

        }else if ($status == '3'){
            //发短信:通知供应商发货
            $sql="select `phone` from ".$pdo->index_pre."user where `id`='".$r['supplier']."'";
            $r2=$pdo->query($sql,2)->fetch(2);
            if ($r2){
                $param = array (
                    'order_id' => $r['out_id'],
                );
                $msg = send_notice_sms(self::$config, $pdo, $r2["phone"], 'order_pending_send', $param);
            }
        }

        exit("{'state':'success','info':'<span class=success>".self::$language['success']."</span>'}");
    }else{
        exit("{'state':'fail','info':'<span class=fail>".self::$language['fail']."</span>'}");
    }

    break;

    case 'question_fix'://================================================================================================================【设为待质检】
        //问题确认中 ->待质检

        if($r['state']<>8){exit("{'state':'fail','info':'<span class=fail>".self::$language['state'].':'.self::$language['order_state'][$r['state']]." ".self::$language['inoperable']."</span>'}");}

        $sql="update ".self::$table_pre."order set `state`='7' where `id`=".$id;

        if($pdo->exec($sql)){
            $r['state']="7";

            exit("{'state':'success','info':'<span class=success>".self::$language['success']."</span>'}");
        }else{
            exit("{'state':'fail','info':'<span class=fail>".self::$language['fail']."</span>'}");
        }
        break;

    case 'cancel'://================================================================================================================【取消订单】
        //在待核价和待签约、待付款状态下，运营人员可以取消订单。

        if($r['state']>2){exit("{'state':'fail','info':'<span class=fail>".self::$language['state'].':'.self::$language['order_state'][$r['state']]." ".self::$language['inoperable']."</span>'}");}

        $sql="update ".self::$table_pre."order set `state`='4',`cancel_reason`='".safe_str(@$_GET['cancel_reason'])."' where `id`=".$id;

        if($pdo->exec($sql)){
            $r['state']="4";

            //发短信通知采购商
            $sql="select `phone` from ".$pdo->index_pre."user where `username`='".$r['buyer']."'";
            $r2=$pdo->query($sql,2)->fetch(2);
            if ($r2){
                $param = array (
                    'order_id' => $r['out_id'],
                    );
                $msg = send_notice_sms(self::$config, $pdo, $r2["phone"], 'order_cancel', $param);
            }

            exit("{'state':'success','info':'<span class=success>".self::$language['success']."</span>'}");
        }else{
            exit("{'state':'fail','info':'<span class=fail>".self::$language['fail']."</span>'}");
        }
        break;
    case 'export'://================================================================================================================【导出订单】

        header("Content-Type: text/csv");
        header("Content-Disposition: attachment; filename=order_".date("Y-m-d H_i_s").".csv");
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');

        $list=self::$language['order_in_out_field']."\r\n";

        $sql="select 
            FROM_UNIXTIME(add_time) as add_time,
            out_id,
            goods_names,
            state,
            (select real_name as name from jzdc_index_user where `username`=buyer limit 1) as buyer,
            (select real_name as name from jzdc_index_user where `id`=supplier limit 1) as supplier,
            contract_number,
            sum_money,
            goods_count,
            buyer_remark,
            buyer_order_code,
            pay_date
             from jzdc_mall_order od where `shop_id`=1 and `seller_del`=0;";

        $r=$pdo->query($sql,2);
        foreach($r as $v){
            $v=de_safe_str($v);
            $list.=str_replace(',',' ',trim($v['add_time']))."\t,";
            $list.=str_replace(',',' ',trim($v['out_id']))."\t,";
            $list.=str_replace(',',' ',trim($v['goods_names']))."\t,";
            $list.=self::$language['order_state'][$v['state']]."\t,";
            $list.=str_replace(',',' ',trim($v['buyer']))."\t,";
            $list.=str_replace(',',' ',trim($v['supplier']))."\t,";
            $list.=str_replace(',',' ',trim($v['contract_number']))."\t,";
            $list.=str_replace(',',' ',trim($v['sum_money']))."\t,";
            $list.=floor($v['goods_count'])."\t,";
            $list.=str_replace(',',' ',trim($v['buyer_remark']))."\t,";
            $list.=str_replace(',',' ',trim($v['buyer_order_code']))."\t,";
            if (!empty($v['pay_date'])){
                $list.=str_replace(',',' ',trim($v['pay_date']))."";
            }else{
                $list.="无账期";
            }

            $list.="\r\n";
        }

        $list=iconv("UTF-8",self::$config['other']['export_csv_charset']."//IGNORE",$list);
        echo $list;
        break;


}