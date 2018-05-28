<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/5/18
 * Time: 11:58
 */
namespace app\api\controller;

use think\Request;
use app\common\model\SliderImg;

class Img
{
    /**
     * @desc 获取商城首页banner图片数据
     * @param Request $request
     * @return array
     */
    public function banner(Request $request)
    {
        $model = new SliderImg();
        $rows = $model->where('group_id',27)->field('id,name,url,target')->select();
        $data = [];
        foreach($rows as $row){
            $data[] = [
                'name' => $row->name,
                'id' => $row->id,
                'url' => $row->url,
                'img' => SliderImg::getFormatImg($row->id),
                'target' => $row->target
            ];
        }
        return ['status'=>1,'data'=>$data,'msg'=>''];
    }
}