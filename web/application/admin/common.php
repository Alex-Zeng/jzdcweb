<?php
/**
 * @desc 返回用户ID||兼容原版本session
 * @return int
 */
function getUserId()
{
    session_start();
    $jzdc = isset($_SESSION['jzdc']) ? $_SESSION['jzdc'] : [];
    return isset($jzdc['id']) ? $jzdc['id'] : 0;
}

/**
 * @desc 返回用户昵称
 * @return int|mixed
 */
function getUserName(){
    $jzdc = isset($_SESSION['jzdc']) ? $_SESSION['jzdc'] : [];
    return isset($jzdc['nickname']) ? $jzdc['nickname'] : 0;
}

/**
 * @desc 返回用户组角色
 * @return int|mixed
 */
function getGroupId(){
    //session_start();
    $jzdc = isset($_SESSION['jzdc']) ? $_SESSION['jzdc'] : [];
    return isset($jzdc['group_id']) ? $jzdc['group_id'] : 0;
}

/**
 * @desc 返回设备
 * @param int $type
 * @return array|mixed
 */
function getDeviceType($type = -1){
    $list = [
       1 => 'PC端',
       2 => 'APP端',
       3 => '微信端'
    ];
    return isset($list[$type]) ? $list[$type] : '';
}
