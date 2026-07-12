<?php
/**
 * srvpro2 PostgreSQL 只读连接
 *
 * 该连接与 RAMSAY 自身的 SQLite Database 完全分离，避免在 PostgreSQL 上
 * 执行 SQLite 初始化和 PRAGMA 语句。
 */
class Srvpro2Database {
    /**
     * 单例实例
     * @var Srvpro2Database
     */
    private static $instance;

    /**
     * PDO 实例
     * @var PDO|null
     */
    private $pdo;

    /**
     * 构造函数
     */
    private function __construct() {
        $this->pdo = null;
    }

    /**
     * 获取单例实例
     *
     * @return Srvpro2Database 数据库实例
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * 获取 PDO 连接
     *
     * @return PDO PDO 实例
     * @throws RuntimeException 扩展缺失或连接失败时抛出
     */
    public function getConnection() {
        if ($this->pdo instanceof PDO) {
            return $this->pdo;
        }

        if (!class_exists('PDO') || !in_array('pgsql', PDO::getAvailableDrivers(), true)) {
            throw new RuntimeException('缺少 PHP pdo_pgsql 扩展，无法连接 srvpro2 PostgreSQL');
        }

        $host = defined('SRVPRO2_DB_HOST') ? (string)SRVPRO2_DB_HOST : '127.0.0.1';
        $port = defined('SRVPRO2_DB_PORT') ? (int)SRVPRO2_DB_PORT : 5432;
        $database = defined('SRVPRO2_DB_NAME') ? (string)SRVPRO2_DB_NAME : 'srvpro2';
        $username = defined('SRVPRO2_DB_USER') ? (string)SRVPRO2_DB_USER : '';
        $password = defined('SRVPRO2_DB_PASSWORD') ? (string)SRVPRO2_DB_PASSWORD : '';
        $sslMode = defined('SRVPRO2_DB_SSLMODE') ? strtolower((string)SRVPRO2_DB_SSLMODE) : 'prefer';
        $connectTimeout = defined('SRVPRO2_DB_CONNECT_TIMEOUT')
            ? (int)SRVPRO2_DB_CONNECT_TIMEOUT
            : 10;

        $this->assertSafeDsnValue($host, '数据库主机');
        $this->assertSafeDsnValue($database, '数据库名');

        if ($username === '') {
            throw new RuntimeException('尚未配置 SRVPRO2_DB_USER 只读账号');
        }

        if ($port < 1 || $port > 65535) {
            throw new RuntimeException('srvpro2 PostgreSQL 端口配置无效');
        }

        $allowedSslModes = ['disable', 'allow', 'prefer', 'require', 'verify-ca', 'verify-full'];
        if (!in_array($sslMode, $allowedSslModes, true)) {
            throw new RuntimeException('SRVPRO2_DB_SSLMODE 配置无效');
        }
        if ($connectTimeout < 1 || $connectTimeout > 300) {
            throw new RuntimeException('SRVPRO2_DB_CONNECT_TIMEOUT 配置无效');
        }

        $dsn = sprintf(
            'pgsql:host=%s;port=%d;dbname=%s;sslmode=%s;connect_timeout=%d',
            $host,
            $port,
            $database,
            $sslMode,
            $connectTimeout
        );

        try {
            $this->pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            $applicationStatement = $this->pdo->prepare(
                "SELECT set_config('application_name', :application_name, false)"
            );
            $applicationStatement->execute(['application_name' => 'RAMSAY']);

            $statementTimeout = defined('SRVPRO2_DB_STATEMENT_TIMEOUT_MS')
                ? max(0, (int)SRVPRO2_DB_STATEMENT_TIMEOUT_MS)
                : 60000;
            if ($statementTimeout > 0) {
                $timeoutStatement = $this->pdo->prepare(
                    "SELECT set_config('statement_timeout', :statement_timeout, false)"
                );
                $timeoutStatement->execute(['statement_timeout' => (string)$statementTimeout]);
            }
        } catch (PDOException $e) {
            $this->pdo = null;
            throw new RuntimeException('连接 srvpro2 PostgreSQL 失败：' . $e->getMessage(), 0, $e);
        }

        return $this->pdo;
    }

    /**
     * 执行参数化查询
     *
     * @param string $sql SQL 语句
     * @param array $params 参数
     * @return PDOStatement 查询结果
     * @throws RuntimeException 查询失败时抛出
     */
    public function query($sql, $params = []) {
        try {
            $statement = $this->getConnection()->prepare($sql);
            foreach ($params as $name => $value) {
                $parameter = is_int($name) ? $name + 1 : ':' . ltrim($name, ':');
                $type = PDO::PARAM_STR;
                if (is_int($value)) {
                    $type = PDO::PARAM_INT;
                } elseif (is_bool($value)) {
                    $type = PDO::PARAM_BOOL;
                } elseif ($value === null) {
                    $type = PDO::PARAM_NULL;
                }
                $statement->bindValue($parameter, $value, $type);
            }
            $statement->execute();
            return $statement;
        } catch (PDOException $e) {
            throw new RuntimeException('查询 srvpro2 PostgreSQL 失败：' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 获取多行数据
     *
     * @param string $sql SQL 语句
     * @param array $params 参数
     * @return array 结果集
     */
    public function getRows($sql, $params = []) {
        return $this->query($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 获取单行数据
     *
     * @param string $sql SQL 语句
     * @param array $params 参数
     * @return array|null 查询结果
     */
    public function getRow($sql, $params = []) {
        $row = $this->query($sql, $params)->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    /**
     * 获取单个值
     *
     * @param string $sql SQL 语句
     * @param array $params 参数
     * @return mixed 查询结果
     */
    public function getValue($sql, $params = []) {
        return $this->query($sql, $params)->fetchColumn();
    }

    /**
     * 获取带 schema 的安全表名
     *
     * @param string $table 表名
     * @return string 双引号引用的表名
     */
    public function getTableName($table) {
        $schema = defined('SRVPRO2_DB_SCHEMA') ? (string)SRVPRO2_DB_SCHEMA : 'public';
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $schema)) {
            throw new RuntimeException('SRVPRO2_DB_SCHEMA 配置无效');
        }
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $table)) {
            throw new InvalidArgumentException('srvpro2 表名无效');
        }

        return '"' . $schema . '"."' . $table . '"';
    }

    /**
     * 校验 DSN 片段
     *
     * @param string $value 配置值
     * @param string $label 配置名称
     * @return void
     */
    private function assertSafeDsnValue($value, $label) {
        if ($value === '' || preg_match('/[;\r\n]/', $value)) {
            throw new RuntimeException('srvpro2 ' . $label . '配置无效');
        }
    }
}
