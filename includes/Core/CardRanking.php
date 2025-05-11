<?php
/**
 * 卡片排行榜类
 *
 * 负责生成卡片使用率排行榜
 */
class CardRanking {
    /**
     * 单例实例
     * @var CardRanking
     */
    private static $instance;

    /**
     * 卡组解析器
     * @var DeckParser
     */
    private $deckParser;

    /**
     * 卡片解析器
     * @var CardParser
     */
    private $cardParser;

    /**
     * 数据库实例
     * @var Database
     */
    private $db;

    /**
     * 缓存目录
     * @var string
     */
    private $cacheDir;

    /**
     * 构造函数
     */
    private function __construct() {
        $this->deckParser = DeckParser::getInstance();
        $this->cardParser = CardParser::getInstance();
        $this->db = Database::getInstance();
        $this->cacheDir = __DIR__ . '/../../data/cache';

        // 确保缓存目录存在
        if (!file_exists($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    /**
     * 获取单例实例
     *
     * @return CardRanking 卡片排行榜实例
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * 获取卡片排行榜
     *
     * @param string $timeRange 时间范围 (week, two_weeks, month, all)
     * @param int $limit 显示数量限制
     * @param bool $forceUpdate 是否强制更新
     * @return array 卡片排行榜数据
     */
    public function getCardRanking($timeRange = 'week', $limit = 10, $forceUpdate = false) {
        // 检查缓存
        $cacheFile = $this->getCacheFilePath($timeRange);
        
        // 如果缓存存在且未过期，且不强制更新，则直接返回缓存数据
        if (!$forceUpdate && file_exists($cacheFile) && $this->isCacheValid($cacheFile)) {
            $cachedData = json_decode(file_get_contents($cacheFile), true);
            
            // 如果请求的限制与缓存的不同，则裁剪数据
            if ($limit !== count($cachedData['top_cards'])) {
                $cachedData['top_cards'] = array_slice($cachedData['top_cards'], 0, $limit);
            }
            
            return $cachedData;
        }
        
        // 根据时间范围获取日期范围
        $dateRange = $this->getDateRangeByTimeRange($timeRange);
        
        // 获取卡组文件
        $deckFiles = $this->deckParser->getDeckFiles($dateRange['start'], $dateRange['end']);
        
        // 分析卡片使用情况
        $cardUsage = $this->deckParser->analyzeCardUsage($deckFiles);
        
        // 获取卡片详细信息并排序
        $rankingData = $this->processRankingData($cardUsage, $limit);
        
        // 缓存数据
        $this->cacheRankingData($rankingData, $timeRange);
        
        return $rankingData;
    }

    /**
     * 处理排行榜数据
     *
     * @param array $cardUsage 卡片使用统计
     * @param int $limit 显示数量限制
     * @return array 处理后的排行榜数据
     */
    private function processRankingData($cardUsage, $limit) {
        $rankingData = [
            'top_cards' => [],
            'all_cards' => [],
            'total_decks' => count($cardUsage) > 0 ? max(array_column($cardUsage, 'total_decks')) : 0,
            'generated_time' => date('Y-m-d H:i:s')
        ];
        
        // 处理每张卡的详细信息
        foreach ($cardUsage as $cardId => $usage) {
            // 先从DIY卡数据库中查找
            $cardInfo = $this->cardParser->getCardById($cardId);
            
            // 如果在DIY卡数据库中找不到，则从TCG卡数据库中查找
            if (!$cardInfo && defined('TCG_CARD_DATA_PATH') && file_exists(TCG_CARD_DATA_PATH)) {
                $cardInfo = $this->getTcgCardInfo($cardId);
            }
            
            // 如果找不到卡片信息，则跳过
            if (!$cardInfo) {
                continue;
            }
            
            // 构建卡片数据
            $cardData = [
                'id' => $cardId,
                'name' => $cardInfo['name'],
                'type' => $cardInfo['type'],
                'type_text' => $cardInfo['type_text'] ?? '',
                'main_count_1' => $usage['main_count_1'],
                'main_count_2' => $usage['main_count_2'],
                'main_count_3' => $usage['main_count_3'],
                'side_count' => $usage['side_count'],
                'total_decks' => $usage['total_decks'],
                'usage_rate' => $rankingData['total_decks'] > 0 ? 
                    round(($usage['total_decks'] / $rankingData['total_decks']) * 100, 2) : 0
            ];
            
            $rankingData['all_cards'][] = $cardData;
        }
        
        // 按使用率排序
        usort($rankingData['all_cards'], function($a, $b) {
            return $b['usage_rate'] <=> $a['usage_rate'];
        });
        
        // 提取前N名
        $rankingData['top_cards'] = array_slice($rankingData['all_cards'], 0, $limit);
        
        return $rankingData;
    }

    /**
     * 从TCG卡数据库中获取卡片信息
     *
     * @param int $cardId 卡片ID
     * @return array|null 卡片信息
     */
    private function getTcgCardInfo($cardId) {
        try {
            $tcgDb = new PDO('sqlite:' . TCG_CARD_DATA_PATH);
            $tcgDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $sql = "
                SELECT
                    d.id, d.ot, d.alias, d.setcode, d.type, d.atk, d.def, d.level, d.race, d.attribute,
                    t.name, t.desc
                FROM
                    datas d
                JOIN
                    texts t ON d.id = t.id
                WHERE
                    d.id = :id
            ";
            
            $stmt = $tcgDb->prepare($sql);
            $stmt->execute(['id' => $cardId]);
            $card = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($card) {
                $card['type_text'] = $this->cardParser->getTypeText($card['type']);
                return $card;
            }
        } catch (PDOException $e) {
            Utils::debug('获取TCG卡片数据失败', ['错误' => $e->getMessage()]);
        }
        
        return null;
    }

    /**
     * 根据时间范围获取日期范围
     *
     * @param string $timeRange 时间范围 (week, two_weeks, month, all)
     * @return array 日期范围 ['start' => '2023-01-01', 'end' => '2023-01-31']
     */
    private function getDateRangeByTimeRange($timeRange) {
        $end = date('Y-m-d');
        $start = null;
        
        switch ($timeRange) {
            case 'week':
                $start = date('Y-m-d', strtotime('-1 week'));
                break;
            case 'two_weeks':
                $start = date('Y-m-d', strtotime('-2 weeks'));
                break;
            case 'month':
                $start = date('Y-m-d', strtotime('-1 month'));
                break;
            case 'all':
            default:
                $start = null;
                break;
        }
        
        return [
            'start' => $start,
            'end' => $end
        ];
    }

    /**
     * 获取缓存文件路径
     *
     * @param string $timeRange 时间范围
     * @return string 缓存文件路径
     */
    private function getCacheFilePath($timeRange) {
        return $this->cacheDir . '/card_ranking_' . $timeRange . '.json';
    }

    /**
     * 检查缓存是否有效
     *
     * @param string $cacheFile 缓存文件路径
     * @return bool 缓存是否有效
     */
    private function isCacheValid($cacheFile) {
        $cacheTime = filemtime($cacheFile);
        $cacheDays = defined('CARD_RANKING_CACHE_DAYS') ? CARD_RANKING_CACHE_DAYS : 7;
        
        return (time() - $cacheTime) < ($cacheDays * 86400);
    }

    /**
     * 缓存排行榜数据
     *
     * @param array $rankingData 排行榜数据
     * @param string $timeRange 时间范围
     */
    private function cacheRankingData($rankingData, $timeRange) {
        $cacheFile = $this->getCacheFilePath($timeRange);
        file_put_contents($cacheFile, json_encode($rankingData, JSON_UNESCAPED_UNICODE));
    }
}
