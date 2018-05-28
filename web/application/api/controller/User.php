<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/5/21
 * Time: 16:41
 */
namespace app\api\controller;

class User extends Base {

    public function __construct()
    {
        $this->auth();
    }

    /**
     * @desc 返回用户所在组
     * @return array
     */
    public function getGroup(){
        return ['status'=>0,'data'=>['groupId'=>$this->groupId],'msg'=>''];
    }


}