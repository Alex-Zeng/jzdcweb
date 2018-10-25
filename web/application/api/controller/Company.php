<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/10/18
 * Time: 17:58
 */

namespace app\api\controller;


use app\common\model\EntCompany;
use app\common\model\IndexUser;
use think\Db;
use app\common\model\EntCode;
use app\common\model\EntOrganization;

class Company extends Base
{
    /**
     * @desc 获取企业用户的公司状态
     * @return array|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getState(){
        $auth = $this->auth();
        if($auth){
            return $auth;
        }
        //
        $userModel = new IndexUser();
        $userInfo = $userModel->getInfoById($this->userId);
        if($userInfo->company_id == 0){
            return ['status'=>0,'data'=>['state'=>0],'msg'=>'当前用户未加入任何公司'];
        }
        $companyModel = new EntCompany();
        $companyInfo = $companyModel->getInfoById($userInfo->company_id);
        if($companyInfo){
            return ['status'=>0,'data'=>['state'=>$companyInfo->audit_state],'msg'=>''];
        }
        return ['status'=>0,'data'=>['state'=>0],'msg'=>'当前用户未加入任何公司'];
    }

    /**
     * [getCompanyId 获取用户ID]
     * @return [int]
     */
    public function getCompanyId(){
        //获取用户ID
        $userId = $this->userId;

        //获取用户对应的企业ID
        $IndexUser = new IndexUser();
        $companyId = $IndexUser->where(['id'=>$userId])->value('company_id');
        return $companyId;
    }

    /**
     * [checkCompanyAdmin 检验操作者是否为公司管理员]
     * @param[post][int]  $companyId 
     * @return [bool]       
     */
    public function checkCompanyAdmin($companyId){
        //获取用户ID
        $userId = $this->userId;

        //获取企业的管理员并验证当前用户是否为管理员
        $EntCompany = new EntCompany();
        $responsibleUserId = $EntCompany->where(['id'=>$companyId])->value('responsible_user_id');
        if($responsibleUserId!=$userId){
            return false;
        }else{
            return true;
        }
    }

    /**
     * [getOrganization 获取部门列表]
     * @param[post][int]  $hasTotal [是否获取成员数量]
     * @return [type] [array]
     */
    public function getOrganization(){
        //验证用户是否登录及管理员权限
        if ($auth = $this->auth()) {
            return $auth;
        }
        $hasTotal = input('post.hasTotal',1,'intval');
        $companyId = $this->getCompanyId();
        if($companyId==0){
            $data = $hasTotal>0?[]:(object)[];
            return ['status'=>1,'data'=>$data,'msg'=>'请进行企业认证后操作'];;
        }

        $EntOrganization = new EntOrganization();
        if($hasTotal>0){
            $data = $EntOrganization->alias('a')->where(['a.company_id'=>$companyId,'a.is_deleted'=>0,'a.parent_id'=>0])->join(['jzdc_index_user'=>'b'],'a.id=b.organization_id','left')->field('a.id as organizationId,a.org_name as organizationName,count(b.id) as total')->group('a.id')->select();
        }else{
            $data = $EntOrganization->alias('a')->where(['a.company_id'=>$companyId,'a.is_deleted'=>0,'a.parent_id'=>0])->field('a.id as organizationId,a.org_name as organizationName')->select();
        }
        
        return ['status'=>0,'data'=>$data,'msg'=>'部门列表'];
    }

