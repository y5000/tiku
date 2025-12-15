<?php
/**
 * 登录接口
 * 功能：处理用户登录请求，验证用户名和密码，返回登录结果
 * 请求方式：POST
 * 请求参数：
 *   - username: 用户名（必填）
 *   - password: 密码（必填）
 * 返回格式：JSON
 * 返回示例：
 *   {"code": 0, "msg": "登录成功", "data": {"user_id": 1, "username": "test"}}
 *   {"code": 1, "msg": "用户名或密码错误"}
 */

// 引入公共函数文件
require_once '../common.php';

// 设置响应头
header('Content-Type: application/json; charset=utf-8');

// 初始化响应数据
$response = array(
    'code' => 1, // 默认失败
    'msg' => '请求失败',
    'data' => array()
);

// 检查请求方式
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['msg'] = '请求方式错误，只支持POST请求';
    echo json_encode($response);
    exit;
}

// 检查当前登录状态
$user = check_login();

if ($user) {
    // 已登录（调试模式下会自动登录）
    $response['code'] = 0;
    $response['msg'] = '登录成功';
    $response['data'] = array(
        'user_id' => $user['id'],
        'username' => $user['username'],
        'email' => $user['email'],
        'avatar' => $user['avatar']
    );
    echo json_encode($response);
    exit;
}

// 非调试模式下，需要验证用户名和密码
// 获取请求参数
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$password = isset($_POST['password']) ? trim($_POST['password']) : '';

// 验证参数
if (empty($username) || empty($password)) {
    $response['msg'] = '用户名和密码不能为空';
    echo json_encode($response);
    exit;
}

// 引入数据库连接
require_once '../db.php';

// 连接数据库
try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // 查询用户信息
    $user = $db->findOne('users', array('username' => $username, 'status' => 1));
    
    if (!$user) {
        $response['msg'] = '用户名或密码错误';
        echo json_encode($response);
        exit;
    }
    
    // 验证密码
    if (!password_verify($password, $user['password'])) {
        $response['msg'] = '用户名或密码错误';
        echo json_encode($response);
        exit;
    }
    
    // 登录成功
    $response['code'] = 0;
    $response['msg'] = '登录成功';
    $response['data'] = array(
        'user_id' => $user['id'],
        'username' => $user['username'],
        'email' => $user['email'],
        'avatar' => $user['avatar']
    );
    
    echo json_encode($response);
    
} catch (Exception $e) {
    $response['msg'] = '登录失败：' . $e->getMessage();
    echo json_encode($response);
    exit;
}
?>