<?php
/**
 * 作者管理模型轻量测试
 *
 * 运行方式：php tests/author_models_test.php
 */

/**
 * 文件缓存失效测试替身。
 */
class AuthorStats {
    /**
     * 失效调用次数。
     * @var int
     */
    public static $invalidations = 0;

    /**
     * 记录文件缓存失效调用。
     *
     * @return bool
     */
    public static function invalidateCacheFiles() {
        self::$invalidations++;
        return true;
    }
}

/**
 * 解析器内存缓存失效测试替身。
 */
class CardParser {
    /**
     * 失效调用次数。
     * @var int
     */
    public static $invalidations = 0;

    /**
     * 记录解析器缓存失效调用。
     *
     * @return void
     */
    public static function invalidateAuthorRuleCache() {
        self::$invalidations++;
    }
}

/**
 * 作者模型使用的内存数据库测试替身。
 */
class Database {
    /**
     * 单例实例。
     * @var Database|null
     */
    private static $instance;

    /**
     * 内存表数据。
     * @var array
     */
    private $tables = [
        'author_mappings' => [],
        'card_match_rules' => []
    ];

    /**
     * 各表下一ID。
     * @var array
     */
    private $nextIds = [
        'author_mappings' => 1,
        'card_match_rules' => 1
    ];

    /**
     * 获取测试数据库单例。
     *
     * @return Database
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 写入指定表的测试初始数据。
     *
     * @param string $table 表名
     * @param array $rows 数据行
     * @return void
     */
    public function seed($table, $rows) {
        $this->tables[$table] = $rows;
        foreach ($rows as $row) {
            if (isset($row['id'])) {
                $this->nextIds[$table] = max($this->nextIds[$table], (int)$row['id'] + 1);
            }
        }
    }

    /**
     * 返回指定内存表。
     *
     * @param string $table 表名
     * @return array
     */
    public function getTable($table) {
        return $this->tables[$table];
    }

    /**
     * 模拟多行查询。
     *
     * @param string $sql SQL文本
     * @param array $params 查询参数
     * @return array
     */
    public function getRows($sql, $params = []) {
        $table = strpos($sql, 'card_match_rules') !== false ? 'card_match_rules' : 'author_mappings';
        $rows = $this->tables[$table];
        if (strpos($sql, 'is_enabled = 1') !== false) {
            $rows = array_values(array_filter($rows, function($row) {
                return isset($row['is_enabled']) && (int)$row['is_enabled'] === 1;
            }));
        }
        return $rows;
    }

    /**
     * 模拟单行查询。
     *
     * @param string $sql SQL文本
     * @param array $params 查询参数
     * @return array|false
     */
    public function getRow($sql, $params = []) {
        $rows = $this->getRows($sql, $params);
        foreach ($rows as $row) {
            if (isset($params[':card_prefix']) && (string)$row['card_prefix'] !== (string)$params[':card_prefix']) {
                continue;
            }
            if (isset($params[':id']) && (int)$row['id'] !== (int)$params[':id']) {
                continue;
            }
            return $row;
        }
        return false;
    }

    /**
     * 模拟插入。
     *
     * @param string $table 表名
     * @param array $data 写入数据
     * @return int
     */
    public function insert($table, $data) {
        $data['id'] = $this->nextIds[$table]++;
        $this->tables[$table][] = $data;
        return $data['id'];
    }

    /**
     * 模拟更新。
     *
     * @param string $table 表名
     * @param array $data 更新数据
     * @param string $where 条件文本
     * @param array $params 条件参数
     * @return int
     */
    public function update($table, $data, $where, $params = []) {
        $affected = 0;
        foreach ($this->tables[$table] as &$row) {
            $matches = isset($params[':id'])
                ? (int)$row['id'] === (int)$params[':id']
                : (string)$row['card_prefix'] === (string)$params[':card_prefix'];
            if ($matches) {
                $row = array_merge($row, $data);
                $affected++;
            }
        }
        unset($row);
        return $affected;
    }

    /**
     * 模拟删除。
     *
     * @param string $table 表名
     * @param string $where 条件文本
     * @param array $params 条件参数
     * @return int
     */
    public function delete($table, $where, $params = []) {
        $before = count($this->tables[$table]);
        $this->tables[$table] = array_values(array_filter($this->tables[$table], function($row) use ($params) {
            if (isset($params[':id'])) {
                return (int)$row['id'] !== (int)$params[':id'];
            }
            return (string)$row['card_prefix'] !== (string)$params[':card_prefix'];
        }));
        return $before - count($this->tables[$table]);
    }
}

require_once __DIR__ . '/../includes/Models/AuthorMapping.php';
require_once __DIR__ . '/../includes/Models/CardMatchRule.php';

$failures = 0;

/**
 * 断言模型测试值严格相等。
 *
 * @param mixed $expected 预期值
 * @param mixed $actual 实际值
 * @param string $message 断言说明
 * @return void
 */
function assertSameModelValue($expected, $actual, $message) {
    global $failures;
    if ($expected === $actual) {
        echo "[PASS] {$message}\n";
        return;
    }
    $failures++;
    echo "[FAIL] {$message}\n";
    echo '  expected: ' . var_export($expected, true) . "\n";
    echo '  actual:   ' . var_export($actual, true) . "\n";
}

$db = Database::getInstance();
$db->seed('author_mappings', [[
    'id' => 1,
    'card_prefix' => '337',
    'author_name' => '人工权威作者',
    'alias' => '保留别名',
    'contact' => '保留联系方式',
    'notes' => '保留备注',
    'priority' => 100
]]);

