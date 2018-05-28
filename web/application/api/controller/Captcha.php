<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/5/28
 * Time: 11:14
 */
namespace app\api\controller;

class Captcha{


    /**
     * @desc 返回图片验证码
     * @return array
     */
    public function img(){
        echo'方法方法付付付付付付付付付';
        exit;
//        $src = captcha_img();
//        return ['status'=>0,'data'=>['src'=>$src],'msg'=>''];
    }


}