<?php
/**
 * 创建答题记录相关表
 * 包括答题批次表和答题记录表
 */

// 引入数据库连接类
require_once 'db.php';

// 创建数据库连接
$db = new Database();
$conn = $db->getConnection();
$prefix = $db->getPrefix();

echo "开始创建答题记录相关表...\n";

try {
    // 1. 创建答题批次表
    $examBatchesTable = "{$prefix}exam_batches";
    $sql = "CREATE TABLE IF NOT EXISTS `{$examBatchesTable}` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL COMMENT '用户ID',
        `exam_type` varchar(50) NOT NULL COMMENT '答题类型',
        `total_questions` int(11) NOT NULL DEFAULT '0' COMMENT '总计题目数量',
        `answered_questions` int(11) NOT NULL DEFAULT '0' COMMENT '已答题数量',
        `correct_questions` int(11) NOT NULL DEFAULT '0' COMMENT '正确数量',
        `wrong_questions` int(11) NOT NULL DEFAULT '0' COMMENT '错误数量',
        `empty_questions` int(11) NOT NULL DEFAULT '0' COMMENT '空题数量',
        `question_types` varchar(100) DEFAULT NULL COMMENT '题型选择，格式：1,2,3',
        `is_random` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否随机出题，1：是，0：否',
        `start_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '开始时间',
        `end_time` datetime DEFAULT NULL COMMENT '结束时间',
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`),
        KEY `start_time` (`start_time`),
        CONSTRAINT `{$examBatchesTable}_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `{$prefix}users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='答题批次表';";
    
    $conn->exec($sql);
    echo "✓ 创建答题批次表 `{$examBatchesTable}` 成功\n";
    
    // 2. 创建答题记录表
    $examAnswersTable = "{$prefix}exam_answers";
    $sql = "CREATE TABLE IF NOT EXISTS `{$examAnswersTable}` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `batch_id` int(11) NOT NULL COMMENT '答题批次ID',
        `user_id` int(11) NOT NULL COMMENT '用户ID',
        `question_id` int(11) NOT NULL COMMENT '题目ID',
        `user_answer` text COLLATE utf8mb4_unicode_ci COMMENT '用户答案',
        `is_correct` tinyint(1) DEFAULT NULL COMMENT '1：正确，0：错误，NULL：未答',
        `answer_time` int(11) DEFAULT NULL COMMENT '答题时间（秒）',
        `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
        PRIMARY KEY (`id`),
        KEY `batch_id` (`batch_id`),
        KEY `user_id` (`user_id`),
        KEY `question_id` (`question_id`),
        KEY `is_correct` (`is_correct`),
        CONSTRAINT `{$examAnswersTable}_ibfk_1` FOREIGN KEY (`batch_id`) REFERENCES `{$examBatchesTable}` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
        CONSTRAINT `{$examAnswersTable}_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `{$prefix}users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
        CONSTRAINT `{$examAnswersTable}_ibfk_3` FOREIGN KEY (`question_id`) REFERENCES `{$prefix}questions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='答题记录表';";
    
    $conn->exec($sql);
    echo "✓ 创建答题记录表 `{$examAnswersTable}` 成功\n";
    
    echo "\n所有表创建完成！\n";
    
} catch (PDOException $e) {
    echo "✗ 创建表失败：" . $e->getMessage() . "\n";
    exit(1);
}
