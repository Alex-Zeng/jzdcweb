<?php
namespace app\admin\controller;
use app\common\model\SmProduct;
use app\common\model\SmProductGallery;
use app\common\model\SmProductsCategories;
use app\common\model\SmProductCategory;
use app\common\model\SmProductSpec;
use app\common\model\SmProductSpecAttrKey;
use app\common\model\SmProductSpecAttrVal;
use app\common\model\SmProductSpecPrice;
use app\common\model\SmCategorySpecAttrKey;
use app\common\model\IndexArea;
use app\common\model\MallUnit;
use app\common\model\MallColor;
use app\common\model\SmCategorySpecAttrOptions;
use think\Db;


class Product extends Base{

	/**
	 * [create 商城商品添加]
	 * @return [type] [description]
	 */
	public function create(){
		if(request()->isPost()){
			//接收所有提交的数据
			$post = input('post.');
            // dump($post);exit();
            //验证数据
            if($post['supplier_id']<=0){
               return $this->errorMsg('101000');
            }
            if($post['category_id'][0]<=0){
               return  $this->errorMsg('101001');
            }
            if(trim($post['title'])==''){
                return $this->errorMsg('101002');
            }
            if(isset($post['custom_switch'])){
                if(trim($post['custom_price'])==''){
                    return $this->errorMsg('101006');
                }
                if($post['custom_unit']==-1){
                    return $this->errorMsg('101007');
                }
                if($post['custom_unit']==="0"){
                    if($post['custom_unit_text']==''){
                        return $this->errorMsg('101011');
                    }
                    if(mb_strlen($post['custom_unit_text'],"utf-8")>3){
                        return $this->errorMsg('101012');
                    }     
                    $post['custom_unit'] = $post['custom_unit_text'];
                }
            }else{
                if(!isset($post['spec'])){
                   return  $this->errorMsg('101003');
                }
            }
            foreach ($post['category_id'] as $key => $val) {
                if(($val+0)==0){
                    return $this->errorMsg('101010');
                }
            }

            //是否重复
            if(isset($post['spec']['category'])){
                foreach ($post['spec']['category'] as $key => $val) {
                    if(max($val)!=-1 && min($val)==-1){
                        $msg = explode('_', $key);
                        $line = array_flip($val);
                        return $this->errorMsg('101005',['replace'=>['__REPLACE__'=>'第'.($line[min($val)]+1).'行的'.$msg[1].'规格未选择']]);
                    }
                    foreach ($val as $kk => $vv) {
                        if($vv==="0"){
                            if($post['spec']['category_text'][$key][$kk]==''){
                                $msg = explode('_', $key);
                                return $this->errorMsg('101005',['replace'=>['__REPLACE__'=>'第'.($kk+1).'行的'.$msg[1].'规格未填写']]);
                            }
                            if(mb_strlen($post['spec']['category_text'][$key][$kk],"utf-8")>100){
                                $msg = explode('_', $key);
                                return $this->errorMsg('101005',['replace'=>['__REPLACE__'=>'第'.($kk+1).'行的'.$msg[1].'规格长度过长']]);
                            }
                            $post['spec']['category'][$key][$kk] = $post['spec']['category_text'][$key][$kk];
                        }

                        //单位选择
                        if($post['spec']['unit'][$kk]==-1){
                            return $this->errorMsg('101005',['replace'=>['__REPLACE__'=>'第'.($kk+1).'行的自定义单位未选择']]);
                        }
                        if($post['spec']['unit'][$kk]==="0"){
                            if($post['spec']['unit_text'][$kk]==''){
                                return $this->errorMsg('101005',['replace'=>['__REPLACE__'=>'第'.($kk+1).'行的自定义单位未填写']]);
                            }
                            if(mb_strlen($post['spec']['unit_text'][$kk],"utf-8")>3){
                                return $this->errorMsg('101005',['replace'=>['__REPLACE__'=>'第'.($kk+1).'行的自定义单位长度不能超过三位']]);
                            }
                            $post['spec']['unit'][$kk] = $post['spec']['unit_text'][$kk];
                        }
                    }
                }
                
                //是否有规格组合但未任何选择、是否有组合规格是一模一样的
                $checkCategoryEqual = array_keys($post['spec']['category']);
                foreach ($checkCategoryEqual as $k => $v) {
                    for ($i=0; $i <count($post['spec']['category'][$checkCategoryEqual[0]]) ; $i++) { 
                        $checkVal[$i] = isset($checkVal[$i])?$checkVal[$i]:'';
                        $checkVal[$i]  =  $checkVal[$i].'|'. $post['spec']['category'][$v][$i];
                    }
                }
                foreach ($checkVal as $key => $val) {
                    if($val==('|'.implode('|',array_fill(0, count($checkCategoryEqual), '-1')))){
                        return $this->errorMsg('101005',['replace'=>['__REPLACE__'=>'第'.($key+1).'行的规格未作任何选择']]);
                    }
                }
                $checkValUnique = array_unique($checkVal);
                if(count($checkVal)!=count($checkValUnique)){
                    for ($i=0; $i <count($checkVal); $i++) { 
                        if(!isset($checkValUnique[$i])){
                            return $this->errorMsg('101005',['replace'=>['__REPLACE__'=>'第'.($i+1).'行的规格组合重复']]);
                        }
                    }
                }

                //删除多余的规格列
                foreach ($post['spec']['category'] as $k => $v) {
                    if(array_sum($v)==(-1*count($v))){
                        unset($post['spec']['category'][$k]);
                        unset($post['spec']['category_text'][$k]);
                    }
                }
            }
            // dump($post);exit();
            if(trim($post['cover_img_url'])==''){
                return $this->errorMsg('101004');
            }

            $post['audit_state'] = $post['audit_state']+0;
            if($post['audit_state']!=1 &&  $post['audit_state']!=2){
                return  $this->errorMsg('101008');
            }

            $SmProduct              = new SmProduct();
            $SmProductGallery       = new SmProductGallery();
            $SmProductsCategories   = new SmProductsCategories();
            $SmProductSpec          = new SmProductSpec();
            $SmProductSpecAttrKey   = new SmProductSpecAttrKey();
            $SmProductSpecAttrVal   = new SmProductSpecAttrVal();
            $SmProductSpecPrice     = new SmProductSpecPrice();


            // dump($post);exit();
            Db::startTrans();
            $dbCommit = 0;
			//公共创建使用的字段值
			$createDefault = $SmProduct->filedDefaultValue('create');
			
			//ok商品表 			sm_product 					SmProduct 	
                if(isset($post['spec']))  {
                    $is_price_neg_at_phone = count($post['spec']['is_price_neg_at_phone'])==array_sum($post['spec']['is_price_neg_at_phone'])?1:0;
                    $min_price             = min($post['spec']['price']);
                    $max_price             = max($post['spec']['price']);
                }else{
                    $is_price_neg_at_phone = 0;
                    $min_price = $post['custom_price'];
                    $max_price = $post['custom_price'];
                }
    			$data1 = [
    				'category_id' 			=> $post['category_id'][0],
    				'is_price_neg_at_phone'	=> $is_price_neg_at_phone,
    				'min_price'				=> $min_price,
    				'max_price'				=> $max_price,
    				'state'					=> 2,
    				'audit_state'			=> $post['audit_state'],
    				'supplier_id'			=> $post['supplier_id'],
    				'title'					=> trim($post['title']),
    				'cover_img_url'			=> $post['cover_img_url'],
    				'html_content_1'		=> htmlspecialchars($post['html_content_1']),
    				'html_content_2'		=> htmlspecialchars($post['html_content_2']),
    				'province_of_origin_id' => isset($post['province_of_origin_id'])?$post['province_of_origin_id']:0,
    				'city_of_origin_id'		=> isset($post['city_of_origin_id'])?$post['city_of_origin_id']:0,
    				'district_of_origin_id' => isset($post['district_of_origin_id'])?$post['district_of_origin_id']:0
    			];
    			$data1 = array_merge($data1,$createDefault);
                
                $data1_result = $SmProduct->data($data1)->save();
                $product_id = $SmProduct->id;
                if(!$data1_result){
                    $dbCommit = 1; 
                }


            //ok商品多图          sm_product_gallery     SmProductGallery
            if( $post['multi_img_url']!=''){
                $data2 = [];
                $data2_result = [];
                $multi_img_url = $post['multi_img_url'];
                $multi_img_url = explode('|', $multi_img_url);
                if(count($multi_img_url)>0){
                    foreach ($multi_img_url as $key => $val) {
                        if($val!=''){
                            $data2[] = array_merge(['product_id'=>$product_id,'product_image_url'=>$val],$createDefault);
                        }
                    }
                    // dump($data2);exit();
                    $data2_result = $SmProductGallery->saveAll($data2);
                    // dump($data2_result);
                }
                if(!$data2_result){
                    $dbCommit = 2; 
                }
            }    

            //ok商品-分类表        sm_products_categories      SmProductsCategories
                $data3 = [];
                $data3_result = [];
                $category_id = $post['category_id'];
                if(count($category_id)>0){
                    foreach ($category_id as $key => $val) {
                        if($val>0){
                            $data3[] = ['product_id'=>$product_id,'category_id'=>$val];
                        }
                    }
                    // dump($data3);exit();
                    $data3_result = $SmProductsCategories->saveAll($data3);
                    // dump($data3_result);
                }
                if(!$data3_result){
                    $dbCommit = 3; 
                }


            if(isset($post['spec']['category'])){    
                //商品规格组合表       sm_product_spec             SmProductSpec 
                    $data4 = [];//sku_code spec_set未插入
                    $data4_result = [];
                    for ($i=0; $i <count($post['spec']['unit']) ; $i++) { 
                        $data4[] = array_merge($createDefault,[
                                'product_id'    =>  $product_id,
                                'sku_code'      =>  '',
                                'spec_set'      =>  '',
                                'spec_img_url'  =>  $post['spec']['spec_img_url'][$i],
                                'price'         =>  $post['spec']['price'][$i],
                                'unit'          =>  $post['spec']['unit'][$i]==-1?$post['spec']['unit_text'][$i]:$post['spec']['unit'][$i],
                                'min_order_qty' =>  $post['spec']['min_order_qty'][$i],
                                'is_customized' =>  0,
                                'is_price_neg_at_phone'=>intval($post['spec']['is_price_neg_at_phone'][$i])
                            ]);
                    }
                    if(!empty($data4)){
                        //dump($data4);
                        $data4_result = $SmProductSpec->saveAll($data4);
                        //dump($data4_result);
                    }
                    if(!$data4_result){
                        $dbCommit = 4; 
                    }
                    

                //ok商品规格明细表       sm_product_spec_attr_key    SmProductSpecAttrKey
                    $data5 = [];
                    $data5_result = [];
                    foreach ($post['spec']['category'] as $key => $val) {
                        $category_spec_attr_key_val = explode('_', $key);
                        $data5[] = array_merge($createDefault,[
                                'product_id'                =>$product_id,
                                'category_spec_attr_key_id' =>$category_spec_attr_key_val[0],
                                'spec_attr_key'             =>$category_spec_attr_key_val[1]
                            ]);
                    }
                    if(!empty($data5)){
                        // dump($data5);
                        $data5_result = $SmProductSpecAttrKey->saveAll($data5);
                        // dump($data5_result);
                    }
                    if(!$data5_result){
                        $dbCommit = 5; 
                    }
                   
                //ok商品规格明细属性表     sm_product_spec_attr_val    smProductSpecAttrVal
                    $data6 = [];
                    $data6_result = [];
                    $data6_plan_param0 = [];//规格明细属性值循环插入，去重
                    $data6_plan_param1 = [];//获取规格明细值对应的ID和分类及其值关联   8_颜色|红色 8_颜色|绿色
                    $data6_plan_param2_data9 = [];//获取规格明细值对应的ID和分类及其值关联   8_颜色|红色 => id  8_颜色|绿色 => id
                    foreach ($post['spec']['category'] as $k => $v) {
                        $data6_plan_param0[$k] = array_unique($v);
                    }
                    foreach ($data5_result as $k => $v) {//获取规格明细ID插入到规格明细属性值表一对多
                        foreach ($data6_plan_param0[$v['category_spec_attr_key_id'].'_'.$v['spec_attr_key']] as $kk => $vv) {
                            $data6[] = array_merge($createDefault,[
                                'spec_attr_key_id'  => $v['id'],
                                'spec_attr_val'     => $vv,
                            ]);
                            $data6_plan_param1[] = $v['category_spec_attr_key_id'].'_'.$v['spec_attr_key'].'|'.$vv;// 8_颜色|红色
                        }
                    }
                    if(!empty($data6)){
                        //dump($data6);
                        $data6_result = $SmProductSpecAttrVal->saveAll($data6);
                        //dump($data6_result);
                    }
                    foreach ($data6_result as $key => $val) { //8_颜色|红色 => id
                        $data6_plan_param2_data9[$data6_plan_param1[$key]] = $val['id'];
                    }
                    if(!$data6_result){
                        $dbCommit = 6; 
                    }
                    // dump($data6_plan_param2_data9);
                     

                //商品规格组合价格表     sm_product_spec_price       SmProductSpecPrice未完成
                    $data7 = [];
                    $data7_result = [];
                    foreach ($post['spec']['price_section'] as $key => $val) {
                        if($val==''){
                            $data7[] = array_merge($createDefault,[
                                'spec_id'       =>  $data4_result[$key]['id'],
                                'min_order_qty' =>  $post['spec']['min_order_qty'][$key],
                                'max_order_qty' =>  99999999.99,
                                'price'         =>  $post['spec']['price'][$key]
                            ]);
                        }else{
                            $spec_price_section = json_decode($val,true);
                            foreach ($spec_price_section as $k => $v) {
                                $data7[] = array_merge($createDefault,[
                                    'spec_id'       =>  $data4_result[$key]['id'],
                                    'min_order_qty' =>  $v['min_order_qty'],
                                    'max_order_qty' =>  $v['max_order_qty'],
                                    'price'         =>  $v['price']
                                ]);
                            }
                        }
                    }
                    if(!empty($data7)){
                        // dump($data7);
                        $data7_result = $SmProductSpecPrice->saveAll($data7);
                        if(!$data7_result){
                            $dbCommit = 7; 
                        }
                        // dump($data7_result);
                    }
                    
                    
                //商品规格SKU更新  规格
                    $data9_plan_param0 = array_keys($post['spec']['category']);
                    $data9_plan_param1 = [];
                    foreach ($post['spec']['category'] as $k => $v) {
                        foreach ($v as $kk => $vv) {
                            $data9_plan_param1[$kk][] = $data6_plan_param2_data9[$k.'|'.$vv];//spec_set组合
                        }
                    }
                    // dump($data9_plan_param1);
                    foreach ($data4_result as $key => $val) {//更新规格组合表
                        $data_result9 = $SmProductSpec->where(['id'=>$val['id']])->update(['sku_code'=>getSku($val['id']),'spec_set'=>implode(',', $data9_plan_param1[$key])]);
                        // dump(['sku_code'=>getSku($val['id']),'spec_set'=>implode(',', $data9_plan_param1[$key])]);
                        if(!$data_result9){
                            $dbCommit = 8; 
                        }
                    }
            }

            //商品SPU更新
                $data_result10 = $SmProduct->where(['id'=>$product_id])->update(['spu_code'=>getSpu($product_id)]);
                if(!$data_result10){
                    $dbCommit = 9; 
                }
            
            //如果开启定制
            if(isset($post['custom_switch'])){
                $data11[] = array_merge($createDefault,[
                            'product_id'    =>  $product_id,
                            'sku_code'      =>  '',
                            'spec_set'      =>  '',
                            'spec_img_url'  =>  '',
                            'price'         =>  $post['custom_price'],
                            'unit'          =>  $post['custom_unit'],
                            'min_order_qty' =>  1,
                            'is_customized' =>  1,
                            'is_price_neg_at_phone'=>0
                        ]);
                $data11_result = $SmProductSpec->saveAll($data11);
                if($data11_result){
                    $data11_result_2 = $SmProductSpec->where(['id'=>$data11_result[0]['id']])->update(['sku_code'=>getSku($data11_result[0]['id'])]);
                }
                if(!$data11_result_2){
                    $dbCommit = 10; 
                }
                //定制的价格区间
                $data12 =  array_merge($createDefault,[
                            'spec_id'       =>  $data11_result[0]['id'],
                            'min_order_qty' =>  1,
                            'max_order_qty' =>  99999999.99,
                            'price'         =>  $post['custom_price']
                        ]);
                $data12_result =  $SmProductSpecPrice->data($data12)->save();
                if(!$data12_result){
                    $dbCommit = 11; 
                }
            }

            if($dbCommit==0){
                Db::commit();
                return $this->successMsg('reload',['msg'=>'添加商品成功']);
            }else{
                Db::rollback();
                return $this->errorMsg('101009',['replace'=>['__REPLACE__'=>$dbCommit]]);
            }

            
		}
		
		//单位
        $MallUnit = new MallUnit();
        $unitRows = $MallUnit->where([])->order('sequence','desc')->field(['id','name'])->select();
        return view('',['unitRows'=>$unitRows]);
	}



