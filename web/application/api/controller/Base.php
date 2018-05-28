<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/5/17
 * Time: 11:59
 */
namespace app\api\controller;

use app\common\model\IndexUser;
use Firebase\JWT\JWT;

class Base
{
    protected $userId = 0;
    protected $groupId = 0;

    public function __construct()
    {



    }

    /**
     * @desc 执行需要登录
     */
    public function auth(){
        //获取http header变量cookie
        $token = cookie('_token');
        if(!$token){
            return ['status'=>-2,'data'=>[],'msg'=>'数据错误'];
        }
        //解析token
        $key = config('jzdc_token_key');
        $data = JWT::decode($token,$key,['HS256']);
        //
        if(!$data->id  || !$data->group || !$data->time || !$data->expire ){
            return ['status'=>-2,'data'=>[],'msg'=>'用户未登录'];
        }

        //更新用户数据
        $model = new IndexUser();
        $row = $model->where(['id'=>$data->id])->field(['id','group','state'])->find();
        if(!$row){
            return ['status'=>-2,'data'=>[],'msg'=>'用户不存在'];
        }

        if($row->group != $data->group || $row->state != 1){
            return ['status'=>-2,'data'=>[],'msg'=>'需要重新登录'];
        }

        $this->userId = $data->id;
        $this->groupId = $data->group;
    }


    /**
     * @desc 执行不需要登录操作
     */
    public function visitor(){


    }

}