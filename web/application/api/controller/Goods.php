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
        $rows = $model->where(['parent_id'=>16,'visible'=>1])->order('sequence','desc')->field(['id','name','url','path'])->select();
        $data = [];
        foreach($rows as $row){
            $data[] = [
                'id' => $row->id,
                'name' => $row->name,
                'url' => $row->url,
                'img' => MenuMenu::getFormatImg($row->path)
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
        //'id':41,'title':'办公室专用打印纸','url':'http://127.0.0.1/program/mall/img_thumb/2018_05/15/1526377338_0_3947.gif','min_price':'0.00','max_price':'0.00'
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
        $productId = $request->post('goodsId',0,'intval');
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
        $productId = $request->post('goodsId',0,'intval');
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
        $type = $request->post('type',0,'intval'); //搜索类型
        $keywords = $request->post('keywords',''); //关键字
        $sort = $request->post('sort','asc');
        $pageSize = $request->post('pageSize',10,'intval');
        $pageNumber = $request->post('pageNumber',1,'intval');
        $categoryId = $request->post('cateId',0,'intval');

        $start = ($pageNumber - 1)*$pageSize;
        $end = $pageNumber*$pageSize;
        if($pageSize > 12){ $pageSize = 12;}

        $this->noauth();

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
            $total =  $model->alias('a')->join(config('prefix').'index_user b','a.supplier=b.id','left')->where('b.real_name','like','%'.$keywords.'%')->count();
            $rows =  $model->alias('a')->join(config('prefix').'index_user b','a.supplier=b.id','left')->where('b.real_name','like','%'.$keywords.'%')->order('a.w_price',$sort)->field(['a.id','a.icon','a.title','a.w_price','a.min_price','a.max_price','a.discount','a.bidding_show'])->select();
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
        if($keywords && $this->userId){
             $this->userId  = 0;
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
        $row = $model->where(['id'=>$id,'state'=>2])->field(['min_price','max_price','state','type','w_price','supplier','icon','multi_angle_img','title','m_detail','option_enable'])->find();
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
            'supplierLogo' => '', //供应商logo
            'option' => $option, //规格
            'imgList' => $imgList, //视图图片
            'detail' => $row->m_detail,
            'isFavorite' => $isFavorite //是否收藏
        ];

        return ['status'=>0,'data'=>$data,'msg'=>''];
    }

}