<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/5/18
 * Time: 16:29
 */
namespace app\common\model;

use think\Model;

class MallGoods extends Model{


    /**
     * @desc 返回格式化产品图片路径
     * @param $img
     * @return string
     */
    public static function getFormatImg($img){
        return config('jzdc_domain').'/program/mall/img_thumb/'.$img;
    }

}