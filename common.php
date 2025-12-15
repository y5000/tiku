<?php
/**
 * 公共函数文件
 * 用于管理系统的公共函数和登录检查
 */

// 引入配置文件
$config = require_once 'config.php';

/**
 * 检查用户是否登录
 * @return array|bool 登录用户信息或false
 */
function check_login() {
    global $config;
    
    // 调试模式下，直接返回指定用户
    if ($config['debug']) {
        // 引入数据库连接
        require_once 'db.php';
        
        try {
            $db = new Database();
            $user = $db->findOne('users', array('id' => $config['debug_user_id'], 'status' => 1));
            
            if ($user) {
                // 设置会话
                session_start();
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                
                return $user;
            }
        } catch (Exception $e) {
            error_log('调试模式下获取用户失败：' . $e->getMessage());
        }
    }
    
    // 非调试模式下，检查会话
    session_start();
    
    if (isset($_SESSION['user_id'])) {
        // 引入数据库连接
        require_once 'db.php';
        
        try {
            $db = new Database();
            $user = $db->findOne('users', array('id' => $_SESSION['user_id'], 'status' => 1));
            
            if ($user) {
                return $user;
            }
        } catch (Exception $e) {
            error_log('获取用户失败：' . $e->getMessage());
        }
    }
    
    return false;
}

/**
 * 获取当前登录用户
 * @return array|bool 登录用户信息或false
 */
function get_current_user() {
    return check_login();
}

/**
 * 检查是否需要登录，如果未登录则跳转到登录页
 * @param string $redirect_url 登录成功后跳转的URL
 */
function require_login($redirect_url = '') {
    $user = check_login();
    
    if (!$user) {
        // 未登录，跳转到登录页
        $login_url = 'login.html';
        if (!empty($redirect_url)) {
            $login_url .= '?redirect=' . urlencode($redirect_url);
        }
        
        header('Location: ' . $login_url);
        exit;
    }
    
    return $user;
}

/**
 * 获取系统配置
 * @param string $key 配置项键名，为空则返回所有配置
 * @return mixed 配置值或所有配置
 */
function get_config($key = '') {
    global $config;
    
    if (empty($key)) {
        return $config;
    }
    
    return isset($config[$key]) ? $config[$key] : null;
}
?>