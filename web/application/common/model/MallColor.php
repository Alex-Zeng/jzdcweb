<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/6/15
 * Time: 14:34
 */
namespace app\common\model;

use think\Model;

class MallColor extends Model{

    /**
     * @param $id
     * @return string
     */
    public static function getFormatImg($id){
        return config('jzdc_domain').'/web/public/static/img/color_icon/'.$id.'.png';
    }

}