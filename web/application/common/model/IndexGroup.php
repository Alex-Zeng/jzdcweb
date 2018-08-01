<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/6/13
 * Time: 14:17
 */
namespace app\common\model;

use think\Model;

class IndexGroup extends Model{

    const GROUP_ADMIN = 2; //平台管理员
    const GROUP_OPERATION = 3; //运营人员
    const GROUP_BUYER = 4; //采购商
    const GROUP_SUPPLIER = 5; //供应商
    const GROUP_MEMBER = 6; //注册会员


}