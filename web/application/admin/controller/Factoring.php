<?php
namespace app\admin\controller;
use app\common\model\FmFactoring;
use app\common\model\IndexUser;

class Factoring extends Base{
	
	/**
	 * [index 集众保理列表]
	 * @return [type] [description]
	 */
	public function index(){
		$FmFactoring = new FmFactoring();

		$start = input('param.start');
		$end = input('param.end');
		$id = input('param.id');

		$where = [];
		if(isset($start) && $start && isset($end) && $end){
           $where['a.add_time'] = ['between',[strtotime($start),strtotime($end.' 23:59:59')]];
           $this->assign('start',$start);
			$this->assign('end',$end);
        }elseif (isset($start) && $start){
            $where['a.add_time'] = ['gt',strtotime($start)];
            $this->assign('start',$start);
        }elseif (isset($end) && $end){
            $where['a.add_time'] = ['lt',strtotime($end.' 23:59:59')];
			$this->assign('end',$end);
        }

        if(isset($id) && $id>0){
        	$where['a.user_id'] = $id;
        	$this->assign('id',$id);
        }
        
		$FmFactoring = new FmFactoring();
		$list = $FmFactoring->alias('a')->field('a.factoring_id,b.real_name,a.order_sn,a.contact_username,a.contact_phone,a.need_account,a.add_time')->join('__INDEX_USER__ b','a.user_id = b.id','left')->where($where)->paginate(20,false,['query'=>request()->param()]);
		$user = new IndexUser();
		$name = $user->where(['group'=> ['in','4,5']])->field(['id','real_name'])->select();

		$this->assign('page',$list->render());
		$this->assign('list',$list);
		$this->assign('name',$name);
		return view();
	}

	/**
	 * [detail 集众保理详情]
	 * @return [type] [description]
	 */
	public function detail(){
		$id = input('param.id',0,'intval');

		if(request()->isPost()){
			$id = input('post.id',0,'intval');
			$type = input('post.type','','trim');

			$FmFactoring = new FmFactoring();
			$row = $FmFactoring->field('need_account,state')->where(['factoring_id'=>$id])->find();
			if(empty($row)){
				return $this->errorMsg('101300');
			}
			if(!in_array($type,['verify','pay'])){
				return $this->errorMsg('101301');
			}
			switch ($type) {
				case 'verify'://审核
					$verify = input('post.verify','','trim');
					if(!in_array($verify,[1,2])){
						return $this->errorMsg('101302');
					}
					if($row['state']!=1){
						return $this->errorMsg('101303');
					}
					if($verify==1){//审核通过
						$loan_account = input('post.loan_account',0);
						if($loan_account==0){
							return $this->errorMsg('101304');
						}
						if($row['need_account']<$loan_account){//申请金额小于审批金额
							return $this->errorMsg('101305');
						}
						$request = $FmFactoring->where(['factoring_id'=>$id,'state'=>1])->update(['state'=>3]);
						if(!$request){
							return $this->errorMsg('101306');
						}
						return $this->successMsg('reload',['msg'=>'完成审核通过操作']);
					}else{//审核不通过
						$reasons = input('post.reasons','','trim');
						if(mb_strlen($reasons,'utf-8')>300){
							return $this->errorMsg('101307');
						}
						$request = $FmFactoring->where(['factoring_id'=>$id,'state'=>1])->update(['state'=>2,'reasons'=>$reasons]);
						if(!$request){
							return $this->errorMsg('101308');
						}
						return $this->successMsg('reload',['msg'=>'完成审核不通过操作']);
					}
					break;
				case 'pay'://确认支付
					if($row['state']!=3){
						return $this->errorMsg('101309');//当前状态非确认支付
					}
					$request = $FmFactoring->where(['factoring_id'=>$id,'state'=>3])->update(['state'=>4]);
					if(!$request){
						return $this->errorMsg('101310');
					}
					return $this->successMsg('reload',['msg'=>'完成确认支付操作']);
					break;
				default:
					exit();
					break;
			}
		}
		$FmFactoring = new FmFactoring();
		$row = $FmFactoring->alias('a')->field('a.*,b.real_name')->join('__INDEX_USER__ b','a.user_id = b.id','left')->where(['a.factoring_id'=>$id])->find();
		$row['stateName'] = $FmFactoring->getStateName($row['state']);
		$this->assign('row',$row);
		return view();
	}
}