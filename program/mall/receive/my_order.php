<?php
$act=@$_GET['act'];

//==================================================================================================================================【删除评论】
if($act=='del_comment'){
	$comment_id=intval(@$_GET['comment_id']);
	$sql="delete from ".self::$table_pre."comment where `id`=".$comment_id." and `buyer`='".$_SESSION['jzdc']['username']."'";
	file_put_contents('t.txt',$sql);
	$r=$pdo->exec($sql);
	exit();
}
		
$id=intval(@$_GET['id']);
if($act!='export' && $id==0){exit("{'state':'fail','info':'id err'}");}

//==================================================================================================================================【查看物流】
if($act=='go_express'){
	$sql="select `url` from ".self::$table_pre."express where `id`='".$id."'";
	$r=$pdo->query($sql,2)->fetch(2);
	if($r['url']==''){exit(sef::$language['query_url'].self::$language['is_null']);}
	header("location:".$r['url'].@$_GET['code']);exit;	
}


if($id!=0){
	$sql="select * from ".self::$table_pre."order where `id`='".$id."' and (`buyer`='".$_SESSION['jzdc']['username']."' or supplier = '".$_SESSION['jzdc']['id']."') limit 0,1";
	$r=$pdo->query($sql,2)->fetch(2);
	if($r['id']==''){exit("{'state':'fail','info':'id err'}");}	
}
switch ($act){

    case 'confirm_receipt':
        if($r['state']!=6 ){exit("{'state':'fail','info':'<span class=fail>".self::$language['state'].':'.self::$language['order_state'][$r['state']]." ".self::$language['forbidden_del']."</span>'}");}

        if($r['supplier'] == $_SESSION["jzdc"]["id"]){
            exit("{'state':'fail','info':'<span class=fail>非法操作</span>'}");
        }
        $sql="update ".self::$table_pre."order set `state`='7' where `id`=".$id;
        if($pdo->exec($sql)){
            exit("{'state':'success','info':'<span class=success>".self::$language['success']."</span>'}");
        }else{
            exit("{'state':'fail','info':'<span class=fail>".self::$language['fail']."</span>'}");
        }
        break;

    case 'confirm_check':
        if($r['state']!=7 ){exit("{'state':'fail','info':'<span class=fail>".self::$language['state'].':'.self::$language['order_state'][$r['state']]." ".self::$language['forbidden_del']."</span>'}");}

        if($r['supplier'] == $_SESSION["jzdc"]["id"]){
            exit("{'state':'fail','info':'<span class=fail>非法操作</span>'}");
        }

        if(empty($r['pay_date'])){
            $sql1="select * from ".self::$table_pre."order_pay where `order_id`='".$id."' limit 1";
            $pay_rec=$pdo->query($sql1,2)->fetch(2);
            if($pay_rec['id']==''){exit("{'state':'fail','info':'请联系平台运营人员'}");}

            //没有账期 -->待打款至供应商
            $sql="update ".self::$table_pre."order set `state`='11' where `id`=".$id;
        }else{
            //有账期 -->账期中
            $sql="update ".self::$table_pre."order set `state`='9' where `id`=".$id;
        }

        if($pdo->exec($sql)){
            exit("{'state':'success','info':'<span class=success>".self::$language['success']."</span>'}");
        }else{
            exit("{'state':'fail','info':'<span class=fail>".self::$language['fail']."</span>'}");
        }
        break;
    case 'check_fail':
        if($r['state']!=7 ){exit("{'state':'fail','info':'<span class=fail>".self::$language['state'].':'.self::$language['order_state'][$r['state']]." ".self::$language['forbidden_del']."</span>'}");}
        if($r['supplier'] == $_SESSION["jzdc"]["id"]){
            exit("{'state':'fail','info':'<span class=fail>非法操作</span>'}");
        }
        //待质检 ->问题确认中
        $sql="update ".self::$table_pre."order set `state`='8' where `id`=".$id;
        if($pdo->exec($sql)){
            exit("{'state':'success','info':'<span class=success>".self::$language['success']."</span>'}");
        }else{
            exit("{'state':'fail','info':'<span class=fail>".self::$language['fail']."</span>'}");
        }
        break;
    case 'confirm_send': //供应商确认发货
        if($r['state']!=3 ){exit("{'state':'fail','info':'<span class=fail>".self::$language['state'].':'.self::$language['order_state'][$r['state']]." ".self::$language['forbidden_del']."</span>'}");}

        //检查是否供应商身份
        if($r['supplier'] <> $_SESSION["jzdc"]["id"]){
            exit("{'state':'fail','info':'<span class=fail>非法操作</span>'}");
        }

        $express=safe_str(@$_GET['express']);
        $express_code=safe_str(@$_GET['express_code']);

        $sql="update ".self::$table_pre."order set `state`='6',`express_name`='".$express."',`express_code`='".$express_code."',`send_time`='".time()."' where `id`=".$id;

        if($pdo->exec($sql)){
            //短信通知采购已发货
            $sql="select `real_name` from ".$pdo->index_pre."user where `id`='".$r['supplier']."'";
            $r2=$pdo->query($sql,2)->fetch(2);

            $sql3="select `phone` from ".$pdo->index_pre."user where `username`='".$r['buyer']."'";
            $r3=$pdo->query($sql3,2)->fetch(2);
            if ($r2 && $r3){
                if (!empty($express) && !empty($express_code)){
                    $param = array (
                        'order_id' => $r['out_id'],
                        'supplier' => $r2['real_name'],
                        'express_name' => $express,
                        'express_code' => $express_code,
                    );
                    $tpl_id='order_send1';
                }else{
                    $param = array (
                        'order_id' => $r['out_id'],
                        'supplier' => $r2['real_name'],
                    );
                    $tpl_id='order_send2';
                }
                $msg = send_notice_sms(self::$config, $pdo, $r3["phone"], $tpl_id, $param);
            }

            exit("{'state':'success','info':'<span class=success>".self::$language['success']."</span>','express_code':'".$express_code."'}");
        }else{
            exit("{'state':'fail','info':'<span class=fail>".self::$language['fail']."</span>'}");
        }
        break;
    case 'receive_money': //供应商确认收款
        if($r['state']!=12 ){exit("{'state':'fail','info':'<span class=fail>".self::$language['state'].':'.self::$language['order_state'][$r['state']]." ".self::$language['forbidden_del']."</span>'}");}

        //检查是否供应商身份
        if($r['supplier'] <> $_SESSION["jzdc"]["id"]){
            exit("{'state':'fail','info':'<span class=fail>非法操作</span>'}");
        }

        $sql="update ".self::$table_pre."order set `state`='13' where `id`=".$id;

        if($pdo->exec($sql)){

            exit("{'state':'success','info':'<span class=success>".self::$language['success']."</span>','express_code':'".$express_code."'}");
        }else{
            exit("{'state':'fail','info':'<span class=fail>".self::$language['fail']."</span>'}");
        }
        break;

    case 'export'://================================================================================================================【导出订单】

        /*header("Content-Type: text/csv");
        header("Content-Disposition: attachment; filename=order_".date("Y-m-d H_i_s").".csv");
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        */

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
             from jzdc_mall_order od where ((`buyer`='".$_SESSION['jzdc']['username']."' and `buyer`!='' and `buyer_del`=0) or supplier = '".$_SESSION['jzdc']['id']."') and `seller_del`=0;";

        //exit("{'state':'fail','info':'<span class=fail>".$sql."</span>'}");
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

        $path = "./temp/order_".$_SESSION['jzdc']['id']."_".date("Y-m-d H_i_s").".csv";
        $fp = fopen($path,'a');
        fwrite($fp, $list);
        fclose($fp);

        $sql="select email from jzdc_index_user where id=".$_SESSION['jzdc']['id'].";";
        $r=$pdo->query($sql,2)->fetch(2);
        //echo $r['email'];

        $content = "您刚刚在集众电采平台成功导出个人订单,请查看附件,<br> 导出时间:".date("Y-m-d H:i:s");
        $result = email($config,$language,$pdo,'jzdc',$r['email'],"集众电采个人订单(".date("Y-m-d H:i:s").")",$content, $path);
        unlink($path);

        if($result){
            exit("{'state':'success','info':'<span class=success>导出订单成功,请查看邮件</span>'}");
        }else{
            exit("{'state':'fail','info':'<span class=fail>导出订单失败,请重试</span>'}");
        }
        break;

		
}