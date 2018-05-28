<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/5/24
 * Time: 10:22
 */
namespace app\admin\controller;


use app\admin\model\IndexUser;
use think\Request;

class Notice extends Base{

    /**
     * @desc 公告列表页面
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function index(){
        $model = new \app\common\model\Notice();
        $url = isset($_REQUEST['s']) ? $_REQUEST['s'] : '';
        $k = Request::instance()->get('k','');
        if(isset($k) && $k){
            $model->where('title|summary','like','%'.$k.'%');
        }
        $rows = $model->order(['id'=>'desc'])->paginate(20,false,['query'=>request()->param()]);

        foreach($rows as &$row){
            $user = IndexUser::get(['id'=>$row->create_by]);
            $row['username'] =  $user->username;
        }

        $this->assign('k',$k);
        $this->assign('list',$rows);
        $this->assign('page',$rows->render());
        return $this->fetch();
    }


    /**
     * @desc 发布公告
     * @param Request $request
     * @return array
     */
    public function create(Request $request){
        $title = $request->post('title');
        $summary = $request->post('summary');
        $content = $request->post('content');
        $status = $request->post('status',0);

        //验证数据

        //添加
        $model = new \app\common\model\Notice();
        $data = ['title'=>$title,'summary'=>$summary,'content'=>$content,'create_time'=>time(),'release_time'=>time(),'create_by'=>getUserId(),'status'=>$status];
        $result = $model->save($data);
        if($result == true){
            return ['status'=>0,'msg'=>'添加成功'];
        }
        return ['status'=>1,'msg'=>'添加失败'];
    }

    /**
     * @desc 修改公告
     * @param Request $request
     * @param $id
     * @return array
     */
    public function edit(Request $request,$id){
        $title = $request->post('title');
        $summary = $request->post('summary');
        $content = $request->post('content');
        $status = $request->post('status',0);

        //验证数据
        //添加
        $model = new \app\common\model\Notice();
        $data = ['title'=>$title,'summary'=>$summary,'content'=>$content,'release_time'=>time(),'edit_by'=>getUserId(),'status'=>$status];
        $result = $model->save($data,['id'=>$id]);
        if($result == true){
            return ['status'=>0,'msg'=>'修改成功'];
        }
        return ['status'=>1,'msg'=>'修改失败'];
    }

    /**
     * @desc 获取数据
     * @param $id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get($id){
        $model = new \app\common\model\Notice();
        $row = $model->where(['id'=>$id])->field(['title','summary','status','content'])->find();
        if($row){
            return ['status'=>0,'data'=>['title'=>$row->title,'summary'=>$row->summary,'status'=>$row->status,'content'=>$row->content]];
        }
        return ['status'=>1,'data'=>[],'msg'=>'数据异常'];
    }


}