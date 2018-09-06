<?php
namespace app\admin\controller;
use app\common\model\SmProduct;
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
            // dump($post['spec']);exit();
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
                }
                //是否未有规格组合但未任何选择、是否有组合规格是一模一样的
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
            }
            if(trim($post['cover_img_url'])==''){
                return $this->errorMsg('101004');
            }

            $post['audit_state'] = $post['audit_state']+0;
            if($post['audit_state']!=1 &&  $post['audit_state']!=2){
                return  $this->errorMsg('101008');
            }

            // dump($post);exit();
            Db::startTrans();
            $dbCommit = 0;
			//公共创建使用的字段值
			$createDefault = model('SmProduct')->filedDefaultValue('create');
			
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
                
                $data1_result = model('SmProduct')->data($data1)->save();
                $data1_id = model('SmProduct')->id;
                if(!$data1_result){
                    $dbCommit = 1; 
                }


            //ok商品多图          sm_product_gallery     SmProductGallery
            if( $post['multi_img_url']!=''){
                $data2 = [];
                $data2_result = [];
                $multi_img_url = $post['multi_img_url'];
                $multi_img_url = explode('|', $multi_img_url);dump( $post);
                if(count($multi_img_url)>0){
                    foreach ($multi_img_url as $key => $val) {
                        if($val!=''){
                            $data2[] = array_merge(['product_id'=>$data1_id,'product_image_url'=>$val],$createDefault);
                        }
                    }
                    dump($data2);exit();
                    $data2_result = model('SmProductGallery')->saveAll($data2);
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
                            $data3[] = ['product_id'=>$data1_id,'category_id'=>$val];
                        }
                    }
                    // dump($data3);
                    $data3_result = model('SmProductsCategories')->saveAll($data3);
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
                            'product_id'    =>  $data1_id,
                            'sku_code'      =>  '',
                            'spec_set'      =>  '',
                            'spec_img_url'  =>  $post['spec']['spec_img_url'][$i],
                            'price'         =>  $post['spec']['price'][$i],
                            'unit'          =>  $post['spec']['unit'][$i]==-1?$post['spec']['unit_text'][$i]:$post['spec']['unit_text'][$i],
                            'min_order_qty' =>  $post['spec']['min_order_qty'][$i],
                            'is_customized' =>  0,
                            'is_price_neg_at_phone'=>intval($post['spec']['is_price_neg_at_phone'][$i])
                        ]);
                }
                if(!empty($data4)){
                    //dump($data4);
                    $data4_result = model('SmProductSpec')->saveAll($data4);
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
                            'product_id'                =>$data1_id,
                            'category_spec_attr_key_id' =>$category_spec_attr_key_val[0],
                            'spec_attr_key'             =>$category_spec_attr_key_val[1]
                        ]);
                }
                if(!empty($data5)){
                    // dump($data5);
                    $data5_result = model('SmProductSpecAttrKey')->saveAll($data5);
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
                    $data6_result = model('smProductSpecAttrVal')->saveAll($data6);
                    //dump($data6_result);
                }
                foreach ($data6_result as $key => $val) { //8_颜色|红色 => id
                    $data6_plan_param2_data9[$data6_plan_param1[$key]] = $val['id'];
                }
                if(!$data6_result){
                    $dbCommit = 6; 
                }
                // dump($data6_plan_param2_data9);
                 

            //商品规格组合价格表     sm_product_spec_price       smProductSpecPrice未完成
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
                    }
                }
                if(!empty($data7)){
                    // dump($data7);
                    $data7_result = model('sm_product_spec_price')->saveAll($data7);
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
                    $data_result9 = model('SmProductSpec')->where(['id'=>$val['id']])->update(['sku_code'=>getSku($val['id']),'spec_set'=>implode(',', $data9_plan_param1[$key])]);
                    // dump(['sku_code'=>getSku($val['id']),'spec_set'=>implode(',', $data9_plan_param1[$key])]);
                    if(!$data_result9){
                        $dbCommit = 8; 
                    }
                }
            }

            //商品SPU更新
                $data_result10 = model('SmProduct')->where(['id'=>$data1_id])->update(['spu_code'=>getSpu($data1_id)]);
                if(!$data_result10){
                    $dbCommit = 9; 
                }
            
            //如果开启定制
            if(isset($post['custom_switch'])){
                $data11[] = array_merge($createDefault,[
                            'product_id'    =>  $data1_id,
                            'sku_code'      =>  '',
                            'spec_set'      =>  '',
                            'spec_img_url'  =>  '',
                            'price'         =>  $post['custom_price'],
                            'unit'          =>  $post['custom_unit'],
                            'min_order_qty' =>  1,
                            'is_customized' =>  1,
                            'is_price_neg_at_phone'=>0
                        ]);
                $data11_result = model('SmProductSpec')->saveAll($data11);
                if($data11_result){
                    $data11_result_2 = model('SmProductSpec')->where(['id'=>$data11_result[0]['id']])->update(['sku_code'=>getSku($data11_result[0]['id'])]);
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
                $data12_result =  model('sm_product_spec_price')->data($data12)->save();
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
        $unitRows = model('MallUnit')->where([])->order('sequence','desc')->field(['id','name'])->select();
        return view('',['unitRows'=>$unitRows]);
	}



	/**
	 * [edit 商城商品修改]
	 * @return [type] [description]
	 */
	public function edit(){
        //单位
        $unitRows = model('MallUnit')->where([])->order('sequence','desc')->field(['id','name'])->select();
        return view('',['unitRows'=>$unitRows]);
	}

	/**
	 * [view 商城商品查看]
	 * @return [type] [description]
	 */
	public function view(){
		//获取商品ID
		$productId = input('param.id',0,'intval');

        $where['a.id'] = $productId;
        $product = model('SmProduct')
                ->field('a.id,a.spu_code,a.cover_img_url,a.html_content_1,a.html_content_2,a.province_of_origin_id,a.city_of_origin_id,a.district_of_origin_id,a.title,b.nickname as supplier_name')
                ->alias('a')
                ->join('jzdc_index_user b','a.supplier_id=b.id','LEFT')->where($where)->find();
        $product['cover_img_url'] = model('SmProduct')->getFormatImg($product['cover_img_url']);

        $rows = [];
        $multiImg = [];
        $categorySelected = [];
        $productArea = [];
        if(!empty($product)){
            //规格
            $rows = model('SmProductSpec')->field('id,sku_code,spec_set,price,unit,is_customized,is_price_neg_at_phone,min_order_qty')->where(['product_id'=>$productId])->select();
            foreach ($rows as $key => $val) {
            	$rows[$key]['attr'] = model('SmProductSpecAttrVal')->field('spec_attr_val')->where(['id'=>['in',$val['spec_set']]])->select();
            }

            //多视角图片
            $multiImg = model('SmProductGallery')->field('product_image_url')->where(['product_id'=>$productId])->select();
            foreach ($multiImg as $key => $val) {
            	$multiImg[$key]['product_image_url'] = model('SmProduct')->getFormatMultiImg($val['product_image_url']);
            }

            //对分类进行多层级回显
            ////通过商品ID找出其关联的所有分类
            $allCategrody = model('SmProductsCategories')->where(['product_id'=>$productId])->column('category_id'); 
            $categorySelected = model('SmProductCategory')->getCategorySelected($allCategrody);

            //产地省市区
            $area = [];
            $area[] = $product['province_of_origin_id'];
            $area[] = $product['city_of_origin_id'];
            $area[] = $product['district_of_origin_id'];
            $productArea = model('IndexArea')->where(['id'=>['in',$area]])->column('id,name');
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
        $SmProduct = model('SmProduct');
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
				$update = array_merge($SmProduct->filedDefaultValue('update'),['audit_state'=>$auditState]);
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
            $productIds = model('SmProductsCategories')->where(['category_id'=>$categoryId])->column('product_id');
            $where['a.id'] = ['in',$productIds];

            //对分类进行多层级回显
            $categorySelected = model('SmProductCategory')->getCategorySelected($categoryId);
           
           	//categorySelected数组中selectedList选中的值、levelSelectList选中值得同级成员
            $this->assign('categorySelected',$categorySelected);
        }
        
        $productList = model('SmProduct')
                ->field('a.id,a.state,a.audit_state,a.cover_img_url,a.title,a.supplier_id,b.nickname as supplier_name,a.is_recommended')
                ->alias('a')
                ->join('jzdc_index_user b','a.supplier_id=b.id','LEFT')->where($where)->paginate(20,false,['query'=>request()->param()]);
        $model_sm_product_spec = model('SmProductSpec');
        foreach ($productList as $key => $val) {
            $productList[$key]['spec_count'] = $model_sm_product_spec->where(['product_id'=>$val['id']])->count();
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
	 * [listDraft 草稿列表]
	 * @return [type] [description]
	 */
	public function listDraft(){

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
            $productIds = model('SmProductsCategories')->where(['category_id'=>$categoryId])->column('product_id');
            $where['a.id'] = ['in',$productIds];

            //对分类进行多层级回显
            $categorySelected = model('SmProductCategory')->getCategorySelected($categoryId);
           
           	//categorySelected数组中selectedList选中的值、levelSelectList选中值得同级成员
            $this->assign('categorySelected',$categorySelected);
        }
        
        $productList = model('SmProduct')
                ->field('a.id,a.audit_state,a.cover_img_url,a.title,a.supplier_id,b.nickname as supplier_name')
                ->alias('a')
                ->join('jzdc_index_user b','a.supplier_id=b.id','LEFT')->where($where)->paginate(20,false,['query'=>request()->param()]);
        $model_sm_product_spec = model('SmProductSpec');
        foreach ($productList as $key => $val) {
            $productList[$key]['spec_count'] = $model_sm_product_spec->where(['product_id'=>$val['id']])->count();
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
        $list = model('SmProductCategory')->getCategoryIds($parentId);
        return ['status'=>0,'data'=>['list'=>$list]];
    }

    /**
     * [getCategoryAttr 获取分类对应的规格属性]
     * @param  [type] $categoryId [分类ID]
     * @return [type]             [description]
     */
    public function getCategoryAttr($categoryId){
        $list = model('SmCategorySpecAttrKey')->getCategorySpecAttr($categoryId);
        return ['status'=>0,'data'=>['list'=>$list]];
    }


    /**
     * [getAreaNextLevelList 通过父级ID获取下一级的地区数据]
     * @param  integer $parentId [description]
     * @return [type]            [description]
     */
    public function getAreaNextLevelList($parentId=45067){
        $list =  model("IndexArea")->getAreaList($parentId);
        return ['status'=>0,'data'=>['list'=>$list]];

    }
}