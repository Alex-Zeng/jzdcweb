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
        $type = $request->post('type','');
        if($file){
            $config = config('jzdc_upload.'.$type);
            $path = $config['path'];
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
        return ['status'=>1,'data'=>[],'msg'=>'上传错误'];
    }


}