<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/6/1
 * Time: 17:29
 */

namespace app\api\controller;

use app\common\model\IndexUser;
use app\common\model\MallGoods;
use app\common\model\MallGoodsSpecifications;
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
        $optionId = $request->post('optionId',0,'intval');
        $colorId = $request->post('colorId',0,'intval');

        //验证登录
        $auth = $this->auth();
        if($auth){
            return $auth;
        }

        //查询是否
        $model = new MallGoods();
        $row = $model->where(['id'=>$id,'state'=>MallGoods::STATE_SALE])->find();
        if(!$row){
            return ['status'=>1,'data'=>[],'msg'=>'商品不存在'];
        }
        //
        $goodsSpecificationsModel = new MallGoodsSpecifications();
        $specificationsRow = $goodsSpecificationsModel->where(['color_id'=>$colorId,'option_id'=>$optionId,'goods_id'=>$id])->field(['id','w_price'])->find();

        $cartModel = new \app\common\model\MallCart();
        $where = ['user_id'=>$this->userId,'goods_id'=>$id,'goods_specifications_id'=>$specificationsRow ? $specificationsRow->id : 0];
        $cartRow = $cartModel->where($where)->find();
        if($cartRow){
            $result = $cartModel->where($where)->setInc('quantity',$number);
        }else{
            $userModel = new IndexUser();
            $user = $userModel->getInfoById($this->userId);
            $data = [
                'user_id' => $this->userId,
                'username' => $user ? $user->username : '',
                'key' => 0,
                'goods_id' => $id,
                'quantity'=>$number,
                'price' => $specificationsRow ? $specificationsRow->w_price : $row->w_price,
                'time' => time(),
                'goods_specifications_id' => $specificationsRow ? $specificationsRow->id : 0
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
        //验证登录
        $auth = $this->auth();
        if($auth){
            return $auth;
        }
        //查询数据
        $model = new \app\common\model\MallCart();
        $rows = $model->alias('a')
                      ->join(config('prefix').'mall_goods b','a.goods_id=b.id','left')
                      ->join(config('prefix').'mall_goods_specifications c','a.goods_specifications_id=c.id','left')
                      ->where(['user_id'=>$this->userId])
                      ->field(['b.id','b.icon','b.title','b.w_price','a.id as cart_id','a.quantity','c.w_price as goods_price'])->select();
        $data = [];
        foreach ($rows as $row){
            $data[] = [
                'goodsId' => $row->id,
                'cartId' => $row->cart_id,
                'price' => $row->goods_price ?  $row->goods_price : $row->w_price,
                'title' => $row->title,
                'icon' => MallGoods::getFormatImg($row->icon),
                'quantity' => intval($row->quantity)
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



}
