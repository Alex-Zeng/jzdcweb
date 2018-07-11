<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/6/1
 * Time: 13:38
 */
namespace app\admin\controller;

use app\common\model\FormFinService;
use think\Request;
use app\common\model\IndexUser;

class Service extends Base {

    /**
     * @desc 金融服务
     * @return mixed
     */
    public function index(){
        $model = new FormFinService();
        $k = Request::instance()->get('k','');
        $type = Request::instance()->get('type',-1,'intval');
        if(isset($k) && $k){
            $model->where('comment|phone','like','%'.$k.'%');
        }
        if($type > -1){
            $model->where(['type'=>$type]);
        }
        $rows = $model->order(['id'=>'desc'])->paginate(20,false,['query'=>request()->param()]);
        $userModel = new IndexUser();
        $typeList = FormFinService::getTypeList();
        foreach ($rows as &$row){
            $user = $userModel->getInfoById($row['writer']);
            $row['writer_name'] = $user ? $user->username : '';
            $row['type_name'] = $typeList[$row->type];
        }

        $this->assign('k',$k);
        $this->assign('list',$rows);
        $this->assign('page',$rows->render());
        $this->assign('typeList',$typeList);
        $this->assign('type',$type);
        return $this->fetch();
    }

}