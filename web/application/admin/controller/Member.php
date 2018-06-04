<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/6/1
 * Time: 22:35
 */
namespace app\admin\controller;

use app\common\model\IndexUser;
use think\Request;

class Member extends Base{

    /**
     * @desc 会员列表
     * @return mixed
     */
    public function index(){
        $model = new IndexUser();
        $k = Request::instance()->get('k','');
        if(isset($k) && $k){
            $model->where('username|phone','like','%'.$k.'%');
        }
        $rows = $model->where([])->order(['id'=>'desc'])->paginate();

        $this->assign('k',$k);
        $this->assign('list',$rows);
        $this->assign('page',$rows->render());
        return $this->fetch();
    }
}