<?php
/**
 * 作者归属解析的轻量回归测试
 *
 * 运行方式：php tests/author_resolution_test.php
 */

/**
 * 测试用作者映射数据库桩
 */
class Database {
    /**
     * 单例
     * @var Database
     */
    private static $instance;

    /**
     * 作者映射
     * @var array
     */
    private $mappings = [];

    /**
     * 文本匹配规则
     * @var array
     */
    private $textRules = [];

    /**
     * 获取测试数据库
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
     * 替换测试映射
     *
     * @param array $mappings 映射列表
     * @return void
     */
    public function setMappings($mappings) {
        $this->mappings = $mappings;
    }

    /**
     * 替换测试文本规则
     *
     * @param array $rules 规则列表
     * @return void
     */
    public function setTextRules($rules) {
        $this->textRules = $rules;
    }

    /**
     * 模拟多行查询
     *
     * @param string $sql SQL
     * @param array $params 参数
     * @return array
     */
    public function getRows($sql, $params = []) {
        if (strpos($sql, 'card_match_rules') !== false) {
            return $this->textRules;
        }
        if (strpos($sql, 'author_mappings') !== false) {
            return $this->mappings;
        }
        return [];
    }

    /**
     * 模拟单行查询
     *
     * @param string $sql SQL
     * @param array $params 参数
     * @return array|false
     */
    public function getRow($sql, $params = []) {
        $prefix = isset($params[':card_prefix']) ? (string)$params[':card_prefix'] : '';
        foreach ($this->mappings as $mapping) {
            if ((string)$mapping['card_prefix'] === $prefix) {
                return $mapping;
            }
        }
        return false;
    }
}

// 本地轻量 PHP 运行时没有 mbstring；这些回退仅供本测试使用。
if (!function_exists('mb_check_encoding')) {
    function mb_check_encoding($value, $encoding = null) {
        return preg_match('//u', $value) === 1;
    }
}
if (!function_exists('mb_convert_encoding')) {
    function mb_convert_encoding($value, $toEncoding, $fromEncoding = null) {
        return $value;
    }
}
if (!function_exists('mb_strpos')) {
    function mb_strpos($haystack, $needle, $offset = 0, $encoding = null) {
        return strpos($haystack, $needle, $offset);
    }
}
if (!function_exists('mb_substr')) {
    function mb_substr($value, $start, $length = null, $encoding = null) {
        return $length === null ? substr($value, $start) : substr($value, $start, $length);
    }
}

if (!class_exists('Utils')) {
    class Utils {
        /**
         * 测试中忽略调试日志
         *
         * @param string $message 消息
         * @param array $context 上下文
         * @return void
         */
        public static function debug($message, $context = []) {
            // 无操作。
        }
    }
}

if (!defined('CARD_DATA_PATH')) {
    define('CARD_DATA_PATH', __DIR__ . '/fixtures/author_sources');
}
if (!defined('CARD_DATABASE_PRIORITY')) {
    define('CARD_DATABASE_PRIORITY', json_encode(['no81.cdb', 'no42.cdb']));
}

require_once __DIR__ . '/../includes/Core/AuthorResolver.php';
require_once __DIR__ . '/../includes/Core/CardParser.php';

$failures = 0;

/**
 * 严格相等断言
 *
 * @param mixed $expected 期望值
 * @param mixed $actual 实际值
 * @param string $message 消息
 * @return void
 */
