<?php
/**
 * 召唤词控制器
 *
 * 处理召唤词相关的请求
 */
class DialogueController {
    /**
     * 召唤词模型
     * @var DialogueModel
     */
    private $dialogueModel;

    /**
     * 用户模型
     * @var User
     */
    private $userModel;

    /**
     * 卡片解析器
     * @var CardParser
     */
    private $cardParser;

    /**
     * 构造函数
     */
    public function __construct() {
        $this->dialogueModel = new DialogueModel();
        $this->userModel = new User();
        $this->cardParser = CardParser::getInstance();
    }

    /**
     * 召唤词一览页面
     */
    public function index() {
        try {
            // 读取召唤词文件
            $dialogues = $this->dialogueModel->loadDialogues();

            // 获取卡片信息
            $dialogueCards = [];
            foreach ($dialogues as $cardId => $dialogueList) {
                $card = $this->cardParser->getCardById($cardId);
                if ($card) {
                    $dialogueCards[] = [
                        'card' => $card,
                        'dialogues' => $dialogueList
                    ];
                }
            }

            // 获取消息
            $message = isset($_GET['message']) ? $_GET['message'] : '';
            $error = isset($_GET['error']) ? $_GET['error'] : '';

            // 渲染视图
            include __DIR__ . '/../Views/layout.php';
            include __DIR__ . '/../Views/dialogues/index.php';
            include __DIR__ . '/../Views/footer.php';
        } catch (Exception $e) {
            // 如果出现错误，显示错误信息
            echo "<h1>召唤词一览</h1>";
            echo "<div style='color: red; padding: 20px; border: 1px solid red; margin: 20px;'>";
            echo "<h2>错误</h2>";
            echo "<p>错误信息: " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p>文件: " . htmlspecialchars($e->getFile()) . "</p>";
            echo "<p>行号: " . $e->getLine() . "</p>";
            echo "<h3>堆栈跟踪:</h3>";
            echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
            echo "</div>";
        }
    }

    /**
     * 召唤词投稿页面
     */
    public function submit() {
        // 获取消息
        $message = isset($_GET['message']) ? $_GET['message'] : '';
        $error = isset($_GET['error']) ? $_GET['error'] : '';

        // 渲染视图
        include __DIR__ . '/../Views/layout.php';
        include __DIR__ . '/../Views/dialogues/submit.php';
        include __DIR__ . '/../Views/footer.php';
    }

