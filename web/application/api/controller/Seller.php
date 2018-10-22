<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/10/22
 * Time: 9:41
 */

namespace app\api\controller;


use app\common\model\EntCompany;
use app\common\model\MallOrder;
use app\common\model\SmProduct;
use app\common\model\SmProductSpec;

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

        //判断是否为管理员
        $companyModel = new EntCompany();
        $companyInfo = $companyModel->getInfoById($companyId);
        //是否为管理员
        $userId = 0;
        if($companyInfo->responsible_user_id != $this->userId){
            $userId = $this->userId;
        }

        $orderModel = new MallOrder();
        $data = $orderModel->getDeskList(MallOrder::ROLE_SELLER,$type,$companyId,$userId);
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

    /**
     * @desc 卖家订单列表
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getList(Request $request){
        $status = $request->post('status',-1,'intval');
        $pageSize = $request->post('pageSize',10,'intval');
        $pageNumber = $request->post('pageNumber',1,'intval');

        $goodsName = $request->post('goodsName','','trim');
        $companyName = $request->post('companyName','','trim');
        $startDate = $request->post('startDate','','filterDate');
        $endDate = $request->post('endDate','','filterDate');
        $orderNo = $request->post('orderNo','','trim');

        if($pageSize > 12){ $pageSize = 12;}
        $start = ($pageNumber - 1)*$pageSize;
        $end = $pageNumber*$pageSize;

        $auth = $this->auth();
        if($auth){
            return $auth;
        }
        $pResult = $this->checkCompanyPermission();
        if($pResult['status'] == 1){
            return $pResult;
        }
        $companyId = $pResult['data']['companyId'];


        $orderModel = new MallOrder();
        $orderGoodsModel = new MallOrderGoods();

        $where = '';
        $where .='supplier='.$companyId;
        //
        if($goodsName){
            $where .=' AND goods_names LIKE \'%'.addslashes($goodsName).'%\'';
        }
        if($startDate){
            $where .=' AND add_time >'.strtotime($startDate);
        }
        if($endDate){
            $where .=' and add_time <'.strtotime($endDate.' 23:59:59');
        }
        if($orderNo){
            $where .= ' AND out_id LIKE \'%'.addslashes($orderNo).'%\'';
        }

        $companyModel = new EntCompany();
        if($companyName){
            $companyRows = $companyModel->where(['company_name'=>['like','%'.addslashes($companyName).'%']])->find(['id'])->select();
            $companyIds = '';
            foreach($companyRows as $companyRow){
                $companyIds .= $companyRow->id.',';
            }
            $companyIds = $companyIds ? substr($companyIds,0,strlen($companyIds)-1) : $companyIds;
            if($companyIds){
                $where .=' buyer_id IN('.$companyIds.')';
            }
        }

        if($status != '-1'){
            switch ($status){
                case 1:  //待确认
                    $where .= ' AND state IN (0,1)';
                    break;
                case 2: //待付款
                    $where .=' AND state IN (2,9,10) AND service_type IN (0,2)';
                    break;
                case 3: //待发货
                    $where .=' AND state = 3';
                    break;
                case 4: //待收货
                    $where .=' AND state=6 AND service_type IN(0,2)';
                    break;
                case 5: //订单关闭
                    $where .=' AND state=4';
                    break;
                case 6: //售后处理
                    $where .=' AND ( state IN(11,13) OR (state IN (6,9,10) AND service_type IN(1,2)))';
                    break;
                default:
            }
        }
        $count = $orderModel->where($where)->count();
        $rows = $orderModel->where($where)->order('add_time','desc')->limit($start,$end)->field(['id','state','out_id','add_time','actual_money','goods_money','receiver_name','supplier','buyer_id','service_type'])->select();

        foreach ($rows as &$row){
            $companyInfo = $companyModel->getInfoById($row->buyer_id);
            $row['companyName']  = $companyInfo ? $companyInfo->company_name : '';
            $row['money'] = getFormatPrice($row->actual_money);
            $row['orderDate'] = date('Y-m-d H:i:s',$row->add_time);

            $goodsRows = $orderGoodsModel->alias('a')
                ->join(['sm_product'=>'b'],'a.goods_id=b.id','left')
                ->join(['sm_product_spec' => 'c'],'a.product_spec_id=c.id','left')
                ->where(['order_id'=>$row->id])->field(['a.title','a.price','a.quantity','a.unit','a.specifications_no','a.specifications_name','b.cover_img_url','a.s_info','c.spec_img_url'])->select();

            foreach($goodsRows as &$goodsRow){
                $goodsRow['quantity'] = intval($goodsRow->quantity);
                $goodsRow['icon'] = $goodsRow->spec_img_url ? SmProductSpec::getFormatImg($goodsRow->spec_img_url) : SmProduct::getFormatImg($goodsRow->cover_img_url);
                $goodsRow['price'] = getFormatPrice($goodsRow->price);
                $goodsRow['specUnit'] = $goodsRow->unit;
                unset($goodsRow->unit);
            }
            $row['goods'] = $goodsRows;
            $queryStatus = $status == 6 ? true : false;
            $row['statusMsg'] = getOrderMsg($this->groupId,$row->state,$row->service_type,$queryStatus);
            $row['cancelType'] = $this->groupId && ($row->state == 1 || $row->state == 0)   ? 1 : 0;
            $row['confirmType'] = 0;
            $row['actual_money'] = getFormatPrice($row->actual_money);
            $row['goods_money'] = getFormatPrice($row->goods_money);
            unset($row->add_time);
        }

        return ['status'=>0,'data'=>['total'=>$count,'list'=>$rows],'msg'=>''];
    }
}