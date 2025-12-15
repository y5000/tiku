<?php
/**
 * 检查数据库表结构
 */

require_once 'db.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    echo "数据库连接成功！\n";
    
    // 检查所有tiku_前缀的表
    $stmt = $conn->query("SHOW TABLES LIKE 'tiku_%'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "数据库中的表：\n";
    foreach ($tables as $table) {
        echo "- $table\n";
    }
    
    // 检查tiku_banks表是否存在
    if (in_array('tiku_banks', $tables)) {
        echo "\ntiku_banks表存在！\n";
        
        // 查看表结构
        $stmt = $conn->query("DESCRIBE tiku_banks");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "tiku_banks表结构：\n";
        foreach ($columns as $column) {
            echo "- {$column['Field']} ({$column['Type']}) {$column['Null']} {$column['Key']} {$column['Default']} {$column['Extra']}\n";
        }
        
        // 查询数据
        $stmt = $conn->query("SELECT * FROM tiku_banks");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "\ntiku_banks表数据：\n";
        foreach ($data as $row) {
            echo "- ID: {$row['id']}, 名称: {$row['name']}, 描述: {$row['description']}\n";
        }
    } else {
        echo "\ntiku_banks表不存在！\n";
    }
    
} catch (Exception $e) {
    echo "错误：" . $e->getMessage() . "\n";
}
?>