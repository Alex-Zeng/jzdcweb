<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/5/31
 * Time: 17:32
 */
namespace app\api\controller;

use app\common\model\FormFinService;
use think\Request;

class Service extends Base{

    /**
     * @desc 金融服务提交数据
     * @param Request $request
     * @return array
     */
    public function Finance(Request $request){
        $phone = $request->post('phone','');
        $comment = $request->post('comment','');
        $sex = $request->post('sex','');
        $name = $request->post('name','');

        if(!$phone){
            return ['status'=>1,'data'=>[],'msg'=>'手机号不能为空'];
        }

        if(!$name){
            return ['status'=>1,'data'=>[],'msg'=>'用户名不能为空'];
        }

        if(!$sex){
            return ['status'=>1,'data'=>[],'msg'=>'性别不能为空'];
        }

        $this->noauth();
        $userId = $this->userId;

        $model = new FormFinService();
        $data = [
            'write_time' => time(),
            'writer' => $userId,
            'write_time' => time(),
            'name' => $name,
            'sex' => $sex,
            'phone' => $phone,
            'comment' => $comment
        ];
        $result = $model->save($data);
        if($result == true){
            return ['status'=>0, 'data'=>[],'msg'=>'添加成功'];
        }
        return ['status'=>1,'data'=>[],'msg'=>'添加失败'];
    }

}