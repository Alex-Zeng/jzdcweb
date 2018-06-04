<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/6/1
 * Time: 17:29
 */

namespace app\api\controller;


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

        //
        $userId = $this->userId;
        $model = new \app\common\model\MallCart();
        $count = $model->where(['user_id'=>$userId])->count();

        return ['status'=>0,'data'=>['total'=>$count],'msg'=>''];
    }



}
