<?php
namespace app\common\model;

use think\Model;

class MebUser extends Base{

	protected $table = 'meb_user';

    /**
     * @返回用户数据
     * @param $id
     * @return array|false|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function  getInfoById($id){
        $row = $this->where(['id'=>$id])->find();
        return $row;
    }
}