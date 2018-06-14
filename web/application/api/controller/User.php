<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/5/21
 * Time: 16:41
 */
namespace app\api\controller;

use app\common\model\IndexUser;
use app\common\model\MallFavorite;
use app\common\model\MallGoods;
use app\common\model\MallOrder;
use app\common\model\MallOrderGoods;
use app\common\model\MallReceiver;
use app\common\model\Notice;
use app\common\model\OrderMsg;
use app\common\model\UserSearchLog;
use think\Request;

class User extends Base {


    /**
     * @desc 返回用户所在组
     * @return array
     */
    public function getGroup(){
        $this->noauth();
        return ['status'=>0,'data'=>['groupId'=>$this->groupId],'msg'=>''];
    }

    /**
     * @desc 添加收货人地址
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function addAddress(Request $request){
        $areaId = $request->post('areaId',0,'intval');
        $detail = $request->post('detail','');
        $postCode = $request->post('postCode','');
        $name = $request->post('name','');
        $phone = $request->post('phone','');
        $tag = $request->post('tag','');

        if(!checkPhone($phone)){
            return  ['status'=>1,'data'=>[],'msg'=>'手机号格式不正确'];
        }

        if($detail){
            return  ['status'=>1,'data'=>[],'msg'=>'详细地址不能为空'];
        }

        if($name){
            return  ['status'=>1,'data'=>[],'msg'=>'收货人不能为空'];
        }

        $auth = $this->auth();
        if($auth){
            return $auth;
        }

        $model = new MallReceiver();

        $userId = $this->userId;
        $count = $model->where(['user_id'=>$userId])->count();
        if($count >= 20){
            return ['status'=>1,'data'=>[],'msg'=>'最多添加20个收货人地址'];
        }


        $userRow = (new IndexUser())->getInfoById($userId);
        $userName = $userRow ? $userRow['username'] : '';


        $data = [
            'username' => $userName,
            'time' => time(),
            'area_id' => $areaId,
            'detail' => $detail,
            'post_code' => $postCode,
            'name' => $name,
            'phone' => $phone,
            'tag' => $tag
        ];
        $result = $model->save($data);
        if($result == true){
            return ['status'=>0,'data'=>[],'msg'=>'添加成功'];
        }
        return ['status'=>1,'data'=>[],'msg'=>'添加失败'];
    }


    /**
     * @desc 修改收货人地址
     * @param Request $request
     * @return array
     */
    public function editAddress(Request $request){
        $id = $request->post('id',0,'intval');
        $areaId = $request->post('areaId',0,'intval');
        $detail = $request->post('detail','');
        $postCode = $request->post('postCode','');
        $name = $request->post('name','');
        $phone = $request->post('phone','');
        $tag = $request->post('tag','');

        $auth = $this->auth();
        if($auth){
            return $auth;
        }

        if(!checkPhone($phone)){
            return  ['status'=>1,'data'=>[],'msg'=>'手机号格式不正确'];
        }

        if($detail){
            return  ['status'=>1,'data'=>[],'msg'=>'详细地址不能为空'];
        }

        if($name){
            return  ['status'=>1,'data'=>[],'msg'=>'收货人不能为空'];
        }

        $model = new MallReceiver();
        $data = [
            'time' => time(),
            'area_id' => $areaId,
            'detail' => $detail,
            'post_code' => $postCode,
            'name' => $name,
            'phone' => $phone,
            'tag' => $tag
        ];
        $result = $model->save($data,['id'=>$id]);
        if($result == true){
            return ['status'=>0,'data'=>[],'msg'=>'修改成功'];
        }
        return ['status'=>1,'data'=>[],'msg'=>'修改失败'];
    }

    /**
     * @desc 删除收货人地址
     * @param Request $request
     * @return array
     */
    public function removeAddress(Request $request){
        $id = $request->post('id',0,'intval');
        $auth = $this->auth();
        if($auth){
            return $auth;
        }

        $model = new MallReceiver();
        $result = $model->where(['id'=>$id])->delete();
        if($result == true){
            return ['status'=>0,'data'=>[],'msg'=>'删除成功'];
        }
        return ['status'=>1,'data'=>[],'msg'=>'删除失败'];
    }

    /**
     * @desc 返回用户收藏商品数量
     * @return array
     */
    public function getFavoriteNumber(){
        $auth = $this->auth();
        if($auth){
            return $auth;
        }

        $model = new MallFavorite();
        $count = $model->where(['user_id'=>$this->userId])->count();

        return ['status'=>0,'data'=>['number'=>$count],'msg'=>''];
    }

