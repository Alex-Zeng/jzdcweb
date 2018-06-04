<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/6/3
 * Time: 14:45
 */
namespace app\admin\controller;

use app\common\model\MallType;
use app\common\model\MallTypeOption;
use think\Request;

class TypeOption extends Base{

    /**
     * @desc 商品分类规格列表
     * @param int $type
     * @return mixed
     */
    public function index($id){
        $model = new MallType();
        $row = $model->where(['id'=>$id])->find();
        if(!$row){
            //跳转错误页面
        }
        $postion[] = ['id'=>$row->id,'name'=>$row->name];
        if($row->parent != 0){
            $row2 = $model->where(['id'=>$row->parent])->find();
            if($row2){
                $postion[] = ['id'=>$row2->id,'name'=>$row2->name];
                if($row2->parent != 0){
                    $row3 = $model->where(['id'=>$row2->id])->find();
                    if($row3){
                        $postion[] = ['id'=>$row3->id,'name'=>$row3->name];
                    }
                }
            }
        }

        //
        $optionModel = new MallTypeOption();
        $optionRows = $optionModel->where(['type_id'=>$id])->select();
        $this->assign('typeRow',$row);
        $this->assign('typeOption',$optionRows);
        $this->assign('postion',array_reverse($postion));

        return $this->fetch();
    }

    /**
     * @desc 添加分类规格
     * @param Request $request
     * @param $id
     * @return array
     */
    public function create(Request $request,$id){
        $name = $request->post('name','');
        $sequence = $request->post('sequence',0);
        $shopId = $request->post('shop_id',0);

        $model = new MallTypeOption();
        $data = ['type_id'=>$id,'name'=>$name,'sequence'=>$sequence,'shop_id'=>$shopId];
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
        $name = $request->post('name','');
        $sequence = $request->post('sequence',0);
        $shopId = $request->post('shop_id',0);

        $model = new MallTypeOption();
        $data = ['name'=>$name,'sequence'=>$sequence,'shop_id'=>$shopId];
        $result = $model->save($data,['id'=>$id]);
        if($result == true){
            return ['status'=>0,'data'=>[],'msg'=>'修改成功'];
        }
        return ['status'=>1,'data'=>[],'msg'=>'修改失败'];
    }

    /**
     * @param Request $request
     * @return array
     */
    public function set(Request $request){
        $id = $request->post('id',0);
        $value = $request->post('value',0);
        $field = $request->post('field','');
        $model = new MallType();
        $row = $model->where(['id'=>$id])->find();
        if(!$row){
            return ['status'=>1,'data'=>[],'msg'=>'数据错误'];
        }

        $result = $model->save([$field=>$value],['id'=>$id]);
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
        $model = new MallTypeOption();
        $row = $model->where(['id'=>$id])->find();
        if(!$row){
            return ['status'=>1,'data'=>[],'msg'=>'数据不存在'];
        }

        $result = $model->where(['id'=>$id])->delete();
        if($result == true){
            return ['status'=>0,'data'=>[],'msg'=>'删除成功'];
        }
        return ['status'=>1,'data'=>[],'msg'=>'删除失败'];
    }
}