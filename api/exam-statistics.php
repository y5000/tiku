<?php
/**
 * 考试统计API，用于获取考试相关的统计数据
 */

header('Content-Type: application/json; charset=utf-8');

require_once '../db.php';

$db = new Database();
$response = array(
    'code' => 200,
    'msg' => '操作成功',
    'data' => array()
);

try {
    // 获取当前用户ID，实际应用中应从登录状态获取
    $user_id = 1; // 这里暂时硬编码为1
    
    // 获取今天的日期（格式：YYYY-MM-DD）
    $today = date('Y-m-d');
    
    // 查询统计数据
    
    // 1. 总练习次数
    $total_batches = $db->query("SELECT COUNT(*) as count FROM {$db->getPrefix()}exam_batches WHERE user_id = :user_id", array(':user_id' => $user_id));
    $total_batches = $total_batches[0]['count'];
    
    // 2. 总答题数和总答对题数
    $question_stats = $db->query("SELECT SUM(answered_questions) as total_questions, SUM(correct_questions) as total_correct FROM {$db->getPrefix()}exam_batches WHERE user_id = :user_id", array(':user_id' => $user_id));
    $total_questions = $question_stats[0]['total_questions'] || 0;
    $total_correct = $question_stats[0]['total_correct'] || 0;
    
    // 3. 计算平均正确率
    $average_score = $total_questions > 0 ? round(($total_correct / $total_questions) * 100) : 0;
    
    // 4. 今日练习次数
    $today_batches = $db->query("SELECT COUNT(*) as count FROM {$db->getPrefix()}exam_batches WHERE user_id = :user_id AND DATE(start_time) = :today", array(
        ':user_id' => $user_id,
        ':today' => $today
    ));
    $today_batches = $today_batches[0]['count'];
    
    // 构造统计数据
    $stats = array(
        'total_batches' => $total_batches,
        'total_questions' => $total_questions,
        'total_correct' => $total_correct,
        'average_score' => $average_score,
        'today_batches' => $today_batches
    );
    
    $response['data'] = $stats;
    
} catch (Exception $e) {
    $response['code'] = 500;
    $response['msg'] = '操作失败：' . $e->getMessage();
}

echo json_encode($response);
