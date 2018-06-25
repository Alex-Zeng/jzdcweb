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


    public function upload2(Request $request){
        $php_path = dirname(__FILE__) . '/';
        $php_url = dirname($_SERVER['PHP_SELF']) . '/';

        //文件保存目录路径
        $save_path = ROOT_PATH . 'web/public/uploads/attached/';
//文件保存目录URL
        $save_url = config('jzdc_domain') . '/web/public/uploads/attached/';

        $file = $request->file('imgFile');
        if($file){
            $info = $file->move($save_path);
            if($info){

            }

        }

/*
//定义允许上传的文件扩展名
        $ext_arr = array(
            'image' => array('gif', 'jpg', 'jpeg', 'png', 'bmp'),
            'flash' => array('swf', 'flv'),
            'media' => array('swf', 'flv', 'mp3', 'wav', 'wma', 'wmv', 'mid', 'avi', 'mpg', 'asf', 'rm', 'rmvb'),
            'file' => array('doc', 'docx', 'xls', 'xlsx', 'ppt', 'htm', 'html', 'txt', 'zip', 'rar', 'gz', 'bz2'),
        );
//最大文件大小
        $max_size = 1000000;

        $save_path = realpath($save_path) . '/';

        print_r($_FILES); EXIT;
//PHP上传失败
        if (!empty($_FILES['imgFile']['error'])) {
            switch($_FILES['imgFile']['error']){
                case '1':
                    $error = '超过php.ini允许的大小。';
                    break;
                case '2':
                    $error = '超过表单允许的大小。';
                    break;
                case '3':
                    $error = '图片只有部分被上传。';
                    break;
                case '4':
                    $error = '请选择图片。';
                    break;
                case '6':
                    $error = '找不到临时目录。';
                    break;
                case '7':
                    $error = '写文件到硬盘出错。';
                    break;
                case '8':
                    $error = 'File upload stopped by extension。';
                    break;
                case '999':
                default:
                    $error = '未知错误。';
            }
            alert($error);
        }

//有上传文件时
        if (empty($_FILES) === false) {
            //原文件名
            $file_name = $_FILES['imgFile']['name'];
            //服务器上临时文件名
            $tmp_name = $_FILES['imgFile']['tmp_name'];
            //文件大小
            $file_size = $_FILES['imgFile']['size'];
            //检查文件名
            if (!$file_name) {
                alert("请选择文件。");
            }
            //检查目录
            if (@is_dir($save_path) === false) {
                alert("上传目录不存在。");
            }
            //检查目录写权限
            if (@is_writable($save_path) === false) {
                alert("上传目录没有写权限。");
            }
            //检查是否已上传
            if (@is_uploaded_file($tmp_name) === false) {
                alert("上传失败。");
            }
            //检查文件大小
            if ($file_size > $max_size) {
                alert("上传文件大小超过限制。");
            }
            //检查目录名
            $dir_name = empty($_GET['dir']) ? 'image' : trim($_GET['dir']);
            if (empty($ext_arr[$dir_name])) {
                alert("目录名不正确。");
            }
            //获得文件扩展名
            $temp_arr = explode(".", $file_name);
            $file_ext = array_pop($temp_arr);
            $file_ext = trim($file_ext);
            $file_ext = strtolower($file_ext);
            //检查扩展名
            if (in_array($file_ext, $ext_arr[$dir_name]) === false) {
                alert("上传文件扩展名是不允许的扩展名。\n只允许" . implode(",", $ext_arr[$dir_name]) . "格式。");
            }
            //创建文件夹
            if ($dir_name !== '') {
                $save_path .= $dir_name . "/";
                $save_url .= $dir_name . "/";
                if (!file_exists($save_path)) {
                    mkdir($save_path);
                }
            }
            $ymd = date("Ymd");
            $save_path .= $ymd . "/";
            $save_url .= $ymd . "/";
            if (!file_exists($save_path)) {
                mkdir($save_path);
            }
            //新文件名
            $new_file_name = date("YmdHis") . '_' . rand(10000, 99999) . '.' . $file_ext;
            //移动文件
            $file_path = $save_path . $new_file_name;
            if (move_uploaded_file($tmp_name, $file_path) === false) {
                alert("上传文件失败。");
            }
            @chmod($file_path, 0644);
            $file_url = $save_url . $new_file_name;

            header('Content-type: text/html; charset=UTF-8');

//            $json = new Services_JSON();
//            echo $json->encode(array('error' => 0, 'url' => $file_url));
            echo json_encode(['error'=>0,'url'=>$file_url]);
            exit;
        }

        */

    }

    function alert($msg) {
        header('Content-type: text/html; charset=UTF-8');
        echo json_encode(['error'=>1,'message'=>$msg]);
        exit;
    }
}