	/**
	 * [edit 商城商品修改]
	 * @return [type] [description]
	 */
	public function edit(){
        $SmProduct              = new SmProduct();
        $SmProductGallery       = new SmProductGallery();
        $SmProductsCategories   = new SmProductsCategories();
        $SmProductCategory      = new SmProductCategory();
        $SmProductSpec          = new SmProductSpec();
        $SmProductSpecAttrKey   = new SmProductSpecAttrKey();
        $SmProductSpecAttrVal   = new SmProductSpecAttrVal();
        $SmProductSpecPrice     = new SmProductSpecPrice();
        $MallColor              = new MallColor();
        $SmCategorySpecAttrKey = new SmCategorySpecAttrKey();
        $SmCategorySpecAttrOptions = new SmCategorySpecAttrOptions();

        if(request()->isPost()){
            $post = input("post.");
            $product_id = input("post.product_id",0,'intval');
            // dump($post);exit();
            //验证数据
            if($post['supplier_id']<=0){
               return $this->errorMsg('101200');
            }
            if(trim($post['title'])==''){
                return $this->errorMsg('101201');
            }
            if(isset($post['custom_switch'])){
                if(trim($post['custom_price'])==''){
                    return $this->errorMsg('101202');
                }
                if($post['custom_unit']==-1){
                    return $this->errorMsg('101203');
                }
                if($post['custom_unit']==="0"){
                    if($post['custom_unit_text']==''){
                        return $this->errorMsg('101213');
                    }
                    if(mb_strlen($post['custom_unit_text'],"utf-8")>3){
                        return $this->errorMsg('101214');
                    }     
                    $post['custom_unit'] = $post['custom_unit_text'];
                }
            }else{
                if(!isset($post['update_spec']) && !isset($post['spec'])){
                   return  $this->errorMsg('101204');
                }
            }
            if(isset($post['update_category_id'])){
                foreach ($post['update_category_id'] as $key => $val) {
                    if(($val+0)==0){
                        return $this->errorMsg('101205');
                    }
                }
            }
            if(isset($post['category_id'])){
                foreach ($post['category_id'] as $key => $val) {
                    if(($val+0)==0){
                        return $this->errorMsg('101206');
                    }
                }
            }

            if(isset($post['spec'])){//删除多余的规格列
                if(isset($post['update_spec'])){
                    $update_category_key = array_keys($post['update_spec']['category']);
                }else{
                    $update_category_key = [];
                }
                foreach ($post['spec']['category'] as $k => $v) {
                    if(count($update_category_key)>0){
                        if(!in_array($k,$update_category_key)){
                            unset($post['spec']['category'][$k]);
                            unset($post['spec']['category_text'][$k]);
                        }
                    }
                }
            }

            $lineAttr = [];
            $lineUpdateCount = 0;
            if(isset($post['update_spec'])){
                $lineAttr = $post['update_spec'];
                $lineUpdateCount = count($post['update_spec']['unit']);//获取已插入的记录数
            }
            if(isset($post['spec'])){
                $lineAttr = array_merge_recursive($lineAttr,$post['spec']);
            }
            if(count($lineAttr)>0){
                foreach ($lineAttr['category'] as $key => $val) {
                    if(max($val)!=-1 && min($val)==-1){
                        $msg = explode('_', $key);
                        $line = array_flip($val);
                        return $this->errorMsg('101207',['replace'=>['__REPLACE__'=>'第'.($line[min($val)]+1).'行的'.$msg[1].'规格未选择']]);
                    }
                    foreach ($val as $kk => $vv) {
                        if($vv==="0"){
                            if($lineAttr['category_text'][$key][$kk]==''){
                                $msg = explode('_', $key);
                                return $this->errorMsg('101207',['replace'=>['__REPLACE__'=>'第'.($kk+1).'行的'.$msg[1].'规格未填写']]);
                            }
                            if(mb_strlen($lineAttr['category_text'][$key][$kk],"utf-8")>100){
                                $msg = explode('_', $key);
                                return $this->errorMsg('101207',['replace'=>['__REPLACE__'=>'第'.($kk+1).'行的'.$msg[1].'规格长度过长']]);
                            }
                            $post['spec']['category'][$key][$kk-$lineUpdateCount] = $lineAttr['category_text'][$key][$kk];//好关键的赋值
                        }
                        $checkVal[$kk] = isset($checkVal[$kk])?$checkVal[$kk]:'';
                        $checkVal[$kk]  =  $checkVal[$kk].'|'. $vv;
                    }
                }
                //是否未有规格组合但未任何选择、是否有组合规格是一模一样的
                foreach ($checkVal as $key => $val) {
                    if($val==('|'.implode('|',array_fill(0, count($lineAttr['category']), '-1')))){
                        return $this->errorMsg('101207',['replace'=>['__REPLACE__'=>'第'.($key+1).'行的规格未作任何选择']]);
                    }
                }
                $checkValUnique = array_unique($checkVal);
                if(count($checkVal)!=count($checkValUnique)){
                    for ($i=0; $i <count($checkVal); $i++) { 
                        if(!isset($checkValUnique[$i])){
                            return $this->errorMsg('101207',['replace'=>['__REPLACE__'=>'第'.($i+1).'行的规格组合重复']]);
                        }
                    }
                }


                // dump($lineAttr);exit();
                //单位
                for ($i=0; $i < count($lineAttr['unit']); $i++) { 
                    if($lineAttr['unit'][$i]=='-1'){
                        return $this->errorMsg('101005',['replace'=>['__REPLACE__'=>'第'.($i+1).'行的自定义单位未选择']]);
                    }
                    if($lineAttr['unit'][$i]==="0"){
                        if($lineAttr['unit_text'][$i]==''){
                            return $this->errorMsg('101207',['replace'=>['__REPLACE__'=>'第'.($i+1).'行的自定义单位未填写']]);
                        }
                        if(mb_strlen($lineAttr['unit_text'][$i],"utf-8")>3){
                            return $this->errorMsg('101207',['replace'=>['__REPLACE__'=>'第'.($i+1).'行的自定义单位长度不能超过三位']]);
                        }
                        if($i<$lineUpdateCount){
                            $post['update_spec']['unit'][$i] = $lineAttr['unit_text'][$i];
                        }else{
                            $post['spec']['unit'][$i-$lineUpdateCount] = $lineAttr['unit_text'][$i];
                        }
                    }
                }
            }   


            // dump($post);exit;
            if(trim($post['cover_img_url'])==''){
                return $this->errorMsg('101208');
            }

            $post['audit_state'] = $post['audit_state']+0;
            if($post['audit_state']!=1 &&  $post['audit_state']!=2){
                return  $this->errorMsg('101209');
            }

            $createDefault = $SmProduct->filedDefaultValue('create');
            $updateDefault = $SmProduct->filedDefaultValue('update');
            $deleteDefault = $SmProduct->filedDefaultValue('delete');
            
            Db::startTrans();

            //商品更新
            $data1 = [
                'is_price_neg_at_phone' =>intval($post['is_price_neg_at_phone']),
                'min_price'             =>$post['min_price'],
                'max_price'             =>$post['max_price'],
                'supplier_id'           =>$post['supplier_id'],
                'title'                 =>$post['title'],
                'cover_img_url'         =>$post['cover_img_url'],
                'district_of_origin_id' =>isset($post['district_of_origin_id'])?$post['district_of_origin_id']:0,
                'city_of_origin_id'     =>isset($post['city_of_origin_id'])?$post['city_of_origin_id']:0,
                'province_of_origin_id' =>isset($post['province_of_origin_id'])?$post['province_of_origin_id']:0,
                'html_content_1'        =>$post['html_content_1'],
                'html_content_2'        =>$post['html_content_2'],
                'audit_state'           =>$post['audit_state'],
                'state'                 =>  2
            ];
            $data1_result = $SmProduct->where(['id'=>$product_id])->update(array_merge($updateDefault,$data1));

            if(!$data1_result){
                Db::rollback(); 
                return $this->errorMsg('101211',['replace'=>['__REPLACE__'=>'data1_result']]);
            }
            // dump($data1_result);exit();

            //ok商品多图          sm_product_gallery     SmProductGallery
            if( $post['multi_img_url']!=''){
                $data2 = [];
                $data2_result = [];
                $multi_img_url = $post['multi_img_url'];
                $multi_img_url = explode('|', $multi_img_url);
                if(count($multi_img_url)>0){
                    foreach ($multi_img_url as $key => $val) {
                        if($val!=''){
                            $data2[] = array_merge(['product_id'=>$product_id,'product_image_url'=>$val],$createDefault);
                        }
                    }
                    // dump($data2);exit();
                    $data2_result = $SmProductGallery->saveAll($data2);
                    // dump($data2_result);
                }
                if(!$data2_result){
                    $dbCommit = 2; 
                }
            } 

            //商品分类表-新增-删除-更新
            if(isset($post['category_id']) && count($post['category_id'])>0){//新增
                foreach ($post['category_id'] as $key => $val) {
                    if($val!=0){
                        $data3 = ['product_id'=>$product_id,'category_id'=>$val];
                        if($SmProductsCategories->where($data3)->count()==0){
                            $data3_result = $SmProductsCategories->data($data3)->save();
                            if(!$data3_result){
                                Db::rollback(); 
                                return $this->errorMsg('101211',['replace'=>['__REPLACE__'=>'data3_result']]);
                            }
                        }else{
                            return $this->errorMsg('101210');
                        }
                    }
                }
            }
            if(isset($post['delete_category_id']) && count($post['delete_category_id'])>0){//删除
                foreach ($post['delete_category_id'] as $key => $val) {
                    $data3_result_delete = $SmProductsCategories->where(['product_id'=>$product_id,'category_id'=>$val])->delete();
                    if(!$data3_result_delete){
                        Db::rollback(); 
                        return $this->errorMsg('101211',['replace'=>['__REPLACE__'=>'data3_result_delete']]);
                    }
                }
            }
            if(isset($post['update_category_id'])){//更新
                foreach ($post['update_category_id'] as $key => $val) {
                    if($key!=$val && $val!=0){
                        $data3_update = ['product_id'=>$product_id,'category_id'=>$val];
                        if($SmProductsCategories->where($data3_update)->count()==0){
                            $data3_result_update = $SmProductsCategories->where(['product_id'=>$product_id,'category_id'=>$key])->update(['category_id'=>$val]);
                            if(!$data3_result_update){
                                Db::rollback(); 
                                return $this->errorMsg('101211',['replace'=>['__REPLACE__'=>'data3_result_update']]);
                            }
                        }else{
                            return $this->errorMsg('101212');
                        }
                    }
                }
            }

            //商品规格新增
            if(isset($post['spec']['category'])){
             
                //商品规格组合表       sm_product_spec             SmProductSpec 
                    $data4 = [];//sku_code spec_set未插入
                    $data4_result = [];
                    for ($i=0; $i <count($post['spec']['unit']) ; $i++) { 
                        $data4[] = array_merge($createDefault,[
                                'product_id'    =>  $product_id,
                                'sku_code'      =>  '',
                                'spec_set'      =>  '',
                                'spec_img_url'  =>  $post['spec']['spec_img_url'][$i],
                                'price'         =>  $post['spec']['price'][$i],
                                'unit'          =>  $post['spec']['unit'][$i]==-1?$post['spec']['unit_text'][$i]:$post['spec']['unit'][$i],
                                'min_order_qty' =>  $post['spec']['min_order_qty'][$i],
                                'is_customized' =>  0,
                                'is_price_neg_at_phone'=>intval($post['spec']['is_price_neg_at_phone'][$i])
                            ]);
                    }
                    $data4_result = $SmProductSpec->saveAll($data4);
                    if(!$data4_result){
                        Db::rollback(); 
                        return $this->errorMsg('101211',['replace'=>['__REPLACE__'=>'data4_result']]);
                    }                    

                //ok商品规格明细表       sm_product_spec_attr_key    SmProductSpecAttrKey
                    $data5 = [];
                    $data5_result = [];
                    foreach ($post['spec']['category'] as $key => $val) {
                        $category_spec_attr_key_val = explode('_', $key);
                        //查询是否存在
                        if($SmProductSpecAttrKey->where(['product_id'=>$product_id,'category_spec_attr_key_id'=>$category_spec_attr_key_val[0],'is_deleted'=>0])->count()==0){
                            $data5[] = array_merge($createDefault,[
                                'product_id'                =>$product_id,
                                'category_spec_attr_key_id' =>$category_spec_attr_key_val[0],
                                'spec_attr_key'             =>$category_spec_attr_key_val[1]
                            ]);
                        }
                    }
                    if(count($data5)>0){
                        $data5_result = $SmProductSpecAttrKey->saveAll($data5);//这个不是所有的规格明细所以要重新查
                        if(!$data5_result){
                            Db::rollback(); 
                            return $this->errorMsg('101211',['replace'=>['__REPLACE__'=>'data5_result']]); 
                        }
                    }
                    $data5_result = $SmProductSpecAttrKey->where(['product_id'=>$product_id,'is_deleted'=>0])->select();//由于上面的仅是插入新增的所以所有的规格明细要重新查

                   
                //ok商品规格明细属性表     sm_product_spec_attr_val    smProductSpecAttrVal
                    $data6 = [];
                    $data6_result = [];
                    $data6_plan_param0 = [];//规格明细属性值循环插入，去重
                    $data6_plan_param1 = [];//获取规格明细值对应的ID和分类及其值关联   8_颜色|红色 8_颜色|绿色
                    $data6_plan_param2_data9 = [];//获取规格明细值对应的ID和分类及其值关联   8_颜色|红色 => id  8_颜色|绿色 => id
                    foreach ($post['spec']['category'] as $k => $v) {
                        $data6_plan_param0[$k] = array_unique($v);
                    }
                    foreach ($data5_result as $k => $v) {//获取规格明细ID插入到规格明细属性值表一对多
                        if(!isset($data6_plan_param0[$v['category_spec_attr_key_id'].'_'.$v['spec_attr_key']])){
                            continue;
                        }
                        foreach ($data6_plan_param0[$v['category_spec_attr_key_id'].'_'.$v['spec_attr_key']] as $kk => $vv) {
                            if($SmProductSpecAttrVal->where(['spec_attr_key_id'=>$v['id'],'spec_attr_val'=>$vv,'is_deleted'=>0])->count()==0){
                                $data6[] = array_merge($createDefault,[
                                    'spec_attr_key_id'  => $v['id'],
                                    'spec_attr_val'     => $vv,
                                ]);
                            }
                            // $data6_plan_param1[] = $v['category_spec_attr_key_id'].'_'.$v['spec_attr_key'].'|'.$vv;// 8_颜色|红色
                        }
                    }
                    if(count($data6)>0){
                        $data6_result = $SmProductSpecAttrVal->saveAll($data6); 
                        if(!$data6_result){
                            Db::rollback(); 
                            return $this->errorMsg('101211',['replace'=>['__REPLACE__'=>'data6_result']]);
                        }
                    }
                    foreach ($data5_result as $k => $v) {
                        $data6_result = $SmProductSpecAttrVal->where(['spec_attr_key_id'=>$v['id'],'is_deleted'=>0])->select();
                        foreach ($data6_result as $kk => $vv) {
                            $data6_plan_param2_data9[$v['category_spec_attr_key_id'].'_'.$v['spec_attr_key'].'|'.$vv['spec_attr_val']] = $vv['id'];
                        }
                    }
                    // foreach ($data6_result as $key => $val) { //8_颜色|红色 => id
                    //     $data6_plan_param2_data9[$data6_plan_param1[$key]] = $val['id'];
                    // }
                    
                     // dump($data6_plan_param2_data9) ;exit();  

                //商品规格组合价格表     sm_product_spec_price       SmProductSpecPrice未完成
                    // dump($data4_result);exit();
                    $data7 = [];
                    $data7_result = [];
                    foreach ($post['spec']['price_section'] as $key => $val) {
                        if($val==''){
                            $data7[] = array_merge($createDefault,[
                                'spec_id'       =>  $data4_result[$key]['id'],
                                'min_order_qty' =>  $post['spec']['min_order_qty'][$key],
                                'max_order_qty' =>  99999999.99,
                                'price'         =>  $post['spec']['price'][$key]
                            ]);
                        }else{
                            $spec_price_section = json_decode($val,true);
                            foreach ($spec_price_section as $k => $v) {
                                $data7[] = array_merge($createDefault,[
                                    'spec_id'       =>  $data4_result[$key]['id'],
                                    'min_order_qty' =>  $v['min_order_qty'],
                                    'max_order_qty' =>  $v['max_order_qty'],
                                    'price'         =>  $v['price']
                                ]);
                            }
                        }
                    }
                    if(!empty($data7)){
                        $data7_result = $SmProductSpecPrice->saveAll($data7);
                        if(!$data7_result){
                            Db::rollback(); 
                            return $this->errorMsg('101211',['replace'=>['__REPLACE__'=>'data7_result']]);
                        }
                    }
                    

                    
                    
                //商品规格SKU更新  规格
                    $data9_plan_param0 = array_keys($post['spec']['category']);
                    $data9_plan_param1 = [];
                    foreach ($post['spec']['category'] as $k => $v) {
                        foreach ($v as $kk => $vv) {
                            $data9_plan_param1[$kk][] = $data6_plan_param2_data9[$k.'|'.$vv];//spec_set组合
                        }
                    }
                    // dump($data9_plan_param1);
                    foreach ($data4_result as $key => $val) {//更新规格组合表
                        $data_result9 = $SmProductSpec->where(['id'=>$val['id']])->update(['sku_code'=>getSku($val['id']),'spec_set'=>implode(',', $data9_plan_param1[$key])]);
                        // dump(['sku_code'=>getSku($val['id']),'spec_set'=>implode(',', $data9_plan_param1[$key])]);
                        if(!$data_result9){
                            Db::rollback(); 
                            return $this->errorMsg('101211',['replace'=>['__REPLACE__'=>'data_result9']]);
                        }
                    }
            }
            //商品规格更新
            if(isset($post['update_spec'])){ 
                foreach ($post['update_spec']['id'] as $key => $val) {
                    $data4_result_update = $SmProductSpec->where(['id'=>$val])->update(array_merge($updateDefault,[
                        'spec_img_url'=>$post['update_spec']['spec_img_url'][$key],
                        'price'=>$post['update_spec']['price'][$key],
                        'min_order_qty'=>$post['update_spec']['min_order_qty'][$key],
                        'unit'=>$post['update_spec']['unit'][$key],
                        'is_price_neg_at_phone'=>intval($post['update_spec']['is_price_neg_at_phone'][$key]),
                    ]));
                    if(!$data4_result_update){
                        Db::rollback(); 
                        return $this->errorMsg('101211',['replace'=>['__REPLACE__'=>'data4_result_update']]);
                    }
                }

                $data8 = [];
                $data8_result = [];
                foreach ($post['update_spec']['price_section'] as $key => $val) {
                    $update_spec_price_section = json_decode($val,true);
                    foreach ($update_spec_price_section as $k => $v) {
                        if($v['id']>0){
                            $data8_update = array_merge($updateDefault,[
                                'min_order_qty' =>  $v['min_order_qty'],
                                'max_order_qty' =>  $v['max_order_qty'],
                                'price'         =>  $v['price']
                            ]);
                            $data8_update_result =  $SmProductSpecPrice->where(['id'=>$v['id']])->update($data8_update);
                            if(!$data8_update_result){
                                Db::rollback(); 
                                return $this->errorMsg('101211',['replace'=>['__REPLACE__'=>'data8_update_result'.serialize($data8_update)]]);
                            }
                        }else{
                            $data8[] = array_merge($createDefault,[
                                'spec_id'       =>  $post['update_spec']['id'][$key],
                                'min_order_qty' =>  $v['min_order_qty'],
                                'max_order_qty' =>  $v['max_order_qty'],
                                'price'         =>  $v['price']
                            ]);
                            
                        } 
                    }
                }
                if(count($data8)>0){
                    $data8_result = $SmProductSpecPrice->saveAll($data8);
                    if(!$data8_result){
                        Db::rollback(); 
                        return $this->errorMsg('101211',['replace'=>['__REPLACE__'=>'data8_result']]);
                    }
                }
            }
            //商品价格区间删除
            if(isset($post['delete_spec_price'])){ 
                foreach ($post['delete_spec_price']['id'] as $key => $val) {
                    $data8_result_delete = $SmProductSpecPrice->where(['id'=>$val])->update($deleteDefault);
                    if(!$data8_result_delete){
                        Db::rollback(); 
                        return $this->errorMsg('101211',['replace'=>['__REPLACE__'=>'data8_result_delete']]);
                    }
                }
            }
            //商品规格删除
            if(isset($post['delete_spec'])){ 
                foreach ($post['delete_spec']['id'] as $key => $val) {
                    $data4_result_delete = $SmProductSpec->where(['id'=>$val])->update($deleteDefault);
                    if(!$data4_result_delete){
                        Db::rollback(); 
                        return $this->errorMsg('101211',['replace'=>['__REPLACE__'=>'data4_result_delete']]);
                    }
                }
            }

            //定制更新
            if(isset($post['custom_switch'])){
                if(isset($post['custom_id']) && $post['custom_id']>0){//更新
                    $data11_result_update = $SmProductSpec->where(['id'=>$post['custom_id'],'is_deleted'=>0])->update(array_merge($updateDefault,['price'=>$post['custom_price'],'unit'=>$post['custom_unit']]));
                    if(!$data11_result_update){
                        Db::rollback(); 
                        return $this->errorMsg('101211',['replace'=>['__REPLACE__'=>'data11_result_update']]);
                    }
                    $data12_result_update = $SmProductSpecPrice->where(['spec_id'=>$post['custom_id'],'is_deleted'=>0])->update(array_merge($updateDefault,['price'=>$post['custom_price']]));
                    if(!$data12_result_update){
                        Db::rollback(); 
                        return $this->errorMsg('101211',['replace'=>['__REPLACE__'=>'data12_result_update']]);
                    }
                }else{//添加
                    $data11[] = array_merge($createDefault,[
                            'product_id'    =>  $product_id,
                            'sku_code'      =>  '',
                            'spec_set'      =>  '',
                            'spec_img_url'  =>  '',
                            'price'         =>  $post['custom_price'],
                            'unit'          =>  $post['custom_unit'],
                            'min_order_qty' =>  1,
                            'is_customized' =>  1,
                            'is_price_neg_at_phone'=>0
                        ]);
                    $data11_result = $SmProductSpec->saveAll($data11);
                    if(!$data11_result){
                        Db::rollback(); 
                        return $this->errorMsg('101211',['replace'=>['__REPLACE__'=>'data11_result']]);
                    }
                    $data11_result_2 = $SmProductSpec->where(['id'=>$data11_result[0]['id']])->update(['sku_code'=>getSku($data11_result[0]['id'])]);
                    if(!$data11_result_2){
                        Db::rollback(); 
                        return $this->errorMsg('101211',['replace'=>['__REPLACE__'=>'data11_result_2']]);
                    }
                    //定制的价格区间
                    $data12 =  array_merge($createDefault,[
                                'spec_id'       =>  $data11_result[0]['id'],
                                'min_order_qty' =>  1,
                                'max_order_qty' =>  99999999.99,
                                'price'         =>  $post['custom_price']
                            ]);
                    $data12_result =  $SmProductSpecPrice->data($data12)->save();
                    if(!$data12_result){
                        Db::rollback(); 
                        return $this->errorMsg('101211',['replace'=>['__REPLACE__'=>'data12_result']]);
                    }
                }
            }else{
                if(isset($post['custom_id']) && $post['custom_id']>0){//删除
                    $data11_result_delete = $SmProductSpec->where(['id'=>$post['custom_id'],'is_deleted'=>0])->update($deleteDefault);
                    if(!$data11_result_delete){
                        Db::rollback(); 
                        return $this->errorMsg('101211',['replace'=>['__REPLACE__'=>'data11_result_delete']]);
                    }
                    $data12_result_delete =$SmProductSpecPrice->where(['spec_id'=>$post['custom_id'],'is_deleted'=>0])->update($deleteDefault);
                    if(!$data11_result_delete){
                        Db::rollback(); 
                        return $this->errorMsg('101211',['replace'=>['__REPLACE__'=>'data12_result_delete']]);
                    }
                }
            }

            Db::commit();
            return $this->successMsg('reload',['msg'=>'修改成功']);

        }

        $product_id = input('param.id',0,'intval');
        //商品表
        $row = $SmProduct->where(['id'=>$product_id])->find();
        $row['cover_img_url_path'] = $SmProduct::getFormatImg($row['cover_img_url']);
        
        // dump($row);exit();
        //商品分类

        //商品多图
        $multi_img_url = $SmProductGallery->field('id,product_image_url')->where(['product_id'=>$product_id,'is_deleted'=>0])->select(); 
        $multi_img_url_path = [];
        foreach ($multi_img_url as $key => $val) {
            $multi_img_url_path[$key] = $SmProduct::getFormatMultiImg($val['product_image_url']);
            $multi_img_url[$key]['url'] = '/?s=admin/product/imgDelete';
            $multi_img_url[$key]['key'] =  $val['id'];
        }
        // dump(json_encode($multi_img_url));exit();
        $row['multi_img_url_path'] = $multi_img_url_path;
        $row['multi_img_url'] = $multi_img_url;
        //商品分类
        $allCategrody = $SmProductsCategories->where(['product_id'=>$product_id])->order('id asc')->column('category_id');
        $categorySelected = $SmProductCategory->getCategorySelected($allCategrody);
        
        //读取商品规格组合
        $all = $SmProductSpec->field('id,is_customized,sku_code,spec_set,spec_img_url,price,unit,min_order_qty,is_price_neg_at_phone')->where(['product_id'=>$product_id,'is_deleted'=>0])->order('is_customized desc')->select();
        $spec = [];
        foreach ($all as $key => $val) {
            if ($val['is_customized']==1) {
                $row['custom_switch']=1;
                $row['custom_id']=$val['id'];
                $row['custom_price']=$val['price'];
                $row['custom_unit']=$val['unit'];
            }else{

                $attrValAll = $SmProductSpecAttrVal->alias('a')->join(['sm_product_spec_attr_key'=>'b'],'a.spec_attr_key_id=b.id','left')->where(['a.id'=>['in',$val['spec_set']]])->field('a.spec_attr_val,b.category_spec_attr_key_id,b.spec_attr_key')->select(); 
                foreach ($attrValAll as $kk => $vv) {
                    $attrVal[$vv['category_spec_attr_key_id'].'_'.$vv['spec_attr_key']] = $vv['spec_attr_val'];
                }
                $price_section = $SmProductSpecPrice->alias('a')->field('a.min_order_qty,a.max_order_qty,a.price,a.id')->where(['spec_id'=>$val['id'],'is_deleted'=>0])->select();
                // dump($price_section[0]);exit();
                $spec[] = 
                [
                    'id'=>$val['id'],
                    'category'=>$attrVal,
                    'unit'=>$val['unit'],
                    'is_price_neg_at_phone'=>$val['is_price_neg_at_phone'],
                    'min_order_qty'=>$val['min_order_qty'],
                    'price'=>$val['price'],
                    'price_id'=>$price_section[0]['id'],
                    'spec_img_url'=>$val['spec_img_url'],
                    'spec_img_url_path'=>$SmProduct->getFormatMultiImg($val['spec_img_url']),
                    'price_section'=> json_encode($price_section),
                ];
            }

        }
        $row['spec'] = $spec;
        // //读取商品规格
        // $specAll = $SmCategorySpecAttrKey->field('id,spec_attr_key')->where(['category_id'=>$row['category_id'],'is_deleted'=>0])->select();
        // $specAttr = [];
        // foreach ($specAll as $key => $val) {
            // if($val['spec_attr_key']=='颜色'){
            //     $specAttr[$val['id'].'_'.$val['spec_attr_key']] = $MallColor->column('name');
            // }else{
            //     $specAttr[$val['id'].'_'.$val['spec_attr_key']] = $SmCategorySpecAttrOptions->where(['category_spec_attr_key_id'=>$val['id'],'is_deleted'=>0])->column('spec_option_text');
            // }
         // } 
         // $row['specAttr'] = $specAttr;



        //单位
        $MallUnit = new MallUnit();
        $unitRows = $MallUnit->where([])->order('sequence','desc')->column('name');


        $this->assign('row',$row); 
        $this->assign('categorySelected',$categorySelected);
        $this->assign('unitRows',$unitRows);
        return view('');
	}


