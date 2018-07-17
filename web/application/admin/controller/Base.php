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
        /*
        建立在外包系统兼容性写法
        if($userId == 0){
            $this->redirect('/index.php?jzdc=index.login');
            exit;
        }
         */
        
        if($userId<=0 ){
            if(!in_array(request()->controller().'/'.request()->action(),['User/changePsw','User/login','User/logout'])){
                $this->redirect('User/login');
            }
        }else{
            //获取用户所在角色
            $groupId = getGroupId();
            if($groupId !=2 && $groupId !=3){
                //没有权限访问
                /*
                建立在外包系统兼容性写法
                $this->redirect('/index.php?jzdc=index.login');
                 */
                $this->redirect('User/login');
                exit;
            }

           $this->assign('groupId',$groupId);
        }
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


    /**
     * [createSession description]
     * @param  array  $arr [所需要的登录后存储session的数据]
     * @return viod     
     */
    protected function createSession($arr=[]){
        if(empty($arr)){
            $arr = [
                'admin_id'=>session('admin_id'),
                'nick_name'=>session('nick_name'),
                'group_id'=>session('group_id'),
            ];
        }
        session('admin_id', $arr['admin_id']);
        session('nick_name', $arr['nick_name']);
        session('group_id', $arr['group_id']);
    }

    /**
     * [deleteSession 退出后台登录]
     * @return viod 
     */
    protected function deleteSession(){
        session('admin_id', null);
        session('nick_name', null);
        session('group_id', null);
        session('last_time', null);
    }

    /**
     * [errorMsg 错误输出]
     * @param  int $code [错误码]
     * @param  array  $data [description]
     * @return mix       [json|页面输出]
     */
    protected function errorMsg($code,$data = []){
        //错误码替换成文本
        $error = config('admin_error_code');
        $msg = isset($error[$code]) ? $error[$code] : '';

        //对文本提示内容进行替换
        if(isset($data['replace'])){$msg = strtr($msg,$data['replace']);}
        unset($data['replace']);

        //对错误信息进行输出
        $data['msg'] = $msg;
        if(request()->isAjax()){
            return json(allLateToString(['code'=>$code,'data'=>$data]));
        }else{
            $this->error($msg);
        }
        exit;
    }

    /**
     * [successMsg 成功输出]
     * @param  string $url  [控制方法组成的字符串如'admin/login']
     * @param  array  $data [需要返回页面的数据]
     * @return [type]       [json|页面输出]
     */
    protected function successMsg($url='',$data = []){
        if(empty($url)){
            $url = request()->controller().'/'.request()->action();;
        }

        //特殊标志，不作为url跳转
        //reload刷新
        //noSkip不做任何操作
        //back返回上一页
        $urlArr = ['reload','noSkip','back'];
        if(is_string($url) && !in_array($url,$urlArr)){
            $url = url($url);
        }else if(is_array($url) && !in_array($url[0],$urlArr)){
            if(isset($url[1]) && is_array($url[1])){
                $url = url($url[0],$url[1]);
            }else{
                $url = url($url[0]);
            }
        }
        $data['url'] = $url;
        if(!isset($data['msg'])){
            // $data['msg'] = isset($this->purList[$this->pur_id])?$this->purList[$this->pur_id].'成功':'操作成功';
            $data['msg'] ='操作成功';
        }
        if(request()->isAjax()){
            return json(allLateToString(['code'=>"0",'data'=>$data]),JSON_UNESCAPED_UNICODE);
        }else{
            $this->success($data['msg'],$url);
        }
        exit;
    }

}