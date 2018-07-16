<?php
namespace app\admin\model;

use think\Model;
use app\common\model\IndexGroup;

class IndexUser extends Model
{
	/**
     * [getUserByUsername 通过用户名查询用户信息]
     * @param  [type] $username [description]
     * @return [type]           [description]
     */
    public function getUserByUsername($username){
        return $this->field('state,password,id,group,nickname')->where(['username'=>$username,'group'=>['in',IndexGroup::GROUP_ADMIN.','.IndexGroup::GROUP_OPERATION]])->find();
    }
}