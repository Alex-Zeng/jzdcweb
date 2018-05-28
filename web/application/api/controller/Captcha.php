<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/5/28
 * Time: 11:14
 */
namespace app\api\controller;

use app\common\model\IndexUser;
use think\Request;

class Captcha{


    /**
     * @desc 返回图片验证码
     * @return array
     */
    public function img(){
        $src = captcha_img();
        return ['status'=>0,'data'=>['src'=>$src],'msg'=>''];
    }

    /**
     * @desc 验证图片验证码并验证手机号
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function valid(Request $request){
        $phone = $request->post('phone','');
        $captcha = $request->post('code','');

        if(!$phone){
            return ['status'=>1,'data'=>[],'msg'=>'手机号不能为空'];
        }

        if(!$captcha){
            return ['status'=>1,'data'=>[],'msg'=>'图片验证码不能为空'];
        }

        //验证手机号是否已注册
        $model = new IndexUser();
        $user = $model->getUserByPhone($phone);
        if($user){
            return ['status'=>1,'data'=>[],'msg'=>'手机号已注册'];
        }

        if(!captcha_check($captcha)){
            return ['status'=>1,'data'=>[],'msg'=>'图片验证码错误'];
        }

        return ['status'=>0,'data'=>[],'msg'=>'验证成功'];
    }


}