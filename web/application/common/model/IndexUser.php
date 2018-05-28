<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/5/21
 * Time: 10:02
 */
namespace app\common\model;

use think\Model;

class IndexUser extends Model{


    /**
     * @desc 手机号查找用户
     * @param $phone
     * @return array|false|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getUserByPhone($phone){
        $row = $this->where(['phone'=>$phone])->find();
        return $row;
    }



}