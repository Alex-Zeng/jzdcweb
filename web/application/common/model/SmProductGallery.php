<?php
namespace app\common\model;

use think\Model;

class SmProductGallery extends Model{

	// 设置当前模型对应的完整数据表名称-分类规格表
    protected $table = 'sm_product_gallery';


    /**
     * @desc 返回详细图
     * @param $img
     * @return string
     */
    public static function getFormatImg($img){
        return config('jzdc_domain').config('jzdc_static_path').'/uploads/goods/'.$img;
    }
}  