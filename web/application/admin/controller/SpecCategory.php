<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/8/28
 * Time: 16:14
 */

namespace app\admin\controller;

use app\common\model\IndexUser;
use app\common\model\SmProductCategory;
use app\common\model\SmSpecCategory;
use app\common\model\SmSpecCategoryDetails;
use think\Request;

class SpecCategory extends Base{

    /**
     * @desc 规格列表
     * @return mixed
     */
    public function index($id){
        $model = new SmProductCategory();
        $row = $model->where(['id'=>$id])->find();
        if(!$row){
            //跳转错误页面
        }
        $postion[] = ['id'=>$row->id,'name'=>$row->name];
        if($row->parent_id != 0){
            $row2 = $model->where(['id'=>$row->parent_id])->find();
            if($row2){
                $postion[] = ['id'=>$row2->id,'name'=>$row2->name];
                if($row2->parent_id != 0){
                    $row3 = $model->where(['id'=>$row2->id])->find();
                    if($row3){
                        $postion[] = ['id'=>$row3->id,'name'=>$row3->name];
                    }
                }
            }
        }

        $specCategoryModel = new SmSpecCategory();
        $specCategoryRow = $specCategoryModel->where(['category_id'=>$id])->find();

        $color = 0;
        $details = [];

        //是否设置颜色规格
        if($specCategoryRow){
            $specCategoryDetailsModel = new SmSpecCategoryDetails();
            //查询颜色规格
             $colorRow = $specCategoryDetailsModel->where(['spec_category_id'=>$specCategoryRow->id,'is_standard'=>1,'is_deleted'=>0])->find();
             if($colorRow){
                 $color = 1;
             }
            //查询自定义规格
            $details = $specCategoryDetailsModel->where(['spec_category_id'=>$specCategoryRow->id,'is_standard'=>0,'is_deleted'=>0])->select();
        }

        $this->assign('specCategoryRow',$specCategoryRow);
        $this->assign('color',$color);
        $this->assign('option',$details ? 1 : 0);
        $this->assign('details',$details);
        $this->assign('postion',$postion);
        $this->assign('categoryId',$id);
        return $this->fetch();
    }


    /**
     * @desc
     * @param Request $request
     * @return array
     */
    public function set(Request $request){
        $categoryId = $request->post('id',0);
        $value = $request->post('value',0);
        $field = $request->post('field','');
        $model = new SmSpecCategory();

        $userId = getUserId();
        $userModel = new IndexUser();
        $userInfo = $userModel->getInfoById($userId);

        $row = $model->where(['category_id'=>$categoryId,'is_deleted'=>0])->find();
        if(!$row){
            $model->save(['category_id'=>$categoryId,'spec_type_name'=>'','created_user_id'=>$userId,'created_user'=>$userInfo ? $userInfo->username : '','create_time'=>time()]);
            $id = $model->id;
        }else{
            $id = $row->id;
        }

        switch ($field){
            case 'color':
                $detailModel = new SmSpecCategoryDetails();
                if($value == 1){  //启用
                    $exit = $detailModel->where(['spec_category_id'=>$id,'is_standard'=>1,'is_deleted'=>0])->find();
                    if($exit){ //更新
                    }else{  //添加
                        $data = ['spec_category_id'=>$id,'spec_attr_name'=>'颜色','is_standard'=>1,'created_user_id'=>$userId,'created_user'=>$userInfo ? $userInfo->username : '','created_time'=>time()];
                        $result = $detailModel->save($data);
                    }
                }else{ //禁用
                    $result = $detailModel->save(['is_deleted'=>1,'deleted_user'=>$userInfo ? $userInfo->username : '','deleted_time'=>time()],['spec_category_id'=>$id,'is_standard'=>1,'is_deleted'=>0]);
                }
                break;
            case 'option': //修改规格名称
                $result = $model->save(['spec_type_name'=>$value],['category_id'=>$categoryId,'is_deleted'=>0]);
                break;
        }

        if($result !== false){
            return ['status'=>0,'data'=>[],'msg'=>'更新成功'];
        }

        return ['status'=>1,'data'=>[],'msg'=>'更新失败'];
    }

