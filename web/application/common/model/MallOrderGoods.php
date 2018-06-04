<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/6/2
 * Time: 18:29
 */
namespace app\common\model;

use think\Model;

class MallOrderGoods extends Model{


    /**
     * @desc 返回格式化后的icon
     * @param $icon
     * @return string
     */
    public static function getFormatIcon($icon){
        return config('jzdc_domain').'/web/public/uploads/order_icon/'.$icon;
    }

}