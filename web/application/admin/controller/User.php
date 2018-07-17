<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/5/31
 * Time: 11:43
 */
namespace app\admin\controller;

use think\Request;
use think\Session;
use think\Verify;

class User extends Base{

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
    }


    /**
     * @desc 退出登录
     */
    public function logout(){
       //清除session
        $this->deleteSession();
        $this->redirect('User/login');
    }

    public function login(){
        if(request()->isPost()){
            //接收参数
            // $data = Request::instance()->only('name,password,captchaCode','post');
            $name           = input('post.name','','trim');
            $password       = input('post.password','','trim');
            $captchaCode    = input('post.captchaCode','','trim');

            //验证码验证
            if(!captcha_check($captchaCode)){
                return $this->errorMsg('100000');
            };

            //数据验证
            $result = $this->validate(['username'=>$name,'password'=>$password],'IndexUser.login');
            if(true !== $result){
                // 验证失败 输出错误信息
                return $this->errorMsg('100001',['replace'=>['__REPLACE__'=>$result]]);
            }

            //对比用户当前状态及密码正确性
            $loginCheck = model('IndexUser')->getUserByUsername($name);
            if(empty($loginCheck)){
                return $this->errorMsg('100002');
            }
            if($loginCheck['state']!=1){
                return $this->errorMsg('100003');//状态1正常2禁用
            }

            if($loginCheck['password']!==md5($password)){
                return $this->errorMsg('100004');
            }

            //session
            $this->createSession(['admin_id'=>$loginCheck['id'],'nick_name'=>$loginCheck['nickname'],'group_id'=>$loginCheck['group']]);

            // $this->admin_log();

            return $this->successMsg('Goods/index',['msg'=>'']);
        }
        // $this->error('$msg');
        return view();
    }

}