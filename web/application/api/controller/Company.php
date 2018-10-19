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

}