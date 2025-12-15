<?php
/**
 * 获取系统配置API
 * 用于前端获取系统配置信息
 */

// 引入配置文件
$config = require_once '../config.php';

// 设置响应头
header('Content-Type: application/json; charset=utf-8');

// 只返回需要暴露给前端的配置
$frontend_config = array(
    'debug' => $config['debug'],
    'site_name' => $config['site_name'],
    'site_description' => $config['site_description']
);

echo json_encode(array(
    'code' => 0,
    'msg' => '获取配置成功',
    'data' => $frontend_config
));
?>