$mappingModel = new AuthorMapping();
$inserted = $mappingModel->importAuthorMappings([
    ['card_prefix' => '337', 'author_name' => '低置信自动作者'],
    ['card_prefix' => '2201771890', 'author_name' => '误识别QQ'],
    ['card_prefix' => '300', 'author_name' => '沃亚森斯奥克斯', 'card_id_length' => 8]
]);
assertSameModelValue(1, $inserted, 'strings候选只新增无冲突的1至4位作者码');
$preserved = $mappingModel->getAuthorMappingByPrefix('337');
assertSameModelValue('人工权威作者', $preserved['author_name'], 'strings导入不覆盖人工作者名');
assertSameModelValue('保留别名', $preserved['alias'], 'strings导入不清空人工别名');
assertSameModelValue(false, $mappingModel->getAuthorMappingByPrefix('2201771890'), 'strings导入拒绝QQ号前缀');

$secondRangeId = $mappingModel->addAuthorMapping(
    '337',
    '同前缀区间作者',
    null,
    null,
    '第二段人工区间',
    8,
    33750000,
    33759999,
    200
);
$prefix337Rows = array_values(array_filter($db->getTable('author_mappings'), function($row) {
    return (string)$row['card_prefix'] === '337';
}));
assertSameModelValue(2, count($prefix337Rows), '同一前缀可以新增多个显式区间而不覆盖');
assertSameModelValue('人工权威作者', $mappingModel->getAuthorMappingById(1)['author_name'], '新增同前缀区间保留原人工行');
assertSameModelValue(true, $mappingModel->updateAuthorMappingById(
    $secondRangeId,
    '337',
    '第二段更新作者',
    null,
    null,
    '只更新第二段',
    8,
    33750000,
    33759999,
    250
), '稳定ID可以精确更新同前缀中的指定区间');
assertSameModelValue('人工权威作者', $mappingModel->getAuthorMappingById(1)['author_name'], '按ID更新不会连带覆盖同前缀首行');
assertSameModelValue('第二段更新作者', $mappingModel->getAuthorMappingById($secondRangeId)['author_name'], '按ID更新命中目标区间');

$legacyId = $mappingModel->addAuthorMapping('337', '旧接口更新作者');
$prefix337Rows = array_values(array_filter($db->getTable('author_mappings'), function($row) {
    return (string)$row['card_prefix'] === '337';
}));
assertSameModelValue(1, (int)$legacyId, '旧版短参数add调用仍返回最早同前缀ID');
assertSameModelValue(2, count($prefix337Rows), '旧版短参数add调用保持upsert而不重复插入');
assertSameModelValue('旧接口更新作者', $mappingModel->getAuthorMappingById(1)['author_name'], '旧版前缀接口只更新最早稳定ID');
assertSameModelValue('第二段更新作者', $mappingModel->getAuthorMappingById($secondRangeId)['author_name'], '旧版前缀接口不覆盖同前缀其他区间');
assertSameModelValue(true, $mappingModel->deleteAuthorMappingById($secondRangeId), '稳定ID可以精确删除指定区间');
assertSameModelValue(false, $mappingModel->getAuthorMappingById($secondRangeId), '按ID删除后目标区间不存在');
assertSameModelValue('旧接口更新作者', $mappingModel->getAuthorMappingById(1)['author_name'], '按ID删除保留同前缀其他人工行');

$ruleModel = new CardMatchRule();
assertSameModelValue(false, $ruleModel->addRule([
    'database_file' => '../no42.cdb',
    'match_field' => 'desc',
    'match_operator' => 'contains',
    'match_value' => 'Copyright 本体',
    'author_name' => '本体作者'
]), '文本规则模型拒绝带路径的CDB来源');

$ruleId = $ruleModel->addRule([
    'database_file' => 'no42.cdb',
    'match_field' => 'desc',
    'match_operator' => 'line_equals',
    'match_value' => 'Copyright 本体',
    'author_name' => '本体作者',
    'priority' => 200,
    'is_case_sensitive' => 0,
    'is_enabled' => 1
]);
assertSameModelValue(1, $ruleId, '添加经过白名单验证的文本规则');
assertSameModelValue(true, $ruleModel->toggleRule($ruleId, 0), '文本规则可以停用');
assertSameModelValue(0, (int)$ruleModel->getRuleById($ruleId)['is_enabled'], '停用状态持久化');

$authorTargetRuleId = $ruleModel->addRule([
    'database_file' => 'no42.cdb',
    'match_field' => 'desc',
    'match_operator' => 'contains',
    'match_value' => '作者标记',
    'target_type' => 'author',
    'target_value' => '无区间作者',
    'is_enabled' => 1
]);
$seriesTargetRuleId = $ruleModel->addRule([
    'database_file' => 'no42.cdb',
    'match_field' => 'desc',
    'match_operator' => 'contains',
    'match_value' => '系列标记',
    'target_type' => 'series',
    'target_value' => '本体系列',
    'is_enabled' => 1
]);
assertSameModelValue(2, $authorTargetRuleId, '文本规则支持显式作者目标');
assertSameModelValue(3, $seriesTargetRuleId, '文本规则支持独立系列目标');
assertSameModelValue('', $ruleModel->getRuleById($seriesTargetRuleId)['author_name'], '系列目标不会写入兼容作者列');
$identifierWhitelist = $mappingModel->getAuthorIdentifierWhitelist();
assertSameModelValue(true, in_array('无区间作者', $identifierWhitelist, true), '作者文本规则进入作者身份白名单');
assertSameModelValue(false, in_array('本体系列', $identifierWhitelist, true), '系列文本规则不会进入作者身份白名单');
assertSameModelValue(true, AuthorStats::$invalidations > 0 && CardParser::$invalidations > 0, '作者写操作自动使文件与内存缓存失效');

if ($failures > 0) {
    echo "\n{$failures} test(s) failed.\n";
    exit(1);
}

echo "\nAll author model tests passed.\n";
