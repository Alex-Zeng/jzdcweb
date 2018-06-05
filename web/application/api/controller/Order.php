<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/6/5
 * Time: 16:10
 */
namespace app\api\controller;

use think\Request;

class Order extends Base{

    /**
     * @desc 生成订单
     * @param Request $request
     * @return array
     */
    public function make(Request $request){
        $receiverId = $request->post('receiverId',0);
        //根据购物清单分商家生成订单
        $data = [];


       return ['status'=>0,'data'=>$data,'msg'=>''];
    }


}