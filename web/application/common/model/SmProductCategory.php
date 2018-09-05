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

    /**
     * [getCategorySelected 通过depthpath获取所有涉及的分类数据，包括层级数据，选中层级是谁]
     * @param  [type] $categoryIds           [当前选中分类的ID集]
     * @return [type]                       [分类集合数据]
     */
    public function getCategorySelected($categoryIds){
        //[分类全路径['/1/2/3/4','1/2/3/5']]
        $selectedCategoryDepthPath = $this->where(['id'=>['in',$categoryIds]])->field('id,depth_path')->select();
       
        //[分类集合 [['id'=>1,'name'=>'手机','parent_id'=>0,'level'=>1,'depth_path'=>'/1']] ]
        $allCategoryList = $this->field('id,name,parent_id,level,depth_path')->select();

        //分析组合
        $zuhe = [];
        $zuhebingzhi = [];
        foreach ($selectedCategoryDepthPath as $k => $v) {
            //不知道是否为最后一级，所以追加一级
            $vStr = $v['depth_path'].'/0';
            $vArr = explode('/',$vStr);
           
            $name = '';
            foreach ($vArr as $kk => $vv) {
                if($kk>0){
                    $name = $name.'/'.$vv;
                }else{
                    $name = $vv;
                }
                if(isset($vArr[$kk+1])){
                    $zuhe[$k]['level'.$name] = $vArr[$kk+1];
                    if(!in_array('level'.$name, $zuhebingzhi)){
                        $zuhebingzhi[] = 'level'.$name;
                    }
                }
            }
        }

        //获取 
        $zuhebingzhiValue = [];
        foreach ($allCategoryList as $key => $val) {
            if($val['parent_id']==0){
                $zuhebingzhiValue['level'][] = $val;
                continue;
            }

            $zuhebingzhivaluestr = 'level'.substr($val['depth_path'],0,strrpos($val['depth_path'],'/'));
            if($zuhebingzhivaluestr=='level'){
                continue;
            }
            if(in_array($zuhebingzhivaluestr,$zuhebingzhi)){
                $zuhebingzhiValue[$zuhebingzhivaluestr][] = $val;
            }
        }
        // dump($zuhe);
        // dump($zuhebingzhiValue);
        //由于上面对每个都假设后面还有一级，当不存在下一级时候，应该对应删除掉
        foreach ($zuhe as $key => $val) {
            foreach ($val as $kk => $vv) {
                if(!isset($zuhebingzhiValue[$kk])){
                    unset($zuhe[$key][$kk]);
                }
            }
        }
        
        return ['selectedList'=>$zuhe,'levelSelectKeyList'=>$zuhebingzhi,'levelSelectList'=>$zuhebingzhiValue];
    }
}  