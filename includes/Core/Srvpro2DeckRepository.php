<?php
/**
 * srvpro2 实战卡组数据仓库
 *
 * 按房间生命周期与座位去重，复刻旧 srvpro 每名玩家每场 Match 只写一份
 * YDK 的统计口径。
 */
class Srvpro2DeckRepository {
    /**
     * srvpro2 数据库
     * @var Srvpro2Database
     */
    private $database;

    /**
     * 卡组解码器
     * @var Srvpro2DeckCodec
     */
    private $codec;

    /**
     * 最近一次迭代的统计
     * @var array
     */
    private $lastStats;

    /**
     * 从 srvpro2 botlist 读取的 Windbot 名称
     * @var array
     */
    private $windbotNames;

    /**
     * 构造函数
     *
     * @param Srvpro2Database|null $database 数据库实例
     * @param Srvpro2DeckCodec|null $codec 卡组解码器
     */
    public function __construct($database = null, $codec = null) {
        $this->database = $database !== null ? $database : Srvpro2Database::getInstance();
        $maxCards = defined('SRVPRO2_MAX_DECK_CARDS') ? SRVPRO2_MAX_DECK_CARDS : 200;
        $this->codec = $codec !== null ? $codec : new Srvpro2DeckCodec($maxCards);
        $this->windbotNames = $this->loadWindbotNames();
        $this->lastStats = [
            'total_rows' => 0,
            'successful_rows' => 0,
            'skipped_rows' => 0,
            'windbot_filter_enabled' => !empty($this->windbotNames)
        ];
    }

    /**
     * 流式获取指定日期范围内的去重卡组
     *
     * 时间过滤在每场比赛取首份卡组之后执行，因此跨越范围边界的 Match
     * 与旧 srvpro 首次 DUEL_START 写文件的行为一致。
     *
     * @param string|null $startDate 开始日期（YYYY-MM-DD）
     * @param string|null $endDate 结束日期（YYYY-MM-DD，包含当天）
     * @return Generator 每项包含 main 与 side
     */
    public function getDeckIterator($startDate = null, $endDate = null) {
        $recordTable = $this->database->getTableName('duel_record');
        $playerTable = $this->database->getTableName('duel_record_player');
        $params = [];
        $dateConditions = [];

        if ($startDate !== null) {
            $this->assertDate($startDate);
            $dateConditions[] = 'record."startTime" >= :start_time';
            $params['start_time'] = $startDate . ' 00:00:00';
        }
        if ($endDate !== null) {
            $this->assertDate($endDate);
            $dateConditions[] = 'record."startTime" < :end_time';
            $params['end_time'] = date('Y-m-d 00:00:00', strtotime($endDate . ' +1 day'));
        }

        $sqlParts = [
            'SELECT',
            '    record.id AS replay_id,',
            '    player.id AS player_record_id,',
            '    player."startDeckBuffer" AS deck_buffer,',
            '    player."startDeckMainc" AS main_count',
            'FROM ' . $recordTable . ' record',
            'INNER JOIN ' . $playerTable . ' player',
            '    ON player."duelRecordId" = record.id',
            '   AND player."deleteTime" IS NULL',
            'WHERE record."deleteTime" IS NULL',
            '  AND record."winReason" IS NOT NULL',
            '  AND player."startDeckBuffer" <> \'\'',
            '  AND player.id > :after_player_id'
        ];

        if (!empty($this->windbotNames)) {
            $windbotPlaceholders = [];
            foreach ($this->windbotNames as $index => $windbotName) {
                $parameter = 'windbot_name_' . $index;
                $windbotPlaceholders[] = ':' . $parameter;
                $params[$parameter] = $windbotName;
            }
            $sqlParts = array_merge($sqlParts, [
                '  AND NOT EXISTS (',
                '      SELECT 1',
                '      FROM ' . $playerTable . ' windbot_player',
                '      WHERE windbot_player."duelRecordId" = record.id',
                '        AND windbot_player."deleteTime" IS NULL',
                '        AND windbot_player.name IN (' . implode(', ', $windbotPlaceholders) . ')',
                '  )'
            ]);
        }

        $sqlParts = array_merge($sqlParts, [
            '  AND NOT EXISTS (',
            '      SELECT 1',
            '      FROM ' . $recordTable . ' earlier_record',
            '      INNER JOIN ' . $playerTable . ' earlier_player',
            '          ON earlier_player."duelRecordId" = earlier_record.id',
            '         AND earlier_player."deleteTime" IS NULL',
            '         AND earlier_player.pos = player.pos',
            '         AND earlier_player."startDeckBuffer" <> \'\'',
            '      WHERE earlier_record."deleteTime" IS NULL',
            '        AND earlier_record."winReason" IS NOT NULL',
            '        AND earlier_record."roomIdentifier" = record."roomIdentifier"',
            '        AND (',
            '            earlier_record."duelCount" < record."duelCount"',
            '            OR (',
            '                earlier_record."duelCount" = record."duelCount"',
            '                AND earlier_record.id < record.id',
            '            )',
            '        )',
            '  )'
        ]);
        $sql = implode("\n", $sqlParts);

        if (!empty($dateConditions)) {
            $sql .= "\n  AND " . implode("\n  AND ", $dateConditions);
        }
        $sql .= "\nORDER BY player.id ASC\nLIMIT :batch_size";

        $this->lastStats = [
            'total_rows' => 0,
            'successful_rows' => 0,
            'skipped_rows' => 0,
            'windbot_filter_enabled' => !empty($this->windbotNames)
        ];

        $batchSize = defined('SRVPRO2_DECK_BATCH_SIZE')
            ? min(5000, max(10, (int)SRVPRO2_DECK_BATCH_SIZE))
            : 500;
        $afterPlayerId = '0';

        do {
            $batchRows = 0;
            $batchParams = array_merge($params, [
                'after_player_id' => $afterPlayerId,
                'batch_size' => $batchSize
            ]);
            $statement = $this->database->query($sql, $batchParams);

            while (($row = $statement->fetch(PDO::FETCH_ASSOC)) !== false) {
                $batchRows++;
                $afterPlayerId = (string)$row['player_record_id'];
                $this->lastStats['total_rows']++;
                try {
                    $deck = $this->codec->decodeRankingDeck(
                        isset($row['deck_buffer']) ? $row['deck_buffer'] : '',
                        isset($row['main_count']) ? $row['main_count'] : 0
                    );
                    $this->lastStats['successful_rows']++;
                    yield $deck;
                } catch (UnexpectedValueException $e) {
                    $this->lastStats['skipped_rows']++;
                    Utils::debug('跳过无效的 srvpro2 卡组快照', [
                        'replay_id' => isset($row['replay_id']) ? $row['replay_id'] : null,
                        'player_record_id' => isset($row['player_record_id']) ? $row['player_record_id'] : null,
                        '错误' => $e->getMessage()
                    ]);
                }
            }
            $statement->closeCursor();
            unset($statement);
        } while ($batchRows === $batchSize);
    }

