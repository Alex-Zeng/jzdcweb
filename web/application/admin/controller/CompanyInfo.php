<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/10/16
 * Time: 10:42
 */

namespace app\admin\controller;

use app\common\model\EntCompany;
use app\common\model\EntOrganization;
use app\common\model\IndexUser;
use think\Request;

class CompanyInfo extends Base
{

    /**
     * @desc 企业列表页面
     * @return mixed
     */
    public function index(){
        $type = Request::instance()->get('type',0);
        $k = Request::instance()->get('k','');

        $model = new EntCompany();
        $userModel = new IndexUser();

        $where = [];
        if($k){
            $where['company_name'] = ['like','%'.$k.'%'];
        }
        if($type > 0){
            if($type == 1){ //未设置
                $where['responsible_user_id'] = 0;
            }elseif ($type == 2){ //已设置
                $where['responsible_user_id'] = ['gt',0];
            }
        }

        $data = [];
        $rows = $model->where($where)->order(['id'=>'desc'])->paginate(null,false,['query'=>request()->param()]);
        foreach ($rows as $row){
            $count = $userModel->where(['company_id'=>$row->id])->count();
            $userInfo =  $userModel->getInfoById($row->responsible_user_id);

            $data[] = [
                'companyId' => $row->id,
                'companyName' => $row->company_name,
                'memberCount' => $count,
                'adminName' => $userInfo ? $userInfo->username : '未设置',
                'adminPhone' => $userInfo ? $userInfo->phone : '-'
            ];
        }



        $this->assign('type',$type);
        $this->assign('k',$k);
        $this->assign('rows',$data);
        $this->assign('page',$rows->render());
        return $this->fetch();
    }

    /**
     * @desc 部门列表
     * @param int $companyId
     */
    public function department($companyId = 0){
        $orgModel = new EntOrganization();
        $companyModel = new EntCompany();

        $companyRow = $companyModel->where(['id'=>$companyId])->find();
        if(!$companyRow){
            $this->errorTips();
        }
        //
        $field = ['a.id','a.company_id','a.org_name','b.id as userId','b.username','b.phone'];
        $where = ['a.company_id'=>$companyId,'a.is_deleted'=>0];
        $rows = $orgModel->alias('a')
                         ->join(['jzdc_index_user'=>'b'],'a.id=b.organization_id','left')
                         ->where($where)
                         ->field($field)
                         ->order(['a.id'=>'desc'])
                         ->paginate(null,false,['query'=>request()->param()]);

        $this->assign('companyRow',$companyRow);
        $this->assign('rows',$rows);
        $this->assign('page',$rows->render());
        return $this->fetch();
    }


    /**
     * @desc 设置管理员
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function responsible(Request $request){
        $companyId = $request->post('companyId',0,'intval');
        $responsibleId = $request->post('userId',0,'intval');

        $model = new EntCompany();
        $row = $model->where(['id'=>$companyId])->find();
        if(!$row){
            return ['status'=>1,'msg'=>'企业不存在'];
        }

        $result = $model->save(['responsible_user_id'=>$responsibleId],['id'=>$companyId]);

        if($result !== false){
            return ['status'=>0,'msg'=>'设置成功'];
        }

          return ['status'=>1,'msg'=>'设置失败'];
    }


}