    /**
     * [organizationAdd 部门添加]
     * @param[post][string] $organizationAdd [部门名称]
     * @return [type] [array]
     */
    public function organizationAdd(){
        //验证用户是否登录及管理员权限
        if ($auth = $this->auth()) {
            return $auth;
        }
        $companyId = $this->getCompanyId();
        if($companyId==0){
            return ['status'=>1,'data'=>[],'msg'=>'请进行企业认证后操作'];;
        }
        if(!($this->checkCompanyAdmin($companyId))){
            return ['status'=>1,'data'=>[],'msg'=>'本操作需管理员权限'];;
        }


        //获取参数并验证
        $organizationName = input('post.organizationName','','trim');
        if($organizationName=='' || (mb_strlen($organizationName,'utf8')>30)){
            return ['status'=>1,'data'=>[],'msg'=>'部门名称需一个字符起，30个字符以内'];
        }

        //查询是否存在该名字了
        $EntOrganization = new EntOrganization();
        if($EntOrganization->where(['is_deleted'=>0,'company_id'=>$companyId,'org_name'=>$organizationName])->count()>0){
            return ['status'=>1,'data'=>[],'msg'=>'该部门已经存在，请重新输入'];
        }

        Db::startTrans();

        //入库
        $data = [
            'company_id'=>$companyId,
            'parent_id'=>0,
            'org_name'=>$organizationName,
            'level'=>1
        ];
        $userId = $this->userId;
        $count = $EntOrganization->data(array_merge($data,$EntOrganization->tableDefaultValue('create',$userId)))->save();
        if($count!=1){
            Db::rollback(); 
            return ['status'=>1,'data'=>[],'msg'=>'添加失败01'];
        }

        $id = $EntOrganization->id;
        if($EntOrganization->where(['id'=>$id])->update(['depth_path'=>$id])==0){
            Db::rollback(); 
            return ['status'=>1,'data'=>[],'msg'=>'添加失败02'];
        }
        
        Db::commit();
        return ['status'=>0,'data'=>[],'msg'=>'添加成功'];
    }

    /**
     * [organizationEdit 部门名称修改]
     * @param[post][int] $organizationId [部门ID]
     * @param[post][string] $organizationName [部门名称]
     * @return [type] [array]
     */
    public function organizationEdit(){
        //验证用户是否登录及管理员权限
        if ($auth = $this->auth()) {
            return $auth;
        }
        $companyId = $this->getCompanyId();
        if($companyId==0){
            return ['status'=>1,'data'=>[],'msg'=>'请进行企业认证后操作'];;
        }
        if(!($this->checkCompanyAdmin($companyId))){
            return ['status'=>1,'data'=>[],'msg'=>'本操作需管理员权限'];;
        }

        //获取参数
        $organizationId = input('post.organizationId',0,'intval');
        $organizationName = input('post.organizationName','','trim');


        //验证参数及是否已存在
        if($organizationName=='' || (mb_strlen($organizationName,'utf8')>30)){
            return ['status'=>1,'data'=>[],'msg'=>'部门名称需一个字符起，30个字符以内'];
        }
        $EntOrganization = new EntOrganization();
        if($EntOrganization->where(['is_deleted'=>0,'company_id'=>$companyId,'org_name'=>$organizationName,'id'=>['<>',$organizationId]])->count()>0){
            return ['status'=>1,'data'=>[],'msg'=>'该部门已经存在，请重新输入'];
        }

        //更新操作
        if($EntOrganization->where(['id'=>$organizationId])->update(['org_name'=>$organizationName])==0){
            return ['status'=>1,'data'=>[],'msg'=>'修改失败'];
        }else{
            return ['status'=>0,'data'=>[],'msg'=>'修改成功'];
        }
    }

    /**
     * [organizationUser 获取部门成员]
     * @param[post][int] $organizationId [部门ID]
     * @return [type] [description]
     */
    public function getOrganizationStaff(){
        //验证用户是否登录及管理员权限
        if ($auth = $this->auth()) {
            return $auth;
        }
        $companyId = $this->getCompanyId();
        if($companyId==0){
            return ['status'=>1,'data'=>(object)[],'msg'=>'请进行企业认证后操作'];;
        }

        //获取参数
        $organizationId = input('post.organizationId',0,'intval');

        $IndexUser = new IndexUser();
        $staffList = $IndexUser->where(['company_id'=>$companyId,'organization_id'=>$organizationId])->field('id as staffId,nickname as staffName,phone,remarks')->select();

        $EntOrganization = new EntOrganization();
        $organizationName = $EntOrganization->where(['company_id'=>$companyId,'id'=>$organizationId])->value('org_name');

        $data['staffList'] =$staffList;
        $data['organizationName'] = $organizationName;
        $data['organizationId'] = $organizationId;

        return ['status'=>0,'data'=>$data,'msg'=>'成员列表'];
    }

