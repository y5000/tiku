<?php
// 测试数据库连接

// 引入数据库连接文件
require_once 'db.php';

echo '<h1>数据库连接测试</h1>';

try {
    echo '<p>正在连接数据库...</p>';
    $db = new Database();
    $conn = $db->getConnection();
    echo '<p style="color: green;">数据库连接成功！</p>';
    
    // 测试查询
    echo '<p>正在测试查询...</p>';
    $users = $db->find('users', array('status' => 1));
    echo '<p style="color: green;">查询成功，共找到 ' . count($users) . ' 个用户</p>';
    
    if (!empty($users)) {
        echo '<h2>用户列表</h2>';
        echo '<table border="1" cellpadding="5" cellspacing="0">';
        echo '<tr><th>ID</th><th>用户名</th><th>邮箱</th><th>状态</th><th>创建时间</th></tr>';
        foreach ($users as $user) {
            echo '<tr>';
            echo '<td>' . $user['id'] . '</td>';
            echo '<td>' . $user['username'] . '</td>';
            echo '<td>' . $user['email'] . '</td>';
            echo '<td>' . ($user['status'] ? '正常' : '禁用') . '</td>';
            echo '<td>' . $user['created_at'] . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }
    
} catch (Exception $e) {
    echo '<p style="color: red;">错误：' . $e->getMessage() . '</p>';
    echo '<p>错误文件：' . $e->getFile() . '</p>';
    echo '<p>错误行号：' . $e->getLine() . '</p>';
    echo '<p>错误堆栈：</p>';
    echo '<pre>' . $e->getTraceAsString() . '</pre>';
}

// 检查数据库配置
$db_config = new ReflectionClass('Database');
$properties = $db_config->getProperties(ReflectionProperty::IS_PRIVATE);

echo '<h2>数据库配置</h2>';
echo '<table border="1" cellpadding="5" cellspacing="0">';
echo '<tr><th>配置项</th><th>值</th></tr>';
foreach ($properties as $property) {
    $property->setAccessible(true);
    $value = $property->getValue(new Database());
    echo '<tr>';
    echo '<td>' . $property->getName() . '</td>';
    echo '<td>' . $value . '</td>';
    echo '</tr>';
}
echo '</table>';
?>