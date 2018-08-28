<?php
namespace app\common\model;

use think\Model;

class SmProductCategory extends Model{

	// 设置当前模型对应的完整数据表名称
    protected $table = 'sm_product_category';
    
	/**
	 * [getCategoryIds 获取分类列表]
	 * @param  integer $parentId [父级ID]
	 * @param  boolean $isIdList [是否仅获取ID集合]
	 * @return [type]            [分类ID及名称集合、分类ID集合]
	 */
	public function getCategoryIds($parentId = 0,$isIdList=false){
        $where = [
           'parent_id' => $parentId,
           'is_display' => 1,
           'is_deleted'=>0
        ];
        $rows = $this->where($where)->order('ordering','desc')->field(['id','name'])->select();
        
        if($isIdList){
        	foreach ($rows as $key => $val) {
	        	$result[] = $val['id'];
	        }
        }else{
	        $result = $rows;
        }
        return $result;
    }
}  