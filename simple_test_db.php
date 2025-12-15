<?php
// 简单的数据库连接测试，输出所有错误

// 开启错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo '数据库连接测试<br>';

try {
    echo '正在加载db.php...<br>';
    require_once 'db.php';
    
    echo '正在创建Database对象...<br>';
    $db = new Database();
    
    echo '正在获取连接...<br>';
    $conn = $db->getConnection();
    
    echo '连接成功！<br>';
    
    // 测试简单查询
    echo '正在执行简单查询...<br>';
    $stmt = $conn->query('SELECT 1 as test');
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo '查询成功，结果：' . $result['test'] . '<br>';
    
    // 测试查询users表
    echo '正在查询users表...<br>';
    $stmt = $conn->query('SELECT COUNT(*) as count FROM tiku_users');
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo 'users表共有 ' . $result['count'] . ' 条记录<br>';
    
    echo '测试完成！<br>';
    
} catch (PDOException $e) {
    echo 'PDO错误：' . $e->getMessage() . '<br>';
    echo '错误代码：' . $e->getCode() . '<br>';
    echo '错误文件：' . $e->getFile() . '<br>';
    echo '错误行号：' . $e->getLine() . '<br>';
    echo '错误堆栈：' . $e->getTraceAsString() . '<br>';
} catch (Exception $e) {
    echo '普通错误：' . $e->getMessage() . '<br>';
    echo '错误文件：' . $e->getFile() . '<br>';
    echo '错误行号：' . $e->getLine() . '<br>';
    echo '错误堆栈：' . $e->getTraceAsString() . '<br>';
} catch (Error $e) {
    echo '致命错误：' . $e->getMessage() . '<br>';
    echo '错误文件：' . $e->getFile() . '<br>';
    echo '错误行号：' . $e->getLine() . '<br>';
    echo '错误堆栈：' . $e->getTraceAsString() . '<br>';
}
?>