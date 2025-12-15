<?php
/**
 * 答题记录API，用于保存答题记录
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
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'POST':
            // 保存答题记录
            $data = json_decode(file_get_contents('php://input'), true);
            if (empty($data)) {
                $data = $_POST;
            }
            
            // 验证必填字段
            if (empty($data['batch_id']) || empty($data['question_id']) || empty($data['user_answer'])) {
                $response['code'] = 400;
                $response['msg'] = '缺少必填字段';
                echo json_encode($response);
                exit;
            }
            
            // 获取当前用户ID，实际应用中应从登录状态获取
            $user_id = 1; // 这里暂时硬编码为1
            
            // 准备保存数据
            $save_data = array(
                'user_id' => $user_id,
                'batch_id' => intval($data['batch_id']),
                'question_id' => intval($data['question_id']),
                'user_answer' => $data['user_answer']
            );
            
            // 检查是否已经存在该题的答题记录
            $existing = $db->findOne('exam_answers', array(
                'batch_id' => $save_data['batch_id'],
                'question_id' => $save_data['question_id']
            ));
            
            if ($existing) {
                // 更新已有记录
                $result = $db->update('exam_answers', $save_data, array(
                    'id' => $existing['id']
                ));
            } else {
                // 插入新记录
                $result = $db->add('exam_answers', $save_data);
            }
            
            if (!$result) {
                $response['code'] = 500;
                $response['msg'] = '保存答题记录失败';
            } else {
                // 返回保存的答题记录
                $response['data'] = array(
                    'id' => $existing ? $existing['id'] : $result,
                    'user_answer' => $save_data['user_answer']
                );
            }
            break;
            
        case 'GET':
            // 获取答题记录（可选功能）
            $batch_id = isset($_GET['batch_id']) ? intval($_GET['batch_id']) : 0;
            if (!$batch_id) {
                $response['code'] = 400;
                $response['msg'] = '缺少批次ID';
                echo json_encode($response);
                exit;
            }
            
            $answers = $db->query("SELECT * FROM {$db->getPrefix()}exam_answers WHERE batch_id = :batch_id ORDER BY id DESC", array(
                ':batch_id' => $batch_id
            ));
            
            $response['data'] = $answers;
            break;
            
        default:
            $response['code'] = 405;
            $response['msg'] = '不支持的请求方法';
    }
} catch (Exception $e) {
    $response['code'] = 500;
    $response['msg'] = '操作失败：' . $e->getMessage();
}

echo json_encode($response);
