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
        $rows = $orderModel->where($where)->order('add_time','desc')->limit($start,$end)->field(['id','state','out_id','receiver_name','supplier','buyer_id'])->select();
        $userModel = new IndexUser();
        foreach ($rows as &$row){
            $userInfo = $userModel->getInfoById($row->supplier);
            $row['supplier'] = $userInfo ? $userInfo->real_name : '';
            $buyerInfo = $userModel->getInfoById($row->buyer_id);
            $row['buyer'] = $buyerInfo ? $buyerInfo->real_name : '';

            $goodsRows = $orderGoodsModel->alias('a')->join(config('prefix').'mall_goods b','a.goods_id=b.id','left')->where(['order_id'=>$row->id])->field(['a.title','a.price','a.quantity','a.specifications_no','a.specifications_name','b.icon'])->select();
            foreach($goodsRows as &$goodsRow){
                $goodsRow['quantity'] = intval($goodsRow->quantity);
                $goodsRow['icon'] = MallGoods::getFormatImg($goodsRow->icon);
                $goodsRow['price'] = getFormatPrice($goodsRow->price);
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
        //  $no = '2018053124610';
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

        $row = $model->where($where)->field(['id','receiver_area_name','add_time','delivery_time','receiver_name','receiver_phone','receiver_detail','state','pay_date','out_id','buyer_comment'])->find();
        if(!$row){
            return ['status'=>1,'data'=>[],'msg'=>'订单不存在'];
        }

        //查询产品
        $goodsModel = new MallOrderGoods();
        $goodsRows = $goodsModel->alias('a')->join(config('prefix').'mall_goods b','a.goods_id=b.id','left')->where(['order_id'=>$row->id])->field(['a.title','a.price','a.quantity','a.specifications_no','a.specifications_name','b.icon'])->select();

        foreach($goodsRows as &$goodsRow){
            $goodsRow['quantity'] = intval($goodsRow->quantity);
            $goodsRow['icon'] = MallGoods::getFormatImg($goodsRow->icon);
        }

        $data = [
            'orderNo' => $row->out_id,
            'state' => $row->state,
            'name' => $row->receiver_name,
            'phone' => $row->receiver_phone,
            'address' => $row->receiver_area_name. $row->receiver_detail,
            'time' => date('Y-m-d H:i',$row->add_time),
            'date' => date('Y-m-d',$row->delivery_time),
            'remark' => $row->buyer_comment,
            'payMethod' => $row->pay_date ? '账期支付': '',
            'goods' => $goodsRows
        ];

        return ['status'=>0,'data'=>$data,'msg'=>''];
    }

}