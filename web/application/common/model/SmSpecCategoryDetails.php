<?php
namespace app\common\model;

use think\Model;

class SmSpecCategoryDetails extends Model{

	// 设置当前模型对应的完整数据表名称-分类规格明细
    protected $table = 'sm_spec_category_details';
    
    //定义分类规格明细与分类规格 【一对一】
    public function smspeccategoryTable(){
    	return $this->hasOne('SmSpecCategory','id')->field('id,category_id');
    }

    //定义分类规格明细与分类规格明细选项 【一对多】
    public function smspecattroptionsTable(){
        return $this->hasMany('SmSpecAttrOptions','spec_attr_id')->field('id,spec_attr_id,spec_option_text');
    }

    //定义分类规格明细与颜色 【一对多】
    public function mallcolorTable(){
    	return $this->hasMany('MallColor')->field('id,name');
    }

    /**
     * [getCategoryDetails 通过分类ID获取分类规格明细及其选项]
     * @param  integer $categoryId [description]
     * @return [type]              [description]
     */
    public function getCategoryDetails($categoryId=12){
    	$return = [];
    	
    	//通过分类ID获取分类规格ID
    	$specCategoryId = $this->smspeccategoryTable()->where(['category_id'=>$categoryId,'is_deleted'=>0])->find();

    	//通过分类规格ID获取分类规格明细
    	$SmSpecCategoryDetails  = $this->field('id,spec_attr_name,is_standard')->where(['spec_category_id'=>$specCategoryId['id'],'is_deleted'=>0])->select();

    	//通过分类规格明细获取分类规格明细选项值
    	foreach ($SmSpecCategoryDetails as $key => $val) {
    		$returnkv = [];
    		if($val['is_standard']==1){
    			switch ($val['spec_attr_name']) {
    				case '颜色':
    					$returnkv['attrId']	  = $val['id'];
    					$returnkv['attrName'] = '颜色';
    					$returnkv['attrList'] = $this->mallcolorTable()->select();
    					foreach ($returnkv['attrList'] as $k => $v) {
    						$returnkv['attrList'][$k]['img'] = config('jzdc_domain').config('jzdc_static_path').'/static/img/color_icon/'.$v['id'].'.png';
    					}
    					
    					break;
    				default:
    					break;
    			}
    		}else{
    			$returnkv['attrId']	  = $val['id'];
    			$returnkv['attrName'] = $val['spec_attr_name'];
    			$returnkv['attrList'] = $this->smspecattroptionsTable()->field('spec_option_text as name')->where(['spec_attr_id'=>$val['id'],'is_deleted'=>0])->select();
    		}
    		$return[] = $returnkv;
    	}
    	return $return;
    }
	
}  