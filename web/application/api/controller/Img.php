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
        $type = $request->post('type',1,'intval');
        $model = new SliderImg();
        $rows = $model->where(['group_id'=>27,'type'=>$type,'status'=>1])->order('sequence','desc')->field('id,name,url,target,path')->select();
        $data = [];
        foreach($rows as $row){
            $data[] = [
                'title' => $row->name,
                'id' => $row->id,
                'url' => $row->url,
                'img' => SliderImg::getFormatImg($row->path),
                'target' => $row->target
            ];
        }

        //如果为空返回默认图
        if(!$data){
            $path = $type == 1 ? 'pc.png' : 'h5.png';
            $data[] = ['title'=>'','id'=>0,'url'=>'http://http://www.jizhongdiancai.com/','img' => SliderImg::getFormatImg($path),'target'=>'_blank'];
        }
        return ['status'=>0,'data'=>$data,'msg'=>''];
    }
}