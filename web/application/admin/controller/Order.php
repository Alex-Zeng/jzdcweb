<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/6/1
 * Time: 14:00
 */

namespace app\admin\controller;

use app\common\model\IndexUser;
use app\common\model\MallGoods;
use app\common\model\MallOrder;
use app\common\model\MallOrderGoods;
use app\common\model\MallOrderPay;
use app\common\model\MallShopFinance;
use app\common\model\OrderMsg;
use sms\Yunpian;
use think\Request;

class Order extends Base{

    /**
     * @desc 订单列表
     * @return mixed
     */
    public function index(){
        $k = Request::instance()->get('k','');
        $state = Request::instance()->get('state','');
        $model = new MallOrder();
        if(isset($k) && $k){
            $model->where('out_id|buyer','like','%'.$k.'%');
        }
        if(isset($state) && $state != ''){
            $model->where(['state' => $state]);
        }

        $rows = $model->order('id','desc')->paginate(10,false,['query'=>request()->param()]);
        $goodsModel = new MallOrderGoods();
        foreach($rows as &$row){
            $goodsRows = $goodsModel->where(['order_id'=>$row->id])->order('time','desc')->select();
            $total = 0;
            foreach ($goodsRows as & $goodsRow){
                $productModel = new MallGoods();
                $productRow = $productModel->where(['id'=>$goodsRow->goods_id])->find();
                $path = $productRow ? $productRow->icon : '';
                $goodsRow['icon'] = MallGoods::getFormatImg($path);

                $total += $goodsRow->price * $goodsRow->quantity;
            }
            $row['total'] = $total;
            $row['goods'] = $goodsRows;

            //支付信息
            $buyerPayInfo = $supplierPayInfo = [];
            $payModel = new MallOrderPay();
            $payRows = $payModel->where(['order_id'=>$row->id])->field(['pay_type','number','picture','pay_time','accept_time'])->select();
            foreach ($payRows as $payRow){
                $payRow['picture'] = MallOrderPay::getFormatPicture($payRow->picture);
                if($payRow->pay_type == 1 || $payRow->pay_type == 2){
                    $buyerPayInfo[] = $payRow;
                }elseif ($payRow->pay_type == 3 || $payRow->pay_type == 4){
                    $supplierPayInfo[] = $payRow;
                }
            }
            $row['buyerPayInfo'] = $buyerPayInfo;
            $row['supplierPayInfo'] = $supplierPayInfo;
        }

        $this->assign('list',$rows);
        $this->assign('state',$state);
        $this->assign('k',$k);
        $this->assign('stateList',MallOrder::getStateList());
        $this->assign('page',$rows->render());
        return $this->fetch();
    }

    /**
     * @desc 核价
     * @param Request $request
     * @param $id
     * @return array
     */
    public function  pricing(Request $request,$id){
       // contract_number
        //pay_date
        //sum_money    received_money  goods_money
        $contractNumber = $request->post('contract_number','');
        $payDate = $request->post('pay_date','');
        $sumMoney = $request->post('sum_money',0);
        $model = new MallOrder();
        $row = $model->where(['id'=>$id])->find();
        if(!$row || $row->state != MallOrder::STATE_PRICING){
            return ['status'=>1,'data'=>[],'msg'=>'数据错误'];
        }
        //有账期 =》 待发货，没账期 => 待采购商打款
        $data = ['contract_number'=>$contractNumber,'pay_date'=>$payDate,'sum_money'=>$sumMoney,'state'=>$payDate ? MallOrder::STATE_DELIVER : MallOrder::STATE_REMITTANCE];
        $result = $model->save($data,['id'=>$id]);
        if($result == true){
            if($payDate){ //通知供应商发货短信通知 ||查询供应商手机号,发送短信,并记录短信日志
                  $userModel = new IndexUser();
                  $user = $userModel->getInfoById($row->supplier);
                  $yunpian = new Yunpian();
                 // $yunpian->send($user->phone,['order_id'=>$row->out_id],Yunpian::TPL_ORDER_PENDING_SEND);

                //更新消息通知
                $orderMsgModel = new OrderMsg();
                $content = "您好，订单号：{$row->out_id}现可安排发货，发货完成后，请在\"用户中心-待发货\"发布物流信息，谢谢。";
                $msgData = ['title'=>"待发货",'content' => $content,'order_no' => $row->out_id,'order_id'=>$row->id,'user_id'=>$row->buyer_id,'create_time'=>time()];
                $orderMsgModel->save($msgData);
            }
            return ['status'=>0,'data'=>[],'msg'=>'成功核价'];
        }
        return ['status'=>1,'data'=>[],'msg'=>'失败核价'];
    }

