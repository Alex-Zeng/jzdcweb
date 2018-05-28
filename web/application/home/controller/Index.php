<?php
namespace app\home\controller;

use think\Controller;

class Index extends Controller
{
    public function index()
    {

        return ['没有权限'];
       // return $this->fetch('index',['name'=>'thinkphp']);
    }
}
