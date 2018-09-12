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
use app\common\model\IndexUser;

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

            return $this->successMsg('Product/listPass',['msg'=>'']);
        }
        // $this->error('$msg');
        return view();
    }

    //修改用户手机号
    public function editPhone(){
        if(request()->isPost()){
            if(input('post.postType','','trim')=='getCode'){
                $oldPhone = input('post.oldPhone','','trim');
                if(!$oldPhone){
                    return $this->errorMsg('100100');
                }
                if(!checkPhone($oldPhone)){
                    return $this->errorMsg('100101');
                }
                $model = new IndexUser();
                $user = $model->getInfoById(getUserId());
                if($user['phone']!=$oldPhone){
                    return $this->errorMsg('100102');
                }

                $code = getVerificationCode();
                $param['code'] = $code;
                //发送短信验证码
                // $yunpian = new \sms\Yunpian();
                // $result = $yunpian->send($oldPhone,$param,\sms\Yunpian::CONTENT);
                $result = true;
                if($result){
                    //更新短信验证码
                    $codeModel = new \app\common\model\Code();
                    $time = time();
                    $data = ['phone'=>$oldPhone,'type'=>\app\common\model\Code::TYPE_PHONE_ADMIN_OLD,'code'=>$code,'create_time'=>$time,'expire_time'=>$time+300];
                    $result = $codeModel->save($data);
                    if($result){
                        return $this->successMsg('noSkip',['msg'=>'验证码发送成功']);
                    }
                }
                return $this->errorMsg('100103');
            }else{
                $oldPhone = input('post.oldPhone','','trim');
                $newPhone = input('post.newPhone','','trim');
                $phoneCode = input('post.phoneCode','','trim');
                
                $result = $this->validate(
                    [
                        'oldPhone'=>$oldPhone,
                        'phoneCode'=>$phoneCode,
                        'newPhone'=>$newPhone
                    ],[
                        'oldPhone'=>'require',
                        'phoneCode'=>'require',
                        'newPhone'=>'require'
                    ],[
                        'oldPhone.require' =>'100200',
                        'phoneCode.require' =>'100202',
                        'newPhone.require' =>'100203'
                    ]
                );
                if(true !== $result){
                    // 验证失败 输出错误信息
                    return $this->errorMsg($result);
                }
                if(!checkPhone($oldPhone)){
                    return $this->errorMsg('100201');
                }
                if(!checkPhone($newPhone)){
                    return $this->errorMsg('100204');
                }

                $model = new IndexUser();
                $user = $model->getInfoById(getUserId());
                if($user['phone']!=$oldPhone){
                    return $this->errorMsg('100205');
                }

                $codeModel = new \app\common\model\Code();
                $codeRow = $codeModel->where(['phone'=>$oldPhone,'type'=>\app\common\model\Code::TYPE_PHONE_ADMIN_OLD])->order('id','desc')->find();
                if(!$codeRow || $codeRow['code']!= $phoneCode){
                    return $this->errorMsg('100206');
                }
                if($codeRow['expire_time'] < time()){
                    return $this->errorMsg('100207');
                }

                if(!db('index_user')->where(['id'=>$user['id']])->update(['phone'=>$newPhone])){
                    return $this->errorMsg('100208');
                }

                return $this->successMsg('reload',['msg'=>'修改成功']);
            }
        }
        return view();
    }

    //修改密码
    public function changePassword(){
        if(request()->isPost()){
            $oldPassword = input('post.oldPassword','','trim');
            $newPassword = input('post.newPassword','','trim');
            $newPasswordAgain = input('post.newPasswordAgain','','trim');

            $result = $this->validate(
                [
                    'oldPassword'=>$oldPassword,
                    'newPassword'=>$newPassword,
                    'newPasswordAgain'=>$newPasswordAgain
                ],[
                    'oldPassword'=>'require|min:6',
                    'newPassword'=>'require|min:6',
                    'newPasswordAgain'=>'require|min:6'
                ],[
                    'oldPassword.require' =>'100300',
                    'oldPassword.min' =>'100301',
                    'newPassword.require' =>'100302',
                    'newPassword.min' =>'100303',
                    'newPasswordAgain.require' =>'100304',
                    'newPasswordAgain.min' =>'100305'
                ]
            );
            if(true !== $result){
                // 验证失败 输出错误信息
                return $this->errorMsg($result);
            }
            if($oldPassword === $newPassword){
                return $this->errorMsg('100306');
            }
            if($newPassword !== $newPasswordAgain){
                return $this->errorMsg('100307');
            }

            $model = new IndexUser();
            $user = $model->getInfoById(getUserId());
            if($user['password']!==md5($oldPassword)){
                return $this->errorMsg('100308');
            }

            if(!db('index_user')->where(['id'=>$user['id']])->update(['password'=>md5($newPassword)])){
                return $this->errorMsg('100309');
            }
            return $this->successMsg('reload',['msg'=>'修改成功']);
        }
        return view();
    }

}