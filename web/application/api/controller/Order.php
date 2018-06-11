<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/6/5
 * Time: 16:10
 */
namespace app\api\controller;

use app\common\model\MallGoods;
use app\common\model\MallOrder;
use app\common\model\MallOrderGoods;
use think\Request;

class Order extends Base{

    /**
     * @desc 生成订单
     * @param Request $request
     * @return array
     */
    public function make(Request $request){
        $receiverId = $request->post('receiverId',0,'intval');
        $deliveryDate = $request->post('date','');
        $remark = $request->post('remark','','');
        $goods = $request->post('goods','');

//        $auth = $this->auth();
//        if($auth){
//            return $auth;
//        }

        //商品规格{}
        //根据购物清单分商家生成订单
        $goodsArr = [19 => 2,20 => 4,21 => 5];
//        $goodsArr = [
//           ['id'=>19,'quantity'=>2],
//           ['id'=>20,'quantity'=>4],
//           ['id'=>21,'quantity'=>5]
//        ];

       // echo json_encode($goodsArr); exit;
        $ids = array_keys($goodsArr);
        //
        $model = new MallGoods();
        $rows = $model->where(['id'=>['in',$ids]])->field(['id','title','supplier','w_price','unit'])->select();
        $supplierGroup = [];

        foreach($rows as $row){
            $supplierGroup[$row->supplier][] = ['goods_id'=>$row->id,'title'=>$row->title,'unit'=>$row->unit,'price'=>$row->w_price,'quantity'=>$goodsArr[$row->id]];
        }

        //循环遍历
        $orderList = [];
        foreach ($supplierGroup as $supplierId => $items){
            $totalPrice = $quantity = 0;
            foreach($items as $item){
                $totalPrice += $item['price']*$item['quantity'];
                $quantity += $item['quantity'];
            }

            $orderList[$supplierId] = [
                'total_price' => $totalPrice,
                'quantity' => $quantity,
                'list' => $items
            ];
        }

        $return = [];
        $orderModel = new MallOrder();
        //生成订单
        foreach ($orderList as $index => $order){
            $orderNo = getOrderOutId($index);
            $orderValue = [
                'shop_id' => 1,
                'receiver_area_name' => '',
                'last_time' => time(),
                'goods_money' => $order['total_price'],
                'actual_money' => $order['total_price'],
                'cashier' => 'jzdc',
                'buyer' => '',
                'add_time' => time(),
                'state' => MallOrder::STATE_PRICING,
                'delivery_time' => '',
                'buyer_comment' => $remark,
                'sum_money' => $order['total_price'],
                'receiver_name' => '',
                'receiver_phone' => '',
                'receiver_area_id' => '',
                'receiver_detail' => '',
                'receiver_post_code' => '', //
                'goods_names' => '',
                'goods_cost' => '',
                'goods_count' => $order['quantity'],
                'out_id' => $orderNo,
                'supplier' => $index,
                'buyer_comment' => $remark
            ];

            $orderGoodsModel = new MallOrderGoods();
            $result = $orderModel->save($orderValue);
            if($result == true){
                //插入order_goods数据表
                $orderGoods = [];
                foreach ($order['list'] as $goodsList){
                    $orderGoods[] = [
                        'buyer' => $this->userId,
                        'order_id' =>  $orderModel->id,
                        'order_state' => MallOrder::STATE_PRICING,
                        'icon' => '',
                        'title' => $goodsList['title'],
                        'price' => $goodsList['price'],
                    ];
                }

                $result2 = $orderGoodsModel->insertAll($orderGoods);
                if($result2){
                    //返回数据
                    $return[] = [
                        'orderNo' =>  $orderNo,
                        'date' => $deliveryDate,
                        'remark' => $remark,
                        'goods' => $goodsList
                    ];

                }

                //添加日志

                //触发消息通知

            }
        }

        return ['status'=>0,'data'=>$return,'msg'=>''];
    }


}