    public function imgDelete(){
        $id = input('post.key',0,'intval');
        $SmProductGallery      = new SmProductGallery();
        $deleteDefault = $SmProductGallery->filedDefaultValue('delete');
        $result = $SmProductGallery->where(['id'=>$id])->update($deleteDefault);
        // dump($result);exit();
        if($result){
            return $this->successMsg('noSkip',['msg'=>'删除成功']);
        }else{
            return $this->errorMsg('101100');
        }

    }

	/**
	 * [view 商城商品查看]
	 * @return [type] [description]
	 */
	public function view(){
		//获取商品ID
		$productId = input('param.id',0,'intval');

        $SmProduct = new SmProduct();
        $SmProductSpec = new SmProductSpec();
        $SmProductSpecAttrVal = new SmProductSpecAttrVal();
        $SmProductGallery = new SmProductGallery();
        $SmProductsCategories = new SmProductsCategories();
        $SmProductCategory = new SmProductCategory();
        $IndexArea = new IndexArea();

        $where['a.id'] = $productId;
        $product = $SmProduct
                ->field('a.id,a.spu_code,a.cover_img_url,a.html_content_1,a.html_content_2,a.province_of_origin_id,a.city_of_origin_id,a.district_of_origin_id,a.title,b.nickname as supplier_name')
                ->alias('a')
                ->join('jzdc_index_user b','a.supplier_id=b.id','LEFT')->where($where)->find();
        $product['cover_img_url'] = $SmProduct->getFormatImg($product['cover_img_url']);

        $rows = [];
        $multiImg = [];
        $categorySelected = [];
        $productArea = [];
        if(!empty($product)){
            //规格
            $rows = $SmProductSpec->field('id,sku_code,spec_set,price,unit,is_customized,is_price_neg_at_phone,min_order_qty')->where(['product_id'=>$productId])->select();
            foreach ($rows as $key => $val) {
            	$rows[$key]['attr'] = $SmProductSpecAttrVal->field('spec_attr_val')->where(['id'=>['in',$val['spec_set']]])->select();
            }

            //多视角图片
            $multiImg = $SmProductGallery->field('product_image_url')->where(['product_id'=>$productId])->select();
            foreach ($multiImg as $key => $val) {
            	$multiImg[$key]['product_image_url'] = $SmProduct->getFormatMultiImg($val['product_image_url']);
            }

            //对分类进行多层级回显
            ////通过商品ID找出其关联的所有分类
            $allCategrody = $SmProductsCategories->where(['product_id'=>$productId])->column('category_id'); 
            $categorySelected = $SmProductCategory->getCategorySelected($allCategrody);

            //产地省市区
            $area = [];
            $area[] = $product['province_of_origin_id'];
            $area[] = $product['city_of_origin_id'];
            $area[] = $product['district_of_origin_id'];
            $productArea = $IndexArea->where(['id'=>['in',$area]])->column('id,name');
        }
        
        $this->assign('product',$product);                        //商品信息
        $this->assign('rows',$rows);                            //回显规格组合
        $this->assign('multiImg',$multiImg);                    //回显多视角图片
        $this->assign('categorySelected',$categorySelected);    //回显所有分类
        $this->assign('productArea',$productArea);    //回显所有分类
		return view();
	}

