<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/10/22
 * Time: 9:41
 */

namespace app\api\controller;


use app\common\model\MallOrder;

class Seller  extends Base
{

    /**
     * @desc
     * @param Request $request
     * @return array|void
     */
    public function getDeskList(Request $request){
        $type = Request::instance()->get('type',1);
        $auth = $this->auth();
        if($auth){
            return $auth;
        }

        //权限验证
        $userModel = new IndexUser();
        $companyModel = new EntCompany();
        $user = $userModel->getInfoById($this->userId);
        $companyInfo = $companyModel->where(['id'=>$user->company_id])->find();
        if($user->company_id == 0 || $companyInfo->audit_state != EntCompany::STATE_PASS){
            return ['status'=>1,'data'=>[],'msg'=>'尚未加入企业或企业审核未通过，无法加入购物车'];
        }

        $companyId = $user->company_id;

        $orderModel = new MallOrder();
        $data = $orderModel->getDeskList(MallOrder::ROLE_SELLER,$type,$companyId,$this->userId);
        return ['status'=>0,'data'=>['list'=>$data],'msg'=>''];
    }
}