    /**
     * [getStaffDetail 获取职员详情]
     * @param[post][int] $staffId [成员ID]
     * @return [type] [array]
     */
    public function getStaffDetail(){
        //验证用户是否登录及管理员权限
        if ($auth = $this->auth()) {
            return $auth;
        }
        $companyId = $this->getCompanyId();
        if($companyId==0){
            return ['status'=>1,'data'=>(object)[],'msg'=>'请进行企业认证后操作'];;
        }

        //获取参数
        $staffId = input('post.staffId',0,'intval');

        //查询改用户信息
        $IndexUser = new IndexUser();
        $data = $IndexUser->where(['company_id'=>$companyId,'id'=>$staffId])->field('id as staffId,nickname as staffName,phone,remarks,organization_id as organizationId')->find();

        $EntOrganization = new EntOrganization();
        $data['organizationName'] = $EntOrganization->where(['id'=>$data['organizationId']])->value('org_name');
        
        return ['status'=>0,'data'=>$data,'msg'=>'成员详细信息'];
    }

    /**
     * [staffAdd 职员添加]
     * @param[post][int] $organizationId [部门ID]
     * @param[post][string] $phone [手机号码]
     * @return [type] [array]
     */
    public function staffAdd(){
        //验证用户是否登录及管理员权限
        if ($auth = $this->auth()) {
            return $auth;
        }
        $companyId = $this->getCompanyId();
        if($companyId==0){
            return ['status'=>1,'data'=>(object)[],'msg'=>'请进行企业认证后操作'];
        }
        if(!($this->checkCompanyAdmin($companyId))){
            return ['status'=>1,'data'=>(object)[],'msg'=>'本操作需管理员权限'];
        }

        //获取参数
        $phone = input('post.phone',0);
        $organizationId = input('post.organizationId',0,'intval');

        //验证信息
        if(!checkPhone($phone)){
            return ['status'=>1,'data'=>(object)[],'msg'=>'请输入正确的手机号码'];
        }
        $IndexUser = new IndexUser();
        $result = $IndexUser->where(['phone'=>$phone])->field('id,company_id,organization_id')->find();
        if($result){
            if($result['company_id']>0 && $result['company_id']!=$companyId){
                return ['status'=>1,'data'=>(object)[],'msg'=>'该用户已经挂在别的企业中'];
            }else if($result['company_id'] = $companyId && $result['organization_id']>0){
                return ['status'=>1,'data'=>(object)[],'msg'=>'该用户已经在企业组织架构内'];
            }else{
                $EntCode = new EntCode();
                $result = $EntCode->where(['phone'=>$phone,'company_id'=>$companyId,'used'=>0])->field('code,id,create_time,send_times')->order('id desc')->find();
                if(empty($result)){
                    $code = getInvitationCode();
                    $type = 'create';
                }else{
                    if($result['send_times']<5){
                        $type = 'update';
                        $code = $result['code'];
                        $entCodeId = $result['id'];
                    }else if($result['send_times']>=5 && (time()-$result['create_time'])>3600){
                        $type = 'create';
                        $code = getInvitationCode();
                    }else{
                        return ['status'=>1,'data'=>(object)[],'msg'=>'一小时内最多对一个手机号只能发送5次邀请'];
                    }
                }
                //发送短信
                $param['code'] = $code;
                $yunpian = new \sms\Yunpian();
                $result = true;//$yunpian->send($phone,$param,\sms\Yunpian::TPL_ORGANIZATION_USER);
                if(!$result){
                    return ['status'=>1,'data'=>(object)[],'msg'=>'验证码发送失败'];
                }
                switch ($type) {
                    case 'create': //入库
                        $data = ['phone'=>$phone,'code'=>$code,'company_id'=>$companyId,'organization_id'=>$organizationId,'create_time'=>time(),'send_times'=>1];
                        $result = $EntCode->data($data)->save();
                        if(!$result){
                            return ['status'=>1,'data'=>(object)[],'msg'=>'验证码分配失败'];
                        }
                        break;
                    
                    default:
                        $result = $EntCode->where(['id'=>$entCodeId])->setInc('send_times');
                        if(!$result){
                            return ['status'=>1,'data'=>(object)[],'msg'=>'验证码分配失败'];
                        }
                        break;
                }
                $msg = "邀请用户加入";
                $data = ['redirect'=>'registered','redirectionInfo'=>'已成功向尾号'.mb_substr($phone,-4,4).'发送验证码,绑定完成后，您将会收到通知'];
                return ['status'=>0,'data'=>$data,'msg'=>$msg];
            }
        }else{
            $msg = "号码未注册";
            $data = ['redirect'=>'unregistered','organizationId'=>$organizationId];
            return ['status'=>0,'data'=>$data,'msg'=>$msg];
        }
    }


