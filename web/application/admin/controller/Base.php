<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/5/24
 * Time: 9:05
 */
namespace app\admin\controller;

use think\Controller;
use think\Request;
use think\View;

class Base extends Controller{

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        //登录验证操作

        $userId = getUserId();
        if($userId == 0){
            $this->redirect('/index.php?jzdc=index.login');
            exit;
        }

       //获取用户所在角色
        $groupId = getGroupId();
        if($groupId !=2 && $groupId !=3){
            //没有权限访问
            $this->redirect('/index.php?jzdc=index.login');
            exit;
        }

       $this->assign('groupId',$groupId);
    }


    /**
     * @desc 错误提示
     * @param $data
     * @return mixed
     */
    public function errorTips($data = []){
        if(Request::instance()->isAjax()){

        }
        $view = new View();
        echo $view->fetch('tips/error',$data);
        exit;
    }


    /**
     * @desc 成功提示
     * @return mixed
     */
    public function successTips(){
        if(Request::instance()->isAjax()){

        }
        return $this->fetch('tips/error',$data);
    }

}