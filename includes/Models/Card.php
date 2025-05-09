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
     * @return array|null 卡片信息
     */
    public function getCardById($cardId) {
        return $this->cardParser->getCardById($cardId);
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
     * 获取卡片图片URL
     *
     * @param int $cardId 卡片ID
     * @return string 图片URL
     */
    public function getCardImageUrl($cardId) {
        $imagePath = $this->cardParser->getCardImagePath($cardId);
        return BASE_URL . $imagePath;
    }
}