    /**
     * @desc 签约
     * @param Request $request
     * @param $id
     * @return array
     */
    public function sign(Request $request,$id){
        $contractNumber = $request->post('contract_number','');
        $payDate = $request->post('pay_date','');
        $model = new MallOrder();
        $row = $model->where(['id'=>$id])->find();
        if(!$row || $row->state != MallOrder::STATE_PRICING){
            return ['status'=>1,'data'=>[],'msg'=>'数据错误'];
        }
        //有账期 =》 待发货，没账期 => 待采购商打款
        $data = ['contract_number'=>$contractNumber,'pay_date'=>$payDate,'state'=>$payDate ? MallOrder::STATE_DELIVER : MallOrder::STATE_REMITTANCE];
        $result = $model->save($data,['id'=>$id]);
        if($result == true){
            if($payDate){ //通知供应商发货短信通知 ||查询供应商手机号,发送短信,并记录短信日志
                $userModel = new IndexUser();
                $user = $userModel->getInfoById($row->supplier);
                $yunpian = new Yunpian();
                // $yunpian->send($user->phone,['order_id'=>$row->out_id],Yunpian::TPL_ORDER_PENDING_SEND);

                //更新消息通知
                $orderMsgModel = new OrderMsg();
                $statusList = MallOrder::getStateList();
                $content = "您好，订单号：{$row->out_id}现可安排发货，发货完成后，请在\"用户中心-待发货\"发布物流信息，谢谢。";
                $msgData = ['title'=>$statusList[$row->state],'content' => $content,'order_no' => $row->out_id,'order_id'=>$row->id,'user_id'=>$row->buyer_id,'create_time'=>time()];
                $orderMsgModel->save($msgData);
            }
            return ['status'=>0,'data'=>[],'msg'=>'成功核价'];
        }
        return ['status'=>1,'data'=>[],'msg'=>'失败核价'];
    }

    /**
     * @desc 收款
     * @param Request $request
     * @param $id
     * @return array
     */
    public function remittance(Request $request,$id){
          $tag = $request->post('tag',1);
          $payType = $request->post('type',1);
          $number = $request->post('number','');
          $payTime = $request->post('pay_time','');
          $picture = $request->post('path','');
        //更新状态 未发货状态并短信通知供应商

        $model = new MallOrder();
        $row = $model->where(['id'=>$id])->find();
        if(!$row || $row->state != MallOrder::STATE_PRICING){
            return ['status'=>1,'data'=>[],'msg'=>'数据错误'];
        }

        //根据当前状态进行识别，更新状态
        $data = ['state'=> MallOrder::STATE_DELIVER ];
        $flag = 1;
        if($row->state == MallOrder::STATE_ACCOUNT_PERIOD || $row->state == MallOrder::STATE_OVERDUE){
            $data['state'] = MallOrder::STATE_REMITTANCE_SUPPLIER;
            $flag = 2;
        }elseif ($row->state == MallOrder::STATE_REMITTANCE_SUPPLIER){
            $data['state'] = MallOrder::STATE_FINISH;
            $flag = 3;
        }

        $result = $model->save($data,['id'=>$id]);
        if($result == true){
            //jzdc_mall_order_pay
            $payModel = new MallOrderPay();
            $data =['order_id'=>$id,'pay_type'=>$payType,'number'=>$number,'picture'=>$picture,'create_time'=>date('Y-m-d H:i:s')];
            if($payType == 3){ //转账
                $data['pay_time'] = $payTime;
            }elseif ($payType == 4){ //汇款
                $data['accept_time'] = $payTime;
            }
            $payModel->save($data);

            //通知供应商发货短信通知 ||查询供应商手机号,发送短信,并记录短信日志
            if($flag == 1){
                $userModel = new IndexUser();
                $user = $userModel->getInfoById($row->supplier);
                $yunpian = new Yunpian();
                // $yunpian->send($user->phone,['order_id'=>$row->out_id],Yunpian::TPL_ORDER_PENDING_SEND);

                //更新消息通知
                $orderMsgModel = new OrderMsg();
                $statusList = MallOrder::getStateList();
                $content = "您好，订单号：{$row->out_id}现可安排发货，发货完成后，请在\"用户中心-待发货\"发布物流信息，谢谢。";
                $msgData = ['title'=>$statusList[$row->state],'content' => $content,'order_no' => $row->out_id,'order_id'=>$row->id,'user_id'=>$row->buyer_id,'create_time'=>time()];
                $orderMsgModel->save($msgData);
            }
            if($flag == 3){
                 //更新店铺财务记录
                   $financeModel = new MallShopFinance();
                   $financeRow = $financeModel->where(['shop_id'=>$row->shop_id])->order('id','desc')->field(['after_money'])->find();
                   $reason = "收到{$row->out_id}号订单费用{$row->actual_money}元";
                   $beforeMoney  = $financeRow ? $financeRow->after_money : 0;
                   $financeData = ['type'=>9,'time'=>time(),'money'=>$row->actual_money,'before_money'=>$beforeMoney,'after_money'=>$beforeMoney+$row->actual_money,'operator'=>getUserName(),'reason'=>$reason,'shop_id'=>$row->shop_id];
                   $financeModel->save($financeData);

                //更新交易统计量
                  $orderGoodsModel = new MallOrderGoods();
                  $orderGoodsRows = $orderGoodsModel->where(['order_id'=>$row->id])->field(['quantity','goods_id'])->select();
                  foreach ($orderGoodsRows as $orderGoodsRow){
                      $goodsModel = new MallGoods();
                      $goodsModel->where(['id'=>$orderGoodsRow->goods_id])->setInc('sold',$orderGoodsRow->quantity);
                  }
                //更新店内会员 订单统计
//                function update_shop_buyer($pdo,$table_pre,$order){
//                    $sql="select count(id) as c,sum(`actual_money`) as c2 from ".self::$table_pre."order where `shop_id`=".$order['shop_id']." and `buyer`='".$order['buyer']."' and `state`=6";
//                    $r=$pdo->query($sql,2)->fetch(2);
//                    $sql="update ".self::$table_pre."shop_buyer set `money`='".$r['c2']."',`order`='".$r['c']."' where `shop_id`=".$order['shop_id']." and `username`='".$order['buyer']."'";
//                    $pdo->exec($sql);
//
//                }

                //更新消息通知
                $orderMsgModel = new OrderMsg();
                $content = "尊敬的供应商用户，订单号：{$row->out_id}的款项已经汇出,实际到账时间依您的银行通知为准，详细汇款信息请进入\"用户中心-待收款\"中查看，谢谢。";
                $msgData = ['title'=>"打款给供应商",'content' => $content,'order_no' => $row->out_id,'order_id'=>$row->id,'user_id'=>$row->supplier,'create_time'=>time()];
                $orderMsgModel->save($msgData);

            }
            return ['status'=>0,'data'=>[],'msg'=>'成功核价'];
        }
        return ['status'=>1,'data'=>[],'msg'=>'失败核价'];
    }

