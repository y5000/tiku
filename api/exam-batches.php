<?php
/**
 * 考试批次API，用于获取和管理答题记录
 */

header('Content-Type: application/json; charset=utf-8');

require_once '../db.php';

$db = new Database();
$method = $_SERVER['REQUEST_METHOD'];
$response = array(
    'code' => 200,
    'msg' => '操作成功',
    'data' => array()
);

try {
    // 获取当前用户ID，实际应用中应从登录状态获取
    $user_id = 1; // 这里暂时硬编码为1
    
    switch ($method) {
        case 'GET':
            // 检查是否需要获取统计数据
            if (isset($_GET['action']) && $_GET['action'] === 'stats') {
                // 获取统计数据
                // 1. 总练习次数
                $totalBatchesSql = "SELECT COUNT(*) as count FROM {$db->getPrefix()}exam_batches WHERE user_id = :user_id";
                $totalBatches = $db->query($totalBatchesSql, array(':user_id' => $user_id));
                $totalBatches = $totalBatches[0]['count'];
                
                // 2. 总答题数
                $totalQuestionsSql = "SELECT SUM(total_questions) as total FROM {$db->getPrefix()}exam_batches WHERE user_id = :user_id";
                $totalQuestions = $db->query($totalQuestionsSql, array(':user_id' => $user_id));
                $totalQuestions = $totalQuestions[0]['total'] ?: 0;
                
                // 3. 平均正确率
                $averageScoreSql = "SELECT SUM(correct_questions) as total_correct, SUM(total_questions) as total FROM {$db->getPrefix()}exam_batches WHERE user_id = :user_id AND total_questions > 0";
                $averageScoreData = $db->query($averageScoreSql, array(':user_id' => $user_id));
                $total_correct = $averageScoreData[0]['total_correct'] ?: 0;
                $total_questions = $averageScoreData[0]['total'] ?: 0;
                $averageScore = $total_questions > 0 ? intval(($total_correct / $total_questions) * 100) : 0;
                
                // 4. 今日练习次数
                $today = date('Y-m-d');
                $todayBatchesSql = "SELECT COUNT(*) as count FROM {$db->getPrefix()}exam_batches WHERE user_id = :user_id AND DATE(start_time) = :today";
                $todayBatches = $db->query($todayBatchesSql, array(
                    ':user_id' => $user_id,
                    ':today' => $today
                ));
                $todayBatches = $todayBatches[0]['count'];
                
                $response['data'] = array(
                    'totalBatches' => $totalBatches,
                    'totalQuestions' => $totalQuestions,
                    'averageScore' => $averageScore,
                    'todayBatches' => $todayBatches
                );
            } else {
                // 检查是否是分页请求（带有page参数）
                if (isset($_GET['page'])) {
                    // 分页请求
                    $page = intval($_GET['page']);
                    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
                    $offset = ($page - 1) * $limit;
                    
                    // 计算总数
                    $total = $db->query("SELECT COUNT(*) as total FROM {$db->getPrefix()}exam_batches WHERE user_id = :user_id", array(':user_id' => $user_id));
                    $total = $total[0]['total'];
                    
                    // 查询数据
                    $sql = "SELECT * FROM {$db->getPrefix()}exam_batches WHERE user_id = :user_id ORDER BY start_time DESC LIMIT :limit OFFSET :offset";
                    $batches = $db->query($sql, array(
                        ':user_id' => $user_id,
                        ':limit' => $limit,
                        ':offset' => $offset
                    ));
                    
                    $response['data'] = array(
                        'total' => $total,
                        'page' => $page,
                        'limit' => $limit,
                        'items' => $batches
                    );
                } else {
                    // 获取最近练习记录（默认返回5条）
                    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 5;
                    
                    // 查询数据
                    $sql = "SELECT * FROM {$db->getPrefix()}exam_batches WHERE user_id = :user_id ORDER BY start_time DESC LIMIT :limit";
                    $batches = $db->query($sql, array(
                        ':user_id' => $user_id,
                        ':limit' => $limit
                    ));
                    
                    $response['data'] = $batches;
                }
            }
            break;
            
        case 'POST':
            // 创建新的答题记录
            $data = json_decode(file_get_contents('php://input'), true);
            if (empty($data)) {
                $data = $_POST;
            }
            
            $exam_type = isset($data['exam_type']) ? $data['exam_type'] : '随机答题';
            $total_questions = isset($data['total_questions']) ? intval($data['total_questions']) : 0;
            $question_types = isset($data['question_types']) ? $data['question_types'] : '';
            $is_random = isset($data['is_random']) ? intval($data['is_random']) : 0;
            
            $batch_id = $db->add('exam_batches', array(
                'user_id' => $user_id,
                'exam_type' => $exam_type,
                'total_questions' => $total_questions,
                'answered_questions' => 0,
                'correct_questions' => 0,
                'wrong_questions' => 0,
                'empty_questions' => 0,
                'question_types' => $question_types,
                'is_random' => $is_random
            ));
            
            $response['data'] = array(
                'batch_id' => $batch_id
            );
            break;
            
        case 'PUT':
            // 更新答题记录
            $data = json_decode(file_get_contents('php://input'), true);
            if (empty($data)) {
                $data = $_POST;
            }
            
            $batch_id = isset($data['batch_id']) ? intval($data['batch_id']) : 0;
            if (!$batch_id) {
                $response['code'] = 400;
                $response['msg'] = '缺少批次ID';
                break;
            }
            
            $update_data = array();
            if (isset($data['answered_questions'])) {
                $update_data['answered_questions'] = intval($data['answered_questions']);
            }
            if (isset($data['correct_questions'])) {
                $update_data['correct_questions'] = intval($data['correct_questions']);
            }
            if (isset($data['wrong_questions'])) {
                $update_data['wrong_questions'] = intval($data['wrong_questions']);
            }
            if (isset($data['empty_questions'])) {
                $update_data['empty_questions'] = intval($data['empty_questions']);
            }
            if (isset($data['end_time'])) {
                $update_data['end_time'] = $data['end_time'];
            } else {
                $update_data['end_time'] = date('Y-m-d H:i:s');
            }
            
            if (!empty($update_data)) {
                $result = $db->update('exam_batches', $update_data, array('id' => $batch_id, 'user_id' => $user_id));
                if (!$result) {
                    $response['code'] = 500;
                    $response['msg'] = '更新失败';
                }
            }
            break;
            
        case 'DELETE':
            // 删除答题记录
            $batch_id = isset($_GET['batch_id']) ? intval($_GET['batch_id']) : 0;
            if (!$batch_id) {
                $response['code'] = 400;
                $response['msg'] = '缺少批次ID';
                break;
            }
            
            // 先删除相关的答题记录
            $db->del('exam_answers', array('batch_id' => $batch_id));
            // 再删除批次记录
            $result = $db->del('exam_batches', array('id' => $batch_id, 'user_id' => $user_id));
            
            if (!$result) {
                $response['code'] = 500;
                $response['msg'] = '删除失败';
            }
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
