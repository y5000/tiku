<?php
/**
 * 分类管理API
 * 实现分类的增删改查功能
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
        
        // 获取分类列表
        case 'GET':
            handleGetCategories($db);
            break;
            
        // 添加分类
        case 'POST':
            handleAddCategory($db);
            break;
            
        // 更新分类
        case 'PUT':
            handleUpdateCategory($db);
            break;
            
        // 删除分类
        case 'DELETE':
            handleDeleteCategory($db);
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
 * 处理获取分类列表请求
 */
function handleGetCategories($db) {
    try {
        // 获取查询参数
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        if ($id > 0) {
            // 获取单个分类
            $category = $db->findOne('categories', ['id' => $id]);
            if ($category) {
                echo json_encode(['code' => 200, 'msg' => '获取成功', 'data' => $category]);
            } else {
                echo json_encode(['code' => 404, 'msg' => '分类不存在']);
            }
        } else {
            // 获取所有分类
            $categories = $db->find('categories', ['status' => 1], 'id, name, description, parent_id, status, created_at');
            echo json_encode(['code' => 200, 'msg' => '获取成功', 'data' => $categories]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['code' => 500, 'msg' => '获取分类失败', 'error' => $e->getMessage()]);
    }
}

/**
 * 处理添加分类请求
 */
function handleAddCategory($db) {
    try {
        // 获取请求数据
        $data = json_decode(file_get_contents('php://input'), true);
        
        // 验证必填字段
        if (empty($data['name'])) {
            echo json_encode(['code' => 400, 'msg' => '分类名称不能为空']);
            return;
        }
        
        // 准备插入数据
        $categoryData = [
            'name' => $data['name'],
            'description' => isset($data['description']) ? $data['description'] : '',
            'parent_id' => isset($data['parent_id']) ? intval($data['parent_id']) : 0,
            'status' => 1
        ];
        
        // 插入分类
        $id = $db->add('categories', $categoryData);
        
        if ($id) {
            // 获取插入后的分类信息
            $category = $db->findOne('categories', ['id' => $id]);
            echo json_encode(['code' => 201, 'msg' => '添加成功', 'data' => $category]);
        } else {
            echo json_encode(['code' => 500, 'msg' => '添加失败']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['code' => 500, 'msg' => '添加分类失败', 'error' => $e->getMessage()]);
    }
}

/**
 * 处理更新分类请求
 */
function handleUpdateCategory($db) {
    try {
        // 获取请求数据
        $data = json_decode(file_get_contents('php://input'), true);
        
        // 验证必填字段
        if (empty($data['id'])) {
            echo json_encode(['code' => 400, 'msg' => '分类ID不能为空']);
            return;
        }
        
        if (empty($data['name'])) {
            echo json_encode(['code' => 400, 'msg' => '分类名称不能为空']);
            return;
        }
        
        // 检查分类是否存在
        $category = $db->findOne('categories', ['id' => $data['id']]);
        if (!$category) {
            echo json_encode(['code' => 404, 'msg' => '分类不存在']);
            return;
        }
        
        // 准备更新数据
        $updateData = [
            'name' => $data['name'],
            'description' => isset($data['description']) ? $data['description'] : '',
            'parent_id' => isset($data['parent_id']) ? intval($data['parent_id']) : 0
        ];
        
        // 更新分类
        $success = $db->update('categories', $updateData, ['id' => $data['id']]);
        
        if ($success) {
            // 获取更新后的分类信息
            $updatedCategory = $db->findOne('categories', ['id' => $data['id']]);
            echo json_encode(['code' => 200, 'msg' => '更新成功', 'data' => $updatedCategory]);
        } else {
            echo json_encode(['code' => 500, 'msg' => '更新失败']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['code' => 500, 'msg' => '更新分类失败', 'error' => $e->getMessage()]);
    }
}

/**
 * 处理删除分类请求
 */
function handleDeleteCategory($db) {
    try {
        // 获取分类ID
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        if ($id <= 0) {
            echo json_encode(['code' => 400, 'msg' => '分类ID不能为空']);
            return;
        }
        
        // 检查分类是否存在
        $category = $db->findOne('categories', ['id' => $id]);
        if (!$category) {
            echo json_encode(['code' => 404, 'msg' => '分类不存在']);
            return;
        }
        
        // 检查是否有子分类
        $childCategories = $db->find('categories', ['parent_id' => $id, 'status' => 1]);
        if (!empty($childCategories)) {
            echo json_encode(['code' => 400, 'msg' => '该分类下存在子分类，无法删除']);
            return;
        }
        
        // 由于题目表有外键约束，删除分类时会自动删除关联的题目
        // 这里可以根据需要添加更复杂的处理逻辑
        // 例如：将题目移动到其他分类，或者仅删除禁用状态的题目等
        
        // 删除分类
        $success = $db->del('categories', ['id' => $id]);
        
        if ($success) {
            echo json_encode(['code' => 200, 'msg' => '删除成功']);
        } else {
            echo json_encode(['code' => 500, 'msg' => '删除失败']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['code' => 500, 'msg' => '删除分类失败', 'error' => $e->getMessage()]);
    }
}
?>