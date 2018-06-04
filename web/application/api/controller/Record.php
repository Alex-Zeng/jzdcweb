<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/6/1
 * Time: 10:26
 */

namespace app\api\controller;

class Record extends Base{

    /**
     * @desc 返回用户搜索记录
     * @return array
     */
    public function getSearch(){
        $this->noauth();
        if($this->userId == 0){
            return ['status'=>0,'data'=>[],'msg'=>''];
        }

        return ['status'=>0,'data'=>[],'msg'=>''];
    }


}