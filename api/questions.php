<?php
/**
 * 题目管理API
 * 实现题目的增删改查功能
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
        
        // 获取题目列表
        case 'GET':
            handleGetQuestions($db);
            break;
            
        // 添加题目
        case 'POST':
            handleAddQuestion($db);
            break;
            
        // 更新题目
        case 'PUT':
            handleUpdateQuestion($db);
            break;
            
        // 删除题目
        case 'DELETE':
            handleDeleteQuestion($db);
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
 * 处理获取题目列表请求
 */
function handleGetQuestions($db) {
    try {
        // 获取查询参数
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $bank_id = isset($_GET['bank_id']) ? intval($_GET['bank_id']) : 0;
        $type = isset($_GET['type']) ? intval($_GET['type']) : 0;
        $count = isset($_GET['count']) ? intval($_GET['count']) : 0;
        $random = isset($_GET['random']) && $_GET['random'] == 1;
        $types = isset($_GET['types']) ? explode(',', $_GET['types']) : [];
        $ids = isset($_GET['ids']) ? explode(',', $_GET['ids']) : [];
        $start = isset($_GET['start']) ? intval($_GET['start']) - 1 : 0;
        
        // 构造基础查询
        $baseQuery = "SELECT id, bank_id, type, content, options, answer, analysis, difficulty, status, created_at FROM {$db->getPrefix()}questions WHERE status = 1";
        $params = [];
        
        // 添加条件
        $whereConditions = [];
        if ($id > 0) {
            $whereConditions[] = "id = ?";
            $params[] = $id;
        }
        if ($bank_id > 0) {
            $whereConditions[] = "bank_id = ?";
            $params[] = $bank_id;
        }
        if ($type > 0) {
            $whereConditions[] = "type = ?";
            $params[] = $type;
        } elseif (!empty($types)) {
            // 使用 IN 条件过滤题型
            $inPlaceholders = rtrim(str_repeat('?,', count($types)), ',');
            $whereConditions[] = "type IN ($inPlaceholders)";
            $params = array_merge($params, $types);
        } elseif (!empty($ids)) {
            // 使用 IN 条件过滤题目ID
            $inPlaceholders = rtrim(str_repeat('?,', count($ids)), ',');
            $whereConditions[] = "id IN ($inPlaceholders)";
            $params = array_merge($params, $ids);
        }
        
        // 合并查询条件
        if (!empty($whereConditions)) {
            $baseQuery .= " AND " . implode(" AND ", $whereConditions);
        }
        
        // 获取题目列表
        $questions = $db->query($baseQuery, $params);
        
        // 随机排序
        if ($random) {
            shuffle($questions);
        } else {
            // 非随机时，应用起始题数
            if ($start > 0) {
                $questions = array_slice($questions, $start);
            }
        }
        
        // 限制数量
        if ($count > 0) {
            $questions = array_slice($questions, 0, $count);
        }
        
        // 获取题库名称和分类名称（通过题库）
        foreach ($questions as &$question) {
            // 获取题库信息
            $bank = null;
            $bank_name = '默认题库';
            $category_name = '未知分类';
            
            if ($question['bank_id'] > 0) {
                $bank = $db->findOne('banks', ['id' => $question['bank_id']]);
                if ($bank) {
                    $bank_name = $bank['name'];
                    
                    // 通过题库获取分类名称
                    $category = $db->findOne('categories', ['id' => $bank['category_id']]);
                    $category_name = $category ? $category['name'] : '未知分类';
                }
            }
            
            $question['bank_name'] = $bank_name;
            $question['category_name'] = $category_name;
            // 保留旧的category_id字段以便兼容
            $question['category_id'] = $bank ? $bank['category_id'] : 0;
        }
        
        echo json_encode(['code' => 200, 'msg' => '获取成功', 'data' => $questions]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['code' => 500, 'msg' => '获取题目失败', 'error' => $e->getMessage()]);
    }
}

/**
 * 处理添加题目请求
 */
