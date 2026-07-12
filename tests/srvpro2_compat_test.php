<?php
/**
 * srvpro2 兼容层的轻量测试
 *
 * 运行方式：php tests/srvpro2_compat_test.php
 */

require_once __DIR__ . '/../includes/Core/Srvpro2DeckCodec.php';
require_once __DIR__ . '/../includes/Core/Srvpro2ReplayFormatter.php';
require_once __DIR__ . '/../includes/Core/Srvpro2ReplayRepository.php';
require_once __DIR__ . '/../includes/Core/Srvpro2DeckRepository.php';
require_once __DIR__ . '/../includes/Core/Srvpro2ApiClient.php';

if (!defined('SRVPRO2_REPLAY_VISIBILITY_CACHE_SECONDS')) {
    define('SRVPRO2_REPLAY_VISIBILITY_CACHE_SECONDS', 0);
}
if (!defined('SRVPRO2_REPLAY_SCAN_BATCH_SIZE')) {
    define('SRVPRO2_REPLAY_SCAN_BATCH_SIZE', 10);
}
if (!defined('SRVPRO2_REPLAY_VISIBILITY_MAX_LOOKUPS')) {
    define('SRVPRO2_REPLAY_VISIBILITY_MAX_LOOKUPS', 10);
}
if (!defined('SRVPRO2_WINDBOT_NAMES')) {
    define('SRVPRO2_WINDBOT_NAMES', '["TestBot"]');
}
if (!defined('SRVPRO2_DECK_BATCH_SIZE')) {
    define('SRVPRO2_DECK_BATCH_SIZE', 10);
}

if (!class_exists('Utils')) {
    /**
     * 测试用日志桩
     */
    class Utils {
        /**
         * 忽略调试日志
         *
         * @param string $message 日志消息
         * @param array $context 上下文
         * @return void
         */
        public static function debug($message, $context = []) {
            // 测试中忽略调试日志。
        }
    }
}

$failures = 0;

/**
 * 断言两个值严格相等
 *
 * @param mixed $expected 期望值
 * @param mixed $actual 实际值
 * @param string $message 失败消息
 * @return void
 */
