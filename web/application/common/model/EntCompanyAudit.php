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

    const STATE_PENDING = 1;  //待审核
    const STATE_REFUSED = 2; //拒绝
    const STATE_PASS = 3;  //通过

    /**
     * @desc 格式化
     * @param $path
     * @return string
     */
    public static function getFormatImg($path){
        return config('jzdc_domain').'/web/public/uploads/company_cert/'.$path;
    }
}