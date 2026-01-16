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
                vote_link TEXT UNIQUE NOT NULL,
                is_series_vote INTEGER DEFAULT 0,
                setcode INTEGER DEFAULT 0
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
                identifier TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (vote_id) REFERENCES votes(id),
                UNIQUE (vote_id, ip_address)
            )
        ');

        // 检查是否需要添加 identifier 字段
        try {
            $columns = $this->pdo->query("PRAGMA table_info(vote_records)")->fetchAll(PDO::FETCH_ASSOC);
            $hasIdentifier = false;

            foreach ($columns as $column) {
                if ($column['name'] === 'identifier') {
                    $hasIdentifier = true;
                    break;
                }
            }

            if (!$hasIdentifier) {
                $this->pdo->exec('ALTER TABLE vote_records ADD COLUMN identifier TEXT');
            }
        } catch (PDOException $e) {
            Utils::debug('检查 identifier 字段失败', ['错误' => $e->getMessage()]);
        }

        // 检查是否需要添加系列投票相关字段
        try {
            $columns = $this->pdo->query("PRAGMA table_info(votes)")->fetchAll(PDO::FETCH_ASSOC);
            $hasSeriesVote = false;
            $hasSetcode = false;
            $hasAdvancedVote = false;
            $hasCardIds = false;

            foreach ($columns as $column) {
                if ($column['name'] === 'is_series_vote') {
                    $hasSeriesVote = true;
                }
                if ($column['name'] === 'setcode') {
                    $hasSetcode = true;
                }
                if ($column['name'] === 'is_advanced_vote') {
                    $hasAdvancedVote = true;
                }
                if ($column['name'] === 'card_ids') {
                    $hasCardIds = true;
                }
            }

            if (!$hasSeriesVote) {
                $this->pdo->exec('ALTER TABLE votes ADD COLUMN is_series_vote INTEGER DEFAULT 0');
            }
            if (!$hasSetcode) {
                $this->pdo->exec('ALTER TABLE votes ADD COLUMN setcode INTEGER DEFAULT 0');
            }
            if (!$hasAdvancedVote) {
                $this->pdo->exec('ALTER TABLE votes ADD COLUMN is_advanced_vote INTEGER DEFAULT 0');
            }
            if (!$hasCardIds) {
                $this->pdo->exec('ALTER TABLE votes ADD COLUMN card_ids TEXT');
            }

            // 检查vote_records表是否需要添加card_id字段
            $recordColumns = $this->pdo->query("PRAGMA table_info(vote_records)")->fetchAll(PDO::FETCH_ASSOC);
            $hasRecordCardId = false;

            foreach ($recordColumns as $column) {
                if ($column['name'] === 'card_id') {
                    $hasRecordCardId = true;
                    break;
                }
            }

            if (!$hasRecordCardId) {
                $this->pdo->exec('ALTER TABLE vote_records ADD COLUMN card_id INTEGER DEFAULT NULL');
            }

            // 检查并修复唯一约束，支持高级投票
            $this->fixVoteRecordsUniqueConstraint();
        } catch (PDOException $e) {
            Utils::debug('检查投票字段失败', ['错误' => $e->getMessage()]);
        }

        // 创建投票周期表
        $this->pdo->exec('
            CREATE TABLE IF NOT EXISTS vote_cycles (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                current_cycle INTEGER NOT NULL DEFAULT 1,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ');

        // 创建作者映射表
        $this->pdo->exec('
            CREATE TABLE IF NOT EXISTS author_mappings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                card_prefix TEXT NOT NULL,
                author_name TEXT NOT NULL,
                alias TEXT,
                contact TEXT,
                notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE (card_prefix)
            )
        ');

        // 创建召唤词投稿表
        $this->pdo->exec('
            CREATE TABLE IF NOT EXISTS dialogue_submissions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id TEXT NOT NULL,
                card_id TEXT NOT NULL,
                dialogue TEXT NOT NULL,
                author_id TEXT NOT NULL,
                status TEXT NOT NULL DEFAULT "pending",
                reviewed_by TEXT,
                reviewed_at TIMESTAMP,
                reject_reason TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ');

        // 创建投票者封禁表
        $this->pdo->exec('
            CREATE TABLE IF NOT EXISTS voter_bans (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                voter_identifier TEXT NOT NULL UNIQUE,
                ban_level INTEGER NOT NULL,
                reason TEXT,
                banned_by TEXT NOT NULL,
                banned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                is_active INTEGER DEFAULT 1
            )
        ');

        // 创建卡组表
        $this->pdo->exec('
            CREATE TABLE IF NOT EXISTS decks (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                main_deck TEXT NOT NULL,
                extra_deck TEXT,
                side_deck TEXT,
                uploader_id TEXT NOT NULL,
                uploader_name TEXT,
                is_admin_deck INTEGER DEFAULT 0,
                deck_group TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ');

        // 创建卡组评论表
        $this->pdo->exec('
            CREATE TABLE IF NOT EXISTS deck_comments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                deck_id INTEGER NOT NULL,
                user_id TEXT NOT NULL,
                user_name TEXT,
                comment TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (deck_id) REFERENCES decks(id)
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
     * 修复vote_records表的唯一约束，支持高级投票
     */
    private function fixVoteRecordsUniqueConstraint() {
        try {
            // 检查当前的索引
            $indexes = $this->pdo->query("PRAGMA index_list(vote_records)")->fetchAll(PDO::FETCH_ASSOC);
            $hasOldUniqueIndex = false;
            $hasNewUniqueIndex = false;

            foreach ($indexes as $index) {
                if ($index['unique'] == 1) {
                    $indexInfo = $this->pdo->query("PRAGMA index_info('{$index['name']}')")->fetchAll(PDO::FETCH_ASSOC);
                    $columns = array_column($indexInfo, 'name');

                    if (count($columns) == 2 && in_array('vote_id', $columns) && in_array('ip_address', $columns)) {
                        $hasOldUniqueIndex = true;
                    } elseif (count($columns) == 3 && in_array('vote_id', $columns) && in_array('ip_address', $columns) && in_array('card_id', $columns)) {
                        $hasNewUniqueIndex = true;
                    }
                }
            }

            // 如果有旧的约束但没有新的约束，需要重建表
            if ($hasOldUniqueIndex && !$hasNewUniqueIndex) {
                Utils::debug('修复vote_records唯一约束', ['开始重建表' => true]);

                // 开始事务
                $this->pdo->beginTransaction();

                // 创建临时表
                $this->pdo->exec('
                    CREATE TABLE vote_records_temp (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        vote_id INTEGER NOT NULL,
                        user_id TEXT NOT NULL,
                        ip_address TEXT NOT NULL,
                        status INTEGER NOT NULL,
                        comment TEXT,
                        identifier TEXT,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        card_id INTEGER DEFAULT NULL,
                        FOREIGN KEY (vote_id) REFERENCES votes(id),
                        UNIQUE (vote_id, ip_address, card_id)
                    )
                ');

                // 复制数据
                $this->pdo->exec('
                    INSERT INTO vote_records_temp (id, vote_id, user_id, ip_address, status, comment, identifier, created_at, card_id)
                    SELECT id, vote_id, user_id, ip_address, status, comment, identifier, created_at, card_id
                    FROM vote_records
                ');

                // 删除旧表
                $this->pdo->exec('DROP TABLE vote_records');

                // 重命名新表
                $this->pdo->exec('ALTER TABLE vote_records_temp RENAME TO vote_records');

                // 提交事务
                $this->pdo->commit();

                Utils::debug('修复vote_records唯一约束', ['完成重建表' => true]);
            }
        } catch (PDOException $e) {
            // 如果出错，回滚事务
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            Utils::debug('修复vote_records唯一约束失败', ['错误' => $e->getMessage()]);
        }
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
