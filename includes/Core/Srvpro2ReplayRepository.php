<?php
/**
 * srvpro2 录像数据仓库
 *
 * PostgreSQL 负责可分页的元数据查询，srvpro2 HTTP API 负责按需动态生成 YRP。
 */
class Srvpro2ReplayRepository {
    /**
     * srvpro2 数据库
     * @var Srvpro2Database
     */
    private $database;

    /**
     * srvpro2 API 客户端
     * @var Srvpro2ApiClient|null
     */
    private $apiClient;

    /**
     * 录像格式化器
     * @var Srvpro2ReplayFormatter
     */
    private $formatter;

    /**
     * 录像状态缓存目录
     * @var string
     */
    private $cacheDir;

    /**
     * 构造函数
     *
     * @param Srvpro2Database|null $database 数据库实例
     * @param Srvpro2ApiClient|null $apiClient API 客户端
     * @param Srvpro2ReplayFormatter|null $formatter 格式化器
     * @param string|null $cacheDir 缓存目录
     */
    public function __construct($database = null, $apiClient = null, $formatter = null, $cacheDir = null) {
        $this->database = $database !== null ? $database : Srvpro2Database::getInstance();
        $this->apiClient = $apiClient;
        $this->formatter = $formatter !== null ? $formatter : new Srvpro2ReplayFormatter();
        $this->cacheDir = $cacheDir !== null
            ? (string)$cacheDir
            : __DIR__ . '/../../data/cache';
    }

