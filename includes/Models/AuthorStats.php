<?php
/**
 * 作者统计模型
 *
 * 处理作者统计相关的数据操作
 */
class AuthorStats {
    /**
     * 卡片解析器
     * @var CardParser
     */
    private $cardParser;

    /**
     * 缓存目录
     * @var string
     */
    private $cacheDir;

    /**
     * 构造函数
     *
     * @param string|null $cacheDir 可选缓存目录，测试可注入隔离目录
     * @return void
     */
    public function __construct($cacheDir = null) {
        $this->cardParser = CardParser::getInstance();
        $this->cacheDir = $cacheDir !== null ? $cacheDir : __DIR__ . '/../../data/cache';

        // 确保缓存目录存在
        if (!file_exists($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    /**
     * 获取作者统计数据
     *
     * @param bool $forceUpdate 是否强制更新
     * @return array 作者统计数据
     */
    public function getAuthorStats($forceUpdate = false) {
        // 检查缓存
        $cacheFile = $this->getCacheFilePath();
        $sourceFingerprint = $this->getSourceFingerprint();

        // 如果缓存存在且未过期，且不强制更新，则直接返回缓存数据
        if (!$forceUpdate) {
            $cachedData = $this->readCachedAuthorStats($cacheFile, $sourceFingerprint);
            if ($cachedData !== null) {
                return $cachedData;
            }
        }

        // 获取所有卡片数据库文件
        $dbFiles = $this->cardParser->getCardDatabaseFiles();

        // 作者统计数据
        $authorStats = [];

        // 已处理的卡片ID集合，用于防止重复统计
        $processedCardIds = [];

        // 获取标准环境禁卡列表
        $standardBanlist = $this->getStandardBanlist();

        // 获取系列信息
        $setcodes = $this->getSetcodes();

        // 处理每个数据库文件
        foreach ($dbFiles as $dbFile) {
            $dbName = basename($dbFile);
            $isNo42 = (strpos($dbName, 'no42') !== false);

            // 分批读取并统计，避免desc + str1..str16全量驻留导致128MiB环境内存紧张。
            foreach ($this->iterateCardsFromDatabase($dbFile) as $card) {
                // 检查卡片ID是否已经处理过，防止重复统计
                $cardId = (int)$card['id'];
                if (isset($processedCardIds[$cardId])) {
                    continue;
                }
                $processedCardIds[$cardId] = true;

                // 根据配置决定使用哪种方式获取作者
                if (defined('AUTHOR_HALL_OF_FAME_SIMPLE_MODE') && AUTHOR_HALL_OF_FAME_SIMPLE_MODE) {
                    // 简略识别模式仍复用统一解析器，但只启用管理员文本规则和卡号区间。
                    $resolution = $this->cardParser->getCardAuthorResolution($card, true);
                    $author = $resolution['author'];
                } else {
                    // getAllCards已完成统一解析，避免再次逐卡执行判定。
                    $author = isset($card['author']) ? $card['author'] : $this->cardParser->getCardAuthor($card);
                }

                // 确保作者名使用UTF-8编码
                if (!mb_check_encoding($author, 'UTF-8')) {
                    // 尝试转换编码
                    $author = mb_convert_encoding($author, 'UTF-8', 'auto');
                }

                // 过滤掉可能导致问题的控制字符
                $author = preg_replace('/[\x00-\x1F\x7F]/u', '', $author);

                // 解析结果被清理为空时统一归入未知作者，禁止重新引入固定前三位推断。
                if (empty(trim($author))) {
                    $author = "未知作者";
                }

                // 初始化作者统计数据
                if (!isset($authorStats[$author])) {
                    $authorStats[$author] = [
                        'name' => $author,
                        'total_cards' => 0,
                        'banned_cards' => 0,
                        'banned_series' => 0,
                        'banned_percentage' => 0,
                        'is_unknown' => ($author === "未知作者"),
                        'cards' => [],
                        'banned_cards_list' => [],
                        'banned_series_list' => []
                    ];
                }

                // 增加卡片总数
                $authorStats[$author]['total_cards']++;

                // 记录卡片信息
                $authorStats[$author]['cards'][] = [
                    'id' => $card['id'],
                    'name' => $card['name'],
                    'setcode' => $card['setcode'],
                    'is_no42' => $isNo42
                ];

                // 检查是否在标准环境被禁止
                if (isset($standardBanlist[$cardId]) && $standardBanlist[$cardId]['status'] === 0 && !$isNo42) {
                    $authorStats[$author]['banned_cards']++;
                    $authorStats[$author]['banned_cards_list'][] = [
                        'id' => $card['id'],
                        'name' => $card['name']
                    ];
                }
            }
        }

        // 计算禁卡比例和被禁系列
        foreach ($authorStats as $author => &$stats) {
            // 计算禁卡比例
            if ($stats['total_cards'] > 0) {
                $stats['banned_percentage'] = round(($stats['banned_cards'] / $stats['total_cards']) * 100, 2);
            }

            // 计算被禁系列
            $stats['banned_series'] = $this->countBannedSeries($stats['cards'], $standardBanlist, $setcodes);
        }
        unset($stats);

        // 将作者分为已知作者和未知作者两组
        $knownAuthors = [];
        $unknownAuthors = [];

        foreach ($authorStats as $author => $stats) {
            if ($stats['is_unknown']) {
                $unknownAuthors[$author] = $stats;
            } else {
                $knownAuthors[$author] = $stats;
            }
        }

        // 按投稿卡片数量从高到低排序已知作者，相同卡片数量的按禁卡比例从高到低排序
        uasort($knownAuthors, function($a, $b) {
            if ($a['total_cards'] != $b['total_cards']) {
                return $b['total_cards'] <=> $a['total_cards'];
            }
            return $b['banned_percentage'] <=> $a['banned_percentage'];
        });

        // 按投稿卡片数量从高到低排序未知作者，相同卡片数量的按禁卡比例从高到低排序
        uasort($unknownAuthors, function($a, $b) {
            if ($a['total_cards'] != $b['total_cards']) {
                return $b['total_cards'] <=> $a['total_cards'];
            }
            return $b['banned_percentage'] <=> $a['banned_percentage'];
        });

        // 合并已知作者和未知作者
        $sortedAuthorStats = $knownAuthors + $unknownAuthors;

        // 添加排名
        $rank = 1;
        foreach ($sortedAuthorStats as &$stats) {
            $stats['rank'] = $rank++;
        }
        unset($stats);

        // 添加生成时间
        $sortedAuthorStats['generated_time'] = date('Y-m-d H:i:s');

        // 缓存数据
        $this->cacheAuthorStats($sortedAuthorStats, $sourceFingerprint);

        return $sortedAuthorStats;
    }

    /**
     * 获取标准环境禁卡列表
     *
     * @return array 禁卡列表
     */
    private function getStandardBanlist() {
        $environments = Utils::getEnvironments();
        $standardEnvironment = null;

        // 查找标准环境
        foreach ($environments as $env) {
            if ($env['text'] === '标准环境') {
                $standardEnvironment = $env;
                break;
            }
        }

        if (!$standardEnvironment) {
            return [];
        }

        // 获取禁卡列表
        $banlist = $this->cardParser->getLflist()[$standardEnvironment['header']] ?? [];

        // 排除 TCG 禁卡
        $tcgBanlist = $this->getTCGBanlist();

        // 从标准环境禁卡列表中移除 TCG 禁卡
        foreach ($tcgBanlist as $cardId => $cardInfo) {
            if (isset($banlist[$cardId])) {
                unset($banlist[$cardId]);
            }
        }

        return $banlist;
    }

    /**
     * 获取 TCG 禁卡列表
     *
     * @return array TCG 禁卡列表
     */
    private function getTCGBanlist() {
        $cardDataPath = CARD_DATA_PATH;
        $lflistFile = $cardDataPath . '/lflist.conf';
        $tcgBanlist = [];

        if (file_exists($lflistFile)) {
            $content = file_get_contents($lflistFile);
            $lines = explode("\n", $content);

            $inTCGSection = false;

            foreach ($lines as $line) {
                $line = trim($line);

                // 检查是否进入 TCG 禁卡部分
                if (strpos($line, '#Forbidden TCG') === 0) {
                    $inTCGSection = true;
                    continue;
                }

                // 检查是否离开 TCG 禁卡部分（遇到新的环境标题或其他主要部分）
                if ($inTCGSection && (strpos($line, '!') === 0 || strpos($line, '#[') === 0)) {
                    $inTCGSection = false;
                    continue;
                }

                // 如果在 TCG 禁卡部分，且不是注释行或空行，则解析卡片信息
                if ($inTCGSection && !empty($line) && strpos($line, '#') !== 0) {
                    $parts = preg_split('/\s+/', $line, 3);
                    if (count($parts) >= 2) {
                        $cardId = (int)trim($parts[0]);
                        $status = (int)trim($parts[1]);
                        $comment = isset($parts[2]) ? trim($parts[2]) : '';

                        $tcgBanlist[$cardId] = [
                            'status' => $status,
                            'comment' => $comment
                        ];
                    }
                }
            }
        }

        return $tcgBanlist;
    }

    /**
     * 获取系列信息
     *
     * @return array 系列信息
     */
    private function getSetcodes() {
        return $this->cardParser->getSetcodes();
    }

    /**
     * 分批遍历数据库中的全部卡片
     *
     * @param string $dbFile 数据库文件路径
     * @return Generator 卡片迭代器
     */
    private function iterateCardsFromDatabase($dbFile) {
        $page = 1;
        $batchSize = 1000;

        do {
            $cards = $this->cardParser->getCardsForAuthorStats($dbFile, $page, $batchSize);
            $cardCount = count($cards);
            foreach ($cards as $card) {
                yield $card;
            }

            unset($cards);
            $page++;
        } while ($cardCount === $batchSize);
    }

    /**
     * 计算被禁系列数量
     *
     * @param array $cards 卡片列表
     * @param array $banlist 禁卡列表
     * @param array $setcodes 系列信息
     * @return int 被禁系列数量
     */
    private function countBannedSeries($cards, $banlist, $setcodes) {
        // 按系列分组卡片
        $seriesCards = [];
        $bannedSeries = [];

        foreach ($cards as $card) {
            // 跳过no42的卡片
            if ($card['is_no42']) {
                continue;
            }

            // 获取卡片的系列
            $cardSetcodes = $this->extractSetcodes($card['setcode']);

            foreach ($cardSetcodes as $setcode) {
                if (!isset($seriesCards[$setcode])) {
                    $seriesCards[$setcode] = [];
                }

                $seriesCards[$setcode][] = $card['id'];
            }
        }

        // 检查每个系列是否全部被禁止
        foreach ($seriesCards as $setcode => $cardIds) {
            if (count($cardIds) > 1) { // 只考虑有多张卡的系列
                $allBanned = true;

                foreach ($cardIds as $cardId) {
                    if (!isset($banlist[$cardId]) || $banlist[$cardId]['status'] !== 0) {
                        $allBanned = false;
                        break;
                    }
                }

                if ($allBanned) {
                    $bannedSeries[] = $setcode;
                }
            }
        }

        return count($bannedSeries);
    }

    /**
     * 提取卡片的系列代码
     *
     * @param int $setcode 系列代码
     * @return array 系列代码列表
     */
    private function extractSetcodes($setcode) {
        $result = [];

        // 系列代码是一个32位整数，每8位代表一个系列
        for ($i = 0; $i < 4; $i++) {
            $code = ($setcode >> ($i * 16)) & 0xFFFF;
            if ($code > 0) {
                $result[] = '0x' . dechex($code);
            }
        }

        return $result;
    }

    /**
     * 获取缓存文件路径
     *
     * @return string 缓存文件路径
     */
    private function getCacheFilePath() {
        return $this->cacheDir . '/author_hall_of_fame.json';
    }

    /**
     * 获取缓存锁文件路径
     *
     * @return string 锁文件路径
     */
    private function getCacheLockFilePath() {
        return $this->cacheDir . '/author_hall_of_fame.lock';
    }

    /**
     * 在共享锁内读取并校验作者榜缓存。
     *
     * @param string $cacheFile 缓存文件路径
     * @param string $sourceFingerprint 当前数据源指纹
     * @return array|null 有效的作者统计数据，缓存无效时返回null
     */
    private function readCachedAuthorStats($cacheFile, $sourceFingerprint) {
        $lockHandle = fopen($this->getCacheLockFilePath(), 'c+');
        if ($lockHandle === false) {
            return null;
        }

        if (!flock($lockHandle, LOCK_SH)) {
            fclose($lockHandle);
            return null;
        }

        $authorStats = null;
        clearstatcache(true, $cacheFile);
        if (is_file($cacheFile)) {
            $cacheTime = filemtime($cacheFile);
            $cacheDays = defined('AUTHOR_HALL_OF_FAME_CACHE_DAYS') ? AUTHOR_HALL_OF_FAME_CACHE_DAYS : 7;
            $isFresh = $cacheTime !== false && (time() - $cacheTime) < ($cacheDays * 86400);

            if ($isFresh) {
                $contents = file_get_contents($cacheFile);
                $envelope = $contents !== false ? json_decode($contents, true) : null;
                if (is_array($envelope)
                    && isset(
                        $envelope['cache_version'],
                        $envelope['source_fingerprint'],
                        $envelope['generated_at'],
                        $envelope['author_stats']
                    )
                    && (int)$envelope['cache_version'] === 2
                    && is_array($envelope['author_stats'])
                    && hash_equals((string)$envelope['source_fingerprint'], $sourceFingerprint)
                ) {
                    $authorStats = $envelope['author_stats'];
                }
            }
        }

        flock($lockHandle, LOCK_UN);
        fclose($lockHandle);
        return $authorStats;
    }

    /**
     * 缓存作者统计数据
     *
     * @param array $authorStats 作者统计数据
     * @param string $sourceFingerprint 数据源指纹
     * @return bool 是否写入成功
     */
    private function cacheAuthorStats($authorStats, $sourceFingerprint) {
        $cacheFile = $this->getCacheFilePath();
        $payload = json_encode([
            'cache_version' => 2,
            'source_fingerprint' => $sourceFingerprint,
            'generated_at' => date('c'),
            'author_stats' => $authorStats
        ], JSON_UNESCAPED_UNICODE);
        if ($payload === false) {
            return false;
        }

        $lockHandle = fopen($this->getCacheLockFilePath(), 'c+');
        if ($lockHandle === false) {
            return false;
        }

        if (!flock($lockHandle, LOCK_EX)) {
            fclose($lockHandle);
            return false;
        }

        $tempFile = tempnam($this->cacheDir, 'author_hall_of_fame.');
        $success = $tempFile !== false
            && file_put_contents($tempFile, $payload, LOCK_EX) !== false;

        if ($success && file_exists($cacheFile) && !unlink($cacheFile)) {
            $success = false;
        }
        if ($success && !rename($tempFile, $cacheFile)) {
            $success = false;
        }
        if ($tempFile !== false && file_exists($tempFile)) {
            unlink($tempFile);
        }

        flock($lockHandle, LOCK_UN);
        fclose($lockHandle);
        return $success;
    }

    /**
     * 计算影响榜单结果的全部数据源指纹
     *
     * @return string SHA-256指纹
     */
    private function getSourceFingerprint() {
        $files = [];
        foreach ($this->cardParser->getCardDatabaseFiles() as $file) {
            $files[] = [
                'name' => basename($file),
                'size' => file_exists($file) ? filesize($file) : null,
                'mtime' => file_exists($file) ? filemtime($file) : null
            ];
        }

        foreach (['strings.conf', 'lflist.conf'] as $fileName) {
            $path = CARD_DATA_PATH . '/' . $fileName;
            $files[] = [
                'name' => $fileName,
                'size' => file_exists($path) ? filesize($path) : null,
                'mtime' => file_exists($path) ? filemtime($path) : null
            ];
        }

        $db = Database::getInstance();
        $mappings = $db->getRows(
            'SELECT id, card_prefix, author_name, card_id_length, card_id_start, card_id_end, priority, alias, updated_at '
            . 'FROM author_mappings ORDER BY id ASC'
        );
        $rules = $db->getRows(
            'SELECT id, database_file, match_field, match_operator, match_value, target_type, target_value, '
            . 'author_name, priority, '
            . 'is_case_sensitive, is_enabled, updated_at FROM card_match_rules ORDER BY id ASC'
        );

        $payload = [
            'resolver_version' => 3,
            'files' => $files,
            'mappings' => $mappings,
            'rules' => $rules,
            'simple_mode' => defined('AUTHOR_HALL_OF_FAME_SIMPLE_MODE') ? AUTHOR_HALL_OF_FAME_SIMPLE_MODE : false,
            'excluded_databases' => defined('EXCLUDED_CARD_DATABASES') ? EXCLUDED_CARD_DATABASES : '',
            'database_priority' => defined('CARD_DATABASE_PRIORITY') ? CARD_DATABASE_PRIORITY : ''
        ];

        return hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE));
    }

    /**
     * 清除缓存
     *
     * @return bool 是否成功
     */
    public function clearCache() {
        return self::invalidateCacheFiles($this->cacheDir);
    }

    /**
     * 清除作者榜单及其指纹元数据
     *
     * @param string|null $cacheDir 可选缓存目录，测试可注入隔离目录
     * @return bool 是否全部清除成功
     */
    public static function invalidateCacheFiles($cacheDir = null) {
        $cacheDir = $cacheDir !== null ? $cacheDir : __DIR__ . '/../../data/cache';
        if (!is_dir($cacheDir)) {
            return true;
        }

        $lockFile = $cacheDir . '/author_hall_of_fame.lock';
        $lockHandle = fopen($lockFile, 'c+');
        if ($lockHandle === false) {
            return false;
        }
        if (!flock($lockHandle, LOCK_EX)) {
            fclose($lockHandle);
            return false;
        }

        $success = true;
        foreach (['author_hall_of_fame.json', 'author_hall_of_fame.meta.json'] as $fileName) {
            $path = $cacheDir . '/' . $fileName;
            if (file_exists($path) && !unlink($path)) {
                $success = false;
            }
        }

        flock($lockHandle, LOCK_UN);
        fclose($lockHandle);
        return $success;
    }

    /**
     * 更新作者光荣榜
     *
     * @return bool 是否成功
     */
    public function updateAuthorHallOfFame() {
        // 强制更新作者统计数据（不使用缓存）
        $this->getAuthorStats(true);

        return true;
    }

    /**
     * 获取作者的所有卡片
     *
     * @param string $authorName 作者名称
     * @param int $page 页码
     * @param int $perPage 每页显示数量
     * @return array 包含卡片列表和分页信息的数组
     */
    public function getAuthorCards($authorName, $page = 1, $perPage = 30) {
        // 获取作者统计数据
        $authorStats = $this->getAuthorStats();

        // 检查作者是否存在
        if (!isset($authorStats[$authorName]) || !is_array($authorStats[$authorName]) ||
            !isset($authorStats[$authorName]['cards'])) {
            return [
                'cards' => [],
                'total' => 0,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => 0
            ];
        }

        // 获取作者的所有卡片
        $allCards = [];
        foreach ($authorStats[$authorName]['cards'] as $cardInfo) {
            // 获取完整的卡片信息
            $card = $this->cardParser->getCardById($cardInfo['id']);
            if ($card) {
                $allCards[] = $card;
            }
        }

        // 计算分页信息
        $total = count($allCards);
        $totalPages = $perPage > 0 ? ceil($total / $perPage) : 1;

        // 获取当前页的卡片
        if ($perPage > 0) {
            $offset = ($page - 1) * $perPage;
            $cards = array_slice($allCards, $offset, $perPage);
        } else {
            // 如果perPage为0，则显示所有卡片
            $cards = $allCards;
        }

        return [
            'cards' => $cards,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => $totalPages
        ];
    }

    /**
     * 获取作者的所有被禁卡片
     *
     * @param string $authorName 作者名称
     * @param int $page 页码
     * @param int $perPage 每页显示数量
     * @return array 包含卡片列表和分页信息的数组
     */
    public function getAuthorBannedCards($authorName, $page = 1, $perPage = 30) {
        // 获取作者统计数据
        $authorStats = $this->getAuthorStats();

        // 检查作者是否存在
        if (!isset($authorStats[$authorName]) || !is_array($authorStats[$authorName]) ||
            !isset($authorStats[$authorName]['banned_cards_list'])) {
            return [
                'cards' => [],
                'total' => 0,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => 0
            ];
        }

        // 获取作者的所有被禁卡片
        $allBannedCards = [];
        foreach ($authorStats[$authorName]['banned_cards_list'] as $cardInfo) {
            // 获取完整的卡片信息
            $card = $this->cardParser->getCardById($cardInfo['id']);
            if ($card) {
                $allBannedCards[] = $card;
            }
        }

        // 计算分页信息
        $total = count($allBannedCards);
        $totalPages = $perPage > 0 ? ceil($total / $perPage) : 1;

        // 获取当前页的卡片
        if ($perPage > 0) {
            $offset = ($page - 1) * $perPage;
            $cards = array_slice($allBannedCards, $offset, $perPage);
        } else {
            // 如果perPage为0，则显示所有卡片
            $cards = $allBannedCards;
        }

        return [
            'cards' => $cards,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => $totalPages
        ];
    }
}
