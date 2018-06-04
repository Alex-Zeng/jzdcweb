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
/*
0 => '未上架',
1 => '提前预售',
2 => '正常出售',
3 => '缺货预售',
*/
    const STATE_PENDING = 0; //未上架
    const STATE_PRE_SALE = 1; //预售
    const STATE_SALE = 2; //正常出售
    const STATE_SHORT_SALE = 3; //缺货预售

    /**
     * @desc 返回格式化产品图片路径
     * @param $img
     * @return string
     */
    public static function getFormatImg($img){
        return config('jzdc_domain').'/program/mall/img_thumb/'.$img;
    }

    /**
     * @desc 返回详细图
     * @param $img
     * @return string
     */
    public static function getFormatMultiImg($img){
        return config('jzdc_domain').'/web/public/uploads/goods'.$img;
    }

}