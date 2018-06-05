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
use app\common\model\MallSearchLog;
use app\common\model\MallType;
use app\common\model\MallTypeOption;
use app\common\model\MenuMenu;
use think\Request;


class Goods  extends Base {


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
        $pageNumber = $request->post('pageNumber',1);
        $pageSize = $request->post('pageSize',10);


        $start = ($pageNumber - 1)*$pageSize;
        $end = $pageNumber*$pageSize;

        $typeModel = new MallType();
        $typeIdArr = $typeModel->getAllIds();
        $typeIds = $typeIdArr ? implode(',',$typeIdArr) : '';
        $model = new MallGoods();

        $where = [
            'state' => ['<>',0],
            'mall_state' => 1,
            'online_forbid' => 0,
            'share' => 0,
            'type' => ['in',$typeIds]
        ];
        $total = $model->where($where)->count();

        $rows = $model->where($where)->order('id desc, bidding_show desc')->limit($start,$end)->field(['id','icon','title','w_price','min_price','max_price','discount','bidding_show'])->select();
        $list = [];
        foreach ($rows as $row){
            $list[] = [
                'id' => $row->id,
                'title' => $row->title,
                'url' => MallGoods::getFormatImg($row->icon),
                'min_price' => $row->min_price,
                'max_price' => $row->max_price
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
        $productId = $request->post('goodsId',0);
        $userId = $this->userId;
        $user = (new IndexUser())->getInfoById($userId);
        $username = $user ? $user->username :  '';
        $auth = $this->auth();
        if($auth){
            return $auth;
        }

        $model = new MallFavorite();
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
        $productId = $request->post('goodsId',0);
        $userId = $this->userId;
        $auth = $this->auth();
        if($auth){
            return $auth;
        }

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
        $type = $request->post('type',0); //搜索类型
        $keywords = $request->post('keywords',''); //关键字
        $sort = $request->post('sort','asc');
        $pageSize = $request->post('pageSize',10);
        $pageNumber = $request->post('pageNumber',1);
        $categoryId = $request->post('cateId',0);

        $start = ($pageNumber - 1)*$pageSize;
        $end = $pageNumber*$pageSize;
        if($pageSize > 12){ $pageSize = 12;}

        $model = new MallGoods();
        $where = [];
        if(!$keywords){
            $type = 0;
        }
        if($type == 0 ){  //商品搜索
            if($keywords){
                $where['title'] = ['like','%'.$keywords.'%'];
            }
            if($categoryId > 0){
                $where['type'] = $categoryId;
            }
            $total = $model->where($where)->count();
            $rows = $model->where($where)->order('w_price',$sort)->limit($start,$end)->field(['id','icon','title','w_price','min_price','max_price','discount','bidding_show'])->select();
        }else{ //供应商搜索
            echo 'tttt'; exit;
            $total =  $model->alias('a')->join(config('prefix').'index_user b','a.supplier=b.id','left')->where('b.real_name','like','%'.$keywords.'%')->count();
            $rows =  $model->alias('a')->join(config('prefix').'index_user b','a.supplier=b.id','left')->where('b.real_name','like','%'.$keywords.'%')->field(['a.id','a.icon','a.title','a.w_price','a.min_price','a.max_price','a.discount','a.bidding_show'])->select();
        }
        $list = [];
        foreach($rows as $row){
            $list[] = [
                'id' => $row->id,
                'title' => $row->title,
                'url' => MallGoods::getFormatImg($row->icon),
                'min_price' => $row->min_price,
                'max_price' => $row->max_price
            ];
        }
        //更新搜索历史
        if($keywords){
            $searchModel = new MallSearchLog();
            $searchRow = $searchModel->where(['keyword'=>$keywords])->find();
            if($searchRow){
                $searchModel->where(['keyword'=>$keywords])->setInc('sum',1);
            }else{
                $searchModel->save(['sum'=>1,'keyword'=>$keywords,'day'=>0,'week'=>0,'month'=>0,'year'=>0]);
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
        $row = $model->where(['id'=>$id,'state'=>2])->field(['min_price','max_price','state','type','w_price','supplier','icon','multi_angle_img','title','m_detail','option_enable'])->find();
        if(!$row){
            return ['status'=>1,'data'=>[],'msg'=>'商品不存在'];
        }
        $this->noauth();

        //格式化图片 multi_angle_img
        $imgList = [];
        $imgArr = $row->multi_angle_img ? explode('|',$row->multi_angle_img) : [];
        for($i = 0; $i < count($imgArr); $i++){
            $imgList[] = MallGoods::getFormatMultiImg($imgArr[$i]);
        }

        //商家
        $userModel = new IndexUser();
        $user = $userModel->getInfoById($row->supplier);

        //查询规格
        $option = [];
        if($row->option_enable){
            $optionModel = new MallTypeOption();
            $optionRows = $optionModel->where(['type_id'=>$row->type])->order('sequence','desc')->select();
            foreach ($optionRows as $optionRow){
                $option[] = ['id'=>$optionRow->id,'name'=>$optionRow->name];
            }
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
            'price' => $row->w_price, //商品价格
            'supplier' => $user ? $user->real_name : '', //供应商
            'option' => $option, //规格
            'imgList' => $imgList, //视图图片
            'detail' => $row->m_detail,
            'isFavorite' => $isFavorite //是否收藏
        ];

        return ['status'=>0,'data'=>$data,'msg'=>''];
    }

    /**
     * @desc 加入购物车
     * @param Request $request
     * @param $id
     */
    public function addCart(Request $request){
        $id = $request->post('id',0);
        $number = $request->post('number',1);

        //验证登录
        $auth = $this->auth();
        if($auth){
            return $auth;
        }

        //查询是否
        $model = new MallGoods();
        $row = $model->where(['id'=>$id,'state'=>MallGoods::STATE_SALE])->find();
        if(!$row){
            return ['status'=>1,'data'=>[],'msg'=>'商品不存在'];
        }
        //$
        $cartModel = new \app\common\model\MallCart();
        $where = ['user_id'=>$this->userId,'goods_id'=>$id];
        $cartRow = $cartModel->where($where)->find();
        if($cartRow){
            $result = $cartModel->where($where)->setInc('quantity',$number);
        }else{
            $userModel = new IndexUser();
            $user = $userModel->getInfoById($this->userId);
            $data = [
                'user_id' => $this->userId,
                'username' => $user ? $user->username : '',
                'key' => 0,
                'goods_id' => $id,
                'quantity'=>$number,
                'price' => $row->w_price,
                'create_time' => time()
            ];
            $result = $cartModel->save($data);
        }

        if($result !== false){
            return ['status'=>0,'data'=>[],'msg'=>'添加成功'];
        }
        return ['status'=>1,'data'=>[],'msg'=>'添加失败'];
    }

    /**
     * @desc 购物车列表
     * @param Request $request
     */
    public function cartList(Request $request){
        //验证登录
        $auth = $this->auth();
        if($auth){
            return $auth;
        }
        //查询数据
        $model = new \app\common\model\MallCart();
        $rows = $model->alias('a')->join(config('prefix').'mall_goods b','a.goods_id=b.id','left')->where(['user_id'=>$this->userId])->field(['b.id','b.icon','b.title','b.w_price','b.min_price','b.max_price','a.id as cart_id','a.quantity'])->select();

        $data = [];
        foreach ($rows as $row){
            $data[] = [
                'goodsId' => $row->id,
                'cartId' => $row->cart_id,
                'price' => $row->w_price,
                'title' => $row->title,
                'icon' => MallGoods::getFormatImg($row->icon),
                'quantity' => intval($row->quantity)
            ];
        }
        return ['status'=>0,'data'=>$data,'msg'=>''];
    }


}