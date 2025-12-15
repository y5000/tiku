<?php
/**
 * 错题与收藏管理API
 * 实现错题和收藏的增删改查功能
 */

// 设置响应头
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// 处理OPTIONS请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 引入数据库连接类和配置文件
try {
    require_once '../config.php';
    require_once '../db.php';
    
    // 获取配置
    $config = require '../config.php';
    
    // 创建数据库连接实例
    $db = new Database();
    
    // 根据请求方法处理不同的操作
    switch ($_SERVER['REQUEST_METHOD']) {
        
        // 获取错题/收藏列表
        case 'GET':
            handleGetWrongQuestions($db);
            break;
            
        // 添加错题/收藏
        case 'POST':
            handleAddWrongQuestion($db);
            break;
            
        // 更新错题/收藏
        case 'PUT':
            handleUpdateWrongQuestion($db);
            break;
            
        // 删除错题/收藏
        case 'DELETE':
            handleDeleteWrongQuestion($db);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['code' => 405, 'msg' => '不支持的请求方法']);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['code' => 500, 'msg' => '服务器内部错误', 'error' => $e->getMessage()]);
}

/**
 * 处理获取错题/收藏列表请求
 */
function handleGetWrongQuestions($db) {
    try {
        // 获取查询参数
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
        $type = isset($_GET['type']) ? intval($_GET['type']) : -1; // -1表示全部
        $reviewed = isset($_GET['reviewed']) ? intval($_GET['reviewed']) : -1;
        
        $conditions = [];
        if ($id > 0) {
            $conditions['id'] = $id;
        }
        if ($user_id > 0) {
            $conditions['user_id'] = $user_id;
        }
        if ($type >= 0) {
            $conditions['type'] = $type;
        }
        if ($reviewed >= 0) {
            $conditions['reviewed'] = $reviewed;
        }
        
        // 获取错题/收藏列表
        $wrongQuestions = $db->find('wrong_questions', $conditions, 'id, user_id, question_id, type, answer, correct_answer, reviewed, reviewed_at, created_at');
        
        // 获取关联的题目信息
        foreach ($wrongQuestions as &$item) {
            // 获取题目信息，包括category_id和options
            $question = $db->findOne('questions', ['id' => $item['question_id']], 'id, type, content, options, answer, analysis, difficulty, category_id');
            if ($question) {
                $item['question'] = $question;
                
                // 获取分类信息
                $category = $db->findOne('categories', ['id' => $question['category_id']]);
                $item['category_name'] = $category ? $category['name'] : '未知分类';
                
                // 题库信息从category获取
                $item['bank_name'] = $item['category_name'];
            }
        }
        
        echo json_encode(['code' => 200, 'msg' => '获取成功', 'data' => $wrongQuestions]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['code' => 500, 'msg' => '获取失败', 'error' => $e->getMessage()]);
    }
}

/**
 * 处理添加错题/收藏请求
 */
