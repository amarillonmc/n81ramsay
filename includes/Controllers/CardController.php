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

        // 获取分页参数
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $perPage = isset($_GET['per_page']) ? intval($_GET['per_page']) : CARDS_PER_PAGE;

        // 验证每页显示数量
        $validPerPageOptions = [10, 20, 50, 100];
        if (!in_array($perPage, $validPerPageOptions)) {
            $perPage = CARDS_PER_PAGE; // 使用配置文件中的默认值
        }

        // 获取高级检索参数
        $advancedFilters = $this->getAdvancedSearchFilters();

        // 搜索卡片（带分页）
        $cards = [];
        $pagination = [
            'total' => 0,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => 0
        ];

        // 检查是否有任何搜索条件
        $hasAdvancedFilters = !empty(array_filter($advancedFilters, function($v) {
            return $v !== null && $v !== '' && $v !== [];
        }));

        if (!empty($keyword) || $hasAdvancedFilters) {
            $result = $this->cardModel->advancedSearchCards($keyword, $advancedFilters, $page, $perPage);
            $cards = $result['cards'];
            $pagination = [
                'total' => $result['total'],
                'page' => $result['page'],
                'per_page' => $result['per_page'],
                'total_pages' => $result['total_pages']
            ];
        }

        // 设置每页显示选项
        $perPageOptions = $validPerPageOptions;

        // 渲染视图
        include __DIR__ . '/../Views/layout.php';
        include __DIR__ . '/../Views/cards/search.php';
        include __DIR__ . '/../Views/footer.php';
    }

    /**
     * 获取高级检索过滤参数
     *
     * @return array 过滤参数数组
     */
    private function getAdvancedSearchFilters() {
        $filters = [];

        // 卡片类型 (monster, spell, trap)
        $cardType = isset($_GET['card_type']) ? trim($_GET['card_type']) : '';
        if (in_array($cardType, ['monster', 'spell', 'trap'])) {
            $filters['card_type'] = $cardType;
        }

        // 属性 (多选，逗号分隔的十六进制值)
        if (!empty($_GET['attribute'])) {
            $filters['attribute'] = $this->parseHexValues($_GET['attribute']);
        }

        // 魔法/陷阱类型 (多选)
        if (!empty($_GET['spell_trap_type'])) {
            $filters['spell_trap_type'] = $this->parseHexValues($_GET['spell_trap_type']);
        }

        // 种族 (多选)
        if (!empty($_GET['race'])) {
            $filters['race'] = $this->parseHexValues($_GET['race']);
        }

        // 其他项目 - 包含的类型 (多选)
        if (!empty($_GET['type_include'])) {
            $filters['type_include'] = $this->parseHexValues($_GET['type_include']);
        }

        // 其他项目 - 排除的类型 (多选)
        if (!empty($_GET['type_exclude'])) {
            $filters['type_exclude'] = $this->parseHexValues($_GET['type_exclude']);
        }

        // 类型逻辑 (and/or)
        $filters['type_logic'] = isset($_GET['type_logic']) && $_GET['type_logic'] === 'or' ? 'or' : 'and';

        // 等级/阶级 (多选)
        if (!empty($_GET['level'])) {
            $filters['level'] = $this->parseIntValues($_GET['level']);
        }

        // 灵摆刻度 (多选)
        if (!empty($_GET['scale'])) {
            $filters['scale'] = $this->parseIntValues($_GET['scale']);
        }

        // 连接值 (多选)
        if (!empty($_GET['link_value'])) {
            $filters['link_value'] = $this->parseIntValues($_GET['link_value']);
        }

        // 连接标记 (多选，十六进制值)
        if (!empty($_GET['link_markers'])) {
            $filters['link_markers'] = $this->parseHexValues($_GET['link_markers']);
        }

        // 连接标记逻辑 (and/or)
        $filters['link_logic'] = isset($_GET['link_logic']) && $_GET['link_logic'] === 'and' ? 'and' : 'or';

        // 攻击力范围
        if (isset($_GET['atk_min']) && $_GET['atk_min'] !== '') {
            $filters['atk_min'] = intval($_GET['atk_min']);
        }
        if (isset($_GET['atk_max']) && $_GET['atk_max'] !== '') {
            $filters['atk_max'] = intval($_GET['atk_max']);
        }

        // 守备力范围
        if (isset($_GET['def_min']) && $_GET['def_min'] !== '') {
            $filters['def_min'] = intval($_GET['def_min']);
        }
        if (isset($_GET['def_max']) && $_GET['def_max'] !== '') {
            $filters['def_max'] = intval($_GET['def_max']);
        }

        return $filters;
    }

    /**
     * 解析十六进制值字符串
     *
     * @param string $input 逗号分隔的十六进制值字符串
     * @return array 整数数组
     */
    private function parseHexValues($input) {
        $values = [];
        $parts = explode(',', $input);
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part !== '') {
                // 支持 0x 前缀和纯十六进制
                if (strpos($part, '0x') === 0) {
                    $values[] = hexdec($part);
                } else {
                    $values[] = hexdec($part);
                }
            }
        }
        return array_unique(array_filter($values, function($v) { return $v > 0; }));
    }

    /**
     * 解析整数值字符串
     *
     * @param string $input 逗号分隔的整数值字符串
     * @return array 整数数组
     */
    private function parseIntValues($input) {
        $values = [];
        $parts = explode(',', $input);
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part !== '' && is_numeric($part)) {
                $values[] = intval($part);
            }
        }
        return array_unique($values);
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
