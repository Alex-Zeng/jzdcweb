<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/5/21
 * Time: 17:17
 */
namespace app\api\controller;

use think\Request;

class ImageUpload{

    /**
     * @desc 上传图片
     * @param Request $request
     * @return array
     */
    public function index(Request $request){
        $file = $request->file('image');
        if($file){
           // $path = ROOT_PATH.'public'.DS.'uploads';
            $config = config('jzdc_upload.company');
            $path = $config['path'];
            $info = $file->move($path);
            if($info){
                return ['status'=>0,'data'=>['filename'=>$info->getSaveName()],'msg'=>''];
            }
            return ['status'=>1,'data'=>[],'msg'=>$file->getError()];
        }
        return ['status'=>1,'data'=>[],'msg'=>'上传错误'];
    }


}