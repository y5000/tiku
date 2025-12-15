<?php
/**
 * 答题详情API，用于获取答题记录的详细信息
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
    
    // 获取batch_id参数
    $batch_id = isset($_GET['batch_id']) ? intval($_GET['batch_id']) : 0;
    if (!$batch_id) {
        $response['code'] = 400;
        $response['msg'] = '缺少批次ID';
        echo json_encode($response);
        exit;
    }
    
    // 查询批次基本信息
    $batch_info = $db->find('exam_batches', array('id' => $batch_id, 'user_id' => $user_id));
    if (!$batch_info) {
        $response['code'] = 404;
        $response['msg'] = '批次记录不存在';
        echo json_encode($response);
        exit;
    }
    
    // 查询该批次下的所有答题记录
    $answers = $db->query("SELECT * FROM {$db->getPrefix()}exam_answers WHERE batch_id = :batch_id ORDER BY question_order", array(':batch_id' => $batch_id));
    
    // 关联题目信息
    $detailed_answers = array();
    foreach ($answers as $answer) {
        // 查询题目详情
        $question = $db->find('questions', array('id' => $answer['question_id']));
        if ($question) {
            // 合并答题记录和题目详情
            $detailed_answer = array_merge($answer, $question);
            $detailed_answers[] = $detailed_answer;
        }
    }
    
    $response['data'] = array(
        'batch_info' => $batch_info,
        'answers' => $detailed_answers
    );
} catch (Exception $e) {
    $response['code'] = 500;
    $response['msg'] = '操作失败：' . $e->getMessage();
}

echo json_encode($response);
