<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/5/31
 * Time: 18:07
 */
namespace app\common\model;

use think\Model;

/**
 * @desc 金融服务
 * Class FormFinService
 * @package app\common\model
 */
class FormFinService extends Model{

    const TYPE_UNDEFINE = 0;
    const TYPE_INSURANCE = 1;
    const TYPE_LAW = 2;
    const  TYPE_FINANCE = 3;
    const  TYPE_SERVICE = 4;
    const  TYPE_PROPERTY = 5;
    const  TYPE_AUTOMATE = 6;
    const  TYPE_AUTHENTICATION = 7;


    public static function getTypeList(){
        return [
            self::TYPE_UNDEFINE => '未知',
            self::TYPE_INSURANCE => '保险',
            self::TYPE_LAW => '法律',
            self::TYPE_FINANCE => '金融',
            self::TYPE_SERVICE => '售后',
            self::TYPE_PROPERTY => '知识产权',
            self::TYPE_AUTOMATE => '自动化',
            self::TYPE_AUTHENTICATION => '企业认证'
        ];
    }


}