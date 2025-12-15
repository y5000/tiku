<?php
/**
 * 修复题目表category_id字段问题
 * 将category_id字段设置为允许为空
 */

// 引入数据库连接类
require_once 'db.php';

// 创建数据库连接
$db = new Database();

// 获取表前缀
$prefix = $db->getPrefix();

// SQL语句：修改题目表的category_id字段，允许为空
$sql = "ALTER TABLE `{$prefix}questions` MODIFY COLUMN `category_id` INT(11) DEFAULT NULL COMMENT '分类ID（通过题库关联）'";

// 执行SQL语句
try {
    $db->query($sql);
    echo "成功将{$prefix}questions表的category_id字段修改为允许为空！\n";
    echo "字段默认值设置为NULL，注释更新为：分类ID（通过题库关联）\n";
    
    // 可选：如果需要，可以将现有数据的category_id字段设置为0
    $update_sql = "UPDATE `{$prefix}questions` SET `category_id` = NULL WHERE `category_id` IS NOT NULL";
    $db->query($update_sql);
    echo "成功更新现有数据的category_id字段为NULL\n";
    
} catch (Exception $e) {
    echo "执行SQL语句失败：" . $e->getMessage() . "\n";
    echo "SQL语句：$sql\n";
    exit(1);
}

echo "\n数据库修复成功！\n";
?>