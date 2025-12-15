<?php
/**
 * 检查数据库结构
 */

require 'db.php';

$db = new Database();

try {
    $conn = $db->getConnection();
    echo '数据库连接成功！\n';
    echo '现有表结构：\n';
    echo '========================\n';
    
    // 获取所有表
    $tables = $conn->query('SHOW TABLES');
    foreach ($tables as $table) {
        $tableName = reset($table);
        echo '表名：' . $tableName . '\n';
        echo '字段结构：\n';
        
        // 获取表结构
        $columns = $conn->query('DESCRIBE ' . $tableName);
        foreach ($columns as $column) {
            echo '  - ' . $column['Field'] . ' (' . $column['Type'] . ') ' . 
                 ($column['Null'] === 'NO' ? 'NOT NULL' : '') . ' ' . 
                 $column['Key'] . ' ' . 
                 ($column['Default'] !== null ? 'DEFAULT ' . $column['Default'] : '') . '\n';
        }
        echo '------------------------\n';
    }
    
} catch (Exception $e) {
    echo '数据库连接失败：' . $e->getMessage() . '\n';
}
