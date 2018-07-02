<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/7/2
 * Time: 12:02
 */

namespace app\admin\controller;

use app\common\model\MenuMenu;
use think\Request;

class IndexMenu extends Base{

    public function index(Request $request){
        $k = $request->post('k','');

        $model = new MenuMenu();
        $rows = $model->where([])->select();

        foreach ($rows as &$row){
            $row['path'] = MenuMenu::getFormatImg($row->path);
        }

        $this->assign('list',$rows);
        $this->assign('k',$k);
        return $this->fetch();
    }

}

