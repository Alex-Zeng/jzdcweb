<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/6/3
 * Time: 10:36
 */
namespace app\common\model;

use think\Model;

class MallOrderPay extends Model{


    /**
     * @desc 格式化图片
     * @param $picture
     * @return string
     */
    public static function getFormatPicture($picture){
        return config('jzdc_domain').'/web/public/uploads/order/'.$picture;
    }

}