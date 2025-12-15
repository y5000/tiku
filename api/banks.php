<?php
/**
 * 题库管理API
 * 实现题库的增删改查功能
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
        
        // 获取题库列表
        case 'GET':
            handleGetBanks($db);
            break;
            
        // 添加题库
        case 'POST':
            handleAddBank($db);
            break;
            
        // 更新题库
        case 'PUT':
            handleUpdateBank($db);
            break;
            
        // 删除题库
        case 'DELETE':
            handleDeleteBank($db);
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
 * 处理获取题库列表请求
 */
function handleGetBanks($db) {
    try {
        // 获取查询参数
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        $conditions = ['status' => 1];
        if ($id > 0) {
            $conditions['id'] = $id;
        }
        
        // 获取题库列表
        $banks = $db->find('banks', $conditions, 'id, category_id, name, description, status, created_at, updated_at');
        
        // 获取分类名称
        foreach ($banks as &$bank) {
            $category = $db->findOne('categories', ['id' => $bank['category_id']]);
            $bank['category_name'] = $category ? $category['name'] : '未知分类';
        }
        
        echo json_encode(['code' => 200, 'msg' => '获取成功', 'data' => $banks]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['code' => 500, 'msg' => '获取题库失败', 'error' => $e->getMessage()]);
    }
}

/**
 * 处理添加题库请求
 */
function handleAddBank($db) {
    try {
        // 获取请求数据
        $data = json_decode(file_get_contents('php://input'), true);
        
        // 验证必填字段
        if (empty($data['name'])) {
            echo json_encode(['code' => 400, 'msg' => '题库名称不能为空']);
            return;
        }
        
        // 准备插入数据
        $bankData = [
            'name' => $data['name'],
            'category_id' => isset($data['category_id']) ? intval($data['category_id']) : 1,
            'description' => isset($data['description']) ? $data['description'] : '',
            'status' => 1
        ];
        
        // 插入题库
        $id = $db->add('banks', $bankData);
        
        if ($id) {
            // 获取插入后的题库信息
            $bank = $db->findOne('banks', ['id' => $id]);
            echo json_encode(['code' => 201, 'msg' => '添加成功', 'data' => $bank]);
        } else {
            echo json_encode(['code' => 500, 'msg' => '添加失败']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['code' => 500, 'msg' => '添加题库失败', 'error' => $e->getMessage()]);
    }
}

/**
 * 处理更新题库请求
 */
function handleUpdateBank($db) {
    try {
        // 获取请求数据
        $data = json_decode(file_get_contents('php://input'), true);
        
        // 验证必填字段
        if (empty($data['id'])) {
            echo json_encode(['code' => 400, 'msg' => '题库ID不能为空']);
            return;
        }
        
        if (empty($data['name'])) {
            echo json_encode(['code' => 400, 'msg' => '题库名称不能为空']);
            return;
        }
        
        // 检查题库是否存在
        $bank = $db->findOne('banks', ['id' => $data['id']]);
        if (!$bank) {
            echo json_encode(['code' => 404, 'msg' => '题库不存在']);
            return;
        }
        
        // 准备更新数据
        $updateData = [
            'name' => $data['name'],
            'category_id' => isset($data['category_id']) ? intval($data['category_id']) : 1,
            'description' => isset($data['description']) ? $data['description'] : ''
        ];
        
        // 更新题库
        $success = $db->update('banks', $updateData, ['id' => $data['id']]);
        
        if ($success) {
            // 获取更新后的题库信息
            $updatedBank = $db->findOne('banks', ['id' => $data['id']]);
            echo json_encode(['code' => 200, 'msg' => '更新成功', 'data' => $updatedBank]);
        } else {
            echo json_encode(['code' => 500, 'msg' => '更新失败']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['code' => 500, 'msg' => '更新题库失败', 'error' => $e->getMessage()]);
    }
}

/**
 * 处理删除题库请求
 */
function handleDeleteBank($db) {
    try {
        // 获取题库ID
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        if ($id <= 0) {
            echo json_encode(['code' => 400, 'msg' => '题库ID不能为空']);
            return;
        }
        
        // 检查题库是否存在
        $bank = $db->findOne('banks', ['id' => $id]);
        if (!$bank) {
            echo json_encode(['code' => 404, 'msg' => '题库不存在']);
            return;
        }
        
        // 检查是否有题目使用该题库
        $questions = $db->find('questions', ['bank_id' => $id]);
        if (!empty($questions)) {
            echo json_encode(['code' => 400, 'msg' => '该题库下存在题目，无法删除']);
            return;
        }
        
        // 删除题库
        $success = $db->del('banks', ['id' => $id]);
        
        if ($success) {
            echo json_encode(['code' => 200, 'msg' => '删除成功']);
        } else {
            echo json_encode(['code' => 500, 'msg' => '删除失败']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['code' => 500, 'msg' => '删除题库失败', 'error' => $e->getMessage()]);
    }
}
?>