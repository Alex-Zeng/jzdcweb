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
use app\common\model\MallOrderGoods;
use app\common\model\MallOrderPay;
use app\common\model\OrderMsg;
use app\common\model\SmProduct;
use app\common\model\SmProductSpec;
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
        $companyId = $pResult['data']['companyId'];

        //判断是否为管理员
        $companyModel = new EntCompany();
        $companyInfo = $companyModel->getInfoById($companyId);
        //是否为管理员
        $userId = 0;
        if($companyInfo->responsible_user_id != $this->userId){
            $userId = $this->userId;
        }

        //根据用户ID进行查询
        $model = new MallOrder();
        $condition = [MallOrder::STATE_REMITTANCE,MallOrder::STATE_ACCOUNT_PERIOD,MallOrder::STATE_OVERDUE];
        $payCount = $model->where(['buyer_id' => $companyId])->whereIn('state',$condition)->count();
        $recieveNumber = $model->where(['buyer_id' => $companyId,'state' => MallOrder::STATE_RECEIVE])->count();
        $pendingNumber = $model->where(['buyer_id' => $companyId,'state' => MallOrder::STATE_DELIVER])->count();
        $serviceNumber = $model->where(['buyer_id'=> $companyId,'service_type'=>1])->count();
        $moneyInfo = $model->where(['buyer_id'=>$companyId,'confirm_delivery_time'=>['gt',0]])->field(['sum(`actual_money`) as money'])->find();
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


    /**
     * @desc 买家订单列表
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
        //权限验证
        $pResult = $this->checkCompanyPermission();
        if($pResult['status'] == 1){
            return $pResult;
        }
        $companyId = $pResult['data']['companyId'];

        $orderModel = new MallOrder();
        $orderGoodsModel = new MallOrderGoods();

        $where = '';
        $where.= 'buyer_id='.$companyId;
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
            $companyRows = $companyModel->where(['real_name'=>['like','%'.addslashes($companyName).'%']])->find(['id'])->select();
            $companyIds = '';
            foreach($companyRows as $companyRow){
                $companyIds .= $companyRow->id.',';
            }
            $companyIds = $companyIds ? substr($companyIds,0,strlen($companyIds)-1) : $companyIds;
            if($companyIds){
                $where .=' supplier IN('.$companyIds.')';
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
            $companyInfo = $companyModel->getInfoById($row->supplier);
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
            $row['cancelType'] =  ($row->state == 1 || $row->state == 0)   ? 1 : 0;
            $row['confirmType'] = ($row->state == 6) && ($row->service_type == 0 || $row->service_type == 2) ? 1 : 0;
            $row['actual_money'] = getFormatPrice($row->actual_money);
            $row['goods_money'] = getFormatPrice($row->goods_money);
            unset($row->add_time);
        }

        return ['status'=>0,'data'=>['total'=>$count,'list'=>$rows],'msg'=>''];
    }


    /**
     * @desc 订单详情
     * @param Request $request
     * @return array|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function detail(Request $request){
        $no = $request->post('no','');
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

        //订单号
        $model = new MallOrder();
        $where['out_id'] = $no;
        $where['buyer_id'] = $companyId;

        $row = $model->where($where)->field(['id','receiver_area_name','add_time','delivery_time','actual_money','goods_money','receiver_name','receiver_phone','receiver_detail','express_name','express_code','state','send_time','estimated_time','pay_date','out_id','buyer_comment','buyer_id','supplier','service_type'])->find();
        if(!$row){
            return ['status'=>1,'data'=>[],'msg'=>'订单不存在'];
        }

        //采购商供应商
        $companyModel = new EntCompany();
        $sellerInfo = $companyModel->getInfoById($row->supplier);
        $buyerInfo = $companyModel->getInfoById($row->buyer_id);

        //查询产品
        $goodsModel = new MallOrderGoods();
        $productModel = new SmProduct();
        $goodsRows = $goodsModel->alias('a')->join(['sm_product_spec'=>'b'],'a.goods_id=b.id','left')->where(['order_id'=>$row->id])
            ->field(['a.id','a.title','a.price','a.quantity','a.unit','a.s_info','a.goods_id','a.specifications_no','a.specifications_name','a.service_type','b.spec_img_url'])->select();

        foreach($goodsRows as &$goodsRow){
            $goodsRow['quantity'] = intval($goodsRow->quantity);
            if($goodsRow->spec_img_url){
                $goodsRow['icon'] = SmProductSpec::getFormatImg($goodsRow->spec_img_url);
            }else{
                $product = $productModel->find(['id'=>$goodsRow->goods_id]);
                $goodsRow['icon'] = SmProduct::getFormatImg($product->cover_img_url);
            }
            $goodsRow['price'] = getFormatPrice($goodsRow->price);
            $goodsRow['specUnit'] = $goodsRow->unit;
        }

        //查询支付凭证
        $payModel = new MallOrderPay();
        $payRow = $payModel->where(['order_id'=>$row->id,'pay_type'=>['in',[3,4]]])->order('id','desc')->find();

        $data = [
            'orderNo' => $row->out_id,
            'companyName' => $buyerInfo ? $buyerInfo->company_name : '' ,
            'supplierName' => $sellerInfo ? $sellerInfo->company_name : '',
            'buyerName' => $buyerInfo ? $buyerInfo->company_name : '',
            'groupId' => $this->groupId,
            'state' => $row->state,
            'money' => getFormatPrice($row->actual_money),
            'goods_money'=> getFormatPrice($row->goods_money),
            'name' => $row->receiver_name,
            'phone' => $row->receiver_phone,
            'address' => $row->receiver_area_name. $row->receiver_detail,
            'time' => date('Y-m-d H:i',$row->add_time),
            'date' => $row->delivery_time > 0 ? date('Y-m-d',$row->delivery_time) : '',
            'remark' => $row->buyer_comment,
            'express' => $row->express_name ? $row->express_name : '',   //物流
            'expressCode' => $row->express_code ? $row->express_code : '', //物流单号
            'sendDate' => $row->send_time > 0 ? date('Y-m-d',$row->send_time) : '', //发货日期
            'estimatedDate' => $row->estimated_time > 0 ? date('Y-m-d',$row->estimated_time) : '',  //到达日期
            'serviceType' => $row->service_type,
            'goods' => $goodsRows,
            'overDate' => $row->pay_date ? substr($row->pay_date,0,10) : ''
        ];


        $data['statusMsg'] = getOrderMsg($this->groupId,$row->state,$row->service_type);
        $data['isService'] =  $this->groupId == 4 && $row->service_type == 0 && ($row->state == 6 || $row->state == 13 || $row->state == 9 || $row->state == 10 || $row->state == 11)  ? 1 : 0;
        $data['payMethod'] = '';
        $data['payNumber'] = '';
        $data['payImg'] = '';
        $data['payDate'] =  '';


        return ['status'=>0,'data'=>$data,'msg'=>''];
    }


    /**
     * @desc 确认收货
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function receipt(Request $request){
        $orderNo = $request->post('no','');
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
        if(!$orderNo){
            return ['status'=>1,'data'=>[],'msg'=>'订单号不能为空'];
        }

        $model = new MallOrder();
        $where['out_id'] = $orderNo;
        $where['buyer_id'] = $companyId;
        $row = $model->where($where)->field(['id','receiver_area_name','add_time','delivery_time','receiver_name','receiver_phone','receiver_detail','goods_names','state','pay_date','out_id','buyer_comment','buyer_id','supplier','service_type','is_account_period'])->find();
        if(!$row){
            return ['status'=>1,'data'=>[],'msg'=>'订单不存在'];
        }
        if($row->state != MallOrder::STATE_RECEIVE || $row->service_type == 1){
            return ['status'=>1,'data'=>[],'msg'=>'订单状态操作错误'];
        }

        //更新状态
        if($row->is_account_period){ //有账期
            $state = MallOrder::STATE_ACCOUNT_PERIOD;
        }else{ //无账期 =>
            $state = MallOrder::STATE_REMITTANCE_SUPPLIER;
        }

        $result = $model->save(['state'=>$state,'receipt_time'=>time()],$where);
        if($result !== false){
            //消息通知
            $orderMsgModel = new OrderMsg();
            $userModel = new IndexUser();
            $companyModel = new EntCompany();

            $sellerInfo = $companyModel->getInfoById($row->supplier);
            $content = "订单号：{$row->out_id}【{$row->goods_names}】买家已经确认收货。";
            $msgData = ['title'=>'买家已确认收货','content' => $content,'order_no' => $row->out_id,'order_id'=>$row->id,'user_id'=>$sellerInfo->responsible_user_id,'create_time'=>time()];
            $orderMsgModel->save($msgData);
            $userModel->where(['id'=>$sellerInfo->responsible_user_id])->setInc('unread',1);
            return ['status'=>0,'data'=>[],'msg'=>'确认收货成功'];
        }
        return ['status'=>1,'data'=>0,'msg'=>'确认收货失败'];
    }


    /**
     * @desc 取消订单
     * @param Request $request
     * @return array|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function cancel(Request $request){
        $orderNo = $request->post('no','');
        if(!$orderNo){
            return ['status'=>1,'data'=>[],'msg'=>'订单号不能为空'];
        }
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

        //查询数据
        $model = new MallOrder();
        $where['out_id'] = $orderNo;
        $where['buyer_id'] = $companyId;
        $row = $model->where($where)->find();
        if(!$row){
            return ['status'=>1,'data'=>[],'msg'=>'订单不存在'];
        }
        if($row->state != MallOrder::STATE_PRICING && $row->state != MallOrder::STATE_SIGN && $row->state != MallOrder::STATE_REMITTANCE){
            return ['status'=>1,'data'=>[],'msg'=>'当前订单状态无法取消'];
        }

        $result = $model->save(['state'=>MallOrder::STATE_CLOSED],$where);
        if($result !== false){
            $orderMsgModel = new OrderMsg();
            $userModel = new IndexUser();
            $companyModel = new EntCompany();
            $sellerInfo = $companyModel->getInfoById($row->supplier);

            $content = "订单号：{$row->out_id}【{$row->goods_names}】已取消该笔订单。";
            $msgData = ['title'=>'订单取消','content' => $content,'order_no' => $row->out_id,'order_id'=>$row->id,'user_id'=>$sellerInfo->responsible_user_id,'create_time'=>time()];
            $orderMsgModel->save($msgData);
            $userModel->where(['id'=>$sellerInfo->responsible_user_id])->setInc('unread',1);
            return ['status'=>0,'data'=>[],'msg'=>'订单取消成功'];
        }
        return ['status'=>1,'data'=>[],'msg'=>'订单取消失败'];
    }

    /**
     * @desc 申请售后
     * @param Request $request
     * @return array|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function service(Request $request){
        $orderNo = $request->post('no','');
        $goodsId = $request->post('goodsId',0,'intval'); //商品主键ID
        $type = $request->post('type',0,'intval');

        if(!$orderNo){
            return ['status'=>1,'data'=>[],'msg'=>'订单号不能为空'];
        }
        $auth = $this->auth();
        if($auth){
            return $auth;
        }
        $pResult = $this->checkCompanyPermission();
        if($pResult['status'] == 1){
            return $pResult;
        }
        $companyId = $pResult['data']['companyId'];

        //查询数据
        $model = new MallOrder();
        $where['out_id'] = $orderNo;
        $where['buyer_id'] = $companyId;
        $row = $model->where($where)->field(['id','state',])->find();
        if(!$row){
            return ['status'=>1,'data'=>[],'msg'=>'订单不存在'];
        }
        if($row->state != MallOrder::STATE_RECEIVE && $row->state != MallOrder::STATE_FINISH  && $row->state != MallOrder::STATE_OVERDUE && $row->state != MallOrder::STATE_ACCOUNT_PERIOD && $row->state != MallOrder::STATE_REMITTANCE_SUPPLIER){
            return ['status'=>1,'data'=>[],'msg'=>'当前订单状态无法申请售后'];
        }

        $goodsModel = new MallOrderGoods();
        $result = $goodsModel->save(['service_type'=>$type],['order_id'=>$row->id,'id'=>$goodsId]);
        if($result !== false){
            $model->save(['service_type'=>1],$where);
            return ['status'=>0,'data'=>[],'msg'=>'服务申请成功'];
        }

        return ['status'=>1,'data'=>[],'msg'=>'服务申请失败'];
    }


}