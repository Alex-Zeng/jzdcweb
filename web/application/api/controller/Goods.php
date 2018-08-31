<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/5/18
 * Time: 14:00
 */

namespace app\api\controller;
use app\common\model\IndexUser;
use app\common\model\MallColor;
use app\common\model\MallFavorite;
use app\common\model\MallGoods;
use app\common\model\MallGoodsSpecifications;
use app\common\model\MallUnit;
use app\common\model\SmProduct;
use app\common\model\SmProductCategory;
use app\common\model\SmProductGallery;
use app\common\model\SmProductSpec;
use app\common\model\SmProductSpecAttrKey;
use app\common\model\SmProductSpecAttrVal;
use app\common\model\UserGoodsSpecifications;
use app\common\model\UserSearchLog;
use app\common\model\MallType;
use app\common\model\MallTypeOption;
use app\common\model\MenuMenu;
use think\Request;
use think\View;


class Goods  extends Base {


    /**
     * @desc 商城首页分类
     * @return array
     */
    public function getCategory(){
        $model = new MenuMenu();
        $rows = $model->where(['parent_id'=>16,'visible'=>1])->order('sequence','desc')->field(['id','name','url','path','type_id','flag'])->select();
        $data = [];
        foreach($rows as $row){
            $data[] = [
                'id' => $row->id,
                'name' => $row->name,
                'url' => $row->url,
                'img' => MenuMenu::getFormatImg($row->path),
                'type' => $row->type_id,
                'flag' => strval($row->flag)
            ];
        }
        return ['status'=>0,'data'=>$data,'msg'=>''];
    }

    /**
     * @desc 获取分类信息
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getCategoryList(Request $request){
        $model = new SmProductCategory();
        $field =  ['id','name'];
        //获取一级分类
        $rows = $model->where(['is_display'=>1,'is_deleted'=>0,'parent_id'=>0])->order('ordering','desc')->field($field)->select();
        foreach ($rows as &$row){
            $row['path'] = '';
            //获取二级分类
            $rows2 = $model->where(['is_display'=>1,'is_deleted'=>0,'parent_id'=>$row->id])->order('ordering','desc')->field($field)->select();
            foreach ($rows2 as &$row2){
                $row2['path'] = '';
                $rows3 = $model->where(['is_display'=>1,'is_deleted'=>0,'parent_id'=>$row2->id])->order('ordering','desc')->field($field)->select();
                foreach ($rows3 as &$row3){
                    $row3['path'] = '';
                }
                $row2['child'] = $rows3;
            }
            $row['child'] = $rows2;
        }
        return ['status'=>0,'data'=>$rows,'msg'=>''];
    }


    /**
     * @desc 返回最新上架商品
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getRecommend(Request $request){
        $pageNumber = $request->post('pageNumber',1,'intval');
        $pageSize = $request->post('pageSize',10,'intval');
        $start = ($pageNumber - 1)*$pageSize;

        $model = new SmProduct();
        $categoryModel = new SmProductCategory();
        $typeIds = $categoryModel->getChildIds(0);
        $where = [
            'a.state' => SmProduct::STATE_FORSALE,
            'a.audit_state' => SmProduct::AUDIT_RELEASED,
            'a.is_deleted' =>0,
            'b.category_id' => ['in',$typeIds]
        ];

        $total = $model->alias('a')->join(['sm_products_categories'=>'b'],'a.id=b.product_id','left')
            ->where($where)->order('a.id desc')
            ->group('a.id')
            ->limit($start,$pageSize)
            ->count();
        $rows = $model->alias('a')->join(['sm_products_categories'=>'b'],'a.id=b.product_id','left')
            ->where($where)->order('a.id desc')
            ->group('a.id')
            ->limit($start,$pageSize)
            ->field(['a.id','a.is_price_neg_at_phone','a.title','a.min_price','a.max_price','a.cover_img_url'])
            ->select();
        $list = [];
        foreach ($rows as $row){
            $list[] = [
                'id' => $row->id,
                'title' => $row->title,
                'url' => SmProduct::getFormatImg($row->cover_img_url),
                'min_price' => getFormatPrice($row->min_price),
                'max_price' => getFormatPrice($row->max_price),
            ];
        }
        return ['status'=>0,'data'=>['total'=>$total,'list'=>$list],'msg'=>''];
    }

    /**
     * @desc 添加商品收藏
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function addFavorite(Request $request){
        $productId = $request->post('goodsId',0,'intval');

        $auth = $this->auth();
        if($auth){
            return $auth;
        }

        $userId = $this->userId;
        $user = (new IndexUser())->getInfoById($userId);
        $username = $user ? $user->username :  '';

        $model = new MallFavorite();

        $result_already = $model->where(['user_id'=>$userId,'goods_id'=>$productId])->find(); //已收藏
        if($result_already){
            return ['status'=>1,'data'=>[],'msg'=>'商品已经收藏过'];
        }

        $goods = model('mall_goods')->field('type')->where(['state'=>2,'id'=>$productId])->find();
        if(!$goods){
            return ['status'=>1,'data'=>[],'msg'=>'商品处于非正常状态不能收藏'];
        }

        $result = $model->save(['user_id'=>$userId,'username'=>$username,'goods_id'=>$productId,'time'=>time(),'type_id'=>$goods['type']]);
        if($result == true){
            return ['status'=>0,'data'=>[],'msg'=>'收藏成功'];
        }
        return ['status'=>1,'data'=>[],'msg'=>'收藏失败'];
    }

    /**
     * @desc 取消商品收藏
     * @param Request $request
     * @return array
     */
    public function removeFavorite(Request $request){
        $productId = $request->post('goodsId',0,'intval');
        $auth = $this->auth();
        if($auth){
            return $auth;
        }

        $userId = $this->userId;
        $model = new MallFavorite();
        $result = $model->where(['user_id'=>$userId,'goods_id'=>$productId])->delete();
        if($result == true){
            return ['status'=>0,'data'=>[],'msg'=>'成功取消收藏'];
        }
        return ['status'=>1,'data'=>[],'msg'=>'失败取消收藏'];
    }

