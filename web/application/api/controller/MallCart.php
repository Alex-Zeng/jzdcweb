<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/6/1
 * Time: 17:29
 */

namespace app\api\controller;

use app\common\model\IndexUser;
use app\common\model\IndexGroup;
use app\common\model\MallGoods;
use app\common\model\MallGoodsSpecifications;
use app\common\model\MallTypeOption;
use app\common\model\MallUnit;
use app\common\model\SmProduct;
use app\common\model\SmProductSpec;
use app\common\model\SmProductSpecAttrs;
use app\common\model\SmProductSpecAttrVal;
use app\common\model\SmProductSpecPrice;
use app\common\model\UserGoodsSpecifications;
use think\Request;

class MallCart extends Base{


    /**
     * @desc 返回用户购物车数量
     * @return array|void
     */
    public function getNumber(){
        $auth = $this->auth();
        if($auth){
            return $auth;
        }

        $userId = $this->userId;
        $model = new \app\common\model\MallCart();
        $count = $model->where(['user_id'=>$userId])->count();

        return ['status'=>0,'data'=>['total'=>$count],'msg'=>''];
    }

    /**
     * @desc 加入购物车
     * @param Request $request
     * @param $id
     */
    public function add(Request $request){
        $id = $request->post('id',0);
        $number = $request->post('number',1);
        $specId = $request->post('specId',0,'intval');

        //验证登录
        $auth = $this->auth();
        if($auth){
            return $auth;
        }
        //采购商权限
        if($this->groupId != IndexGroup::GROUP_BUYER){
            return ['status'=>1,'data'=>[],'msg'=>'没有权限操作'];
        }

        //查询商品是否存在
        $model = new SmProduct();
        $row = $model->where(['id'=>$id,'state'=>SmProduct::STATE_FORSALE,'audit_state'=>SmProduct::AUDIT_RELEASED,'is_deleted'=>0])->find();
        if(!$row){
            return ['status'=>1,'data'=>[],'msg'=>'商品不存在或已下架'];
        }
        //商品规格是否存在
        $specModel = new SmProductSpec();
        $specRow = $specModel->where(['id'=>$specId,'is_deleted'=>0])->find();
        if(!$specRow){
            return ['status'=>1,'data'=>[],'msg'=>'商品规格不存在'];
        }

        //加入购物车
        $cartModel = new \app\common\model\MallCart();
        $where = ['user_id'=>$this->userId,'goods_id'=>$id,'product_spec_id'=>$specId];
        $cartRow = $cartModel->where($where)->find();
        if($cartRow){  //存在更新数量
            $result = $cartModel->save(['quantity'=>$cartRow->quantity+$number,'price' => isset($specRow->price) ? $specRow->price : '0.00'],$where);
        }else{ //不存在插入数据
            $userModel = new IndexUser();
            $user = $userModel->getInfoById($this->userId);
            $data = [
                'user_id' => $this->userId,
                'username' => $user ? $user->username : '',
                'key' => 0,
                'goods_id' => $id,
                'quantity'=>$number,
                'price' => isset($specRow->price) ? $specRow->price : '0.00',
                'time' => time(),
                'product_spec_id' => $specId
            ];
            $result = $cartModel->save($data);
        }

        if($result !== false){
            return ['status'=>0,'data'=>[],'msg'=>'添加成功'];
        }
        return ['status'=>1,'data'=>[],'msg'=>'添加失败'];
    }

