<?php
/**
 * @desc 返回用户ID||兼容原版本session
 * @return int
 */
function getUserId()
{
    /*
    建立在外包系统兼容性写法
    if(!isset($_SESSION)){
        session_start();
    }
    $jzdc = isset($_SESSION['jzdc']) ? $_SESSION['jzdc'] : [];
    return isset($jzdc['id']) ? $jzdc['id'] : 0;
     */
    return session('?admin_id')?session('admin_id'):0;
}

/**
 * @desc 返回用户昵称
 * @return int|mixed
 */
function getUserName(){
    /*
    建立在外包系统兼容性写法
    $jzdc = isset($_SESSION['jzdc']) ? $_SESSION['jzdc'] : [];
    return isset($jzdc['nickname']) ? $jzdc['nickname'] : 0;
    */
  return session('?nick_name')?session('nick_name'):0;
}

/**
 * @desc 返回用户组角色
 * @return int|mixed
 */
function getGroupId(){
    /*
      建立在外包系统兼容性写法
      //session_start();
      $jzdc = isset($_SESSION['jzdc']) ? $_SESSION['jzdc'] : [];
      return isset($jzdc['group_id']) ? $jzdc['group_id'] : 0;
     */
    return session('?group_id')?session('group_id'):0;
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
function getGoodsMallState($state = -1,$style = 0){
   // [0]=> string(9) "待审核" [1]=> string(12) "审核通过" [2]=> string(12) "审核失败"
    $list = [
        0 => '待审核',
        1 => '审核通过',
        2 => '审核失败'
    ];
    if($style == 1){
        $list = [
            0 => '<span style="color:#0069d9 !important">待审核</span>',
            1 => '<span style="color:#28a745!important">审核通过</span> ',
            2 => '<span style="color:#dc3545 !important">审核失败</span>'
        ];
    }

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

/**
 * @desc 根据层级返回分类列表数据
 * @param int $level
 * @return array
 * @throws \think\db\exception\DataNotFoundException
 * @throws \think\db\exception\ModelNotFoundException
 * @throws \think\exception\DbException
 */
function getProductCategory($level = 2){
    $model = new \app\common\model\SmProductCategory();
    $rows = $model->where(['parent_id'=>0])->field(['id','name','parent_id'])->select();
    $list = [];
    foreach ($rows as $row){
        $list[] = ['id'=>$row->id,'name'=>$row->name,'parent'=>$row->parent_id,'level'=>1];
        if($level == 2 || $level == 3){
            $rows2 = $model->where(['parent_id'=>$row->id])->field(['id','name','parent_id'])->select();
            foreach ($rows2 as $row2){
                $list[] = ['id'=>$row2->id,'name'=>$row2->name,'parent'=>$row2->parent_id,'level'=>2];
                if($level == 3){
                    $rows3 = $model->where(['parent_id'=>$row2->id])->field(['id','name','parent_id'])->select();
                    foreach($rows3 as $row3){
                        $list[] = ['id'=>$row3->id,'name'=>$row3->name,'parent'=>$row3->parent_id,'level'=>3];
                    }
                }
            }
        }
    }
    return $list;
}


/**
 * @desc 返回订单状态
 * @param int $state
 * @return mixed|string
 */
function getOrderState($state = -1,$style = 0){
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
    if($style == 1){
        $list = [
            0 => '<span style="color:#0069d9 !important">待核价</span>',
            1 => '<span style="color:#0069d9 !important">待签约</span>',
            2 => '<span style="color:#0069d9 !important">待采购商打款</span>',
            3 => '<span style="color:#0069d9 !important">待发货</span>',
            4 => '<span style="color:#dc3545 !important">订单关闭</span>',
            6 => '<span style="color:#0069d9 !important">待收货</span>',
            7 => '<span style="color:#0069d9 !important">待质检</span>',
            8 => '<span style="color:#0069d9 !important">问题确认中</span>',
            9 => '<span style="color:#0069d9 !important">账期中</span>',
            10 => '<span style="color:#0069d9 !important">逾期中</span>',
            11 => '<span style="color:#0069d9 !important">待打款至供应商</span>',
            13 => '<span style="color:#28a745!important">交易完成</span>'
        ];
    }
    return isset($list[$state]) ? $list[$state] : '';
}

/**
 * @desc 返回企业认证状态
 * @param int $status
 * @return mixed|string
 */
function getCertificationStatus($status = -1){
    $list = [
        1 => '<span style="color:#0069d9 !important">待审核</span>',
        2 => '<span style="color:#28a745!important">已通过</span>',
        3 => '<span style="color:#dc3545 !important">已拒绝</span>'
    ];
    return isset($list[$status]) ? $list[$status] : '';
}

/**
 * @param int $typeId
 * @return array
 * @throws \think\db\exception\DataNotFoundException
 * @throws \think\db\exception\ModelNotFoundException
 * @throws \think\exception\DbException
 */
function getTypeLevel($typeId = 0){
    $model = new \app\common\model\MallType();
    $row = $model->where(['id'=>$typeId])->field(['id','name','parent'])->find();
    $list = [];
    if($row){
        $list[] = $row->name;
        if($row->parent > 0){
            $row2 = $model->where(['id'=>$row->parent])->field(['id','name','parent'])->find();
            $list[] = $row2->name;
            if($row2->parent > 0){
                $row3 = $model->where(['id'=>$row2->parent])->field(['id','name','parent'])->find();
                $list[] = $row3->name;
            }
        }
    }

    return $list;
}

/**
 * @desc
 * @param int $categoryId
 * @return array
 * @throws \think\db\exception\DataNotFoundException
 * @throws \think\db\exception\ModelNotFoundException
 * @throws \think\exception\DbException
 */
function getCategoryLevel($categoryId = 0){
    $model = new \app\common\model\SmProductCategory();
    $row = $model->where(['id'=>$categoryId])->field(['id','name','parent_id'])->find();
    $list = [];
    if($row){
        $list[] = $row->name;
        if($row->parent_id > 0){
            $row2 = $model->where(['id'=>$row->parent_id])->field(['id','name','parent_id'])->find();
            $list[] = $row2->name;
            if($row2->parent_id > 0){
                $row3 = $model->where(['id'=>$row2->parent_id])->field(['id','name','parent_id'])->find();
                $list[] = $row3->name;
            }
        }
    }
    return $list;
}


/**
 * @desc 返回商品颜色
 * @return false|PDOStatement|string|\think\Collection
 * @throws \think\db\exception\DataNotFoundException
 * @throws \think\db\exception\ModelNotFoundException
 * @throws \think\exception\DbException
 */
function getColorList(){
    $model = new \app\common\model\MallColor();
    $rows = $model->field(['id','name'])->select();

    foreach ($rows as &$row){
        $row['path'] = \app\common\model\MallColor::getFormatImg($row->id);
    }
    return $rows;
}

function getServiceList($type = -1){
    $list = [
        1 => '保险',
        2 => '法务',
        3 => '金融',
        4 => '售后',
        5 => '知识产权',
        6 => '自动'
    ];return isset($list[$type]) ? $list[$type] : '';
}

/**
 * [allLateToString 将返回结果的所有值类型转换为字符串]
 * @param  [array] $data [数组]
 * @return [array]       [数组]
 */
function allLateToString($data){
  foreach ($data as $key => $val) {
    if(is_array($val)){
      if(count($val)>0){
        $data[$key] = allLateToString($val);
      }
    }else{
      if(is_numeric($val)){
        $data[$key] = (string)$val;
      }else if(empty($val)){
        $data[$key] = '';
      }
    }
  }
  return $data;
}

/**
 * [getProductAuditState 返回状态描述]
 * @param  [int] $id    [状态值]
 * @return [string]     [状态描述]
 */
function getProductAuditState($id){
    return model('SmProduct')->getAuditState($id);
}