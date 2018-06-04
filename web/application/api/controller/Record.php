<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/6/1
 * Time: 10:26
 */

namespace app\api\controller;

use app\common\model\MallSearchLog;

class Record extends Base{

    /**
     * @desc 热门搜索
     * @return array
     */
    public function getSearch(){
        $model = new MallSearchLog();
        $rows = $model->where([])->order(['sum'=>'desc','id'=>'desc'])->field(['keyword'])->limit(5)->select();
        return ['status'=>0,'data'=>$rows,'msg'=>''];
    }


}