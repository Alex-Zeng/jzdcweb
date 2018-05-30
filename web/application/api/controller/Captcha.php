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
    public function img(Request $request){
        $id = $request->get('id',0);
        $id = session_id();
        $src = captcha_src($id);
        $http = config('jzdc_domain');
        return ['status'=>0,'data'=>['src'=>$http.$src,'id'=>$id],'msg'=>''];
    }

    public function test(Request $request){
        $captcha = $request->get('code','');

        if(!captcha_check($captcha)){
            return ['status'=>1,'data'=>[],'msg'=>'图片验证码错误'];
        }
        return ['status'=>0];
    }


}