<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>题库系统 - 安装向导</title>
    <!-- 引入layui CSS -->
    <link rel="stylesheet" href="https://lib.baomitu.com/layui/2.8.18/css/layui.css">
    <style>
        body {
            background-color: #f2f2f2;
        }
        .setup-container {
            max-width: 600px;
            margin: 50px auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .setup-title {
            text-align: center;
            font-size: 24px;
            margin-bottom: 30px;
            color: #333;
        }
        .step-nav {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }
        .step-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin: 0 15px;
        }
        .step-number {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: #e6e6e6;
            color: #999;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            margin-bottom: 5px;
        }
        .step-number.active {
            background-color: #1e9fff;
            color: #fff;
        }
        .step-number.done {
            background-color: #5fb878;
            color: #fff;
        }
        .step-text {
            font-size: 12px;
            color: #999;
        }
        .step-text.active {
            color: #1e9fff;
        }
        .step-text.done {
            color: #5fb878;
        }
        .step {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        .step-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
        }
        .step-content {
            font-size: 14px;
            color: #666;
        }
        .status {
            margin-top: 10px;
            padding: 10px;
            border-radius: 3px;
        }
        .status.success {
            background-color: #f0f9eb;
            color: #67c23a;
            border-left: 4px solid #67c23a;
        }
        .status.error {
            background-color: #fef0f0;
            color: #f56c6c;
            border-left: 4px solid #f56c6c;
        }
        .status.info {
            background-color: #ecf5ff;
            color: #409eff;
            border-left: 4px solid #409eff;
        }
        .layui-btn {
            margin-right: 10px;
        }
        pre {
            background-color: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            font-size: 13px;
            line-height: 1.5;
            margin: 10px 0;
        }
        .layui-form-label {
            width: 120px;
        }
        .layui-input-block {
            margin-left: 150px;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <h1 class="setup-title">题库系统 - 安装向导</h1>
        
        <?php
        // 安装锁定文件
        $install_lock_file = 'install.lock';
        
        // 检查是否已安装
        if (file_exists($install_lock_file)) {
            echo '<div class="status error">系统已安装，请勿重复安装！</div>';
            echo '<div style="margin-top: 20px; text-align: center;"><a href="login.html" class="layui-btn layui-btn-normal">进入登录页面</a></div>';
            exit;
        }
        
        // 安装步骤
        $step = isset($_GET['step']) ? intval($_GET['step']) : 1;
        $message = '';
        $message_type = '';
        $db_config = array();
        $admin_info = array();
        $env_check = array();
        $env_ok = false;
        
        // 步骤导航数据
        $steps = array(
            array('number' => 1, 'text' => '环境检测'),
            array('number' => 2, 'text' => '数据库配置'),
            array('number' => 3, 'text' => '管理员创建'),
            array('number' => 4, 'text' => '数据初始化'),
            array('number' => 5, 'text' => '安装完成')
        );
        
        // 步骤1：环境检测
        if ($step == 1) {
            // 检查PHP版本
            $php_version = phpversion();
            $php_ok = version_compare($php_version, '7.0.0', '>=');
            
            // 检查PDO扩展
            $pdo_ok = extension_loaded('pdo_mysql');
            
            // 检查session扩展
            $session_ok = extension_loaded('session');
            
            // 检查目录权限
            $uploads_ok = is_writable('uploads/');
            
            $env_check = array(
                'PHP版本' => array($php_version, $php_ok, '>= 7.0.0'),
                'PDO扩展' => array($pdo_ok ? '已加载' : '未加载', $pdo_ok, '必须加载'),
                'Session扩展' => array($session_ok ? '已加载' : '未加载', $session_ok, '必须加载'),
                'uploads目录权限' => array($uploads_ok ? '可写' : '不可写', $uploads_ok, '必须可写')
            );
            
            // 检查是否所有环境条件都满足
            $env_ok = true;
            foreach ($env_check as $check) {
                if (!$check[1]) {
                    $env_ok = false;
                    break;
                }
            }
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                if (!$env_ok) {
                    $message = '环境检测未通过，请修复后重试';
                    $message_type = 'error';
                } else {
                    // 环境检测通过，跳转到下一步
                    header('Location: setup.php?step=2');
                    exit;
                }
            }
        }
        
        // 步骤2：数据库配置
        if ($step == 2) {
            // 处理测试连接请求
            if (isset($_POST['test_db']) && $_POST['test_db'] == '1') {
                // 确保没有输出任何内容
                ob_clean();
                
                // 获取表单数据
                $test_config = array(
                    'host' => isset($_POST['db_host']) ? trim($_POST['db_host']) : 'localhost',
                    'db_name' => isset($_POST['db_name']) ? trim($_POST['db_name']) : '',
                    'username' => isset($_POST['db_username']) ? trim($_POST['db_username']) : 'root',
                    'password' => isset($_POST['db_password']) ? trim($_POST['db_password']) : '',
                    'prefix' => isset($_POST['db_prefix']) ? trim($_POST['db_prefix']) : 'tiku_'
                );
                
                // 测试数据库连接
                $test_result = array('code' => 1, 'msg' => '连接失败');
                
                try {
                    // 先连接到MySQL服务器
                    $dsn = "mysql:host={$test_config['host']};charset=utf8mb4;connect_timeout=10";
                    $conn = new PDO($dsn, $test_config['username'], $test_config['password'], array(
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_PERSISTENT => false,
                        PDO::ATTR_TIMEOUT => 30,
                        PDO::ATTR_EMULATE_PREPARES => false,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                    ));
                    
                    // 如果提供了数据库名称，尝试连接到该数据库
                    if (!empty($test_config['db_name'])) {
                        $conn->exec("USE `{$test_config['db_name']}`");
                        $test_result = array('code' => 0, 'msg' => '数据库连接成功！');
                    } else {
                        $test_result = array('code' => 0, 'msg' => 'MySQL服务器连接成功！');
                    }
                } catch (PDOException $e) {
                    $test_result['msg'] = '连接失败，请检查数据库地址、用户名和密码是否正确！';
                }
                
                // 设置CORS头（如果需要）
                header('Access-Control-Allow-Origin: *');
                // 设置JSON响应头
                header('Content-Type: application/json; charset=utf-8');
                // 确保没有其他输出
                header('Cache-Control: no-cache, must-revalidate');
                header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
                // 输出JSON结果
                echo json_encode($test_result);
                // 确保立即输出
                flush();
                exit;
            }
            
            // 处理表单提交
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!isset($_POST['test_db']) || $_POST['test_db'] != '1')) {
                // 获取表单数据
                $db_config = array(
                    'host' => isset($_POST['db_host']) ? trim($_POST['db_host']) : 'localhost',
                    'db_name' => isset($_POST['db_name']) ? trim($_POST['db_name']) : '',
                    'username' => isset($_POST['db_username']) ? trim($_POST['db_username']) : 'root',
                    'password' => isset($_POST['db_password']) ? trim($_POST['db_password']) : '',
                    'prefix' => isset($_POST['db_prefix']) ? trim($_POST['db_prefix']) : 'tiku_'
                );
                
                // 验证表单数据
                $errors = array();
                if (empty($db_config['host'])) {
                    $errors[] = '数据库主机地址不能为空';
                }
                if (empty($db_config['username'])) {
                    $errors[] = '数据库用户名不能为空';
                }
                if (empty($db_config['prefix'])) {
                    $errors[] = '数据表前缀不能为空';
                }
                
                if (empty($errors)) {
                    // 尝试连接数据库
                    try {
                        $dsn = "mysql:host={$db_config['host']};charset=utf8mb4;connect_timeout=10";
                        $conn = new PDO($dsn, $db_config['username'], $db_config['password'], array(
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_PERSISTENT => false,
                            PDO::ATTR_TIMEOUT => 30,
                            PDO::ATTR_EMULATE_PREPARES => false,
                            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                        ));
                        
                        // 如果提供了数据库名称，检查是否存在，不存在则创建
                        if (!empty($db_config['db_name'])) {
                            $conn->exec("CREATE DATABASE IF NOT EXISTS `{$db_config['db_name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                            $conn->exec("USE `{$db_config['db_name']}`");
                        } else {
                            $errors[] = '数据库名称不能为空';
                        }
                        
                        if (empty($errors)) {
                            // 检查是否已存在指定前缀的表
                            $prefix = $conn->quote($db_config['prefix'] . '%');
                            $stmt = $conn->query("SHOW TABLES LIKE $prefix");
                            if ($stmt->rowCount() > 0) {
                                $errors[] = '数据库中已存在以"' . $db_config['prefix'] . '"为前缀的表，请更换表前缀或清空数据库后重新安装！';
                            } else {
                                // 保存数据库配置到会话
                                session_start();
                                $_SESSION['db_config'] = $db_config;
                                
                                // 跳转到下一步
                                header('Location: setup.php?step=3');
                                exit;
                            }
                        }
                    } catch (PDOException $e) {
                        // 友好的错误提示
                        $errors[] = '数据库连接失败，请检查数据库地址、用户名和密码是否正确！';
                    }
                }
                
                if (!empty($errors)) {
                    $message = implode('<br>', $errors);
                    $message_type = 'error';
                }
            }
        }
        
        // 步骤3：管理员创建
        if ($step == 3) {
            // 检查会话中的数据库配置
            session_start();
            if (!isset($_SESSION['db_config'])) {
                header('Location: setup.php?step=1');
                exit;
            }
            $db_config = $_SESSION['db_config'];
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // 获取表单数据
                $admin_info = array(
                    'username' => isset($_POST['admin_username']) ? trim($_POST['admin_username']) : 'admin',
                    'email' => isset($_POST['admin_email']) ? trim($_POST['admin_email']) : '',
                    'password' => isset($_POST['admin_password']) ? trim($_POST['admin_password']) : '',
                    'confirm_password' => isset($_POST['admin_confirm_password']) ? trim($_POST['admin_confirm_password']) : ''
                );
                
                // 验证表单数据
                $errors = array();
                if (empty($admin_info['username'])) {
                    $errors[] = '管理员用户名不能为空';
                } elseif (strlen($admin_info['username']) < 3 || strlen($admin_info['username']) > 50) {
                    $errors[] = '管理员用户名长度必须在3-50个字符之间';
                }
                
                if (empty($admin_info['email'])) {
                    $errors[] = '管理员邮箱不能为空';
                } elseif (!filter_var($admin_info['email'], FILTER_VALIDATE_EMAIL)) {
                    $errors[] = '管理员邮箱格式不正确';
                }
                
                if (empty($admin_info['password'])) {
                    $errors[] = '管理员密码不能为空';
                } elseif (strlen($admin_info['password']) < 6 || strlen($admin_info['password']) > 20) {
                    $errors[] = '管理员密码长度必须在6-20个字符之间';
                }
                
                if (empty($admin_info['confirm_password'])) {
                    $errors[] = '确认密码不能为空';
                } elseif ($admin_info['password'] !== $admin_info['confirm_password']) {
                    $errors[] = '两次输入的密码不一致';
                }
                
                if (empty($errors)) {
                    // 保存管理员信息到会话
                    $_SESSION['admin_info'] = $admin_info;
                    
                    // 跳转到下一步
                    header('Location: setup.php?step=4');
                    exit;
                }
                
                if (!empty($errors)) {
                    $message = implode('<br>', $errors);
                    $message_type = 'error';
                }
            }
        }
        
        // 步骤4：数据初始化
        if ($step == 4) {
            // 检查会话中的配置
            session_start();
            if (!isset($_SESSION['db_config']) || !isset($_SESSION['admin_info'])) {
                header('Location: setup.php?step=1');
                exit;
            }
            $db_config = $_SESSION['db_config'];
            $admin_info = $_SESSION['admin_info'];
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                try {
                    // 连接数据库
                    $dsn = "mysql:host={$db_config['host']};dbname={$db_config['db_name']};charset=utf8mb4;connect_timeout=10";
                    $conn = new PDO($dsn, $db_config['username'], $db_config['password'], array(
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_PERSISTENT => false,
                        PDO::ATTR_TIMEOUT => 30,
                        PDO::ATTR_EMULATE_PREPARES => false,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                    ));
                    
                    // 使用嵌入式SQL语句，避免文件读取和分割问题
                    $sql_statements = array();
                    
                    // 创建用户表
                    $sql_statements[] = "CREATE TABLE IF NOT EXISTS `{$db_config['prefix']}users` (
                      `id` INT(11) NOT NULL AUTO_INCREMENT,
                      `username` VARCHAR(50) NOT NULL,
                      `email` VARCHAR(100) NOT NULL,
                      `password` VARCHAR(255) NOT NULL,
                      `phone` VARCHAR(20) DEFAULT NULL,
                      `avatar` VARCHAR(255) DEFAULT NULL,
                      `status` TINYINT(1) DEFAULT '1' COMMENT '1：正常，0：禁用',
                      `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                      `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                      PRIMARY KEY (`id`),
                      UNIQUE KEY `username` (`username`),
                      UNIQUE KEY `email` (`email`),
                      KEY `status` (`status`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户表'";
                    
                    // 创建题目分类表
                    $sql_statements[] = "CREATE TABLE IF NOT EXISTS `{$db_config['prefix']}categories` (
                      `id` INT(11) NOT NULL AUTO_INCREMENT,
                      `name` VARCHAR(100) NOT NULL,
                      `description` TEXT DEFAULT NULL,
                      `parent_id` INT(11) DEFAULT '0' COMMENT '父分类ID，0表示顶级分类',
                      `status` TINYINT(1) DEFAULT '1' COMMENT '1：启用，0：禁用',
                      `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                      PRIMARY KEY (`id`),
                      KEY `parent_id` (`parent_id`),
                      KEY `status` (`status`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='题目分类表'";
                    
                    // 创建题目表
                    $sql_statements[] = "CREATE TABLE IF NOT EXISTS `{$db_config['prefix']}questions` (
                      `id` INT(11) NOT NULL AUTO_INCREMENT,
                      `category_id` INT(11) NOT NULL,
                      `type` TINYINT(1) NOT NULL COMMENT '1：单选，2：多选，3：判断，4：填空，5：简答',
                      `content` TEXT NOT NULL COMMENT '题目内容',
                      `answer` TEXT DEFAULT NULL COMMENT '答案（填空、简答题直接存储答案）',
                      `analysis` TEXT DEFAULT NULL COMMENT '解析',
                      `difficulty` TINYINT(1) DEFAULT '2' COMMENT '1：简单，2：中等，3：困难',
                      `status` TINYINT(1) DEFAULT '1' COMMENT '1：启用，0：禁用',
                      `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                      `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                      PRIMARY KEY (`id`),
                      KEY `category_id` (`category_id`),
                      KEY `type` (`type`),
                      KEY `difficulty` (`difficulty`),
                      KEY `status` (`status`),
                      CONSTRAINT `{$db_config['prefix']}questions_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `{$db_config['prefix']}categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='题目表'";
                    
                    // 创建选项表
                    $sql_statements[] = "CREATE TABLE IF NOT EXISTS `{$db_config['prefix']}options` (
                      `id` INT(11) NOT NULL AUTO_INCREMENT,
                      `question_id` INT(11) NOT NULL,
                      `option_content` TEXT NOT NULL COMMENT '选项内容',
                      `option_letter` VARCHAR(10) NOT NULL COMMENT '选项字母（A, B, C, D...）',
                      `is_correct` TINYINT(1) DEFAULT '0' COMMENT '1：正确答案，0：错误答案',
                      PRIMARY KEY (`id`),
                      KEY `question_id` (`question_id`),
                      KEY `is_correct` (`is_correct`),
                      CONSTRAINT `{$db_config['prefix']}options_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `{$db_config['prefix']}questions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='选项表'";
                    
                    // 创建用户答题记录表
                    $sql_statements[] = "CREATE TABLE IF NOT EXISTS `{$db_config['prefix']}answers` (
                      `id` INT(11) NOT NULL AUTO_INCREMENT,
                      `user_id` INT(11) NOT NULL,
                      `question_id` INT(11) NOT NULL,
                      `answer` TEXT NOT NULL COMMENT '用户答案',
                      `is_correct` TINYINT(1) NOT NULL COMMENT '1：正确，0：错误',
                      `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                      PRIMARY KEY (`id`),
                      KEY `user_id` (`user_id`),
                      KEY `question_id` (`question_id`),
                      KEY `is_correct` (`is_correct`),
                      CONSTRAINT `{$db_config['prefix']}answers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `{$db_config['prefix']}users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                      CONSTRAINT `{$db_config['prefix']}answers_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `{$db_config['prefix']}questions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户答题记录表'";
                    
                    // 创建错题记录表
                    $sql_statements[] = "CREATE TABLE IF NOT EXISTS `{$db_config['prefix']}wrong_questions` (
                      `id` INT(11) NOT NULL AUTO_INCREMENT,
                      `user_id` INT(11) NOT NULL,
                      `question_id` INT(11) NOT NULL,
                      `answer` TEXT NOT NULL COMMENT '用户答案',
                      `correct_answer` TEXT NOT NULL COMMENT '正确答案',
                      `reviewed` TINYINT(1) DEFAULT '0' COMMENT '1：已复习，0：未复习',
                      `reviewed_at` DATETIME DEFAULT NULL,
                      `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                      PRIMARY KEY (`id`),
                      KEY `user_id` (`user_id`),
                      KEY `question_id` (`question_id`),
                      KEY `reviewed` (`reviewed`),
                      CONSTRAINT `{$db_config['prefix']}wrong_questions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `{$db_config['prefix']}users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                      CONSTRAINT `{$db_config['prefix']}wrong_questions_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `{$db_config['prefix']}questions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='错题记录表'";
                    
                    // 创建系统设置表
                    $sql_statements[] = "CREATE TABLE IF NOT EXISTS `{$db_config['prefix']}system_settings` (
                      `id` INT(11) NOT NULL AUTO_INCREMENT,
                      `key` VARCHAR(100) NOT NULL,
                      `value` TEXT DEFAULT NULL,
                      `description` VARCHAR(255) DEFAULT NULL,
                      `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                      `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                      PRIMARY KEY (`id`),
                      UNIQUE KEY `key` (`key`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统设置表'";
                    
                    // 插入默认分类
                    $sql_statements[] = "INSERT INTO `{$db_config['prefix']}categories` (`name`, `description`, `parent_id`, `status`) VALUES
                    ('默认分类', '系统默认分类', 0, 1),
                    ('数学', '数学相关题目', 0, 1),
                    ('英语', '英语相关题目', 0, 1),
                    ('物理', '物理相关题目', 0, 1),
                    ('化学', '化学相关题目', 0, 1)";
                    
                    // 插入默认系统设置
                    $sql_statements[] = "INSERT INTO `{$db_config['prefix']}system_settings` (`key`, `value`, `description`) VALUES
                    ('site_name', '题库系统', '网站名称'),
                    ('site_description', '智能题库管理系统', '网站描述'),
                    ('max_upload_size', '10485760', '最大上传文件大小（字节）'),
                    ('allowed_file_types', 'doc,docx,xls,xlsx,csv,txt', '允许上传的文件类型'),
                    ('questions_per_page', '20', '每页显示题目数量')";
                    
                    // 插入测试用户
                    $sql_statements[] = "INSERT INTO `{$db_config['prefix']}users` (`username`, `email`, `password`, `status`) VALUES
                    ('test', 'test@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1)";
                    
                    // 执行SQL语句
                    $conn->beginTransaction();
                    $success_count = 0;
                    
                    error_log('准备执行 ' . count($sql_statements) . ' 条SQL语句');
                    
                    foreach ($sql_statements as $index => $sql) {
                        error_log('执行第 ' . ($index + 1) . ' 条SQL语句');
                        
                        try {
                            $affected_rows = $conn->exec($sql);
                            $success_count++;
                            error_log('执行成功！影响行数: ' . $affected_rows);
                        } catch (PDOException $e) {
                            error_log('SQL执行失败: ' . $e->getMessage());
                            error_log('失败的SQL语句: ' . $sql);
                            $conn->rollBack();
                            throw new Exception('SQL执行失败：' . $e->getMessage() . '<br>失败的SQL语句：' . substr($sql, 0, 200) . '...');
                        }
                    }
                    
                    error_log('成功执行了 ' . $success_count . ' 条SQL语句');
                    error_log('当前数据库: ' . $db_config['db_name']);
                    error_log('当前表前缀: ' . $db_config['prefix']);
                    
                    // 创建管理员用户
                    $hashed_password = password_hash($admin_info['password'], PASSWORD_DEFAULT);
                    $admin_sql = "INSERT INTO `{$db_config['prefix']}users` (`username`, `email`, `password`, `status`) VALUES (:username, :email, :password, 1)";
                    $stmt = $conn->prepare($admin_sql);
                    $stmt->bindValue(':username', $admin_info['username']);
                    $stmt->bindValue(':email', $admin_info['email']);
                    $stmt->bindValue(':password', $hashed_password);
                    $stmt->execute();
                    
                    $conn->commit();
                    
                    // 更新db.php文件
                    $db_file_content = file_get_contents('db.php');
                    $db_file_content = preg_replace('/private \$host = \'[^\']*\';/', "private \$host = '{$db_config['host']}';", $db_file_content);
                    $db_file_content = preg_replace('/private \$db_name = \'[^\']*\';/', "private \$db_name = '{$db_config['db_name']}';", $db_file_content);
                    $db_file_content = preg_replace('/private \$username = \'[^\']*\';/', "private \$username = '{$db_config['username']}';", $db_file_content);
                    $db_file_content = preg_replace('/private \$password = \'[^\']*\';/', "private \$password = '{$db_config['password']}';", $db_file_content);
                    $db_file_content = preg_replace('/private \$prefix = \'[^\']*\';/', "private \$prefix = '{$db_config['prefix']}';", $db_file_content);
                    file_put_contents('db.php', $db_file_content);
                    
                    // 跳转到安装完成页面
                    header('Location: setup.php?step=5');
                    exit;
                    
                } catch (Exception $e) {
                    $message = '数据初始化失败：' . $e->getMessage();
                    $message_type = 'error';
                }
            }
        }
        
        // 步骤5：安装完成
        if ($step == 5) {
            // 检查是否已安装
            if (file_exists($install_lock_file)) {
                echo '<div class="status error">系统已安装，请勿重复安装！</div>';
                echo '<div style="margin-top: 20px; text-align: center;"><a href="login.html" class="layui-btn layui-btn-normal">进入登录页面</a></div>';
                exit;
            }
            
            // 生成安装锁定文件
            file_put_contents($install_lock_file, '');
            
            // 清理会话
            session_start();
            $admin_info = $_SESSION['admin_info'];
            session_destroy();
        }
        
        // 获取当前步骤的导航状态
        function getStepStatus($current_step, $step_num) {
            if ($current_step == $step_num) {
                return 'active';
            } elseif ($current_step > $step_num) {
                return 'done';
            } else {
                return '';
            }
        }
        
        // 获取当前步骤的导航文本状态
        function getStepTextStatus($current_step, $step_num) {
            if ($current_step == $step_num) {
                return 'active';
            } elseif ($current_step > $step_num) {
                return 'done';
            } else {
                return '';
            }
        }
        ?>
        
        <!-- 步骤导航 -->
        <div class="step-nav">
            <?php foreach ($steps as $step_item): ?>
                <div class="step-item">
                    <div class="step-number <?php echo getStepStatus($step, $step_item['number']); ?>">
                        <?php echo $step_item['number']; ?>
                    </div>
                    <div class="step-text <?php echo getStepTextStatus($step, $step_item['number']); ?>">
                        <?php echo $step_item['text']; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- 步骤1：环境检测 -->
        <?php if ($step == 1): ?>
            <div class="step">
                <div class="step-title">步骤1：环境检测</div>
                <div class="step-content">
                    <?php if (!empty($message)): ?>
                        <div class="status <?php echo $message_type; ?>">
                            <?php echo $message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <p>正在检测系统环境...</p>
                    <table class="layui-table">
                        <thead>
                            <tr>
                                <th>检查项</th>
                                <th>当前状态</th>
                                <th>要求</th>
                                <th>结果</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($env_check as $name => $check): ?>
                            <tr>
                                <td><?php echo $name; ?></td>
                                <td><?php echo $check[0]; ?></td>
                                <td><?php echo $check[2]; ?></td>
                                <td>
                                    <?php if ($check[1]): ?>
                                        <i class="layui-icon layui-icon-ok-circle" style="color: #67c23a;"></i> 合格
                                    <?php else: ?>
                                        <i class="layui-icon layui-icon-close-circle" style="color: #f56c6c;"></i> 不合格
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <form class="layui-form" action="setup.php?step=1" method="post">
                        <div class="layui-form-item">
                            <div class="layui-input-block">
                                <button class="layui-btn" lay-submit lay-filter="env_check" <?php echo !$env_ok ? 'disabled' : ''; ?>>环境检测通过，下一步</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- 步骤2：数据库配置 -->
        <?php if ($step == 2): ?>
            <div class="step">
                <div class="step-title">步骤2：数据库配置</div>
                <div class="step-content">
                    <?php if (!empty($message)): ?>
                        <div class="status <?php echo $message_type; ?>">
                            <?php echo $message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form class="layui-form" action="setup.php?step=2" method="post" id="db_form">
                        <div class="layui-form-item">
                            <label class="layui-form-label">数据库主机</label>
                            <div class="layui-input-block">
                                <input type="text" name="db_host" placeholder="请输入数据库主机地址" autocomplete="off" class="layui-input" value="<?php echo isset($db_config['host']) ? $db_config['host'] : 'localhost'; ?>">
                            </div>
                        </div>
                        <div class="layui-form-item">
                            <label class="layui-form-label">数据库名称</label>
                            <div class="layui-input-block">
                                <input type="text" name="db_name" placeholder="请输入数据库名称" autocomplete="off" class="layui-input" value="<?php echo isset($db_config['db_name']) ? $db_config['db_name'] : ''; ?>">
                            </div>
                        </div>
                        <div class="layui-form-item">
                            <label class="layui-form-label">数据库用户名</label>
                            <div class="layui-input-block">
                                <input type="text" name="db_username" placeholder="请输入数据库用户名" autocomplete="off" class="layui-input" value="<?php echo isset($db_config['username']) ? $db_config['username'] : 'root'; ?>">
                            </div>
                        </div>
                        <div class="layui-form-item">
                            <label class="layui-form-label">数据库密码</label>
                            <div class="layui-input-block">
                                <input type="password" name="db_password" placeholder="请输入数据库密码" autocomplete="off" class="layui-input" value="<?php echo isset($db_config['password']) ? $db_config['password'] : ''; ?>">
                            </div>
                        </div>
                        <div class="layui-form-item">
                            <label class="layui-form-label">数据表前缀</label>
                            <div class="layui-input-block">
                                <input type="text" name="db_prefix" placeholder="请输入数据表前缀" autocomplete="off" class="layui-input" value="<?php echo isset($db_config['prefix']) ? $db_config['prefix'] : 'tiku_'; ?>">
                            </div>
                        </div>
                        <div class="layui-form-item">
                            <div class="layui-input-block">
                                <button type="button" class="layui-btn layui-btn-primary" id="test_db_btn">测试连接</button>
                                <button class="layui-btn" lay-submit lay-filter="db_config">下一步</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- 步骤3：管理员创建 -->
        <?php if ($step == 3): ?>
            <div class="step">
                <div class="step-title">步骤3：管理员创建</div>
                <div class="step-content">
                    <?php if (!empty($message)): ?>
                        <div class="status <?php echo $message_type; ?>">
                            <?php echo $message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form class="layui-form" action="setup.php?step=3" method="post">
                        <div class="layui-form-item">
                            <label class="layui-form-label">管理员用户名</label>
                            <div class="layui-input-block">
                                <input type="text" name="admin_username" required lay-verify="required" placeholder="请输入管理员用户名" autocomplete="off" class="layui-input" value="admin">
                            </div>
                        </div>
                        <div class="layui-form-item">
                            <label class="layui-form-label">管理员邮箱</label>
                            <div class="layui-input-block">
                                <input type="email" name="admin_email" required lay-verify="required|email" placeholder="请输入管理员邮箱" autocomplete="off" class="layui-input">
                            </div>
                        </div>
                        <div class="layui-form-item">
                            <label class="layui-form-label">管理员密码</label>
                            <div class="layui-input-block">
                                <input type="password" name="admin_password" required lay-verify="required" placeholder="请输入管理员密码" autocomplete="off" class="layui-input">
                            </div>
                        </div>
                        <div class="layui-form-item">
                            <label class="layui-form-label">确认密码</label>
                            <div class="layui-input-block">
                                <input type="password" name="admin_confirm_password" required lay-verify="required" placeholder="请再次输入密码" autocomplete="off" class="layui-input">
                            </div>
                        </div>
                        <div class="layui-form-item">
                            <div class="layui-input-block">
                                <a href="setup.php?step=2" class="layui-btn layui-btn-primary">上一步</a>
                                <button class="layui-btn" lay-submit lay-filter="create_admin">下一步</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- 步骤4：数据初始化 -->
        <?php if ($step == 4): ?>
            <div class="step">
                <div class="step-title">步骤4：数据初始化</div>
                <div class="step-content">
                    <?php if (!empty($message)): ?>
                        <div class="status <?php echo $message_type; ?>">
                            <?php echo $message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <p>确认执行数据库初始化？</p>
                    <p>执行后将创建以下数据表：</p>
                    <ul style="margin: 10px 0; padding-left: 20px;">
                        <li><?php echo $db_config['prefix']; ?>users - 用户表</li>
                        <li><?php echo $db_config['prefix']; ?>categories - 题目分类表</li>
                        <li><?php echo $db_config['prefix']; ?>questions - 题目表</li>
                        <li><?php echo $db_config['prefix']; ?>options - 选项表</li>
                        <li><?php echo $db_config['prefix']; ?>answers - 用户答题记录表</li>
                        <li><?php echo $db_config['prefix']; ?>wrong_questions - 错题记录表</li>
                        <li><?php echo $db_config['prefix']; ?>system_settings - 系统设置表</li>
                    </ul>
                    
                    <form class="layui-form" action="setup.php?step=4" method="post">
                        <div class="layui-form-item">
                            <div class="layui-input-block">
                                <a href="setup.php?step=3" class="layui-btn layui-btn-primary">上一步</a>
                                <button class="layui-btn layui-btn-normal" lay-submit lay-filter="init_data">确认执行</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- 步骤5：安装完成 -->
        <?php if ($step == 5): ?>
            <div class="step">
                <div class="step-title">步骤5：安装完成</div>
                <div class="step-content">
                    <div class="status success">
                        <i class="layui-icon layui-icon-ok-circle"></i> 恭喜！题库系统安装成功！
                    </div>
                    
                    <h3>安装信息</h3>
                    <ul style="margin: 10px 0; padding-left: 20px;">
                        <li>系统访问地址：<a href="http://localhost/tiku/login.html" target="_blank">http://localhost/tiku/login.html</a></li>
                        <li>管理员用户名：<?php echo $admin_info['username']; ?></li>
                        <li>管理员邮箱：<?php echo $admin_info['email']; ?></li>
                    </ul>
                    
                    <h3>下一步操作</h3>
                    <ul style="margin: 10px 0; padding-left: 20px;">
                        <li>登录系统，开始使用题库功能</li>
                        <li>修改管理员密码，确保账户安全</li>
                        <li>通过数据导入功能添加题目</li>
                        <li>创建题目分类，组织题库内容</li>
                    </ul>
                    
                    <div style="margin-top: 20px; text-align: center;">
                        <a href="login.html" class="layui-btn layui-btn-normal">进入登录页面</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- 引入jQuery -->
    <script src="https://lib.baomitu.com/jquery/3.6.0/jquery.min.js"></script>
    <!-- 引入layui JS -->
    <script src="https://lib.baomitu.com/layui/2.8.18/layui.js"></script>
    <script>
        // 确保DOM加载完成后执行
        $(document).ready(function(){
            // 初始化layui
            layui.use(['form', 'layer'], function(){
                var form = layui.form;
                var layer = layui.layer;
                
                form.render();
            });
            
            // 测试数据库连接
            $('#test_db_btn').on('click', function(){
                layer.load(1, {
                    shade: [0.5, '#000']
                });
                
                // 获取表单数据
                var formData = $('#db_form').serializeArray();
                var data = {};
                $.each(formData, function(index, item){
                    data[item.name] = item.value;
                });
                data['test_db'] = 1;
                
                // 发送AJAX请求
                $.ajax({
                    url: 'setup.php?step=2',
                    type: 'POST',
                    dataType: 'json',
                    data: data,
                    success: function(res){
                        layui.layer.closeAll('loading');
                        layui.layer.msg(res.msg, {
                            icon: res.code === 0 ? 1 : 5,
                            time: res.code === 0 ? 2000 : 3000
                        });
                    },
                    error: function(){
                        layui.layer.closeAll('loading');
                        layui.layer.msg('网络错误，请稍后重试', {
                            icon: 5,
                            time: 2000
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>