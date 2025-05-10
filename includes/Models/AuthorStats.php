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
     * 构造函数
     */
    public function __construct() {
        $this->cardParser = CardParser::getInstance();
    }

    /**
     * 获取作者统计数据
     *
     * @return array 作者统计数据
     */
    public function getAuthorStats() {
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

        // 如果是简略识别模式，预先加载所有作者映射
        $authorMappings = [];
        if (defined('AUTHOR_HALL_OF_FAME_SIMPLE_MODE') && AUTHOR_HALL_OF_FAME_SIMPLE_MODE) {
            $db = Database::getInstance();
            $mappings = $db->getRows('SELECT * FROM author_mappings');
            foreach ($mappings as $mapping) {
                $authorMappings[$mapping['card_prefix']] = $mapping['author_name'];
            }
        }

        // 处理每个数据库文件
        foreach ($dbFiles as $dbFile) {
            $dbName = basename($dbFile);
            $isNo42 = (strpos($dbName, 'no42') !== false);

            // 获取数据库中的所有卡片
            $cards = $this->getAllCardsFromDatabase($dbFile);

            // 统计每个作者的卡片数量
            foreach ($cards as $card) {
                // 检查卡片ID是否已经处理过，防止重复统计
                $cardId = (int)$card['id'];
                if (isset($processedCardIds[$cardId])) {
                    continue;
                }
                $processedCardIds[$cardId] = true;

                // 根据配置决定使用哪种方式获取作者
                if (defined('AUTHOR_HALL_OF_FAME_SIMPLE_MODE') && AUTHOR_HALL_OF_FAME_SIMPLE_MODE) {
                    // 简略识别模式：仅使用管理员配置的作者列表
                    $author = $this->getAuthorFromMappings($card, $authorMappings);
                } else {
                    // 完整识别模式：使用 CardParser 的 getCardAuthor 方法
                    // 该方法已经实现了优先级：数据库记录 > 卡片描述文本 > strings.conf
                    $author = $this->cardParser->getCardAuthor($card);
                }

                // 如果作者名为空，使用卡片ID前三位作为作者名
                if (empty(trim($author)) && $author !== "未知作者") {
                    $cardIdStr = (string)$card['id'];
                    if (strlen($cardIdStr) >= 3) {
                        $author = "ID前缀: " . substr($cardIdStr, 0, 3);
                    } else {
                        $author = "未知作者";
                    }
                }

                // 初始化作者统计数据
                if (!isset($authorStats[$author])) {
                    $authorStats[$author] = [
                        'name' => $author,
                        'total_cards' => 0,
                        'banned_cards' => 0,
                        'banned_series' => 0,
                        'banned_percentage' => 0,
                        'is_unknown' => ($author === "未知作者" || strpos($author, "ID前缀: ") === 0),
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
     * 获取数据库中的所有卡片
     *
     * @param string $dbFile 数据库文件路径
     * @return array 卡片列表
     */
    private function getAllCardsFromDatabase($dbFile) {
        $result = $this->cardParser->getAllCards($dbFile, 1, 10000, false);
        return $result['cards'] ?? [];
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
     * 从预加载的作者映射中获取作者信息
     *
     * @param array $card 卡片信息
     * @param array $authorMappings 预加载的作者映射
     * @return string 作者名称
     */
    private function getAuthorFromMappings($card, $authorMappings) {
        $cardId = (string)$card['id'];

        // 尝试使用卡片ID前缀查找作者映射
        if (strlen($cardId) >= 3) {
            $cardPrefix = substr($cardId, 0, 3);

            if (isset($authorMappings[$cardPrefix])) {
                return $authorMappings[$cardPrefix];
            }
        }

        // 如果找不到作者信息，返回"未知作者"
        return "未知作者";
    }

    /**
     * 更新作者光荣榜
     *
     * @return bool 是否成功
     */
    public function updateAuthorHallOfFame() {
        // 获取作者统计数据
        $authorStats = $this->getAuthorStats();

        // 生成更新时间
        $updateTime = date('Y-m-d H:i:s');

        // 保存到数据库或文件
        // 这里可以根据需要实现保存逻辑

        return true;
    }
}
