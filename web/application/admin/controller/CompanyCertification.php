<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/10/16
 * Time: 10:24
 */

namespace app\admin\controller;

use app\common\model\EntCompany;
use app\common\model\EntCompanyAudit;
use app\common\model\IndexUser;
use sms\Yunpian;
use think\Request;

class CompanyCertification  extends Base
{

    /**
     * @desc 列表页
     * @return mixed
     */
    public function index(){
        $model = new EntCompanyAudit();
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
     * @desc 认证详情
     * @param $id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function view($id){
        $model = new EntCompanyAudit();
        $row = $model->find(['id'=>$id]);

        //格式化
        $row->business_licence_uri = $row->business_licence_uri ?  EntCompanyAudit::getFormatImg($row->business_licence_uri) : '';
        $row->legal_repres_id_card_uri = $row->legal_repres_id_card_uri ?  EntCompanyAudit::getFormatImg($row->legal_repres_id_card_uri) : '';
        $row->agent_id_card_uri = $row->agent_id_card_uri ? EntCompanyAudit::getFormatImg($row->agent_id_card_uri) : '';
        $row->opening_permit_uri = $row->opening_permit_uri ? EntCompanyAudit::getFormatImg($row->opening_permit_uri) : '';
        $row->power_attorney_uri = $row->power_attorney_uri ? EntCompanyAudit::getFormatImg($row->power_attorney_uri) : '';
        $this->assign('row',$row);
        return $this->fetch();
    }
    /**
     * @desc 审核通过
     * @param Request $request
     */
    public function audit(Request $request){
        $id = $request->post('id',0,'intval');
        $state = $request->post('state',0,'intval');  // 0=拒绝  1=通过
        $reason = $request->post('reason','');

        if(!$reason && $state == 0){
            return ['status'=>1,'data'=>[],'msg'=>'拒绝原因不能为空'];
        }

        $model = new EntCompanyAudit();
        $companyModel = new EntCompany();
        $row = $model->find(['id'=>$id]);
        if(!$row){
            return ['status'=>1,'msg'=>'数据异常'];
        }
        //查询数据
        $companyRow = $companyModel->where(['id'=>$row->company_id])->find();

        //如果审核通过 验证公司名称
        if($state == 1){
            $exit = $companyModel->where(['id'=>['not in',[$row->company_id]],'audit_state'=>3,'is_deleted'=>0,'company_name'=>$row->company_name])->find();
            if($exit){
                return ['status'=>1,'msg'=>'公司名称已经存在'];
            }
        }

        $commit = true;
        $companyId = $responsibleId = 0;

        //开启事务
        $model->startTrans();
        $result = $model->save(['state'=>3,'description'=>$reason],['id'=>$id]);
        if($result === false){
            $commit = false;
        }
        if($commit ){
            $data = [
                'company_name'=>$row->company_name,
                'enterprise_type'=>$row->enterprise_type,
                'reg_capital'=>$row->reg_capital,
                'address'=> $row->address,
                'telephone' => $row->telephone,
                'contacts' => $row->contacts,
                'contact_phone' => $row->contact_phone,
                'legal_representative'=>$row->legal_representative,
                'legal_repres_id_card_uri' => $row->legal_repres_id_card_uri,
                'opening_permit_uri' => $row->opening_permit_uri,
                'business_licence_uri' => $row->business_licence_uri,
                'power_attorney_uri' => $row->power_attorney_uri,
                'agent_id_card_uri' => $row->agent_id_card_uri,
                'audit_state' => $state == 1 ? 3 : 2,
                'remarks' => '',
            ];
            if($companyRow){  //更新数据
                $data['last_modified_user_id'] = '';
                $data['last_modified_user'] = '';
                $data['last_modified_time'] = time();
                $result = $companyModel->save($data,['id'=>$companyRow->id]);
                $companyId = $companyRow->id;
            }else{
                $data['responsible_user_id'] = $row->created_user_id;
                $data['created_user_id'] = 0;
                $data['created_user'] = '';
                $data['created_time'] = time();
                $result = $companyModel->save($data);
                $companyId = $companyModel->id;
            }
            if($result === false){
                $commit = false;
            }
        }

        if($commit && !$companyRow){
            //更新companyId
            $result = $model->save(['company_id'=>$companyId],['id'=>$row->id]);
            if($result === false){
                $commit = false;
            }
        }

        if($commit){
            $model->commit();

            //发送通知管理员
            $userModel = new IndexUser();
            $userRow = $userModel->getInfoById($id);

            //发送短信通知
            $yunpian = new Yunpian();
//            if($state == 1){
//                $yunpian->send($userRow->phone,[],Yunpian::TPL_CERT_SUC);
//            }else{
//                $yunpian->send($userRow->mobile,[],Yunpian::TPL_CERT_FAIL);
//            }

            //同步更新原数据表

            return ['status'=>0,'msg'=>'审核成功'];
        }else{
            $model->rollback();
            return ['status'=>1,'msg'=>'审核失败'];
        }
    }

}