    /**
     * 处理召唤词投稿
     */
    public function submitDialogue() {
        // 检查是否是POST请求
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '?controller=dialogue&action=submit');
            exit;
        }

        // 获取表单数据
        $cardId = isset($_POST['card_id']) ? trim($_POST['card_id']) : '';
        $dialogue = isset($_POST['dialogue']) ? trim($_POST['dialogue']) : '';
        $authorId = isset($_POST['author_id']) ? trim($_POST['author_id']) : '';
        $userId = isset($_POST['user_id']) ? trim($_POST['user_id']) : '';

        // 验证输入
        if (empty($cardId) || empty($dialogue) || empty($authorId) || empty($userId)) {
            header('Location: ' . BASE_URL . '?controller=dialogue&action=submit&error=' . urlencode('所有字段都是必填的'));
            exit;
        }

        // 验证卡片ID格式
        if (!is_numeric($cardId)) {
            header('Location: ' . BASE_URL . '?controller=dialogue&action=submit&error=' . urlencode('卡片ID必须是数字'));
            exit;
        }

        // 验证卡片是否存在
        $card = $this->cardParser->getCardById($cardId);
        if (!$card) {
            header('Location: ' . BASE_URL . '?controller=dialogue&action=submit&error=' . urlencode('找不到指定的卡片'));
            exit;
        }

        // 检查用户待审核投稿数量
        $pendingCount = $this->dialogueModel->getUserPendingCount($userId);
        if ($pendingCount >= MAX_PENDING_DIALOGUES_PER_USER) {
            header('Location: ' . BASE_URL . '?controller=dialogue&action=submit&error=' . urlencode('您的待审核召唤词数量已达上限(' . MAX_PENDING_DIALOGUES_PER_USER . '个)'));
            exit;
        }

        // 验证作者信息
        $validation = $this->dialogueModel->validateAuthor($authorId, $cardId, DIALOGUE_SUBMISSION_STRICTNESS);
        if (!$validation['valid']) {
            header('Location: ' . BASE_URL . '?controller=dialogue&action=submit&error=' . urlencode($validation['message']));
            exit;
        }

        // 提交召唤词
        $submissionId = $this->dialogueModel->submitDialogue($userId, $cardId, $dialogue, $authorId);
        if ($submissionId) {
            $message = '召唤词投稿成功，等待管理员审核';
            if ($validation['warning']) {
                $message .= '（注意：卡片前缀与作者不匹配，已标记给管理员）';
            }
            header('Location: ' . BASE_URL . '?controller=dialogue&action=submit&message=' . urlencode($message));
        } else {
            header('Location: ' . BASE_URL . '?controller=dialogue&action=submit&error=' . urlencode('投稿失败，请稍后重试'));
        }
        exit;
    }

    /**
     * 管理员召唤词管理页面
     */
    public function admin() {
        try {
            // 要求管理员权限（等级1以上）
            $this->userModel->requirePermission(1);

            // 获取待审核的投稿
            $pendingSubmissions = $this->dialogueModel->getPendingSubmissions();

            // 为每个投稿添加卡片信息和验证状态
            foreach ($pendingSubmissions as &$submission) {
                $submission['card'] = $this->cardParser->getCardById($submission['card_id']);
                $validation = $this->dialogueModel->validateAuthor($submission['author_id'], $submission['card_id'], 1);
                $submission['has_warning'] = $validation['warning'];
                // 由于没有users表，username就使用user_id
                $submission['username'] = $submission['user_id'];
            }
            // 解除引用，防止后续操作影响数组
            unset($submission);

            // 读取现有召唤词
            $dialogues = $this->dialogueModel->loadDialogues();

            // 获取卡片信息
            $dialogueCards = [];
            foreach ($dialogues as $cardId => $dialogueList) {
                $card = $this->cardParser->getCardById($cardId);
                if ($card) {
                    $dialogueCards[] = [
                        'card_id' => $cardId,
                        'card' => $card,
                        'dialogues' => $dialogueList
                    ];
                }
            }

            // 获取消息
            $message = isset($_GET['message']) ? $_GET['message'] : '';
            $error = isset($_GET['error']) ? $_GET['error'] : '';

            // 渲染视图
            include __DIR__ . '/../Views/layout.php';
            include __DIR__ . '/../Views/dialogues/admin.php';
            include __DIR__ . '/../Views/footer.php';
        } catch (Exception $e) {
            // 如果出现错误，显示错误信息
            echo "<h1>召唤词管理</h1>";
            echo "<div style='color: red; padding: 20px; border: 1px solid red; margin: 20px;'>";
            echo "<h2>错误</h2>";
            echo "<p>错误信息: " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p>文件: " . htmlspecialchars($e->getFile()) . "</p>";
            echo "<p>行号: " . $e->getLine() . "</p>";
            echo "<h3>堆栈跟踪:</h3>";
            echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
            echo "</div>";
        }
    }

    /**
     * 审核召唤词投稿
     */
    public function reviewSubmission() {
        // 要求管理员权限（等级1以上）
        $this->userModel->requirePermission(1);

        // 检查是否是POST请求
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '?controller=dialogue&action=admin');
            exit;
        }

        // 获取表单数据
        $submissionId = isset($_POST['submission_id']) ? (int)$_POST['submission_id'] : 0;
        $action = isset($_POST['action']) ? $_POST['action'] : '';
        $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';

        // 验证输入
        if ($submissionId <= 0 || !in_array($action, ['accept', 'reject'])) {
            header('Location: ' . BASE_URL . '?controller=dialogue&action=admin&error=' . urlencode('无效的操作'));
            exit;
        }

        if ($action === 'reject' && empty($reason)) {
            header('Location: ' . BASE_URL . '?controller=dialogue&action=admin&error=' . urlencode('拒绝投稿时必须提供原因'));
            exit;
        }

        // 获取当前管理员
        $currentUser = $this->userModel->getCurrentUser();
        $adminId = $currentUser['username'];

        // 审核投稿
        $success = $this->dialogueModel->reviewSubmission($submissionId, $action, $adminId, $reason);

        if ($success) {
            $message = $action === 'accept' ? '召唤词已接受并添加到文件' : '召唤词已拒绝';
            header('Location: ' . BASE_URL . '?controller=dialogue&action=admin&message=' . urlencode($message));
        } else {
            header('Location: ' . BASE_URL . '?controller=dialogue&action=admin&error=' . urlencode('操作失败'));
        }
        exit;
    }

    /**
     * 删除召唤词投稿
     */
    public function deleteSubmission() {
        // 要求管理员权限（等级1以上）
        $this->userModel->requirePermission(1);

        // 检查是否是POST请求
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '?controller=dialogue&action=admin');
            exit;
        }

        // 获取投稿ID
        $submissionId = isset($_POST['submission_id']) ? (int)$_POST['submission_id'] : 0;

        if ($submissionId <= 0) {
            header('Location: ' . BASE_URL . '?controller=dialogue&action=admin&error=' . urlencode('无效的投稿ID'));
            exit;
        }

        // 删除投稿
        $success = $this->dialogueModel->deleteSubmission($submissionId);

        if ($success) {
            header('Location: ' . BASE_URL . '?controller=dialogue&action=admin&message=' . urlencode('投稿已删除'));
        } else {
            header('Location: ' . BASE_URL . '?controller=dialogue&action=admin&error=' . urlencode('删除失败'));
        }
        exit;
    }

    /**
     * 添加召唤词
     */
    public function addDialogue() {
        // 要求管理员权限（等级1以上）
        $this->userModel->requirePermission(1);

        // 检查是否是POST请求
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '?controller=dialogue&action=admin');
            exit;
        }

        // 获取表单数据
        $cardId = isset($_POST['card_id']) ? trim($_POST['card_id']) : '';
        $dialogue = isset($_POST['dialogue']) ? trim($_POST['dialogue']) : '';

        // 验证输入
        if (empty($cardId) || empty($dialogue)) {
            header('Location: ' . BASE_URL . '?controller=dialogue&action=admin&error=' . urlencode('卡片ID和召唤词内容都是必填的'));
            exit;
        }

        // 验证卡片ID格式
        if (!is_numeric($cardId)) {
            header('Location: ' . BASE_URL . '?controller=dialogue&action=admin&error=' . urlencode('卡片ID必须是数字'));
            exit;
        }

        // 验证卡片是否存在
        $card = $this->cardParser->getCardById($cardId);
        if (!$card) {
            header('Location: ' . BASE_URL . '?controller=dialogue&action=admin&error=' . urlencode('找不到指定的卡片'));
            exit;
        }

        // 读取现有召唤词
        $dialogues = $this->dialogueModel->loadDialogues();

        // 添加新召唤词
        $dialogues[$cardId] = [$dialogue];

        // 保存召唤词
        $saveResult = $this->dialogueModel->saveDialogues($dialogues);
        if ($saveResult === true) {
            header('Location: ' . BASE_URL . '?controller=dialogue&action=admin&message=' . urlencode('成功添加召唤词'));
        } else {
            header('Location: ' . BASE_URL . '?controller=dialogue&action=admin&error=' . urlencode('保存失败: ' . $saveResult));
        }
        exit;
    }

    /**
     * 编辑召唤词
     */
    public function editDialogue() {
        // 要求管理员权限（等级1以上）
        $this->userModel->requirePermission(1);

        // 检查是否是POST请求
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '?controller=dialogue&action=admin');
            exit;
        }

        // 获取表单数据
        $cardId = isset($_POST['card_id']) ? trim($_POST['card_id']) : '';
        $dialogue = isset($_POST['dialogue']) ? trim($_POST['dialogue']) : '';

        // 验证输入
        if (empty($cardId) || empty($dialogue)) {
            header('Location: ' . BASE_URL . '?controller=dialogue&action=admin&error=' . urlencode('卡片ID和召唤词内容都是必填的'));
            exit;
        }

        // 读取现有召唤词
        $dialogues = $this->dialogueModel->loadDialogues();

        // 检查卡片是否存在召唤词
        if (!isset($dialogues[$cardId])) {
            header('Location: ' . BASE_URL . '?controller=dialogue&action=admin&error=' . urlencode('该卡片没有召唤词'));
            exit;
        }

        // 更新召唤词
        $dialogues[$cardId] = [$dialogue];

        // 保存召唤词
        $saveResult = $this->dialogueModel->saveDialogues($dialogues);
        if ($saveResult === true) {
            header('Location: ' . BASE_URL . '?controller=dialogue&action=admin&message=' . urlencode('成功更新召唤词'));
        } else {
            header('Location: ' . BASE_URL . '?controller=dialogue&action=admin&error=' . urlencode('保存失败: ' . $saveResult));
        }
        exit;
    }

    /**
     * 删除召唤词
     */
    public function deleteDialogue() {
        // 要求管理员权限（等级1以上）
        $this->userModel->requirePermission(1);

        // 检查是否是POST请求
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '?controller=dialogue&action=admin');
            exit;
        }

        // 获取卡片ID
        $cardId = isset($_POST['card_id']) ? trim($_POST['card_id']) : '';

        // 验证输入
        if (empty($cardId)) {
            header('Location: ' . BASE_URL . '?controller=dialogue&action=admin&error=' . urlencode('卡片ID是必填的'));
            exit;
        }

        // 读取现有召唤词
        $dialogues = $this->dialogueModel->loadDialogues();

        // 检查卡片是否存在召唤词
        if (!isset($dialogues[$cardId])) {
            header('Location: ' . BASE_URL . '?controller=dialogue&action=admin&error=' . urlencode('该卡片没有召唤词'));
            exit;
        }

        // 删除召唤词
        unset($dialogues[$cardId]);

        // 保存召唤词
        $saveResult = $this->dialogueModel->saveDialogues($dialogues);
        if ($saveResult === true) {
            header('Location: ' . BASE_URL . '?controller=dialogue&action=admin&message=' . urlencode('成功删除召唤词'));
        } else {
            header('Location: ' . BASE_URL . '?controller=dialogue&action=admin&error=' . urlencode('保存失败: ' . $saveResult));
        }
        exit;
    }
}
