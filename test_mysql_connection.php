<?php
// 测试MySQL连接，不指定数据库名称

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo 'MySQL连接测试<br>';

// 尝试不同的密码组合
$passwords = array('', 'root', '123456', '111111');

foreach ($passwords as $password) {
    echo '<br>尝试密码: "' . $password . '"<br>';
    
    try {
        $dsn = 'mysql:host=localhost;charset=utf8mb4;connect_timeout=10';
        $pdo = new PDO($dsn, 'root', $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        
        echo '连接MySQL服务器成功！<br>';
        
        // 列出所有数据库
        $stmt = $pdo->query('SHOW DATABASES');
        $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo '可用数据库列表：<br>';
        foreach ($databases as $db) {
            echo '- ' . $db . '<br>';
        }
        
        // 检查tiku数据库是否存在
        if (in_array('tiku', $databases)) {
            echo 'tiku数据库存在！<br>';
        } else {
            echo 'tiku数据库不存在！<br>';
        }
        
        break; // 连接成功，退出循环
        
    } catch (PDOException $e) {
        echo '连接失败: ' . $e->getMessage() . '<br>';
    }
}

echo '<br>测试完成！<br>';
?>