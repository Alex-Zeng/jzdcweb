<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/10/17
 * Time: 14:41
 */

namespace app\common\model;


class EntCompanyAudit extends Base
{
    protected $table='ent_company_audit';

    /**
     * @desc 格式化
     * @param $path
     * @return string
     */
    public static function getFormatImg($path){
        return config('jzdc_domain').'/web/public/uploads/company_cert/'.$path;
    }
}