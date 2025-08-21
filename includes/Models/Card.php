<?php
/**
 * 卡片模型
 *
 * 处理卡片相关的数据操作
 */
class Card {
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
     * 获取所有卡片（支持分页）
     *
     * @param string $dbFile 数据库文件路径，如果为null则获取所有数据库的卡片
     * @param int $page 页码，从1开始
     * @param int $perPage 每页显示的卡片数量
     * @param bool $countOnly 是否只返回卡片总数
     * @return array 包含卡片列表和分页信息的数组
     */
    public function getAllCards($dbFile = null, $page = 1, $perPage = 20, $countOnly = false) {
        return $this->cardParser->getAllCards($dbFile, $page, $perPage, $countOnly);
    }

    /**
     * 获取所有卡片数据库文件
     *
     * @return array 数据库文件列表
     */
    public function getAllDatabaseFiles() {
        return $this->cardParser->getCardDatabaseFiles();
    }

    /**
     * 根据ID获取卡片
     *
     * @param int $cardId 卡片ID
     * @param bool $forceNewConnection 是否强制使用新的数据库连接
     * @return array|null 卡片信息
     */
    public function getCardById($cardId, $forceNewConnection = false) {
        return $this->cardParser->getCardById($cardId, $forceNewConnection);
    }

    /**
     * 搜索卡片
     *
     * @param string $keyword 关键词
     * @return array 卡片列表
     */
    public function searchCards($keyword) {
        return $this->cardParser->searchCards($keyword);
    }

    /**
     * 获取卡片禁限状态
     *
     * @param int $cardId 卡片ID
     * @param string $environment 环境名称
     * @return int 禁限状态 (0:禁止, 1:限制, 2:准限制, 3:无限制)
     */
    public function getCardLimitStatus($cardId, $environment) {
        return $this->cardParser->getCardLimitStatus($cardId, $environment);
    }

    /**
     * 获取所有环境
     *
     * @return array 环境列表
     */
    public function getAllEnvironments() {
        return $this->cardParser->getAllEnvironments();
    }

    /**
     * 根据系列代码获取同系列卡片
     *
     * @param int $setcode 系列代码
     * @param bool $excludeTcgCards 是否排除TCG卡片
     * @return array 卡片列表
     */
    public function getCardsBySetcode($setcode, $excludeTcgCards = true) {
        return $this->cardParser->getCardsBySetcode($setcode, $excludeTcgCards);
    }

    /**
     * 获取卡片图片URL
     *
     * @param int $cardId 卡片ID
     * @return string 图片URL
     */
    public function getCardImageUrl($cardId) {
        $imagePath = $this->cardParser->getCardImagePath($cardId);
        return BASE_URL . $imagePath;
    }

    /**
     * 随机获取一张卡片
     *
     * @return array|null 卡片信息
     */
    public function getRandomCard() {
        return $this->cardParser->getRandomCard();
    }

    /**
     * 根据卡片前缀获取卡片列表
     *
     * @param int $prefix 卡片前缀（卡号前三位；若为7位卡号则取前两位）
     * @param int|null $excludeId 需要排除的卡片ID
     * @return array 卡片列表
     */
    public function getCardsByPrefix($prefix, $excludeId = null) {
        $cards = $this->cardParser->getCardsByPrefix($prefix);
        if ($excludeId !== null) {
            $cards = array_filter($cards, function($c) use ($excludeId) { return $c['id'] != $excludeId; });
        }
        return $cards;
    }

    /**
     * 根据ID列表批量获取卡片
     *
     * @param array $cardIds 卡片ID列表
     * @return array 卡片信息列表
     */
    public function getCardsByIds($cardIds) {
        if (empty($cardIds)) {
            return [];
        }

        // 确保输入是数组并去重
        if (!is_array($cardIds)) {
            $cardIds = [$cardIds];
        }

        $cardIds = array_unique($cardIds);
        $cards = [];

        // 调试信息
        Utils::debug('Card::getCardsByIds 开始', [
            'inputCardIds' => $cardIds,
            'count' => count($cardIds)
        ]);

        // 为每个卡片ID单独查询，确保不会出现重复
        foreach ($cardIds as $index => $cardId) {
            // 确保卡片ID是整数
            $cardId = (int)$cardId;
            if ($cardId <= 0) {
                continue;
            }

            Utils::debug('Card::getCardsByIds 查询单个卡片', [
                'index' => $index,
                'cardId' => $cardId
            ]);

            // 强制使用新的数据库连接，避免缓存问题
            $card = $this->getCardById($cardId, true);

            // 额外验证：确保返回的卡片ID与请求的ID匹配
            if ($card && (int)$card['id'] !== $cardId) {
                Utils::debug('Card::getCardsByIds ID不匹配', [
                    'requestedId' => $cardId,
                    'returnedId' => $card['id'],
                    'cardName' => $card['name']
                ]);
                // ID不匹配，跳过这张卡片
                continue;
            }

            if ($card) {
                // 确保卡片ID正确
                $card['id'] = $cardId;
                $cards[] = $card;

                Utils::debug('Card::getCardsByIds 找到卡片', [
                    'index' => $index,
                    'cardId' => $cardId,
                    'cardName' => $card['name'],
                    'returnedId' => $card['id']
                ]);
            } else {
                Utils::debug('Card::getCardsByIds 未找到卡片', [
                    'index' => $index,
                    'cardId' => $cardId
                ]);
            }
        }

        Utils::debug('Card::getCardsByIds 完成', [
            'inputCount' => count($cardIds),
            'resultCount' => count($cards),
            'resultIds' => array_column($cards, 'id'),
            'resultNames' => array_column($cards, 'name')
        ]);

        return $cards;
    }
}
