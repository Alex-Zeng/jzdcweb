<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/5/18
 * Time: 14:07
 */
namespace app\common\model;
use think\Model;

class MenuMenu extends Model{


    /**
     * @desc 根据ID返回首页菜单图标路径
     * @param $id
     * @return string
     */
   public static function  getFormatImg($path){
        return config('jzdc_domain').'/web/public/uploads/type_index_icon/'.$path;
    }


}