    /**
     * @desc 商品搜索
     * @param Request $request
     * @return array
     */
    public function search3(Request $request){
        $type = $request->post('type',0,'intval'); //搜索类型
        $keywords = $request->post('keywords',''); //关键字
        $sort = $request->post('sort','asc');
        $pageSize = $request->post('pageSize',10,'intval');
        $pageNumber = $request->post('pageNumber',1,'intval');
        $categoryId = $request->post('cateId',0,'intval');
        if($pageSize > 12){ $pageSize = 12;}

        $start = ($pageNumber - 1)*$pageSize;

//        if(!$keywords) {
//            return ['status'=>1,'data'=>[],'msg'=>'搜索关键词不能为空'];
//        }

        $this->noauth();

        $model = new MallGoods();
        $where = [];
        if(!$keywords){
            $type = 0;
        }

        if($type == 0 ){  //商品搜索
            $where['state'] = 2;
            $where['mall_state'] = 1;
            if($keywords){
                $where['title'] = ['like','%'.$keywords.'%'];
            }
            if($categoryId > 0){
                //查询子类包含的ID
                 $typeIds = (new MallType())->getChildIds($categoryId);
                 $typeIds = array_merge([$categoryId],$typeIds);
                 $where['type'] = ['in',$typeIds];
            }
            $total = $model->where($where)->count();

            $rows = $model->where($where)->order('w_price',$sort)->limit($start,$pageSize)->field(['id','icon','title','w_price','min_price','max_price','w_price','discount','bidding_show'])->select();
        }else{ //供应商搜索
            $total =  $model->alias('a')->join(config('prefix').'index_user b','a.supplier=b.id','left')->where(['a.state'=>2,'a.mall_state'=>1])->where('b.real_name','like','%'.$keywords.'%')->count();
            $rows =  $model->alias('a')->join(config('prefix').'index_user b','a.supplier=b.id','left')->where(['a.state'=>2,'a.mall_state'=>1])->where('b.real_name','like','%'.$keywords.'%')->order('a.w_price',$sort)->field(['a.id','a.icon','a.title','a.w_price','a.min_price','a.max_price','a.discount','a.bidding_show'])->select();
        }
        $list = [];

        $goodsIds = $goodsIdArr = [];
        if($this->userId > 0){
            foreach ($rows as $row){
                $goodsIds[] = $row->id;
            }
            //查询
            $favoriteModel = new MallFavorite();
            $favoriteRows = $favoriteModel->where(['user_id'=>$this->userId,'goods_id'=>['in',$goodsIds]])->field(['goods_id'])->select();

            foreach ($favoriteRows as $favoriteRow){
                $goodsIdArr[] = $favoriteRow->goods_id;
            }
        }
        
        foreach($rows as $row){
            $list[] = [
                'id' => $row->id,
                'title' => $row->title,
                'url' => MallGoods::getFormatImg($row->icon),
                'min_price' => getFormatPrice($row->min_price),
                'max_price' => getFormatPrice($row->max_price),
                'w_price' => getFormatPrice($row->w_price),
                'isFavorite' => in_array($row->id,$goodsIdArr) ? 1 : 0
            ];
        }
        //更新搜索历史
        if($keywords && $this->userId){
            $searchModel = new UserSearchLog();
            $searchRow = $searchModel->where(['user_id'=>$this->userId,'keyword'=>$keywords])->find();
            if($searchRow){
                $searchModel->save(['times'=>$searchRow->times+1,'update_time'=>time()],['user_id'=>$this->userId,'keyword'=>$keywords,'type'=>$type]);
            }else{
                $searchModel->save(['user_id'=>$this->userId,'keyword'=>$keywords,'type'=>$type,'times'=>1,'create_time'=>time(),'update_time'=>time()]);
            }
        }


        return ['status'=>0,'data'=>['total'=> $total,'list'=>$list],'msg'=>''];
    }

