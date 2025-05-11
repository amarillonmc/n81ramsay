<?php
/**
 * 卡片排行榜控制器
 *
 * 处理卡片排行榜相关的请求
 */
class CardRankingController {
    /**
     * 卡片排行榜模型
     * @var CardRankingModel
     */
    private $cardRankingModel;

    /**
     * 用户模型
     * @var User
     */
    private $userModel;

    /**
     * 构造函数
     */
    public function __construct() {
        // 确保所有必要的类已加载
        if (!class_exists('DeckParser')) {
            require_once __DIR__ . '/../Core/DeckParser.php';
        }

        // 确保模型类已加载
        require_once __DIR__ . '/../Models/CardRanking.php';

        $this->cardRankingModel = new CardRankingModel();
        $this->userModel = new User();
    }

    /**
     * 卡片排行榜首页
     */
    public function index() {
        // 检查功能是否启用
        if (!defined('CARD_RANKING_ENABLED') || !CARD_RANKING_ENABLED) {
            header('Location: ' . BASE_URL);
            exit;
        }

        // 获取请求参数
        $timeRange = isset($_GET['time_range']) ? $_GET['time_range'] : 'week';
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

        // 验证参数
        $timeRange = $this->cardRankingModel->validateTimeRange($timeRange);
        $limit = $this->cardRankingModel->validateLimit($limit);

        // 获取卡片排行榜数据
        $rankingData = $this->cardRankingModel->getCardRanking($timeRange, $limit);

        // 获取时间范围选项
        $timeRangeOptions = $this->cardRankingModel->getTimeRangeOptions();

        // 获取显示数量选项
        $limitOptions = $this->cardRankingModel->getLimitOptions();

        // 渲染视图
        include __DIR__ . '/../Views/layout.php';
        include __DIR__ . '/../Views/card_ranking/index.php';
        include __DIR__ . '/../Views/footer.php';
    }

    /**
     * 更新卡片排行榜
     */
    public function update() {
        // 检查功能是否启用
        if (!defined('CARD_RANKING_ENABLED') || !CARD_RANKING_ENABLED) {
            header('Location: ' . BASE_URL);
            exit;
        }

        // 要求管理员权限
        $this->userModel->requirePermission(1);

        // 获取请求参数
        $timeRange = isset($_GET['time_range']) ? $_GET['time_range'] : 'week';

        // 验证参数
        $timeRange = $this->cardRankingModel->validateTimeRange($timeRange);

        // 强制更新卡片排行榜数据
        $this->cardRankingModel->getCardRanking($timeRange, 10, true);

        // 设置成功消息
        $_SESSION['success_message'] = '卡片排行榜已更新';

        // 重定向回卡片排行榜页面
        header('Location: ' . BASE_URL . '?controller=card_ranking&time_range=' . $timeRange);
        exit;
    }
}
