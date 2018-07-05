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
        $type = $request->post('type',0,'intval');
        $submitTime = time();
        $dayTimestamp = 86400;  //表示一天的时间戳

        if(!$phone){
            return ['status'=>1,'data'=>[],'msg'=>'手机号不能为空'];
        }
        if(!checkPhone($phone)){
            return ['status'=>1,'data'=>[],'msg'=>'手机号格式不正确'];
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
        //判断

        $exist = $model->where(['phone'=>$phone,'type'=>$type])->order(['write_time' => 'desc'])->find();

        if($exist){
            $intervalTime = $submitTime - $exist->write_time;
            if($intervalTime < $dayTimestamp){
                return ['status'=>1,'data'=>[],'msg'=>'24小时内同一个手机号同一个业务只能提交一次'];
            }
        }


        $data = [
            'write_time' => time(),
            'writer' => $userId,
            'name' => $name,
            'sex' => $sex,
            'phone' => $phone,
            'comment' => $comment,
            'type' => $type
        ];
        $result = $model->save($data);
        if($result == true){
            //发送邮件通知
            $email = 'liangjiahui@jizhongdiancai.com';
            $email = 'songanwei@jizhongdiancai.com';
            $subject='集众电采服务预约';
            $content='您好，当前有新的服务预约申请，请及时跟进处理。';
            $result = SendMail($email,$subject,$content);
//            if($result == true){
//                return ['status'=>0,'data'=>[],'msg'=>'邮件发送成功'];
//            }
            return ['status'=>0, 'data'=>[],'msg'=>'添加成功'];
        }
        return ['status'=>1,'data'=>[],'msg'=>'添加失败'];
    }

}