function assertSameAuthorValue($expected, $actual, $message) {
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

$reflection = new ReflectionClass('CardParser');
$parser = $reflection->newInstanceWithoutConstructor();

Database::getInstance()->setMappings([
    ['card_prefix' => '1300', 'card_id_length' => 9, 'author_name' => '煌武院悠阳'],
]);

$author = $parser->getCardAuthor([
    'id' => 130001000,
    'name' => '区间优先级夹具',
    'desc' => '测试效果\nDoItYourself - CDB署名作者',
]);

assertSameAuthorValue(
    '煌武院悠阳',
    $author,
    '管理员维护的四位作者码和九位卡号范围优先于CDB署名'
);

$resolver = new AuthorResolver();
$prefixMappings = [
    ['id' => 1, 'card_prefix' => '130', 'card_id_length' => 8, 'author_name' => '乐乐'],
    ['id' => 2, 'card_prefix' => '1300', 'card_id_length' => 9, 'author_name' => '煌武院悠阳'],
    ['id' => 3, 'card_prefix' => '10', 'card_id_length' => 8, 'author_name' => '篁楠'],
];

$resolution = $resolver->resolve([
    'id' => 13000745,
    'name' => '八位卡号区间夹具',
    'desc' => 'DoItYourself - 错误署名',
    'database_file' => 'no81.cdb',
], $prefixMappings, [], []);
assertSameAuthorValue('乐乐', $resolution['author'], '八位130区间不会被九位1300区间抢占');
assertSameAuthorValue('manual_prefix', $resolution['source'], '解析结果记录人工区间来源');

$resolution = $resolver->resolve([
    'id' => 130001000,
    'name' => '九位卡号区间夹具',
    'desc' => '',
    'database_file' => 'no81.cdb',
], $prefixMappings, [], []);
assertSameAuthorValue('煌武院悠阳', $resolution['author'], '九位卡号命中四位作者码');

$resolution = $resolver->resolve([
    'id' => 1000360,
    'name' => '前导零夹具',
    'desc' => '',
    'database_file' => 'no81.cdb',
], $prefixMappings, [], []);
assertSameAuthorValue('篁楠', $resolution['author'], '七位整数卡号按八位形式补前导零后匹配作者码');

$resolution = $resolver->resolve([
    'id' => 1234500000,
    'name' => '五位前缀默认长度夹具',
    'desc' => '',
    'database_file' => 'no81.cdb',
], [
    ['id' => 8, 'card_prefix' => '12345', 'author_name' => '五位码作者'],
], [], []);
assertSameAuthorValue('五位码作者', $resolution['author'], '五位以上人工前缀留空总位数时不会被错误固定为八位');
assertSameAuthorValue(10, $parser->inferAuthorCardIdLength('12345'), 'CardParser与解析器统一五位前缀默认总位数');

$resolution = $resolver->resolve([
    'id' => 1234567890123456,
    'name' => '十六位前缀夹具',
    'desc' => '',
    'database_file' => 'no81.cdb',
], [
    ['id' => 9, 'card_prefix' => '1234567890123456', 'author_name' => '十六位码作者'],
], [], []);
assertSameAuthorValue('十六位码作者', $resolution['author'], '十六位人工前缀默认长度不会短于前缀本身');

$samePrefixRanges = [
    [
        'id' => 20,
        'card_prefix' => '337',
        'card_id_start' => 33700000,
        'card_id_end' => 33749999,
        'author_name' => '337前半区作者'
    ],
    [
        'id' => 21,
        'card_prefix' => '337',
        'card_id_start' => 33750000,
        'card_id_end' => 33799999,
        'author_name' => '337后半区作者'
    ],
];
$resolution = $resolver->resolve([
    'id' => 33755000,
    'name' => '同前缀多区间夹具',
    'desc' => '',
    'database_file' => 'no81.cdb',
], $samePrefixRanges, [], []);
assertSameAuthorValue('337后半区作者', $resolution['author'], '同一前缀的多个显式区间按卡号精确选择');

$resolution = $resolver->resolve([
    'id' => 33700000,
    'name' => '权威名称夹具',
    'desc' => 'DoItYourself - Tran. Aer. El. S. 之外的人',
    'database_file' => 'no81.cdb',
], [
    ['id' => 4, 'card_prefix' => '337', 'card_id_length' => 8, 'author_name' => 'Tran. Aer. El. S.'],
], [], []);
assertSameAuthorValue('Tran. Aer. El. S.', $resolution['author'], '人工作者名称不经过启发式截断');

$resolution = $resolver->resolve([
    'id' => 33500001,
    'name' => '括号作者名夹具',
    'desc' => '',
    'database_file' => 'no81.cdb',
], [
    ['id' => 7, 'card_prefix' => '335', 'card_id_length' => 8, 'author_name' => '决斗者＿啥呀(OPPO)'],
], [], []);
assertSameAuthorValue('决斗者＿啥呀(OPPO)', $resolution['author'], '人工作者名称完整保留括号内容');

$textRules = [
    [
        'id' => 12,
        'database_file' => 'no42.cdb',
        'match_field' => 'desc',
        'match_operator' => 'line_equals',
        'match_value' => 'Copyright 本体',
        'author_name' => '本体作者',
        'priority' => 200,
        'is_enabled' => 1,
    ],
    [
        'id' => 13,
        'database_file' => '',
        'match_field' => 'str10',
        'match_operator' => 'contains',
        'match_value' => 'CardDesign by 其空葵',
        'author_name' => '其空葵',
        'priority' => 100,
        'is_enabled' => 1,
    ],
];

$resolution = $resolver->resolve([
    'id' => 4058,
    'name' => '纳迦的存在',
    'desc' => "效果文本\r\nDoItYourself 极の一击\r——Copyright 本体\r\nN：sm30848171",
    'database_file' => 'no42.cdb',
], [
    ['id' => 5, 'card_prefix' => '000', 'card_id_length' => 8, 'author_name' => '错误区间作者'],
], $textRules, []);
assertSameAuthorValue('本体作者', $resolution['author'], '限定来源与字段的文本规则优先于区间和CDB署名');
assertSameAuthorValue('manual_text', $resolution['source'], '解析结果记录文本规则来源');
assertSameAuthorValue(12, $resolution['rule_id'], '解析结果记录命中的文本规则ID');

$resolution = $resolver->resolve([
    'id' => 50000000,
    'name' => '优先级夹具',
    'desc' => 'Marker Specific',
    'database_file' => 'no81.cdb',
], [], [
    [
        'id' => 20,
        'database_file' => '',
        'match_field' => 'desc',
        'match_operator' => 'contains',
        'match_value' => 'Marker',
        'author_name' => '低优先级作者',
        'priority' => 100,
        'is_enabled' => 1
    ],
    [
        'id' => 21,
        'database_file' => '',
        'match_field' => 'desc',
        'match_operator' => 'contains',
        'match_value' => 'Specific',
        'author_name' => '高优先级作者',
        'priority' => 200,
        'is_enabled' => 1
    ],
    [
        'id' => 22,
        'database_file' => '',
        'match_field' => 'desc',
        'match_operator' => 'contains',
        'match_value' => 'Marker Specific',
        'author_name' => '已停用作者',
        'priority' => 999,
        'is_enabled' => 0
    ]
], []);
assertSameAuthorValue('高优先级作者', $resolution['author'], '文本规则按优先级决定且忽略停用规则');

$resolution = $resolver->resolve([
    'id' => 4058,
    'name' => '同号不同来源',
    'desc' => "DoItYourself - no81作者\nCopyright 本体",
    'database_file' => 'no81.cdb',
], [], $textRules, []);
assertSameAuthorValue('no81作者', $resolution['author'], '数据库限定阻止文本规则跨CDB误命中');

$resolution = $resolver->resolve([
    'id' => 29010005,
    'name' => '辅助文本署名夹具',
    'desc' => '',
    'str10' => 'CardDesign by 其空葵',
    'database_file' => 'no81.cdb',
], [], $textRules, []);
assertSameAuthorValue('其空葵', $resolution['author'], '文本规则可以匹配str1至str16辅助文本字段');

$resolution = $resolver->resolve([
    'id' => 114514,
    'name' => '辅助文本CDB署名夹具',
    'desc' => '',
    'str10' => 'DoItYourself by 其空あおい',
    'database_file' => 'no81.cdb',
], [], [], []);
assertSameAuthorValue('其空あおい', $resolution['author'], 'CDB署名兜底也读取str1至str16辅助文本字段');
assertSameAuthorValue('str10', $resolution['matched_on'], '解析结果记录辅助文本署名字段');

$resolution = $resolver->resolve([
    'id' => 111100,
    'name' => '拼写变体夹具',
    'desc' => "效果\r\n--DoltYourself by 某失智刀客塔\r\nScript By 其他贡献者",
    'database_file' => 'no81.cdb',
], [], [], []);
assertSameAuthorValue('某失智刀客塔', $resolution['author'], 'CDB署名兜底识别常见DoItYourself拼写变体');
assertSameAuthorValue('signature', $resolution['source'], '解析结果记录CDB署名来源');

$resolution = $resolver->resolve([
    'id' => 82800003,
    'name' => '博丽神社 玄爷',
    'desc' => "效果文本\r\n\r\n--「DoItYourself」by 黑塔",
    'database_file' => 'no42.cdb',
], [], [], []);
assertSameAuthorValue('黑塔', $resolution['author'], 'CDB署名识别被书名号括起的DoItYourself标记');

$resolution = $resolver->resolve([
    'id' => 13250001,
    'name' => '魂锁制造业',
    'desc' => "效果文本\r\n\r\nTama--DoItYourself",
    'database_file' => 'no81.cdb',
], [], [], []);
assertSameAuthorValue('Tama', $resolution['author'], 'CDB署名识别作者位于DoItYourself标记前的账号格式');

$resolution = $resolver->resolve([
    'id' => 14000021,
    'name' => '杀戮行者·奈陌',
    'desc' => "效果文本\r\n-CardDesign by Justfish 「Besessenheit」\r\n-illust from ph.",
    'database_file' => 'no81.cdb',
], [], [], []);
assertSameAuthorValue('Justfish', $resolution['author'], 'CDB署名识别独立行CardDesign并排除作品名');

$resolution = $resolver->resolve([
    'id' => 90000001,
    'name' => '正文英文短语负例',
    'desc' => '这个效果的示例写作 CardDesign by 假作者，但这不是独立署名行。',
    'database_file' => 'no81.cdb',
], [], [], []);
assertSameAuthorValue('未知作者', $resolution['author'], '正文中的CardDesign by短语不会被误判为署名');

$resolution = $resolver->resolve([
    'id' => 90000002,
    'name' => '正文反向标记负例',
    'desc' => '发动这个效果--DoItYourself',
    'database_file' => 'no81.cdb',
], [], [], []);
assertSameAuthorValue('未知作者', $resolution['author'], '中文效果正文加反向DoItYourself标记不会被误判为作者');

$resolution = $resolver->resolve([
    'id' => 77700000,
    'name' => '别名归一夹具',
    'desc' => 'DoItYourself - 少年S',
    'database_file' => 'no81.cdb',
], [
    [
        'id' => 6,
        'card_prefix' => '714',
        'card_id_length' => 8,
        'author_name' => '少年',
        'alias' => '少年S,少年-S'
    ],
], [], []);
assertSameAuthorValue('少年', $resolution['author'], 'CDB署名别名归一到管理员维护的权威作者名');

$seriesRules = [[
    'id' => 30,
    'database_file' => 'no42.cdb',
    'match_field' => 'desc',
    'match_operator' => 'line_equals',
    'match_value' => 'Copyright 本体',
    'target_type' => 'series',
    'target_value' => '本体系列',
    'author_name' => '',
    'priority' => 300,
    'is_enabled' => 1
]];
$seriesCard = [
    'id' => 4058,
    'name' => '系列目标夹具',
    'desc' => "效果文本\nDoItYourself by 独立作者\nCopyright 本体",
    'database_file' => 'no42.cdb'
];
$resolution = $resolver->resolve($seriesCard, [], $seriesRules, []);
assertSameAuthorValue('独立作者', $resolution['author'], '系列文本规则不会污染作者解析或作者排行榜');
$seriesResolution = $resolver->resolveSeries($seriesCard, $seriesRules);
assertSameAuthorValue('本体系列', $seriesResolution['series_name'], '系列文本规则产生独立的人工系列归属');
assertSameAuthorValue(30, $seriesResolution['rule_id'], '人工系列归属保留可解释的规则ID');

$stringsParserReflection = new ReflectionClass('CardParser');
$stringsParser = $stringsParserReflection->newInstanceWithoutConstructor();
$loadAuthors = $stringsParserReflection->getMethod('loadAuthors');
$loadAuthors->setAccessible(true);
$loadAuthors->invoke($stringsParser);
$parsedAuthors = $stringsParser->getAuthorsFromStringsConf();
assertSameAuthorValue('沃亚森斯奥克斯', $parsedAuthors['300']['name'], 'strings解析忽略作者码前的QQ号');
assertSameAuthorValue('Akashic', $parsedAuthors['25']['name'], 'strings解析忽略作者码后的QQ号');
assertSameAuthorValue('零儿/02', $parsedAuthors['648']['name'], 'strings解析多作者码声明的第一段');
assertSameAuthorValue('零儿/02', $parsedAuthors['650']['name'], 'strings解析多作者码声明的第二段');
assertSameAuthorValue(false, isset($parsedAuthors['112']), 'strings中的冲突作者码进入人工复核而不静默覆盖');
assertSameAuthorValue(false, isset($parsedAuthors['257569726']), 'strings不把QQ号导入为作者码');

$databaseFiles = array_map('basename', $stringsParser->getCardDatabaseFiles());
assertSameAuthorValue(['no81.cdb', 'no42.cdb'], $databaseFiles, '重复卡号来源按显式CDB优先级稳定排序');

if ($failures > 0) {
    echo "\n{$failures} test(s) failed.\n";
    exit(1);
}

echo "\nAll author resolution tests passed.\n";
