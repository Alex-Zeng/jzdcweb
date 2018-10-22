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
       $pResult = $this->checkCompanyPermission();
        if($pResult['status'] == 1){
            return $pResult;
        }
        $companyId = $pResult['data']['companyId'];

        $orderModel = new MallOrder();
        $data = $orderModel->getDeskList(MallOrder::ROLE_BUYER,$type,$companyId,$this->userId);
        return ['status'=>0,'data'=>['list'=>$data],'msg'=>''];
    }


    /**
     * @desc 返回订单信息
     * @return array|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getOrderInfo(){
        $auth = $this->auth();
        if ($auth) {
            return $auth;
        }
        //权限验证
        $pResult = $this->checkCompanyPermission();
        if($pResult['status'] == 1){
            return $pResult;
        }

        $model = new MallOrder();
        $condition = [MallOrder::STATE_REMITTANCE,MallOrder::STATE_ACCOUNT_PERIOD,MallOrder::STATE_OVERDUE];
        $payCount = $model->where(['buyer_id' => $this->userId])->whereIn('state',$condition)->count();
        $recieveNumber = $model->where(['buyer_id' => $this->userId,'state' => MallOrder::STATE_RECEIVE])->count();
        $pendingNumber = $model->where(['buyer_id' => $this->userId,'state' => MallOrder::STATE_DELIVER])->count();
        $serviceNumber = $model->where(['buyer_id'=> $this->userId,'service_type'=>1])->count();
        $moneyInfo = $model->where(['buyer_id'=>$this->userId,'confirm_delivery_time'=>['gt',0]])->field(['sum(`actual_money`) as money'])->find();
        return [
            'status' => 0,
            'data' => [
                'pay' => $payCount,
                'recieve' => $recieveNumber,
                'deliver' => $pendingNumber,
                'service'=>$serviceNumber,
                'money'=>$moneyInfo && $moneyInfo->money ? number_format($moneyInfo->money) : 0
            ],
            'msg' => ''
        ];
    }

}