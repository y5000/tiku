<?php
/**
 * 创建题库表
 */

require_once 'db.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    echo "数据库连接成功！\n";
    
    // 创建题库表
    $sql = "CREATE TABLE IF NOT EXISTS `tiku_banks` (
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
    echo "tiku_banks表创建成功！\n";
    
    // 插入初始数据
    $sql = "INSERT INTO `tiku_banks` (`name`, `description`, `status`) VALUES
    ('默认题库', '系统默认题库', 1),
    ('测试题库', '用于测试的题库', 1);";
    
    $conn->exec($sql);
    echo "初始数据插入成功！\n";
    
    // 验证创建结果
    $stmt = $conn->query("SELECT * FROM tiku_banks");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n创建的题库数据：\n";
    foreach ($data as $row) {
        echo "- ID: {$row['id']}, 名称: {$row['name']}, 描述: {$row['description']}, 状态: {$row['status']}\n";
    }
    
} catch (Exception $e) {
    echo "错误：" . $e->getMessage() . "\n";
}
?>