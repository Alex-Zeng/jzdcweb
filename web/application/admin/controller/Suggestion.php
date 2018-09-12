<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/6/1
 * Time: 11:25
 */
namespace app\admin\controller;

use app\common\model\ComplaintsSuggestions;
use app\common\model\IndexUser;
use think\Request;

class Suggestion extends Base{


    /**
     * @desc 投诉建议列表
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function index(Request $request){
        $model = new ComplaintsSuggestions();
        $start = $request->get('start');
        $end = $request->get('end');
        $contacts = $request->get('contacts','','trim');
        $content = $request->get('content','','trim');
        $contact_num = $request->get('contact_num','','trim');

        $where = [];
        if(isset($start) && $start && isset($end) && $end){
            $where['created_time'] = ['between',[strtotime($start),strtotime($end.' 23:59:59')]];
        }elseif (isset($start) && $start){
            $where['created_time'] = ['gt',strtotime($start)];
        }elseif (isset($end) && $end){
            $where['created_time'] = ['lt',strtotime($end.' 23:59:59')];
        }

        if($contacts){
            $where['contacts'] = ['like','%'.$contacts.'%'];
        }
        if($contact_num){
            $where['contact_num'] = ['like','%'.$contact_num.'%'];
        }
        if($content){
            $where['content'] = ['like','%'.$content.'%'];
        }

        $rows = $model->where($where)->order(['id'=>'desc'])->paginate(20,false,['query'=>request()->param()]);

        $this->assign('start',$start);
        $this->assign('end',$end);
        $this->assign('contacts',$contacts);
        $this->assign('content',$content);
        $this->assign('contact_num',$contact_num);


        $this->assign('list',$rows);
        $this->assign('page',$rows->render());
        return $this->fetch();
    }

}