    /**
     * [staffEdit 职员修改]
     * @param[post][int] $organizationId [部门ID]
     * @param[post][int] $staffId [成员ID]
     * @param[post][string] $remarks [备注]
     * @return [type] [description]
     */
    public function staffEdit(){
        //验证用户是否登录及管理员权限
        if ($auth = $this->auth()) {
            return $auth;
        }
        $companyId = $this->getCompanyId();
        if($companyId==0){
            return ['status'=>1,'data'=>[],'msg'=>'请进行企业认证后操作'];
        }
        if(!($this->checkCompanyAdmin($companyId))){
            return ['status'=>1,'data'=>[],'msg'=>'本操作需管理员权限'];
        }
        // dump($companyId);exit();
        //获取参数
        $staffId = input('post.staffId',0,'intval');
        $organizationId = input('post.organizationId',0,'intval');
        $remarks = input('post.remarks','','trim');

        //验证
        $IndexUser = new IndexUser();
        if($IndexUser->where(['id'=>$staffId,'company_id'=>$companyId])->count()==0){
            return ['status'=>1,'data'=>[],'msg'=>'所修改的用户不存在'];
        }
        $EntOrganization = new EntOrganization();
        if($EntOrganization->where(['company_id'=>$companyId,'id'=>$organizationId,'is_deleted'=>0])->count()==0){
            return ['status'=>1,'data'=>[],'msg'=>'所选择的部门不存在'];
        }
        if(mb_strlen($remarks,'utf8')>100){
            return ['status'=>1,'data'=>[],'msg'=>'备注长度不能超过100字符'];
        }

        //更新操作
        if($IndexUser->where(['id'=>$staffId])->update(['organization_id'=>$organizationId,'remarks'=>$remarks])){
            return ['status'=>0,'data'=>[],'msg'=>'修改成功'];
        }else{
            return ['status'=>1,'data'=>[],'msg'=>'修改失败'];
        }
    }

    /**
     * [staffDelete 删除职员]
     * @param[post][int] $staffId [成员ID]
     * @return [array] [返回值]
     */
    public function staffDelete(){
        //验证用户是否登录及管理员权限
        if ($auth = $this->auth()) {
            return $auth;
        }
        $companyId = $this->getCompanyId();
        if($companyId==0){
            return ['status'=>1,'data'=>[],'msg'=>'请进行企业认证后操作'];
        }
        if(!($this->checkCompanyAdmin($companyId))){
            return ['status'=>1,'data'=>[],'msg'=>'本操作需管理员权限'];
        }

        //获取参数
        $staffId = input('post.staffId',0,'intval'); 

        //验证
        $IndexUser = new IndexUser();
        if($IndexUser->where(['id'=>$staffId,'company_id'=>$companyId])->count()==0){
            return ['status'=>1,'data'=>[],'msg'=>'所修改的用户不存在'];
        }
        if($this->userId==$staffId){
            return ['status'=>1,'data'=>[],'msg'=>'暂不支持删除管理员'];
        }

        //更新操作
        if($IndexUser->where(['id'=>$staffId])->update(['organization_id'=>0,'company_id'=>0])==1){
            return ['status'=>0,'data'=>[],'msg'=>'删除成功'];
        }else{
            return ['status'=>1,'data'=>[],'msg'=>'删除失败'];
        }
    }

