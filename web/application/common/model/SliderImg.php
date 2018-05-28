<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/5/18
 * Time: 13:34
 */
namespace app\common\model;

use think\Model;

class SliderImg extends Model{


    /**
     * @desc 格式化输出banner图片路径
     * @param $img
     * @return string
     */
    public static function getFormatImg($img){
        return config('jzdc_domain').'/program/slider/img/'.$img.'.jpg';
    }

}
