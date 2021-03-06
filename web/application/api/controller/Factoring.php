<?php
namespace app\api\controller;

use app\common\model\FmFactoring;
use app\common\model\IndexUser;
use app\common\model\IndexGroup;
use app\common\model\MallOrder;
use app\common\model\EntCompany;

class Factoring extends Base {

	//获取企业名字
	public function getName(){
		//验证登录
		$auth = $this->auth();
		if($auth){
			return $auth;
		}
        $userId = $this->userId;

        $IndexUser = new IndexUser();
        $company_name = $IndexUser->alias('a')->join(['ent_company b'],'a.company_id=b.id','left')->where(['a.id'=>$userId])->value('company_name');
        return ['status'=>0,'data'=>['name'=>(string)$company_name],'msg'=>'请求成功'];

	}

	//获取申请者的订单信息
	public function getOrderInfo(){
		//验证登录
		$auth = $this->auth();
		if($auth){
			return $auth;
		}
		$userId = $this->userId;

        $IndexUser = new IndexUser();
        $companyId = ($IndexUser->where(['id'=>$userId])->value('company_id'))+0;

        $MallOrder = new MallOrder();
		$dataList = $MallOrder->field('id as orderId,out_id as orderSn,actual_money as account')->where(['state'=>['not in','4,13'],'supplier'=>$companyId])->order('id desc')->select();
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

        $IndexUser = new IndexUser();
        $companyId = ($IndexUser->where(['id'=>$userId])->value('company_id'))+0;

        $MallOrder = new MallOrder();
        $order = $MallOrder->field('out_id,actual_money')->where(['state'=>['not in','4,13'],'supplier'=>$companyId,'id'=>$data['order_id']])->order('id desc')->find();
        //验证订单号及获取订单编号
        if(!$order){
            return ['status'=>-2,'data'=>'','msg'=>'所选择的订单有误'];
        }
        if($data['need_account']>$order['actual_money']){
        	return ['status'=>-2,'data'=>'','msg'=>'融资申请金额不能大于订单金额'];
        }

        unset($data['bank_corporate_confirm']);
        //增加订单编号和时间入库
        $data['order_sn'] = $order['out_id'];
        $data['add_time'] = time();
        $data['user_id'] = $userId;
        $data['company_id'] = $companyId;
        $data['state']  = 1;

        $FmFactoring = new FmFactoring();
        if($FmFactoring->data($data)->save()){
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
        
        $FmFactoring = new FmFactoring();
    	$data = $FmFactoring->field('factoring_id,add_time,order_sn,need_account,state,loan_account')->order('factoring_id desc')->where(['user_id'=>$userId])->select();
		$factoringList = [];
		$dt = [];
		foreach ($data as $key => $value) {
            $dt['factoringId'] = $value['factoring_id'];
			$dt['dataTime']     = date('Y-m-d H:i:s',$value['add_time']); 
			$dt['orderSn']      = $value['order_sn']; 
            $dt['needAccount']  = $value['need_account'];  
            $dt['stateName']    = $FmFactoring->getStateName($value['state']); 
			$dt['loanAccount'] = $value['loan_account']; 
			$factoringList[]    = $dt;
		}
		return ['status'=>0,'data'=>['factoringList'=>$factoringList],'msg'=>'列表数据'];
    }

    //获取保理业务详情
    public function getFactoringDetail(){
        //验证登录
        $auth = $this->auth();
        if($auth){
         return $auth;
        }
        $userId = $this->userId;

        $factoring_id = input('post.factoringId',0,'intval');
        $FmFactoring = new FmFactoring();
        $data = $FmFactoring->field('need_account,add_time,order_sn,contact_username,contact_phone,state,loan_account,bank_corporate,bank_address,reasons,company_id')->where(['user_id'=>$userId,'factoring_id'=>$factoring_id])->find();
        
        $EntCompany = new EntCompany();
        $company_id = isset($data['company_id'])?$data['company_id']:0;
        $companyName = $EntCompany->where(['id'=>$company_id])->value('company_name');
        $factoringDetail = [
            'orderSn'       =>isset($data->order_sn)?$data->order_sn:'',
            'loanAccount'   =>isset($data->loan_account)?$data->loan_account:'0.00',
            'stateName'     =>isset($data->state)?$FmFactoring->getStateName($data->state):'',
            'needAccount'   =>isset($data->need_account)?$data->need_account:'0.00',
            'dataTime'      =>isset($data->add_time)?date('Y-m-d H:i:s',$data->add_time):'',
            'contactUsername'=>isset($data->contact_username)?$data->contact_username:'',
            'contactphone'  =>isset($data->contact_phone)?$data->contact_phone:'',
            'name'          =>(string)$companyName,
            'bankCorporate' =>isset($data->bank_corporate)?$data->bank_corporate:'',
            'bankAddress'   =>isset($data->bank_address)?$data->bank_address:'',
            'reasons'       =>isset($data->reasons)?$data->reasons:'',
        ];
        return ['status'=>0,'data'=>['factoringDetail'=>$factoringDetail],'msg'=>'详情数据'];
    }


}