	/**
	 * [update 上架/下架/设为推荐/取消推荐/删除/审核]
	 * @return [type] [description]
	 */
	public function update(){
		$type = input('post.type','','trim');
		$id   = input('post.id',0,'intval');

        //查询是否存在
        $SmProduct = new SmProduct();
        $data = $SmProduct->where(['id'=>$id])->field('id')->find();
        if(!$data){
        	return $this->errorMsg('100900');
        }

        $where['id'] = $id;
		switch ($type) {
			case 'sellDown':
				$where['state'] = 1; 
				$update = array_merge($SmProduct->filedDefaultValue('update'),['state'=>2]);
				$msg = '下架';
				break;
			case 'sellUp':
				$where['state'] = 2; 
				$update = array_merge($SmProduct->filedDefaultValue('update'),['state'=>1]);
				$msg = '上架';	
				break;
			case 'pushUp':
				$where['is_recommended'] = 0; 
				$update = array_merge($SmProduct->filedDefaultValue('update'),['is_recommended'=>1]);
				$msg = '设为推荐';
				break;
			case 'pushDown':
				$where['is_recommended'] = 1; 
				$update = array_merge($SmProduct->filedDefaultValue('update'),['is_recommended'=>0]);
				$msg = '取消推荐';
				break;
			case 'delete':
				$where['is_deleted'] = 0; 
				$update = $SmProduct->filedDefaultValue('delete');
				$msg = '删除商品';
				break;
			case 'verify':
				$state = input('post.state',0,'intval');
				switch ($state) {
		            case 1://审核通过
		                $auditState = SmProduct::AUDIT_RELEASED;
		                break;
		            case 2://审核失败
		                $auditState = SmProduct::AUDIT_NOTAPPROVED;
		                break;
		            default:
		                $auditState = SmProduct::AUDIT_PENDING;
		                break;
				}
				$update = array_merge($SmProduct->filedDefaultValue('update'),['state'=>1,'audit_state'=>$auditState]);
				$where['audit_state'] = SmProduct::AUDIT_PENDING; 
				$msg = '审核';
				break;
		}

		if(!isset($update)){
			return $this->errorMsg('100901');
		}
		$result = $SmProduct->where($where)->update($update);
		if($result==1){
			return $this->successMsg('reload',['msg'=>$msg.'操作成功']);
		}else{
			return $this->errorMsg('100902',['replace'=>['__REPLACE__'=>$msg]]);
		}
	}

