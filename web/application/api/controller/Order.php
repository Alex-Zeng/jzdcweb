<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/6/5
 * Time: 16:10
 */
namespace app\api\controller;

use app\common\model\Counter;
use app\common\model\EntCompany;
use app\common\model\IndexGroup;
use app\common\model\IndexUser;
use app\common\model\IndexArea;
use app\common\model\MallOrder;
use app\common\model\MallOrderGoods;
use app\common\model\MallOrderPay;
use app\common\model\MallReceiver;
use app\common\model\OrderMsg;
use app\common\model\SmProduct;
use app\common\model\SmProductSpec;
use app\common\model\SmProductSpecAttrVal;
use app\common\model\SmProductSpecPrice;
use app\common\model\UserGoodsSpecifications;
use sms\Yunpian;
use think\Request;

class Order extends Base{

    /**
     * @desc 下单操作
     * @param Request $request
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function make(Request $request){
        $receiverId = $request->post('receiverId',0,'intval');  //收货地址ID
        $channel = $request->post('channel',0,'intval'); //渠道
        $detailRows = $request->post('detail','');
        // $detailRows = '[{"list":[{"goodsId":"169","specId":"7","quantity":"12","materialCode":"SX00001","materialSpec":"测试商品名称A"}],"date": "2018-06-12","remark": "请尽快发货"}]';
        $detailRows = $detailRows ? json_decode($detailRows,true) : [];

        //验证数据
        if($receiverId <= 0){
            return ['status'=>1,'data'=>[],'msg'=>'收货地址错误'];
        }
        if(!$detailRows){
            return  ['status'=>1,'data'=>[],'msg'=>'未选择商品,无法下单'];
        }
        foreach ($detailRows as $detailRow){
            foreach ($detailRow['list'] as $detailList){
                if(!$detailList['goodsId'] || !$detailList['specId']){
                    return ['status'=>1,'data'=>[],'msg'=>'商品规格错误'];
                }
            }
        }
        //认证
        $auth = $this->auth();
        if($auth){
            return $auth;
        }
        //权限验证
        $userModel = new IndexUser();
        $pResult = $this->checkCompanyPermission();
        if($pResult['status'] == 1){
            return $pResult;
        }
        $companyId = $pResult['data']['companyId'];
        $userInfo = $userModel->getInfoById($this->userId);
        $companyModel = new EntCompany();
        $buyerInfo = $companyModel->getInfoById($companyId);



        //根据购物清单分商家生成订单
        $goodsRows = [];
        $productModel = new SmProduct();
        $specModel = new SmProductSpec();
        $specValModel = new SmProductSpecAttrVal();
        $specPriceModel = new SmProductSpecPrice();

        //循环遍历数据
        foreach($detailRows as $detailRow){
            foreach($detailRow['list'] as $detailList){
                 //验证商品是否存在
                $product = $productModel->where(['id'=>$detailList['goodsId']])->find();
                if(!$product|| $product->supplier_id == $companyId ){
                    continue;
                }
                //商品规格
                $specRow = $specModel->where(['product_id'=>$detailList['goodsId'],'id'=>$detailList['specId']])->find();
                if(!$specRow){
                    continue;
                }

                $optionInfo = '';
                //判断是否定制,根据规格查询 所有规格，进行拼接字符
                if(getBinDecimal($specRow->is_customized) == 1){
                   $optionInfo = '定制';
                }else{
                    $specSetIds = $specRow->spec_set ? explode(',',$specRow->spec_set) : [];
                    $specVals = $specValModel->where(['id'=>['in',$specSetIds]])->select();
                    foreach ($specVals as $specVal){
                        $optionInfo .= $specVal->spec_attr_val.',';
                    }
                    $optionInfo = $optionInfo ? substr($optionInfo,0,strlen($optionInfo)-1) : '';
                }
                //根据规格以及购买数量查询对应的价格
                $price = $specRow->price;
                $specNum = isset($detailList['quantity']) ? $detailList['quantity'] : 1;
                //是否议价
                if(!$specRow->is_price_neg_at_phone){
                    $priceWhere = [
                        'spec_id' => $specRow->id,
                        'is_deleted' => 0,
                        'min_order_qty' => ['lt',$specNum],
                        'max_order_qty' => ['gt',$specNum]
                    ];
                    $priceRow = $specPriceModel->where($priceWhere)->find();
                    if($priceRow){
                        $price = $priceRow->price;
                    }
                }

                $goodsRows[] = [
                    'supplier' => $product->supplier_id,
                    'goods_id' => $detailList['goodsId'],
                    'price' => $price,
                    'cost_price' =>'0.00',
                    'title'=>$product->title,
                    'unit'=>$specRow->unit,
                    'quantity'=> $specNum,
                    'date' => isset($detailRow['date']) ? $detailRow['date'] : '',
                    'remark' =>isset($detailRow['remark']) ? $detailRow['remark'] : '',
                    'materialCode' => isset($detailList['materialCode']) ? $detailList['materialCode'] : '',  //物料编号
                    'materialSpec' => isset($detailList['materialSpec']) ? $detailList['materialSpec'] : '', //物料规格
                    'optionInfo' => $optionInfo,
                    'icon'=>$specRow->spec_img_url,
                    'iconPath' =>$specRow->spec_img_url ? SmProductSpec::getFormatImg($specRow->spec_img_url) : SmProduct::getFormatImg($product->cover_img_url),
                    'specId' => $detailList['specId'],
                    'isDiscussPrice' => $specRow->is_price_neg_at_phone   //是否议价
                ];
            }
        }

        //获取数据列表，根据供应商进行分类
        $supplierGroup = [];
        foreach($goodsRows as $row){
            $supplierGroup[$row['supplier']][] = [
                'goods_id'=>$row['goods_id'],
                'title'=>$row['title'],
                'unit'=>$row['unit'],
                'price'=>$row['price'],
                'cost_price'=>$row['cost_price'],
                'specId'=>$row['specId'],
                'quantity'=>$row['quantity'],
                'date'=>$row['date'],
                'remark'=>$row['remark'],
                'materialCode' => $row['materialCode'],
                'materialSpec' => $row['materialSpec'],
                'specificationsInfo' => $row['optionInfo'],
                'icon' =>$row['icon'],
                'iconPath' => $row['iconPath'],
                'isDiscussPrice' => $row['isDiscussPrice']
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

            //计算订单数据信息
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

        //.........................

        $return = [];

        //查询收货地址数据
        $receiverModel = new MallReceiver();
        $receiverRow = $receiverModel->where(['id'=>$receiverId])->find();
        $areaModel = new IndexArea();
        $areaList = $areaModel->getAreaInfo($receiverRow->area_id);
        if($areaList){
            array_pop($areaList);
        }
        $areaInfo = $areaList ?  implode(' ',array_reverse($areaList)) : '';

        //生成订单
        foreach ($orderList as $index => $order) {
            //订单数据
            $orderModel = new MallOrder();
            $orderNo = getOrderOutId($channel);
            $orderValue = [
                'shop_id' => 1,
                'receiver_area_name' => $areaInfo,
                'last_time' => time(),
                'goods_money' => $order['total_price'],
                'actual_money' => $order['total_price'],
                'cashier' => 'jzdc',
                'buyer' => $buyerInfo ? $buyerInfo->company_name : '',
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
                'buyer_id' => $companyId,
                'created_user_id' => $this->userId,
                'created_user' => $userInfo ? $userInfo->username : ''
            ];

            $orderGoodsModel = new MallOrderGoods();
            $userGoodSpecificationsModel = new UserGoodsSpecifications();
            $cartModel = new \app\common\model\MallCart();
            $result = $orderModel->save($orderValue);
            if ($result == true) {
                //插入order_goods数据表
                $counterModel = new Counter();
                $counterModel->where(['id' => 1])->setInc('order_count', 1);
                $orderGoods = [];
                $returnGoodsList = [];
                foreach ($order['list'] as $goodsList) {
                    $orderGoods[] = [
                        'buyer' => $buyerInfo ? $buyerInfo->company_name : '',
                        'order_id' => $orderModel->id,
                        'order_state' => MallOrder::STATE_PRICING,
                        'icon' => $goodsList['icon'],
                        'title' => $goodsList['title'],
                        'price' => $goodsList['price'],
                        's_info' => $goodsList['specificationsInfo'],
                        'shop_id' => 1,
                        'snapshot_id' => 0,
                        'quantity' => $goodsList['quantity'],
                        'goods_id' => $goodsList['goods_id'],
                        'unit' => $goodsList['unit'],
                        'time' => time(),
                        'cost_price' => $goodsList['cost_price'],
                        'buyer_id' => $this->userId,
                        'specifications_no' => $goodsList['materialCode'],
                        'specifications_name' => $goodsList['materialSpec'],
                        'is_price_neg_at_phone' => $goodsList['isDiscussPrice'],
                        'product_spec_id' => $goodsList['specId']
                     ];

                    $returnGoodsList[] = [
                        'goodsId' => $goodsList['goods_id'],
                        'title' => $goodsList['title'],
                        'quantity' => $goodsList['quantity'],
                        'price' => getFormatPrice($goodsList['price']),
                        'unit' => $goodsList['unit'],
                        'materialCode' => $goodsList['materialCode'],
                        'icon' => $goodsList['iconPath'],
                        'materialSpec' => $goodsList['materialSpec'],
                        'specificationsInfo' => $goodsList['specificationsInfo'],
                    ];
                }

                $result2 = $orderGoodsModel->insertAll($orderGoods);
                if ($result2) {
                    $supplerInfo = $companyModel->getInfoById($index);

                    //返回数据
                    $return[] = [
                        'orderNo' => $orderNo,
                        'totalPrice' => $order['total_price'],
                        'date' => $order['date'],
                        'goods' => $returnGoodsList,
                        'remark' => $order['remark'],
                        'supplierName' => $supplerInfo ? $supplerInfo->company_name : '',
                    ];

                    //保存用户最后一次商品规格物料并删除购物车
                    foreach ($order['list'] as $list) {
                        if($list['materialCode']){
                            $specificationsWhere = ['user_id' => $this->userId, 'goods_id' => $list['goods_id'], 'product_spec_id' => $list['specId']];
                            $exist = $userGoodSpecificationsModel->where($specificationsWhere)->find();
                            if ($exist) {
                                $userGoodSpecificationsModel->save(['specifications_no' => $list['materialCode'], 'specifications_name' => $list['materialSpec'], 'update_time' => time()], $specificationsWhere);
                            } else {
                                $specificationsWhere['specifications_no'] = $list['materialCode'];
                                $specificationsWhere['specifications_name'] = $list['materialSpec'];
                                $specificationsWhere['create_time'] = time();
                                $specificationsWhere['update_time'] = time();
                                (new UserGoodsSpecifications())->save($specificationsWhere);
                            }
                        }
                        //删除购物清单 同步操作,
                        $cartModel->where(['user_id' => $this->userId, 'goods_id' => $list['goods_id'], 'product_spec_id' => $list['specId']])->delete();
                    }
                }
            }
        }

        //发送邮件通知
        $emailStr = config('JZDC_OP_EMAIL');
        $subject='集众电采平台系统订单通知';
        $content='您好，现有新的订单生成，请及时跟进处理，谢谢。';
        SendMail($emailStr,$subject,$content);

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
        //判断是否为管理员
        $companyModel = new EntCompany();
        $companyInfo = $companyModel->getInfoById($companyId);
        //是否为管理员
        $userId = 0;
        if($companyInfo->responsible_user_id != $this->userId){
            $userId = $this->userId;
        }

        $orderModel = new MallOrder();
        $orderGoodsModel = new MallOrderGoods();

        if($this->groupId != IndexGroup::GROUP_BUYER && $this->groupId != IndexGroup::GROUP_SUPPLIER && $this->groupId != IndexGroup::GROUP_MEMBER){
            return ['status'=>1,'data'=>[],'msg'=>'没有权限'];
        }

        $where = '';
        if($this->groupId == IndexGroup::GROUP_BUYER){
            $where.= 'buyer_id='.$companyId;
            if($userId > 0){
                $where .=' created_user_id='.$userId;
            }
        }else{
            $where .='supplier='.$companyId;
        }
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
            $userInfo = [];
            if($this->groupId  == IndexGroup::GROUP_SUPPLIER){
                $userInfo = $companyModel->getInfoById($row->buyer_id);
            }elseif ($this->groupId == IndexGroup::GROUP_BUYER){
                $userInfo = $companyModel->getInfoById($row->supplier);
            }
            $row['companyName']  = $userInfo ? $userInfo->company_name : '';
            $row['groupId'] = $this->groupId;
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
            $row['confirmType'] = ($this->groupId == IndexGroup::GROUP_BUYER)&& ($row->state == 6) && ($row->service_type == 0 || $row->service_type == 2) ? 1 : 0;
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

        if($this->groupId != IndexGroup::GROUP_BUYER && $this->groupId != IndexGroup::GROUP_SUPPLIER){
            return ['status'=>1,'data'=>[],'msg'=>'没有权限'];
        }

        if($this->groupId == IndexGroup::GROUP_BUYER){
            $where['buyer_id'] = $companyId;
        }else{
            $where['supplier'] = $companyId;
        }

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

        //express expressCode sendDate estimatedDate
        $data = [
            'orderNo' => $row->out_id,
            'companyName' => $this->groupId == IndexGroup::GROUP_SUPPLIER ? ($sellerInfo ? $sellerInfo->company_name : '') : ($buyerInfo ? $buyerInfo->company_name : '') ,
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
        if($this->groupId == IndexGroup::GROUP_SUPPLIER){
            $data['payMethod'] = !$payRow && isset($row->pay_date) ? '账期支付': ($payRow->pay_type == 4 ? '汇票' : '转账');
            $data['payNumber'] = $payRow ? $payRow->number : '';
            $data['payImg'] = $payRow ? MallOrderPay::getFormatPicture($payRow->picture) : '';
            $data['payDate'] = $payRow && $payRow->pay_time ? substr($payRow->pay_time,0,10) : '';
        }else{
            $data['payMethod'] = '';
            $data['payNumber'] = '';
            $data['payImg'] = '';
            $data['payDate'] =  '';
        }

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
        //权限验证
        $pResult = $this->checkCompanyPermission();
        if($pResult['status'] == 1){
            return $pResult;
        }
        $companyId = $pResult['data']['companyId'];

        $model = new MallOrder();
        $where['out_id'] = $orderNo;
        if($this->groupId != IndexGroup::GROUP_SUPPLIER){
            return ['status'=>1,'data'=>[],'msg'=>'没有权限'];
        }
        $where['supplier'] = $companyId;
        $row = $model->where($where)->field(['id','receiver_area_name','add_time','delivery_time','receiver_name','receiver_phone','receiver_detail','goods_names','state','pay_date','out_id','buyer_comment','buyer_id','supplier','created_user_id'])->find();
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
            'confirm_delivery_time' => time(),
            'state' => MallOrder::STATE_RECEIVE
        ];

        $result = $model->save($data,$where);
        if($result !== false){

            //消息通知采购商
            $orderMsgModel = new OrderMsg();
            $userModel = new IndexUser();
            $companyModel = new EntCompany();

            $content = "订单号：{$row->out_id}【{$row->goods_names}】供应商已经发货。";
            $msgData = ['title'=>'订单已发货','content' => $content,'order_no' => $row->out_id,'order_id'=>$row->id,'user_id'=>$row->created_user_id,'create_time'=>time()];
            $orderMsgModel->save($msgData);
            $userModel->where(['id'=>$row->created_user_id])->setInc('unread',1);

            //短信通知采购商
            $buyerUserInfo = $userModel->getInfoById($row->created_user_id);
            $sellerInfo = $companyModel->getInfoById($row->supplier);

            $yunpian = new Yunpian();
            $yunpian->send($buyerUserInfo->phone,['order_id'=>$row->out_id,'express_code'=>$express_code,'express_name'=>$express_name,'supplier'=>$sellerInfo ? $sellerInfo->company_name : ''],Yunpian::TPL_ORDER_SEND);

            return ['status'=>0,'data'=>[],'msg'=>'提交成功'];
        }
        return ['status'=>1,'data'=>0,'msg'=>'提交失败'];
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
        if($this->groupId != IndexGroup::GROUP_BUYER){
            return ['status'=>1,'data'=>[],'msg'=>'没有权限操作'];
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

        if($this->groupId != IndexGroup::GROUP_BUYER){
            return ['status'=>1,'data'=>[],'msg'=>'没有权限'];
        }

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
        //权限验证
        $pResult = $this->checkCompanyPermission();
        if($pResult['status'] == 1){
            return $pResult;
        }
        $companyId = $pResult['data']['companyId'];

        if($this->groupId != IndexGroup::GROUP_BUYER){
            return ['status'=>1,'data'=>[],'msg'=>'没有权限'];
        }

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

    /**
     * @desc 返回交易状态列表
     * @return array
     */
    public function showStatusList(){
        $list = getOrderShowStatus();
        return ['status'=>0,'data'=>$list,'msg'=>''];
    }


