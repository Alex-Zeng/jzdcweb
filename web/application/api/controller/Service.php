<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/5/31
 * Time: 17:32
 */
namespace app\api\controller;

use app\common\model\FbMerchant;
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
                return ['status'=>1,'data'=>[],'msg'=>'24小时内请勿重复提交'];
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
            $email = config('JZDC_SERVICE_EMAIL');
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


    /**
     * @desc 招商信息收集
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function merchant(Request $request){
        $companyName = $request->post('companyName','','trim');
        $contact = $request->post('contact','','trim');
        $contactNum = $request->post('contactNum','','trim');

        if(!$companyName){
            return ['status'=>1,'data'=>[],'msg'=>'企业名称不能为空'];
        }
        if(!$contact){
            return ['status'=>1,'data'=>[],'msg'=>'姓名不能为空'];
        }

        if(mb_strlen($companyName,"utf-8") > 100){
            return ['status'=>1,'data'=>[],'msg'=>'企业名称最多100个字'];
        }
        if(mb_strlen($contact,"utf-8") > 50){
            return ['status'=>1,'data'=>[],'msg'=>'姓名最多50个字'];
        }

        if(!$contactNum){
            return ['status'=>1,'data'=>[],'msg'=>'手机号不能为空'];
        }
        if(!checkPhone($contactNum)){
            return ['status'=>1,'data'=>[],'msg'=>'手机号格式不正确'];
        }

        $model = new FbMerchant();
        $row = $model->where(['contact_num'=>$contactNum])->find();
        if($row){
            return ['status'=>1,'data'=>[],'msg'=>'该手机号已经提交'];
        }

        $data = [
            'name' => $companyName,
            'contacts' => $contact,
            'contact_num' => $contactNum,
            'created_time' => time()
        ];
        $result = $model->save($data);

        if($result){
            //发送邮件通知
            $email = config('JZDC_SERVICE_EMAIL');
            $subject='集众电采服务预约';
            $content='您好，当前有新的招商服务，请及时跟进处理。';
            $result = SendMail($email,$subject,$content);
            return ['status'=>0,'data'=>[],'msg'=>'提交成功'];
        }
        return ['status'=>1,'data'=>[],'msg'=>'提交失败'];
    }

}