	/**
	 * [listPass 商城商品已审核列表]
	 * @return [type] [description]
	 */
	public function listPass(){
		$title = input('get.title','','trim');
        $supplierId = input('get.supplier_id',0,'intval');
        $isRecommended = input('get.is_recommended',-1,'intval');
        $categoryId = input('get.category_id',0,'intval');

        $SmProductsCategories = new SmProductsCategories();
        $SmProductCategory = new SmProductCategory();
        $SmProduct = new SmProduct();
        $SmProductSpec = new SmProductSpec();
        //是否删除
        $where['a.is_deleted'] = 0;
        
        //已审核
        $where['a.audit_state'] = SmProduct::AUDIT_RELEASED;
        
        //是否推荐
        if($isRecommended>-1){
            $where['a.is_recommended']  = $isRecommended;
        }
        //供应商
        if($supplierId>0){
            $where['a.supplier_id']  = $supplierId;
        }
        //商品名称
        if($title!=''){
            $where['a.title']  = ['like','%'.$title.'%'];
        }
        //分类
        if($categoryId>0){
            $productIds = $SmProductsCategories->where(['category_id'=>$categoryId])->column('product_id');
            $where['a.id'] = ['in',$productIds];

            //对分类进行多层级回显
            $categorySelected = $SmProductCategory->getCategorySelected([$categoryId]);
           
           	//categorySelected数组中selectedList选中的值、levelSelectList选中值得同级成员
            $this->assign('categorySelected',$categorySelected);
        }
        
        $productList = $SmProduct
                ->field('a.id,a.state,a.audit_state,a.cover_img_url,a.title,a.supplier_id,b.real_name as supplier_name,a.is_recommended')
                ->alias('a')
                ->join('jzdc_index_user b','a.supplier_id=b.id','LEFT')->where($where)->order('id desc')->paginate(20,false,['query'=>request()->param()]);
        foreach ($productList as $key => $val) {
            $productList[$key]['spec_count'] = $SmProductSpec->where(['product_id'=>$val['id']])->count();
            //     $user = $userModel->getInfoById($row->supplier);
             $productList[$key]['cover_img_url'] = $val->cover_img_url ? $SmProduct::getFormatImg($val->cover_img_url) : '';
            //     $row['supplier_name'] = $user ? $user->real_name : '';
        }

        $this->assign('title',$title);
        $this->assign('supplier_id',$supplierId);
        $this->assign('category_id',$categoryId);
        $this->assign('is_recommended',$isRecommended);       
        $this->assign('list',$productList);
        $this->assign('page',$productList->render());
        return view();
	}