function handleAddQuestion($db) {
    try {
        // 获取请求数据
        $data = json_decode(file_get_contents('php://input'), true);
        
        // 检查是否为批量导入
        $isBatch = is_array($data) && count($data) > 0 && is_array($data[0]);
        
        if ($isBatch) {
            // 批量导入
            $successCount = 0;
            $failedCount = 0;
            
            foreach ($data as $questionData) {
                // 验证必填字段
                if (empty($questionData['type']) || empty($questionData['content'])) {
                    $failedCount++;
                    continue;
                }
                
                // 只有判断题、填空题、简答题需要验证答案
                if (in_array($questionData['type'], [3, 4, 5]) && empty($questionData['answer'])) {
                    $failedCount++;
                    continue;
                }
                
                // 选择题特殊验证
                if (in_array($questionData['type'], [1, 2])) {
                    // 检查选项
                    if (!isset($questionData['options']) || empty($questionData['options'])) {
                        $failedCount++;
                        continue;
                    }
                }
                
                // 准备插入数据
                $insertData = [
                    'bank_id' => isset($questionData['bank_id']) ? intval($questionData['bank_id']) : 0,
                    'category_id' => NULL, // 设置为NULL，不再使用该字段
                    'type' => intval($questionData['type']),
                    'content' => $questionData['content'],
                    'answer' => isset($questionData['answer']) ? $questionData['answer'] : '',
                    'analysis' => isset($questionData['analysis']) ? $questionData['analysis'] : '',
                    'difficulty' => isset($questionData['difficulty']) ? intval($questionData['difficulty']) : 2,
                    'status' => 1
                ];
                
                // 处理选项（使用|分隔格式）
                if (in_array($questionData['type'], [1, 2]) && isset($questionData['options']) && !empty($questionData['options'])) {
                    // 前端已经处理好选项格式，直接使用
                    $insertData['options'] = $questionData['options'];
                }
                
                // 插入题目
                $question_id = $db->add('questions', $insertData);
                
                if ($question_id) {
                    $successCount++;
                } else {
                    $failedCount++;
                }
            }
            
            echo json_encode(['code' => 200, 'msg' => '批量导入完成', 'data' => ['success' => $successCount, 'failed' => $failedCount]]);
        } else {
            // 单个导入
            // 验证必填字段
            if (empty($data['type'])) {
                echo json_encode(['code' => 400, 'msg' => '题目类型不能为空']);
                return;
            }
            
            if (empty($data['content'])) {
                echo json_encode(['code' => 400, 'msg' => '题目内容不能为空']);
                return;
            }
            
            // 只有判断题、填空题、简答题需要验证答案
            if (in_array($data['type'], [3, 4, 5]) && empty($data['answer'])) {
                echo json_encode(['code' => 400, 'msg' => '答案不能为空']);
                return;
            }
            
            // 选择题特殊验证
            if (in_array($data['type'], [1, 2])) {
                // 检查选项
                if (!isset($data['options']) || empty($data['options'])) {
                    echo json_encode(['code' => 400, 'msg' => '选择题必须至少有一个选项']);
                    return;
                }
            }
            
            // 准备插入数据
            $questionData = [
                'bank_id' => isset($data['bank_id']) ? intval($data['bank_id']) : 0,
                'category_id' => NULL, // 设置为NULL，不再使用该字段
                'type' => intval($data['type']),
                'content' => $data['content'],
                'answer' => isset($data['answer']) ? $data['answer'] : '',
                'analysis' => isset($data['analysis']) ? $data['analysis'] : '',
                'difficulty' => isset($data['difficulty']) ? intval($data['difficulty']) : 2,
                'status' => 1
            ];
            
            // 处理选项（使用|分隔格式）
            if (in_array($data['type'], [1, 2]) && isset($data['options']) && !empty($data['options'])) {
                // 前端已经处理好选项格式，直接使用
                $questionData['options'] = $data['options'];
            }
            
            // 插入题目
            $question_id = $db->add('questions', $questionData);
            
            if ($question_id) {
                // 获取插入后的题目信息
                $question = $db->findOne('questions', ['id' => $question_id]);
                $category = null;
                $category_name = '未知分类';
                $bank_name = '默认题库';
                
                // 获取题库名称和分类名称（通过题库）
                if ($question['bank_id'] > 0) {
                    $bank = $db->findOne('banks', ['id' => $question['bank_id']]);
                    if ($bank) {
                        $bank_name = $bank['name'];
                        
                        // 通过题库获取分类名称
                        $category = $db->findOne('categories', ['id' => $bank['category_id']]);
                        $category_name = $category ? $category['name'] : '未知分类';
                    }
                }
                
                $question['bank_name'] = $bank_name;
                $question['category_name'] = $category_name;
                // 保留旧的category_id字段以便兼容
                $question['category_id'] = $category ? $category['id'] : 0;
                
                echo json_encode(['code' => 201, 'msg' => '添加成功', 'data' => $question]);
            } else {
                echo json_encode(['code' => 500, 'msg' => '添加失败']);
            }
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['code' => 500, 'msg' => '添加题目失败', 'error' => $e->getMessage()]);
    }
}

