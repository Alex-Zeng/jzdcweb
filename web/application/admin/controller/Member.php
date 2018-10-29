<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/6/1
 * Time: 22:35
 */
namespace app\admin\controller;

use app\common\model\EntCompany;
use app\common\model\IndexGroup;
use app\common\model\IndexUser;
use think\Request;

class Member extends Base{

    /**
     * @desc 会员列表
     * @return mixed
     */
    public function index(){
        $model = new IndexUser();
        $k = Request::instance()->get('k','');
        $group = Request::instance()->get('group',0,'intval');
        if(isset($k) && $k){
            $model->where('username|phone|email','like','%'.$k.'%');
        }
        if($group > 0){
            $model->where(['group'=>$group]);
        }

        $companyModel = new EntCompany();
        $rows = $model->where([])->order(['id'=>'desc'])->paginate(null,false,['query'=>request()->param()]);
        foreach ($rows as &$row){
            $companyInfo = $companyModel->getInfoById($row->company_id);
            $row->icon = $row->icon ? IndexUser::getFormatIcon($row->icon) : '';
            $row->logoUri = $companyInfo ? EntCompany::getFormatLogo($companyInfo->logo_uri) : '';
            $row->companyName = $companyInfo ? $companyInfo->company_name : '';
        }

        $this->assign('k',$k);
        $this->assign('list',$rows);
        $this->assign('page',$rows->render());
        $this->assign('group',$group);
        return $this->fetch();
    }

    /**
     * @desc 新增
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function create(Request $request){
        $username = $request->post('username','');
        $phone = $request->post('phone','');
        $realName = $request->post('real_name','');
        $email = $request->post('email','');
        $password = $request->post('password','');
        $role = $request->post('role','6','intval');
        //
        if(!$username){
            return ['status'=>1,'data'=>[],'msg'=>'用户名不能为空'];
        }
        if(!$phone){
            return ['status'=>1,'data'=>[],'msg'=>'手机号不能为空'];
        }
        if(!$realName){
            return ['status'=>1,'data'=>[],'msg'=>'真实姓名不能为空'];
        }
        if(!$password){
            return ['status'=>1,'data'=>[],'msg'=>'密码不能为空'];
        }

        if(checkEmail($username) || checkPhone($username)){
            return ['status'=>1,'data'=>[],'msg'=>'用户名不能为手机号或邮箱'];
        }
        if($email && !checkEmail($email)){
            return ['status'=>1,'data'=>[],'msg'=>'邮箱格式不正确'];
        }

        $model = new IndexUser();

        $exist = $model->where(['username'=>$username])->find();
        if($exist){
            return ['status'=>1,'data'=>[],'msg'=>'用户名已经存在'];
        }
        $exist = $model->where(['phone'=>$phone])->find();
        if($exist){
            return ['status'=>1,'data'=>[],'msg'=>'手机号已经存在'];
        }
        if($email){
            $exist = $model->where(['email'=>$email])->find();
            if($exist){
                return ['status'=>1,'data'=>[],'msg'=>'邮箱已经存在'];
            }
        }

        $row = ['username'=>$username,'password'=>md5($password),'phone'=>$phone,'nickname'=>$username,'reg_time'=>time(),'group'=>$role,'state'=>1,'email'=>$email,'real_name'=>$realName];
        $result = $model->save($row);
        if($result == true){
            return ['status'=>0,'data'=>[],'msg'=>'添加成功'];
        }
        return ['status'=>1,'data'=>[],'msg'=>'添加失败'];
    }

    /**
     * @desc 修改
     * @param Request $request
     * @param $id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function edit(Request $request, $id){
        $nickName = $request->post('nickname','');
        $password = $request->post('password','');
        $state = $request->post('state',2,'intval');

        $model = new IndexUser();

        $row = $model->getInfoById($id);
        if(!$row){
            return ['status'=>1,'data'=>[],'msg'=>'异常'];
        }

        $data['nickname'] = $nickName;
        $data['state'] = $state;
        if($password){
            $data['password'] = md5($password);
        }

        $result = $model->save($data,['id'=>$id]);
        if($result !== false){
            return ['status'=>0,'data'=>[],'msg'=>'更新成功'];
        }
        return ['status'=>1,'data'=>[],'msg'=>'更新失败'];
    }

}