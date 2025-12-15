<?php
/**
 * 更新题库与分类的关联关系
 * 将题目与分类的关联改为题库与分类的关联
 */

require_once 'db.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    echo "数据库连接成功！\n";
    
    // 1. 在题库表中添加分类关联字段（先不添加NOT NULL约束）
    $sql = "ALTER TABLE `tiku1_banks` ADD COLUMN `category_id` INT(11) DEFAULT NULL COMMENT '分类ID' AFTER `id`;";
    $conn->exec($sql);
    echo "在tiku1_banks表中添加category_id字段成功！\n";
    
    // 2. 更新现有题库的分类关联
    $sql = "UPDATE `tiku1_banks` SET `category_id` = 1;";
    $conn->exec($sql);
    echo "更新现有题库的分类关联成功！\n";
    
    // 3. 添加NOT NULL约束
    $sql = "ALTER TABLE `tiku1_banks` MODIFY COLUMN `category_id` INT(11) NOT NULL COMMENT '分类ID';";
    $conn->exec($sql);
    echo "添加NOT NULL约束成功！\n";
    
    // 4. 添加索引
    $sql = "ALTER TABLE `tiku1_banks` ADD INDEX `category_id` (`category_id`);";
    $conn->exec($sql);
    echo "添加category_id索引成功！\n";
    
    // 5. 尝试添加外键约束（可选，如果失败则忽略，因为不是必须的）
    try {
        $sql = "ALTER TABLE `tiku1_banks` ADD CONSTRAINT `tiku1_banks_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `tiku1_categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;";
        $conn->exec($sql);
        echo "添加外键约束成功！\n";
    } catch (Exception $e) {
        echo "添加外键约束失败：" . $e->getMessage() . "\n";
        echo "注意：外键约束不是必须的，系统可以正常运行。\n";
    }
    
    // 6. 从题目表中移除分类关联（可选，这里选择保留但不再使用）
    // 注意：如果直接删除字段，可能会影响现有数据，所以这里选择保留
    echo "\n注意：为了兼容现有数据，没有直接删除tiku1_questions表中的category_id字段，而是改为不再使用该字段。\n";
    echo "题目将通过题库间接关联分类。\n";
    
    // 4. 查看更新后的表结构
    echo "\ntiku1_banks表结构：\n";
    $stmt = $conn->query("DESCRIBE tiku1_banks");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $column) {
        echo "- {$column['Field']} ({$column['Type']}) {$column['Null']} {$column['Key']} {$column['Default']} {$column['Extra']}\n";
    }
    
    echo "\n所有更新完成！\n";
    echo "现在系统的设计是：题库关联分类，题目关联题库，题目通过题库间接关联分类。\n";
    
} catch (Exception $e) {
    echo "错误：" . $e->getMessage() . "\n";
    // 如果是字段已存在的错误，忽略
    if (strpos($e->getMessage(), 'Duplicate column name') === false) {
        echo "错误文件：" . $e->getFile() . "\n";
        echo "错误行号：" . $e->getLine() . "\n";
    } else {
        echo "字段已存在，跳过该操作！\n";
    }
}
?>