    /**
     * @desc 商品搜索
     * @param Request $request
     * @return array
     */
    public function search(Request $request){
        $type = $request->post('type',0,'intval'); //搜索类型
        $keywords = $request->post('keywords',''); //关键字
        $sort = $request->post('sort','asc'); //排序
        $pageSize = $request->post('pageSize',10,'intval');
        $pageNumber = $request->post('pageNumber',1,'intval');
        $categoryId = $request->post('cateId',0,'intval');  //分类
        if($pageSize > 12){ $pageSize = 12;}

        $start = ($pageNumber - 1)*$pageSize;

        $this->noauth();

        $model = new SmProduct();
        if(!$keywords){
            $type = 0;
        }

        if($type == 0){  //商品搜索
            $where = [];
            if($keywords){
                $where['a.title'] = ['like','%'.$keywords.'%'];
            }
            if($categoryId > 0){
                //查询子类包含的ID
                $typeIds = (new SmProductCategory())->getChildIds($categoryId);
                $typeIds = array_merge([$categoryId],$typeIds);
                $where['b.category_idtype'] = ['in',$typeIds];
            }

            //查询数据   //['think_work'=>'w']
            $total = $model->alias('a')->join(['sm_products_categories'=> 'b'],'a.id=b.product_id','left')
                ->where(['a.state'=>SmProduct::STATE_FORSALE,'a.audit_state'=>SmProduct::AUDIT_RELEASED,'is_deleted'=>0])
                ->where($where)
                ->group('a.id')
                ->count();

            $rows = $model->alias('a')->join(['sm_products_categories'=> 'b'],'a.id=b.product_id','left')
                ->where(['a.state'=>SmProduct::STATE_FORSALE,'a.audit_state'=>SmProduct::AUDIT_RELEASED,'is_deleted'=>0])
                ->where($where)
                ->group('a.id')
                ->limit($start,$pageSize)
                ->field(['a.id','a.is_price_neg_at_phone','a.title','a.min_price','a.max_price','a.cover_img_url'])
                ->select();
        }else{ //供应商搜索
            $total = $model->alias('a')->join(config('prefix').'index_user b','a.supplier_id=b.id','left')
                ->where(['a.state'=>SmProduct::STATE_FORSALE,'a.audit_state'=>SmProduct::AUDIT_RELEASED,'is_deleted'=>0])
                ->where('b.real_name','like','%'.$keywords.'%')
                ->count();
            $rows = $model->alias('a')->join(config('prefix').'index_user b','a.supplier_id=b.id')
                ->where(['a.state'=>SmProduct::STATE_FORSALE,'a.audit_state'=>SmProduct::AUDIT_RELEASED,'is_deleted'=>0])
                ->where('b.real_name','like','%'.$keywords.'%')
                ->limit($start,$pageSize)
                ->order('a.min_price',$sort)
                ->field(['a.id','a.is_price_neg_at_phone','a.title','a.min_price','a.max_price','a.cover_img_url'])
                ->select();
        }

        //返回数据
        $list = $goodsIds = $goodsIdArr =  [];
        if($this->userId > 0){
            foreach($rows as $row){
                $goodsIds[] = $row->id;
            }
            //查询
            $favoriteModel = new MallFavorite();
            $favoriteRows = $favoriteModel->where(['user_id'=>$this->userId,'goods_id'=>['in',$goodsIds]])->field(['goods_id'])->select();

            foreach ($favoriteRows as $favoriteRow){
                $goodsIdArr[] = $favoriteRow->goods_id;
            }
        }
        //格式化返回数据
        foreach($rows as $row){
            $list[] = [
                'id' => $row->id,
                'title' => $row->title,
                'url' => SmProduct::getFormatImg($row->cover_img_url),
                'isDiscussPrice' => $row->is_price_neg_at_phone,
                'min_price' => getFormatPrice($row->min_price),
                'max_price' => getFormatPrice($row->max_price),
                'isFavorite' => in_array($row->id,$goodsIdArr) ? 1 : 0
            ];
        }
        //更新搜索历史
        if($keywords && $this->userId){
            $searchModel = new UserSearchLog();
            $searchRow = $searchModel->where(['user_id'=>$this->userId,'keyword'=>$keywords])->find();
            if($searchRow){
                $searchModel->save(['times'=>$searchRow->times+1,'update_time'=>time()],['user_id'=>$this->userId,'keyword'=>$keywords,'type'=>$type]);
            }else{
                $searchModel->save(['user_id'=>$this->userId,'keyword'=>$keywords,'type'=>$type,'times'=>1,'create_time'=>time(),'update_time'=>time()]);
            }
        }

        return ['status'=>0,'data'=>['total'=> $total,'list'=>$list],'msg'=>''];
    }

