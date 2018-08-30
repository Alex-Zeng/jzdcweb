<?php
namespace app\common\model;

use think\Model;

class SmProductCategory extends Model{

	// 设置当前模型对应的完整数据表名称
    protected $table = 'sm_product_category';

    protected $prefix = false;
    
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


    /**
     * @desc 返回子类的所有ID
     * @return [array]
     */
    public function getChildIds($parentId,$parentIdIn=false){
        $array = $this->where(['is_display'=>1,'is_deleted'=>0])->field('id,parent_id')->select();
        $data  = $this->getRecursionType($array,$parentId);
        if($parentIdIn===true){
            $data[] = $parentId;
            return  $data;
        }
        if(count($data)>0){
            return $data;
        }else{
            return [];
        }
    }

    /**
     * @desc 递归获取子类Id
     * param $array 包含子类的搜索的数组
     * param $id 父类ID用于查询其子类
     * @return array
     */
    function getRecursionType($array,$id){
        $arr = [];
        foreach($array as $value){
            if($value['parent_id']==$id){
                $arr[] = $value['id'];
                $arr = array_merge($arr,$this->getRecursionType($array,$value['id']));
            }
        }
        return $arr;
    }
}  