    /**
     * @desc 导出订单
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function export(Request $request){
        $status = $request->post('status',-1,'intval');
        $goodsName = $request->post('goodsName','','trim|addslashes');
        $companyName = $request->post('companyName','','trim|addslashes');
        $startDate = $request->post('startDate','','filterDate');
        $endDate = $request->post('endDate','','filterDate');
        $orderNo = $request->post('orderNo','','addslashes');

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

        $model = new MallOrder();

        //判断是否为管理员
        $companyModel = new EntCompany();
        $companyInfo = $companyModel->getInfoById($companyId);
        //是否为管理员
        $userId = 0;
        if($companyInfo->responsible_user_id != $this->userId){
            $userId = $this->userId;
        }


        if($this->groupId != IndexGroup::GROUP_BUYER && $this->groupId != IndexGroup::GROUP_SUPPLIER && $this->groupId != IndexGroup::GROUP_MEMBER){
            return ['status'=>1,'data'=>[],'msg'=>'没有权限'];
        }

        $where = '';
        if($this->groupId == IndexGroup::GROUP_BUYER){
            $where.= 'buyer_id='.$companyId;
            if($userId > 0){
                $where.=' created_user_id='.$userId;
            }
        }else{
            $where .='supplier='.$companyId;
        }
        //
        if($goodsName){
            $where .=' AND goods_names LIKE %'.$goodsName.'%';
        }
        if($startDate){
            $where .=' AND add_time >'.strtotime($startDate);
        }
        if($endDate){
            $where .=' and add_time <'.strtotime($endDate.' 23:59:59');
        }
        if($orderNo){
            $where .= ' AND out_id LIKE \'%'.$orderNo.'%\'';
        }

        if($companyName){
            $companyRows = $companyModel->where(['company_name'=>['like','%'.$companyName.'%']])->find(['id'])->select();
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

        vendor('PHPExcel.PHPExcel');
        $objPHPExcel = new \PHPExcel();
        $objPHPExcel->setActiveSheetIndex(0);
        //设置表头
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', '下单时间')
            ->setCellValue('B1', '订单号')
            ->setCellValue('C1', '订单状态')
            ->setCellValue('D1','采购商')
            ->setCellValue('E1', '采购商联系人')
            ->setCellValue('F1', '供应商')
            ->setCellValue('G1', '商品名称')
            ->setCellValue('H1','商品规格')
            ->setCellValue('I1','数量')
            ->setCellValue('J1','单价')
            ->setCellValue('K1','小计');
        if($this->groupId == IndexGroup::GROUP_BUYER){
            $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('L1','物料编号')
                ->setCellValue('M1','物料规格')
                ->setCellValue('N1','买家留言')
                ->setCellValue('O1','合同编号')
                ->setCellValue('P1','是否账期支付')
                ->setCellValue('Q1','账期截止');
        }elseif ($this->groupId == IndexGroup::GROUP_SUPPLIER){
            $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('L1','买家留言')
                ->setCellValue('M1','合同编号')
                ->setCellValue('N1','是否账期支付')
                ->setCellValue('O1','账期截止');
        }
        
        //查询数据
        $total = $model->where($where)->count();

        $pageSize = 100;
        $page = ceil($total/$pageSize);

        $counter = 2;
        $companyModel = new EntCompany();
        $goodsModel = new MallOrderGoods();
        $objPHPExcel->getDefaultStyle()->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);

        //设置宽度
        $objPHPExcel->getActiveSheet(0)->getColumnDimension('A')->setWidth(16);
        $objPHPExcel->getActiveSheet(0)->getColumnDimension('B')->setWidth(16);
        $objPHPExcel->getActiveSheet(0)->getColumnDimension('C')->setWidth(16);
        $objPHPExcel->getActiveSheet(0)->getColumnDimension('D')->setWidth(20);
        $objPHPExcel->getActiveSheet(0)->getColumnDimension('E')->setWidth(20);
        $objPHPExcel->getActiveSheet(0)->getColumnDimension('F')->setWidth(20);
        $objPHPExcel->getActiveSheet(0)->getColumnDimension('G')->setWidth(25);
        $objPHPExcel->getActiveSheet(0)->getColumnDimension('H')->setWidth(15);

        for($i =0; $i < $page; $i++){
            $start = $page*$i;
            $rows = $model->where($where)->limit($start,$pageSize)->order('add_time','desc')->select();

            //查询
            $supplierIds = $buyerIds = [];
            foreach($rows as $row){
                $supplierIds[] = $row->supplier;
                $buyerIds[] = $row->buyer_id;
            }

            $supplierInfos = $companyModel->where(['id'=>['in',$supplierIds]])->select();
            $buyerInfos = $companyModel->where(['id'=>['in',$buyerIds]])->select();
            $supplierMap = $buyerMap = [];
            //转化为key => value
            foreach ($supplierInfos as $supplierInfo){
                $supplierMap[$supplierInfo->id] = $supplierInfo->company_name;
            }
            foreach($buyerInfos as $buyerInfo){
                $buyerMap[$buyerInfo->id]['name'] = $buyerInfo->company_name ? $buyerInfo->company_name : '';
                $buyerMap[$buyerInfo->id]['contacts'] = $buyerInfo->contacts ? $buyerInfo->contacts : '';
            }

            foreach ($rows as $row){
                //查询订单商品
                $goodsRows = $goodsModel->where(['order_id'=>$row->id])->select();
                $goodsCount = count($goodsRows);
                $orderStart = $counter;
                $orderEnd = $counter + $goodsCount -1;

                //合并单元格
                if($orderEnd > $orderStart){
                    $objPHPExcel->setActiveSheetIndex(0)->mergeCells('A'.$orderStart.':A'.$orderEnd);
                    $objPHPExcel->setActiveSheetIndex(0)->mergeCells('B'.$orderStart.':B'.$orderEnd);
                    $objPHPExcel->setActiveSheetIndex(0)->mergeCells('C'.$orderStart.':C'.$orderEnd);
                    $objPHPExcel->setActiveSheetIndex(0)->mergeCells('D'.$orderStart.':D'.$orderEnd);
                    $objPHPExcel->setActiveSheetIndex(0)->mergeCells('E'.$orderStart.':E'.$orderEnd);
                    $objPHPExcel->setActiveSheetIndex(0)->mergeCells('F'.$orderStart.':F'.$orderEnd);

                    if($this->groupId == IndexGroup::GROUP_BUYER){
                        $objPHPExcel->setActiveSheetIndex(0)->mergeCells('N'.$orderStart.':N'.$orderEnd);
                        $objPHPExcel->setActiveSheetIndex(0)->mergeCells('O'.$orderStart.':O'.$orderEnd);
                        $objPHPExcel->setActiveSheetIndex(0)->mergeCells('P'.$orderStart.':P'.$orderEnd);
                        $objPHPExcel->setActiveSheetIndex(0)->mergeCells('Q'.$orderStart.':Q'.$orderEnd);
                    }elseif ($this->groupId == IndexGroup::GROUP_SUPPLIER){
                        $objPHPExcel->setActiveSheetIndex(0)->mergeCells('N'.$orderStart.':N'.$orderEnd);
                        $objPHPExcel->setActiveSheetIndex(0)->mergeCells('O'.$orderStart.':O'.$orderEnd);
                    }
                }

                foreach($goodsRows as $goodsRow){
                    $objPHPExcel->setActiveSheetIndex(0) ->setCellValue('A'.$counter, date('Y-m-d H:i',$row->add_time));
                    $objPHPExcel->setActiveSheetIndex(0) ->setCellValueExplicit('B'.$counter, $row->out_id,\PHPExcel_Cell_DataType::TYPE_STRING);
                    $objPHPExcel->setActiveSheetIndex(0) ->setCellValue('C'.$counter, getOrderStatusInfo($row->state,$row->service_type));
                    $objPHPExcel->setActiveSheetIndex(0) ->setCellValue('D'.$counter, isset($buyerMap[$row->buyer_id]) ? $buyerMap[$row->buyer_id]['name'] : '');
                    $objPHPExcel->setActiveSheetIndex(0) ->setCellValue('E'.$counter,  isset($buyerMap[$row->buyer_id]) ? $buyerMap[$row->buyer_id]['contact'] : '');
                    $objPHPExcel->setActiveSheetIndex(0) ->setCellValue('F'.$counter, isset($supplierMap[$row->supplier]) ? $supplierMap[$row->supplier] : '');
                    $objPHPExcel->setActiveSheetIndex(0) ->setCellValue('G'.$counter, $goodsRow->title);
                    $objPHPExcel->setActiveSheetIndex(0) ->setCellValue('H'.$counter, $goodsRow->s_info);
                    $objPHPExcel->setActiveSheetIndex(0) ->setCellValue('I'.$counter, $goodsRow->quantity);
                    $objPHPExcel->setActiveSheetIndex(0) ->setCellValue('J'.$counter, '¥'.$goodsRow->price);
                    $objPHPExcel->setActiveSheetIndex(0) ->setCellValue('K'.$counter, '¥'.$goodsRow->quantity*$goodsRow->price);

                    if($this->groupId == IndexGroup::GROUP_BUYER){
                        $objPHPExcel->setActiveSheetIndex(0) ->setCellValue('L'.$counter, $goodsRow->specifications_no);
                        $objPHPExcel->setActiveSheetIndex(0) ->setCellValue('M'.$counter, $goodsRow->specifications_name);
                        $objPHPExcel->setActiveSheetIndex(0) ->setCellValue('N'.$counter, $row->buyer_comment);
                        $objPHPExcel->setActiveSheetIndex(0) ->setCellValue('O'.$counter, $row->contract_number);
                        $objPHPExcel->setActiveSheetIndex(0) ->setCellValue('P'.$counter, $row->pay_date ? '是' : '否');
                        $objPHPExcel->setActiveSheetIndex(0) ->setCellValue('Q'.$counter, $row->pay_date ? substr($row->pay_date,0,10): '');
                    }elseif ($this->groupId == IndexGroup::GROUP_SUPPLIER){
                        $objPHPExcel->setActiveSheetIndex(0) ->setCellValue('L'.$counter, $row->buyer_comment);
                        $objPHPExcel->setActiveSheetIndex(0) ->setCellValue('M'.$counter, $row->contract_number);
                        $objPHPExcel->setActiveSheetIndex(0) ->setCellValue('N'.$counter, $row->pay_date ? '是' : '否');
                        $objPHPExcel->setActiveSheetIndex(0) ->setCellValue('O'.$counter, $row->pay_date ? substr($row->pay_date,0,10): '');
                    }

                    $counter++;
                }
                unset($goodsRows);
            }

            unset($rows);
        }

        $filename = 'order_'.$this->userId.'_'.date('YmdHis',time()).'.xls';
        $objPHPExcel->getActiveSheet()->setTitle('商品订单信息');

        //设置商品
        //生成excel文件
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');

        //设置目录
        $path = ROOT_PATH.'public/uploads/temp/';
        if(!is_dir($path)){ mkdir($path,0777);}

        $objWriter->save($path.$filename);
        return ['status'=>0,'data'=>['url'=>config('jzdc_domain').'/web/public/uploads/temp/'.$filename],'msg'=>''];
    }


    /**
     * @desc 采购商供应商订单
     * @return array|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getDeskList(){
        $type = Request::instance()->get('type',1);
        $auth = $this->auth();
        if($auth){
            return $auth;
        }

        //
        $model = new MallOrder();
        $where = [];
        if($this->groupId!= IndexGroup::GROUP_BUYER && $this->groupId != IndexGroup::GROUP_SUPPLIER){
            return ['status'=>1,'data'=>[],'msg'=>'没有权限'];
        }
        if($this->groupId == IndexGroup::GROUP_SUPPLIER){
            $where['supplier'] = $this->userId;
        }elseif ($this->groupId == IndexGroup::GROUP_BUYER){
            $where['buyer_id'] = $this->userId;
        }

        switch ($type){
            case 1:  //近期成交
                break;
            case 2: //待发货
                $where['state'] = 3;
                break;
            case 3: //待售后
                $where['service_type'] = 1;
                break;
            default:
        }

        $field = ['id','add_time','out_id','supplier','goods_count','actual_money','state','service_type'];
        $rows = $model->where($where)->order('add_time','desc')->field($field)->select();
        $supplierIds = [];
        foreach ($rows as $row){
            $supplierIds[] = $row->supplier;
        }

        $companyModel = new EntCompany();
        $supplierInfos = $companyModel->where(['id'=>['in',$supplierIds]])->field(['id','company_name'])->select();

        $supplierMap = [];
        foreach ($supplierInfos as $supplierInfo){
            $supplierMap[$supplierInfo->id] = $supplierInfo->company_name;
        }

        $data = [];
        foreach ($rows as $row){
            $data[] = [
                'orderNo'=> $row->out_id,
                'supplierName' => isset($supplierMap[$row->supplier]) ? $supplierMap[$row->supplier] : '',
                'orderTime' => date('Y-m-d H:i',$row->add_time),
                'goodsNumber' => intval($row->goods_count),
                'totalMoney' => $row->actual_money,
                'stateInfo'=> getOrderStatusInfo($row->state,$row->service_type)
            ];
        }
        return ['status'=>0,'data'=>['list'=>$data],'msg'=>''];
    }

}