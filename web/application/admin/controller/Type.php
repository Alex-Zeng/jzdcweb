<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/6/1
 * Time: 23:00
 */
namespace app\admin\controller;

use app\common\model\MallGoods;
use app\common\model\MallType;
use think\Request;

class Type extends Base{

    /**
     * @return mixed
     */
    public function index(){
        $model = new MallType();
        $k = Request::instance()->get('k','');
        $fields = ['id','name','parent','sequence','path','push','web_path'];
        $rows = $model->where(['parent'=>0])->field($fields)->select();

        $list = [];
        //查询第二层  program/mall/type_icon/34.png
        foreach($rows as $row){
            $list[] = ['id'=>$row->id,'name'=>$row->name,'parent'=>$row->parent,'sequence'=>$row->sequence,'level'=>0,'push'=>$row->push,'icon'=>MallType::getFormatIcon($row->path),'web_icon'=>MallType::getFormatIcon($row->web_path)];
            //查询第二层
            $rows2 = $model->where(['parent'=>$row->id])->field($fields)->select();
            foreach ($rows2 as $row2){
                $list[] = ['id'=>$row2->id,'name'=>$row2->name,'parent'=>$row2->parent,'sequence'=>$row2->sequence,'level'=>1,'push'=>$row2->push,'icon'=>MallType::getFormatIcon($row2->path),'web_icon'=>MallType::getFormatIcon($row2->web_path)];
                $rows3 = $model->where(['parent'=>$row2->id])->field($fields)->select();
                foreach ($rows3 as $row3){
                    $list[] = ['id'=>$row3->id,'name'=>$row3->name,'parent'=>$row3->parent,'sequence'=>$row3->sequence,'level'=>2,'push'=>$row3->push,'icon'=>MallType::getFormatIcon($row3->path),'web_icon'=>MallType::getFormatIcon($row3->web_path)];
                }
            }
        }

        $this->assign('k',$k);
        $this->assign('list',$list);
//        $this->assign('page',$rows->render());
        return $this->fetch();
    }

    /**
     * @desc 添加分类
     * @param Request $request
     * @return mixed
     */
    public function create(Request$request,$id = 0){
        $model = new MallType();
        if($request->isPost()){
            $name = $request->post('name','');
            $parent = $request->post('parent',0);
            $sequence = $request->post('sequence',0);
            $path = $request->post('path','');
            $webPath = $request->post('web_path','');
            $data = [
              'name' => $name,
              'parent' => $parent,
              'sequence' => $sequence,
              'path' => $path,
              'web_path' => $webPath
            ];
            $result = $model->save($data);
            if($result){
                $this->redirect(url('admin/type/index'));
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
        $model = new MallType();
        $field = ['id','name','parent','sequence','path','web_path'];
        $row = $model->where(['id'=>$id])->field($field)->find();
        if($request->isPost()){
            $name = $request->post('name','');
            $parent = $request->post('parent',0);
            $sequence = $request->post('sequence',0);
            $path = $request->post('path','');
            $webPath = $request->post('web_path','');
            $data = [
                'name' => $name,
                'parent' => $parent,
                'sequence' => $sequence,
                'path' => $path,
                'web_path' => $webPath
            ];
            $result = $model->save($data,['id'=>$id]);
            if($result !== false){
                $this->redirect(url('admin/type/index'));
            }
        }
        $this->assign('row',$row);
        $this->assign('preview_path',MallType::getFormatIcon($row->path));
        $this->assign('preview_web_path',MallType::getFormatIcon($row->web_path));
        return $this->fetch();
    }

    /**
     * @desc 删除分类
     * @param Request $request
     * @param $id
     */
    public function delete(Request$request,$id){
        $model = new MallType();
        $row = $model->where(['id'=>$id])->find();
        if(!$row){
            return ['status'=>1,'data'=>[],'msg'=>'数据错误'];
        }

        //判断是否有子分类
        $child = $model->where(['parent'=>$row->id])->find();
        if($child){
            return ['status'=>1,'data'=>[],'msg'=>'请先删除子类'];
        }

        //判断是否有当前产品在使用
        $goodsModel = new MallGoods();
        $goods = $goodsModel->where(['type'=>$id])->find();
        if($goods){
            return ['status'=>1,'data'=>[],'msg'=>'请先删除该分类的产品'];
        }

        $result = $model->where(['id'=>$id])->delete();
        if($result == true){
            return ['status'=>0,'data'=>[],'msg'=>'删除成功'];
        }

        return ['status'=>1,'data'=>[],'msg'=>'删除失败'];
    }

    /**
     * @desc 更新sequence
     * @param Request $request
     * @return array
     */
    public function sequence(Request $request){
        $id = $request->post('id',0);
        $sequence = $request->post('value',0);
        $model = new MallType();
        $result = $model->save(['sequence'=>$sequence],['id'=>$id]);
        if($result == true){
            return ['status'=>0,'data'=>[],'msg'=>'更新成功'];
        }

        return ['status'=>1,'data'=>[],'msg'=>'更新失败'];
    }

    //设为推荐
    public function pushUp(){
        $id = input('post.id',0,'intval');

        $mallType = model('mall_type');
        //查询是否存在
        $data = $mallType->field('push')->where(['id'=>$id])->find();

        if(!$data){
            return $this->errorMsg('100400');//不存在你所需改的分类
        }

        if($data['push']>0){
            return $this->errorMsg('100401');//当前已经是推荐状态
        }

        if($mallType->where(['id'=>$id])->update(['push'=>1])){
            return $this->successMsg('reload',['msg'=>'推荐成功']);
        }else{
            return $this->errorMsg('100402');
        }
    }

    //取消推荐
    public function pushDown(){
        $id = input('post.id',0,'intval');

        $mallType = model('mall_type');
        //查询是否存在
        $data = $mallType->field('push')->where(['id'=>$id])->find();

        if(!$data){
            return $this->errorMsg('100500');//不存在你所需改的分类
        }

        if($data['push']==0){
            return $this->errorMsg('100501');//当前不是推荐状态无需取消
        }

        if($mallType->where(['id'=>$id])->update(['push'=>0])){
            return $this->successMsg('reload',['msg'=>'取消推荐成功']);
        }else{
            return $this->errorMsg('100502');
        }
    }

}