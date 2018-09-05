<?php
namespace app\admin\controller;
use app\common\model\SmProduct;

class Product extends Base{

	/**
	 * [create 商城商品添加]
	 * @return [type] [description]
	 */
	public function create(){
		if(request()->isPost()){
			//接收所有提交的数据
			$post = input('post.');

			//公共创建使用的字段值
			$createDefault = model('SmProduct')->filedDefaultValue('create');
			//商品-分类表 		sm_products_categories 		smProductsCategories
			//商品规格组合表 		sm_product_spec 			smProductSpec 
			//商品规格明细表 		sm_product_spec_attr_key 	smProductSpecAttrKey
			//商品规格明细属性表 	sm_product_spec_attr_val 	smProductSpecAttrVal
			//商品规格组合价格表 	sm_product_spec_price 		smProductSpecPrice
			
			foreach ($sm_product_spec as $key => $val) {
				
				$sm_product_spec_filed_price[] = $val['price'];//取所有价格
				$sm_product_spec_filed_phone[] = $val['phone'];//取所有是否电议
			}

			
			//商品表 			sm_product 					SmProduct 	
			$foreach_sp_product_spec  = [
				'is_price_neg_at_phone' => max($sm_product_spec_filed_phone),
				'min_price' => max($sm_product_spec_filed_price),
				'max_price' => mix($sm_product_spec_filed_price)
			];
			$data1 = [
				'category_id' 			=> $post['category_id'][0],
				'is_price_neg_at_phone'	=> $foreach_sp_product_spec['is_price_neg_at_phone'],
				'min_price'				=> $foreach_sp_product_spec['min_price'],
				'max_price'				=> $foreach_sp_product_spec['max_price'],
				'state'					=> 2,
				'audit_state'			=> $post['audit_state'],
				'supplier_id'			=> $post['supplier_id'],
				'title'					=> $post['title'],
				'cover_img_url'			=> $post['cover_img_url'],
				'html_content_1'		=> $post['html_content_1'],
				'html_content_2'		=> $post['html_content_2'],
				'province_of_origin_id' => $post['province_of_origin_id'],
				'city_of_origin_id'		=> $post['city_of_origin_id'],
				'district_of_origin_id' => $post['district_of_origin_id']
			];
			$data1 = array_merge($data1,$createDefault);
			$data1_result = model('SmProduct')->data($data1)->save();
			$data1_id = model('SmProduct')->id;




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
}