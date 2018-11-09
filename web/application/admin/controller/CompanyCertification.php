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
use app\common\model\EntOrganization;
use app\common\model\FormUserCert;
use app\common\model\IndexGroup;
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
            $where['state'] = $status;
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
        $row->organization_code_uri = $row->organization_code_uri ?  EntCompanyAudit::getFormatImg($row->organization_code_uri) : '';
        $row->agent_id_card_uri = $row->agent_id_card_uri ? EntCompanyAudit::getFormatImg($row->agent_id_card_uri) : '';
        $row->tax_registration_uri = $row->tax_registration_uri ? EntCompanyAudit::getFormatImg($row->tax_registration_uri) : '';
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

        if($state == 1){
            return $this->auditPass($id);
        }

        return $this->auditRefuse($id,$reason);
    }

    /**
     * @desc 审核成功
     * @param $id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    protected function auditPass( $id){
        $model = new EntCompanyAudit();
        $companyModel = new EntCompany();
        $userModel = new IndexUser();
        $orgModel = new EntOrganization();
        $row = $model->find(['id'=>$id]);
        if(!$row){
            return ['status'=>1,'msg'=>'数据异常'];
        }
        //验证公司名称
        $exit = $companyModel->where(['id'=>['not in',[$row->company_id]],'audit_state'=>3,'is_deleted'=>0,'company_name'=>$row->company_name])->find();
        if($exit){
            return ['status'=>1,'msg'=>'公司名称已经存在'];
        }
        //查询数据
        $companyRow = $companyModel->where(['id'=>$row->company_id])->find();
        //公司不存在，判断当前提交用户是否已加入其他公司
        if(!$companyRow){
            $userInfo = $userModel->getInfoById($row->created_user_id);
            if($userInfo->company_id > 0){
                return ['status'=>1,'msg'=>'该用户已加入其他公司'];
            }
        }

        $commit = true;
        $companyId = $responsibleId = 0;

        //开启事务
        $model->startTrans();
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
                'organization_code_uri' => $row->organization_code_uri,
                'tax_registration_uri' => $row->tax_registration_uri,
                'business_licence_uri' => $row->business_licence_uri,
                'power_attorney_uri' => $row->power_attorney_uri,
                'agent_id_card_uri' => $row->agent_id_card_uri,
                'audit_state' => EntCompany::STATE_PASS,
                'remarks' => '',
            ];
            if($companyRow){  //更新数据
                $data['last_modified_user_id'] = '';
                $data['last_modified_user'] = '';
                $data['last_modified_time'] = microtime(true)*1000;
                $result = $companyModel->save($data,['id'=>$companyRow->id]);
                $companyId = $companyRow->id;
                $responsibleId = $companyRow->responsible_user_id;
            }else{
                $data['responsible_user_id'] = $row->created_user_id;
                $data['created_user_id'] = 0;
                $data['created_user'] = '';
                $data['created_time'] = microtime(true)*1000;
                $result = $companyModel->save($data);
                $companyId = $companyModel->id;
                $responsibleId = $row->created_user_id;
            }
            if($result === false){
                $commit = false;
            }
        }

        if($commit && !$companyRow){
            //是否设置机构
            $result = $orgModel->save(['company_id'=>$companyId,'parent_id'=>0,'org_name'=>'未分组','level'=>1,'depth_path'=>1,'created_time'=>time()]);
            if($result === false){
                $commit = false;
            }
            //如果首次认证绑定用户与公司的关系
            if($commit){
                $result = $userModel->save(['company_id'=>$companyId,'organization_id'=>$orgModel->id],['id'=>$responsibleId]);
                if($result === false){
                    $commit = false;
                }
            }
        }

        //更新companyId
        $result = $model->save(['company_id'=>$companyId,'state'=>EntCompanyAudit::STATE_PASS,'audit_time'=>time()],['id'=>$row->id]);
        if($result === false){
            $commit = false;
        }

        if($commit) {
            $model->commit();
            $yunpian = new Yunpian();
            //发送通知管理员
            if ($responsibleId > 0) {
                $userRow = $userModel->getInfoById($responsibleId);
                $yunpian->send($userRow->phone, [], Yunpian::TPL_CERT_SUC);
            }

            //同步更新原数据表
            $certModel = new FormUserCert();
            $certRow = $certModel->where(['company_name'=>$row->company_name])->order('id','desc')->find();
            if ($certRow) {
                $result = $certModel->save(['status' => 2, 'audit_time' => time()], ['id' => $certRow->id]);
                if ($result !== false) {
                    $userRow2 = $userModel->getInfoById($certRow->writer);
                    //更改角色
                    $groupId = 0;
                    if ($certRow->reg_role == '采购商') {
                        $groupId = IndexGroup::GROUP_BUYER;
                    }
                    if ($certRow->reg_role == '供应商') {
                        $groupId = IndexGroup::GROUP_SUPPLIER;
                    }
                    $groupId = $groupId > 0 ? $groupId : $userRow2->group;
                    $userModel->save(['group' => $groupId, 'real_name' => $certRow->company_name], ['id' => $certRow->writer]);
                }
            }

            return ['status' => 0, 'msg' => '审核成功'];
        }else{
            return ['status' => 0, 'msg' => '审核失败'];
        }
    }


    /**
     * @desc 审核失败||验证数据，判断是否为首次提交公司认证
     * @param $id
     * @param string $reason
     * @return array
     */
    protected function auditRefuse($id, $reason = ''){
        if(!$reason){
            return ['status'=>1,'data'=>[],'msg'=>'拒绝原因不能为空'];
        }
        $model = new EntCompanyAudit();
        $companyModel = new EntCompany();
        $userModel = new IndexUser();
        $row = $model->find(['id'=>$id]);
        if(!$row){
            return ['status'=>1,'msg'=>'数据异常'];
        }

        //查询数据
        $companyRow = $companyModel->where(['id'=>$row->company_id])->find();

        $commit = true;
        $companyId = $responsibleId = 0;


        //开启事务
        $model->startTrans();
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
                'organization_code_uri' => $row->organization_code_uri,
                'tax_registration_uri' => $row->tax_registration_uri,
                'business_licence_uri' => $row->business_licence_uri,
                'power_attorney_uri' => $row->power_attorney_uri,
                'agent_id_card_uri' => $row->agent_id_card_uri,
                'audit_state' => EntCompany::STATE_REFUSED,
                'remarks' => '',
            ];
            if($companyRow){  //更新数据
                $data['last_modified_user_id'] = '';
                $data['last_modified_user'] = '';
                $data['last_modified_time'] = time();
                $result = $companyModel->save($data,['id'=>$companyRow->id]);
                $companyId = $companyRow->id;
                $responsibleId = $companyRow->responsible_user_id;
            }else{
                $data['responsible_user_id'] = $row->created_user_id;
                $data['created_user_id'] = 0;
                $data['created_user'] = '';
                $data['created_time'] = time();
                $result = $companyModel->save($data);
                $companyId = $companyModel->id;
                $responsibleId = $row->created_user_id;
            }
            if($result === false){
                $commit = false;
            }
        }

        if($commit){
            //更新companyId
            $result = $model->save(['company_id'=>$companyId,'state'=>EntCompanyAudit::STATE_REFUSED,'audit_time'=>time()],['id'=>$row->id]);
            if($result === false){
                $commit = false;
            }
        }

        if($commit) {
            $model->commit();
            $yunpian = new Yunpian();
            //发送通知管理员
            if ($responsibleId > 0) {
                $userRow = $userModel->getInfoById($responsibleId);
                $yunpian->send($userRow->phone, [], Yunpian::TPL_CERT_FAIL);
            }

            //同步更新原数据表
            $certModel = new FormUserCert();
            $certRow = $certModel->where(['company_name'=>$row->company_name])->order('id','desc')->find();
            if ($certRow) {
                $certModel->save(['status' => 3,'refuse_reason'=>$reason, 'audit_time' => time()], ['id' => $certRow->id]);
            }

            return ['status' => 0, 'msg' => '审核成功'];
        }else{
            return ['status' => 0, 'msg' => '审核失败'];
        }
    }

}