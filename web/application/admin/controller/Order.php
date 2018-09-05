<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/6/1
 * Time: 14:00
 */

namespace app\admin\controller;

use app\common\model\IndexGroup;
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

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $groupId = getGroupId();
        if($groupId != IndexGroup::GROUP_OPERATION){
            $this->errorTips();
        }
    }


    /**
     * @desc 订单列表
     * @return mixed
     */
    public function index(){
        $k = Request::instance()->get('k','','trim');
        $state = Request::instance()->get('state','-1');
        $start = Request::instance()->get('start','');
        $end = Request::instance()->get('end','');
        $model = new MallOrder();
        $where = [];
        if(isset($k) && $k){
            $where['out_id|buyer'] = ['like','%'.$k.'%'];
        }
        if(isset($state) && $state >= 0){
            if($state == 8){
                $where['service_type'] = ['in',[1,2,3]];
            }else{
                $where['state'] = $state;
            }
        }

        if(isset($start) && $start && isset($end) && $end){
           $where['add_time'] = ['between',[strtotime($start),strtotime($end.' 23:59:59')]];
        }elseif (isset($start) && $start){
            $where['add_time'] = ['gt',strtotime($start)];
        }elseif (isset($end) && $end){
            $where['add_time'] = ['lt',strtotime($end.' 23:59:59')];
        }

        //查询总价
        if($state < 0){
            $totalMoney = $model->where($where)->where(['state'=>['neq',4]])->field(['sum(`actual_money`) AS money'])->find();
        }else{
            $totalMoney = $model->where($where)->field(['sum(`actual_money`) AS money'])->find();
        }


        $rows = $model->where($where)->order('id','desc')->paginate(10,false,['query'=>request()->param()]);
        $userModel = new IndexUser();
        $goodsModel = new MallOrderGoods();
        foreach($rows as &$row){
            $goodsRows = $goodsModel->where(['order_id'=>$row->id])->order('time','desc')->select();
            $userInfo = $userModel->getInfoById($row->supplier);
            $buyerInfo = $userModel->getInfoById($row->buyer_id);

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
            $row['supplierName'] = $userInfo ? $userInfo->real_name : '';
            $row['buyerName'] = $buyerInfo ? $buyerInfo->real_name : '';
            $row['buyerPayInfo'] = $buyerPayInfo;
            $row['supplierPayInfo'] = $supplierPayInfo;
        }

        $this->assign('list',$rows);
        $this->assign('state',$state);
        $this->assign('start',$start);
        $this->assign('end',$end);
        $this->assign('k',$k);
        $this->assign('stateList',MallOrder::getStateList());
        $this->assign('page',$rows->render());
        $this->assign('total',$totalMoney ? ($totalMoney->money ? $totalMoney->money : '0.00'  ): '0.00');
        return $this->fetch();
    }

    /**
     * @desc 核价
     * @param Request $request
     * @param $id
     * @return array
     */
    public function  pricing(Request $request,$id){
        $contract_type = $request->post('contract_type',0,'intval');
        $contractNumber = $request->post('contract_number','');
        $accountPeriod = $request->post('is_account_period',0,'intval');
        $model = new MallOrder();
        $row = $model->where(['id'=>$id])->find();
        if(!$row || $row->state != MallOrder::STATE_PRICING){
            return ['status'=>1,'data'=>[],'msg'=>'数据错误'];
        }
        if($contract_type == 2){
            //有账期 =》 待发货，没账期 => 待采购商打款
            $data = ['contract_number'=>$contractNumber,'is_account_period'=>$accountPeriod,'state'=>$accountPeriod ? MallOrder::STATE_DELIVER : MallOrder::STATE_REMITTANCE];
            $result = $model->save($data,['id'=>$id]);
            if($result == true){
                if($accountPeriod == 1){ //通知供应商发货短信通知 ||查询供应商手机号,发送短信,并记录短信日志
                    $userModel = new IndexUser();
                    $user = $userModel->getInfoById($row->supplier);
                    $yunpian = new Yunpian();
                    $yunpian->send($user->phone,['order_id'=>$row->out_id],Yunpian::TPL_ORDER_PENDING_SEND);

                    //更新消息通知
                    $orderMsgModel = new OrderMsg();
                    $content = "订单号：{$row->out_id}【{$row->goods_names}】工作人员已完成订单审核，下一步等待签约。";
                    //采购商
                    $msgData = ['title'=>"待发货",'content' => $content,'order_no' => $row->out_id,'order_id'=>$row->id,'user_id'=>$row->buyer_id,'create_time'=>time()];
                    $orderMsgModel->save($msgData);
                    $userModel->where(['id'=>$row->buyer_id])->setInc('unread',1);
                    //供应商
                    $msgData = ['title'=>"待发货",'content' => $content,'order_no' => $row->out_id,'order_id'=>$row->id,'user_id'=>$row->supplier,'create_time'=>time()];
                    $orderMsgModel = new OrderMsg();
                    $orderMsgModel->save($msgData);
                    $userModel->where(['id'=>$row->supplier])->setInc('unread',1);
                }
                return ['status'=>0,'data'=>[],'msg'=>'成功核价'];
            }
        }else{ //待签约
            $data = ['state'=>MallOrder::STATE_SIGN];
            $result = $model->save($data,['id'=>$id]);
            if($result == true){
                //更新消息通知
                $orderMsgModel = new OrderMsg();
                $content = "订单号：{$row->out_id}【{$row->goods_names}】工作人员已完成订单审核，下一步等待签约。";
                //采购商
                $msgData = ['title'=>"已核单",'content' => $content,'order_no' => $row->out_id,'order_id'=>$row->id,'user_id'=>$row->buyer_id,'create_time'=>time()];
                $orderMsgModel->save($msgData);
                $userModel = new IndexUser();
                $userModel->where(['id'=>$row->buyer_id])->setInc('unread',1);
                //供应商
                $msgData = ['title'=>"已核单",'content' => $content,'order_no' => $row->out_id,'order_id'=>$row->id,'user_id'=>$row->supplier,'create_time'=>time()];
                $orderMsgModel = new OrderMsg();
                $orderMsgModel->save($msgData);
                $userModel->where(['id'=>$row->buyer_id])->setInc('unread',1);
                return ['status'=>0,'data'=>[],'msg'=>'成功核价'];
            }
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
        $accountPeriod = $request->post('is_account_period',0,'intval');
        $model = new MallOrder();
        $row = $model->where(['id'=>$id])->find();
        if(!$row || $row->state != MallOrder::STATE_SIGN){
            return ['status'=>1,'data'=>[],'msg'=>'数据错误'];
        }

        //有账期 =》 待发货，没账期 => 待采购商打款
        $data = ['contract_number'=>$contractNumber,'is_account_period'=>$accountPeriod,'state'=>$accountPeriod ? MallOrder::STATE_DELIVER : MallOrder::STATE_REMITTANCE];

        $result = $model->save($data,['id'=>$id]);
        if($result == true){
            $userModel = new IndexUser();
            if($accountPeriod == 1){ //通知供应商发货短信通知 ||查询供应商手机号,发送短信,并记录短信日志
                $user = $userModel->getInfoById($row->supplier);
                $yunpian = new Yunpian();
                $yunpian->send($user->phone,['order_id'=>$row->out_id],Yunpian::TPL_ORDER_PENDING_SEND);

                //更新消息通知
                $orderMsgModel = new OrderMsg();
                $content = "订单号：{$row->out_id}【{$row->goods_names}】,现已完成签约，请尽快安排发货。";
                $msgData = ['title'=>'待发货提醒','content' => $content,'order_no' => $row->out_id,'order_id'=>$row->id,'user_id'=>$row->supplier,'create_time'=>time()];
                $orderMsgModel->save($msgData);
                $userModel->where(['id'=>$row->supplier])->setInc('unread',1);
            }else{
                //更新消息通知
                $orderMsgModel = new OrderMsg();
                $content = "订单号：{$row->out_id}【{$row->goods_names}】现已完成签约。";
                $msgData = ['title'=>'订单已签约','content' => $content,'order_no' => $row->out_id,'order_id'=>$row->id,'user_id'=>$row->buyer_id,'create_time'=>time()];
                $orderMsgModel->save($msgData);
                $userModel->where(['id'=>$row->buyer_id])->setInc('unread',1);
            }
            return ['status'=>0,'data'=>[],'msg'=>'签约成功'];
        }
        return ['status'=>1,'data'=>[],'msg'=>'签约失败'];
    }

    /**
     * @desc 收款
     * @param Request $request
     * @param $id
     * @return array
     */
    public function remittance(Request $request,$id){
          $payType = $request->post('pay_type',1);
          $number = $request->post('number','');
          $payTime = $request->post('pay_time','');
          $picture = $request->post('path','');
        //更新状态 未发货状态并短信通知供应商

        $model = new MallOrder();
        $row = $model->where(['id'=>$id])->find();
        if(!$row || $row->state == MallOrder::STATE_PRICING){
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
            $payModel = new MallOrderPay();
            $data =['order_id'=>$id,'pay_type'=>$payType,'number'=>$number,'picture'=>$picture,'create_time'=>date('Y-m-d H:i:s')];
            if($payType == 3){ //转账
                $data['pay_time'] = $payTime;
            }elseif ($payType == 4){ //汇款
                $data['accept_time'] = $payTime;
            }
            $payModel->save($data);
            $userModel = new IndexUser();
            //通知供应商发货短信通知 ||查询供应商手机号,发送短信,并记录短信日志
            if($flag == 1){
                $user = $userModel->getInfoById($row->supplier);
                $yunpian = new Yunpian();
                $yunpian->send($user->phone,['order_id'=>$row->out_id],Yunpian::TPL_ORDER_PENDING_SEND);

                //更新消息通知
                $orderMsgModel = new OrderMsg();
                $content = "订单号：{$row->out_id}【{$row->goods_names}】已完成签约，请尽快安排发货。";
                $msgData = ['title'=>'待发货提醒','content' => $content,'order_no' => $row->out_id,'order_id'=>$row->id,'user_id'=>$row->supplier,'create_time'=>time()];
                $orderMsgModel->save($msgData);
                $userModel->where(['id'=>$row->supplier])->setInc('unread',1);
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
                $content = "订单号：{$row->out_id} 金额：{$row->actual_money},已完成付款。";
                $msgData = ['title'=>"订单款已付",'content' => $content,'order_no' => $row->out_id,'order_id'=>$row->id,'user_id'=>$row->supplier,'create_time'=>time()];
                $orderMsgModel->save($msgData);
                $userModel->where(['id'=>$row->supplier])->setInc('unread',1);
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

        if(!in_array($row->state,[MallOrder::STATE_PRICING,MallOrder::STATE_SIGN,MallOrder::STATE_REMITTANCE])){
            return ['status'=>1,'data'=>[],'msg'=>'不能取消该订单'];
        }

        $result = $model->save(['state'=>MallOrder::STATE_CLOSED],['id'=>$id]);
        if($result !== false){
            $userModel = new IndexUser();
            //更新消息通知
            $orderMsgModel = new OrderMsg();
            $content = "订单号：{$row->out_id}【{$row->goods_names}】已取消该笔订单。";
            //采购商
            $msgData = ['title'=>"订单取消",'content' => $content,'order_no' => $row->out_id,'order_id'=>$row->id,'user_id'=>$row->buyer_id,'create_time'=>time()];
            $orderMsgModel->save($msgData);
            $userModel->where(['id'=>$row->buyer_id])->setInc('unread',1);
            //供应商
            $msgData['user_id'] = $row->supplier;
            $orderMsgModel = new OrderMsg();
            $orderMsgModel->save($msgData);
            $userModel->where(['id'=>$row->supplier])->setInc('unread',1);
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
     * @desc 售后处理
     * @param $id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function service($id){
        $model = new MallOrder();
        $row = $model->where(['id'=>$id])->find();
        if(!$row){
            return ['status'=>1,'data'=>[],'msg'=>'数据错误'];
        }
        if($row->service_type != 1){
            return ['status'=>1,'data'=>[],'msg'=>'不能操作该订单'];
        }

        $result = $model->save(['service_type'=>2],['id'=>$id]);
        if($result == true){
            //更新子订单
            $goodsModel = new MallOrderGoods();
            $goodsModel->save(['service_type'=>0],['order_id'=>$id]);
            return ['status'=>0,'data'=>[],'msg'=>'操作成功'];
        }
        return ['status'=>1,'data'=>[],'msg'=>'操作失败'];
    }

    /**
     * @desc 账期--->逾期
     * @param $id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function overdue($id){
        $model = new MallOrder();
        $row = $model->where(['id'=>$id])->find();
        if(!$row){
            return ['status'=>1,'data'=>[],'msg'=>'数据错误'];
        }
        if($row->state != MallOrder::STATE_ACCOUNT_PERIOD){
            return ['status'=>1,'data'=>[],'msg'=>'不能设置为逾期'];
        }

        $result = $model->save(['state'=>MallOrder::STATE_OVERDUE],['id'=>$id]);
        if($result == true){
            $userModel = new IndexUser();
            //发送短信
            $user = $userModel->getInfoById($row->buyer_id);
            $yunpian = new Yunpian();
            $yunpian->send($user->phone,['order_id'=>$row->out_id],Yunpian::TPL_ORDER_OUT_DATE);

            //更新消息通知
            $orderMsgModel = new OrderMsg();
            $content = "订单号：{$row->out_id} 金额：{$row->actual_money},已逾期支付，请尽快付款。";
            $msgData = ['title'=>'逾期中','content' => $content,'order_no' => $row->out_id,'order_id'=>$row->id,'user_id'=>$row->buyer_id,'create_time'=>time()];
            $orderMsgModel->save($msgData);
            $userModel->where(['id'=>$row->buyer_id])->setInc('unread',1);
            return ['status'=>0,'data'=>[],'msg'=>'操作逾期成功'];
        }
        return ['status'=>1,'data'=>[],'msg'=>'操作失败'];
    }

    /**
     * @desc 导出订单
     */
    public function export($state = -1, $start = '',$k = '',$end = '')
    {
        $model = new MallOrder();
        $where = [];
        if (isset($k) && $k) {
            $where['out_id|buyer'] = ['like', '%' . trim($k) . '%'];
        }
        if (isset($state) && $state >= 0) {
            $where['state'] = $state;
        }
        if(isset($state) && $start && isset($end) && $end){
            $where['add_time'] =[['lt', strtotime($end . ' 23:59:59')],['gt', strtotime($start)],'and'] ;
        }elseif (isset($state) && $start){
            $where['add_time'] = ['gt', strtotime($start)];
        }elseif (isset($end) && $end){
            $where['add_time'] = ['lt', strtotime($end . ' 23:59:59')];
        }
        vendor('PHPExcel.PHPExcel');
        $objPHPExcel = new \PHPExcel();
        $objPHPExcel->setActiveSheetIndex(0);
        //设置表头
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', '下单时间')
            ->setCellValue('B1', '订单号')
            ->setCellValue('C1', '订单状态')
            ->setCellValue('D1', '采购商')
            ->setCellValue('E1', '用户名')
            ->setCellValue('F1', '供应商')
            ->setCellValue('G1', '商品名称')
            ->setCellValue('H1', '商品规格')
            ->setCellValue('I1', '数量')
            ->setCellValue('J1', '单价')
            ->setCellValue('K1', '小计')
            ->setCellValue('L1', '物料编号')
            ->setCellValue('M1', '物料规格')
            ->setCellValue('N1', '买家留言')
            ->setCellValue('O1', '合同编号')
            ->setCellValue('P1', '是否账期支付')
            ->setCellValue('Q1', '账期截止')
            ->setCellValue('R1', '总价');

        //查询数据
        $total = $model->where($where)->count();
        $pageSize = 100;
        $page = ceil($total / $pageSize);

        $counter = 2;
        $userModel = new IndexUser();
        $goodsModel = new MallOrderGoods();

        $objPHPExcel->getDefaultStyle()->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);

        //设置宽度
        $objPHPExcel->getActiveSheet(0)->getColumnDimension('A')->setWidth(16);
        $objPHPExcel->getActiveSheet(0)->getColumnDimension('B')->setWidth(16);
        $objPHPExcel->getActiveSheet(0)->getColumnDimension('B')->setWidth(16);
        $objPHPExcel->getActiveSheet(0)->getColumnDimension('D')->setWidth(20);
        $objPHPExcel->getActiveSheet(0)->getColumnDimension('E')->setWidth(20);
        $objPHPExcel->getActiveSheet(0)->getColumnDimension('F')->setWidth(20);
        $objPHPExcel->getActiveSheet(0)->getColumnDimension('G')->setWidth(25);
        $objPHPExcel->getActiveSheet(0)->getColumnDimension('H')->setWidth(15);

        for ($i = 0; $i < $page; $i++) {
            $start = $pageSize * $i;
            $rows = $model->where($where)->limit($start, $pageSize)->order('add_time', 'desc')->select();
            foreach ($rows as $row) {
                $buyerInfo = $userModel->getInfoById($row->buyer_id);
                $supplier = $userModel->getInfoById($row->supplier);
                //查询订单商品
                $goodsRows = $goodsModel->where(['order_id' => $row->id])->select();
                $goodsCount = count($goodsRows);
                $orderStart = $counter;
                $orderEnd = $counter + $goodsCount - 1;

                //合并单元格
//                if ($orderEnd > $orderStart) {
//                    $objPHPExcel->setActiveSheetIndex(0)->mergeCells('A' . $orderStart . ':A' . $orderEnd);
//                    $objPHPExcel->setActiveSheetIndex(0)->mergeCells('B' . $orderStart . ':B' . $orderEnd);
//                    $objPHPExcel->setActiveSheetIndex(0)->mergeCells('C' . $orderStart . ':C' . $orderEnd);
//                    $objPHPExcel->setActiveSheetIndex(0)->mergeCells('D' . $orderStart . ':D' . $orderEnd);
//                    $objPHPExcel->setActiveSheetIndex(0)->mergeCells('E' . $orderStart . ':E' . $orderEnd);
//                    $objPHPExcel->setActiveSheetIndex(0)->mergeCells('F' . $orderStart . ':F' . $orderEnd);
//
//                    $objPHPExcel->setActiveSheetIndex(0)->mergeCells('N' . $orderStart . ':N' . $orderEnd);
//                    $objPHPExcel->setActiveSheetIndex(0)->mergeCells('O' . $orderStart . ':O' . $orderEnd);
//                    $objPHPExcel->setActiveSheetIndex(0)->mergeCells('P' . $orderStart . ':P' . $orderEnd);
//                    $objPHPExcel->setActiveSheetIndex(0)->mergeCells('Q' . $orderStart . ':Q' . $orderEnd);
//                    $objPHPExcel->setActiveSheetIndex(0)->mergeCells('R' . $orderStart . ':R' . $orderEnd);
//
//                }
                foreach ($goodsRows as $goodsRow) {
                    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A' . $counter, date('Y-m-d H:i', $row->add_time));
                    $objPHPExcel->setActiveSheetIndex(0)->setCellValueExplicit('B'.$counter,$row->out_id,\PHPExcel_Cell_DataType::TYPE_STRING);
                    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('C' . $counter, getOrderState($row->state));
                    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('D' . $counter, $buyerInfo ? $buyerInfo->real_name : '');
                    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('E' . $counter, $buyerInfo ? $buyerInfo->nickname : '');
                    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('F' . $counter, $supplier ? $supplier->real_name : '');
                    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('G' . $counter, $goodsRow->title);
                    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('H' . $counter, $goodsRow->s_info);
                    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('I' . $counter, $goodsRow->quantity);
                    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('J' . $counter, '¥' . $goodsRow->price);
                    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('K' . $counter, '¥' . $goodsRow->quantity * $goodsRow->price);
                    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('L' . $counter, $goodsRow->specifications_no);
                    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('M' . $counter, $goodsRow->specifications_name);
                    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('N' . $counter, $row->buyer_comment);
                    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('O' . $counter, $row->contract_number);
                    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('P' . $counter, $row->pay_date ? '是' : '否');
                    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('Q' . $counter, $row->pay_date ? substr($row->pay_date, 0, 10) : '');
                    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('R' . $counter, '¥' .$row->actual_money);

                    $counter++;
                    unset($goodsRows);
                }

                unset($rows);
            }
        }
        $filename = 'order_' . date('YmdHi', time()) . '.xls';
        $objPHPExcel->getActiveSheet()->setTitle('商品订单信息');
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header('Content-Disposition:inline;filename="' . $filename . '"');
        //生成excel文件
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }

    /**
     * @desc 调价
     * @param $id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function adjustPrice($id){
        //接收参数
        $goods = Request::instance()->post('goods/a');

        //获取总价
        $totalMoney = 0;
        foreach ($goods as $orderGoodsId => $item){
            $totalMoney += $item['price']*$item['quantity'];
        }
        //提取数据

        $model = new MallOrder();
        $row = $model->where(['id'=>$id])->find();
        if(!$row){
            return ['status'=>1,'data'=>[],'msg'=>'数据错误'];
        }

        //更新order_goods数据
        $goodsModel = new MallOrderGoods();
        foreach($goods as $orderGoodsId => $item){
            $goodsModel->save(['price'=> $item['price'],'quantity'=>$item['quantity']],['id'=>$orderGoodsId]);
        }
        //更新order价格
        $orderModel = new MallOrder();
        $orderModel->save(['actual_money'=>$totalMoney,'sum_money'=>$totalMoney],['id'=>$id]);
        //exit;
        return ['status'=>0,'data'=>[],'msg'=>'操作失败'];
    }

    /**
     * @desc 获取订单商品列表
     * @param $id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getOrderProductList($id){
        $model = new MallOrder();
        $row = $model->where(['id'=>$id])->find();
        if(!$row){
            return ['status'=>1,'data'=>[],'msg'=>'数据错误'];
        }
        $goodsModel = new MallOrderGoods();

        $rows = $goodsModel->alias('a')->join('mall_goods b','a.goods_id=b.id','left')->where(['a.order_id'=>$id])->order('a.id','asc')->field(['b.icon','a.id','a.title','a.price','a.s_info','a.quantity'])->select();
        foreach ($rows as &$row){
            $row['iconPath'] = MallGoods::getFormatImg($row->icon);
            $row['quantity'] = intval($row->quantity);
        }

        return ['status'=>0,'data'=>$rows,'msg'=>''];
    }

    /**
     * @desc  设置账期
     * @param $id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function setPayday($id){
        $date = Request::instance()->post('date');
        $model = new MallOrder();
        $row = $model->where(['id'=>$id])->find();
        if(!$row){
            return ['status'=>1,'data'=>[],'msg'=>'数据错误'];
        }
        //设置账期日期
        $result = $model->save(['pay_date'=>$date],['id'=>$id]);
        if($result !== false){
            return ['status'=>0,'data'=>[],'msg'=>'设置成功'];
        }
        return ['status'=>1,'data'=>[],'msg'=>'设置失败'];
    }

}
