<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/5/28
 * Time: 9:13
 */
namespace app\admin\controller;

use think\Request;
use think\Image;

class File extends Base{

    /**
     * @desc 文件上传
     * @param Request $request
     * @return array
     */
    public function upload(Request $request,$dir = ''){
        $file = $request->file('file');
        $type = $request->post('type','');
        $type = $dir ? $dir : $type;
        if($file){
            $config = config('jzdc_upload.'.$type);
            //验证大小以及文件格式
            $validate = $file->validate(['size'=>$config['size'],'ext'=>$config['ext']]);
            if($validate){
                $path = $config['path'];
                //创建文件权限
                if(!is_dir($path)){ mkdir($path,0777);}
                $info = $file->move($path);
                if($info){
                    $fileName = $info->getSaveName();
                    //是否需要生成缩略图
                    if(isset($config['thumb'])){
                        //分割数组
                        $fileArr = explode(DS,$fileName);
                        $fileArr[1] = 'thumb-'.$fileArr[1];
                        $thumbPath = $path.DS.implode(DS,$fileArr);  //
                        $width = isset($config['thumb']['width']) ? $config['thumb']['width'] : 100;
                        $height = isset($config['thumb']['height']) ? $config['thumb']['height'] : 100;
                        //是否生成缩略图
                        $image = Image::open($path.DS.$fileName);
                        $image->thumb($width, $height,Image::THUMB_CENTER)->save($thumbPath);
                    }

                    return ['status'=>0,'data'=>['filename'=>$fileName],'msg'=>''];
                }
                return ['status'=>1,'data'=>[],'msg'=>$file->getError()];
            }
             return ['status'=>1,'data'=>[],'msg'=>'图片上传格式不正确或已超过限制大小'];
        }
        return ['status'=>1,'data'=>[],'msg'=>'上传错误'];
    }

}
