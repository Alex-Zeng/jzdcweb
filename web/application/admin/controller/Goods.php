<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/6/1
 * Time: 14:00
 */
namespace app\admin\controller;

use app\common\model\IndexUser;
use app\common\model\MallColor;
use app\common\model\MallGoods;
use app\common\model\MallGoodsSpecifications;
use app\common\model\MallOrderGoods;
use app\common\model\MallType;
use app\common\model\MallTypeOption;
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
        $state = Request::instance()->get('state','-1','intval');
        if(isset($k) && $k){
            $model->where('title','like','%'.$k.'%');
        }
        if($supplier > 0){
            $model->where(['supplier'=>$supplier]);
        }
        if($state >= 0){
            $model->where(['mall_state'=>$state]);
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
        $this->assign('state',$state);
        return $this->fetch();
    }

    /**
     * @desc 新增商品
     * @param Request $request
     * @return mixed
     */
    public function create(Request $request){
        if($request->isPost()){
            $title = $request->post('title','');
            $type = $request->post('type',0,'intval');
            $supplier = $request->post('supplier',0,'intval');
            $unit = $request->post('unit',0,'intval');
            $cover_path = $request->post('cover_path','');
            $multi_path = $request->post('multi_path','');
            $color = $request->post('color/a');

            $standard = $request->post('standard/a');
            $cost_price = $request->post('cost_price',0);
            $w_price = $request->post('w_price',0);
            $e_price = $request->post('e_price',0);

            $content = $request->post('content','');
            $state = $request->post('state',0,'intval');
            $standardArr =  [];

            //计算min_price,max_price
             $priceArr = [];
             if($standard){
                 foreach ($standard as $arr){
                     $priceArr[] = $arr['w_price'];
                 }
             }

            //添加商品
            $goodsModel = new MallGoods();
            $goods = [
                'shop_id' =>1,
                'min_price' => $priceArr ? min($priceArr):$w_price,
                'max_price' => $priceArr ? max($priceArr):$w_price,
                'state' => $state,
                'type' =>$type,
                'unit' =>$unit,
                'w_price' =>$w_price,
                'e_price' => $e_price,
                'cost_price' => $cost_price,
                'supplier' => $supplier,
                'title' => $title,
                'icon' => $cover_path,
                'multi_angle_img' => $multi_path,
                'detail' => $content,
                'm_detail' => $content,
                'limit_cycle' => '',
                'time' => time()
            ];

            $result = $goodsModel->save($goods);
            if($result == true){
                for($i =0; $i < count($standard); $i++){
                    $colorId = $standard[$i]['color_id'];
                    $standardArr[] = [
                        'color_id' => $standard[$i]['color_id'],
                        'color_name' => isset($color[$colorId]) ? $color[$colorId]['name'] : '',
                        'color_img' => isset($color[$colorId]) ? $color[$colorId]['path'] : '',
                        'option_id' => $standard[$i]['option_id'],
                        'goods_id' => $goodsModel->id,
                        'e_price' => $standard[$i]['e_price'],
                        'w_price' => $standard[$i]['w_price'],
                        'cost_price' => $standard[$i]['cost_price'],
                        'quantity' => 1000000,
                        'barcode' => $standard[$i]['barcode'],
                        'type' => $type,
                        'store_code' => $standard[$i]['store_code']
                    ];
                }

                //商品规格处理
                $specificationModel = new MallGoodsSpecifications();
                $specificationModel->saveAll($standardArr);

                $this->redirect(url('admin/goods/index'));
            }
        }

        $unitModel = new MallUnit();
        $unitRows = $unitModel->where([])->order('sequence','desc')->field(['id','name'])->select();

        $this->assign('unitRows',$unitRows);
        return $this->fetch();
    }

    /**
     * @desc 修改商品
     * @param Request $request
     * @param $id
     * @return mixed
     */
    public function edit(Request $request,$id){
        $goodsModel = new MallGoods();
        $row = $goodsModel->where(['id'=>$id])->find();
        if($request->isPost()){
            //更新为待审核状态
            $title = $request->post('title','');
            $type = $request->post('type',0,'intval');
            $supplier = $request->post('supplier',0,'intval');
            $unit = $request->post('unit',0,'intval');
            $cover_path = $request->post('cover_path','');
            $multi_path = $request->post('multi_path','');
            $color = $request->post('color/a');

            $standard = $request->post('standard/a');
            $cost_price = $request->post('cost_price',0);
            $w_price = $request->post('w_price',0);
            $e_price = $request->post('e_price',0);

            $content = $request->post('content','');
            $state = $request->post('state',0,'intval');
            $standardArr =  [];

            //计算min_price,max_price
            $priceArr = [];
            if($standard){
                foreach ($standard as $arr){
                    $priceArr[] = $arr['w_price'];
                }
            }
            //添加商品
            $goodsModel = new MallGoods();
            $goods = [
                'shop_id' =>1,
                'min_price' => $priceArr ? min($priceArr):$w_price,
                'max_price' => $priceArr ? max($priceArr):$w_price,
                'state' => $state,
                'type' =>$type,
                'unit' =>$unit,
                'w_price' =>$w_price,
                'e_price' => $e_price,
                'cost_price' => $cost_price,
                'supplier' => $supplier,
                'title' => $title,
                'icon' => $cover_path,
                'multi_angle_img' => $multi_path,
                'detail' => $content,
                'm_detail' => $content,
                'limit_cycle' => '',
                'time'=> time()
            ];

            $result = $goodsModel->save($goods,['id'=>$id]);
            if($result !== false){
                for($i =0; $i < count($standard); $i++){
                    $colorId = $standard[$i]['color_id'];
                    $standardArr[] = [
                        'color_id' => $standard[$i]['color_id'],
                        'color_name' => isset($color[$colorId]) ? $color[$colorId]['name'] : '',
                        'color_img' => isset($color[$colorId]) ? $color[$colorId]['path'] : '',
                        'option_id' => $standard[$i]['option_id'],
                        'goods_id' => $id,
                        'cost_price' =>$standard[$i]['cost_price'],
                        'e_price' => $standard[$i]['e_price'],
                        'w_price' => $standard[$i]['w_price'],
                        'quantity' => 1000000,
                        'barcode' => $standard[$i]['barcode'],
                        'type' => $type,
                        'store_code' => $standard[$i]['store_code']
                    ];
                }
                //商品规格处理
                $specificationModel = new MallGoodsSpecifications();
                //删除规格数据
                $specificationModel->where(['goods_id'=>$id])->delete();
                //保存规格数据
                $specificationModel->saveAll($standardArr);

                $this->redirect(url('admin/goods/index'));
            }
        }

        $imgList = [];
        $imgArr = $row->multi_angle_img ? explode('|',$row->multi_angle_img) : [];
        for($i = 0; $i < count($imgArr); $i++){
            $imgList[] =["img"=>MallGoods::getFormatMultiImg($imgArr[$i])];
        }

        $row['iconPath'] = MallGoods::getFormatImg($row->icon);
        $row['imgList'] = $imgList;

        $unitModel = new MallUnit();
        $unitRows = $unitModel->where([])->order('sequence','desc')->field(['id','name'])->select();
        $this->assign('unitRows',$unitRows);
        $this->assign('row',$row);
        $this->assign('id',$id);
        return $this->fetch();
    }


    /**
     * @desc 删除商品
     * @param Request $request
     * @param $id
     * @return array
     */
    public function delete(Request $request, $id){
        //检查删除条件
        $orderGoodsModel = new MallOrderGoods();
        $row = $orderGoodsModel->where(['goods_id'=>$id])->find();
        if($row){
            return ['status'=>1,'data'=>[],'msg'=>'已有下单商品,不能删除'];
        }
        $model = new MallGoods();

        $result = $model->where(['id'=>$id])->delete();
        if($result == true){
            return ['status'=>0,'data'=>[],'msg'=>'删除成功'];
        }
        return ['status'=>1,'data'=>[],'msg'=>'删除失败'];
    }

    /**
     * @desc
     * @param Request $request
     * @param $id
     * @return array
     */
    public function update(Request $request,$id){
        $goodsModel = new MallGoods();
        $state = $request->post('state','');
        $result = $goodsModel->save(['state'=>$state],['id'=>$id]);
        if($result !== false){
            return ['status'=>0,'data'=>[],'msg'=>'设置成功'];
        }
        return ['status'=>1,'data'=>[],'msg'=>'设置失败'];
    }

    /**
     * @param Request $request
     * @param $id
     * @return array
     */
    public function check(Request $request,$id){
        $goodsModel = new MallGoods();
        $state = $request->post('state','');
        $result = $goodsModel->save(['mall_state'=>$state],['id'=>$id]);
        if($result !== false){
            return ['status'=>0,'data'=>[],'msg'=>'设置成功'];
        }
        return ['status'=>1,'data'=>[],'msg'=>'设置失败'];
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
        $typeModel = new MallType();
        $specificationModel = new MallGoodsSpecifications();
        $userInfo = $userModel->getInfoById($row->supplier);
        $row['supplierName'] = $userInfo ? $userInfo->real_name : '';

        $unitInfo = $unitModel->where(['id'=>$row->unit])->find();
        $row['unitName'] = $unitInfo ? $unitInfo->name : '';

        //
        $typeRow = $typeModel->where(['id'=>$row->type])->find();
        $rows = [];
        if($typeRow->color == 1 || $typeRow->diy_option){
           $rows = $specificationModel->alias('a')->join(config('prefix').'mall_type_option b','a.option_id=b.id','left')->where(['a.goods_id'=>$id])->field(['a.color_name','a.color_img','a.e_price','a.w_price','a.cost_price','a.barcode','a.store_code','b.name'])->select();
           foreach ($rows as &$row2){
               $row2['color_img'] = $row2->color_img ? MallGoodsSpecifications::getFormatPath($row2->color_img) : '';
           }
        }

        //
        $goodsTypeArr = array_reverse(getTypeLevel($row->type));
        $row['goodsTypeName'] = implode('&nbsp;>',$goodsTypeArr);
        $this->assign('goods',$row);
        $this->assign('rows',$rows);
        return $this->fetch();
    }


    public function getType($typeId = 0,$goodsId = 0){
        $model = new MallType();
        $row = $model->where(['id'=>$typeId])->find();

        //根据color查询
        $colorList = $optionList = [];
        if($row->color == 1){
           $colorList = getColorList();
        }
        //根据
        if($row->diy_option == 1){
            $typeOptionModel = new MallTypeOption();
            $optionList = $typeOptionModel->where(['type_id'=>$typeId])->field(['id','name'])->select();
        }
        $specificationModel = new MallGoodsSpecifications();

        $colorRows = $specificationModel->where(['goods_id'=>$goodsId,'type'=>$typeId,'color_id'=>['gt',0]])->field(['color_id','color_name','color_img'])->group('color_id')->select();
        foreach ($colorRows as &$colorRow){
            $colorRow['color_path'] = MallColor::getFormatImg($colorRow->color_id);
            $colorRow['color_img_path'] = MallGoodsSpecifications::getFormatPath($colorRow->color_img);
        }

        $colorIds  = $optionIds = [];

        $rows = $specificationModel->where(['goods_id'=>$goodsId,'type'=>$typeId])->field(['color_id','color_name','color_img','option_id','cost_price','w_price','e_price','cost_price','barcode','store_code'])->select();
        foreach ($rows as &$row2){
            $colorIds[] = $row2->color_id;
            $optionIds[] = $row2->option_id;

            $row2['option_name'] = '';
            foreach ($optionList as $opt_list){
                if($row2->option_id == $opt_list->id){
                    $row2['option_name'] = $opt_list->name;
                    continue;
                }
            }
        }
        $colorIds = array_unique($colorIds);
        $optionIds = array_unique($optionIds);

        foreach ($colorList as &$color_list){
            if(in_array($color_list->id,$colorIds)){
                $color_list['checked'] = 1;
            }else{
                $color_list['checked'] = 0;
            }
        }

        foreach($optionList as &$option_list){
            if(in_array($option_list->id,$optionIds)){
                $option_list['checked'] = 1;
            }else{
                $option_list['checked'] = 0;
            }
        }

        //查询规格
        return [
            'status'=>0,
            'data'=>[
                'color'=>$row->color,
                'color_list' => $colorList,
                'option'=>$row->diy_option,
                'option_list'=>$optionList,
                'option_name'=>$row->option_name,
                'specification'=>$rows,
                'color_row' => $colorRows,
                'colorIds' => $colorIds,
                'optionIds' => $optionIds
            ],
            'msg'=>''
        ];
    }

    /**
     * @desc 预览
     * @param $id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function preview($id){
        $model = new MallGoods();
        $row = $model->where(['id'=>$id])->find();

        $row['icon'] = MallGoods::getFormatImg($row->icon);

        $this->assign('goods',$row);
        return $this->fetch();
    }


}
