<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/9/7
 * Time: 14:04
 */

namespace app\admin\controller;


class Report extends Base{

    /**
     * @desc 交易报表
     * @return mixed
     */
    public function trade(){
        $this->assign('list',[]);
        $this->assign('page',[]);
        return $this->fetch();
    }

    /**
     * @desc 供应商
     * @return mixed
     */
    public function supplier(){
        $this->assign('list',[]);
        $this->assign('page',[]);
        return $this->fetch();
    }

    public function buyer(){
        $this->assign('list',[]);
        $this->assign('page',[]);
        return $this->fetch();
    }

    public function order(){
        $this->assign('list',[]);
        $this->assign('page',[]);
        return $this->fetch();
    }

    public function company(){
        $this->assign('list',[]);
        $this->assign('page',[]);
        return $this->fetch();
    }

}