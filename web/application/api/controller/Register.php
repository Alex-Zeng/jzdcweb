<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/5/21
 * Time: 9:40
 */
namespace app\api\controller;

use app\common\model\IndexUser;
use think\Request;

class Register{

    /**
     * @desc 手机号注册
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function phone(Request $request){
        $phone = $request->post('phone','');
        $code = $request->post('code','');
        $username = $request->post('username','');

        //判断手机号
        if(!$phone){
            return  ['status'=>1,'data'=>[],'msg'=>'手机号不能为空'];
        }
        if(!$code){
            return ['status'=>1,'data'=>[],'msg'=>'短信验证码不能为空'];
        }
        if(!$username){
            return ['status'=>1,'data'=>[],'msg' => '用户名不能为空'];
        }

        //检查账号是否已注册
        $model = new IndexUser();
        $user = $model->getUserByPhone($phone);
        if($user){
            return ['status'=>1,'data'=>[],'msg'=>'手机号已注册'];
        }
        //验证短信
        $codeModel = new \app\common\model\Code();
        $codeRow = $codeModel->where(['phone'=>$phone,'type'=>\app\common\model\Code::TYPE_PHONE_REGISTER])->order('id','desc')->find();
        if(!$codeRow || $codeRow['code']!= $code){
            return ['status'=>1,'data'=>[],'msg'=>'短信验证码错误'];
        }
        //注册账户
        $data = [
            'username' => $username,
            'phone' => $phone,
            'reg_time' => time(),
            'reg_ip' => get_ip(),
            'group' => 6,
        ];
        $userResult = $model->save($data);

        //返回token
        if($userResult){
            $data = [];
            //生成token
            $key = config('jzdc_token_key');
            $token = [
                "id" => $userResult->id,
                "group" => 6,
                "time" => time(),
                "expire" => time() + 5*3600   //过期时间
            ];
            $jwt = JWT::encode($token,$key);
            $data['token']= $jwt;
            return ['status'=>0,'data'=>$data,'msg'=>''];

        }
        return ['status'=>1,'data'=>[],'msg'=>'注册失败'];
    }


}