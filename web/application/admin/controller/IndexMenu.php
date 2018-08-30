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
   //列表
    public function index(Request $request){
        $k = $request->get('k','','trim');
        $model = new MenuMenu();
        if(isset($k) && $k){
            $model->where('name','like','%'.$k.'%');
        }
        $rows = $model->order('id','desc')->select();

        foreach ($rows as &$row){
            $row['path'] = MenuMenu::getFormatImg($row->path);
            if($row->type_id > 0){
                $goodsTypeArr = array_reverse(getCategoryLevel($row->type_id));
                $row['goodsTypeName'] = implode('&nbsp;>',$goodsTypeArr);
            }else{
                $row['goodsTypeName'] = '';
            }
        }

        $this->assign('list',$rows);
        $this->assign('k',$k);
        return $this->fetch();
    }

    //创建
    public function create(Request $request){
        $name = $request->post('name');
        $tag = $request->post('tag',0,'intval');
        $type = $request->post('type',0,'intval');
        $flag = $request->post('flag',0,'intval');
        $sequence = $request->post('sequence',0,'intval');
        $path = $request->post('path','');
        $visible = $request->post('visible',0,'intval');

        if(!$name){
            return ['status'=>1,'data'=>[],'msg'=>'名称不能为空'];
        }

        //$tag =1   $tag=2
        $model = new MenuMenu();
        $condition = [];
        if($tag == 1){
            $condition['type_id'] = $type;
        }elseif($tag == 2){
            $condition['flag'] = $flag;
        }
        $exist = $model->where($condition)->find();
        if($exist){
            return ['status'=>1,'data'=>[],'msg'=>'菜单名称已经存在'];
        }

        $data = [
            'name' => $name,
            'url' => '',
            'sequence' => $sequence,
            'parent_id' => 16,
            'open_target' => '_self',
            'visible' => $visible,
            'path' => $path,
            'type_id' => $tag == 1 ? $type : 0,
            'flag' => $tag == 2 ? $flag : 0
        ];
        $result = $model->save($data);
        if($result == true){
            return ['status'=>0,'data'=>[],'msg'=>'添加成功'];
        }
        return ['status'=>1,'data'=>[],'msg'=>'添加失败'];
    }

   //修改
    public function edit(Request $request, $id){
        $name = $request->post('edit_name');
        $tag = $request->post('edit_tag',0,'intval');
        $type = $request->post('edit_type',0,'intval');
        $flag = $request->post('edit_flag',0,'intval');
        $sequence = $request->post('edit_sequence',0,'intval');
        $path = $request->post('edit_path','');
        $visible = $request->post('edit_visible',0,'intval');

        if(!$name){
            return ['status'=>1,'data'=>[],'msg'=>'名称不能为空'];
        }

        //$tag =1   $tag=2
        $model = new MenuMenu();
        $condition = [];
       // $condition['name'] = $name;
        $condition['id'] = ['not in',$id];
        if($tag == 1){
            $condition['type_id'] = $type;
        }elseif($tag == 2){
            $condition['flag'] = $flag;
        }
        $exist = $model->where($condition)->find();

        if($exist){
            return ['status'=>1,'data'=>[],'msg'=>'菜单已经存在'];
        }
        $data = [
            'name' => $name,
            'url' => '',
            'sequence' => $sequence,
            'parent_id' => 16,
            'open_target' => '_self',
            'visible' => $visible,
            'path' => $path,
            'type_id' => $tag == 1 ? $type : 0,
            'flag' => $tag == 2 ? $flag : 0
        ];
        $result = $model->save($data,['id'=>$id]);
        if($result == true){
            return ['status'=>0,'data'=>[],'msg'=>'更新成功'];
        }
        return ['status'=>1,'data'=>[],'msg'=>'更新失败'];
    }

    //删除
    public function delete($id){
        $model = new MenuMenu();
        $result = $model->where(['id'=>$id])->delete();
        if($result !== false){
            return ['status'=>0,'data'=>[],'msg'=>'删除成功'];
        }
        return ['status'=>1,'data'=>[],'msg'=>'删除失败'];
    }

    public function get($id){
        $model = new MenuMenu();
        $row = $model->where(['id'=>$id])->find();

        $data = [
            'name' => $row->name,
            'sequence' => $row->sequence,
            'path' => $row->path,
            'type' => $row->type_id,
            'flag' => $row->flag,
            'tag' => $row->flag > 0 ? 2 :1,
            'preview' => MenuMenu::getFormatImg($row->path)
        ];

        return ['status'=>0,'data'=>$data,'msg'=>''];
    }


}

