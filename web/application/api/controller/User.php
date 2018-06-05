<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/5/21
 * Time: 16:41
 */
namespace app\api\controller;

use app\common\model\IndexUser;
use app\common\model\MallReceiver;
use app\common\model\UserSearchLog;
use think\Request;

class User extends Base {

    public function __construct()
    {
    }

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
        $areaId = $request->post('areaId',0);
        $detail = $request->post('detail','');
        $postCode = $request->post('postCode','');
        $name = $request->post('name','');
        $phone = $request->post('phone','');
        $tag = $request->post('tag','');

        if(!checkPhone($phone)){
            return  ['status'=>1,'data'=>[],'msg'=>'手机号格式不正确'];
        }

        $auth = $this->auth();
        if($auth){
            return $auth;
        }

        $userId = $this->userId;
        $userRow = (new IndexUser())->getInfoById($userId);
        $userName = $userRow ? $userRow['username'] : '';

        $model = new MallReceiver();
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
        $id = $request->post('id',0);
        $areaId = $request->post('areaId',0);
        $detail = $request->post('detail','');
        $postCode = $request->post('postCode','');
        $name = $request->post('name','');
        $phone = $request->post('phone','');
        $tag = $request->post('tag','');

        $auth = $this->auth();
        if($auth){
            return $auth;
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
        $id = $request->post('id',0);
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

}