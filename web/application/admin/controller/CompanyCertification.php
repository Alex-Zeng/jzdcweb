<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/10/16
 * Time: 10:24
 */

namespace app\admin\controller;


use app\common\model\EntCompany;
use think\Request;

class CompanyCertification  extends Base
{

    /**
     * @desc 列表页
     * @return mixed
     */
    public function index(){
        $model = new EntCompany();
        $k = Request::instance()->get('k','','trim');
        $status = Request::instance()->get('status',0,'intval');
        $where = [];
        if(isset($k) && $k){
            $where['company_name'] = ['like','%'.$k.'%'];
        }
        if($status > 0){
            $where['audit_state'] = $status;
        }
        $rows = $model->where($where)->order(['id'=>'desc'])->paginate(20,false,['query'=>request()->param()]);

        $this->assign('k',$k);
        $this->assign('status',$status);
        $this->assign('list',$rows);
        $this->assign('page',$rows->render());
        return $this->fetch();
    }

    /**
     * @desc 详情页
     * @param $id
     * @return mixed
     */
    public function view($id){

        return $this->fetch();
    }

    /**
     * @desc 审核通过
     * @param Request $request
     */
    public function confirm(Request $request){

    }


    /**
     * @desc 审核拒绝
     * @param Request $request
     */
    public function refuse(Request $request){

    }

}