	/**
	 * [listDraft 草稿列表]
	 * @return [type] [description]
	 */
	public function listDraft(){
        $title = input('get.title','','trim');
        $supplierId = input('get.supplier_id',0,'intval');
        $isRecommended = input('get.is_recommended',-1,'intval');
        $categoryId = input('get.category_id',0,'intval');

        $SmProductsCategories = new SmProductsCategories();
        $SmProductCategory = new SmProductCategory();
        $SmProduct = new SmProduct();
        $SmProductSpec = new SmProductSpec();


        //是否删除
        $where['a.is_deleted'] = 0;
        
        //已审核
        $where['a.audit_state'] = SmProduct::AUDIT_SAVED;
        
        //是否推荐
        if($isRecommended>-1){
            $where['a.is_recommended']  = $isRecommended;
        }
        //供应商
        if($supplierId>0){
            $where['a.supplier_id']  = $supplierId;
        }
        //商品名称
        if($title!=''){
            $where['a.title']  = ['like','%'.$title.'%'];
        }
        //分类
        if($categoryId>0){
            $productIds = $SmProductsCategories->where(['category_id'=>$categoryId])->column('product_id');
            $where['a.id'] = ['in',$productIds];

            //对分类进行多层级回显
            $categorySelected = $SmProductCategory->getCategorySelected([$categoryId]);
           
            //categorySelected数组中selectedList选中的值、levelSelectList选中值得同级成员
            $this->assign('categorySelected',$categorySelected);
        }
        
        $productList = $SmProduct
                ->field('a.id,a.state,a.audit_state,a.cover_img_url,a.title,a.supplier_id,b.real_name as supplier_name,a.is_recommended')
                ->alias('a')
                ->join('jzdc_index_user b','a.supplier_id=b.id','LEFT')->where($where)->order('id desc')->paginate(20,false,['query'=>request()->param()]);
        foreach ($productList as $key => $val) {
            $productList[$key]['spec_count'] = $SmProductSpec->where(['product_id'=>$val['id']])->count();
            //     $user = $userModel->getInfoById($row->supplier);
             $productList[$key]['cover_img_url'] = $val->cover_img_url ? $SmProduct::getFormatImg($val->cover_img_url) : '';
            //     $row['supplier_name'] = $user ? $user->real_name : '';
        }

        $this->assign('title',$title);
        $this->assign('supplier_id',$supplierId);
        $this->assign('category_id',$categoryId);
        $this->assign('is_recommended',$isRecommended);       
        $this->assign('list',$productList);
        $this->assign('page',$productList->render());
        return view();
	}

