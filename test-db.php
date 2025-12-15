<?php
/**
 * 测试数据库连接和system_setting表
 */

// 包含配置文件
$config = require_once 'config.php';

// 包含数据库连接类
require_once 'db.php';

// 输出JSON格式
header('Content-Type: application/json; charset=utf-8');

try {
    // 创建数据库连接
    $db = new Database();
    
    // 测试连接
    $conn = $db->getConnection();
    echo json_encode(array(
        'code' => 0,
        'msg' => '数据库连接成功',
        'data' => array(
            'connected' => true
        )
    ));
    
    // 测试system_setting表是否存在
    $tableExists = $db->tableExists('system_settings');
    echo json_encode(array(
        'code' => 0,
        'msg' => '检查表是否存在',
        'data' => array(
            'table_exists' => $tableExists
        )
    ));
    
    // 测试查询site_name
    $siteName = $db->findOne('system_settings', array('key' => 'site_name'));
    echo json_encode(array(
        'code' => 0,
        'msg' => '查询site_name',
        'data' => array(
            'site_name' => $siteName
        )
    ));
    
} catch (Exception $e) {
    echo json_encode(array(
        'code' => 1,
        'msg' => '数据库连接失败: ' . $e->getMessage(),
        'data' => array()
    ));
}
