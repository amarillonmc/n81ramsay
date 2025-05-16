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

        // 验证数据库文件是否在允许的列表中
        if ($selectedDb !== null) {
            $isValid = false;
            foreach ($dbFiles as $dbFile) {
                if ($selectedDb === $dbFile || $selectedDb === basename($dbFile)) {
                    $selectedDb = $dbFile; // 确保使用完整路径
                    $isValid = true;
                    break;
                }
            }

            // 如果不是有效的数据库文件，则重置为null
            if (!$isValid) {
                $selectedDb = null;
            }
        }

        // 如果没有选择数据库文件，则使用第一个
        if ($selectedDb === null && !empty($dbFiles)) {
            $selectedDb = $dbFiles[0];
        }

        // 获取分页参数
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $perPage = isset($_GET['per_page']) ? intval($_GET['per_page']) : CARDS_PER_PAGE;

        // 验证每页显示数量
        $validPerPageOptions = [10, 20, 50, 100];
        if (!in_array($perPage, $validPerPageOptions)) {
            $perPage = CARDS_PER_PAGE; // 使用配置文件中的默认值
        }

        // 获取卡片列表
        $result = [];
        if ($selectedDb !== null) {
            $result = $this->cardModel->getAllCards($selectedDb, $page, $perPage);
            $cards = $result['cards'];
            $pagination = [
                'total' => $result['total'],
                'page' => $result['page'],
                'per_page' => $result['per_page'],
                'total_pages' => $result['total_pages']
            ];
        } else {
            $cards = [];
            $pagination = [
                'total' => 0,
                'page' => 1,
                'per_page' => $perPage,
                'total_pages' => 0
            ];
        }

        // 设置每页显示选项
        $perPageOptions = $validPerPageOptions;

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

        // 判断是否为TCG卡片
        $isTcgCard = false;
        if (isset($card['database_file']) && $card['database_file'] === basename(TCG_CARD_DATA_PATH)) {
            $isTcgCard = true;
        }

        // 获取环境列表
        $environments = Utils::getEnvironments();

        // 获取卡片在各环境中的禁限状态
        $limitStatus = [];
        foreach ($environments as $env) {
            $header = $env['header'];
            $limitStatus[$header] = $this->cardModel->getCardLimitStatus($cardId, $header);
        }

        // 是否允许对TCG卡发起禁卡投票
        $allowTcgCardVoting = defined('ALLOW_TCG_CARD_VOTING') ? ALLOW_TCG_CARD_VOTING : false;

        // 渲染视图
        include __DIR__ . '/../Views/layout.php';
        include __DIR__ . '/../Views/cards/detail.php';
        include __DIR__ . '/../Views/footer.php';
    }
}