function assertSameValue($expected, $actual, $message) {
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

/**
 * 断言回调会抛出指定异常
 *
 * @param string $exceptionClass 异常类名
 * @param callable $callback 测试回调
 * @param string $message 失败消息
 * @return void
 */
function assertThrows($exceptionClass, $callback, $message) {
    global $failures;

    try {
        call_user_func($callback);
    } catch (Exception $e) {
        if ($e instanceof $exceptionClass) {
            echo "[PASS] {$message}\n";
            return;
        }

        $failures++;
        echo "[FAIL] {$message}\n";
        echo '  unexpected exception: ' . get_class($e) . ': ' . $e->getMessage() . "\n";
        return;
    }

    $failures++;
    echo "[FAIL] {$message}\n";
    echo "  expected exception: {$exceptionClass}\n";
}

$codec = new Srvpro2DeckCodec(100);
$payload = pack('V*', 4, 2, 100, 101, 200, 201, 300, 301);
$deck = $codec->decodeBase64(base64_encode($payload), 2);

assertSameValue([100, 101], $deck['main'], '主卡组按 startDeckMainc 分割');
assertSameValue([200, 201], $deck['extra'], '额外卡组从主区域剩余卡片分割');
assertSameValue([300, 301], $deck['side'], '副卡组按 payload 的 sidec 分割');

$rankingDeck = $codec->decodeRankingDeck(base64_encode($payload), 2);
assertSameValue(
    [100, 101, 200, 201],
    $rankingDeck['main'],
    '排行榜保持旧逻辑，将主卡组和额外卡组合并统计'
);

assertThrows(
    'UnexpectedValueException',
    function() use ($codec) {
        $codec->decodeBase64('not-base64!', 0);
    },
    '拒绝无效 base64 卡组'
);

assertThrows(
    'UnexpectedValueException',
    function() use ($codec) {
        $codec->decodeBase64(base64_encode(pack('V*', 2, 0, 123)), 1);
    },
    '拒绝长度与卡片数量不一致的 payload'
);

assertThrows(
    'RuntimeException',
    function() {
        new Srvpro2ApiClient('file:///tmp/srvpro2', 'user', 'password');
    },
    '动态录像 API 仅允许 HTTP/HTTPS 地址'
);

assertThrows(
    'RuntimeException',
    function() {
        new Srvpro2ApiClient('https://127.0.0.1:7922', 'user', '');
    },
    '动态录像 API 拒绝缺失凭据'
);

assertThrows(
    'RuntimeException',
    function() {
        new Srvpro2ApiClient('http://192.0.2.10:7922', 'user', 'password');
    },
    '远程动态录像 API 强制使用 HTTPS'
);

$configuredApiClient = new Srvpro2ApiClient('https://127.0.0.1:7922', 'user', 'password');
assertThrows(
    'InvalidArgumentException',
    function() use ($configuredApiClient) {
        $configuredApiClient->downloadReplay('../secret.yrp');
    },
    '动态录像 API 拒绝非数字录像文件名'
);

assertThrows(
    'InvalidArgumentException',
    function() use ($configuredApiClient) {
        $configuredApiClient->downloadReplay('9007199254740992.yrp');
    },
    '动态录像 API 拒绝超出 JavaScript 安全整数范围的 ID'
);

$formatter = new Srvpro2ReplayFormatter();
$formatted = $formatter->format([
    'id' => '42',
    'end_time' => '2026-07-09 12:34:56',
    'room_name' => '测试房间',
    'duel_count' => 2,
    'host_info' => json_encode(['mode' => 2]),
], [
    ['pos' => 2, 'name' => 'C', 'score' => 1, 'winner' => false],
    ['pos' => 0, 'name' => 'A', 'score' => 2, 'winner' => true],
    ['pos' => 3, 'name' => 'D', 'score' => 1, 'winner' => false],
    ['pos' => 1, 'name' => 'B', 'score' => 2, 'winner' => true],
]);

assertSameValue('42.yrp', $formatted['filename'], '动态录像使用 srvpro2 录像 ID 作为文件名');
assertSameValue(['A', 'B', 'C', 'D'], $formatted['player_names'], '玩家按座位排序');
assertSameValue('A+B vs C+D', $formatted['versus_text'], '双打录像按队伍显示玩家');
assertSameValue('双打', $formatted['duel_rule'], '识别 srvpro2 双打模式');
assertSameValue(true, $formatted['is_yrp2'], 'srvpro2 动态生成的录像标记为 YRP2');
assertSameValue(null, $formatted['file_size'], '动态录像不伪造文件大小');

/**
 * 测试用录像数据库桩
 */
class FakeSrvpro2ReplayDatabase {
    /**
     * 收到的 SQL
     * @var array
     */
    private $sqlLog = [];

    /**
     * 录像候选查询游标
     * @var array
     */
    private $candidateCursors = [];

    /**
     * 获取收到的 SQL
     *
     * @return array SQL 列表
     */
    public function getSqlLog() {
        return $this->sqlLog;
    }

    /**
     * 获取录像候选查询游标
     *
     * @return array 游标列表
     */
    public function getCandidateCursors() {
        return $this->candidateCursors;
    }

    /**
     * 获取测试表名
     *
     * @param string $table 表名
     * @return string 引用后的表名
     */
    public function getTableName($table) {
        return '"public"."' . $table . '"';
    }

    /**
     * 返回录像总数
     *
     * @param string $sql SQL
     * @param array $params 参数
     * @return int 总数
     */
    public function getValue($sql, $params = []) {
        $this->sqlLog[] = $sql;
        return 2;
    }

    /**
     * 返回录像或玩家列表
     *
     * @param string $sql SQL
     * @param array $params 参数
     * @return array 结果
     */
    public function getRows($sql, $params = []) {
        $this->sqlLog[] = $sql;
        if (strpos($sql, 'WITH room_record_state AS') !== false) {
            return [
                ['room_identifier' => 'room-complete']
            ];
        }
        if (strpos($sql, 'player."duelRecordId" AS replay_id') !== false) {
            return [
                ['replay_id' => '42', 'pos' => 0, 'name' => 'A', 'score' => 1, 'winner' => true],
                ['replay_id' => '42', 'pos' => 1, 'name' => 'B', 'score' => 0, 'winner' => false],
                ['replay_id' => '41', 'pos' => 0, 'name' => 'C', 'score' => 1, 'winner' => true],
                ['replay_id' => '41', 'pos' => 1, 'name' => 'D', 'score' => 0, 'winner' => false],
            ];
        }

        $cursor = isset($params['before_id']) ? (string)$params['before_id'] : 'start';
        $this->candidateCursors[] = $cursor;
        if ($cursor === 'start') {
            $rows = [];
            for ($id = 100; $id >= 91; $id--) {
                $rows[] = [
                    'id' => (string)$id,
                    'room_identifier' => 'room-active-' . $id,
                    'end_time' => '2026-07-09 12:30:00',
                    'room_name' => '活动房间' . $id,
                    'duel_count' => 1,
                    'host_info' => '{"mode":1}'
                ];
            }
            return $rows;
        }
        if ($cursor === '91') {
            return [
                [
                    'id' => '42',
                    'room_identifier' => 'room-complete',
                    'end_time' => '2026-07-09 12:34:56',
                    'room_name' => '房间A',
                    'duel_count' => 1,
                    'host_info' => '{"mode":0}'
                ]
            ];
        }
        return [];
    }

    /**
     * 返回单条录像
     *
     * @param string $sql SQL
     * @param array $params 参数
     * @return array 录像
     */
    public function getRow($sql, $params = []) {
        $this->sqlLog[] = $sql;
        if (isset($params['replay_id']) && (string)$params['replay_id'] === '41') {
            return [
                'id' => '41',
                'room_identifier' => 'room-active',
                'end_time' => '2026-07-09 12:30:00',
                'room_name' => '房间B',
                'duel_count' => 1,
                'host_info' => '{"mode":1}'
            ];
        }
        return [
            'id' => '42',
            'room_identifier' => 'room-complete',
            'end_time' => '2026-07-09 12:34:56',
            'room_name' => '房间A',
            'duel_count' => 1,
            'host_info' => '{"mode":0}'
        ];
    }
}

/**
 * 测试用 srvpro2 API 桩
 */
class FakeSrvpro2ApiClient {
    /**
     * 已下载文件
     * @var array
     */
    private $downloaded = [];

    /**
     * 已检查状态的房间
     * @var array
     */
    private $visibilityRooms = [];

    /**
     * 获取已下载文件
     *
     * @return array 文件名列表
     */
    public function getDownloaded() {
        return $this->downloaded;
    }

    /**
     * 获取已检查状态的房间
     *
     * @return array 房间名
     */
    public function getVisibilityRooms() {
        return $this->visibilityRooms;
    }

    /**
     * 返回测试录像实时状态
     *
     * @param string $roomName 房间名
     * @return array 录像状态
     */
    public function getReplayVisibilityByRoomName($roomName) {
        $this->visibilityRooms[] = $roomName;
        if ($roomName === '房间B') {
            return ['41' => false];
        }
        if (preg_match('/^活动房间([0-9]+)$/', $roomName, $matches)) {
            return [$matches[1] => false];
        }
        return ['42' => true];
    }

    /**
     * 返回测试录像内容
     *
     * @param string $filename 文件名
     * @return string 内容
     */
    public function downloadReplay($filename) {
        $this->downloaded[] = $filename;
        return 'YRP:' . $filename;
    }
}

$fakeReplayApi = new FakeSrvpro2ApiClient();
$fakeReplayDatabase = new FakeSrvpro2ReplayDatabase();
$replayRepository = new Srvpro2ReplayRepository(
    $fakeReplayDatabase,
    $fakeReplayApi,
    new Srvpro2ReplayFormatter()
);
$replayList = $replayRepository->getReplayList(1, 20);
assertSameValue(1, $replayList['total'], 'srvpro2 录像仓库返回过滤后的可见总数');
assertSameValue(1, count($replayList['replays']), '录像列表隐藏 srvpro2 活动 Match 的已结束前局');
assertSameValue(['start', '91'], $fakeReplayDatabase->getCandidateCursors(), '当前批次全被隐藏时继续 keyset 扫描较旧录像');
assertSameValue('A vs B', $replayList['replays'][0]['versus_text'], 'srvpro2 录像仓库批量合并玩家');
assertSameValue(null, $replayRepository->getReplayInfo('41.yrp'), '录像详情拒绝活动 Match 前局');
assertSameValue('YRP:42.yrp', $replayRepository->getReplayContent('42.yrp'), '动态录像由服务端 API 代理');
assertSameValue(['42.yrp'], $fakeReplayApi->getDownloaded(), 'API 凭据与下载留在服务端仓库层');
assertSameValue(11, count($fakeReplayApi->getVisibilityRooms()), '列表使用 10 次状态预算后，详情请求获得独立预算');
assertSameValue('房间B', $fakeReplayApi->getVisibilityRooms()[10], '录像详情也由 srvpro2 /api/duellog 精确确认');
$replaySqlHasLiteralEscapes = false;
foreach ($fakeReplayDatabase->getSqlLog() as $sql) {
    if (strpos($sql, '\\n') !== false) {
        $replaySqlHasLiteralEscapes = true;
    }
}
assertSameValue(false, $replaySqlHasLiteralEscapes, '录像 SQL 使用真实换行而非反斜杠转义文本');
$replaySql = implode("\n", $fakeReplayDatabase->getSqlLog());
assertSameValue(false, strpos($replaySql, 'visible_rooms') !== false, '录像列表不做全历史活动房间聚合');
assertSameValue(true, strpos($replaySql, 'record."winReason" IS NOT NULL') !== false, '录像列表只公开已经完成的单局');

/**
 * 测试用状态预算数据库桩
 */
class BudgetSrvpro2ReplayDatabase extends FakeSrvpro2ReplayDatabase {
    /**
     * 返回连续两批不确定录像
     *
     * @param string $sql SQL
     * @param array $params 参数
     * @return array 结果
     */
    public function getRows($sql, $params = []) {
        if (strpos($sql, 'WITH room_record_state AS') !== false) {
            return [];
        }
        if (strpos($sql, 'player."duelRecordId" AS replay_id') !== false) {
            return [];
        }

        $beforeId = isset($params['before_id']) ? (string)$params['before_id'] : null;
        if ($beforeId === null) {
            $rows = [];
            for ($id = 100; $id >= 91; $id--) {
                $rows[] = [
                    'id' => (string)$id,
                    'room_identifier' => 'budget-room-' . $id,
                    'end_time' => '2026-07-09 12:30:00',
                    'room_name' => '活动房间' . $id,
                    'duel_count' => 1,
                    'host_info' => '{"mode":1}'
                ];
            }
            return $rows;
        }
        if ($beforeId === '91') {
            return [[
                'id' => '90',
                'room_identifier' => 'budget-room-90',
                'end_time' => '2026-07-09 12:20:00',
                'room_name' => '活动房间90',
                'duel_count' => 1,
                'host_info' => '{"mode":1}'
            ]];
        }
        return [];
    }
}

$budgetReplayRepository = new Srvpro2ReplayRepository(
    new BudgetSrvpro2ReplayDatabase(),
    new FakeSrvpro2ApiClient(),
    new Srvpro2ReplayFormatter()
);
assertThrows(
    'RuntimeException',
    function() use ($budgetReplayRepository) {
        $budgetReplayRepository->getReplayList(1, 20);
    },
    '活动状态查询预算跨 keyset 批次共享并在耗尽时 fail-closed'
);

/**
 * 测试用卡组查询结果桩
 */
class FakeSrvpro2DeckStatement {
    /**
     * 测试行
     * @var array
     */
    private $rows;

    /**
     * 当前下标
     * @var int
     */
    private $index = 0;

    /**
     * 构造函数
     *
     * @param array $rows 测试行
     */
    public function __construct($rows) {
        $this->rows = $rows;
    }

    /**
     * 获取下一行
     *
     * @param mixed $mode PDO 获取模式
     * @return array|false 下一行
     */
    public function fetch($mode = null) {
        if ($this->index >= count($this->rows)) {
            return false;
        }
        return $this->rows[$this->index++];
    }

    /**
     * 关闭测试游标
     *
     * @return bool 是否成功
     */
    public function closeCursor() {
        return true;
    }
}

/**
 * 测试用卡组数据库桩
 */
class FakeSrvpro2DeckDatabase {
    /**
     * 最近执行的 SQL
     * @var string
     */
    private $lastSql = '';

    /**
     * 最近执行的参数
     * @var array
     */
    private $lastParams = [];

    /**
     * 查询次数
     * @var int
     */
    private $queryCount = 0;

    /**
     * 获取最近执行的 SQL
     *
     * @return string SQL
     */
    public function getLastSql() {
        return $this->lastSql;
    }

    /**
     * 获取最近执行的参数
     *
     * @return array 参数
     */
    public function getLastParams() {
        return $this->lastParams;
    }

    /**
     * 获取查询次数
     *
     * @return int 查询次数
     */
    public function getQueryCount() {
        return $this->queryCount;
    }

    /**
     * 获取测试表名
     *
     * @param string $table 表名
     * @return string 引用后的表名
     */
    public function getTableName($table) {
        return '"public"."' . $table . '"';
    }

    /**
     * 返回测试卡组结果
     *
     * @param string $sql SQL
     * @param array $params 参数
     * @return FakeSrvpro2DeckStatement 查询结果
     */
    public function query($sql, $params = []) {
        $this->lastSql = $sql;
        $this->lastParams = $params;
        $this->queryCount++;
        $validPayload = base64_encode(pack('V*', 2, 1, 10, 11, 12));
        if ((string)$params['after_player_id'] === '0') {
            $rows = [];
            for ($id = 1; $id <= 10; $id++) {
                $rows[] = [
                    'replay_id' => (string)$id,
                    'player_record_id' => (string)$id,
                    'deck_buffer' => $validPayload,
                    'main_count' => 1
                ];
            }
            return new FakeSrvpro2DeckStatement($rows);
        }

        return new FakeSrvpro2DeckStatement([
            [
                'replay_id' => '11',
                'player_record_id' => '11',
                'deck_buffer' => 'invalid!',
                'main_count' => 0
            ]
        ]);
    }
}

$fakeDeckDatabase = new FakeSrvpro2DeckDatabase();
$deckRepository = new Srvpro2DeckRepository($fakeDeckDatabase, new Srvpro2DeckCodec(100));
$deckIterator = $deckRepository->getDeckIterator('2026-07-01', '2026-07-09');
$decks = iterator_to_array($deckIterator, false);
$deckStats = $deckRepository->getLastStats();
assertSameValue(10, count($decks), '排行榜只保留成功解码的 srvpro2 卡组');
assertSameValue(1, $deckStats['skipped_rows'], '排行榜记录异常卡组快照数量');
assertSameValue(10, $deckStats['successful_rows'], '排行榜记录成功解码的卡组数量');
assertSameValue(true, $deckStats['windbot_filter_enabled'], '排行榜记录 Windbot 名单过滤已启用');
assertSameValue([10, 11], $decks[0]['main'], '排行榜卡组包含主卡组与额外卡组');
assertSameValue(true, strpos($fakeDeckDatabase->getLastSql(), 'NOT EXISTS') !== false, '排行榜 SQL 按房间和座位取首份卡组');
assertSameValue(true, strpos($fakeDeckDatabase->getLastSql(), 'player.id > :after_player_id') !== false, '排行榜使用 keyset 分批读取 PostgreSQL');
assertSameValue(2, $fakeDeckDatabase->getQueryCount(), '排行榜按批次重复查询而非一次缓冲全量结果');
assertSameValue(true, strpos($fakeDeckDatabase->getLastSql(), 'windbot_player.name IN') !== false, '排行榜按 Windbot 名称排除整场机器人对局');
assertSameValue('TestBot', $fakeDeckDatabase->getLastParams()['windbot_name_0'], 'Windbot 名称通过查询参数绑定');
assertSameValue(true, strpos($fakeDeckDatabase->getLastSql(), 'player."startDeckBuffer"') !== false, '排行榜读取初始卡组快照');
assertSameValue(false, strpos($fakeDeckDatabase->getLastSql(), '\\n') !== false, '排行榜 SQL 使用真实换行而非反斜杠转义文本');

if (!defined('SRVPRO2_INTEGRATION_ENABLED')) {
    define('SRVPRO2_INTEGRATION_ENABLED', false);
}
if (!defined('REPLAY_PATH')) {
    define('REPLAY_PATH', __DIR__ . '/fixtures/nonexistent-replay-directory');
}
require_once __DIR__ . '/../includes/Models/Replay.php';

$legacyReplay = new Replay();
$legacyReplayList = $legacyReplay->getReplayList(1, 20);
assertSameValue('legacy_files', $legacyReplayList['source'], '关闭 srvpro2 开关后使用旧录像文件入口');

if ($failures > 0) {
    echo "\n{$failures} test(s) failed.\n";
    exit(1);
}

echo "\nAll srvpro2 compatibility tests passed.\n";