    /**
     * @desc 获取商品信息
     * @param Request $request
     * @param $id
     * @return array
     */

    public function get3(Request $request, $id){
        $model = new MallGoods();
        $row = $model->where(['id'=>$id,'state'=>2])->field(['id','title','min_price','max_price','w_price','state','type','w_price','supplier','icon','multi_angle_img','unit','title','m_detail','option_enable'])->find();
        if(!$row){
            return ['status'=>1,'data'=>[],'msg'=>'商品不存在或已下架'];
        }
        $this->noauth();

        //格式化图片 multi_angle_img
        $imgList = [];
        $imgArr = $row->multi_angle_img ? explode('|',$row->multi_angle_img) : [];
        if($imgArr){  //取多图
            for($i = 0; $i < count($imgArr); $i++){
                $imgList[] =["img"=>MallGoods::getFormatMultiImg($imgArr[$i])];
            }
        }else{  //取封面图
            $imgList[] =["img"=>MallGoods::getFormatImg($row->icon)];
        }


        //商家
        $userModel = new IndexUser();
        $user = $userModel->getInfoById($row->supplier);

        //
        $standards = [];
        $mallTypeModel = new MallType();
        $mallTypeRow = $mallTypeModel->where(['id'=>$row->type])->find();

        $goodsSpecificationsModel = new MallGoodsSpecifications();
        if($mallTypeRow && $mallTypeRow->color == 1){
            $colorRows =  $goodsSpecificationsModel->where(['goods_id'=>$row->id])->field(['color_id','color_name','color_img'])->group('color_id')->select();
            foreach ($colorRows as &$colorRow){
                $colorRow['color_img'] = $colorRow->color_img ? MallGoodsSpecifications::getFormatPath($colorRow->color_img) : ($colorRow->color_id > 0 ? MallColor::getFormatImg($colorRow->color_id) : '');
            }
            $colorList = $colorRows;
            if($colorList){
                $standards[] = [
                    'title' =>'规格',
                    'list' =>  $colorList
                ];
            }
        }
        if($mallTypeRow && $mallTypeRow->diy_option == 1){
            $optionRows =  $goodsSpecificationsModel->alias('a')->join(config('prefix').'mall_type_option b','a.option_id=b.id','left')->where(['a.goods_id'=>$row->id,'a.option_id'=>['gt',0]])->field(['a.option_id','b.name as option_name'])->group('a.option_id')->select();
            $optionName = $mallTypeRow->option_name ? $mallTypeRow->option_name : '二级规格';
            if($optionRows){
                $standards[] = [
                    'title' => $optionName,
                    'list' =>  $optionRows
                ];
            }
        }

        $standardsPriceRows = $goodsSpecificationsModel->where(['goods_id'=>$row->id])->field(['color_id','option_id','w_price'])->select();
        $standardsPrice = [];
        foreach ($standardsPriceRows as $standardsPriceRow){
            $standardsPrice[] = [
                'option_id' => $standardsPriceRow->option_id,
                'color_id' => $standardsPriceRow->color_id,
                'price' => $standardsPriceRow->w_price
            ];
        }

        //是否收藏
        $isFavorite = 0;
        if($this->userId > 0){
            $favoriteModel = new MallFavorite();
            $exist = $favoriteModel->where(['user_id'=>$this->userId,'goods_id'=>$id])->find();
            $isFavorite = $exist ? 1 : 0;
        }

        //更新商品访问量
        $model->where(['id'=>$id])->setInc('visit',1);

        //单位
        $unitModel = new MallUnit();
        $unitRow = $unitModel->find(['id'=>$row->unit]);

        $data = [
            'title' => $row->title,  //商品标题
            'min_price' => getFormatPrice($row->min_price), //商品价格
            'max_price' => getFormatPrice($row->max_price),//
            'price' => getFormatPrice($row->w_price),
            'unit' => $unitRow ? $unitRow->name : '',
            'supplier' => $user ? $user->real_name : '', //供应商
            'supplierLogo' => $user->icon ? IndexUser::getFormatIcon($user->icon) : '', //供应商logo
            'standard' => $standards ? $standards : [], //规格
            'standardPrice' => $standardsPrice,
            'imgList' => $imgList, //视图图片
            'detail' => getImgUrl($row->m_detail),
            'detailUrl' =>config('jzdc_domain').url('api/goods/detail',['id'=>$id]),
            'isFavorite' => $isFavorite //是否收藏
        ];

        return ['status'=>0,'data'=>$data,'msg'=>''];
    }


