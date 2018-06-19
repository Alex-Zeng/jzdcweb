<?php
namespace app\api\controller;

use app\common\model\IndexArea;
use think\Request;

class Index extends Base
{
    public function index(Request $request)
    {

//        $auth = $this->auth();
//        print_r($auth);

    }

    /**
     * @desc 返回区域数据
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getArea(Request $request){
        $id = $request->get('id',0);
        $type = $request->get('type',0,'intval');
        $model = new IndexArea();

        switch ($type){
            case 0: //获取省份
                $rows = $model->getProvinceList();
                break;
            case 1: //获取市
                $rows = $model->getCityListByProvince($id);
                break;
            case 2: //获取区县
                $rows = $model->getCountyListByCity($id);
                break;
            case 3: //获取镇
                $rows = $model->getTownListByCounty($id);
                break;
            default:
                $rows = $model->getProvinceList();
        }
        return ['status'=>0,'data'=>['list'=>$rows],'msg'=>''];
    }
}
