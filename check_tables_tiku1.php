<?php
/**
 * 检查tiku1_前缀的表结构
 */

require_once 'db.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    echo "数据库连接成功！\n";
    
    // 检查所有tiku1_前缀的表
    $stmt = $conn->query("SHOW TABLES LIKE 'tiku1_%'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "tiku1_前缀的表：\n";
    foreach ($tables as $table) {
        echo "- $table\n";
    }
    
    // 检查tiku1_banks表是否存在
    if (in_array('tiku1_banks', $tables)) {
        echo "\ntiku1_banks表存在！\n";
    } else {
        echo "\ntiku1_banks表不存在，需要创建！\n";
        
        // 创建tiku1_banks表
        $sql = "CREATE TABLE IF NOT EXISTS `tiku1_banks` (
          `id` INT(11) NOT NULL AUTO_INCREMENT,
          `name` VARCHAR(100) NOT NULL COMMENT '题库名称',
          `description` TEXT DEFAULT NULL COMMENT '题库描述',
          `status` TINYINT(1) DEFAULT '1' COMMENT '1：启用，0：禁用',
          `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='题库表';";
        
        $conn->exec($sql);
        echo "tiku1_banks表创建成功！\n";
        
        // 插入初始数据
        $sql = "INSERT INTO `tiku1_banks` (`name`, `description`, `status`) VALUES
        ('默认题库', '系统默认题库', 1),
        ('测试题库', '用于测试的题库', 1);";
        
        $conn->exec($sql);
        echo "初始数据插入成功！\n";
    }
    
    // 检查tiku1_questions表结构
    $stmt = $conn->query("DESCRIBE tiku1_questions");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\ntiku1_questions表结构：\n";
    $has_bank_id = false;
    $has_options = false;
    
    foreach ($columns as $column) {
        echo "- {$column['Field']} ({$column['Type']}) {$column['Null']} {$column['Key']} {$column['Default']} {$column['Extra']}\n";
        if ($column['Field'] === 'bank_id') {
            $has_bank_id = true;
        }
        if ($column['Field'] === 'options') {
            $has_options = true;
        }
    }
    
    // 如果缺少字段，添加字段
    if (!$has_bank_id) {
        echo "\n缺少bank_id字段，添加中...\n";
        $sql = "ALTER TABLE `tiku1_questions` ADD COLUMN `bank_id` INT(11) DEFAULT '0' COMMENT '题库ID，0表示默认题库' AFTER `id`;";
        $conn->exec($sql);
        echo "添加bank_id字段成功！\n";
        
        // 添加索引
        $sql = "ALTER TABLE `tiku1_questions` ADD INDEX `bank_id` (`bank_id`);";
        $conn->exec($sql);
        echo "添加bank_id索引成功！\n";
    }
    
    if (!$has_options) {
        echo "\n缺少options字段，添加中...\n";
        $sql = "ALTER TABLE `tiku1_questions` ADD COLUMN `options` TEXT DEFAULT NULL COMMENT '选项，格式：A|选项内容|0,B|选项内容|1' AFTER `content`;";
        $conn->exec($sql);
        echo "添加options字段成功！\n";
    }
    
    echo "\n所有检查和更新完成！\n";
    
} catch (Exception $e) {
    echo "错误：" . $e->getMessage() . "\n";
}
?>