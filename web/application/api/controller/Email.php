<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/6/26
 * Time: 9:35
 */
namespace app\api\controller;

use app\common\model\EmailCode;
use app\common\model\IndexUser;
use think\Request;



class Email extends Base{

   //发送
    public function sendNew(Request $request){
        //发送邮件
        $email = $request->post('email','');
        $channel = $request->post('channel',0,'intval');
        $auth = $this->auth();
        if($auth){
            return $auth;
        }

        if(!$email){
            return ['status'=>1,'data'=>[],'msg'=>'邮箱不能为空'];
        }
        if(!checkEmail($email)){
            return ['status'=>1,'data'=>[],'msg'=>'无效的邮箱'];
        }
        $userModel = new IndexUser();
        $userInfo = $userModel->where(['email'=>$email])->find();
        if($userInfo){
            return ['status'=>1,'data'=>[],'msg'=>'邮箱已经存在'];
        }
        $type = $channel >0 ? EmailCode::TYPE_EMAIL_NEW : EmailCode::TYPE_EMAIL_INIT;
        return $this->sendEmail($email,$type);
    }

    //发送
    public function send(Request $request){
        //发送邮件
        $auth = $this->auth();
        if($auth){
            return $auth;
        }

        $userModel = new IndexUser();
        $userInfo = $userModel->getInfoById($this->userId);
        if(!$userInfo || !$userInfo->email){
            return ['status'=>1,'data'=>[],'msg'=>'数据异常'];
        }
        return $this->sendEmail($userInfo->email,EmailCode::TYPE_EMAIL_OLD);
    }


    protected function sendEmail($email,$type){
        //发送邮件
//        $code = getVerificationCode();
        $code = '6666';
        //写入数据库
        $codeModel = new EmailCode();
        $time = time();
        $result = $codeModel->save(['email'=>$email,'type'=>$type,'create_time'=>$time,'expire_time'=>$time+300]);
        if($result == true){
            //发送邮件
            $subject='集众电采邮箱验证码';
            $content='验证码为：'.$code.',五分钟内有效。';
//            $result = SendMail($email,$subject,$content);
            $result = true;
            if($result == true){
                return ['status'=>0,'data'=>[],'msg'=>'邮件发送成功'];
            }
        }
        return ['status'=>1,'data'=>[],'msg'=>'邮件发送失败'];
    }

    public function valid(Request $request){
        $code = $request->post('code');
        //发送邮件
        $auth = $this->auth();
        if($auth){
            return $auth;
        }
        $model = new IndexUser();
        $userInfo = $model->getInfoById($this->userId);
        if (!$userInfo) {
            return ['status' => 1, 'data' => [], 'msg' => '数据异常'];
        }
        //验证邮箱
        $codeModel = new EmailCode();
        $codeRow = $codeModel->where(['email' => $userInfo->email, 'type' => EmailCode::TYPE_EMAIL_OLD])->order('id', 'desc')->find();
        if (!$codeRow || $codeRow['code'] != $code) {
            return ['status' => 1, 'data' => [], 'msg' => '邮箱验证码错误'];
        }
        if ($codeRow['expire_time'] < time()) {
            return ['status' => 1, 'data' => [], 'msg' => '邮箱验证已过期'];
        }
        return ['status'=>0,'data'=>[],'msg'=>'验证成功'];
    }


}