    /**
     * 获取分页录像列表
     *
     * @param int $page 页码
     * @param int $perPage 每页数量
     * @param string|null $cursor 上一页最后一条可见录像 ID
     * @return array 录像列表与分页信息
     */
    public function getReplayList($page = 1, $perPage = 20, $cursor = null) {
        $maxPage = defined('SRVPRO2_REPLAY_MAX_PAGE')
            ? min(100000, max(1, (int)SRVPRO2_REPLAY_MAX_PAGE))
            : 1000;
        $page = min($maxPage, max(1, (int)$page));
        $perPage = min(100, max(1, (int)$perPage));
        $cursorId = $this->normalizeReplayId($cursor);
        if ($cursor !== null && $cursor !== '' && $cursorId === null) {
            throw new InvalidArgumentException('srvpro2 录像分页游标无效');
        }
        $usingCursor = $cursorId !== null;
        $recordTable = $this->database->getTableName('duel_record');
        $batchSize = defined('SRVPRO2_REPLAY_SCAN_BATCH_SIZE')
            ? min(500, max(10, (int)SRVPRO2_REPLAY_SCAN_BATCH_SIZE))
            : 50;
        $maxScanBatches = defined('SRVPRO2_REPLAY_MAX_SCAN_BATCHES')
            ? min(1000, max(1, (int)SRVPRO2_REPLAY_MAX_SCAN_BATCHES))
            : 20;
        $targetStart = $usingCursor ? 0 : ($page - 1) * $perPage;
        $targetCount = $targetStart + $perPage + 1;
        $visibleRows = [];
        $beforeId = $cursorId;
        $exhausted = false;
        $scanBatches = 0;
        $remainingVisibilityLookups = defined('SRVPRO2_REPLAY_VISIBILITY_MAX_LOOKUPS')
            ? min(50, max(1, (int)SRVPRO2_REPLAY_VISIBILITY_MAX_LOOKUPS))
            : 10;

        while (count($visibleRows) < $targetCount) {
            if ($scanBatches >= $maxScanBatches) {
                throw new RuntimeException('srvpro2 录像分页超过单次请求扫描预算，请使用下一页游标');
            }
            $scanBatches++;
            $sqlParts = [
                'SELECT',
                '    record.id,',
                '    record."roomIdentifier" AS room_identifier,',
                '    record."endTime" AS end_time,',
                '    record.name AS room_name,',
                '    record."duelCount" AS duel_count,',
                '    record."hostInfo"::text AS host_info',
                'FROM ' . $recordTable . ' record',
                'WHERE record."deleteTime" IS NULL',
                '  AND record."winReason" IS NOT NULL'
            ];
            $params = ['limit' => $batchSize];
            if ($beforeId !== null) {
                $sqlParts[] = '  AND record.id < :before_id';
                $params['before_id'] = $beforeId;
            }
            $sqlParts = array_merge($sqlParts, [
                'ORDER BY record.id DESC',
                'LIMIT :limit'
            ]);
            $candidateRows = $this->database->getRows(implode("\n", $sqlParts), $params);
            if (empty($candidateRows)) {
                $exhausted = true;
                break;
            }

            $lastRow = $candidateRows[count($candidateRows) - 1];
            $beforeId = (string)$lastRow['id'];
            $visibleRows = array_merge(
                $visibleRows,
                $this->filterVisibleReplayRows($candidateRows, $remainingVisibilityLookups)
            );
            if (count($candidateRows) < $batchSize) {
                $exhausted = true;
                break;
            }
        }

        if (
            !$usingCursor &&
            $exhausted &&
            $targetStart >= count($visibleRows) &&
            !empty($visibleRows)
        ) {
            $page = (int)ceil(count($visibleRows) / $perPage);
            $targetStart = ($page - 1) * $perPage;
        }
        if ($exhausted && empty($visibleRows)) {
            $page = 1;
            $targetStart = 0;
        }
        $hasNext = count($visibleRows) > $targetStart + $perPage;
        $rows = array_slice($visibleRows, $targetStart, $perPage);
        $nextCursor = null;
        if ($hasNext && !empty($rows)) {
            $lastVisibleRow = $rows[count($rows) - 1];
            $nextCursor = (string)$lastVisibleRow['id'];
        }

        $playersByReplay = $this->getPlayersByReplayIds(array_column($rows, 'id'));
        $replays = [];
        foreach ($rows as $row) {
            $id = (string)$row['id'];
            $players = isset($playersByReplay[$id]) ? $playersByReplay[$id] : [];
            $replays[] = $this->formatter->format($row, $players);
        }

        $total = !$usingCursor && $exhausted ? count($visibleRows) : null;
        $totalPages = !$usingCursor && $exhausted
            ? ($total > 0 ? (int)ceil($total / $perPage) : 0)
            : null;
        return [
            'replays' => $replays,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => $totalPages,
            'has_next' => $hasNext,
            'next_cursor' => $nextCursor,
            'source' => 'srvpro2'
        ];
    }

    /**
     * 根据文件名获取录像元数据
     *
     * @param string $filename {id}.yrp
     * @return array|null 录像信息
     */
    public function getReplayInfo($filename) {
        $id = $this->getReplayIdFromFilename($filename);
        if ($id === null) {
            return null;
        }

        $recordTable = $this->database->getTableName('duel_record');
        $row = $this->database->getRow(
            implode("\n", [
                'SELECT',
                '    record.id,',
                '    record."roomIdentifier" AS room_identifier,',
                '    record."endTime" AS end_time,',
                '    record.name AS room_name,',
                '    record."duelCount" AS duel_count,',
                '    record."hostInfo"::text AS host_info',
                'FROM ' . $recordTable . ' record',
                'WHERE record.id = :replay_id',
                '  AND record."deleteTime" IS NULL',
                '  AND record."winReason" IS NOT NULL'
            ]),
            ['replay_id' => $id]
        );
        if ($row === null) {
            return null;
        }
        $visibleRows = $this->filterVisibleReplayRows([$row]);
        if (empty($visibleRows)) {
            return null;
        }

        $playersByReplay = $this->getPlayersByReplayIds([$row['id']]);
        $players = isset($playersByReplay[(string)$row['id']])
            ? $playersByReplay[(string)$row['id']]
            : [];
        return $this->formatter->format($row, $players);
    }

