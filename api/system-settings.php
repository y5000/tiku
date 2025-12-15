<?php
/**
 * 系统设置API
 * 用于获取和更新系统设置
 */

// 输出JSON格式的调试信息
header('Content-Type: application/json; charset=utf-8');

// 包含配置文件
$config = require_once '../config.php';

// 包含数据库连接类
require_once '../db.php';

// 数据库连接实例
$db = null;

// 尝试创建数据库连接
$db = new Database();

// 获取请求方法
$method = $_SERVER['REQUEST_METHOD'];

// 根据请求方法处理
switch ($method) {
    // 获取系统设置
    case 'GET':
        // 获取参数
        $key = isset($_GET['key']) ? trim($_GET['key']) : '';
        $response = array();
        
        try {
            if (!empty($key)) {
                // 获取单个设置
                $setting = $db->findOne('system_settings', array('key' => $key));
                if ($setting) {
                    $response['code'] = 0;
                    $response['msg'] = '获取设置成功';
                    $response['data'] = array(
                        'key' => $setting['key'],
                        'value' => $setting['value'],
                        'description' => $setting['description']
                    );
                } else {
                    // 如果数据库中没有找到设置，尝试从配置文件中获取默认值
                    if ($key === 'site_name' && isset($config['site_name'])) {
                        $response['code'] = 0;
                        $response['msg'] = '获取设置成功（使用默认值）';
                        $response['data'] = array(
                            'key' => 'site_name',
                            'value' => $config['site_name'],
                            'description' => '网站名称'
                        );
                    } else {
                        $response['code'] = 1;
                        $response['msg'] = '设置不存在';
                        $response['data'] = array();
                    }
                }
            } else {
                // 获取所有设置
                $settings = $db->find('system_settings');
                $response['code'] = 0;
                $response['msg'] = '获取所有设置成功';
                $response['data'] = $settings;
            }
        } catch (Exception $e) {
            // 数据库连接或查询失败
            $response['code'] = 0;
            $response['msg'] = '获取设置成功（使用默认值）';
            $response['data'] = array();
            
            // 如果是查询site_name，使用配置文件中的默认值
            if ($key === 'site_name' && isset($config['site_name'])) {
                $response['data'] = array(
                    'key' => 'site_name',
                    'value' => $config['site_name'],
                    'description' => '网站名称'
                );
            }
        }
        
        // 输出JSON响应
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response);
        break;
    
    // 更新系统设置
    case 'POST':
        // 获取请求体
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data)) {
            $data = $_POST;
        }
        
        $key = isset($data['key']) ? trim($data['key']) : '';
        $value = isset($data['value']) ? trim($data['value']) : '';
        
        $response = array();
        
        try {
            if (empty($key)) {
                $response['code'] = 1;
                $response['msg'] = '设置键名不能为空';
                $response['data'] = array();
            } else {
                // 检查设置是否存在
                $existing = $db->findOne('system_settings', array('key' => $key));
                if ($existing) {
                    // 更新设置
                    $result = $db->update('system_settings', array('value' => $value), array('key' => $key));
                    if ($result) {
                        $response['code'] = 0;
                        $response['msg'] = '更新设置成功';
                        $response['data'] = array(
                            'key' => $key,
                            'value' => $value
                        );
                    } else {
                        $response['code'] = 1;
                        $response['msg'] = '更新设置失败';
                        $response['data'] = array();
                    }
                } else {
                    // 添加新设置
                    $result = $db->add('system_settings', array(
                        'key' => $key,
                        'value' => $value,
                        'description' => isset($data['description']) ? $data['description'] : ''
                    ));
                    if ($result) {
                        $response['code'] = 0;
                        $response['msg'] = '添加设置成功';
                        $response['data'] = array(
                            'key' => $key,
                            'value' => $value
                        );
                    } else {
                        $response['code'] = 1;
                        $response['msg'] = '添加设置失败';
                        $response['data'] = array();
                    }
                }
            }
        } catch (Exception $e) {
            $response['code'] = 1;
            $response['msg'] = '操作失败：' . $e->getMessage();
            $response['data'] = array();
        }
        
        // 输出JSON响应
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response);
        break;
    
    default:
        // 不支持的请求方法
        $response = array(
            'code' => 1,
            'msg' => '不支持的请求方法',
            'data' => array()
        );
        
        // 输出JSON响应
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response);
        break;
}
