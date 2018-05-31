<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/5/18
 * Time: 14:00
 */

namespace app\api\controller;
use app\common\model\MallGoods;
use app\common\model\MallType;
use app\common\model\MenuMenu;


class Goods {

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
        return ['status'=>1,'data'=>$data,'msg'=>''];
    }


    /**
     * @desc 返回最新上架商品
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getRecommend(){
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
        $rows = $model->where($where)->order('id desc, bidding_show desc')->limit(0,4)->field(['id','icon','title','w_price','min_price','max_price','discount','bidding_show'])->select();
        $data = [];
        foreach ($rows as $row){
            $data[] = [
                'id' => $row->id,
                'title' => $row->title,
                'url' => MallGoods::getFormatImg($row->icon),
                'min_price' => $row->min_price,
                'max_price' => $row->max_price
            ];
        }
        return ['status'=>1,'data'=>$data,'msg'=>''];
    }

}