    /**
     * @desc 删除
     * @param Request $request
     * @param $id
     * @return array
     */
    public function delete(Request $request,$id){
        $model = new SmSpecCategoryDetails();
        $row = $model->where(['id'=>$id])->find();
        if(!$row){
            return ['status'=>1,'data'=>[],'msg'=>'数据不存在'];
        }

        $userId = getUserId();
        $userModel = new IndexUser();
        $userInfo = $userModel->getInfoById($userId);


        $result = $model->save(['is_deleted'=>1,'deleted_user'=>$userInfo ? $userInfo->username : '','deleted_time'=>time()],['id'=>$id]);
        if($result !== false){
            return ['status'=>0,'data'=>[],'msg'=>'删除成功'];
        }
        return ['status'=>1,'data'=>[],'msg'=>'删除失败'];
    }

    /**
     * @desc 添加分类规格
     * @param Request $request
     * @param $id
     * @return array
     */
    public function create(Request $request,$categoryId){
        $name = $request->post('name','trim');
        $model = new SmSpecCategory();

        $userId = getUserId();
        $userModel = new IndexUser();
        $userInfo = $userModel->getInfoById($userId);

        $row = $model->where(['category_id'=>$categoryId,'is_deleted'=>0])->find();
        if(!$row){
            $model->save(['category_id'=>$categoryId,'spec_type_name'=>'','created_user_id'=>$userId,'created_user'=>$userInfo ? $userInfo->username : '','create_time'=>time()]);
            $id = $model->id;
        }else{
            $id = $row->id;
        }

        $model = new SmSpecCategoryDetails();
        //验证数据是否存在
        $exit = $model->where(['spec_category_id'=>$id,'spec_attr_name'=>$name,'is_standard'=>0,'is_deleted'=>0])->find();
        if($exit){
            return ['status'=>1,'data'=>[],'msg'=>'规格选项已经存在'];
        }

        $data = ['spec_category_id'=>$id,'spec_attr_name'=>$name,'is_standard'=>0,'created_user_id'=>$userId,'created_user'=>$userInfo ? $userInfo->username : '','created_time'=>time()];
        $result = $model->save($data);
        if($result == true){
            return ['status'=>0,'data'=>[],'msg'=>'添加成功'];
        }
        return ['status'=>1,'data'=>[],'msg'=>'添加失败'];
    }

    /**
     * @desc 修改分类规格
     * @param Request $request
     * @param $id
     */
    public function edit(Request $request,$id){
        $name = $request->post('name','trim');

        $userId = getUserId();
        $userModel = new IndexUser();
        $userInfo = $userModel->getInfoById($userId);

        $model = new SmSpecCategoryDetails();

        $row = $model->where(['id'=>$id])->find();
        if(!$row){
            return ['status'=>1,'data'=>[],'msg'=>'数据异常'];
        }

        //验证名称是否存在
        $exit = $model->where(['spec_category_id'=>$row->spec_category_id,'spec_attr_name'=>$name,'is_standard'=>0,'is_deleted'=>0,'id'=>['not in',[$id]]])->find();
        if($exit){
            return ['status'=>1,'data'=>[],'msg'=>'规格选项已经存在'];
        }

        $data = ['spec_attr_name'=>$name,'last_modified_user_id'=>$userId,'last_modified_user'=>$userInfo ? $userInfo->username : '','last_modified_time'=>time()];
        $result = $model->save($data,['id'=>$id]);
        if($result !== false){
            return ['status'=>0,'data'=>[],'msg'=>'修改成功'];
        }
        return ['status'=>1,'data'=>[],'msg'=>'修改失败'];
    }


}