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
        startSession();
        $id = session_id();
        $src = captcha_src($id);
        $http = config('jzdc_domain');
        return ['status'=>0,'data'=>['src'=>$http.$src,'id'=>$id],'msg'=>''];
    }
}