	/**
	 * [list 商城商品待审核列表]
	 * @return [type] [description]
	 */
	public function listPending(){
		$title = input('get.title','','trim');
        $supplierId = input('get.supplier_id',0,'intval');
        $isRecommended = input('get.is_recommended',-1,'intval');
        $categoryId = input('get.category_id',0,'intval');

        $SmProductsCategories = new SmProductsCategories();
        $SmProductCategory = new SmProductCategory();
        $SmProduct = new SmProduct();
        $SmProductSpec = new SmProductSpec();

        //是否删除
        $where['a.is_deleted'] = 0;

        //待审核列表不同的角色显示的数据不一样，所以要分角色展示读取对应内容
        switch (getGroupId()) {
            case 2: //管理员
                $where = ['a.audit_state'=>SmProduct::AUDIT_PENDING];
                break;
            case 3: //运营人员
                $where = ['a.audit_state'=>['in',[SmProduct::AUDIT_PENDING,SmProduct::AUDIT_NOTAPPROVED]]];
                break;
            default:
                $where = ['a.audit_state'=>'-1'];
                break;
        }
        
        //是否推荐
        if($isRecommended>-1){
            $where['a.is_recommended']  = $isRecommended;
        }
        //供应商
        if($supplierId>0){
            $where['a.supplier_id']  = $supplierId;
        }
        //商品名称
        if($title!=''){
            $where['a.title']  = ['like','%'.$title.'%'];
        }
        //分类
        if($categoryId>0){
            $productIds = $SmProductsCategories->where(['category_id'=>$categoryId])->column('product_id');
            $where['a.id'] = ['in',$productIds];

            //对分类进行多层级回显
            $categorySelected = $SmProductCategory->getCategorySelected([$categoryId]);
           
           	//categorySelected数组中selectedList选中的值、levelSelectList选中值得同级成员
            $this->assign('categorySelected',$categorySelected);
        }
        
        $productList = $SmProduct
                ->field('a.id,a.audit_state,a.cover_img_url,a.title,a.supplier_id,b.real_name as supplier_name')
                ->alias('a')
                ->join('jzdc_index_user b','a.supplier_id=b.id','LEFT')->where($where)->order('id desc')->paginate(20,false,['query'=>request()->param()]);
        foreach ($productList as $key => $val) {
            $productList[$key]['spec_count'] = $SmProductSpec->where(['product_id'=>$val['id']])->count();
            $productList[$key]['cover_img_url'] = $val->cover_img_url ? $SmProduct::getFormatImg($val->cover_img_url) : '';
            //     $user = $userModel->getInfoById($row->supplier);
            //     $row['cover_img_url'] = $row->cover_img_url ? model('MallGoods')::getFormatImg($row->cover_img_url) : '';
            //     $row['supplier_name'] = $user ? $user->real_name : '';
        }

        $this->assign('title',$title);
        $this->assign('supplier_id',$supplierId);
        $this->assign('category_id',$categoryId);
        $this->assign('is_recommended',$isRecommended);       
        $this->assign('list',$productList);
        $this->assign('page',$productList->render());
        return view();
	}

	/**
     * [getCategoryNextLevelList 通过父级ID获取下一级的分类数据]
     * @param  integer $parentId [父级ID]
     * @return [type]            [array]
     */
    public function getCategoryNextLevelList($parentId = 0){
        $SmProductCategory = new SmProductCategory();
        $list = $SmProductCategory->getCategoryIds($parentId);
        return ['status'=>0,'data'=>['list'=>$list]];
    }

    /**
     * [getCategoryAttr 获取分类对应的规格属性]
     * @param  [type] $categoryId [分类ID]
     * @return [type]             [description]
     */
    public function getCategoryAttr($categoryId){
        $SmCategorySpecAttrKey = new SmCategorySpecAttrKey();
        $list = $SmCategorySpecAttrKey->getCategorySpecAttr($categoryId);
        return ['status'=>0,'data'=>['list'=>$list]];
    }


    /**
     * [getAreaNextLevelList 通过父级ID获取下一级的地区数据]
     * @param  integer $parentId [description]
     * @return [type]            [description]
     */
    public function getAreaNextLevelList($parentId=45067){
        $IndexArea = new IndexArea();
        $list =  $IndexArea->getAreaList($parentId);
        return ['status'=>0,'data'=>['list'=>$list]];

    }
}