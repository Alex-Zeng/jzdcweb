<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/5/21
 * Time: 9:35
 */
namespace app\api\controller;


use app\common\model\Code;
use app\common\model\IndexUser;
use app\common\model\EntCompany;
use think\Request;
use Firebase\JWT\JWT;

class Login{

    /**
     * @desc 用户登录
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index(Request  $request){
       $name = $request->post('userName','');
       $password = $request->post('password','');
       $code = $request->post('code','');
       $id = $request->post('id','');

       //图片验证
       if($code && $id){
           if(!captchaDb_check($code,$id)){
               return ['status'=>1,'data'=>[],'msg'=>'图片验证码错误'];
           }
       }

       //测试数据
       if(!$name){
           return ['status'=>1,'data'=>[],'msg'=>'账户不能为空'];
       }
       if(!$password){
           return ['status'=>1,'data'=>[],'msg'=>'密码不能为空'];
       }

       $model = new IndexUser();
       $where = [];

       //判断账户类型
        $field = ['id','username','password','group','nickname','icon','state', 'contact', 'tel', 'phone', 'email', 'real_name','company_id'];
        if(stripos( $name,'@')){
            $row = $model->where(['email'=>$name])->field($field)->find();
        }else{
            $row = $model->where(['username'=>$name])->whereOr(['phone'=>$name])->field($field)->find();
        }

        //校验
        if(!$row){
            return ['status'=>1,'data'=>[],'msg'=>'用户不存在'];
        }
        if($row->state != 1){
            return ['status'=>1,'data'=>[],'msg'=>'用户已被禁用'];
        }
        if($row->password != md5(trim($password))){
            return ['status'=>1,'data'=>[],'msg'=>'密码不正确'];
        }

        if($row['company_id']>0){
            $roleId = 2;
            $EntCompany = new EntCompany();
            $company = $EntCompany->field('responsible_user_id,company_name')->where(['id'=>$row['company_id']])->find();
        }else{
            $roleId = 0;
            $company = ['responsible_user_id'=>0,'company_name'=>''];
        }
        if($company['responsible_user_id']>0 && $company['responsible_user_id']==$row['id']){
            $roleId = 1;
        }

        $data = [
            'contact' => $row->contact,
            'tel' => $row->tel ? $row->tel : '',
            'icon' => $row->icon ? $row->icon : '',
            'path' => $row->icon ? IndexUser::getFormatIcon($row->icon) : '',
            'phone' => $row->phone,
            'email' => $row->email,
            'username' => $row->username,
            'group' => $row->group,
            'companyName' => $company['company_name'],
            'roleId'=> $roleId
        ];
        //生成token
        $key = config('jzdc_token_key');
        $token = [
            "id" => $row->id,
            "group" => $row->group,
            "time" => time(),
            "expire" => time() + config('JZDC_TOKEN_EXPIRE')   //过期时间
        ];
       $jwt = JWT::encode($token,$key);
       $data['token']= $jwt;
       return ['status'=>0,'data'=>$data,'msg'=>''];
    }


    /**
     * @desc 手机验证码登录
     * @param Request $request
     * @return array
     */
    public function phone(Request $request){
        $phone = $request->post('phone','');
        $code = $request->post('code','');

        if(!$phone){
            return ['status'=>1,'data'=>[],'msg'=>'手机号不能为空'];
        }
        if(!checkPhone($phone)){
            return  ['status'=>1,'data'=>[],'msg'=>'手机号格式不正确'];
        }
        if(!$code){
            return ['status'=>1,'data'=>[],'msg'=>'验证码不能为空'];
        }

        //查询验证码
        $codeModel = new Code();
        $codeRow = $codeModel->where(['phone'=>$phone,'type'=>Code::TYPE_PHONE_LOGIN])->order(['id'=>'desc'])->field(['code','create_time','expire_time'])->find();
        if(!$codeRow){
           return ['status'=>1,'data'=>[],'msg'=>'验证码不存在'];
        }
        //比较验证码
        if($code != $codeRow['code']){
            return ['status'=>1,'data'=>[],'msg'=>'验证码不正确'];
        }
        if($codeRow['expire_time'] < time()){
            return ['status'=>1,'data'=>[],'msg'=>'验证码已过期'];
        }

        //查询user表是否存在
        $model = new IndexUser();
        $row = $model->where(['phone'=>$phone])->field(['id','username','password','group','nickname','icon','state', 'contact', 'tel', 'phone', 'email', 'real_name','company_id'])->find();
        if(!$row){
            return ['status'=>-3,'data'=>[],'msg'=>'用户未注册'];
        }else{
            if($row->state != 1){
                return ['status'=>1,'data'=>[],'msg'=>'用户已被禁用'];
            }
            $token = [
                "id" => $row->id,
                "group" => $row->group,
                "time" => time(),
                "expire" => time() + config('JZDC_TOKEN_EXPIRE')   //过期时间
            ];
        }

        if($row['company_id']>0){
            $roleId = 2;
            $EntCompany = new EntCompany();
            $company = $EntCompany->field('responsible_user_id,company_name')->where(['id'=>$row['company_id']])->find();
        }else{
            $roleId = 0;
            $company = ['responsible_user_id'=>0,'company_name'=>''];
        }
        if($company['responsible_user_id']>0 && $company['responsible_user_id']==$row['id']){
            $roleId = 1;
        }
        
        $data = [
            'contact' => $row->contact,
            'tel' => $row->tel ? $row->tel : '',
            'icon' => $row->icon ? $row->icon : '',
            'path' => $row->icon ? IndexUser::getFormatIcon($row->icon) : '',
            'phone' => $row->phone,
            'email' => $row->email,
            'username' => $row->username,
            'group' => $row->group,
            'companyName' => $company['company_name'],
            'roleId'=> $roleId

        ];
        //生成token
        $key = config('jzdc_token_key');
        $jwt = JWT::encode($token,$key);

        $data['token']= $jwt;
        return ['status'=>0,'data'=>$data,'msg'=>''];
    }
}