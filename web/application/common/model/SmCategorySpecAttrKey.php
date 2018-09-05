<?php
namespace app\common\model;


use think\Model;

class SmCategorySpecAttrKey extends Model
{
    protected $table = 'sm_category_spec_attr_key';

    /**
     * [getCategorySpecAttr 获取分类对应规格属性]
     * @param  [type] $categoryId [分类ID]
     * @return [type]             [description]
     */
    public function getCategorySpecAttr($categoryId){
        $attrKey = $this->field('id,is_standard,spec_attr_key')->where(['category_id'=>$categoryId,'is_deleted'=>0,'category_id'=>$categoryId])->select();

        foreach ($attrKey as $key => $val) {
            if($val['is_standard']==1){
                //系统统一添加的规格值
                switch ($val['spec_attr_key']) {
                    case '颜色':
                        $colorAttrVal = model('MallColor')->field('id,name as spec_option_text')->select();
                        foreach ($colorAttrVal as $k => $v) {
                            $colorAttrVal[$k]['img'] = config('jzdc_doc_path').'../static/img/color_icon/'.$v['id'].'.png';
                        }
                        $attrKey[$key]['spec_attr_val'] = $colorAttrVal;
                        break;
                    default:
                        $attrKey[$key]['spec_attr_val'] = [];
                        break;
                }
            }else{
                //自行添加的规格值
                $attrKey[$key]['spec_attr_val'] = model('SmCategorySpecAttrOptions')->where(['category_spec_attr_key_id'=>$val['id'],'is_deleted'=>0])->field('id,spec_option_text')->select();
            }
        }

        return $attrKey;
    }
}
