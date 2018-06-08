<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/5/29
 * Time: 9:32
 */
namespace app\api\controller;

use app\common\model\IndexUser;
use think\Request;
use Firebase\JWT\JWT;

class Password{

    /**
     * @desc 验证手机号是否存在
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function checkPhone(Request $request){
        $phone = $request->post('phone','');
        if(!$phone){
            return ['status'=>1,'data'=>[],'msg'=>"手机号不能为空"];
        }
        if(!checkPhone($phone)){
            return  ['status'=>1,'data'=>[],'msg'=>'手机号格式不正确'];
        }

        //检查手机号是否存在
        $model = new IndexUser();
        $user = $model->getUserByPhone($phone);
        if(!$user){
            return ['status'=>1,'data'=>[],'msg'=>'手机号不存在'];
        }

        return ['status'=>0,'data'=>[],'msg'=>'手机号存在'];
    }

    /**
     * @desc 验证短信
     * @param Request $reques
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function checkCode(Request $request){
        $phone = $request->post('phone','');
        $code = $request->post('code','');
        if(!$phone){
            return ['status'=>1,'data'=>[],'msg'=>'手机号不能为空'];
        }
        if(!checkPhone($phone)){
            return  ['status'=>1,'data'=>[],'msg'=>'手机号格式不正确'];
        }

        if(!$code){
            return ['status'=>1,'data'=>[],'msg'=>'短信验证码不能为空'];
        }
        $model = new IndexUser();
        $user = $model->getUserByPhone($phone);
        if(!$user){
            return ['status'=>1,'data'=>[],'msg'=>'手机号尚未注册'];
        }
        //验证短信
        $codeModel = new \app\common\model\Code();
        $codeRow = $codeModel->where(['phone'=>$phone,'type'=>\app\common\model\Code::TYPE_PHONE_FORGET_PASSWORD])->order('id','desc')->find();
        if(!$codeRow || $codeRow['code']!= $code){
            return ['status'=>1,'data'=>[],'msg'=>'短信验证码错误'];
        }
        if($codeRow['expire_time'] < time()){
            return ['status'=>1,'data'=>[],'msg'=>'短信验证已过期'];
        }
        return ['status'=>0,'data'=>[],'msg'=>'验证成功'];
    }

    /**
     * @desc 修改密码
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function update(Request $request){
        $phone = $request->post('phone','');
        $code = $request->post('code','');
        $password = $request->post('password','');
        $confirmPassword = $request->post('confirmPassword','');

        if(!$phone){
            return ['status'=>1,'data'=>[],'msg'=>'手机号不能为空'];
        }
        if(!checkPhone($phone)){
            return  ['status'=>1,'data'=>[],'msg'=>'手机号格式不正确'];
        }

        if(!$code){
            return ['status'=>1,'data'=>[],'msg'=>'短信验证码不能为空'];
        }
        if(!$password){
            return ['status'=>1,'data'=>[],'msg'=>'密码不能为空'];
        }
        if(!$confirmPassword){
            return ['status'=>1,'data'=>[],'msg'=>'确认密码不能为空'];
        }

        if($password != $confirmPassword){
            return ['status'=>1,'data'=>[],'msg'=>'两次密码输入不一致'];
        }

        $model = new IndexUser();
        $user = $model->getUserByPhone($phone);
        if(!$user){
            return ['status'=>1,'data'=>[],'msg'=>'手机号尚未注册'];
        }

       // 验证短信
        $codeModel = new \app\common\model\Code();
        $codeRow = $codeModel->where(['phone'=>$phone,'type'=>\app\common\model\Code::TYPE_PHONE_FORGET_PASSWORD])->order('id','desc')->find();
        if(!$codeRow || $codeRow['code']!= $code){
            return ['status'=>1,'data'=>[],'msg'=>'短信验证码错误'];
        }
        if($codeRow['expire_time'] < time()){
            return ['status'=>1,'data'=>[],'msg'=>'短信验证已过期'];
        }

        //更新密码
        $result = $model->save(['password'=>md5($password)],['id'=>$user->id]);
        if($result !== false){
            $data = [];
            //生成token
            $key = config('jzdc_token_key');
            $token = [
                "id" => $user->id,
                "group" => 6,
                "time" => time(),
                "expire" => time() + 5*3600   //过期时间
            ];
            $jwt = JWT::encode($token,$key);
            $data['token']= $jwt;
            return ['status'=>0,'data'=>$data,'msg'=>''];
        }
        return ['status'=>1,'data'=>[],'msg'=>'新密码修改失败'];
    }

}