    /**
     * @desc 取消订单
     * @param $id
     * @return array
     */
    public function cancel($id){
       //查询订单
        $model = new MallOrder();
        $row = $model->where(['id'=>$id])->find();
        if(!$row){
            return ['status'=>1,'data'=>[],'msg'=>'数据错误'];
        }
        if($row->state != MallOrder::STATE_PRICING || $row->state != MallOrder::STATE_SIGN || $row->state !=MallOrder::STATE_REMITTANCE){
            return ['status'=>1,'data'=>[],'msg'=>'不能取消该订单'];
        }

        $result = $model->save(['state'=>MallOrder::STATE_SIGN],['id'=>$id]);
        if($result == true){
            //更新消息通知
            $orderMsgModel = new OrderMsg();
            $content = "尊敬的用户，经与您确认，订单号：{$row->out_id}现已取消交易，感谢您的使用。";
            //采购商
            $msgData = ['title'=>"关闭订单",'content' => $content,'order_no' => $row->out_id,'order_id'=>$row->id,'user_id'=>$row->buyer,'create_time'=>time()];
            $orderMsgModel->save($msgData);
            //供应商
            $msgData['user_id'] = $row->supplier;
            $orderMsgModel->save($msgData);

            return ['status'=>0,'data'=>[],'msg'=>'成功取消订单'];
        }
        return ['status'=>1,'data'=>[],'msg'=>'失败取消订单'];
    }

    /**
     * @desc 问题解决
     * @param $id
     * @return array
     */
    public function problem($id){
        $model = new MallOrder();
        $row = $model->where(['id'=>$id])->find();
        if(!$row){
            return ['status'=>1,'data'=>[],'msg'=>'数据错误'];
        }
        if($row->state != MallOrder::STATE_PROBLEM_CONFIRM){
            return ['status'=>1,'data'=>[],'msg'=>'不能取消该订单'];
        }

        $result = $model->save(['state'=>MallOrder::STATE_QUALITY_CHECK],['id'=>$id]);
        if($result == true){
            return ['status'=>0,'data'=>[],'msg'=>'操作成功'];
        }
        return ['status'=>1,'data'=>[],'msg'=>'操作失败'];
    }


    /**
     * @desc 导出订单
     */
    public function export(){
        vendor('PHPExcel.PHPExcel');
        $objPHPExcel = new \PHPExcel();
        $objPHPExcel->setActiveSheetIndex(0);
        //设置表头
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', 'ID')
            ->setCellValue('B1', '订单号')
            ->setCellValue('C1', '商品')
            ->setCellValue('D1', '数量')
            ->setCellValue('E1', '采购商')
            ->setCellValue('F1', '供应商');

        //查询数据


        $filename = '商品订单信息'.date('Ymd',time()).'.xls';
        $objPHPExcel->getActiveSheet()->setTitle('商品订单信息');


        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header('Content-Disposition:inline;filename="'.$filename.'"');
        //生成excel文件
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }
}
