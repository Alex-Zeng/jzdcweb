<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/6/26
 * Time: 10:10
 */
namespace app\common\model;

use think\Model;

class EmailCode extends Model{

    const TYPE_EMAIL_INIT = 1;
    const TYPE_EMAIL_OLD = 2;
    const TYPE_EMAIL_NEW = 3;


}