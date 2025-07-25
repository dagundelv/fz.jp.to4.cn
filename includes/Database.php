<?php
class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("数据库连接失败: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    // 执行查询
    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                die("SQL错误: " . $e->getMessage() . "<br>SQL: " . $sql);
            } else {
                die("数据库操作失败");
            }
        }
    }
    
    // 获取所有结果
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    // 获取单行结果
    public function fetch($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    // 插入数据
    public function insert($table, $data) {
        $fields = array_keys($data);
        $placeholders = ':' . implode(', :', $fields);
        $sql = "INSERT INTO `{$table}` (`" . implode('`, `', $fields) . "`) VALUES ({$placeholders})";
        
        $stmt = $this->query($sql, $data);
        return $this->connection->lastInsertId();
    }
    
    // 更新数据
    public function update($table, $data, $where, $whereParams = []) {
        $set = [];
        foreach ($data as $field => $value) {
            $set[] = "`{$field}` = :{$field}";
        }
        $sql = "UPDATE `{$table}` SET " . implode(', ', $set) . " WHERE {$where}";
        
        $params = array_merge($data, $whereParams);
        return $this->query($sql, $params);
    }
    
    // 删除数据
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM `{$table}` WHERE {$where}";
        return $this->query($sql, $params);
    }
    
    // 获取记录数
    public function count($table, $where = '', $params = []) {
        $sql = "SELECT COUNT(*) as count FROM `{$table}`";
        if ($where) {
            $sql .= " WHERE {$where}";
        }
        $result = $this->fetch($sql, $params);
        return $result['count'];
    }
    
    // 检查记录是否存在
    public function exists($table, $where, $params = []) {
        return $this->count($table, $where, $params) > 0;
    }
    
    // 开始事务
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    // 提交事务
    public function commit() {
        return $this->connection->commit();
    }
    
    // 回滚事务
    public function rollback() {
        return $this->connection->rollback();
    }
    
    // 防止克隆
    private function __clone() {}
    
    // 防止反序列化
    private function __wakeup() {}
}
?>