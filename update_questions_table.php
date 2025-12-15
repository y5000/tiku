<?php
/**
 * 更新题目表结构
 */

require_once 'db.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    echo "数据库连接成功！\n";
    
    // 更新题目表，添加bank_id字段
    $sql = "ALTER TABLE `tiku_questions` ADD COLUMN `bank_id` INT(11) DEFAULT '0' COMMENT '题库ID，0表示默认题库' AFTER `id`;";
    $conn->exec($sql);
    echo "添加bank_id字段成功！\n";
    
    // 更新题目表，添加options字段
    $sql = "ALTER TABLE `tiku_questions` ADD COLUMN `options` TEXT DEFAULT NULL COMMENT '选项，格式：A|选项内容|0,B|选项内容|1' AFTER `content`;";
    $conn->exec($sql);
    echo "添加options字段成功！\n";
    
    // 添加索引
    $sql = "ALTER TABLE `tiku_questions` ADD INDEX `bank_id` (`bank_id`);";
    $conn->exec($sql);
    echo "添加bank_id索引成功！\n";
    
    // 查看更新后的表结构
    $stmt = $conn->query("DESCRIBE tiku_questions");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\ntiku_questions表结构：\n";
    foreach ($columns as $column) {
        echo "- {$column['Field']} ({$column['Type']}) {$column['Null']} {$column['Key']} {$column['Default']} {$column['Extra']}\n";
    }
    
    echo "\n题目表更新成功！\n";
    
} catch (Exception $e) {
    echo "错误：" . $e->getMessage() . "\n";
    // 如果是字段已存在的错误，忽略
    if (strpos($e->getMessage(), 'Duplicate column name') === false) {
        echo "错误文件：" . $e->getFile() . "\n";
        echo "错误行号：" . $e->getLine() . "\n";
        echo "错误堆栈：" . $e->getTraceAsString() . "\n";
    } else {
        echo "字段已存在，跳过该操作！\n";
    }
}
?>