<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/8/27
 * Time: 14:16
 */
namespace app\api\controller;

use app\common\model\ComplaintsSuggestions;
use think\Request;

class  Suggestion extends Base{

    /**
     * @desc v1.0.2版本接口
     * @param Request $request
     */
    public function add(Request $request){
        $contacts = $request->post('contact','','trim');
        $contactsNum = $request->post('contactNum','','trim');
        $content = $request->post('content','','trim');

        //验证数据
//        $str='中';
//        echo strlen($str);
//        echo '<br />';
//        echo mb_strlen($str,'UTF8');

        //
        $model = new ComplaintsSuggestions();
        $data = [
            'content' => $content,
            'contacts' => $contacts,
            'contact_num' => $contactsNum,
            'created_time' => time()
        ];

        $result = $model->save($data);
        if($result == true){
            return ['status'=>0,'data'=>[],'msg'=>'您的建议已提交成功!'];
        }
        return ['status'=>1,'data'=>[],'msg'=>'您的建议提交失败，请重试!'];
    }


}