/**
 * 处理更新题目请求
 */
function handleUpdateQuestion($db) {
    try {
        // 获取请求数据
        $data = json_decode(file_get_contents('php://input'), true);
        
        // 验证必填字段
        if (empty($data['id'])) {
            echo json_encode(['code' => 400, 'msg' => '题目ID不能为空']);
            return;
        }
        
        if (empty($data['type'])) {
            echo json_encode(['code' => 400, 'msg' => '题目类型不能为空']);
            return;
        }
        
        if (empty($data['content'])) {
            echo json_encode(['code' => 400, 'msg' => '题目内容不能为空']);
            return;
        }
        
        // 只有判断题、填空题、简答题需要验证答案
        if (in_array($data['type'], [3, 4, 5]) && empty($data['answer'])) {
            echo json_encode(['code' => 400, 'msg' => '答案不能为空']);
            return;
        }
        
        // 选择题特殊验证
        if (in_array($data['type'], [1, 2])) {
            // 检查选项
            if (!isset($data['options']) || empty($data['options'])) {
                echo json_encode(['code' => 400, 'msg' => '选择题必须至少有一个选项']);
                return;
            }
        }
        
        // 检查题目是否存在
        $question = $db->findOne('questions', ['id' => $data['id']]);
        if (!$question) {
            echo json_encode(['code' => 404, 'msg' => '题目不存在']);
            return;
        }
        
        // 准备更新数据
        $updateData = [
            'type' => intval($data['type']),
            'content' => $data['content'],
            'category_id' => NULL, // 设置为NULL，不再使用该字段
            'answer' => isset($data['answer']) ? $data['answer'] : '',
            'analysis' => isset($data['analysis']) ? $data['analysis'] : '',
            'difficulty' => isset($data['difficulty']) ? intval($data['difficulty']) : 2
        ];
        
        // 如果有bank_id则更新
        if (isset($data['bank_id'])) {
            $updateData['bank_id'] = intval($data['bank_id']);
        }
        
        // 处理选项（使用|分隔格式）
        if (in_array($data['type'], [1, 2])) {
            if (isset($data['options']) && !empty($data['options'])) {
                // 前端已经处理好选项格式，直接使用
                $updateData['options'] = $data['options'];
            }
        }
        
        // 更新题目
        $success = $db->update('questions', $updateData, ['id' => $data['id']]);
        
        if ($success) {
            // 选择题不需要单独处理选项表，选项已经存储在questions表的options字段中
            
            
            // 获取更新后的题目信息
            $updatedQuestion = $db->findOne('questions', ['id' => $data['id']]);
            $category = null;
            $category_name = '未知分类';
            $bank_name = '默认题库';
            
            // 获取题库名称和分类名称（通过题库）
            if ($updatedQuestion['bank_id'] > 0) {
                $bank = $db->findOne('banks', ['id' => $updatedQuestion['bank_id']]);
                if ($bank) {
                    $bank_name = $bank['name'];
                    
                    // 通过题库获取分类名称
                    $category = $db->findOne('categories', ['id' => $bank['category_id']]);
                    $category_name = $category ? $category['name'] : '未知分类';
                }
            }
            
            $updatedQuestion['bank_name'] = $bank_name;
            $updatedQuestion['category_name'] = $category_name;
            // 保留旧的category_id字段以便兼容
            $updatedQuestion['category_id'] = $category ? $category['id'] : 0;
            
            echo json_encode(['code' => 200, 'msg' => '更新成功', 'data' => $updatedQuestion]);
        } else {
            echo json_encode(['code' => 500, 'msg' => '更新失败']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['code' => 500, 'msg' => '更新题目失败', 'error' => $e->getMessage()]);
    }
}

/**
 * 处理删除题目请求
 */
function handleDeleteQuestion($db) {
    try {
        // 获取题目ID
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        if ($id <= 0) {
            echo json_encode(['code' => 400, 'msg' => '题目ID不能为空']);
            return;
        }
        
        // 检查题目是否存在
        $question = $db->findOne('questions', ['id' => $id]);
        if (!$question) {
            echo json_encode(['code' => 404, 'msg' => '题目不存在']);
            return;
        }
        
        // 删除题目（会自动删除关联的选项）
        $success = $db->del('questions', ['id' => $id]);
        
        if ($success) {
            echo json_encode(['code' => 200, 'msg' => '删除成功']);
        } else {
            echo json_encode(['code' => 500, 'msg' => '删除失败']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['code' => 500, 'msg' => '删除题目失败', 'error' => $e->getMessage()]);
    }
}
?>