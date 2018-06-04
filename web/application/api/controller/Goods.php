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
use app\common\model\MallType;
use app\common\model\MenuMenu;
use think\Request;


class Goods  extends Base {

    /**
     * @desc 商城首页分类
     * @return array
     */
    public function getCategory(){
        $model = new MenuMenu();
        $rows = $model->where(['parent_id'=>16,'visible'=>1])->order('sequence','desc')->field('id,name,url')->select();
        $data = [];
        foreach($rows as $row){
            $data[] = [
                'id' => $row->id,
                'name' => $row->name,
                'url' => $row->url,
                'img' => getFormatImg($row->id)
            ];
        }
        return ['status'=>0,'data'=>$data,'msg'=>''];
    }


    /**
     * @desc 返回最新上架商品
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getRecommend(Request $request){
        //
        $pageNumber = $request->post('pageNumber',1);
        $pageSize = $request->post('pageSize',4);

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

}