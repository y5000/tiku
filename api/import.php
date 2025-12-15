<?php
/**
 * 数据导入API
 * 实现题目批量导入功能
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
        
        // 批量导入题目
        case 'POST':
            handleImportQuestions($db);
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
 * 处理批量导入题目请求
 */
function handleImportQuestions($db) {
    try {
        // 获取请求数据
        $data = json_decode(file_get_contents('php://input'), true);
        
        // 验证必填字段
        if (empty($data['data'])) {
            echo json_encode(['code' => 400, 'msg' => '导入数据不能为空']);
            return;
        }
        
        // 解析导入数据
        $importData = $data['data'];
        $lines = explode('\n', $importData);
        $successCount = 0;
        $errorCount = 0;
        $errorMessages = [];
        
        // 类型映射 - 支持多种别名
        $typeMap = [
            // 单选题类型
            '单选' => 1,
            '单选题' => 1,
            // 多选题类型
            '多选' => 2,
            '多选题' => 2,
            '复选题' => 2,
            // 判断题类型
            '判断' => 3,
            '判断题' => 3,
            '选择题' => 3,
            '选择' => 3,
            // 填空题类型
            '填空' => 4,
            '填空题' => 4,
            // 简答题类型
            '简答' => 5,
            '简答题' => 5,
            '问答题' => 5,
            '问答' => 5
        ];
        
        // 遍历导入的每一行
        foreach ($lines as $index => $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }
            
            // 分割字段 - 处理多个连续Tab的情况
            $parts = preg_split('/\t+/', $line);
            // 过滤空字段
            $parts = array_filter($parts, function($part) {
                return trim($part) !== '';
            });
            $parts = array_values($parts);
            
            // 检查字段数量 - 允许缺少序号字段
            if (count($parts) < 5) {
                $errorCount++;
                $errorMessages[] = '第' . ($index + 1) . '行：格式错误，字段数量不足，需要5-6个字段（序号可选）';
                continue;
            }
            
            // 解析字段 - 处理缺少序号的情况
            $hasIndex = true;
            if (count($parts) == 5) {
                // 缺少序号字段
                $hasIndex = false;
                $lineData = [
                    'index' => $index + 1,
                    'type_text' => $parts[0],
                    'type' => isset($typeMap[$parts[0]]) ? $typeMap[$parts[0]] : 0,
                    'content' => $parts[1],
                    'options' => $parts[2],
                    'answer' => $parts[3],
                    'analysis' => $parts[4]
                ];
            } else {
                // 包含序号字段
                $lineData = [
                    'index' => $parts[0],
                    'type_text' => $parts[1],
                    'type' => isset($typeMap[$parts[1]]) ? $typeMap[$parts[1]] : 0,
                    'content' => $parts[2],
                    'options' => $parts[3],
                    'answer' => $parts[4],
                    'analysis' => $parts[5]
                ];
            }
            
            // 验证类型
            if (empty($lineData['type'])) {
                $errorCount++;
                $errorMessages[] = '第' . ($index + 1) . '行：不支持的题目类型';
                continue;
            }
            
            // 验证题目内容
            if (empty($lineData['content'])) {
                $errorCount++;
                $errorMessages[] = '第' . ($index + 1) . '行：题目内容不能为空';
                continue;
            }
            
            // 验证答案
            if (in_array($lineData['type'], [3, 4, 5]) && empty($lineData['answer'])) {
                $errorCount++;
                $errorMessages[] = '第' . ($index + 1) . '行：答案不能为空';
                continue;
            }
            
            // 选择题特殊验证
            if (in_array($lineData['type'], [1, 2])) {
                if (empty($lineData['options'])) {
                    $errorCount++;
                    $errorMessages[] = '第' . ($index + 1) . '行：选择题必须至少有一个选项';
                    continue;
                }
            }
            
            // 准备插入数据
            $questionData = [
                'bank_id' => 0, // 默认题库
                'category_id' => NULL, // 设置为NULL，不再使用该字段
                'type' => intval($lineData['type']),
                'content' => $lineData['content'],
                'answer' => $lineData['answer'],
                'analysis' => $lineData['analysis'],
                'difficulty' => 2, // 默认中等难度
                'status' => 1
            ];
            
            // 处理选项（使用|分隔格式）
            if (in_array($lineData['type'], [1, 2, 3])) {
                if (!empty($lineData['options'])) {
                    // 去除选项字符串开头的竖线
                    $options = ltrim($lineData['options'], '|');
                    $questionData['options'] = $options;
                } else {
                    // 为空时设置为空字符串
                    $questionData['options'] = '';
                }
            }
            
            // 插入题目
            $question_id = $db->add('questions', $questionData);
            if ($question_id) {
                $successCount++;
            } else {
                $errorCount++;
                $errorMessages[] = '第' . ($index + 1) . '行：导入失败';
            }
        }
        
        // 返回导入结果
        $result = [
            'code' => 200,
            'msg' => '导入完成',
            'success_count' => $successCount,
            'error_count' => $errorCount,
            'total_count' => $successCount + $errorCount
        ];
        
        if ($errorCount > 0) {
            $result['error_messages'] = $errorMessages;
        }
        
        echo json_encode($result);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['code' => 500, 'msg' => '导入题目失败', 'error' => $e->getMessage()]);
    }
}
