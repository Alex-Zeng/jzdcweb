<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/8/28
 * Time: 12:03
 */

namespace app\admin\controller;

use app\common\model\IndexUser;
use app\common\model\SmCategorySpecAttrKey;
use think\Request;
use app\common\model\SmProductCategory;

class ProductCategory  extends Base{

    /**
     * @desc 商品类型列表
     * @return mixed
     */
    public function index(){
        $model = new SmProductCategory();
        $fields = ['id','name','parent_id','is_display','ordering'];
        $rows = $model->where(['parent_id'=>0,'is_deleted'=>0])->field($fields)->select();

        $attrKeyModel = new SmCategorySpecAttrKey();
        $list = [];
        //查询第二层
        foreach($rows as $row){
            $list[] = [
                'id'=>$row->id,
                'name'=>$row->name,
                'parent'=>$row->parent_id,
                'sequence'=>$row->ordering,
                'display' => $row->is_display,
                'quantity' => $attrKeyModel->where(['category_id'=>$row->id,'is_deleted'=>0])->count(),
                'level'=>0,
            ];
            //查询第二层
            $rows2 = $model->where(['parent_id'=>$row->id,'is_deleted'=>0])->field($fields)->select();
            foreach ($rows2 as $row2){
                $list[] = [
                    'id'=>$row2->id,
                    'name'=>$row2->name,
                    'parent'=>$row2->parent_id,
                    'sequence'=>$row2->ordering,
                    'display' => $row2->is_display,
                    'quantity' => $attrKeyModel->where(['category_id'=>$row2->id,'is_deleted'=>0])->count(),
                    'level'=>1
                ];
                $rows3 = $model->where(['parent_id'=>$row2->id,'is_deleted'=>0])->field($fields)->select();
                foreach ($rows3 as $row3){
                    $list[] = [
                        'id'=>$row3->id,
                        'name'=>$row3->name,
                        'parent'=>$row3->parent_id,
                        'sequence'=>$row3->ordering,
                        'display' => $row3->is_display,
                        'quantity' => $attrKeyModel->where(['category_id'=>$row3->id,'is_deleted'=>0])->count(),
                        'level'=>2,
                    ];
                }
            }
        }
        $this->assign('list',$list);
        return $this->fetch();
    }

    /**
     * @desc 添加分类
     * @param Request $request
     * @return mixed
     */
    public function create(Request$request,$id = 0){
        $model = new SmProductCategory();
        if($request->isPost()){
            $name = $request->post('name','','trim');
            $parent = $request->post('parent',0,'intval');
            $sequence = $request->post('sequence',0,'intval');
            $display = $request->post('is_display',1,'intval');

            $userId = getUserId();
            $userModel = new IndexUser();
            $userInfo = $userModel->getInfoById($userId);

            //查询父级
            $parentRow = $model->where(['id'=>$parent])->find();


            $data = [
                'name' => $name,
                'parent_id' => $parent,
                'ordering' => $sequence,
                'is_display' => $display,
                'level' => $parentRow ?  intval($parentRow->level +1) : 1,
                'created_user_id' => $userId,
                'created_user' => $userInfo ? $userInfo->username : '',
                'created_time' => time()
            ];

            $result = $model->save($data);
            if($result){
                $depth = $parentRow ?  $parentRow->depth_path.'/'.$parentRow->id.'/'.$model->id : '/'.$model->id;
                $model->save(['depth_path'=>$depth],['id'=>$model->id]);
                $this->redirect(url('admin/product_category/index'));
            }
        }
        $this->assign('parent_id',$id);
        return $this->fetch();
    }

    /**
     * @desc 修改分类
     * @param Request $request
     * @param $id
     * @return mixed
     */
    public function edit(Request$request,$id){
        $model = new SmProductCategory();
        $field = ['id','name','parent_id','ordering','is_display'];
        $row = $model->where(['id'=>$id])->field($field)->find();

        if($request->isPost()){
            $name = $request->post('name','','trim');
            $parent = $request->post('parent',0,'intval');
            $sequence = $request->post('sequence',0,'intval');
            $display = $request->post('is_display',1,'intval');

            $userId = getUserId();
            $userModel = new IndexUser();
            $userInfo = $userModel->getInfoById($userId);

            //查询父级
            $parentRow = $model->where(['id'=>$parent])->find();

            $data = [
                'name' => $name,
                'parent_id' => $parent,
                'ordering' => $sequence,
                'is_display' => $display,
                'level' => $parentRow ?  intval($parentRow->level +1) : 1,
                'depth_path' =>$parentRow ?  $parentRow->depth_path.'/'.$parentRow->id.'/'.$id : '/'.$id ,
                'last_modified_user_id' => $userId,
                'last_modified_user' => $userInfo ? $userInfo->username : '',
                'last_modified_time' => time()
            ];
            $result = $model->save($data,['id'=>$id]);
            if($result !== false){
                $this->redirect(url('admin/product_category/index'));
            }
        }
        $this->assign('row',$row);
        return $this->fetch();
    }

    /**
     * @desc 删除类型
     * @param $id
     */
    public function delete($id){
        $model = new SmProductCategory();
        $row = $model->where(['id'=>$id])->find();
        if(!$row){
            return ['status'=>1,'data'=>[],'msg'=>'数据错误'];
        }

        //判断是否有子分类
        $child = $model->where(['parent_id'=>$row->id])->find();
        if($child){
            return ['status'=>1,'data'=>[],'msg'=>'请先删除子类'];
        }

        $userId = getUserId();
        $userModel = new IndexUser();
        $userInfo = $userModel->getInfoById($userId);

        $data = [
            'is_deleted' => 1,
            'deleted_user' => $userInfo ? $userInfo->username : '',
            'deleted_time' => time()
        ];


        $result = $model->save($data,['id'=>$id]);
        if($result !== false){
            return ['status'=>0,'data'=>[],'msg'=>'删除成功'];
        }

        return ['status'=>1,'data'=>[],'msg'=>'删除失败'];
    }

    /**
     * @desc 设置排序
     * @param $id
     */
    public function sequence(Request $request){
        $id = $request->post('id',0);
        $sequence = $request->post('value',0);
        $model = new SmProductCategory();
        $result = $model->save(['ordering'=>$sequence],['id'=>$id]);
        if($result == true){
            return ['status'=>0,'data'=>[],'msg'=>'更新成功'];
        }

        return ['status'=>1,'data'=>[],'msg'=>'更新失败'];
    }


}