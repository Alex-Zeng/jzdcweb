<?php
namespace app\api\controller;

use think\Request;

class Index
{
    public function index(Request $request)
    {
       $id = $request->get('id');
        session_start();
        $_SESSION['ttttttttt'] = 1;
//        $t = config('jzdc_upload.company');
//        print_r($t);
//        exit;
//        print_r($session);
        print_r($_SESSION);

        exit;
        print_r($_SESSION);


        exit;
//       echo session('jzdc_id');
        //先从cookie里面获取
        ini_set('session.cookie_path', '/');
        ini_set('session.cookie_domain', '127.0.0.1');
        ini_set('session.cookie_lifetime', '1800');

       cookie('think','think you');

        session_start();
        echo session('think');
       echo  session('jzdc_id');
//        $data = ['name'=>'thinkphp','url'=>'thinkphp.cn'];
//        return ['status'=>0,'data'=>['hello'=>'world','id'=>$id]];
    }
}
