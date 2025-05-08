<?php
/**
 * 卡片检索控制器
 *
 * 处理卡片检索相关的请求
 */
class CardController {
    /**
     * 卡片模型
     * @var Card
     */
    private $cardModel;

    /**
     * 构造函数
     */
    public function __construct() {
        $this->cardModel = new Card();
    }

    /**
     * 首页
     */
    public function index() {
        // 获取所有数据库文件
        $dbFiles = $this->cardModel->getAllDatabaseFiles();

        // 获取当前选择的数据库文件
        $selectedDb = isset($_GET['db']) ? $_GET['db'] : null;

        // 如果没有选择数据库文件，则使用第一个
        if ($selectedDb === null && !empty($dbFiles)) {
            $selectedDb = $dbFiles[0];
        }

        // 获取卡片列表
        $cards = [];
        if ($selectedDb !== null) {
            $cards = $this->cardModel->getAllCards($selectedDb);
        }

        // 渲染视图
        include __DIR__ . '/../Views/layout.php';
        include __DIR__ . '/../Views/cards/index.php';
        include __DIR__ . '/../Views/footer.php';
    }

    /**
     * 搜索
     */
    public function search() {
        // 获取搜索关键词
        $keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';

        // 搜索卡片
        $cards = [];
        if (!empty($keyword)) {
            $cards = $this->cardModel->searchCards($keyword);
        }

        // 渲染视图
        include __DIR__ . '/../Views/layout.php';
        include __DIR__ . '/../Views/cards/search.php';
        include __DIR__ . '/../Views/footer.php';
    }

    /**
     * 详情
     */
    public function detail() {
        // 获取卡片ID
        $cardId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        // 获取卡片信息
        $card = $this->cardModel->getCardById($cardId);

        // 如果卡片不存在，则重定向到首页
        if (!$card) {
            header('Location: ' . BASE_URL);
            exit;
        }

        // 获取环境列表
        $environments = Utils::getEnvironments();

        // 获取卡片在各环境中的禁限状态
        $limitStatus = [];
        foreach ($environments as $env) {
            $header = $env['header'];
            $limitStatus[$header] = $this->cardModel->getCardLimitStatus($cardId, $header);
        }

        // 渲染视图
        include __DIR__ . '/../Views/layout.php';
        include __DIR__ . '/../Views/cards/detail.php';
        include __DIR__ . '/../Views/footer.php';
    }
}
