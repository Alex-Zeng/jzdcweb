<?php
namespace app\common\model;

use think\Model;

class SmProductSpec extends Model{

	// 设置当前模型对应的完整数据表名称-分类规格表
    protected $table = 'sm_product_spec';

    /**
     * @desc 返回格式化产品图片路径
     * @param $img
     * @return string
     */
    public static function getFormatImg($img){
        return $img ? config('jzdc_doc_path').'/goods_thumb/'.$img : '';
    }
	
}  