    /**
     * [userAdd 用户添加]
     * @param[post][string] $phone [手机号]
     * @param[post][string] $userName [用户名]
     * @param[post][string] $password [密码]
     * @param[post][string] $passwordConfirm [确认密码]
     * @param[post][int] $organizationId [部门ID]
     * @param[post][string] $remarks [备注]
     * @return [type] [description]
     */
    public function userAdd(){
        //验证用户是否登录及管理员权限
        if ($auth = $this->auth()) {
            return $auth;
        }
        $companyId = $this->getCompanyId();
        if($companyId==0){
            return ['status'=>1,'data'=>[],'msg'=>'请进行企业认证后操作'];
        }
        if(!($this->checkCompanyAdmin($companyId))){
            return ['status'=>1,'data'=>[],'msg'=>'本操作需管理员权限'];
        }

        //获取参数
        $phone = input('post.phone','','trim');
        $userName = input('post.userName','','trim,htmlspecialchars');
        $password = input('post.password','','trim');
        $passwordConfirm = input('post.passwordConfirm','','trim');
        $organizationId = input('post.organizationId',0,'intval');
        $remarks = input('post.remarks','','trim');

        //验证
        if(!$phone){
            return  ['status'=>1,'data'=>[],'msg'=>'手机号不能为空'];
        }
        if(!checkPhone($phone)){
            return  ['status'=>1,'data'=>[],'msg'=>'手机号格式不正确'];
        }
        if(!$userName){
            return ['status'=>1,'data'=>[],'msg' => '用户名不能为空'];
        }
        if(checkEmail($userName)){
            return ['status'=>1,'data'=>[],'msg' => '用户名不能为邮箱'];
        }
        if(checkPhone($userName)){
            return ['status'=>1,'data'=>[],'msg' => '用户名不能为手机号'];
        }
        if(!$password){
            return ['status'=>1,'data'=>[],'msg' => '密码必须填写'];
        }
        if(!checkPassword($password)){
            return ['status'=>1,'data'=>[],'msg'=>'密码必须为6-20位的数字和字母组合'];
        }
        if($password!=$passwordConfirm){
            return ['status'=>1,'data'=>[],'msg' => '两次输入的密码不一致'];
        }
        if($organizationId==0){
            return ['status'=>1,'data'=>[],'msg' => '部门未选择'];
        }
        if(mb_strlen($remarks,'utf8')>100){
            return ['status'=>1,'data'=>[],'msg' => '备注长度不能超过100字符'];
        }

        //检查账号是否已注册
        $IndexUser = new IndexUser();
        $user = $IndexUser->getUserByPhone($phone);
        if($user){
            return ['status'=>1,'data'=>[],'msg'=>'手机号已注册'];
        }

        $u = $IndexUser->where(['username'=>$userName])->whereOr(['phone'=>$userName])->whereOr(['email'=>$userName])->find();
        if(checkPhone($userName)){
            return ['status'=>1,'data'=>[],'msg'=>'用户名不能为手机号码'];
        }
        if(checkEmail($userName)){
            return ['status'=>1,'data'=>[],'msg'=>'用户名不能为邮箱'];
        }
        if($u){
            return ['status'=>1,'data'=>[],'msg'=>'用户名称已注册'];
        }

        $EntOrganization = new EntOrganization();
        if($EntOrganization->where(['company_id'=>$companyId,'id'=>$organizationId,'is_deleted'=>0])->count()==0){
            return ['status'=>1,'data'=>[],'msg'=>'公司不存在该部门'];
        }
        //注册账户
        $data = [
            'username' => $userName,
            'nickname' => $userName,
            'company_id'=>$companyId,
            'organization_id'=>$organizationId,
            'phone' => $phone,
            'password' => md5($password),
            'reg_time' => time(),
            'reg_ip' => request()->ip(),
            'group' => 6,
            'state' => 1
        ];
        if($IndexUser->data($data)->save()){
            return ['status'=>0,'data'=>[],'msg'=>'添加成功'];
        }else{
            return ['status'=>1,'data'=>[],'msg'=>'添加失败'];
        }
    }

