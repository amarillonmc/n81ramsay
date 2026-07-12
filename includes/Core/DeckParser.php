<?php
/**
 * 卡组解析类
 *
 * 负责解析卡组文件，提取卡片使用情况
 */
class DeckParser {
    /**
     * 单例实例
     * @var DeckParser
     */
    private static $instance;

    /**
     * 卡组文件目录
     * @var string
     */
    private $deckLogPath;

    /**
     * 卡片 alias 归一化缓存
     * @var array
     */
    private $realCardIdCache;

    /**
     * 构造函数
     */
    private function __construct() {
        $this->deckLogPath = defined('DECK_LOG_PATH') ? DECK_LOG_PATH : __DIR__ . '/../../deck_log';
        $this->realCardIdCache = [];
    }

    /**
     * 获取单例实例
     *
     * @return DeckParser 卡组解析实例
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * 获取卡组文件列表
     *
     * @param string $startDate 开始日期 (YYYY-MM-DD)
     * @param string $endDate 结束日期 (YYYY-MM-DD)
     * @return array 卡组文件列表
     */
    public function getDeckFiles($startDate = null, $endDate = null) {
        // 确保目录存在
        if (!file_exists($this->deckLogPath)) {
            return [];
        }

        $files = scandir($this->deckLogPath);
        $deckFiles = [];

        foreach ($files as $file) {
            // 跳过目录和非ydk文件
            if ($file === '.' || $file === '..' || !str_ends_with($file, '.ydk')) {
                continue;
            }

            // 从文件名中提取日期
            $dateMatch = [];
            if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $file, $dateMatch)) {
                $fileDate = $dateMatch[1];

                // 如果指定了日期范围，则过滤文件
                if ($startDate !== null && $fileDate < $startDate) {
                    continue;
                }
                if ($endDate !== null && $fileDate > $endDate) {
                    continue;
                }

                $deckFiles[] = $this->deckLogPath . '/' . $file;
            }
        }

        return $deckFiles;
    }

    /**
     * 解析卡组文件
     *
     * @param string $filePath 文件路径
     * @return array 卡组数据，包含主卡组和副卡组
     */
    public function parseDeckFile($filePath) {
        if (!file_exists($filePath)) {
            return [
                'main' => [],
                'side' => []
            ];
        }

        $content = file_get_contents($filePath);
        $lines = explode("\n", $content);

        $main = [];
        $side = [];
        $currentSection = null;

        foreach ($lines as $line) {
            $line = trim($line);

            // 跳过空行
            if (empty($line)) {
                continue;
            }

            // 处理节标题
            if (strpos($line, '#') === 0) {
                if ($line === '#main') {
                    $currentSection = 'main';
                }
                continue;
            }

            if (strpos($line, '!side') === 0) {
                $currentSection = 'side';
                continue;
            }

            // 处理卡片ID
            if (is_numeric($line)) {
                $cardId = (int)$line;
                if ($currentSection === 'main') {
                    $main[] = $cardId;
                } elseif ($currentSection === 'side') {
                    $side[] = $cardId;
                }
            }
        }

        return [
            'main' => $main,
            'side' => $side,
            'filename' => basename($filePath)
        ];
    }

    /**
     * 从文件名中提取日期
     *
     * @param string $filename 文件名
     * @return string|null 日期 (YYYY-MM-DD)
     */
    public function extractDateFromFilename($filename) {
        $dateMatch = [];
        if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $filename, $dateMatch)) {
            return $dateMatch[1];
        }
        return null;
    }

    /**
     * 获取卡片的真实ID（考虑alias字段）
     *
     * @param int $cardId 卡片ID
     * @return int 真实卡片ID
     */
    private function getRealCardId($cardId) {
        $cardId = (int)$cardId;
        if (array_key_exists($cardId, $this->realCardIdCache)) {
            return $this->realCardIdCache[$cardId];
        }

        // 获取卡片解析器实例
        $cardParser = CardParser::getInstance();

        // 获取卡片信息
        $card = $cardParser->getCardById($cardId);

        // 如果卡片存在且有alias字段，则返回alias对应的卡片ID
        if ($card && $card['alias'] > 0) {
            $this->realCardIdCache[$cardId] = (int)$card['alias'];
            return $this->realCardIdCache[$cardId];
        }

        // 否则返回原始卡片ID
        $this->realCardIdCache[$cardId] = $cardId;
        return $this->realCardIdCache[$cardId];
    }

    /**
     * 统计卡片使用情况
     *
     * @param array $deckFiles 卡组文件列表
     * @return array 卡片使用统计
     */
    public function analyzeCardUsage($deckFiles) {
        Utils::checkMemoryUsage('卡组分析开始');

        $cardUsage = [];
        $processedCount = 0;
        $totalFiles = count($deckFiles);

        foreach ($deckFiles as $file) {
            $deck = $this->parseDeckFile($file);
            $this->analyzeSingleDeck($deck, $cardUsage);

            $processedCount++;

            // 每处理100个文件检查一次内存使用情况
            if ($processedCount % 100 === 0) {
                if (Utils::checkMemoryUsage("卡组分析进度 {$processedCount}/{$totalFiles}", 2048)) {
                    Utils::forceGarbageCollection('卡组分析');
                }
            }
        }

        Utils::checkMemoryUsage('卡组分析完成');
        return $cardUsage;
    }

    /**
     * 统计已经解码的卡组数据
     *
     * @param array|Traversable $decks 卡组数据，每项包含 main 与 side
     * @return array 卡片使用统计
     */
    public function analyzeDeckData($decks) {
        Utils::checkMemoryUsage('卡组数据分析开始');

        $cardUsage = [];
        $processedCount = 0;
        $totalDecks = is_array($decks) || $decks instanceof Countable
            ? count($decks)
            : null;

        foreach ($decks as $deck) {
            $this->analyzeSingleDeck($deck, $cardUsage);
            $processedCount++;

            if ($processedCount % 100 === 0) {
                $progress = $totalDecks === null
                    ? (string)$processedCount
                    : $processedCount . '/' . $totalDecks;
                if (Utils::checkMemoryUsage("卡组数据分析进度 {$progress}", 2048)) {
                    Utils::forceGarbageCollection('卡组数据分析');
                }
            }
        }

        Utils::checkMemoryUsage('卡组数据分析完成');
        return $cardUsage;
    }

    /**
     * 将单副卡组合并进使用统计
     *
     * @param array $deck 单副卡组
     * @param array $cardUsage 累计统计（引用）
     * @return void
     */
    private function analyzeSingleDeck($deck, &$cardUsage) {
        $mainCards = isset($deck['main']) && is_array($deck['main']) ? $deck['main'] : [];
        $sideCards = isset($deck['side']) && is_array($deck['side']) ? $deck['side'] : [];

        // 处理主卡组卡片，将卡片ID映射到真实ID（考虑alias字段）
        $mainDeck = [];
        foreach ($mainCards as $cardId) {
            $mainDeck[] = $this->getRealCardId($cardId);
        }

        // 处理副卡组卡片，将卡片ID映射到真实ID（考虑alias字段）
        $sideDeck = [];
        foreach ($sideCards as $cardId) {
            $sideDeck[] = $this->getRealCardId($cardId);
        }

        $mainDeckCounts = array_count_values($mainDeck);
        $uniqueMainCards = array_unique($mainDeck);

        foreach ($uniqueMainCards as $cardId) {
            $this->initializeCardUsage($cardUsage, $cardId);
            $cardCount = $mainDeckCounts[$cardId];

            if ($cardCount === 1) {
                $cardUsage[$cardId]['main_count_1']++;
            } elseif ($cardCount === 2) {
                $cardUsage[$cardId]['main_count_2']++;
            } elseif ($cardCount === 3) {
                $cardUsage[$cardId]['main_count_3']++;
            }

            $cardUsage[$cardId]['total_decks']++;
        }

        $uniqueSideCards = array_unique($sideDeck);
        foreach ($uniqueSideCards as $cardId) {
            $this->initializeCardUsage($cardUsage, $cardId);
            $cardUsage[$cardId]['side_count']++;

            if (!in_array($cardId, $mainDeck, true)) {
                $cardUsage[$cardId]['total_decks']++;
            }
        }
    }

    /**
     * 初始化单张卡的统计槽位
     *
     * @param array $cardUsage 累计统计（引用）
     * @param int $cardId 卡片 ID
     * @return void
     */
    private function initializeCardUsage(&$cardUsage, $cardId) {
        if (!isset($cardUsage[$cardId])) {
            $cardUsage[$cardId] = [
                'main_count_1' => 0,
                'main_count_2' => 0,
                'main_count_3' => 0,
                'side_count' => 0,
                'total_decks' => 0
            ];
        }
    }
}

// 兼容PHP 7.x的str_ends_with函数
if (!function_exists('str_ends_with')) {
    function str_ends_with($haystack, $needle) {
        $length = strlen($needle);
        if ($length === 0) {
            return true;
        }
        return (substr($haystack, -$length) === $needle);
    }
}
