<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/5/31
 * Time: 11:43
 */
namespace app\admin\controller;

use think\Request;
use think\Session;

class User extends Base{

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
    }


    /**
     * @desc 退出登录
     */
    public function logout(){
       //清除session
        $_SESSION['jzdc'] = null;
        $this->redirect('/index.php?jzdc=index.login');
    }


}