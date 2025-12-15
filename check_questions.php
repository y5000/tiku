<?php
/**
 * 检查题目管理API
 */

require_once 'db.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    echo "数据库连接成功！\n";
    
    // 尝试执行题目API中的查询
    $conditions = ['status' => 1];
    $sql = "SELECT id, bank_id, category_id, type, content, answer, analysis, difficulty, status, created_at FROM tiku_questions WHERE status = :status";
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':status', 1);
    $stmt->execute();
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "获取到 " . count($questions) . " 条题目数据！\n";
    
    if (count($questions) > 0) {
        // 处理第一条题目，模拟API中的处理逻辑
        $question = $questions[0];
        echo "\n处理第一条题目：\n";
        echo "- 题目ID: {$question['id']}\n";
        
        // 获取分类名称
        $sql = "SELECT name FROM tiku_categories WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':id', $question['category_id']);
        $stmt->execute();
        $category = $stmt->fetch(PDO::FETCH_ASSOC);
        $category_name = $category ? $category['name'] : '未知分类';
        echo "- 分类名称: {$category_name}\n";
        
        // 获取题库名称
        $bank_name = '默认题库';
        if ($question['bank_id'] > 0) {
            $sql = "SELECT name FROM tiku_banks WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':id', $question['bank_id']);
            $stmt->execute();
            $bank = $stmt->fetch(PDO::FETCH_ASSOC);
            $bank_name = $bank ? $bank['name'] : '未知题库';
        }
        echo "- 题库名称: {$bank_name}\n";
        
        echo "\n题目处理成功！\n";
    }
    
} catch (Exception $e) {
    echo "错误：" . $e->getMessage() . "\n";
    echo "错误文件：" . $e->getFile() . "\n";
    echo "错误行号：" . $e->getLine() . "\n";
    echo "错误堆栈：" . $e->getTraceAsString() . "\n";
}
?>