<?php
/**
 * 测试获取site_name
 */

// 直接从配置文件获取site_name
$config = require_once 'config.php';

echo json_encode(array(
    'code' => 0,
    'msg' => '获取成功',
    'data' => array(
        'key' => 'site_name',
        'value' => $config['site_name'],
        'description' => '网站名称'
    )
));
