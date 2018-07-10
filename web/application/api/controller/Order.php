<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/6/5
 * Time: 16:10
 */
namespace app\api\controller;

use app\common\model\Counter;
use app\common\model\IndexGroup;
use app\common\model\IndexUser;
use app\common\model\IndexArea;
use app\common\model\MallGoods;
use app\common\model\MallGoodsSpecifications;
use app\common\model\MallOrder;
use app\common\model\MallOrderGoods;
use app\common\model\MallReceiver;
use app\common\model\MallTypeOption;
use app\common\model\UserGoodsSpecifications;
use think\Request;

class Order extends Base{

    /**
     * @desc 生成订单
     * @param Request $request
     * @return array
     */
    public function make(Request $request){
//        $jsonStr = '{"receiverId": 81,"detail":[{"list":[{"goods_id":"169","option_id":"8","color_id":"7","quantity":"12","no":"SX00001","requirement":"测试商品名称A"}],"date": "2018-06-12","remark": "请尽快发货"}]}';
//        $jsonArr = json_decode($jsonStr,true);
//        $receiverId = $jsonArr['receiverId'];
//        $detailRows = $jsonArr['detail'];
        $receiverId = $request->post('receiverId',0,'intval');
        $channel = $request->post('channel',0,'intval');
        $detailRows = $request->post('detail','');
       // $detailRows = '[{"list":[{"goodsId":"169","option_id":"8","color_id":"7","quantity":"12","no":"SX00001","requirement":"测试商品名称A"}],"date": "2018-06-12","remark": "请尽快发货"}]';
        $detailRows = $detailRows ? json_decode($detailRows,true) : [];

        if($receiverId <= 0 || !$detailRows){
            return ['status'=>1,'data'=>[],'msg' => '数据异常'];
        }

        $auth = $this->auth();
        if($auth){
            return $auth;
        }

        if($this->groupId != IndexGroup::GROUP_BUYER){
            return ['status'=>1,'data'=>[],'msg'=>'没有权限下单'];
        }

        //根据购物清单分商家生成订单
        $goodsRows = [];
        $model = new MallGoods();
        $specificationsModel = new MallGoodsSpecifications();
        $typeOptionModel = new MallTypeOption();

        foreach ($detailRows as $detailRow){
            foreach ($detailRow['list'] as $detailList){
                $row = $model->where(['id'=>$detailList['goodsId']])->field(['id','title','supplier','w_price','unit','icon'])->find();
                if(!$row){
                    continue;
                }
                $specificationsRow = [];
                if(isset($detailList['color_id']) && isset($detailList['option_id'])){
                    $specificationsRow = $specificationsModel->where(['color_id'=>$detailList['color_id'],'option_id'=>$detailList['option_id'],'goods_id'=>$detailList['goodsId']])->find();
                }
                $goodsRows[] = [
                    'supplier' => $row->supplier,
                    'goods_id' => $detailList['goodsId'],
                    'price' => $specificationsRow ? $specificationsRow->w_price : $row->w_price,
                    'cost_price' => $specificationsRow ? $specificationsRow->cost_price : $row->w_price,
                    's_id' => $specificationsRow ? $specificationsRow->id : 0,
                    'title'=>$row->title,
                    'unit'=>$row->unit,
                    'quantity'=>$detailList['quantity'],
                    'date' => $detailRow['date'],
                    'remark' => $detailRow['remark'],
                    'no' => $detailList['no'],
                    'requirement' => $detailList['requirement'],
                    'color_name' => $specificationsRow ? $specificationsRow->color_name : '',
                    'option_id' => $specificationsRow ? $specificationsRow->option_id : '',
                    'icon'=>$row->icon
                ];
            }
        }

        //获取数据列表，根据供应商进行分类
        $supplierGroup = [];
        foreach($goodsRows as $row){
            $specificationsInfo = $row['color_name'] ? $row['color_name'] : '';
            if($row['option_id'] > 0){
                $typeOptionRow = $typeOptionModel->where(['id'=>$row['option_id']])->find();
                if($typeOptionRow){
                    $specificationsInfo .=','.$typeOptionRow->name;
                }
            }

            $supplierGroup[$row['supplier']][] = [
                'goods_id'=>$row['goods_id'],
                'title'=>$row['title'],
                'unit'=>$row['unit'],
                'price'=>$row['price'],
                'cost_price'=>$row['cost_price'],
                's_id'=>$row['s_id'],
                'quantity'=>$row['quantity'],
                'date'=>$row['date'],
                'remark'=>$row['remark'],
                'no' => $row['no'],
                'requirement' => $row['requirement'],
                'specificationsInfo' => $specificationsInfo,
                'icon' =>$row['icon']
            ];
        }
        //循环遍历
        $orderList = [];
        foreach ($supplierGroup as $supplierId => $items){
            $totalPrice = $quantity  = $costPrice= 0;
            $goodsName = '';
            foreach($items as $item){
                $totalPrice += $item['price']*$item['quantity'];
                $costPrice += $item['cost_price']*$item['quantity'];
                $quantity += $item['quantity'];
                $goodsName .= $item['title'].',';
            }

            $orderList[$supplierId] = [
                'total_price' => $totalPrice,
                'cost_price' => $costPrice,
                'quantity' => $quantity,
                'goods_name' => $goodsName ? substr($goodsName,0,strlen($goodsName)-1) : '',
                'date' => isset($items[0]['date']) ? $items[0]['date'] : '',
                'remark' => isset($items[0]['remark']) ? $items[0]['remark'] : '',
                'list' => $items,
            ];
        }

        $return = [];

        $receiverModel = new MallReceiver();
        $receiverRow = $receiverModel->where(['id'=>$receiverId])->find();
        $areaModel = new IndexArea();
        $areaList = $areaModel->getAreaInfo($receiverRow->area_id);
        if($areaList){
            array_pop($areaList);
        }
        $areaInfo = $areaList ?  implode(' ',array_reverse($areaList)) : '';

        $userModel = new IndexUser();
        $userInfo = $userModel->getInfoById($this->userId);

        //生成订单
        foreach ($orderList as $index => $order){
            $orderModel = new MallOrder();
            $orderNo = getOrderOutId($channel);
            $orderValue = [
                'shop_id' => 1,
                'receiver_area_name' => $areaInfo,
                'last_time' => time(),
                'goods_money' => $order['total_price'],
                'actual_money' => $order['total_price'],
                'cashier' => 'jzdc',
                'buyer' => $userInfo ? $userInfo->username : '',
                'add_time' => time(),
                'received_money' => $order['total_price'],
                'state' => MallOrder::STATE_PRICING,
                'delivery_time' => strtotime($order['date']),
                'buyer_remark' => $order['remark'],
                'sum_money' => $order['total_price'],
                'receiver_name' => $receiverRow ? $receiverRow->name : '',
                'receiver_phone' => $receiverRow ? $receiverRow->phone : '',
                'receiver_area_id' => $receiverRow ? $receiverRow->area_id : 0,
                'receiver_detail' => $receiverRow ? $receiverRow->detail : '',
                'receiver_post_code' => $receiverRow ? $receiverRow->post_code : 0, //
                'goods_names' => $order['goods_name'],
                'goods_cost' => $order['cost_price'],  //成本
                'goods_count' => $order['quantity'],
                'out_id' => $orderNo,
                'supplier' => $index,
                'buyer_comment' => $order['remark'],
                'buyer_id' => $this->userId
            ];

            $orderGoodsModel = new MallOrderGoods();
            $userGoodSpecificationsModel = new UserGoodsSpecifications();
            $cartModel = new \app\common\model\MallCart();
            $result = $orderModel->save($orderValue);
            if($result == true){
                //插入order_goods数据表
                $counterModel = new Counter();
                $counterModel->where(['id'=>1])->setInc('order_count',1);
                $orderGoods = [];
                $returnGoodsList = [];
                foreach ($order['list'] as $goodsList){
                    $orderGoods[] = [
                        'buyer' => $userInfo ? $userInfo->username : '',
                        'order_id' =>  $orderModel->id,
                        'order_state' => MallOrder::STATE_PRICING,
                        'icon' => $goodsList['icon'],
                        'title' => $goodsList['title'],
                        'price' => $goodsList['price'],
                        's_id' => $goodsList['s_id'],
                        'shop_id' =>1,
                        'snapshot_id' => 0,
                        'quantity' => $goodsList['quantity'],
                        'goods_id' => $goodsList['goods_id'],
                        'unit' => '',
                        'time' => time(),
                        'cost_price' => $goodsList['cost_price'],
                        'buyer_id' => $this->userId,
                        'specifications_no' => $goodsList['no'],
                        'specifications_name' => $goodsList['requirement'],
                    ];

                    $returnGoodsList[] = [
                        'goods_id' => $goodsList['goods_id'],
                        'title' => $goodsList['title'],
                        'quantity' => $goodsList['quantity'],
                        'price' => $goodsList['price'],
                        'no' => $goodsList['no'],
                        'icon' => MallGoods::getFormatImg($goodsList['icon']),
                        'requirement' => $goodsList['requirement'],
                        'specificationsInfo' => $goodsList['specificationsInfo'],
                    ];
                }

                $result2 = $orderGoodsModel->insertAll($orderGoods);
                if($result2){
                    $supplerInfo = $userModel->getInfoById($index);

                    //返回数据
                    $return[] = [
                        'orderNo' =>  $orderNo,
                        'date' => $order['date'],
                        'goods' => $returnGoodsList,
                        'remark' => $order['remark'],
                        'supplierName' => $supplerInfo ? $supplerInfo->real_name : '',
                    ];
                    foreach ($order['list'] as $list){
                        $specificationsWhere = ['user_id'=>$this->userId,'goods_id'=>$list['goods_id'],'specifications_id'=>$list['s_id']];
                        $exist = $userGoodSpecificationsModel->where($specificationsWhere)->find();
                        if($exist){
                            $userGoodSpecificationsModel->save(['specifications_no'=>$list['no'],'specifications_name'=>$list['requirement'],'update_time'=>time()],$specificationsWhere);
                        }else{
                            $specificationsWhere['specifications_no'] = $list['no'];
                            $specificationsWhere['specifications_name'] = $list['requirement'];
                            $specificationsWhere['create_time'] = time();
                            $userGoodSpecificationsModel->save($specificationsWhere);
                        }
                        //删除购物清单 同步操作,
                        $cartModel->where(['user_id'=>$this->userId,'goods_id'=>$list['goods_id'],'goods_specifications_id'=>$list['s_id']])->delete();
//                        if($cartRow){
//                            if($cartRow['quantity'] <= $list['quantity']){
//                                $cartModel->where(['user_id'=>$this->userId,'goods_id'=>$list['goods_id']])->delete();
//                            }else{
//                                //同一用户不考虑扣减为负，并发量没那么高
//                                $cartModel->where(['user_id'=>$this->userId,'goods_id'=>$list['goods_id']])->setDec('quantity',$list['quantity']);
//                            }
//                        }
                    }
                }

                //添加日志

                //触发消息通知
            }
        }


        return ['status'=>0,'data'=>$return,'msg'=>'订单生成成功'];
    }


