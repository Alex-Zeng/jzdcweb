<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/9/10
 * Time: 11:12
 */
namespace app\api\controller;




use app\common\model\SmProduct;
use app\common\model\SmProductSpec;
use app\common\model\SmProductSpecAttrVal;
use app\common\model\UserGoodsSpecifications;
use think\Request;

class Material extends Base{


    /**
     * @desc 物料编号数量
     * @return array|void
     */
    public function getNumber(){
        $auth = $this->auth();
        if($auth){
            return $auth;
        }

        //查询物料规格数量
        $model = new \app\common\model\UserGoodsSpecifications();
        $where = [
            'a.user_id' => $this->userId,
            'c.state' => SmProduct::STATE_FORSALE,
            'c.audit_state' => SmProduct::AUDIT_RELEASED,
            'c.is_deleted' =>0,
            'b.is_deleted' => 0
        ];

        $count = $model->alias('a')
            ->join(['sm_product_spec' => 'b'],'a.product_spec_id = b.id','left')
            ->join(['sm_product' => 'c'],'b.product_id=c.id','left')
            ->where($where)->count();

        return ['status'=>0,'data'=>['total'=>$count],'msg'=>''];
    }

    /**
     * @desc 返回列表 APP,H5
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getList(Request $request){
        $pageSize = $request->post('pageSize',10,'intval');
        $pageNumber = $request->post('pageNumber',1,'intval');
        $keyword = $request->post('keyword','','trim');
        $start = ($pageNumber - 1)*$pageSize;

        $auth = $this->auth();
        if($auth){
            return $auth;
        }

        //查询物料规格数量
        $model = new \app\common\model\UserGoodsSpecifications();
        $where = [
            'a.user_id' => $this->userId,
            'c.state' => SmProduct::STATE_FORSALE,
            'c.audit_state' => SmProduct::AUDIT_RELEASED,
            'c.is_deleted' =>0,
            'b.is_deleted' => 0
        ];

        if($keyword){
            $where['a.specifications_no|a.specifications_name|c.title'] = ['like','%'.$keyword.'%'];
        }

        $field = ['a.id','a.specifications_no','a.specifications_name','a.product_spec_id','b.sku_code','b.spec_set','b.is_customized','b.spec_img_url','b.min_order_qty','c.title','c.cover_img_url','d.real_name'];
        $total = $model->alias('a')
            ->join(['sm_product_spec' => 'b'],'a.product_spec_id = b.id','left')
            ->join(['sm_product' => 'c'],'b.product_id=c.id','left')
            ->join(['jzdc_index_user'=>'d'],'c.supplier_id=d.id','left')
            ->where($where)->count();

        $rows = $model->alias('a')
            ->join(['sm_product_spec' => 'b'],'a.product_spec_id = b.id','left')
            ->join(['sm_product' => 'c'],'b.product_id=c.id','left')
            ->join(['jzdc_index_user'=>'d'],'c.supplier_id=d.id','left')
            ->where($where)->limit($start,$pageSize)->field($field)->select();

        $specAttrValModel = new SmProductSpecAttrVal();

        $list = [];
        foreach ($rows as $row){
            $specInfo = '';
            if($row->is_customized == 1){
                $specInfo = '定制';
            }else{
                //
                $specSetArr = explode(',',$row->spec_set);
                $specAttrValRows = $specAttrValModel->where(['id'=>['in',$specSetArr]])->select();
                foreach ($specAttrValRows as $specAttrValRow){
                    $specInfo .= $specAttrValRow->spec_attr_val.',';
                }
                $specInfo = $specInfo ? substr($specInfo,0,strlen($specInfo)-1) : $specInfo;
            }

            $list[] = [
                'materialId' => $row->id,
                'specId' => $row->product_spec_id,
                'materialCode' => $row->specifications_no,
                'materialSpec' => $row->specifications_name,
                'title' => $row->title,
                'minOrderQty' => $row->min_order_qty,
                'soldQty' => $row->min_order_qty,
                'specInfo' => $specInfo,
                'supplierName' => $row->real_name,
                'imgUrl' => $row->spec_img_url ? SmProductSpec::getFormatImg($row->spec_img_url) : SmProduct::getFormatImg($row->cover_img_url)
            ];
        }

        return ['status'=>0,'data'=>['total'=> $total,'list'=>$list],'msg'=>''];
    }


    /**
     * @desc PC端列表接口
     * @param Request $request
     * @return array|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getWebList(Request $request){
        $pageSize = $request->post('pageSize',10,'intval');
        $pageNumber = $request->post('pageNumber',1,'intval');
        $materialCode = $request->post('materialCode','','trim');
        $materialSpec = $request->post('materialSpec','','trim');
        $supplier = $request->post('supplierName','','trim');
        $start = ($pageNumber - 1)*$pageSize;

        $auth = $this->auth();
        if($auth){
            return $auth;
        }

        //查询物料规格数量
        $model = new \app\common\model\UserGoodsSpecifications();
        $where = [
            'a.user_id' => $this->userId,
            'c.state' => SmProduct::STATE_FORSALE,
            'c.audit_state' => SmProduct::AUDIT_RELEASED,
            'c.is_deleted' =>0,
            'b.is_deleted' => 0
        ];

        if($materialCode){
            $where['a.specifications_no'] = ['like','%'.$materialCode.'%'];
        }
        if($materialSpec){
            $where['a.specifications_name'] = ['like','%'.$materialSpec.'%'];
        }
        if($supplier){
            $where['d.real_name'] = ['like','%'.$supplier.'%'];
        }

       // $field = ['a.id','a.specifications_no','a.specifications_name','a.product_spec_id','b.sku_code','b.spec_set','b.is_customized','b.spec_img_url','b.min_order_qty','c.title','c.cover_img_url'];
        $field = ['a.specifications_no','a.specifications_name'];
        $total = $model->alias('a')
            ->join(['sm_product_spec' => 'b'],'a.product_spec_id = b.id','left')
            ->join(['sm_product' => 'c'],'b.product_id=c.id','left')
            ->join(['jzdc_index_user' => 'd'],'c.supplier_id=d.id','left')
            ->where($where)->group('a.specifications_no')->count();

        $rows = $model->alias('a')
            ->join(['sm_product_spec' => 'b'],'a.product_spec_id = b.id','left')
            ->join(['sm_product' => 'c'],'b.product_id=c.id','left')
            ->join(['jzdc_index_user' => 'd'],'c.supplier_id=d.id','left')
            ->where($where)->group('a.specifications_no')->limit($start,$pageSize)->field($field)->select();

        $specAttrValModel = new SmProductSpecAttrVal();

        $list = [];
        foreach ($rows as $row){
            //根据物料查询商品
            $specRows = $model->alias('a')
                ->join(['sm_product_spec' => 'b'],'a.product_spec_id = b.id','left')
                ->join(['sm_product' => 'c'],'b.product_id=c.id','left')
                ->join(['jzdc_index_user' => 'd'],'c.supplier_id=d.id','left')
                ->where(['a.user_id'=>$this->userId, 'c.state' => SmProduct::STATE_FORSALE, 'c.audit_state' => SmProduct::AUDIT_RELEASED, 'c.is_deleted' =>0, 'b.is_deleted' => 0])
                ->field(['a.id','a.specifications_no','a.specifications_name','a.product_spec_id','b.sku_code','b.spec_set','b.is_customized','b.spec_img_url','b.min_order_qty','c.title','c.cover_img_url','d.real_name'])
                ->select();
            $specList = [];
            foreach ($specRows as $specRow){
                $specInfo = '';
                if($specRow->is_customized == 1){
                    $specInfo = '定制';
                }else{
                    //
                    $specSetArr = explode(',',$specRow->spec_set);
                    $specAttrValRows = $specAttrValModel->where(['id'=>['in',$specSetArr]])->select();
                    foreach ($specAttrValRows as $specAttrValRow){
                        $specInfo .= $specAttrValRow->spec_attr_val.',';
                    }
                    $specInfo = $specInfo ? substr($specInfo,0,strlen($specInfo)-1) : $specInfo;
                }
                $specList[] = [
                    'materialId' => $specRow->id,
                    'specId' => $specRow->product_spec_id,
                    'title' => $specRow->title,
                    'skuCode' => $specRow->sku_code,
                    'minOrderQty' => $specRow->min_order_qty,
                    'specInfo' => $specInfo,
                    'supplierName' => $specRow->real_name,
                    'imgUrl' => $specRow->spec_img_url ? SmProductSpec::getFormatImg($specRow->spec_img_url) : SmProduct::getFormatImg($specRow->cover_img_url)
                ];
            }

            $list[] = [
                'materialCode' => $row->specifications_no,
                'materialSpec' => $row->specifications_name,
                "detail" => $specList
            ];
        }

        return ['status'=>0,'data'=>['total'=> $total,'list'=>$list],'msg'=>''];
    }

    /**
     * @desc 修改物料
     * @param Request $request
     * @return array|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function edit(Request $request){
        $materialId = $request->post('materialId',0,'intval');
        $specId = $request->post('specId',0,'intval');
        $materialCode = $request->post('materialCode','','trim');
        $materialSpec = $request->post('materialSpec','','trim');

        $auth = $this->auth();
        if($auth){
            return $auth;
        }

        //验证数据
        if($materialId == 0 && $specId == 0){
            return ['status'=>1,'data'=>[],'msg'=>'参数错误'];
        }

        if(!$materialCode){
            return ['status'=>1,'data'=>[],'msg'=>'物料编号必须填写'];
        }
        if(strlen($materialCode) > 30){
            return ['status'=>1,'data'=>[],'msg'=>'物料编号最多30个字'];
        }
        if(strlen($materialSpec) > 40){
            return ['status'=>1,'data'=>[],'msg'=>'物料规格最多40个字'];
        }
        $model = new UserGoodsSpecifications();
        $specModel = new SmProductSpec();

        //验证数据是否存在
        if($materialId > 0){
            $row = $model->where(['id'=>$materialId,'user_id'=>$this->userId])->find();
            if(!$row){
                return ['status'=>1,'data'=>[],'msg'=>'物料编号规格不存在'];
            }

            $result = $model->save(['specifications_no'=>$materialCode,'specifications_name'=>$materialSpec,'update_time'=>time()],['id'=>$materialId]);
            if($result !== false){
                return ['status'=>0,'data'=>[],'msg'=>'修改成功'];
            }
        }else{
            $row = $model->where(['product_spec_id'=>$specId,'user_id'=>$this->userId])->find();
            $specRow = $specModel->find(['id'=>$specId]);

            if(!$row){  //新增
                $result = $model->save(['user_id'=>$this->userId,'goods_id'=>$specRow ?  $specRow->product_id : 0,'specifications_no'=>$materialCode,'specifications_name'=>$materialSpec,'product_spec_id'=>$specId,'create_time'=>time()]);
            }else{  //修改
                $result = $model->save(['specifications_no'=>$materialCode,'specifications_name'=>$materialSpec,'update_time'=>time()],['id'=>$row->id]);
            }

            if($result !== false){
                return ['status'=>0,'data'=>[],'msg'=>'修改成功'];
            }
        }


        return ['status'=>1,'data'=>[],'msg'=>'修改失败'];
    }

    /**
     * @desc 删除物料
     * @param Request $request
     * @return array|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function delete(Request $request){
        $materialId = $request->post('materialId',0,'intval');
        $auth = $this->auth();
        if($auth){
            return $auth;
        }
        $model = new UserGoodsSpecifications();
        //验证数据是否存在
        $row = $model->where(['id'=>$materialId,'user_id'=>$this->userId])->find();
        if(!$row){
            return ['status'=>1,'data'=>[],'msg'=>'物料编号规格不存在'];
        }

        $result = $model->where(['id'=>$materialId])->delete();
        if($result !== false){
            return ['status'=>0,'data'=>[],'msg'=>'物料编号规格删除成功'];
        }
        return ['status'=>1,'data'=>[],'msg'=>'物料编号规格删除失败'];
    }

}