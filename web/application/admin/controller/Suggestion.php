<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/6/1
 * Time: 11:25
 */
namespace app\admin\controller;

use app\common\model\FormSuggestion;
use app\common\model\IndexUser;
use think\Request;

class Suggestion extends Base{


    /**
     * @desc 投诉建议列表
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function index(Request $request){
        $model = new FormSuggestion();
        $k = Request::instance()->get('k','');
        if(isset($k) && $k){
            $model->where('comment','like','%'.$k.'%');
        }
        $rows = $model->order(['id'=>'desc'])->paginate(20,false,['query'=>request()->param()]);
        $userModel = new IndexUser();
        foreach ($rows as &$row){
            $user = $userModel->getInfoById($row['writer']);
            $row['writer_name'] = $user ? $user->username : '';
        }

        $this->assign('k',$k);
        $this->assign('list',$rows);
        $this->assign('page',$rows->render());
        return $this->fetch();
    }

}