    /**
     * @desc 订单状态
     * @return array
     */
    public function getStatus(){
        $statusList = MallOrder::getStateList();
        $statusList[-1] = '全部';
        ksort($statusList);
        return ['status'=>0,'data'=>$statusList,'msg'=>''];
    }

    /**
     * @desc 订单列表
     * @param Request $request
     * @return array|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getList(Request $request){
        $status = $request->post('status',-1,'intval');
        $pageSize = $request->post('pageSize',10,'intval');
        $pageNumber = $request->post('pageNumber',1,'intval');

        if($pageSize > 12){ $pageSize = 12;}
        $start = ($pageNumber - 1)*$pageSize;
        $end = $pageNumber*$pageSize;

        $auth = $this->auth();
        if($auth){
            return $auth;
        }

        $orderModel = new MallOrder();
        $orderGoodsModel = new MallOrderGoods();

        if($this->groupId != IndexGroup::GROUP_BUYER && $this->groupId != IndexGroup::GROUP_SUPPLIER && $this->groupId != IndexGroup::GROUP_MEMBER){
            return ['status'=>1,'data'=>[],'msg'=>'没有权限'];
        }

        $where = [];
        if($this->groupId == IndexGroup::GROUP_BUYER){
            $where['buyer_id'] = $this->userId;
        }else{
            $where['supplier'] = $this->userId;
        }

        if($status != '-1'){
            $where['state'] = $status;
        }
        $count = $orderModel->where($where)->count();
        $rows = $orderModel->where($where)->order('add_time','desc')->limit($start,$end)->field(['id','state','out_id','receiver_name','supplier','buyer_id','service_type'])->select();
        $userModel = new IndexUser();
        foreach ($rows as &$row){
            $userInfo = [];
            if($this->groupId  == IndexGroup::GROUP_SUPPLIER){
                $userInfo = $userModel->getInfoById($row->buyer_id);
            }elseif ($this->groupId == IndexGroup::GROUP_BUYER){
                $userInfo = $userModel->getInfoById($row->supplier);
            }
            $row['companyName']  = $userInfo ? $userInfo->real_name : '';
            $row['groupId'] = $this->groupId;

            $goodsRows = $orderGoodsModel->alias('a')->join(config('prefix').'mall_goods b','a.goods_id=b.id','left')->where(['order_id'=>$row->id])->field(['a.title','a.price','a.quantity','a.specifications_no','a.specifications_name','b.icon','a.s_id'])->select();
            $specificationModel = new MallGoodsSpecifications();

            foreach($goodsRows as &$goodsRow){
                $goodsRow['quantity'] = intval($goodsRow->quantity);
                $goodsRow['icon'] = MallGoods::getFormatImg($goodsRow->icon);
                $goodsRow['price'] = getFormatPrice($goodsRow->price);

                $specifications_info = '';
                if($goodsRow->s_id > 0){
                     $specificationRow = $specificationModel->where(['id'=>$goodsRow->s_id])->find();
                     if($specificationRow && $specificationRow->color_name){
                         $specifications_info = $specificationRow->color_name;
                         if($specificationRow->option_id > 0){
                             $optionModel = new MallTypeOption();
                             $optionRow = $optionModel->where(['id'=>$specificationRow->option_id])->find();
                             if($optionRow && $optionRow->name){
                                 $specifications_info .=','.$optionRow->name;
                             }
                         }
                     }
                     //查询规格
                }
                $goodsRow['specifications_info'] = $specifications_info;

            }
            $row['goods'] = $goodsRows;
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
        //订单号
        $model = new MallOrder();
        $where['out_id'] = $no;

        if($this->groupId != IndexGroup::GROUP_BUYER && $this->groupId != IndexGroup::GROUP_SUPPLIER){
            return ['status'=>1,'data'=>[],'msg'=>'没有权限'];
        }

        if($this->groupId == IndexGroup::GROUP_BUYER){
            $where['buyer_id'] = $this->userId;
        }else{
            $where['supplier'] = $this->userId;
        }

        $row = $model->where($where)->field(['id','receiver_area_name','add_time','delivery_time','receiver_name','receiver_phone','receiver_detail','express_name','express_code','state','send_time','estimated_time','pay_date','out_id','buyer_comment','buyer_id','supplier'])->find();
        if(!$row){
            return ['status'=>1,'data'=>[],'msg'=>'订单不存在'];
        }

        //采购商供应商
        $userModel = new IndexUser();
        $userInfo = [];
        if($this->groupId == IndexGroup::GROUP_SUPPLIER){
            $userInfo = $userModel->getInfoById($row->buyer_id);
        }elseif ($this->groupId == IndexGroup::GROUP_BUYER){
            $userInfo = $userModel->getInfoById($row->supplier);
        }


        //查询产品
        $goodsModel = new MallOrderGoods();
        $goodsRows = $goodsModel->alias('a')->join(config('prefix').'mall_goods b','a.goods_id=b.id','left')->where(['order_id'=>$row->id])->field(['a.title','a.price','a.quantity','a.specifications_no','a.specifications_name','b.icon'])->select();

        foreach($goodsRows as &$goodsRow){
            $goodsRow['quantity'] = intval($goodsRow->quantity);
            $goodsRow['icon'] = MallGoods::getFormatImg($goodsRow->icon);
        }

        //express expressCode sendDate estimatedDate
        $data = [
            'orderNo' => $row->out_id,
            'companyName' => $userInfo ? $userInfo->real_name : '',
            'groupId' => $this->groupId,
            'state' => $row->state,
            'name' => $row->receiver_name,
            'phone' => $row->receiver_phone,
            'address' => $row->receiver_area_name. $row->receiver_detail,
            'time' => date('Y-m-d H:i',$row->add_time),
            'date' => date('Y-m-d',$row->delivery_time),
            'remark' => $row->buyer_comment,
            'payMethod' => $row->pay_date ? '账期支付': '',
            'express' => $row->express_name ? $row->express_name : '',   //物流
            'expressCode' => $row->express_code ? $row->express_code : '', //物流单号
            'sendDate' => $row->send_time > 0 ? date('Y-m-d',$row->send_time) : '', //发货日期
            'estimatedDate' => $row->estimated_time > 0 ? date('Y-m-d',$row->estimated_time) : '',  //到达日期
            'goods' => $goodsRows,
        ];

        return ['status'=>0,'data'=>$data,'msg'=>''];
    }


    /**
     * @desc 发货
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function delivery(Request $request){
        $orderNo = $request->post('no','');
        $express_name= $request->post('express','');   //物流公司
        $express_code = $request->post('expressCode',''); // 物流单号
        $send_time = $request->post('sendDate',''); //发货日期
        $estimated_time = $request->post('estimatedDate',''); //到达日期
        //
        if(!$orderNo){
            return ['status'=>1,'data'=>[],'msg'=>'订单号不能为空'];
        }
        if(!$express_name){
            return ['status'=>1,'data'=>[],'msg'=>'物流公司不能为空'];
        }
        if(!$express_code){
            return ['status'=>1,'data'=>[],'msg'=>'物流单号不能为空'];
        }
        if(!$send_time){
            return ['status'=>1,'data'=>[],'msg'=>'发货日期不能为空'];
        }
        if(!$estimated_time){
            return ['status'=>1,'data'=>[],'msg'=>'到达日期不能为空'];
        }

        $auth = $this->auth();
        if($auth){
            return $auth;
        }

        $model = new MallOrder();
        $where['out_id'] = $orderNo;
        if($this->groupId != IndexGroup::GROUP_SUPPLIER){
            return ['status'=>1,'data'=>[],'msg'=>'没有权限'];
        }
        $where['supplier'] = $this->userId;
        $row = $model->where($where)->field(['id','receiver_area_name','add_time','delivery_time','receiver_name','receiver_phone','receiver_detail','state','pay_date','out_id','buyer_comment','buyer_id','supplier'])->find();
        if(!$row){
            return ['status'=>1,'data'=>[],'msg'=>'订单不存在'];
        }
        if($row->state != MallOrder::STATE_DELIVER){
            return ['status'=>1,'data'=>[],'msg'=>'订单状态操作错误'];
        }

        $data = [
            'express_name' =>$express_name,
            'express_code' => $express_code,
            'send_time' => strtotime($send_time),
            'estimated_time' => strtotime($estimated_time),
            'state' => MallOrder::STATE_RECEIVE
        ];

        $result = $model->save($data,$where);
        if($result !== false){
            return ['status'=>0,'data'=>[],'msg'=>'提交成功'];
        }
        return ['status'=>1,'data'=>0,'msg'=>'提交失败'];
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
        if($this->groupId != IndexGroup::GROUP_BUYER){
            return ['status'=>1,'data'=>[],'msg'=>'没有权限'];
        }

        //查询数据
        $model = new MallOrder();
        $where['out_id'] = $orderNo;
        $where['buyer_id'] = $this->userId;
        $row = $model->where($where)->field(['id','state',])->find();
        if(!$row){
            return ['status'=>1,'data'=>[],'msg'=>'订单不存在'];
        }
        if($row->state != MallOrder::STATE_PRICING && $row->state != MallOrder::STATE_SIGN && $row->state != MallOrder::STATE_REMITTANCE){
            return ['status'=>1,'data'=>[],'msg'=>'当前订单状态无法取消'];
        }

        $result = $model->save(['state'=>MallOrder::STATE_CLOSED],$where);
        if($result !== false){
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
        $type = $request->post('type',0,'intval');

        if(!$orderNo){
            return ['status'=>1,'data'=>[],'msg'=>'订单号不能为空'];
        }
        $auth = $this->auth();
        if($auth){
            return $auth;
        }
        if($this->groupId != IndexGroup::GROUP_BUYER){
            return ['status'=>1,'data'=>[],'msg'=>'没有权限'];
        }

        //查询数据
        $model = new MallOrder();
        $where['out_id'] = $orderNo;
        $where['buyer_id'] = $this->userId;
        $row = $model->where($where)->field(['id','state',])->find();
        if(!$row){
            return ['status'=>1,'data'=>[],'msg'=>'订单不存在'];
        }
        if($row->state != MallOrder::STATE_RECEIVE && $row->state != MallOrder::STATE_FINISH ){
            return ['status'=>1,'data'=>[],'msg'=>'当前订单状态无法申请售后'];
        }

        $result = $model->save(['service_type'=>$type],$where);
        if($result !== false){
            return ['status'=>0,'data'=>[],'msg'=>'服务申请成功'];
        }
        return ['status'=>1,'data'=>[],'msg'=>'服务申请失败'];
    }


}