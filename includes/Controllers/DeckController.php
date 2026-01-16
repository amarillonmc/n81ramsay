<?php
/**
 * 卡组控制器
 *
 * 处理卡组分享相关的请求
 */
class DeckController {
    /**
     * 卡组模型
     * @var Deck
     */
    private $deckModel;

    /**
     * 卡片模型
     * @var Card
     */
    private $cardModel;

    /**
     * 构造函数
     */
    public function __construct() {
        $this->deckModel = new Deck();
        $this->cardModel = new Card();
    }

    /**
     * 卡组列表
     */
    public function index() {
        // 检查功能是否启用
        if (!defined('DECK_SHARING_ENABLED') || !DECK_SHARING_ENABLED) {
            header('Location: ' . BASE_URL);
            exit;
        }

        // 获取分页参数
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $perPage = defined('DECKS_PER_PAGE') ? DECKS_PER_PAGE : 20;

        // 获取卡组列表
        $result = $this->deckModel->getDeckList($page, $perPage);

        // 检查用户是否可以上传及原因
        $identifier = Utils::generateVoterIdentifier(Utils::getClientIp(), session_id());
        $auth = Auth::getInstance();
        $isAdmin = $auth->isLoggedIn() && $auth->hasPermission(1);
        $uploadPermission = $this->deckModel->getUploadPermissionInfo($identifier, $isAdmin);
        $canUpload = $uploadPermission['can_upload'];
        $uploadDeniedReason = $uploadPermission['reason'];

        // 渲染视图
        $decks = $result['decks'];
        $pagination = [
            'total' => $result['total'],
            'page' => $result['page'],
            'per_page' => $result['per_page'],
            'total_pages' => $result['total_pages']
        ];

        include __DIR__ . '/../Views/layout.php';
        include __DIR__ . '/../Views/decks/index.php';
        include __DIR__ . '/../Views/footer.php';
    }

    /**
     * 创建卡组页面
     */
    public function create() {
        // 检查功能是否启用
        if (!defined('DECK_SHARING_ENABLED') || !DECK_SHARING_ENABLED) {
            header('Location: ' . BASE_URL);
            exit;
        }

        // 检查上传权限
        $identifier = Utils::generateVoterIdentifier(Utils::getClientIp(), session_id());
        $auth = Auth::getInstance();
        $isAdmin = $auth->isLoggedIn() && $auth->hasPermission(1);
        
        if (!$this->deckModel->canUploadDeck($identifier, $isAdmin)) {
            $_SESSION['error_message'] = '您没有权限上传卡组';
            header('Location: ' . BASE_URL . '?controller=deck');
            exit;
        }

        $errors = [];
        
        // 渲染视图
        include __DIR__ . '/../Views/layout.php';
        include __DIR__ . '/../Views/decks/create.php';
        include __DIR__ . '/../Views/footer.php';
    }

