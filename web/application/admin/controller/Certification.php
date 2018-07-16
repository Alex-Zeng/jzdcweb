<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/6/14
 * Time: 9:45
 */

namespace app\admin\controller;

use app\common\model\FormUserCert;
use app\common\model\IndexGroup;
use sms\Yunpian;
use think\Request;


class Certification extends Base{


    /**
     * @desc 认证列表
     * @param Request $request
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function index(Request $request){
        $model = new FormUserCert();
        $k = Request::instance()->get('k','','trim');
        $type = Request::instance()->get('type','');
        $status = Request::instance()->get('status',0,'intval');
        $where = [];
        if(isset($k) && $k){
            $where['a.company_name|a.legal_representative|a.contact_point'] = ['like','%'.$k.'%'];
        }
        if($status > 0){
            $where['a.status'] = $status;
        }
        if(isset($type) && $type){
            $where['reg_role'] = $type;
        }

        $fields = ['a.*','b.username','b.phone'];
        $rows = $model->alias('a')->join(config('prefix').'index_user b','a.writer=b.id','left')->where($where)->field($fields)->order('a.write_time','desc')->paginate(20,false,['query'=>request()->param()]);

        $this->assign('type',$type);
        $this->assign('k',$k);
        $this->assign('status',$status);
        $this->assign('list',$rows);
        $this->assign('page',$rows->render());
        return $this->fetch();
    }


    /**
     * @desc 认证详情
     * @param $id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function view($id){
        $model = new FormUserCert();
        $row = $model->find(['id'=>$id]);

        //格式化
        $row->business_license = $row->business_license ?  FormUserCert::getFormatImg($row->business_license) : '';
        $row->legal_identity_card = $row->legal_identity_card ?  FormUserCert::getFormatImg($row->legal_identity_card) : '';
        $row->agent_identity_card = $row->legal_identity_card ? FormUserCert::getFormatImg($row->agent_identity_card) : '';
        $row->permits_accounts = $row->permits_accounts ? FormUserCert::getFormatImg($row->permits_accounts) : '';
        $row->org_structure_code_permits = $row->org_structure_code_permits ? FormUserCert::getFormatImg($row->org_structure_code_permits) : '';
        $row->tax_registration_cert = $row->tax_registration_cert ? FormUserCert::getFormatImg($row->tax_registration_cert) : '';

        $this->assign('row',$row);
        return $this->fetch();
    }

    /**
     * @desc 审核
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function confirm(Request $request){
        $id = $request->post('id','0','intval');
        $model = new FormUserCert();
        $row = $model->find(['id'=>$id]);
        if(!$row){
            return ['status'=>1,'data'=>[],'msg'=>'数据异常'];
        }
        $result = $model->save(['status'=>2],['id'=>$id]);
        if($result !== false){
            $userModel = new \app\common\model\IndexUser();
            $userRow = $userModel->getInfoById($row->writer);

            //更改角色
            $groupId = 0;
            if($row->reg_role == '采购商'){
                $groupId = IndexGroup::GROUP_BUYER;
            }
            if($row->reg_role == '供应商'){
                $groupId = IndexGroup::GROUP_SUPPLIER;
            }
            $groupId = $groupId>0 ? $groupId : $userRow->group;
            $userModel->save(['group'=>$groupId,'real_name'=>$row->company_name],['id'=>$row->writer]);
            //发送短信通知
            $yunpian = new Yunpian();
            $yunpian->send($userRow->phone,[],Yunpian::TPL_CERT_SUC);

            return ['status'=>0,'data'=>[],'msg'=>'审核成功'];
        }
        return ['status'=>1,'data'=>[],'msg'=>'审核失败'];
    }

    /**
     * @desc 拒绝
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function refuse(Request $request){
        $id = $request->post('id','0','intval');
        $reason = $request->post('reason','');
        if(!$reason){
            return ['status'=>1,'data'=>[],'msg'=>'拒绝原因不能为空'];
        }
        $model = new FormUserCert();
        $row = $model->find(['id'=>$id]);
        if(!$row){
            return ['status'=>1,'data'=>[],'msg'=>'数据异常'];
        }
        $result = $model->save(['status'=>3,'refuse_reason'=>$reason],['id'=>$id]);
        if($result !== false){
            //发送短信通知
            $userModel = new \app\common\model\IndexUser();
            $userRow = $userModel->getInfoById($row->writer);

            //发送短信通知
            $yunpian = new Yunpian();
            $yunpian->send($userRow->phone,[],Yunpian::TPL_CERT_FAIL);
            return ['status'=>0,'data'=>[],'msg'=>'审核成功'];
        }
        return ['status'=>1,'data'=>[],'msg'=>'审核拒绝失败'];
    }

}