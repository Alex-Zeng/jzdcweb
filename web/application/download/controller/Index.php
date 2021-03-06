<?php
namespace app\download\controller;

class Index 
{
    //苹果下载
    public function iphoneNormal(){
        // 苹果已经上线则去苹果应用市场下载
        header('Location: https://itunes.apple.com/cn/app/%E9%9B%86%E4%BC%97%E7%94%B5%E9%87%87/id1434814844');
        exit();
        
        // 没有上线用第三方公司则去指引页下载
        // $url = 'itms-services://?action=download-manifest&url=https://www.pgyer.com/app/plist/8f3fad7265f5359172788719080d3dbb/install/s.plist';
        // return view('',['url'=>$url]);
    }

    //安卓下载
    public function androidNormal(){
        $version = db('version')->field('app_name')->where(['up_time'=>['elt',time()],'is_del'=>1])->order('version_id desc')->find();
        
        //验证后台是否已经发布了版本
        if($version){
            header('Location: '.request()->domain().'/version/'.$version['app_name']);
        }else{
            header("Content-type:text/html;charset=utf-8");
            echo '<p style="font-size:20px;text-align:center;margin-top:300px;">敬请期待Android版本</p>';
            exit;
        }
    }

    //安卓应用宝下载
    public function androidWechat(){
        header('Location: http://android.myapp.com/myapp/detail.htm?apkName=com.jzdc.jzdc&ADTAG=mobile');
        exit();
    }

    public function index(){
        //获取扫码应用来源
        $getDevice = getDevice();

        //如果是微信设备扫码，指引用浏览器打开
        $pop = false;
        if(stripos($getDevice,'Wechat')){
            $pop = true;
        }

        switch ($getDevice) {
            case 'iphoneNormal':
                $url = url('Index/iphoneNormal');
                break;
            case 'androidNormal':

                $url = url('Index/androidNormal');
                break;
            case 'androidWechat':
                $this->androidWechat();return;
                // $url = url('Index/androidWechat');
                break;
            default:
                $url = 'javascript:;';
                break;
        }

        return view('',['pop'=>$pop,'url'=>$url]);
    }
}