<?php
/**
 * 登录检查测试页面
 * 演示如何使用公共函数进行登录检查
 */

// 引入公共函数文件
require_once 'common.php';

// 检查用户是否登录
$user = check_login();

if ($user) {
    echo '<h1>登录成功</h1>';
    echo '<p>欢迎，' . $user['username'] . '！</p>';
    echo '<p>您的邮箱：' . $user['email'] . '</p>';
    echo '<p>用户ID：' . $user['id'] . '</p>';
    echo '<p>登录状态：已登录</p>';
    echo '<p>调试模式：' . (get_config('debug') ? '开启' : '关闭') . '</p>';
    echo '<p><a href="index.html">返回首页</a></p>';
} else {
    echo '<h1>未登录</h1>';
    echo '<p>您尚未登录，请先登录</p>';
    echo '<p><a href="login.html">前往登录</a></p>';
    echo '<p>调试模式：' . (get_config('debug') ? '开启' : '关闭') . '</p>';
}
?>