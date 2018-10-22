<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/10/18
 * Time: 17:41
 */

namespace app\api\controller;


use app\common\model\EntCode;
use app\common\model\EntCompany;
use app\common\model\EntCompanyAudit;
use app\common\model\EntOrganization;
use app\common\model\IndexUser;
use sms\Yunpian;
use think\Request;

class CompanyAudit extends Base
{


    /**
     * @desc 获取认证权限
     * @return array|void
     */
    public function getPermission(){
        $auth = $this->auth();
        if($auth){
            return $auth;
        }
        //
        $userModel = new IndexUser();
        $companyModel = new EntCompany();
        //获取用户ID
        $userId = $this->userId;
        $roleId = 0;   //未加入公司
        if($userId > 0){
            $userInfo = $userModel->getInfoById($userId);
            if($userInfo->company_id > 0){
                $companyInfo = $companyModel->where(['id'=>$userInfo->company_id,'is_deleted'=>0])->find();
                if($companyInfo){
                    if($userId != $companyInfo->responsible_user_id){
                        $roleId = 1; //公司管理员
                    }else{
                        $roleId = 2; //公司非管理员
                    }
                }
            }
        }

        return ['status'=>0,'data'=>['role'=>$roleId],'msg'=>''];
    }

    /**
     * @desc 获取企业认证信息
     */
    public function get(){
        $auth = $this->auth();
        if($auth){
            return $auth;
        }
        $model = new EntCompanyAudit();
        $userModel = new IndexUser();
        //查询用户数据
        $userInfo = $userModel->getInfoById($this->userId);
        $where = [];

        if($userInfo->company_id == 0){  //未关联公司
            //查询

            $where['created_user_id'] = $this->userId;
            return ['status'=>1,'data'=>['status'=>0],'msg'=>'无企业认证信息'];
        }else{ //已关联公司
            $where['company_id'] = $userInfo->company_id;
        }
        $companyAuditInfo = $model->where(['created_user_id'=> $this->userId])->find();
        if(!$companyAuditInfo){
            return ['status'=>1,'data'=>['status'=>0],'msg'=>'无企业认证信息'];
        }

        $data = [
            'status'=>$companyAuditInfo->state,  //认证状态
            'id' => $companyAuditInfo->id,  //ID
            'companyName' => $companyAuditInfo->company_name,  //企业名称
            'representative' => $companyAuditInfo->legal_representative, //法定代表人
            'capital' => $companyAuditInfo->reg_capital,  //注册资金
            'address' => $companyAuditInfo->address,  //企业地址
            'property' => $companyAuditInfo->enterprise_type,   //企业性质
            'isAgent' => $companyAuditInfo->agent_id_card_uri ? 1 : 0,
            'business' => $companyAuditInfo->business_licence_uri, //营业执照
            'agentIdentityCard' => $companyAuditInfo->agent_id_card_uri, //代办人身份
            'attorney' => $companyAuditInfo->power_attorney_uri,//委托书
            'orgStructureCode' => $companyAuditInfo->organization_code_uri,   //组织机构代码
            'taxRegistrationCert' => $companyAuditInfo->tax_registration_uri, //税务登记
            'businessPath' => EntCompanyAudit::getFormatImg($companyAuditInfo->business_licence_uri),
            'orgStructureCodePath' => EntCompanyAudit::getFormatImg($companyAuditInfo->organization_code_uri),
            'taxRegistrationCertPath' => EntCompanyAudit::getFormatImg($companyAuditInfo->tax_registration_uri),
            'agentIdentityCardPath' => EntCompanyAudit::getFormatImg($companyAuditInfo->agent_id_card_uri),
            'attorneyPath' => EntCompanyAudit::getFormatImg($companyAuditInfo->power_attorney_uri),
            'refuseReason' => $companyAuditInfo->description
        ];

        return ['status'=>0,'data'=>$data,'msg'=>''];
    }