    /**
     * 获取动态录像内容
     *
     * @param string $filename {id}.yrp
     * @return string|null YRP 内容
     */
    public function getReplayContent($filename) {
        $replay = $this->getReplayInfo($filename);
        if ($replay === null) {
            return null;
        }

        try {
            if ($this->apiClient === null) {
                $this->apiClient = new Srvpro2ApiClient();
            }
            return $this->apiClient->downloadReplay($replay['filename']);
        } catch (OutOfBoundsException $e) {
            return null;
        }
    }

    /**
     * 获取本页录像的公开玩家信息
     *
     * @param array $replayIds 录像 ID
     * @return array 以录像 ID 分组的玩家
     */
    private function getPlayersByReplayIds($replayIds) {
        if (empty($replayIds)) {
            return [];
        }

        $placeholders = [];
        $params = [];
        foreach (array_values($replayIds) as $index => $replayId) {
            $name = 'replay_id_' . $index;
            $placeholders[] = ':' . $name;
            $params[$name] = (string)$replayId;
        }

        $playerTable = $this->database->getTableName('duel_record_player');
        $rows = $this->database->getRows(
            implode("\n", [
                'SELECT',
                '    player."duelRecordId" AS replay_id,',
                '    player.pos,',
                '    player.name,',
                '    player.score,',
                '    player.winner',
                'FROM ' . $playerTable . ' player',
                'WHERE player."deleteTime" IS NULL',
                '  AND player."duelRecordId" IN (' . implode(', ', $placeholders) . ')',
                'ORDER BY player."duelRecordId", player.pos'
            ]),
            $params
        );

        $grouped = [];
        foreach ($rows as $row) {
            $id = (string)$row['replay_id'];
            if (!isset($grouped[$id])) {
                $grouped[$id] = [];
            }
            $grouped[$id][] = $row;
        }

        return $grouped;
    }

    /**
     * 过滤仍属于 srvpro2 活动房间的录像
     *
     * 正常达到胜场/最大局数的房间可由持久化数据直接确认；其余情况使用
     * `/api/duellog` 的 cloud_replay_id 实时状态，避免把 siding 中双方卡组公开。
     * API 检查失败时采用保守策略隐藏该录像。
     *
     * @param array $rows 录像数据库行
     * @param int|null $remainingLookups 本次请求剩余实时 API 查询预算（引用）
     * @return array 可公开行
     */
    private function filterVisibleReplayRows($rows, &$remainingLookups = null) {
        if (empty($rows)) {
            return [];
        }
        if ($remainingLookups === null) {
            $remainingLookups = defined('SRVPRO2_REPLAY_VISIBILITY_MAX_LOOKUPS')
                ? min(50, max(1, (int)SRVPRO2_REPLAY_VISIBILITY_MAX_LOOKUPS))
                : 10;
        }

        $roomIdentifiers = [];
        foreach ($rows as $row) {
            if (isset($row['room_identifier'])) {
                $roomIdentifiers[] = (string)$row['room_identifier'];
            }
        }
        $completedRooms = $this->getDefinitelyCompletedRooms(array_values(array_unique($roomIdentifiers)));
        $visibility = [];
        $unresolvedByRoomName = [];

        foreach ($rows as $row) {
            $id = (string)$row['id'];
            $roomIdentifier = isset($row['room_identifier']) ? (string)$row['room_identifier'] : '';
            if (isset($completedRooms[$roomIdentifier])) {
                $visibility[$id] = true;
                continue;
            }

            $cached = $this->readVisibilityCache($id);
            if ($cached !== null) {
                $visibility[$id] = $cached;
                continue;
            }

            $roomName = isset($row['room_name']) ? (string)$row['room_name'] : '';
            if (!isset($unresolvedByRoomName[$roomName])) {
                $unresolvedByRoomName[$roomName] = [];
            }
            $unresolvedByRoomName[$roomName][] = $id;
        }

        foreach ($unresolvedByRoomName as $roomName => $ids) {
            if ($remainingLookups <= 0) {
                throw new RuntimeException('srvpro2 活动房间状态查询超过单次请求预算');
            }
            $remainingLookups--;
            try {
                if ($this->apiClient === null) {
                    $this->apiClient = new Srvpro2ApiClient();
                }
                $roomVisibility = $this->apiClient->getReplayVisibilityByRoomName($roomName);
            } catch (Throwable $e) {
                throw new RuntimeException('无法确认 srvpro2 活动房间录像状态', 0, $e);
            }
            foreach ($ids as $id) {
                if (array_key_exists($id, $roomVisibility)) {
                    $visibility[$id] = (bool)$roomVisibility[$id];
                    $this->writeVisibilityCache($id, $visibility[$id]);
                }
            }
        }

        $visibleRows = [];
        foreach ($rows as $row) {
            $id = (string)$row['id'];
            if (isset($visibility[$id]) && $visibility[$id]) {
                $visibleRows[] = $row;
            }
        }
        return $visibleRows;
    }

