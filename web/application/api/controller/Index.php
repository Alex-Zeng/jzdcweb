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
        $model = new IndexArea();
        $list = [];
        //获取省级
        $rows = $model->getProvinceList();
        foreach ($rows as $row){
            $list[] = ['name'=>$row->name,'value'=>trim($row->id)];
            $cityRows = $model->getCityListByProvince($row->id);
            foreach($cityRows as $cityRow){
                $list[] = ['name'=>$cityRow->name,'value'=>trim($cityRow->id),'parent'=>trim($cityRow->upid)];
                $countyRows = $model->getCountyListByCity($cityRow->id);
                foreach ($countyRows as $countyRow){
                    $list[] = ['name'=>$countyRow->name,'value'=>trim($countyRow->id),'parent'=>trim($countyRow->upid)];
//                    $townRows = $model->getTownListByCounty($countyRow->id);
//                    foreach ($townRows as $townRow){
//                        $list[] = ['name'=>$townRow->name,'value'=>trim($townRow->id),'parent'=>trim($townRow->upid)];
//                    }
                }
            }

        }
        return ['status'=>0,'data'=>['list'=>$list],'msg'=>''];
    }

    /**
     * @desc 返回层级区域数据
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getLevelArea(Request $request){
        $model = new IndexArea();
        $provinceId = $request->post('provinceId', 0, 'intval');
        $cityId = $request->post('cityId', 0, 'intval');

        if($provinceId === 0 && $cityId === 0) {
            $list =  $model->getProvinceList();
            return ['status' => 0, 'data' => ['list' => $list], 'msg' => '返回成功'];
        }

        if ($cityId === 0) {
            $list = $model->getCityListByProvince($provinceId);
            return ['status' => 0, 'data' => ['list' => $list], 'msg' => '返回成功'];
        }else{
            $list = $model->getCountyListByCity($cityId);
            return ['status' => 0, 'data' => ['list' => $list], 'msg' => '返回成功'];
        }
    }

    /**
     * [turnover 成交额]
     * @return [json] [格式化后的本月成交额&累计成交额]
     */
    public function turnover(){
        $turnoverMonth = number_format(model('MallOrder')->getTurnover('month'),2,',',' ');//本月成交额
        $turnoverAll = number_format(model('MallOrder')->getTurnover('all'),2,',',' ');//累计成交额
        return ['status'=>0,'data'=>['turnoverMonth'=>$turnoverMonth,'turnoverAll'=>$turnoverAll],'msg'=>'返回成功'];
    }

}