function handleAddWrongQuestion($db) {
    try {
        // 获取请求数据
        $data = json_decode(file_get_contents('php://input'), true);
        
        // 检查是否为批量添加（数组形式）
        if (is_array($data) && count($data) > 0 && is_array($data[0])) {
            // 批量添加处理
            $result = [];
            foreach ($data as $item) {
                // 验证必填字段
                if (empty($item['user_id'])) {
                    continue; // 跳过无效数据
                }
                
                if (empty($item['question_id'])) {
                    continue; // 跳过无效数据
                }
                
                // 设置默认值
                $type = 0; // 错题类型固定为0
                $reviewed = 0; // 复习状态固定为0
                
                // 准备数据
                $user_id = intval($item['user_id']);
                $question_id = intval($item['question_id']);
                $wrongQuestionData = [
                    'user_id' => $user_id,
                    'question_id' => $question_id,
                    'type' => $type,
                    'answer' => isset($item['answer']) ? $item['answer'] : '',
                    'correct_answer' => isset($item['correct_answer']) ? $item['correct_answer'] : '',
                    'reviewed' => $reviewed
                ];
                
                // 检查是否已存在同一用户的同一题目
                $existing = $db->findOne('wrong_questions', ['user_id' => $user_id, 'question_id' => $question_id]);
                
                if ($existing) {
                    // 更新现有记录
                    $success = $db->update('wrong_questions', $wrongQuestionData, ['id' => $existing['id']]);
                    if ($success) {
                        // 获取更新后的记录
                        $wrongQuestion = $db->findOne('wrong_questions', ['id' => $existing['id']]);
                        $result[] = $wrongQuestion;
                    }
                } else {
                    // 插入新记录
                    $wrong_question_id = $db->add('wrong_questions', $wrongQuestionData);
                    if ($wrong_question_id) {
                        // 获取插入后的记录
                        $wrongQuestion = $db->findOne('wrong_questions', ['id' => $wrong_question_id]);
                        $result[] = $wrongQuestion;
                    }
                }
            }
            
            echo json_encode(['code' => 201, 'msg' => '批量添加成功', 'data' => $result]);
        } else {
            // 单条添加处理
            // 验证必填字段
            if (empty($data['user_id'])) {
                echo json_encode(['code' => 400, 'msg' => '用户ID不能为空']);
                return;
            }
            
            if (empty($data['question_id'])) {
                echo json_encode(['code' => 400, 'msg' => '题目ID不能为空']);
                return;
            }
            
            // 设置默认值
            $type = 0; // 错题类型固定为0
            $reviewed = 0; // 复习状态固定为0
            
            // 准备数据
            $user_id = intval($data['user_id']);
            $question_id = intval($data['question_id']);
            $wrongQuestionData = [
                'user_id' => $user_id,
                'question_id' => $question_id,
                'type' => $type,
                'answer' => isset($data['answer']) ? $data['answer'] : '',
                'correct_answer' => isset($data['correct_answer']) ? $data['correct_answer'] : '',
                'reviewed' => $reviewed
            ];
            
            // 检查是否已存在同一用户的同一题目
            $existing = $db->findOne('wrong_questions', ['user_id' => $user_id, 'question_id' => $question_id]);
            
            if ($existing) {
                // 更新现有记录
                $success = $db->update('wrong_questions', $wrongQuestionData, ['id' => $existing['id']]);
                if ($success) {
                    // 获取更新后的记录
                    $wrongQuestion = $db->findOne('wrong_questions', ['id' => $existing['id']]);
                    echo json_encode(['code' => 200, 'msg' => $type == 1 ? '收藏成功' : '更新错题成功', 'data' => $wrongQuestion]);
                } else {
                    echo json_encode(['code' => 500, 'msg' => $type == 1 ? '收藏失败' : '更新错题失败']);
                }
            } else {
                // 插入新记录
                $wrong_question_id = $db->add('wrong_questions', $wrongQuestionData);
                if ($wrong_question_id) {
                    // 获取插入后的记录
                    $wrongQuestion = $db->findOne('wrong_questions', ['id' => $wrong_question_id]);
                    echo json_encode(['code' => 201, 'msg' => $type == 1 ? '收藏成功' : '添加错题成功', 'data' => $wrongQuestion]);
                } else {
                    echo json_encode(['code' => 500, 'msg' => $type == 1 ? '收藏失败' : '添加错题失败']);
                }
            }
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['code' => 500, 'msg' => '添加失败', 'error' => $e->getMessage()]);
    }
}

/**
 * 处理更新错题/收藏请求
 */
function handleUpdateWrongQuestion($db) {
    try {
        // 获取请求数据
        $data = json_decode(file_get_contents('php://input'), true);
        
        // 准备更新数据
        $updateData = [];
        if (isset($data['type'])) {
            $updateData['type'] = intval($data['type']);
        }
        if (isset($data['reviewed'])) {
            $updateData['reviewed'] = intval($data['reviewed']);
            if (intval($data['reviewed']) == 1) {
                $updateData['reviewed_at'] = date('Y-m-d H:i:s');
            }
        }
        if (isset($data['answer'])) {
            $updateData['answer'] = $data['answer'];
        }
        if (isset($data['correct_answer'])) {
            $updateData['correct_answer'] = $data['correct_answer'];
        }
        
        if (empty($updateData)) {
            echo json_encode(['code' => 400, 'msg' => '没有需要更新的数据']);
            return;
        }
        
        // 构建更新条件
        $whereConditions = [];
        if (isset($data['id'])) {
            $whereConditions['id'] = $data['id'];
        } elseif (isset($data['question_id'])) {
            $whereConditions['question_id'] = $data['question_id'];
        } else {
            echo json_encode(['code' => 400, 'msg' => '记录ID或题目ID不能为空']);
            return;
        }
        
        // 更新错题/收藏
        $success = $db->update('wrong_questions', $updateData, $whereConditions);
        
        if ($success) {
            // 获取更新后的记录
            $wrongQuestion = $db->findOne('wrong_questions', $whereConditions);
            echo json_encode(['code' => 200, 'msg' => '更新成功', 'data' => $wrongQuestion]);
        } else {
            echo json_encode(['code' => 500, 'msg' => '更新失败']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['code' => 500, 'msg' => '更新失败', 'error' => $e->getMessage()]);
    }
}

/**
 * 处理删除错题/收藏请求
 */
function handleDeleteWrongQuestion($db) {
    try {
        // 获取记录ID
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        if ($id <= 0) {
            echo json_encode(['code' => 400, 'msg' => '记录ID不能为空']);
            return;
        }
        
        // 检查记录是否存在
        $wrongQuestion = $db->findOne('wrong_questions', ['id' => $id]);
        if (!$wrongQuestion) {
            echo json_encode(['code' => 404, 'msg' => '记录不存在']);
            return;
        }
        
        // 删除错题/收藏
        $success = $db->del('wrong_questions', ['id' => $id]);
        
        if ($success) {
            echo json_encode(['code' => 200, 'msg' => '删除成功']);
        } else {
            echo json_encode(['code' => 500, 'msg' => '删除失败']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['code' => 500, 'msg' => '删除失败', 'error' => $e->getMessage()]);
    }
}
?>