    public function get(Request $request, $id){
        //获取商品
        $productModel = new SmProduct();
        $condition = ['state'=>SmProduct::STATE_FORSALE,'audit_state'=>SmProduct::AUDIT_RELEASED,'is_deleted'=>0];
        $product = $productModel->where($condition)->find();
        if(!$product){
            return ['status'=>1,'data'=>[],'msg'=>'商品不存在或已下架'];
        }

        $this->noauth();

        //查询多图数据
        $galleryModel = new SmProductGallery();
        $gallerys = $galleryModel->where(['product_id'=>$id,'is_deleted'=>0])->select();
        $imgList = [];
        if($gallerys){  //取多图
            foreach ($gallerys as $gallery){
                $imgList[] = ['img' => SmProductGallery::getFormatImg($gallery->product_image_url)];
            }
        }else{  //取封面图
            $imgList[] =["img"=>SmProduct::getFormatImg($product->cover_img_url)];
        }

        //获取商家
        $userModel = new IndexUser();
        $supplierInfo = $userModel->getInfoById($product->supplier_id);

        //是否收藏
        $isFavorite = 0;
        if($this->userId > 0){
            $favoriteModel = new MallFavorite();
            $exist = $favoriteModel->where(['user_id'=>$this->userId,'goods_id'=>$id])->find();
            $isFavorite = $exist ? 1 : 0;
        }

        $specModel = new SmProductSpec();

        //取规格数据
        $keyModel = new SmProductSpecAttrKey();
        $keyRows = $keyModel->where(['product_id'=>$id,'is_deleted'=>0])->order('ordering desc')->select();

        $specList = [];
        $valModel = new SmProductSpecAttrVal();

        //判断是否有定制
        $isCustomSpec = $specModel->where(['product_id'=>$id,'is_deleted'=>0,'is_customized'=>1])->find();


        foreach($keyRows as $keyIndex => $keyRow){
            //循环获取数据
            $valRows = $valModel->where(['spec_attr_key_id'=>$keyRow->id,'is_deleted'=>0])->select();
            $valList = [];
            foreach ($valRows as  $valRow){
                $valList[] = [
                  "id" => $valRow->id,
                  "name" => $valRow->spec_attr_val,
                  "isCustom" => 0,
                ];
            }

            if($valRows){
                $specList[] = ["desc"=> $keyRow->spec_attr_key,"list"=>$valList,"id"=>$keyRow->id];
            }
        }

        //将定制数据放置在第一个规格组合
        if($specList){
            foreach ($specList as $index => &$item){
                //增加定制选项
                if($index == 0 && $isCustomSpec){
                    $item['list'][] = [
                        "id" => "0",
                        "name" => "定制",
                        "isCustom" => 1,
                    ];
                }
            }
        }else{
            $specList[] = ["desc"=> "定制规格","list"=>[ "id" => "0", "name" => "定制", "isCustom" => 1],"id"=>0];
        }

        //商品规格数据
        $specRows = $specModel->where(['product_id'=>$id,'is_deleted'=>0])->select();
        $specInfo = [];
        foreach($specRows as $specRow){
            //对于定制
            if($specRow->is_customized == 1){
                $specSet = "0";
            }else{   //非定制
                $specSet = explode(',',$specRow->spec_set);
            }
            $specInfo[][] = [
                "setIds" => $specSet,
                "specId" => $specRow->id,
                "sku" => $specRow->sku_code,
                "price" => $specRow->price,
                "unit" => $specRow->unit,
                "pic" => SmProductSpec::getFormatImg($specRow->spec_img_url),
                "num" => $specRow->min_order_qty,
                "isDiscussPrice" => $specRow->is_price_neg_at_phone
            ];
        }

        $icon = $supplierInfo ? $supplierInfo->icon : '';
        //返回结果
        $list = [
            "img" => $imgList,
            "companyName" => $supplierInfo ? $supplierInfo->real_name : '',
            "companyLogo" => IndexUser::getFormatIcon($icon),
            "title" => $product->title,
            "isDiscussPrice" => $product->is_price_neg_at_phone,
            "minPrice" => $product->min_price,
            "maxPrice" => $product->max_price,
            "speclist" => $specList,
            "specInfo" => $specInfo,
            'detail' => getImgUrl($product->html_content_1),  //H5详情
            "webDetail" => getImgUrl($product->html_content_2),//PC详情
            'detailUrl' =>config('jzdc_domain').url('api/goods/detail',['id'=>$id]), //H5 Url
            'isFavorite' => $isFavorite //是否收藏
        ];
        return ['status'=>0,'data'=>$list,'msg'=>''];
    }



