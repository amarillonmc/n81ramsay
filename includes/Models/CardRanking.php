<?php
/**
 * 卡片排行榜模型
 *
 * 处理卡片排行榜相关的数据操作
 */
class CardRankingModel {
    /**
     * 卡片排行榜核心类
     * @var \CardRanking
     */
    private $cardRankingCore;

    /**
     * 构造函数
     */
    public function __construct() {
        // 确保核心类已加载
        require_once __DIR__ . '/../Core/CardRanking.php';
        $this->cardRankingCore = \CardRanking::getInstance();
    }

    /**
     * 获取卡片排行榜
     *
     * @param string $timeRange 时间范围 (week, two_weeks, month, all)
     * @param int $limit 显示数量限制
     * @param bool $forceUpdate 是否强制更新
     * @param bool $diyOnly 是否只显示DIY卡片
     * @return array 卡片排行榜数据
     */
    public function getCardRanking($timeRange = 'week', $limit = 10, $forceUpdate = false, $diyOnly = false) {
        return $this->cardRankingCore->getCardRanking($timeRange, $limit, $forceUpdate, $diyOnly);
    }

    /**
     * 获取可用的时间范围选项
     *
     * @return array 时间范围选项
     */
    public function getTimeRangeOptions() {
        return [
            'week' => '一周内',
            'two_weeks' => '两周内',
            'month' => '一个月内',
            'all' => '全部'
        ];
    }

    /**
     * 获取可用的显示数量选项
     *
     * @return array 显示数量选项
     */
    public function getLimitOptions() {
        return [3, 7, 10];
    }

    /**
     * 获取可用的详细统计显示数量选项
     *
     * @return array 详细统计显示数量选项
     */
    public function getDetailLimitOptions() {
        return [
            10 => '前10名',
            30 => '前30名',
            50 => '前50名',
            0 => '全部'
        ];
    }

    /**
     * 验证时间范围
     *
     * @param string $timeRange 时间范围
     * @return string 有效的时间范围
     */
    public function validateTimeRange($timeRange) {
        $validOptions = array_keys($this->getTimeRangeOptions());
        return in_array($timeRange, $validOptions) ? $timeRange : 'week';
    }

    /**
     * 验证显示数量
     *
     * @param int $limit 显示数量
     * @return int 有效的显示数量
     */
    public function validateLimit($limit) {
        $validOptions = $this->getLimitOptions();
        return in_array($limit, $validOptions) ? $limit : 10;
    }

    /**
     * 清除所有缓存文件
     */
    public function clearAllCaches() {
        return $this->cardRankingCore->clearAllCaches();
    }
}
