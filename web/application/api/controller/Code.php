<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/5/21
 * Time: 13:49
 */
namespace app\api\controller;


use app\common\model\IndexUser;
use app\extend\yunpian;
use think\Request;

class Code{

    /**
     * @desc 发送注册短信验证码
     * @return array
     */
    public function registerSend(Request $request){
        $phone = $request->post('phone','');
        if(!$phone){
            return ['status'=>1,'data'=>[],'msg'=>'手机号不能为空'];
        }

        //检查手机号是否存在
        $model = new IndexUser();
        $user = $model->getUserByPhone($phone);
        if($user){
            return ['status'=>1,'data'=>[],'msg'=>'用户已存在'];
        }

        $code = getVerificationCode();

        $param['code'] = $code;
        $yunpian = new yunpian();
        //发送短信验证码
        $result = $yunpian->send($phone,$param,yunpian::TPL_CERT_SUC);
        if($result){
            //更新短信验证码
            $codeModel = new \app\common\model\Code();
            $time = time();
            $data = ['phone'=>$phone,'type'=>\app\common\model\Code::TYPE_PHONE_REGISTER,'code'=>$code,'create_time'=>$time,'expire_time'=>$time+300];
            $result = $codeModel->save($data);
            if($result){
                return ['status'=>0,'data'=>[],'msg'=>'已成功发送验证码'];
            }
        }

        return ['status'=>1,'data'=>[],'msg'=>'发送短信验证码失败'];
    }

    /**
     * @desc 验证码注册
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function registerValid(Request $request){
        $phone = $request->post('phone','');
        $code = $request->post('code','');
        if(!$phone){
            return ['status'=>1,'data'=>[],'msg'=>'手机号不能为空'];
        }
        if(!$code){
            return ['status'=>1,'data'=>[],'msg'=>'验证码不能为空'];
        }

        $codeModel = new \app\common\model\Code();
        $codeRow = $codeModel->where(['phone'=>$phone,'type'=>\app\common\model\Code::TYPE_PHONE_REGISTER])->order('id','desc')->find();
        if(!$codeRow || $codeRow['code']!= $code){
            return ['status'=>1,'data'=>[],'msg'=>'短信验证码错误'];
        }
        return ['status'=>0,'data'=>[],'msg'=>'验证成功'];
    }




}

