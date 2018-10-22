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
        $pResult = $this->checkCompanyPermission();
        if($pResult['status'] == 1){
            return $pResult;
        }
        $companyId = $pResult['data']['companyId'];

        $orderModel = new MallOrder();
        $data = $orderModel->getDeskList(MallOrder::ROLE_SELLER,$type,$companyId,$this->userId);
        return ['status'=>0,'data'=>['list'=>$data],'msg'=>''];
    }

    /**
     * @desc
     * @return array|void
     */
    public function getOrderInfo()
    {
        $auth = $this->auth();
        if ($auth) {
            return $auth;
        }
        //权限验证
        $pResult = $this->checkCompanyPermission();
        if($pResult['status'] == 1){
            return $pResult;
        }

        //
        $model = new MallOrder();
        $startTime = strtotime(date("Y-m-d 00:00:00", strtotime("-1 day")));
        $endTime = strtotime(date('Y-m-d 23:59:59', strtotime("-1 day")));

        //
        $yesterdayCount = $model->where(['supplier' => $this->userId])->where('add_time', '>', $startTime)->where('add_time', '<', $endTime)->count();
        $total = $model->where(['supplier' => $this->userId])->count();
        $pendingNumber = $model->where(['supplier' => $this->userId, 'state' => MallOrder::STATE_DELIVER])->count();
        $serviceNumber = $model->where(['supplier'=> $this->userId,'state'=>MallOrder::STATE_RECEIVE,'service_type'=>1])->order(['supplier'=> $this->userId,'state'=>MallOrder::STATE_FINISH,'service_type'=>1])->count();
        //在售商品总数
        $productModel = new SmProduct();
        //交易金额   $where['confirm_delivery_time'] = ['>',0];
        $moneyInfo = $model->where(['supplier'=>$this->userId,'confirm_delivery_time'=>['gt',0]])->field(['sum(`actual_money`) as money'])->find();

        //在售商品访问量
        $goodsInfo = $productModel->where(['state'=>SmProduct::STATE_FORSALE,'audit_state'=>SmProduct::AUDIT_RELEASED,'is_deleted'=>0,'supplier_id'=>$this->userId])->field(['count(*) as count','sum(page_view) as visit'])->find();
        //
        return [
            'status' => 0,
            'data' => [
                'yesterday' => $yesterdayCount,
                'total' => $total,
                'pending' => $pendingNumber,
                'service'=>$serviceNumber,
                'goodsNumber'=>$goodsInfo->count,
                'visit'=>$goodsInfo->visit ? $goodsInfo->visit : 0,
                'money' => $moneyInfo  && $moneyInfo->money ? number_format($moneyInfo->money) : 0
            ],
            'msg' => ''
        ];
    }

}