    /**
     * @desc 返回收藏列表
     * @param Request $request
     * @return array|void
     */
    public function getFavoriteList(Request $request){
        $pageSize = $request->post('pageSize',10,'intval');
        $pageNumber = $request->post('pageNumber',1,'intval');
        $field = $request->post('field','time','trim');
        $sort = $request->post('sort','desc','trim');
        if(!in_array($field,['time','price'])){
            return ['status'=>1,'data'=>[],'msg'=>'数据错误'];
        }
        if(!in_array($sort,['asc','desc'])){
            return ['status'=>1,'data'=>[],'msg'=>'数据错误'];
        }

        if($pageSize > 10){ $pageSize = 10;};
        $offset = ($pageNumber - 1)*$pageSize;

        $auth = $this->auth();
        if($auth){
            return $auth;
        }
        $model = new MallFavorite();
        $field == 'time' ? ($field = 'a.'.$field) : ($field = 'b.w_price');
        $total = $model->alias('a')->join(config('prefix').'mall_goods b','a.goods_id=b.id','left')->where(['user_id'=>6])->count();
        $rows =  $model->alias('a')->join(config('prefix').'mall_goods b','a.goods_id=b.id','left')->where(['user_id'=>6])->order([$field => $sort])->field(['b.id','b.title','b.w_price','b.icon'])->limit($offset,$pageSize)->select();

        foreach ($rows as &$row){
            $row['icon'] = MallGoods::getFormatImg($row->icon);
        }
        return ['status'=>0,'data'=>['total'=>$total,'list'=>$rows],'msg'=>''];
    }

    /**
     * @desc
     * @return array|void
     */
    public function getSupplierOrderInfo(){
        $auth = $this->auth();
        if($auth){
            return $auth;
        }
        //
        $model = new MallOrder();

        $startTime = strtotime(date("Y-m-d 00:00:00",strtotime("-1 day")));
        $endTime = strtotime(date('Y-m-d 23:59:59',strtotime("-1 day")));

        //
        $yesterdayCount = $model->where(['supplier'=>$this->userId])->where('add_time','>',$startTime)->where('add_time','<',$endTime)->count();
        $total = $model->where(['supplier'=>$this->userId])->count();
        $pendingNumber = $model->where(['supplier'=>$this->userId,'state'=>MallOrder::STATE_DELIVER])->count();

       return ['status'=>0,'data'=>['yesterday'=>$yesterdayCount,'total'=>$total,'pending'=>$pendingNumber],'msg'=>''];
    }

    /**
     * @desc
     * @return array|void
     */
    public function getBuyerOrderInfo(){
        $auth = $this->auth();
        if($auth){
            return $auth;
        }
        //
        $model = new MallGoods();
        $payCount = $model->where(['buyer_id'=>$this->userId])->count();
        $recieveNumber = $model->where(['buyer_id'=>$this->userId])->count();
        $pendingNumber = $model->where(['buyer_id'=>$this->userId])->count();

        return ['status'=>0,'data'=>['pay'=>$payCount,'recieve'=>$recieveNumber,'deliver'=>$pendingNumber],'msg'=>''];
    }


    /**
     * @desc 消息列表
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getMessageList(Request $request){
        $pageSize = $request->post('pageSize',10,'intval');
        $pageNumber = $request->post('pageNumber',1,'intval');
        $pageSize = $pageSize > 10 ? 10 : $pageSize;

        $start = ($pageNumber - 1)*$pageNumber;
        $model = new OrderMsg();
        $rows = $model->where(['user_id'=>$this->userId,'is_delete'=>0])->order('create_time','desc')->field(['id','title','content','order_no','create_time'])->limit($start,$pageSize)->select();

        $data = [];
        foreach($rows as &$row){
            $orderModel = new MallOrder();
            $orderRow = $orderModel->where(['out_id'=>$row->order_no])->field('id')->find();

            $orderGoodsModel = new MallOrderGoods();
            $orderGoodsRow = $orderGoodsModel->alias('a')->join(config('prefix').'mall_goods b','a.goods_id=b.id','left')->where(['order_id'=>$orderRow->id])->field(['b.icon'])->find();
            $icon= MallGoods::getFormatImg($orderGoodsRow ? $orderGoodsRow->icon : '') ;
            $time = strtotime($row['create_time']);

           $data[] = [
              'id' => $row->id,
              'title' => $row->title,
              'content' => $row->content,
              'orderNo' => $row->order_no,
              'release_time' =>  date('Y',$time).'年'.date('m',$time).'月'.date('d',$time).'日 '.date('H:i',$time),
               'icon' => $icon
           ];

        }
        return ['status'=>0,'data'=>['list'=>$data],'msg'=>''];
    }

    /**
     * @desc 通知列表
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getNoticeList(Request $request){
        $pageSize = $request->post('pageSize',10,'intval');
        $pageNumber = $request->post('pageNumber',1,'intval');
        $pageSize = $pageSize > 10 ? 10 : $pageSize;

        $start = ($pageNumber - 1)*$pageSize;
        $model = new Notice();
        $rows = $model->where(['status'=>1])->order('release_time','desc')->field(['id','title','summary','release_time'])->limit($start,$pageSize)->select();

        foreach($rows as &$row){
          $row['release_time'] = date('Y',$row->release_time).'年'.date('m',$row->release_time).'月'.date('d',$row->release_time).'日 '.date('H:i',$row->release_time);
        }
        return ['status'=>0,'data'=>['list'=>$rows],'msg'=>''];
    }

    /**
     * @param Request $request
     * @param $id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getNoticeInfo(Request $request,$id){
        $model = new Notice();
        $row = $model->where(['status'=>1,'id'=>$id])->order('release_time','desc')->field(['id','title','summary','content','release_time'])->find();

        if(!$row){
            return ['status'=>1,'data'=>[],'msg'=>'公告不存在'];
        }

        $row['release_time'] = date('Y',$row->release_time).'年'.date('m',$row->release_time).'月'.date('d',$row->release_time).'日 '.date('H:i',$row->release_time);
        return ['status'=>0,'data'=>$row,'msg'=>''];
    }



}