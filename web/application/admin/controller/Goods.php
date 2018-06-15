<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/6/1
 * Time: 14:00
 */
namespace app\admin\controller;

use app\common\model\IndexUser;
use app\common\model\MallGoods;
use app\common\model\MallType;
use app\common\model\MallUnit;
use think\Request;

class Goods extends Base{

    /**
     * @desc 商品列表
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function index(){
        $model = new MallGoods();

        $k = Request::instance()->get('k','');
        $supplier = Request::instance()->get('supplier',0);

        if(isset($k) && $k){
            $model->where('title','like','%'.$k.'%');
        }
        if($supplier > 0){
            $model->where(['supplier'=>$supplier]);
        }
        $rows = $model->order(['id'=>'desc'])->paginate(20,false,['query'=>request()->param()]);
        $userModel = new IndexUser();
        foreach ($rows as &$row){
            $user = $userModel->getInfoById($row->supplier);
            $row['icon_path'] = MallGoods::getFormatImg($row->icon);
            $row['supplier_name'] = $user ? $user->real_name : '';
        }

        $this->assign('k',$k);
        $this->assign('list',$rows);
        $this->assign('page',$rows->render());
        $this->assign('supplier',$supplier);
        return $this->fetch();
    }

    /**
     * @desc 新增商品
     * @param Request $request
     * @return mixed
     */
    public function create(Request $request){
        if($request->isPost()){


        }

        return $this->fetch();
    }

    /**
     * @desc 修改商品
     * @param Request $request
     * @param $id
     * @return mixed
     */
    public function edit(Request $request,$id){

        if($request->isPost()){


        }

        return $this->fetch('template/create');
    }


    /**
     * @desc 删除商品
     * @param Request $request
     * @param $id
     * @return array
     */
    public function delete(Request $request, $id){
        //检查删除条件
        $model = new MallGoods();
        $result = $model->save(['is_delete'=>1],['id'=>$id]);
        if($result == true){
            return ['status'=>0,'data'=>[],'msg'=>'删除成功'];
        }
        return ['status'=>1,'data'=>[],'msg'=>'删除失败'];
    }


    /**
     * @desc 商品详情
     * @param Request $request
     * @param $id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function view(Request $request, $id){
        $model = new MallGoods();
        $row = $model->where(['id'=>$id])->find();

        $row['icon'] = MallGoods::getFormatImg($row->icon);
        $multiImgArr = $row->multi_angle_img ? explode('|',$row->multi_angle_img) : [];
        $multiImgs = [];
        foreach ($multiImgArr as $path){
            $multiImgs[] = MallGoods::getFormatMultiImg($path);
        }
        $row['multiImg'] = $multiImgs;

        //
        $userModel = new IndexUser();
        $unitModel = new MallUnit();
        $userInfo = $userModel->getInfoById($row->supplier);
        $row['supplierName'] = $userInfo ? $userInfo->real_name : '';

        $unitInfo = $unitModel->where(['id'=>$row->unit])->find();
        $row['unitName'] = $unitInfo ? $unitInfo->name : '';

        //
        $goodsTypeArr = array_reverse(getTypeLevel($row->type));
        $row['goodsTypeName'] = implode('&nbsp;>',$goodsTypeArr);
        $this->assign('goods',$row);
        return $this->fetch();
    }


    public function getType($typeId = 0){
        $model = new MallType();
        $row = $model->where(['id'=>$typeId])->find();
        return ['status'=>0,'data'=>['color'=>$row->color,'option'=>$row->diy_option],'msg'=>''];
    }


}