    /**
     * [getContactList 通讯录]
     * @param[post][string] $type [获取数据的类型，默认值list，可选object]
     * @return [type] [description]
     */
    public function getContactList(){
        //验证用户是否登录及管理员权限
        if ($auth = $this->auth()) {
         return $auth;
        }
        $companyId = $this->getCompanyId();
        if($companyId==0){
            return ['status'=>1,'data'=>[],'msg'=>'请进行企业认证后操作'];;
        }

        //获取参数
        $likeParam = input('post.likeParam','','trim');
        $organizationId = input('post.organizationId',0,'intval');
        $userName = input('post.userName','','trim');
        $phone = input('post.phone','','trim');
        
        if($likeParam!=''){
            $whereOr["b.nickname"] = ["like","%".$likeParam."%"];

            $EntOrganization = new EntOrganization();
            $organizationIds = $EntOrganization->where(['is_deleted'=>0,'company_id'=>$companyId,'org_name'=>['like','%'.$likeParam.'%']])->column('id');
            if(count($organizationIds)>0){
                $whereOr['a.id'] = ['in',$organizationIds];
            }

            if(is_numeric($likeParam)){
                $whereOr["b.phone"]=["like","%".$likeParam."%"];
            }
            
            //使用闭包查询，匿名函数需借助use关键字来传参    
            $data = $EntOrganization->alias('a')->where(function($query) use ($whereOr){
            $query->whereOr($whereOr);})->where(['a.company_id'=>$companyId,'a.is_deleted'=>0,'a.parent_id'=>0])->join(['jzdc_index_user'=>'b'],'a.id=b.organization_id','right')->field('a.org_name as organizationName,b.id as staffId,b.phone,b.nickname as staffName')->order('org_name')->select();
        }else{
            $where = [];
            if($organizationId>0){
                $where['a.id'] = $organizationId;
            }
            if($userName!=''){
                $where['b.nickname'] = ['like','%'.$userName.'%'];
            }
            if($phone!=''){
                $where['b.phone'] = $phone;
            }
            $EntOrganization = new EntOrganization();
            $data = $EntOrganization->alias('a')->where($where)->where(['a.company_id'=>$companyId,'a.is_deleted'=>0,'a.parent_id'=>0])->join(['jzdc_index_user'=>'b'],'a.id=b.organization_id','right')->field('a.org_name as organizationName,b.id as staffId,b.phone,b.nickname as staffName')->order('org_name')->select();
        }

        //获取参数
        $type = input('post.type','list','trim');
        switch ($type) {
            case 'object':
                $arr = [];
                $dt = [];
                foreach ($data as $key => $val) {
                    $arr[$val['organizationName']][] = ['phone'=>$val['phone'],'staffId'=>$val['staffId'],'staffName'=>$val['staffName']];
                }
                foreach ($arr as $k => $v) {
                    $dt[] = ['organizationName'=>$k,'staffList'=>$v];
                }
                return ['status'=>0,'data'=>$dt,'msg'=>'通讯录列表'];
                break;
            
            default:
                return ['status'=>0,'data'=>$data,'msg'=>'通讯录列表'];
                break;
        } 
    }
}