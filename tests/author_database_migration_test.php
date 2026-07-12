<?php
/**
 * 作者映射表重复前缀迁移集成测试。
 *
 * 运行方式：php tests/author_database_migration_test.php
 */

if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
    echo "[SKIP] 当前PHP未安装pdo_sqlite，跳过作者映射数据库迁移集成测试。\n";
    exit(0);
}

/**
 * Database迁移日志测试替身。
 */
class Utils {
    /**
     * 忽略测试中的调试日志。
     *
     * @param string $message 日志消息
     * @param array $context 日志上下文
     * @return void
     */
    public static function debug($message, $context = []) {
        // 集成测试无需输出迁移调试日志。
    }
}

$databasePath = tempnam(sys_get_temp_dir(), 'ramsay_author_migration_');
if ($databasePath === false) {
    echo "[FAIL] 无法创建临时SQLite数据库。\n";
    exit(1);
}

$legacyPdo = new PDO('sqlite:' . $databasePath);
$legacyPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$legacyPdo->exec('
    CREATE TABLE author_mappings (
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
$legacyPdo->exec(
    "INSERT INTO author_mappings (id, card_prefix, author_name, alias, contact, notes) " .
    "VALUES (7, '337', '人工权威作者', '保留别名', '保留联系方式', '保留备注')"
);
$legacyPdo->exec('
    CREATE TABLE card_match_rules (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        database_file TEXT,
        match_field TEXT NOT NULL DEFAULT "desc",
        match_operator TEXT NOT NULL DEFAULT "contains",
        match_value TEXT NOT NULL,
        author_name TEXT NOT NULL,
        priority INTEGER NOT NULL DEFAULT 100,
        is_case_sensitive INTEGER NOT NULL DEFAULT 0,
        is_enabled INTEGER NOT NULL DEFAULT 1,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
');
$legacyPdo->exec(
    "INSERT INTO card_match_rules (id, database_file, match_value, author_name) " .
    "VALUES (3, 'no42.cdb', 'Copyright 本体', '本体作者')"
);
$legacyPdo = null;

define('DB_PATH', $databasePath);
define('DEBUG_MODE', true);
require_once __DIR__ . '/../includes/Core/Database.php';

$db = Database::getInstance();
$legacyRow = $db->getRow('SELECT * FROM author_mappings WHERE id = :id', [':id' => 7]);
$secondId = $db->insert('author_mappings', [
    'card_prefix' => '337',
    'author_name' => '同前缀第二段',
    'card_id_length' => 8,
    'card_id_start' => 33750000,
    'card_id_end' => 33759999,
    'priority' => 200,
    'alias' => null,
    'contact' => null,
    'notes' => '第二段',
    'created_at' => date('Y-m-d H:i:s'),
    'updated_at' => date('Y-m-d H:i:s')
]);
$samePrefixCount = (int)$db->getValue(
    'SELECT COUNT(*) FROM author_mappings WHERE card_prefix = :card_prefix',
    [':card_prefix' => '337']
);
$indexNames = array_column($db->getRows('PRAGMA index_list(author_mappings)'), 'name');
$legacyRule = $db->getRow('SELECT * FROM card_match_rules WHERE id = :id', [':id' => 3]);

$passed = $legacyRow !== false &&
    (int)$legacyRow['id'] === 7 &&
    $legacyRow['author_name'] === '人工权威作者' &&
    $legacyRow['alias'] === '保留别名' &&
    $legacyRow['contact'] === '保留联系方式' &&
    $legacyRow['notes'] === '保留备注' &&
    (int)$secondId === 8 &&
    $samePrefixCount === 2 &&
    in_array('idx_author_mappings_resolution', $indexNames, true) &&
    $legacyRule !== false &&
    $legacyRule['target_type'] === 'author' &&
    $legacyRule['target_value'] === '本体作者';

// 释放Windows上的SQLite文件句柄后再清理测试文件。
$databaseReflection = new ReflectionClass('Database');
$instanceProperty = $databaseReflection->getProperty('instance');
$instanceProperty->setAccessible(true);
$instanceProperty->setValue(null, null);
$db = null;
gc_collect_cycles();
@unlink($databasePath);

if (!$passed) {
    echo "[FAIL] 作者映射或文本规则迁移未完整保留旧数据。\n";
    exit(1);
}

echo "[PASS] 作者映射迁移允许同前缀区间，旧文本规则迁移为作者目标。\n";