    /**
     * 保存卡组
     */
    public function store() {
        // 检查功能是否启用
        if (!defined('DECK_SHARING_ENABLED') || !DECK_SHARING_ENABLED) {
            header('Location: ' . BASE_URL);
            exit;
        }

        // 验证请求方法
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '?controller=deck&action=create');
            exit;
        }

        // 检查上传权限
        $identifier = Utils::generateVoterIdentifier(Utils::getClientIp(), session_id());
        $auth = Auth::getInstance();
        $isAdmin = $auth->isLoggedIn() && $auth->hasPermission(1);
        
        if (!$this->deckModel->canUploadDeck($identifier, $isAdmin)) {
            $_SESSION['error_message'] = '您没有权限上传卡组';
            header('Location: ' . BASE_URL . '?controller=deck');
            exit;
        }

        $errors = [];

        // 获取卡组名称
        $deckName = isset($_POST['deck_name']) ? trim($_POST['deck_name']) : '';
        if (empty($deckName)) {
            $errors[] = '请输入卡组名称';
        }

        // 获取YDK内容
        $ydkContent = '';
        
        // 优先处理文件上传
        if (isset($_FILES['ydk_file']) && $_FILES['ydk_file']['error'] === UPLOAD_ERR_OK) {
            $ydkContent = file_get_contents($_FILES['ydk_file']['tmp_name']);
        } elseif (!empty($_POST['ydk_content'])) {
            $ydkContent = $_POST['ydk_content'];
        }

        if (empty($ydkContent)) {
            $errors[] = '请上传YDK文件或粘贴YDK内容';
        }

        // 解析YDK内容
        $parsedDeck = $this->deckModel->parseYdkContent($ydkContent);
        
        if (!$parsedDeck['is_valid']) {
            $errors[] = '卡组格式无效（主卡组需要40-60张，额外卡组最多15张，副卡组最多15张）';
        }

        if (!empty($errors)) {
            $_SESSION['error_message'] = implode('<br>', $errors);
            header('Location: ' . BASE_URL . '?controller=deck&action=create');
            exit;
        }

        // 保存卡组
        $uploaderName = $isAdmin ? $auth->getCurrentUsername() : $identifier;

        $deckData = [
            'name' => $deckName,
            'main_deck' => $parsedDeck['main'],
            'extra_deck' => $parsedDeck['extra'],
            'side_deck' => $parsedDeck['side'],
            'uploader_id' => $identifier,
            'uploader_name' => $uploaderName,
            'is_admin_deck' => $isAdmin ? 1 : 0
        ];

        // 如果是管理员批量上传
        if ($isAdmin && !empty($_POST['deck_group'])) {
            $deckData['deck_group'] = $_POST['deck_group'];
        }

        $deckId = $this->deckModel->createDeck($deckData);

        if ($deckId) {
            $_SESSION['success_message'] = '卡组上传成功';
            header('Location: ' . BASE_URL . '?controller=deck&action=detail&id=' . $deckId);
        } else {
            $_SESSION['error_message'] = '卡组上传失败';
            header('Location: ' . BASE_URL . '?controller=deck&action=create');
        }
        exit;
    }

    /**
     * 批量保存卡组（管理员）
     */
    public function storeBatch() {
        // 检查功能是否启用
        if (!defined('DECK_SHARING_ENABLED') || !DECK_SHARING_ENABLED) {
            header('Location: ' . BASE_URL);
            exit;
        }

        // 验证请求方法
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '?controller=deck&action=create');
            exit;
        }

        // 检查管理员权限
        $auth = Auth::getInstance();
        if (!$auth->isLoggedIn() || !$auth->hasPermission(1)) {
            $_SESSION['error_message'] = '需要管理员权限';
            header('Location: ' . BASE_URL . '?controller=deck');
            exit;
        }

        $identifier = Utils::generateVoterIdentifier(Utils::getClientIp(), session_id());
        $batchContent = isset($_POST['batch_content']) ? $_POST['batch_content'] : '';

        if (empty($batchContent)) {
            $_SESSION['error_message'] = '请输入卡组内容';
            header('Location: ' . BASE_URL . '?controller=deck&action=create');
            exit;
        }

        // 生成deck_group标识和时间戳
        $deckGroup = 'batch_' . time() . '_' . Utils::generateRandomString(8);
        $timestamp = date('Y-m-d');

        // 分割多个卡组（使用#created或#main作为分隔符）
        $deckBlocks = preg_split('/(#created|#main)/i', $batchContent, -1, PREG_SPLIT_DELIM_CAPTURE);

        $createdCount = 0;
        $currentDeck = '';

        for ($i = 0; $i < count($deckBlocks); $i++) {
            $block = $deckBlocks[$i];

            if (preg_match('/^#(created|main)$/i', $block)) {
                // 如果有之前的卡组内容，尝试保存
                if (!empty($currentDeck)) {
                    $this->saveSingleDeck($currentDeck, $identifier, $auth->getCurrentUsername(), $deckGroup, $createdCount, $timestamp);
                }
                $currentDeck = $block;
            } else {
                $currentDeck .= $block;
            }
        }

        // 保存最后一个卡组
        if (!empty($currentDeck)) {
            $this->saveSingleDeck($currentDeck, $identifier, $auth->getCurrentUsername(), $deckGroup, $createdCount, $timestamp);
        }

        if ($createdCount > 0) {
            $_SESSION['success_message'] = "成功创建 {$createdCount} 个卡组";
        } else {
            $_SESSION['error_message'] = '没有成功创建任何卡组';
        }

        header('Location: ' . BASE_URL . '?controller=deck');
        exit;
    }

    /**
     * 保存单个卡组（辅助方法）
     */
    private function saveSingleDeck($content, $identifier, $uploaderName, $deckGroup, &$count, $timestamp) {
        $parsed = $this->deckModel->parseYdkContent($content);

        if ($parsed['is_valid']) {
            // 卡组名称包含时间戳
            $deckName = '更新卡展示 ' . $timestamp . ' #' . ($count + 1);

            $deckData = [
                'name' => $deckName,
                'main_deck' => $parsed['main'],
                'extra_deck' => $parsed['extra'],
                'side_deck' => $parsed['side'],
                'uploader_id' => $identifier,
                'uploader_name' => $uploaderName,
                'is_admin_deck' => 1,
                'deck_group' => $deckGroup
            ];

            if ($this->deckModel->createDeck($deckData)) {
                $count++;
            }
        }
    }

    /**
     * 卡组详情页
     */
    public function detail() {
        // 检查功能是否启用
        if (!defined('DECK_SHARING_ENABLED') || !DECK_SHARING_ENABLED) {
            header('Location: ' . BASE_URL);
            exit;
        }

        $deckId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        $deck = $this->deckModel->getDeckById($deckId);

        if (!$deck) {
            $_SESSION['error_message'] = '卡组不存在';
            header('Location: ' . BASE_URL . '?controller=deck');
            exit;
        }

        // 获取相关卡组（如果是批量上传的）
        $relatedDecks = [];
        if (!empty($deck['deck_group'])) {
            $relatedDecks = $this->deckModel->getDecksByGroup($deck['deck_group']);
        }

        // 获取评论
        $comments = $this->deckModel->getComments($deckId);

        // 获取卡片信息
        $allCardIds = array_merge($deck['main_deck'], $deck['extra_deck'], $deck['side_deck']);
        $allCardIds = array_unique($allCardIds);
        $cardInfoMap = [];

        foreach ($allCardIds as $cardId) {
            $card = $this->cardModel->getCardById($cardId);
            if ($card) {
                $cardInfoMap[$cardId] = $card;
            }
        }

        // 检查删除权限
        $identifier = Utils::generateVoterIdentifier(Utils::getClientIp(), session_id());
        $auth = Auth::getInstance();
        $isAdmin = $auth->isLoggedIn() && $auth->hasPermission(1);
        $canDelete = $this->deckModel->canDeleteDeck($deckId, $identifier, $isAdmin);

        // 渲染视图
        include __DIR__ . '/../Views/layout.php';
        include __DIR__ . '/../Views/decks/detail.php';
        include __DIR__ . '/../Views/footer.php';
    }

    /**
     * 删除卡组
     */
    public function delete() {
        // 检查功能是否启用
        if (!defined('DECK_SHARING_ENABLED') || !DECK_SHARING_ENABLED) {
            header('Location: ' . BASE_URL);
            exit;
        }

        $deckId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        // 检查删除权限
        $identifier = Utils::generateVoterIdentifier(Utils::getClientIp(), session_id());
        $auth = Auth::getInstance();
        $isAdmin = $auth->isLoggedIn() && $auth->hasPermission(1);

        if (!$this->deckModel->canDeleteDeck($deckId, $identifier, $isAdmin)) {
            $_SESSION['error_message'] = '您没有权限删除此卡组';
            header('Location: ' . BASE_URL . '?controller=deck');
            exit;
        }

        if ($this->deckModel->deleteDeck($deckId)) {
            $_SESSION['success_message'] = '卡组删除成功';
        } else {
            $_SESSION['error_message'] = '卡组删除失败';
        }

        header('Location: ' . BASE_URL . '?controller=deck');
        exit;
    }

    /**
     * 添加评论
     */
    public function comment() {
        // 检查功能是否启用
        if (!defined('DECK_SHARING_ENABLED') || !DECK_SHARING_ENABLED) {
            header('Location: ' . BASE_URL);
            exit;
        }

        // 验证请求方法
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '?controller=deck');
            exit;
        }

        $deckId = isset($_POST['deck_id']) ? (int)$_POST['deck_id'] : 0;
        $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

        if (empty($comment)) {
            $_SESSION['error_message'] = '请输入评论内容';
            header('Location: ' . BASE_URL . '?controller=deck&action=detail&id=' . $deckId);
            exit;
        }

        $identifier = Utils::generateVoterIdentifier(Utils::getClientIp(), session_id());
        $auth = Auth::getInstance();
        $userName = $auth->isLoggedIn() ? $auth->getCurrentUsername() : $identifier;

        if ($this->deckModel->addComment($deckId, $identifier, $userName, $comment)) {
            $_SESSION['success_message'] = '评论成功';
        } else {
            $_SESSION['error_message'] = '评论失败';
        }

        header('Location: ' . BASE_URL . '?controller=deck&action=detail&id=' . $deckId);
        exit;
    }

    /**
     * 下载卡组
     */
    public function download() {
        // 检查功能是否启用
        if (!defined('DECK_SHARING_ENABLED') || !DECK_SHARING_ENABLED) {
            header('Location: ' . BASE_URL);
            exit;
        }

        $deckId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        $deck = $this->deckModel->getDeckById($deckId);

        if (!$deck) {
            $_SESSION['error_message'] = '卡组不存在';
            header('Location: ' . BASE_URL . '?controller=deck');
            exit;
        }

        // 生成YDK内容
        $ydkContent = $this->deckModel->generateYdkContent($deck);

        // 设置下载头
        $filename = preg_replace('/[^a-zA-Z0-9\x{4e00}-\x{9fa5}_-]/u', '', $deck['name']) . '.ydk';
        if (empty($filename) || $filename === '.ydk') {
            $filename = 'deck_' . $deckId . '.ydk';
        }

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($ydkContent));

        echo $ydkContent;
        exit;
    }

    /**
     * 获取卡片图片路径（支持TCG卡图）
     *
     * @param int $cardId 卡片ID
     * @param array|null $cardInfo 卡片信息（可选）
     * @return string 图片路径
     */
    public function getCardImageForDeck($cardId, $cardInfo = null) {
        // 如果是DIY卡片，使用默认图片路径
        if ($cardInfo && isset($cardInfo['image_path'])) {
            return $cardInfo['image_path'];
        }

        // 检查TCG卡图路径配置
        $tcgImagePath = defined('TCG_CARD_IMAGE_PATH') ? TCG_CARD_IMAGE_PATH : '';

        if (!empty($tcgImagePath) && is_dir($tcgImagePath)) {
            // 尝试从TCG卡图目录读取
            $imagePaths = [
                $tcgImagePath . '/' . $cardId . '.jpg',
                $tcgImagePath . '/' . $cardId . '.png',
                $tcgImagePath . '/c' . $cardId . '.jpg',
                $tcgImagePath . '/c' . $cardId . '.png'
            ];

            foreach ($imagePaths as $path) {
                if (file_exists($path)) {
                    // 返回相对于web根目录的路径
                    return BASE_URL . 'tcg_pics/' . basename($path);
                }
            }
        }

        // 使用默认卡背
        return BASE_URL . 'assets/images/card_back.jpg';
    }
}
