<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/5/28
 * Time: 9:13
 */
namespace app\admin\controller;

use think\Request;

class File extends Base{

    /**
     * @desc 文件上传
     * @param Request $request
     * @return array
     */
    public function upload(Request $request){
        $file = $request->file('image');
        $type = $request->get('type','');
        if($file){
            $config = config('jzdc_upload.'.$type);
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
