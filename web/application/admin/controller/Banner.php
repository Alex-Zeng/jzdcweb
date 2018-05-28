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
        $rows = $model->where(['group_id'=>27])->field(['id','name','url','target','sequence','status'])->paginate(20,false,['query'=>request()->param()]);
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
        $title = $request->get('title');
        $link = $request->get('link');
        $group = $request->get('group');
        $type = $request->get('type');
        $img = $request->get('path');

        //验证数据

        //保存数据
        $model = new SliderImg();
        $data = [];
        $result = $model->save($data);
        if($result == true){
            return ['status'=>0,'msg'=>'添加成功'];
        }
        return ['status'=>1,'添加失败'];
    }

    /**
     * @desc 删除banner
     * @param Request $request
     * @return array
     */
    public function delete(Request $request){
        $id = $request->post('id',0);
        $model = new SliderImg();

        $result = $model->delete(['id'=>$id]);
        if($result == true){
            return ['status'=>0,'msg'=>'删除成功'];
        }

        return ['status'=>1,'msg'=>'删除失败'];
    }

}