    /**
     * 从持久化比分判断已经正常结束的房间
     *
     * @param array $roomIdentifiers 房间生命周期 ID
     * @return array 以 roomIdentifier 为键的集合
     */
    private function getDefinitelyCompletedRooms($roomIdentifiers) {
        if (empty($roomIdentifiers)) {
            return [];
        }

        $placeholders = [];
        $params = [];
        foreach ($roomIdentifiers as $index => $roomIdentifier) {
            $name = 'room_identifier_' . $index;
            $placeholders[] = ':' . $name;
            $params[$name] = $roomIdentifier;
        }

        $recordTable = $this->database->getTableName('duel_record');
        $playerTable = $this->database->getTableName('duel_record_player');
        $winCount = '(((state.mode & 1) | ((state.mode & 252) >> 1)) + 1)';
        $rows = $this->database->getRows(
            implode("\n", [
                'WITH room_record_state AS (',
                '    SELECT',
                '        record.id,',
                '        record."roomIdentifier" AS room_identifier,',
                '        record."duelCount" AS duel_count,',
                '        COALESCE(NULLIF(record."hostInfo"->>\'mode\', \'\')::integer, 0) AS mode,',
                '        MAX(player.score) AS max_score',
                '    FROM ' . $recordTable . ' record',
                '    INNER JOIN ' . $playerTable . ' player',
                '        ON player."duelRecordId" = record.id',
                '       AND player."deleteTime" IS NULL',
                '    WHERE record."deleteTime" IS NULL',
                '      AND record."winReason" IS NOT NULL',
                '      AND record."roomIdentifier" IN (' . implode(', ', $placeholders) . ')',
                '    GROUP BY record.id',
                ')',
                'SELECT DISTINCT state.room_identifier',
                'FROM room_record_state state',
                'WHERE state.max_score >= ' . $winCount,
                '   OR state.duel_count >= (' . $winCount . ' * 2 - 1)'
            ]),
            $params
        );

        $completed = [];
        foreach ($rows as $row) {
            $completed[(string)$row['room_identifier']] = true;
        }
        return $completed;
    }

    /**
     * 读取实时可见性缓存
     *
     * @param string $id 录像 ID
     * @return bool|null 可见状态；未命中返回 null
     */
    private function readVisibilityCache($id) {
        $cacheSeconds = defined('SRVPRO2_REPLAY_VISIBILITY_CACHE_SECONDS')
            ? max(0, (int)SRVPRO2_REPLAY_VISIBILITY_CACHE_SECONDS)
            : 30;
        if ($cacheSeconds === 0) {
            return null;
        }

        $cacheFile = $this->getVisibilityCacheFile($id);
        if (!is_file($cacheFile)) {
            return null;
        }
        $data = json_decode((string)file_get_contents($cacheFile), true);
        if (
            !is_array($data) ||
            !array_key_exists('visible', $data) ||
            !is_bool($data['visible']) ||
            !isset($data['checked_at']) ||
            !is_int($data['checked_at']) ||
            $data['checked_at'] < 0 ||
            $data['checked_at'] > time() + 300
        ) {
            return null;
        }
        $visible = $data['visible'];
        if (!$visible && (time() - $data['checked_at']) >= $cacheSeconds) {
            return null;
        }
        return $visible;
    }