    /**
     *
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function standardPrice(Request $request){
        $id = $request->post('id',0,'intval');
        $colorId = $request->post('colorId',0,'intval');
        $optionId = $request->post('optionId',0,'intval');

        $model = new MallGoodsSpecifications();
        $row = $model->where(['color_id'=>$colorId,'option_id'=>$optionId,'goods_id'=>$id])->field(['w_price'])->find();
        if($row && $row->w_price){
            return ['status'=>0,'data'=>['price'=>$row->w_price],'msg'=>''];
        }
        $goodsMoel = new MallGoods();
        $goodsRow = $goodsMoel->where(['id'=>$id])->field(['w_price'])->find();
        if($goodsRow && $goodsRow->w_price){
            return ['status'=>0,'data'=>['price'=>getFormatPrice($goodsRow->w_price)],'msg'=>''];
        }

        return ['status'=>0,'data'=>['price'=>0],'msg'=>''];
    }

    /**
     * @desc 收藏列表
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getFavoriteList(Request $request){
        $pageSize = $request->post('pageSize',10,'intval');
        $pageNumber = $request->post('pageNumber',1,'intval');
        $categoryId = $request->post('cateId',0,'intval');
        if($pageSize > 20){ $pageSize = 20;}

        $auth = $this->auth();
        if($auth){
            return $auth;
        }
        $start = ($pageNumber-1)*$pageSize;
        //
        $model = new MallFavorite();
        $where['a.user_id'] = $this->userId;
        if($categoryId > 0){
            $maps =   $maps = getTypeMap();
            $targetIds = [];
            foreach ($maps as $map){
                if(in_array($categoryId,$map)){
                    $targetIds = $map;
                    break;
                }
            }
            $where['b.category_id'] = ['in',$targetIds];
        }

        //分类
        $categoryModel = new SmProductCategory();
        $typeList = [['count'=>0,'name'=>'全部','typeId'=>0]];
        $parent = $categoryModel->where(['parent_id'=>0,'is_display'=>1,'is_deleted'=>0])->order('ordering desc')->select();
        foreach ($parent as $key => $val){
            $count = $model->where(['user_id'=>$this->userId,'type_id'=>['in',$categoryModel->getChildIds($val['id'],true)]])->count();
            $typeList[] = ['count'=>$count,'name'=>$val['name'],'typeId'=>$val['id']];
            $typeList[0]['count'] += $count;
        }

        //数据获取
        $total = $model->alias('a')
                       ->join(['sm_product'=>'b'],'a.goods_id=b.id','left')
                       ->where($where)
                       ->count();
        $rows = $model->alias('a')
                       ->join(['sm_product' => 'b'],'a.goods_id=b.id','left')
                       ->where($where)
                       ->order('a.time','desc')
                       ->limit($start,$pageSize)
                       ->field(['b.id','b.title','b.cover_img_url','b.min_price','b.max_price','b.is_price_neg_at_phone'])
                       ->select();

        foreach ($rows as &$row){
            $row['icon'] = SmProduct::getFormatImg($row->cover_img_url);
            $row['min_price'] = getFormatPrice($row->min_price);
            $row['max_price'] = getFormatPrice($row->max_price);
            $row['isDiscussPrice'] = $row->is_price_neg_at_phone;
            unset($row->is_price_neg_at_phone);
        }

        return ['status'=>0,'data'=>['total'=>$total,'typeList'=>$typeList,'list'=>$rows],'msg'=>''];
    }

    /**
     * @desc 收藏分类数目
     * @return array|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getFavoriteType(){
        $auth = $this->auth();
        if($auth){
            return $auth;
        }
        $model = new MallFavorite();
        $where['a.user_id'] = $this->userId;

        $rows = $model->alias('a')
                      ->join(['sm_product' =>'b'],'a.goods_id=b.id','left')
                      ->join(['sm_product_category'=>'c'],'b.category_id=c.id','left')
                      ->where($where)
                      ->group('b.category_id')
                      ->field(['COUNT(*) AS count','c.name','c.id'])
                      ->select();
        $maps = getTypeMap();
        $list = [];
        foreach ($rows as $row){
            foreach ($maps as $parentId => $value){
                if(in_array($row->id,$value)){
                    $list[$parentId][] = ['id'=>$row->id,'count'=>$row->count,'parent'=>$parentId];
                    continue;
                }
            }
        }

        $return = [];
        $ids = [];
        foreach ($list as $index => $item){
            $count = 0;
            for($i=0; $i < count($item); $i++){
                $count += $item[$i]['count'];
            }
            if($count > 0){
                $return[] = ['id'=>$item[0]['parent'],'count'=>$count];
                $ids[] = $index;
            }
        }

        if($ids){
            $categoryModel = new SmProductCategory();
            $categoryRows = $categoryModel->where(['id'=>['in',$ids]])->field(['id','name'])->select();
        }
        foreach ($return as &$return_list){
            $return_list['name'] = '';
            foreach ($categoryRows as $typeRow){
                if($return_list['id'] == $typeRow->id){
                    $return_list['name'] = $typeRow->name;
                    continue;
                }
            }
        }

        return ['status'=>0,'data'=>['list'=>$return],'msg'=>''];
    }


    /**
     * @desc 商品详情展示
     * @param $id
     * @throws \think\Exception
     */
    public function detail($id){
        $model = new SmProduct();
        $row = $model->find(['id'=>$id]);

        $view = new View();
        echo $view->fetch('index/goods_detail',['detail'=>$row ? getImgUrl($row->html_content_2) : '']);
    }

