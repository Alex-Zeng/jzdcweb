<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/6/14
 * Time: 10:22
 */
namespace app\common\model;

use think\Model;

class FormUserCert extends Model{


    /**
     * @desc 格式化
     * @param $path
     * @return string
     */
    public static function getFormatImg($path){
        return config('jzdc_domain').'/web/public/uploads/company_cert/'.$path;
    }

}