    /**
     * 写入实时可见性缓存
     *
     * 已确认房间不再活动后，roomIdentifier 不会复用，因此 true 状态可永久复用；
     * false 状态只在短 TTL 内复用。
     *
     * @param string $id 录像 ID
     * @param bool $visible 是否可公开
     * @return void
     */
    private function writeVisibilityCache($id, $visible) {
        $cacheSeconds = defined('SRVPRO2_REPLAY_VISIBILITY_CACHE_SECONDS')
            ? max(0, (int)SRVPRO2_REPLAY_VISIBILITY_CACHE_SECONDS)
            : 30;
        if ($cacheSeconds === 0) {
            return;
        }

        $cacheFile = $this->getVisibilityCacheFile($id);
        $directory = dirname($cacheFile);
        if (!is_dir($directory) && !@mkdir($directory, 0755, true)) {
            return;
        }
        if (!is_writable($directory)) {
            return;
        }
        $encoded = json_encode([
            'visible' => (bool)$visible,
            'checked_at' => time()
        ]);
        if ($encoded === false) {
            return;
        }
        $temporaryFile = tempnam($directory, 'visibility_');
        if ($temporaryFile === false) {
            return;
        }
        if (file_put_contents($temporaryFile, $encoded, LOCK_EX) === false) {
            @unlink($temporaryFile);
            return;
        }
        if (!@rename($temporaryFile, $cacheFile)) {
            file_put_contents($cacheFile, $encoded, LOCK_EX);
            @unlink($temporaryFile);
        }
    }

    /**
     * 获取隔离到当前 srvpro2 数据源的可见性缓存路径
     *
     * @param string $id 录像 ID
     * @return string 缓存路径
     */
    private function getVisibilityCacheFile($id) {
        $identity = implode('|', [
            defined('SRVPRO2_DB_HOST') ? (string)SRVPRO2_DB_HOST : '127.0.0.1',
            defined('SRVPRO2_DB_PORT') ? (string)SRVPRO2_DB_PORT : '5432',
            defined('SRVPRO2_DB_NAME') ? (string)SRVPRO2_DB_NAME : 'srvpro2',
            defined('SRVPRO2_DB_SCHEMA') ? (string)SRVPRO2_DB_SCHEMA : 'public'
        ]);
        $directory = $this->cacheDir . '/srvpro2_replay_visibility_' . substr(sha1($identity), 0, 12);
        return $directory . '/' . sha1((string)$id) . '.json';
    }

    /**
     * 从文件名提取录像 ID
     *
     * @param string $filename 文件名
     * @return string|null 录像 ID
     */
    private function getReplayIdFromFilename($filename) {
        $filename = basename((string)$filename);
        if (!preg_match('/^([1-9][0-9]{0,15})\.yrp$/', $filename, $matches)) {
            return null;
        }

        return $this->normalizeReplayId($matches[1]);
    }

    /**
     * 校验 srvpro2 JavaScript 安全整数 ID
     *
     * @param mixed $id 录像 ID
     * @return string|null 标准化 ID
     */
    private function normalizeReplayId($id) {
        if ($id === null || (!is_int($id) && !is_string($id))) {
            return null;
        }
        $id = (string)$id;
        if (!preg_match('/^[1-9][0-9]{0,15}$/', $id)) {
            return null;
        }
        if (strlen($id) === 16 && strcmp($id, '9007199254740991') > 0) {
            return null;
        }
        return $id;
    }
}
