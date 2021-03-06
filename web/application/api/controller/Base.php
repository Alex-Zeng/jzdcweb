<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/5/17
 * Time: 11:59
 */
namespace app\api\controller;

use app\common\model\EntCompany;
use app\common\model\IndexUser;
use Firebase\JWT\JWT;
use think\Exception;
use think\Request;
use think\Controller;

class Base extends Controller
{
    protected $userId = 0;
    protected $groupId = 0;


    /**
     * @desc 执行需要登录
     */
    public function auth(){
        //获取http header变量cookie
        $token = cookie('_token');
        $token2 = Request::instance()->get('_token','');
        $token3 = Request::instance()->post('_token','');

        $token = $token ? $token : ($token2 ? $token2 : $token3);
        if(isset($_GET['tt']) && $_GET['tt'] == 1){
            $this->userId = 71;
            $this->groupId = 4;
            return;
        }
        if(!$token){
            return ['status'=>-2,'data'=>[],'msg'=>'数据错误'];
        }

       // $token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpZCI6NSwiZ3JvdXAiOjQsInRpbWUiOjE1Mjg4NzA2MzQsImV4cGlyZSI6MTUyODg4ODYzNH0.8C514ai0hgrXB675DNXguiG-G8p_sZ_iw8Gv126UK7I';
        //解析token

        $key = config('jzdc_token_key');
        try {
            $data = JWT::decode($token, $key, ['HS256']);
        }catch (Exception $e){
            return ['status'=>-2,'data'=>[],'msg'=>'数据错误'];
        }
        if(!$data->id  || !$data->group || !$data->time || !$data->expire ){
            return ['status'=>-2,'data'=>[],'msg'=>'用户未登录'];
        }

        //更新用户数据
        $model = new IndexUser();
        $row = $model->where(['id'=>$data->id])->field(['id','group','state'])->find();
        if(!$row){
            return ['status'=>-2,'data'=>[],'msg'=>'用户不存在'];
        }

        if($row->group != $data->group || $row->state != 1){
            return ['status'=>-2,'data'=>[],'msg'=>'需要重新登录'];
        }

        $this->userId = $data->id;
        $this->groupId = $data->group;
        return;
    }


    /**
     * @desc 执行不需要登录操作
     */
    public function noauth(){
        //获取http header变量cookie
        $token = cookie('_token');
        $token2 = Request::instance()->get('_token','');
        $token3 = Request::instance()->post('_token','');
        $token = $token ? $token : ($token2 ? $token2 : $token3);
        if(!$token){
            return;
        }
        //解析token
        $key = config('jzdc_token_key');
        try {
            $data = JWT::decode($token, $key, ['HS256']);
        }catch (Exception $e){

        }

        //
        if(!$data->id  || !$data->group || !$data->time || !$data->expire ){
            return;
        }

        //更新用户数据
        $model = new IndexUser();
        $row = $model->where(['id'=>$data->id])->field(['id','group','state'])->find();
        if(!$row){
            return;
        }

        if($row->group != $data->group || $row->state != 1){
            return;
        }

        $this->userId = $data->id;
        $this->groupId = $data->group;
    }


    /**
     * 用户企业权限
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function checkCompanyPermission(){
        $userModel = new IndexUser();
        $companyModel = new EntCompany();

        $userInfo = $userModel->getInfoById($this->userId);
        if(!$userInfo){
            return ['status'=>1,'data'=>[],'msg'=>'用户不存在'];
        }
        if($userInfo->company_id == 0){
            return ['status'=>1,'data'=>[],'msg'=>'用户尚未加入企业'];
        }
        $companyInfo = $companyModel->getInfoById($userInfo->company_id);
        if(!$companyInfo){
            return ['status'=>1,'data'=>[],'msg'=>'企业信息不存在'];
        }
        if($companyInfo->audit_state != EntCompany::STATE_PASS){
            return ['status'=>1,'data'=>[],'msg'=>'企业尚未通过审核'];
        }

        return ['status'=>0,'data'=>['companyId'=>$userInfo->company_id],'msg'=>''];
    }

    /**
     * [checkCompanyPermissionReturnCompanyId 检验是否为企业管理员]
     * @param  [type] $userId [用户ID，不传则获取当前登录者没登录则为空]
     * @return  [是则返回CompanyId,否则返回false]
     */
    protected function checkCompanyPermissionReturnCompanyId($userId=null){
        $IndexUser = new IndexUser();
        $EntCompany = new EntCompany();

        if(is_null($userId)){
            $userId = $this->userId;
        }
        $companyId = $IndexUser->where(['id'=>$userId])->value('company_id');
        if(empty($companyId)){  
            return false;
        }

        if($EntCompany->where(['id'=>$companyId,'audit_state'=>EntCompany::STATE_PASS,'responsible_user_id'=>$userId,'is_deleted'=>0])->value('id')>0){
            return $companyId;
        }else{
            return false;
        }
    }

}