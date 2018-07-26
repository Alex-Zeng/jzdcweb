<?php
/**
 * User huangjiahui
 * Date 2018/07/26
 */
namespace app\api\controller;

class Factoring extends Base {

	//获取用户实名
	public function getName(){
		//验证登录
		$auth = $this->auth();
		if($auth){
			return $auth;
		}
  		$userId = $this->userId;
        $data = db('index_user')->field('real_name as name')->where(['id'=>$userId])->find();
        if(!$data){
        	$data['real_name'] = '';
        }
        return ['status'=>0,'data'=>['name'=>$data['name']],'msg'=>'请求成功'];

	}

	//获取申请者的订单信息
	public function getOrderInfo(){
		//验证登录
		$auth = $this->auth();
		if($auth){
			return $auth;
		}
		$userId = $this->userId;
        $dataList = db('mall_order')->field('id as orderId,out_id as orderSn,actual_money as account')->where(['state'=>['not in','0,4,13'],'buyer_id'=>$userId])->order('id desc')->select();
        if(!$dataList){
        	$dataList = [];
        }
        return ['status'=>0,'data'=>['orderList'=>$dataList],'msg'=>'请求成功'];
	}

	//添加保理申请
    public function factoringAdd(){
        //验证登录
        $auth = $this->auth();
        if($auth){
         return $auth;
        }
        $userId = $this->userId;

        //接收参数
        $data['order_id'] = input('post.orderId',0,'intval');
        $data['contact_username']  = input('post.contactUsername','','trim');
        $data['contact_phone']  = input('post.contactPhone','','trim');
        $data['need_account']  = input('post.needAccount','','trim');
        $data['bank_corporate']  = input('post.bankCorporate','','trim');
        $data['bank_corporate_confirm']  = input('post.bankCorporateConfirm','','trim');
        $data['bank_address']  = input('post.bankAddress','','trim');
        $result = $this->validate($data,'Factoring');
        if(true !== $result){
            // 验证失败 输出错误信息
            return ['status'=>-2,'data'=>'','msg'=>$result];
        }

        //验证订单号及获取订单编号
        $order = db('mall_order')->field('out_id')->where(['state'=>['not in','0,4,13'],'buyer_id'=>$userId,'id'=>$data['order_id']])->find();
        if(!$order){
            return ['status'=>-2,'data'=>'','msg'=>'所选择的订单有误'];
        }

        unset($data['bank_corporate_confirm']);
        //增加订单编号和时间入库
        $data['order_sn'] = $order['out_id'];
        $data['add_time'] = time();
        $data['user_id'] = $userId;

        if(db('factoring')->insert($data)){
            return ['status'=>0,'data'=>'','msg'=>'申请提交成功'];
        }else{
            return ['status'=>-2,'data'=>'','msg'=>'申请提交失败'];
        }
    }

    //获取保理业务列表
    public function getFactoringList(){
    	//验证登录
        $auth = $this->auth();
        if($auth){
         return $auth;
        }
        $userId = $this->userId;
        
    	$factoringList = db('factoring')->field('add_time,order_sn,need_account')->order('factoring_id desc')->where(['user_id'=>$userId])->select();
		return ['status'=>0,'data'=>['factoringList'=>$factoringList],'msg'=>'申请提交成功'];
    }


}