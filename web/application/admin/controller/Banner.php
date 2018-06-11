<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/5/23
 * Time: 11:54
 */

namespace app\admin\controller;

use app\common\model\SliderImg;
use think\Request;

class Banner extends Base {

    /**
     * @desc 列表页
     * @return mixed
     */
    public function index(Request $request){
        $model = new SliderImg();
        $k = Request::instance()->get('k','');
        $model->where(['group_id'=>27]);
        if(isset($k) && $k){
            $model->where('name|url','like','%'.$k.'%');
        }
        $rows = $model->field(['id','name','url','path','target','sequence','status','type'])->paginate(20,false,['query'=>request()->param()]);
        foreach ($rows as &$row){
            $row['path'] = SliderImg::getFormatImg($row['path']);
        }

        $this->assign('list',$rows);
        $this->assign('page',$rows->render());
        $this->assign('k',$k);
        return $this->fetch();
    }

    /**
     * @desc 添加banner
     * @param Request $request
     * @return array
     */
    public function create(Request $request){
        $title = $request->post('title');
        $link = $request->post('link');
        $type = $request->post('type');
        $target = $request->post('target');
        $path = $request->post('path');
        $sequence = $request->post('sequence',0);
        $status = $request->post('status',1);
        //验证数据

        //保存数据
        $model = new SliderImg();
        $data = ['group_id'=>27,'name'=>$title,'url'=>$link,'type'=>$type,'target'=>$target,'sequence'=>$sequence,'status'=>$status,'path'=>$path];
        $result = $model->save($data);
        if($result !== false){
            return ['status'=>0,'msg'=>'添加成功'];
        }
        return ['status'=>1,'添加失败'];
    }

    /**
     * @desc 修改
     * @param Request $request
     * @return array
     */
    public function edit(Request $request,$id){
        $title = $request->post('title');
        $link = $request->post('link');
        $type = $request->post('type',1);
        $target = $request->post('target','_blank');
        $path = $request->post('path','');
        $sequence = $request->post('sequence',0);
        $status = $request->post('status',1);
        //验证数据

        //保存数据
        $model = new SliderImg();
        $data = ['name'=>$title,'url'=>$link,'type'=>$type,'target'=>$target,'sequence'=>$sequence,'status'=>$status,'path'=>$path];
        $result = $model->save($data,['id'=>$id]);
        if($result !== false){
            return ['status'=>0,'msg'=>'修改成功'];
        }
        return ['status'=>1,'msg'=>'修改失败'];
    }

    /**
     * @desc 删除banner
     * @param Request $request
     * @return array
     */
    public function delete(Request $request,$id){
        $model = new SliderImg();
        $result = $model->where(['id'=>$id])->delete();
        if($result == true){
            return ['status'=>0,'msg'=>'删除成功'];
        }

        return ['status'=>1,'msg'=>'删除失败'];
    }


    /**
     * @desc 获取banner
     * @param $id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get($id){
        $model = new SliderImg();
        $row = $model->where(['id'=>$id])->field(['name','url','target','sequence','status','path','type'])->find();
        if($row){
            $data = [
                'title'=>$row->name,
                'link'=>$row->url,
                'status'=>$row->status,
                'target'=>$row->target,
                'sequence'=>$row->sequence,
                'status'=>$row->status,
                'path' => $row->path,
                'preview'=>SliderImg::getFormatImg($row->path) ,
                'type'=>$row->type
            ];
            return ['status'=>0,'data'=>$data,'msg'=>''];
        }
        return ['status'=>1,'data'=>[],'msg'=>'数据异常'];

    }

}