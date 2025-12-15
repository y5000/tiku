<?php
class Database {
    private $host = 'localhost';
    private $db_name = 'tiku';
    private $username = 'tiku';  // 修改为您的数据库用户名
    private $password = '111111';      // 修改为您的数据库密码
    private $prefix = 'tiku1_';  // 表前缀
    private $conn;
    
    
    // 数据库连接
    public function getConnection() {
        // 检查连接是否存在且有效
        try {
            if ($this->conn instanceof PDO) {
                // 尝试执行一个简单查询来验证连接是否有效
                $this->conn->query("SELECT 1");
                return $this->conn;
            }
        } catch (PDOException $e) {
            // 连接无效，释放连接资源
            $this->conn = null;
        }
        
        // 重新建立连接
        $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4;connect_timeout=10";
        
        try {
            $this->conn = new PDO(
                $dsn,
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_PERSISTENT => false, // 关闭持久连接
                    PDO::ATTR_TIMEOUT => 30, // 设置连接超时
                    PDO::ATTR_EMULATE_PREPARES => false, // 禁用预处理语句模拟
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
            return $this->conn;
        } catch (PDOException $e) {
            // 连接失败，抛出异常
            throw new Exception("数据库连接失败: " . $e->getMessage() . " (DSN: $dsn, Username: {$this->username})");
        }
    }
    
    // 查找数据
    public function find($table, $conditions = [], $fields = '*') {
        $conn = $this->getConnection();
        $where = '';
        $params = [];
        
        // 添加表前缀
        $table = $this->prefix . $table;
        
        if (!empty($conditions)) {
            $whereParts = [];
            foreach ($conditions as $field => $value) {
                $whereParts[] = "$field = :$field";
                $params[":$field"] = $value;
            }
            $where = 'WHERE ' . implode(' AND ', $whereParts);
        }
        
        $query = "SELECT $fields FROM $table $where";
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // 查找单条数据
    public function findOne($table, $conditions = [], $fields = '*') {
        $conn = $this->getConnection();
        $where = '';
        $params = [];
        
        // 添加表前缀
        $table = $this->prefix . $table;
        
        if (!empty($conditions)) {
            $whereParts = [];
            foreach ($conditions as $field => $value) {
                $whereParts[] = "$field = :$field";
                $params[":$field"] = $value;
            }
            $where = 'WHERE ' . implode(' AND ', $whereParts);
        }
        
        $query = "SELECT $fields FROM $table $where LIMIT 1";
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // 添加数据
    public function add($table, $data) {
        $conn = $this->getConnection();
        $fields = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        // 添加表前缀
        $table = $this->prefix . $table;
        
        $query = "INSERT INTO $table ($fields) VALUES ($placeholders)";
        $stmt = $conn->prepare($query);
        
        foreach ($data as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        
        if ($stmt->execute()) {
            return $conn->lastInsertId();
        }
        return false;
    }
    
    // 更新数据
    public function update($table, $data, $conditions) {
        $conn = $this->getConnection();
        
        $setParts = [];
        $params = [];
        
        // 添加表前缀
        $table = $this->prefix . $table;
        
        foreach ($data as $field => $value) {
            $setParts[] = "$field = :set_$field";
            $params[":set_$field"] = $value;
        }
        
        $whereParts = [];
        foreach ($conditions as $field => $value) {
            $whereParts[] = "$field = :where_$field";
            $params[":where_$field"] = $value;
        }
        
        $query = "UPDATE $table SET " . implode(', ', $setParts) . 
                 " WHERE " . implode(' AND ', $whereParts);
        
        $stmt = $conn->prepare($query);
        return $stmt->execute($params);
    }
    
    // 删除数据
    public function del($table, $conditions) {
        $conn = $this->getConnection();
        
        $whereParts = [];
        $params = [];
        
        // 添加表前缀
        $table = $this->prefix . $table;
        
        foreach ($conditions as $field => $value) {
            $whereParts[] = "$field = :$field";
            $params[":$field"] = $value;
        }
        
        $query = "DELETE FROM $table WHERE " . implode(' AND ', $whereParts);
        $stmt = $conn->prepare($query);
        return $stmt->execute($params);
    }
    
    // 执行自定义查询
    public function query($sql, $params = []) {
        $conn = $this->getConnection();
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        
        if (stripos($sql, 'SELECT') === 0) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            return $stmt->rowCount();
        }
    }
    
    // 检查表是否存在
    public function tableExists($table) {
        $conn = $this->getConnection();
        
        // 添加表前缀
        $table = $this->prefix . $table;
        
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        return $result->rowCount() > 0;
    }
    
    // 获取表前缀
    public function getPrefix() {
        return $this->prefix;
    }
}
?>
