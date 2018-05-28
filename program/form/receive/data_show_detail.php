<?php
$table_id=intval(@$_GET['table_id']);
if($table_id==0){exit('table_id err');}
$id=intval(@$_GET['id']);
if($id==0){exit('id err');}

$act=@$_GET['act'];

if($act=='count'){
	$sql="select `name`,`description`,`edit_power` from ".self::$table_pre."table where `id`=$table_id";
	$r=$pdo->query($sql,2)->fetch(2);
	$table_name=$r['name'];
	if($table_name==''){exit('table_id err');}
	$sql="update ".self::$table_pre.$table_name." set `visit`=`visit`+1 where `id`=".$id;
	$pdo->exec($sql);
}elseif ($act=='refuse'){

    if($table_id<>37){
        exit('table_id err');
    }
    if ($_SESSION['jzdc']['group_id']<>3){
        exit("err");
    }

    $sql="select * from ".self::$table_pre."user_cert  where `id`=".$id;
    $r=$pdo->query($sql,2)->fetch(2);
    if (!$r){
        exit("err");
    }
    if($r['status'] <> 1){
        exit("{'state':'fail','info':'该认证非待审核状态,不能审核'}");
    }

    $editor = $_SESSION['jzdc']['id'];
    $edit_time=time();
    $reason=safe_str(@$_POST['reason']);

    $sql="update ".self::$table_pre."user_cert set status=3 ,`refuse_reason`='".$reason."', `edit_time`='".$edit_time."',`editor`='".$editor."' where `id`=".$id;
    if($pdo->exec($sql)) {

        $sql="select `phone` from ".$pdo->index_pre."user where `id`='".$r["writer"]."'";
        $r2=$pdo->query($sql,2)->fetch(2);

        if ($r2){
            $msg = send_notice_sms(self::$config, $pdo, $r2["phone"], 'cert_fail');
        }

        exit("{'state':'success','info':'<span class=success>" . self::$language['submit'] . self::$language['success'] . " " . $view . ' <a href="./index.php?jzdc=form.data_show_list&table_id=' . $table_id . '" class="return_button">' . self::$language['return'] . '</a>' . "</span>'}");
    }else{
        exit("{'state':'fail','info':'".self::$language['fail']."'}");
    }

}elseif ($act=='pass'){
    if($table_id<>37){
        exit('table_id err');
    }
    if ($_SESSION['jzdc']['group_id']<>3){
        exit("{'state':'fail','info':'非法访问'}");
    }
    $sql="select * from ".self::$table_pre."user_cert  where `id`=".$id;
    $r=$pdo->query($sql,2)->fetch(2);
    if (!$r){
        exit("err");
    }
    if($r['status'] <> 1){
       exit("{'state':'fail','info':'该认证非待审核状态,不能审核'}");
    }

    if($r['reg_role'] =='采购商'){
        $group_id = 4;
    }else if($r['reg_role'] =='供应商'){
        $group_id = 5;
    }

    //更改身份
    $sql="update jzdc_index_user set `group`=".$group_id.",`real_name`='".$r['company_name']."'  where `id`=".$r['writer'];
    $pdo->exec($sql);

    $editor = $_SESSION['jzdc']['id'];
    $edit_time=time();
    $sql="update ".self::$table_pre."user_cert set status=2 ,`edit_time`='".$edit_time."',`editor`='".$editor."' where `id`=".$id;
    if($pdo->exec($sql)) {

        $sql="select `phone` from ".$pdo->index_pre."user where `id`='".$r["writer"]."'";
        $r2=$pdo->query($sql,2)->fetch(2);
        if ($r2){
            $msg = send_notice_sms(self::$config, $pdo, $r2["phone"], 'cert_suc');
        }

        exit("{'state':'success','info':'<span class=success>" . self::$language['submit'] . self::$language['success'] . " " . $view . ' <a href="./index.php?jzdc=form.data_show_list&table_id=' . $table_id . '" class="return_button">' . self::$language['return'] . '</a>' . "</span>'}");
    }else{
        exit("{'state':'fail','info':'".self::$language['fail']."'}");
    }
}
