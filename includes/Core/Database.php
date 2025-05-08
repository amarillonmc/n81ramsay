<?php
/**
 * 数据库操作类
 * 
 * 负责与SQLite数据库交互，提供数据库操作接口
 */
class Database {
    /**
     * PDO实例
     * @var PDO
     */
    private $pdo;
    
    /**
     * 单例实例
     * @var Database
     */
    private static $instance;
    
    /**
     * 构造函数
     * 
     * 初始化PDO连接
     */
    private function __construct() {
        try {
            // 检查数据库文件是否存在，如果不存在则创建
            $dbFile = DB_PATH;
            $dbDir = dirname($dbFile);
            
            if (!file_exists($dbDir)) {
                mkdir($dbDir, 0755, true);
            }
            
            // 创建PDO连接
            $this->pdo = new PDO('sqlite:' . $dbFile);
            
            // 设置错误模式
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // 启用外键约束
            $this->pdo->exec('PRAGMA foreign_keys = ON');
            
            // 初始化数据库表
            $this->initTables();
            
        } catch (PDOException $e) {
            die('数据库连接失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 获取单例实例
     * 
     * @return Database 数据库实例
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * 初始化数据库表
     */
    private function initTables() {
        // 创建投票表
        $this->pdo->exec('
            CREATE TABLE IF NOT EXISTS votes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                card_id INTEGER NOT NULL,
                environment_id INTEGER NOT NULL,
                status INTEGER NOT NULL,
                reason TEXT,
                initiator_id TEXT NOT NULL,
                vote_cycle INTEGER NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                is_closed INTEGER DEFAULT 0,
                vote_link TEXT UNIQUE NOT NULL
            )
        ');
        
        // 创建投票记录表
        $this->pdo->exec('
            CREATE TABLE IF NOT EXISTS vote_records (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                vote_id INTEGER NOT NULL,
                user_id TEXT NOT NULL,
                ip_address TEXT NOT NULL,
                status INTEGER NOT NULL,
                comment TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (vote_id) REFERENCES votes(id),
                UNIQUE (vote_id, ip_address)
            )
        ');
        
        // 创建投票周期表
        $this->pdo->exec('
            CREATE TABLE IF NOT EXISTS vote_cycles (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                current_cycle INTEGER NOT NULL DEFAULT 1,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ');
        
        // 检查投票周期表是否有数据，如果没有则插入初始数据
        $stmt = $this->pdo->query('SELECT COUNT(*) FROM vote_cycles');
        $count = $stmt->fetchColumn();
        
        if ($count == 0) {
            $this->pdo->exec('
                INSERT INTO vote_cycles (current_cycle, updated_at)
                VALUES (1, CURRENT_TIMESTAMP)
            ');
        }
    }
    
    /**
     * 执行SQL查询
     * 
     * @param string $sql SQL语句
     * @param array $params 参数数组
     * @return PDOStatement 查询结果
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                die('SQL查询错误: ' . $e->getMessage() . '<br>SQL: ' . $sql);
            } else {
                error_log('SQL查询错误: ' . $e->getMessage() . ' SQL: ' . $sql);
                die('数据库操作失败，请联系管理员');
            }
        }
    }
    
    /**
     * 获取单行数据
     * 
     * @param string $sql SQL语句
     * @param array $params 参数数组
     * @return array|false 查询结果
     */
    public function getRow($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * 获取多行数据
     * 
     * @param string $sql SQL语句
     * @param array $params 参数数组
     * @return array 查询结果
     */
    public function getRows($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * 获取单个值
     * 
     * @param string $sql SQL语句
     * @param array $params 参数数组
     * @return mixed 查询结果
     */
    public function getValue($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchColumn();
    }
    
    /**
     * 插入数据
     * 
     * @param string $table 表名
     * @param array $data 数据数组
     * @return int 最后插入的ID
     */
    public function insert($table, $data) {
        $fields = array_keys($data);
        $placeholders = array_map(function($field) {
            return ':' . $field;
        }, $fields);
        
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $table,
            implode(', ', $fields),
            implode(', ', $placeholders)
        );
        
        $this->query($sql, $data);
        return $this->pdo->lastInsertId();
    }
    
    /**
     * 更新数据
     * 
     * @param string $table 表名
     * @param array $data 数据数组
     * @param string $where 条件语句
     * @param array $whereParams 条件参数
     * @return int 受影响的行数
     */
    public function update($table, $data, $where, $whereParams = []) {
        $setParts = array_map(function($field) {
            return $field . ' = :' . $field;
        }, array_keys($data));
        
        $sql = sprintf(
            'UPDATE %s SET %s WHERE %s',
            $table,
            implode(', ', $setParts),
            $where
        );
        
        $params = array_merge($data, $whereParams);
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    /**
     * 删除数据
     * 
     * @param string $table 表名
     * @param string $where 条件语句
     * @param array $params 条件参数
     * @return int 受影响的行数
     */
    public function delete($table, $where, $params = []) {
        $sql = sprintf('DELETE FROM %s WHERE %s', $table, $where);
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    /**
     * 开始事务
     */
    public function beginTransaction() {
        $this->pdo->beginTransaction();
    }
    
    /**
     * 提交事务
     */
    public function commit() {
        $this->pdo->commit();
    }
    
    /**
     * 回滚事务
     */
    public function rollBack() {
        $this->pdo->rollBack();
    }
    
    /**
     * 获取当前投票周期
     * 
     * @return int 当前投票周期
     */
    public function getCurrentVoteCycle() {
        return $this->getValue('SELECT current_cycle FROM vote_cycles ORDER BY id DESC LIMIT 1');
    }
    
    /**
     * 更新投票周期
     * 
     * @param int $cycle 新的投票周期
     * @return bool 是否成功
     */
    public function updateVoteCycle($cycle) {
        $this->update('vote_cycles', 
            ['current_cycle' => $cycle, 'updated_at' => date('Y-m-d H:i:s')], 
            'id = (SELECT id FROM vote_cycles ORDER BY id DESC LIMIT 1)'
        );
        return true;
    }
}
