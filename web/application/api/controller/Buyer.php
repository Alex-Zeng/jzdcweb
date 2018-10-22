<?php
/**
 * 买家控制器
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/10/19
 * Time: 17:07
 */

namespace app\api\controller;


use app\common\model\EntCompany;
use app\common\model\IndexUser;
use app\common\model\MallOrder;
use think\Request;

class Buyer extends Base
{

    /**
     * @desc
     * @param Request $request
     * @return array|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
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
        $data = $orderModel->getDeskList(MallOrder::ROLE_BUYER,$type,$companyId,$this->userId);
        return ['status'=>0,'data'=>['list'=>$data],'msg'=>''];
    }

}