    /**
     * @desc 提交数据
     */
    public function submit(Request $request){
        $id = $request->post('id',0,'intval');
        $agent = $request->post('agent', 0, 'intval');
        $companyName = $request->post('companyName', '','htmlspecialchars'); //公司名称
        $representative = $request->post('representative', '','htmlspecialchars'); //代表人
        $property = $request->post('property', 0, 'intval'); //企业性质
        $capital = $request->post('capital', ''); //资金
        $detailAddress = $request->post('address', '','htmlspecialchars'); //住址
        $businessPath = $request->post('business', '');  //营业执照
        $orgStructureCodePermits = $request->post('orgStructureCode', ''); //组织机构代码
        $taxRegistrationCert = $request->post('taxRegistrationCert', ''); //税务登记
        $agentIdentityCard = $request->post('agentIdentityCard', '');//代理人身份证
        $powerOfAttorney = $request->post('attorney', ''); //代办人授权委托书

        if (!$companyName) {
            return ['status' => 1, 'data' => [], 'msg' => '企业名称不能为空'];
        }
        if (!$representative) {
            return ['status' => 1, 'data' => [], 'msg' => '法人代表不能为空'];
        }
        if (!$businessPath) {
            return ['status' => 1, 'data' => [], 'msg' => '营业执照必须上传'];
        }

        if ($agent == 1) {
            if (!$agentIdentityCard) {
                return ['status' => 1, 'data' => [], 'msg' => '代理人身份证必须上传'];
            }
            if (!$powerOfAttorney) {
                return ['status' => 1, 'data' => [], 'msg' => '代办人授权委托书必须上传'];
            }
        }

        $auth = $this->auth();
        if ($auth) {
            return $auth;
        }

        $model = new EntCompanyAudit();
        $userModel = new IndexUser();
        $companyModel = new EntCompany();
        //查询用户数据
        $userInfo = $userModel->getInfoById($this->userId);
        //查询当前数据
        $companyAuditInfo = $model->where(['id'=> $id])->find();

        //
        $companyIds = [];
        if($companyAuditInfo){
            $companyIds[] = $companyAuditInfo->company_id;
        }
        $exit = $companyModel->where(['id'=>['not in',$companyIds],'company_name'=>$companyName,'audit_state'=>EntCompany::STATE_PASS])->find();
        if($exit){
            return ['status'=>1,'data'=>[],'msg'=>'企业名称已经存在'];
        }

        $data = [
            'edit_time' => time(),
            'writer' => $this->userId,
            'editor' => $this->userId,
            'company_name' => $companyName,
            'business_licence_uri' => $businessPath,
            'status' => EntCompanyAudit::STATE_PENDING,
            'enterprise_type' => $property,
            'reg_capital' => $capital,
            'legal_representative' => $representative,
            'organization_code_uri' => $orgStructureCodePermits,
            'agent_id_card_uri' => $agentIdentityCard,
            'tax_registration_uri' => $taxRegistrationCert,
            'address' => $detailAddress,
            'power_attorney_uri' => $powerOfAttorney,
            'last_modified_user_id' => $this->userId,
            'last_modified_user' => $userInfo->username,
            'last_modified_time' => microtime(),
        ];

        if($companyAuditInfo){
            if($companyAuditInfo->created_user_id != $this->userId || $companyAuditInfo->company_id != $userInfo->company_id){
                return ['status' => 1, 'data' => [], 'msg' => '无权限操作'];
            }
            if($companyAuditInfo->state == EntCompanyAudit::STATE_PENDING) {
                return ['status' => 0, 'data' => [], 'msg' => '已提交审核，请勿重复提交...'];
            }
            //更新数据
            $result = $model->save($data,['id'=>$id]);
        }else{ //添加数据
            $data['created_user_id'] = $this->userId;
            $data['created_user'] = $userInfo->username;
            $data['created_time'] = microtime();
            $result = $model->save($data);
        }

        if ($result !== false) {
            //发送短信通知
            $userModel = new IndexUser();
            $userInfo = $userModel->getInfoById($this->userId);
            if ($userInfo && $userInfo->phone) {
                $yunPian = new Yunpian();
                $yunPian->send($userInfo->phone, [], Yunpian::TPL_CERT_SUBMIT);
            }

            //原数据表写入数据


            //发送邮件通知
            $emailStr = config('JZDC_OP_EMAIL');
            $subject='集众电采平台系统认证通知';
            $content='现有用户提交企业认证申请，请及时跟进，谢谢。';
            SendMail($emailStr,$subject,$content);
            return ['status' => 0, 'data' => [], 'msg' => '已提交认证信息,等待审核...'];
        }
        return ['status' => 1, 'data' => [], 'msg' => '提交审核失败'];
    }

    /**
     * @desc 邀请码验证
     * @param Request $request
     * @return array|void
     */
    public function invitationVerify(Request $request){
        $code = $request->post('code','','trim');
        if(!$code){
            return ['status'=>1,'data'=>[],'msg'=>'验证码不能为空'];
        }
        $auth = $this->auth();
        if($auth){
            return $auth;
        }
        //
        $codeModel = new EntCode();
        $companyModel = new EntCompany();
        $origanizationModel = new EntOrganization();

        //验证邀请码
        $codeInfo = $codeModel->where(['code'=>$code,'used'=>0])->find();
        if(!$codeInfo){
            return ['status'=>1,'data'=>[],'msg'=>'无效验证码'];
        }

        $companyInfo = $companyModel->where(['id'=>$codeInfo->company_id])->find();
        $origanizationInfo = $origanizationModel->where(['id'=>$codeInfo->organization_id])->find();

        return ['status'=>0,'data'=>['companyName'=>$companyInfo ? $companyInfo->company_name : '','organizationName'=>$origanizationInfo ? $origanizationInfo->org_name : '']];
    }

    /**
     * @desc 确认邀请
     * @param Request $request
     * @return array|void
     */
    public function invitationConfirm(Request $request){
        $code = $request->post('code','','trim');
        if(!$code){
            return ['status'=>1,'data'=>[],'msg'=>'验证码不能为空'];
        }
        $auth = $this->auth();
        if($auth){
            return $auth;
        }
        //
        $codeModel = new EntCode();
        $userModel = new IndexUser();
        //验证邀请码
        $codeInfo = $codeModel->where(['code'=>$code,'used'=>0])->find();
        if(!$codeInfo){
            return ['status'=>1,'data'=>[],'msg'=>'无效验证码'];
        }

        //加入该企业
        $result = $userModel->save(['company_id'=>$codeInfo->company_id,'organization_id'=>$codeInfo->organization_id],['id'=>$this->userId]);
        if($result !== false){
            return ['status'=>0,'data'=>[],'msg'=>'成功加入该企业'];
        }

        return ['status'=>1,'data'=>[],'msg'=>'失败加入该企业'];
    }

}