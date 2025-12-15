<?php
/**
 * 注册接口
 * 功能：处理用户注册请求，验证用户信息，创建新用户
 * 请求方式：POST
 * 请求参数：
 *   - username: 用户名（必填，长度3-50个字符）
 *   - email: 邮箱（必填，格式正确）
 *   - password: 密码（必填，长度6-20个字符）
 *   - confirm_password: 确认密码（必填，与密码一致）
 * 返回格式：JSON
 * 返回示例：
 *   {"code": 0, "msg": "注册成功", "data": {"user_id": 2, "username": "newuser"}}
 *   {"code": 1, "msg": "用户名已存在"}
 */

// 引入数据库连接文件
require_once '../db.php';

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

// 获取请求参数
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? trim($_POST['password']) : '';
$confirm_password = isset($_POST['confirm_password']) ? trim($_POST['confirm_password']) : '';

// 验证参数
if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
    $response['msg'] = '所有字段都不能为空';
    echo json_encode($response);
    exit;
}

// 验证用户名长度
if (strlen($username) < 3 || strlen($username) > 50) {
    $response['msg'] = '用户名长度必须在3-50个字符之间';
    echo json_encode($response);
    exit;
}

// 验证邮箱格式
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response['msg'] = '邮箱格式不正确';
    echo json_encode($response);
    exit;
}

// 验证密码长度
if (strlen($password) < 6 || strlen($password) > 20) {
    $response['msg'] = '密码长度必须在6-20个字符之间';
    echo json_encode($response);
    exit;
}

// 验证两次密码是否一致
if ($password !== $confirm_password) {
    $response['msg'] = '两次输入的密码不一致';
    echo json_encode($response);
    exit;
}

// 连接数据库
$db = new Database();
$conn = $db->getConnection();

// 检查用户名是否已存在
$existing_user = $db->findOne('users', array('username' => $username));
if ($existing_user) {
    $response['msg'] = '用户名已存在';
    echo json_encode($response);
    exit;
}

// 检查邮箱是否已存在
$existing_email = $db->findOne('users', array('email' => $email));
if ($existing_email) {
    $response['msg'] = '邮箱已被注册';
    echo json_encode($response);
    exit;
}

// 密码加密
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// 准备用户数据
$user_data = array(
    'username' => $username,
    'email' => $email,
    'password' => $hashed_password,
    'status' => 1
);

// 插入用户数据
$user_id = $db->add('users', $user_data);

if (!$user_id) {
    $response['msg'] = '注册失败，请稍后重试';
    echo json_encode($response);
    exit;
}

// 注册成功
$response['code'] = 0;
$response['msg'] = '注册成功';
$response['data'] = array(
    'user_id' => $user_id,
    'username' => $username
);

// 返回响应
echo json_encode($response);
?>