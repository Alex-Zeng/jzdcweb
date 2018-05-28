<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/5/24
 * Time: 10:40
 */
namespace app\common\model;

use think\Model;

class Notice extends Model{

    public function getUser(){
        return $this->hasOne('IndexUser','create_by');
    }


    public static function beforeInsert($callback, $override = false)
    {
        parent::beforeInsert($callback, $override);

    }



}