    /**
     * 获取最近一次完整或进行中迭代的统计
     *
     * @return array total_rows、successful_rows、skipped_rows
     */
    public function getLastStats() {
        return $this->lastStats;
    }

    /**
     * 读取与校验 Windbot 名称配置
     *
     * srvpro2 数据表没有持久化 client.windbot 标记，只能用其 bots.json 中的
     * 精确名称排除整场包含机器人的对局。
     *
     * @return array 去重后的 Windbot 名称
     */
    private function loadWindbotNames() {
        $names = [];
        $configuredJson = defined('SRVPRO2_WINDBOT_NAMES')
            ? trim((string)SRVPRO2_WINDBOT_NAMES)
            : '[]';
        if ($configuredJson !== '' && $configuredJson !== '[]') {
            $decodedNames = json_decode($configuredJson, true);
            if (!is_array($decodedNames)) {
                throw new RuntimeException('SRVPRO2_WINDBOT_NAMES 必须是 JSON 数组');
            }
            $names = array_merge($names, $decodedNames);
        }

        $botlistPath = defined('SRVPRO2_WINDBOT_BOTLIST_PATH')
            ? trim((string)SRVPRO2_WINDBOT_BOTLIST_PATH)
            : '';
        if ($botlistPath !== '') {
            if (!is_file($botlistPath) || !is_readable($botlistPath)) {
                throw new RuntimeException('无法读取 SRVPRO2_WINDBOT_BOTLIST_PATH');
            }
            $size = filesize($botlistPath);
            if ($size === false || $size > 5242880) {
                throw new RuntimeException('srvpro2 Windbot botlist 文件过大或无法读取');
            }
            $botlist = json_decode((string)file_get_contents($botlistPath), true);
            if (!is_array($botlist) || !isset($botlist['windbots']) || !is_array($botlist['windbots'])) {
                throw new RuntimeException('srvpro2 Windbot botlist 格式无效');
            }
            foreach ($botlist['windbots'] as $bot) {
                if (is_array($bot) && isset($bot['name'])) {
                    $names[] = $bot['name'];
                }
            }
        }

        $normalized = [];
        foreach ($names as $name) {
            if (!is_scalar($name)) {
                throw new RuntimeException('Windbot 名称配置包含无效值');
            }
            $name = trim((string)$name);
            if ($name === '' || strlen($name) > 80) {
                throw new RuntimeException('Windbot 名称配置为空或过长');
            }
            $normalized[$name] = true;
        }

        return array_keys($normalized);
    }

    /**
     * 校验日期字符串
     *
     * @param string $date 日期
     * @return void
     */
    private function assertDate($date) {
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', (string)$date, $matches)) {
            throw new InvalidArgumentException('排行榜日期格式无效');
        }

        if (!checkdate((int)$matches[2], (int)$matches[3], (int)$matches[1])) {
            throw new InvalidArgumentException('排行榜日期无效');
        }
    }
}
