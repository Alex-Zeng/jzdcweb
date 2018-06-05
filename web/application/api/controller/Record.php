<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/6/1
 * Time: 10:26
 */

namespace app\api\controller;

use app\common\model\UserSearchLog;

class Record extends Base{

    /**
     * @desc 热门搜索
     * @return array
     */
    public function getSearch(){
        $this->noauth();
        if($this->userId <= 0){
            return ['status'=>0,'data'=>[],'msg'=>''];
        }
        $model = new UserSearchLog();
        $rows = $model->where(['user_id'=>$this->userId])->order('update_time','desc')->limit(20)->field(['type','keyword'])->select();
        return ['status'=>0,'data'=>$rows,'msg'=>''];
    }


}