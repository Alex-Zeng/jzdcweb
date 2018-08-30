<?php
namespace app\common\model;

use think\Model;

class SmProduct extends Model{

	// 设置当前模型对应的完整数据表名称-分类规格表
    protected $table = 'sm_product';

    const STATE_FORSALE = 1;   //上架
    const STATE_SOLDOUT = 2;  //下架

    const AUDIT_PENDING = 2;  //未审核
    const AUDIT_RELEASED = 5; //已发布

    /**
     * @desc 返回格式化产品图片路径
     * @param $img
     * @return string
     */
    public static function getFormatImg($img){
        return $img ? config('jzdc_domain').config('jzdc_static_path').'/uploads/goods_thumb/'.$img : '';
    }


}  