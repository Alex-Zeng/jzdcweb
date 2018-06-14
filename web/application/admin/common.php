<?php
/**
 * @desc 返回用户ID||兼容原版本session
 * @return int
 */
function getUserId()
{
    if(!isset($_SESSION)){
        session_start();
    }
    $jzdc = isset($_SESSION['jzdc']) ? $_SESSION['jzdc'] : [];
    return isset($jzdc['id']) ? $jzdc['id'] : 0;
}

/**
 * @desc 返回用户昵称
 * @return int|mixed
 */
function getUserName(){
    $jzdc = isset($_SESSION['jzdc']) ? $_SESSION['jzdc'] : [];
    return isset($jzdc['nickname']) ? $jzdc['nickname'] : 0;
}

/**
 * @desc 返回用户组角色
 * @return int|mixed
 */
function getGroupId(){
    //session_start();
    $jzdc = isset($_SESSION['jzdc']) ? $_SESSION['jzdc'] : [];
    return isset($jzdc['group_id']) ? $jzdc['group_id'] : 0;
}

/**
 * @desc 返回设备
 * @param int $type
 * @return array|mixed
 */
function getDeviceType($type = -1){
    $list = [
       1 => 'PC端',
       2 => 'APP端',
       3 => '微信端'
    ];
    return isset($list[$type]) ? $list[$type] : '';
}

/**
 * @desc 返回商品审核状态
 * @param int $state
 * @return mixed|string
 */
function getGoodsMallState($state = -1){
   // [0]=> string(9) "待审核" [1]=> string(12) "审核通过" [2]=> string(12) "审核失败"
    $list = [
        0 => '待审核',
        1 => '审核通过',
        2 => '审核失败'
    ];
    return isset($list[$state]) ? $list[$state] : '';
}

/**
 * @desc 返回供应商列表
 * @return false|PDOStatement|string|\think\Collection
 * @throws \think\db\exception\DataNotFoundException
 * @throws \think\db\exception\ModelNotFoundException
 * @throws \think\exception\DbException
 */
function getSupplierList(){
    $user = new \app\common\model\IndexUser();
    $rows = $user->where(['group'=> 5])->field(['id','real_name'])->select();
    return $rows;
}

/**
 * @desc 返回用户组
 * @param int $group
 * @return mixed|string
 */
function getMemberGroup($group = -1){
    $list = [
       2 => '平台管理员',
       3 => '运营人员',
       4 => '采购商',
       5 => '供货商',
       6 => '注册会员'
    ];return isset($list[$group]) ? $list[$group] : '';
}

/**
 * @desc 返回
 * @param int $level
 * @return array
 */
function  getTypeLevelList($level = 2){
    $model = new \app\common\model\MallType();
    $rows = $model->where(['parent'=>0])->field(['id','name','parent'])->select();
    $list = [];
    foreach ($rows as $row){
        $list[] = ['id'=>$row->id,'name'=>$row->name,'parent'=>$row->parent,'level'=>1];
        if($level == 2 || $level == 3){
            $rows2 = $model->where(['parent'=>$row->id])->field(['id','name','parent'])->select();
            foreach ($rows2 as $row2){
                $list[] = ['id'=>$row2->id,'name'=>$row2->name,'parent'=>$row2->parent,'level'=>2];
                if($level == 3){
                    $rows3 = $model->where(['parent'=>$row2->id])->field(['id','name','parent'])->select();
                    foreach($rows3 as $row3){
                        $list[] = ['id'=>$row3->id,'name'=>$row3->name,'parent'=>$row3->parent,'level'=>3];
                    }
                }
            }
        }
    }
   return $list;
}
/*
<select id="state" name="state">
      <option value="" selected="">全部状态</option>
      <option value="0">待核价</option>
      <option value="1">待签约</option>
      <option value="2">待采购商打款</option>
      <option value="3">待发货</option>
      <option value="4">订单关闭</option>
      <option value="6">待收货</option>
      <option value="7">待质检</option>
      <option value="8">问题确认中</option>
      <option value="9">账期中</option>
       <option value="10">逾期中</option>
       <option value="11">待打款至供应商</option>
      <option value="13">交易完成</option>
</select>

*/
/**
 * @desc 返回订单状态
 * @param int $state
 * @return mixed|string
 */
function getOrderState($state = -1){
    $list = [
        0 => '待核价',
        1 => '待签约',
        2 => '待采购商打款',
        3 => '待发货',
        4 => '订单关闭',
        6 => '待收货',
        7 => '待质检',
        8 => '问题确认中',
        9 => '账期中',
        10 => '逾期中',
        11 => '待打款至供应商',
        13 => '交易完成'
    ];
    return isset($list[$state]) ? $list[$state] : '';
}

/**
 * @desc 返回企业认证状态
 * @param int $status
 * @return mixed|string
 */
function getCertificationStatus($status = -1){
    $list = [
        1 => '<span class="bg-primary">待审核</span>',
        2 => '<span class="bg-success">已通过</span>',
        3 => '<span class="bg-danger">已拒绝</span>'
    ];
    return isset($list[$status]) ? $list[$status] : '';
}