    /**
     * @desc 获取商品分类数据
     * @param $id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getPath($id){
        $productModel = new SmProduct();
        $productRow = $productModel->where(['id'=>$id,'state'=>SmProduct::STATE_FORSALE,'audit_state'=>SmProduct::AUDIT_RELEASED,'is_deleted'=>0])->find();
        if(!$productRow){
            return ['status'=>1,'data'=>[],'msg'=>'商品不存在'];
        }

        //查询商品类型
        $model = new SmProductCategory();
        $row = $model->where(['id'=>$productRow->category_id])->field(['id','name','parent_id'])->find();
        $list = [];
        if($row){
            $list[] = ['id'=>$row->id,'name'=>$row->name];
            if($row->parent > 0){
                $row2 = $model->where(['id'=>$row->parent])->field(['id','name','parent_id'])->find();
                $list[] = ['id'=>$row2->id,'name'=>$row2->name];
                if($row2->parent > 0){
                    $row3 = $model->where(['id'=>$row2->parent])->field(['id','name','parent_id'])->find();
                    $list[] =['id'=>$row3->id,'name'=>$row3->name];
                }
            }
        }



        return ['status'=>0,'data'=>$list,'msg'=>''];
    }

    /**
     * [getSupplierNewest 获取供应商最新商品（热门）]
     * @return [list] [查询集合]
     */
    public function getSupplierHot(){
        //id是否合法
        $gid = input('post.id',0,'intval');
        if($gid==0){
            return ['status'=>0,'data'=>[],'msg'=>''];
        }

        //商品是否存在
        $mallGoods = new SmProduct();
        $goods = $mallGoods->field('supplier')->where(['id'=>$gid,'state'=>2])->find();
        if(!$goods){
            return ['status'=>0,'data'=>[],'msg'=>'商品不存在'];
        }

        //获取九个热门
        $dataGoods = $mallGoods->where(['id'=>['<>',$gid],'supplier_id'=>$goods['supplier_id'],'state'=>SmProduct::STATE_FORSALE,'audit_state'=>SmProduct::AUDIT_RELEASED,'is_deleted'=>0])
                               ->order('created_time desc')
                               ->field(['id','cover_img_url'])
                               ->limit(9)
                               ->select();

        foreach ($dataGoods as $k => $v) {
            $dataGoods[$k]['icon'] = SmProduct::getFormatImg($v->cover_img_url);
        }

        return ['status'=>0,'data'=>$dataGoods,'msg'=>''];
    }

    /**
     * @desc 返回规格
     * @return array|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getSpecification(){
        $goodsId = Request::instance()->get('goodsId',0,'intval');
        $colorId = Request::instance()->get('colorId',0,'intval');
        $optionId = Request::instance()->get('optionId',0,'intval');

        //
        $auth = $this->auth();
        if($auth){
            return $auth;
        }

        $goodsSpecificationModel = new MallGoodsSpecifications();
        $row = $goodsSpecificationModel->where(['color_id'=>$colorId,'option_id'=>$optionId,'goods_id'=>$goodsId])->find();
        if(!$row){
            return ['status'=>0,'data'=>['no'=> '','name' => ''],'msg'=>'' ];
        }

        $userGoodsSpecificationModel = new UserGoodsSpecifications();
        $userGoodsRow = $userGoodsSpecificationModel->where(['user_id'=>$this->userId,'goods_id'=>$goodsId,'specifications_id'=>$row->id])->find();
        return ['status'=>0,'data'=>['no'=>$userGoodsRow ? $userGoodsRow->specifications_no : '' ,'name'=>$userGoodsRow ? $userGoodsRow->specifications_name :''],'msg'=>''];
    }
}