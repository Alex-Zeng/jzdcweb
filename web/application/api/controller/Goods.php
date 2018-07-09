<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/5/18
 * Time: 14:00
 */

namespace app\api\controller;
use app\common\model\IndexUser;
use app\common\model\MallFavorite;
use app\common\model\MallGoods;
use app\common\model\MallGoodsSpecifications;
use app\common\model\UserSearchLog;
use app\common\model\MallType;
use app\common\model\MallTypeOption;
use app\common\model\MenuMenu;
use think\Request;


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
                'flag' => $row->flag
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
        $model = new MallType();
        $field =  ['id','name','path'];
        //获取一级分类
        $rows = $model->where(['visible'=>1,'parent'=>0])->order('sequence','desc')->field($field)->select();
        foreach ($rows as &$row){
            $row['path'] = MallType::getFormatIcon($row->path);
            //获取二级分类
            $rows2 = $model->where(['visible'=>1,'parent'=>$row->id])->order('sequence','desc')->field($field)->select();
            foreach ($rows2 as &$row2){
                $row2['path'] = MallType::getFormatIcon($row2->path);
                $rows3 = $model->where(['visible'=>1,'parent'=>$row2->id])->order('sequence','desc')->field($field)->select();
                foreach ($rows3 as &$row3){
                    $row3['path'] = MallType::getFormatIcon($row3->path);
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

        $typeModel = new MallType();
        $typeIdArr = $typeModel->getAllIds();
        $typeIds = $typeIdArr ? implode(',',$typeIdArr) : '';
        $model = new MallGoods();

        $where = [
            'state' => 2,
            'mall_state' => 1,
            'online_forbid' => 0,
            'share' => 0,
            'type' => ['in',$typeIds]
        ];
        $total = $model->where($where)->count();

        $rows = $model->where($where)->order('id desc, bidding_show desc')->limit($start,$pageSize)->field(['id','icon','title','w_price','min_price','max_price','w_price','discount','bidding_show'])->select();
        $list = [];
        foreach ($rows as $row){
            $list[] = [
                'id' => $row->id,
                'title' => $row->title,
                'url' => MallGoods::getFormatImg($row->icon),
                'min_price' => getFormatPrice($row->min_price),
                'max_price' => getFormatPrice($row->max_price),
                'w_price' => getFormatPrice($row->w_price)
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

        $result = $model->save(['user_id'=>$userId,'username'=>$username,'goods_id'=>$productId,'time'=>time()]);
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
    public function search(Request $request){
        $type = $request->post('type',0,'intval'); //搜索类型
        $keywords = $request->post('keywords',''); //关键字
        $sort = $request->post('sort','asc');
        $pageSize = $request->post('pageSize',10,'intval');
        $pageNumber = $request->post('pageNumber',1,'intval');
        $categoryId = $request->post('cateId',0,'intval');
        if($pageSize > 12){ $pageSize = 12;}

        $start = ($pageNumber - 1)*$pageSize;
        $end = $pageNumber*$pageSize;


        $this->noauth();

        $model = new MallGoods();
        $where = [];
        if(!$keywords){
            $type = 0;
        }

        if($type == 0 ){  //商品搜索
            $where['state'] = 2;
            if($keywords){
                $where['title'] = ['like','%'.$keywords.'%'];
            }
            if($categoryId > 0){
                $where['type'] = $categoryId;
            }
            $total = $model->where($where)->count();

            $rows = $model->where($where)->order('w_price',$sort)->limit($start,$end)->field(['id','icon','title','w_price','min_price','max_price','w_price','discount','bidding_show'])->select();
        }else{ //供应商搜索
            $total =  $model->alias('a')->join(config('prefix').'index_user b','a.supplier=b.id','left')->where(['a.state'=>2,'a.mall_state'=>1])->where('b.real_name','like','%'.$keywords.'%')->count();
            $rows =  $model->alias('a')->join(config('prefix').'index_user b','a.supplier=b.id','left')->where(['a.state'=>2,'a.mall_state'=>1])->where('b.real_name','like','%'.$keywords.'%')->order('a.w_price',$sort)->field(['a.id','a.icon','a.title','a.w_price','a.min_price','a.max_price','a.discount','a.bidding_show'])->select();
        }
        $list = [];
        foreach($rows as $row){
            $list[] = [
                'id' => $row->id,
                'title' => $row->title,
                'url' => MallGoods::getFormatImg($row->icon),
                'min_price' => getFormatPrice($row->min_price),
                'max_price' => getFormatPrice($row->max_price),
                'w_price' => getFormatPrice($row->w_price)
            ];
        }
        //更新搜索历史
        if($keywords && $this->userId){
            $searchModel = new UserSearchLog();
            $searchRow = $searchModel->where(['user_id'=>$this->userId,'keyword'=>$keywords,'type'=>$type])->find();
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
    public function get(Request $request, $id){
        $model = new MallGoods();
        $row = $model->where(['id'=>$id,'state'=>2])->field(['id','title','min_price','max_price','w_price','state','type','w_price','supplier','icon','multi_angle_img','title','m_detail','option_enable'])->find();
        if(!$row){
            return ['status'=>1,'data'=>[],'msg'=>'商品不存在'];
        }
        $this->noauth();

        //格式化图片 multi_angle_img
        $imgList = [];
        $imgArr = $row->multi_angle_img ? explode('|',$row->multi_angle_img) : [];
        for($i = 0; $i < count($imgArr); $i++){
            $imgList[] =["img"=>MallGoods::getFormatMultiImg($imgArr[$i])];
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
            $colorRows =  $goodsSpecificationsModel->where(['goods_id'=>$row->id])->field(['color_id','color_name'])->group('color_id')->select();
            $colorList = $colorRows;
            if($colorList){
                $standards[] = [
                    'title' =>'规格',
                    'list' =>  $colorList
                ];
            }
        }
        if($mallTypeRow && $mallTypeRow->diy_option == 1){
            $optionRows =  $goodsSpecificationsModel->alias('a')->join(config('prefix').'mall_type_option b','a.option_id=b.id','left')->where(['a.goods_id'=>$row->id])->field(['a.option_id','b.name as option_name'])->group('a.option_id')->select();
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

        $data = [
            'title' => $row->title,  //商品标题
            'min_price' => getFormatPrice($row->min_price), //商品价格
            'max_price' => getFormatPrice($row->max_price),//
            'price' => getFormatPrice($row->w_price),
            'supplier' => $user ? $user->real_name : '', //供应商
            'supplierLogo' => $user->icon ? IndexUser::getFormatIcon($user->icon) : '', //供应商logo
            'standard' => $standards ? $standards : [], //规格
            'standardPrice' => $standardsPrice,
            'imgList' => $imgList, //视图图片
            'detail' => getImgUrl($row->m_detail),
            'isFavorite' => $isFavorite //是否收藏
        ];

        return ['status'=>0,'data'=>$data,'msg'=>''];
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
            $where['b.type'] = ['in',$targetIds];
        }
        $total = $model->alias('a')->join(config('prefix').'mall_goods b','a.goods_id=b.id','left')->where($where)->count();
        $rows = $model->alias('a')->join(config('prefix').'mall_goods b','a.goods_id=b.id','left')->where($where)->order('a.time','desc')->limit($start,$pageSize)->field(['b.id','b.title','b.icon','b.min_price','b.max_price'])->select();

        foreach ($rows as &$row){
            $row['icon'] = MallGoods::getFormatImg($row->icon);
            $row['min_price'] = getFormatPrice($row->min_price);
            $row['max_price'] = getFormatPrice($row->max_price);
        }

        return ['status'=>0,'data'=>['total'=>$total,'list'=>$rows],'msg'=>''];
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

        $rows = $model->alias('a')->join(config('prefix').'mall_goods b','a.goods_id=b.id','left')
            ->join(config('prefix').'mall_type c','b.type=c.id')
            ->where($where)->group('b.type')->field(['COUNT(*) AS count','c.name','c.id'])->select();

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
            $typeModel = new MallType();
            $typeRows = $typeModel->where(['id'=>['in',$ids]])->field(['id','name'])->select();
        }
        foreach ($return as &$return_list){
            $return_list['name'] = '';
            foreach ($typeRows as $typeRow){
                if($return_list['id'] == $typeRow->id){
                    $return_list['name'] = $typeRow->name;
                    continue;
                }
            }
        }

        return ['status'=>0,'data'=>['list'=>$return],'msg'=>''];
    }

}