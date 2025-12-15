<?php
/**
 * 更新数据库表结构，添加收藏功能支持
 */

// 引入数据库连接类
require_once 'db.php';

// 创建数据库连接
$db = new Database();

// 获取表前缀
$prefix = $db->getPrefix();

// SQL语句：修改wrong_questions表，添加type字段
$sql = "ALTER TABLE `{$prefix}wrong_questions` 
        ADD COLUMN `type` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '0：错题，1：收藏' AFTER `reviewed_at`";

// 执行SQL语句
try {
    $db->query($sql);
    echo "成功在{$prefix}wrong_questions表中添加type字段！\n";
    echo "字段说明：0-错题，1-收藏\n";
    
    // 更新表注释
    $sql = "ALTER TABLE `{$prefix}wrong_questions` 
            COMMENT='错题与收藏记录表'";
    $db->query($sql);
    echo "成功更新表注释！\n";
    
} catch (Exception $e) {
    echo "执行SQL语句失败：" . $e->getMessage() . "\n";
    echo "SQL语句：$sql\n";
    exit(1);
}

echo "\n数据库表结构更新成功！\n";
?>