    /**
     * @desc 购物车列表
     * @param Request $request
     */
    public function index(Request $request){
        $ids = $request->get('ids','');
        //验证登录
        $auth = $this->auth();
        if($auth){
            return $auth;
        }
        //查询数据
        $model = new \app\common\model\MallCart();

        $where = [];
        $where['a.user_id'] = $this->userId;
        $where['c.state'] = SmProduct::STATE_FORSALE;
        $where['c.audit_state'] = SmProduct::AUDIT_RELEASED;
        $where['c.is_deleted'] = 0;
        $where['b.is_deleted'] = 0;
        if($ids){
            $where['a.id'] = ['in',explode(',',$ids)];
        }

        $specValModel = new SmProductSpecAttrVal();
        //查询数据
        $rows = $model->alias('a')->join(['sm_product_spec'=>'b'],'b.id=a.product_spec_id','left')
            ->join(['sm_product'=>'c'],'b.product_id=c.id')
            ->where($where)
            ->field(['c.id','c.title','c.supplier_id','b.unit','b.is_price_neg_at_phone','b.spec_img_url','b.is_customized','b.id as spec_id','b.spec_set','a.quantity','b.price','b.min_order_qty','a.id as cart_id'])
            ->select();
        $supplierData = [];
        foreach ($rows as $row){
            //查询规格
            $optionInfo = '';
            $specPriceDetails = [];
            if($row->is_customized == 1){
                $optionInfo = '定制';
            }else{
                $specSetIds = $row->spec_set ? explode(',',$row->spec_set) : [];
                $specVals = $specValModel->where(['id'=>['in',$specSetIds]])->select();
                foreach ($specVals as $specVal){
                    $optionInfo .= $specVal->spec_attr_val.',';
                }
                $optionInfo = $optionInfo ? substr($optionInfo,0,strlen($optionInfo)-1) : '';
                $specPriceDetails = (new SmProductSpecPrice())->getPriceDetail($row->spec_id);
            }

            //查询物料编号、物料规格
            $userSpecificationsModel = new UserGoodsSpecifications();
            $userSpecificationsRow = $userSpecificationsModel->where(['user_id'=>$this->userId,'product_spec_id'=>$row->spec_id])->order('create_time desc')->find();

            $supplierData[$row->supplier_id][] =[
                'goodsId' => $row->id,
                'cartId' => $row->cart_id,
                'price' => getFormatPrice($row->price),
                'title' => $row->title,
                'icon' =>  $row->spec_img_url,
                'quantity' => intval($row->quantity),
                'moq' =>  $row->min_order_qty,
                'specificationsInfo' => $optionInfo, //规格描述
                'materialCode' => $userSpecificationsRow ? $userSpecificationsRow->specifications_no : '',  //物料编号
                'materialSpec' => $userSpecificationsRow ? $userSpecificationsRow->specifications_name : '',//物料名称
                'unit' => $row->unit,  //单位
                'isDiscussPrice' => $row->is_price_neg_at_phone, //议价
                "specPriceDetails" => $specPriceDetails  //价格范围
            ];
        }

        $data = [];
        foreach($supplierData as $supplierId => $supplierRow){
            $userModel = new IndexUser();
            $userInfo = $userModel->getInfoById($supplierId);

            $data[] = [
                'supplierName' => $userInfo ? ($userInfo->real_name ? $userInfo->real_name : '') : '',
                'list' => $supplierRow
            ];
        }
        return ['status'=>0,'data'=>$data,'msg'=>''];
    }

    /**
     * @desc 删除购物清单
     * @param Request $request
     * @return array|void
     */
    public function delete(Request $request){
        $ids = $request->post('ids','');
        //验证登录
        $auth = $this->auth();
        if($auth){
            return $auth;
        }
        $idsArr = explode(',',$ids);
        if(!$idsArr){
            return ['status'=>1,'data'=>[],'msg'=>'删除失败'];
        }

        $cartModel = new \app\common\model\MallCart();
        $result = $cartModel->where('id','in',$idsArr)->where(['user_id'=>$this->userId])->delete();
        if($result !== false){
            return ['status'=>0,'data'=>[],'msg'=>'删除成功'];
        }
        return ['status'=>1,'data'=>[],'msg'=>'删除失败'];
    }

    /**
     * @desc 更细购物车数量
     * @param Request $request
     * @return array|void
     */
    public function update(Request $request){
        $id = $request->post('id','');
        $number = $request->post('number',1,'intval');
        $auth = $this->auth();
        if($auth){
            return $auth;
        }

        $cartModel = new \app\common\model\MallCart();
        $result = $cartModel->save(['quantity'=>$number],['user_id'=>$this->userId,'id'=>$id]);
        if($result !== false){
            return ['status'=>0,'data'=>[],'msg'=>'更新成功'];
        }
        return ['status'=>1,'data'=>[],'msg'=>'更细失败'];
    }



}
