<?php
/**
 * 管理员控制器
 *
 * 处理管理员相关的请求
 */
class AdminController {
    /**
     * 用户模型
     * @var User
     */
    private $userModel;

    /**
     * 投票模型
     * @var Vote
     */
    private $voteModel;

    /**
     * 卡片模型
     * @var Card
     */
    private $cardModel;

    /**
     * 作者映射模型
     * @var AuthorMapping
     */
    private $authorMappingModel;

    /**
     * 卡片文本匹配规则模型
     * @var CardMatchRule
     */
    private $cardMatchRuleModel;

    /**
     * 投票者封禁模型
     * @var VoterBan
     */
    private $voterBanModel;

    /**
     * 构造函数
     */
    public function __construct() {
        $this->userModel = new User();
        $this->voteModel = new Vote();
        $this->cardModel = new Card();
        $this->authorMappingModel = new AuthorMapping();
        $this->cardMatchRuleModel = new CardMatchRule();
        $this->voterBanModel = new VoterBan();
    }

    /**
     * 登录页面
     */
    public function login() {
        // 如果已经登录，则重定向到管理页面
        if ($this->userModel->isLoggedIn()) {
            header('Location: ' . BASE_URL . '?controller=admin&action=votes');
            exit;
        }

        // 检查是否是POST请求
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requirePostCsrf('admin_login');
            // 获取表单数据
            $username = isset($_POST['username']) ? trim($_POST['username']) : '';
            $password = isset($_POST['password']) ? $_POST['password'] : '';

            // 验证数据
            $errors = [];

            if (empty($username)) {
                $errors[] = '请输入用户名';
            }

            if (empty($password)) {
                $errors[] = '请输入密码';
            }

            // 如果没有错误，则尝试登录
            if (empty($errors)) {
                $result = $this->userModel->login($username, $password);

                if ($result) {
                    // 获取重定向URL
                    $redirectUrl = isset($_GET['redirect']) ? $_GET['redirect'] : BASE_URL . '?controller=admin&action=votes';

                    // 重定向到管理页面
                    header('Location: ' . $redirectUrl);
                    exit;
                } else {
                    $errors[] = '用户名或密码错误';
                }
            }

            // 如果有错误，则显示错误信息
            if (!empty($errors)) {
                include __DIR__ . '/../Views/layout.php';
                include __DIR__ . '/../Views/admin/login.php';
                include __DIR__ . '/../Views/footer.php';
                return;
            }
        }

        // 渲染视图
        include __DIR__ . '/../Views/layout.php';
        include __DIR__ . '/../Views/admin/login.php';
        include __DIR__ . '/../Views/footer.php';
    }

    /**
     * 注销
     */
    public function logout() {
        $this->userModel->logout();

        // 重定向到首页
        header('Location: ' . BASE_URL);
        exit;
    }

    /**
     * 投票管理
     */
    public function votes() {
        // 要求管理员权限
        $this->userModel->requirePermission(1);

        // 获取页码
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($page < 1) {
            $page = 1;
        }

        // 获取投票列表
        $votes = $this->voteModel->getAllVotes(false, $page, VOTES_PER_PAGE);
        $totalVotes = $this->voteModel->getVoteCount(false);

        // 计算总页数
        $totalPages = ceil($totalVotes / VOTES_PER_PAGE);

        // 处理投票数据
        Utils::checkMemoryUsage('管理员投票列表处理开始');

        foreach ($votes as &$vote) {
            // 获取卡片信息
            $card = $this->cardModel->getCardById($vote['card_id']);
            $vote['card'] = $card;

            // 获取环境信息
            $environment = Utils::getEnvironmentById($vote['environment_id']);
            $vote['environment'] = $environment;

            // 获取投票统计
            $vote['stats'] = $this->voteModel->getVoteStats($vote['id']);

            // 获取投票记录
            $vote['records'] = $this->voteModel->getVoteRecords($vote['id']);

            // 检查内存使用情况
            if (Utils::checkMemoryUsage('管理员投票数据处理', 2048)) {
                Utils::forceGarbageCollection('管理员投票列表处理');
            }
        }
        // 解除引用，防止后续操作影响数组
        unset($vote);

        Utils::checkMemoryUsage('管理员投票列表处理完成');

        // 渲染视图
        include __DIR__ . '/../Views/layout.php';
        include __DIR__ . '/../Views/admin/votes.php';
        include __DIR__ . '/../Views/footer.php';
    }

    /**
     * 关闭投票
     */
    public function closeVote() {
        // 要求管理员权限
        $this->userModel->requirePermission(1);
        $this->requirePostCsrf('admin_close_vote');

        // 检查是否是POST请求
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // 获取表单数据
            $voteId = isset($_POST['vote_id']) ? (int)$_POST['vote_id'] : 0;

            // 关闭投票
            $result = $this->voteModel->closeVote($voteId);

            // 重定向到投票管理页面
            header('Location: ' . BASE_URL . '?controller=admin&action=votes');
            exit;
        }

        // 如果不是POST请求，则重定向到投票管理页面
        header('Location: ' . BASE_URL . '?controller=admin&action=votes');
        exit;
    }

    /**
     * 禁卡表管理
     *
     * 重定向到BanlistController的index方法
     */
    public function banlist() {
        // 要求管理员权限
        $this->userModel->requirePermission(1);

        // 创建BanlistController实例
        $banlistController = new BanlistController();

        // 调用index方法
        $banlistController->index();
    }

    /**
     * 生成禁卡表
     *
     * 重定向到BanlistController的generate方法
     */
    public function generate() {
        // 要求管理员权限
        $this->userModel->requirePermission(1);

        // 创建BanlistController实例
        $banlistController = new BanlistController();

        // 调用generate方法
        $banlistController->generate();
    }

    /**
     * 重置投票
     *
     * 重定向到BanlistController的reset方法
     */
    public function reset() {
        // 要求管理员权限
        $this->userModel->requirePermission(2);

        // 创建BanlistController实例
        $banlistController = new BanlistController();

        // 调用reset方法
        $banlistController->reset();
    }

    /**
     * 更新禁卡表
     *
     * 重定向到BanlistController的update方法
     */
    public function update() {
        // 要求管理员权限
        $this->userModel->requirePermission(2);

        // 创建BanlistController实例
        $banlistController = new BanlistController();

        // 调用update方法
        $banlistController->update();
    }

    /**
     * 作者管理页面
     */
    public function authors() {
        // 要求管理员权限（等级2以上）
        $this->userModel->requirePermission(2);

        $message = $this->getFormString($_GET, 'message');
        $errors = [];
        $error = $this->getFormString($_GET, 'error');
        if ($error !== '') {
            $errors[] = $error;
        }

        $this->renderAuthorManagement($message, $errors);
    }

    /**
     * 识别作者
     */
    public function identifyAuthors() {
        // 要求管理员权限（等级255以上）
        $this->userModel->requirePermission(255);
        $this->requirePostCsrf('admin_identify_authors');

        // 检查是否是POST请求
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // 从strings.conf文件中识别作者并导入
            $count = $this->authorMappingModel->identifyAuthorsFromStringsConf();

            // 设置消息
            $message = '成功识别并导入' . $count . '个作者';

            // 重定向到作者管理页面
            header('Location: ' . BASE_URL . '?controller=admin&action=authors&message=' . urlencode($message));
            exit;
        }

        // 如果不是POST请求，则重定向到作者管理页面
        header('Location: ' . BASE_URL . '?controller=admin&action=authors');
        exit;
    }

    /**
     * 添加作者映射
     *
     * @return void
     */
    public function addAuthor() {
        // 要求管理员权限（等级2以上）
        $this->userModel->requirePermission(2);
        $this->requirePostCsrf('admin_add_author');

        $authorForm = $this->getAuthorMappingFormData($_POST);
        $errors = $this->validateAuthorMappingFormData($authorForm);

        if (empty($errors)) {
            $mappingId = $this->authorMappingModel->addAuthorMapping(
                $authorForm['card_prefix'],
                $authorForm['author_name'],
                $authorForm['alias'],
                $authorForm['contact'],
                $authorForm['notes'],
                $this->nullableInteger($authorForm['card_id_length']),
                $this->nullableInteger($authorForm['card_id_start']),
                $this->nullableInteger($authorForm['card_id_end']),
                (int)$authorForm['priority']
            );

            if ($mappingId !== false) {
                header('Location: ' . BASE_URL . '?controller=admin&action=authors&message=' . urlencode('成功添加作者映射'));
                exit;
            }
            $errors[] = '添加作者映射失败';
        }

        $this->renderAuthorManagement('', $errors, $authorForm);
    }

    /**
     * 删除作者映射
     *
     * @return void
     */
    public function deleteAuthor() {
        // 要求管理员权限（等级2以上）
        $this->userModel->requirePermission(2);
        $this->requirePostCsrf('admin_delete_author');

        $mappingId = $this->getPositiveIntegerFormValue($_POST, 'mapping_id');
        $deleted = $mappingId > 0
            ? $this->authorMappingModel->deleteAuthorMappingById($mappingId)
            : false;

        // 兼容升级前仍停留在浏览器中的旧表单；新页面始终提交稳定ID。
        if (!$deleted && $mappingId <= 0) {
            $legacyPrefix = $this->getFormString($_POST, 'card_prefix');
            if ($legacyPrefix !== '') {
                $deleted = $this->authorMappingModel->deleteAuthorMapping($legacyPrefix);
            }
        }

        $parameter = $deleted ? 'message' : 'error';
        $message = $deleted ? '成功删除作者映射' : '删除作者映射失败';
        header('Location: ' . BASE_URL . '?controller=admin&action=authors&' . $parameter . '=' . urlencode($message));
        exit;
    }

    /**
     * 编辑作者映射页面
     *
     * @return void
     */
    public function editAuthor() {
        // 要求管理员权限（等级2以上）
        $this->userModel->requirePermission(2);

        $isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
        if ($isPost) {
            $this->requirePostCsrf('admin_edit_author');
            $mappingId = $this->getPositiveIntegerFormValue($_POST, 'mapping_id');
        } else {
            $mappingId = $this->getPositiveIntegerFormValue($_GET, 'id');
        }

        // 兼容旧版按card_prefix打开的编辑链接，并立即转换为稳定ID定位。
        if ($mappingId <= 0) {
            $legacyPrefix = $this->getFormString($_GET, 'card_prefix');
            $legacyMapping = $legacyPrefix === ''
                ? false
                : $this->authorMappingModel->getAuthorMappingByPrefix($legacyPrefix);
            $mappingId = $legacyMapping ? (int)$legacyMapping['id'] : 0;
        }

        $authorMapping = $mappingId > 0
            ? $this->authorMappingModel->getAuthorMappingById($mappingId)
            : false;
        if (!$authorMapping) {
            header('Location: ' . BASE_URL . '?controller=admin&action=authors&error=' . urlencode('找不到指定的作者映射'));
            exit;
        }

        if ($isPost) {
            $authorForm = $this->getAuthorMappingFormData($_POST);
            $errors = $this->validateAuthorMappingFormData($authorForm);

            // 如果没有错误，则更新作者映射
            if (empty($errors)) {
                $result = $this->authorMappingModel->updateAuthorMappingById(
                    $mappingId,
                    $authorForm['card_prefix'],
                    $authorForm['author_name'],
                    $authorForm['alias'],
                    $authorForm['contact'],
                    $authorForm['notes'],
                    $this->nullableInteger($authorForm['card_id_length']),
                    $this->nullableInteger($authorForm['card_id_start']),
                    $this->nullableInteger($authorForm['card_id_end']),
                    (int)$authorForm['priority']
                );

                if ($result) {
                    header('Location: ' . BASE_URL . '?controller=admin&action=authors&message=' . urlencode('成功更新作者映射'));
                    exit;
                }

                $errors[] = '更新作者映射失败';
            }

            // 如果有错误，则显示错误信息
            if (!empty($errors)) {
                $authorMapping = array_merge($authorMapping, $authorForm);

                // 渲染视图
                include __DIR__ . '/../Views/layout.php';
                include __DIR__ . '/../Views/admin/edit_author.php';
                include __DIR__ . '/../Views/footer.php';
                return;
            }
        }

        // 渲染视图
        include __DIR__ . '/../Views/layout.php';
        include __DIR__ . '/../Views/admin/edit_author.php';
        include __DIR__ . '/../Views/footer.php';
    }

    /**
     * 添加作者文本匹配规则
     *
     * @return void
     */
    public function addAuthorRule() {
        $this->userModel->requirePermission(2);
        $this->requirePostCsrf('admin_add_author_rule');

        $ruleForm = $this->getAuthorRuleFormData($_POST);
        $errors = $this->validateAuthorRuleFormData($ruleForm);

        if (empty($errors)) {
            $ruleId = $this->cardMatchRuleModel->addRule($this->normalizeAuthorRuleData($ruleForm));
            if ($ruleId !== false) {
                header('Location: ' . BASE_URL . '?controller=admin&action=authors&message=' . urlencode('成功添加文本匹配规则'));
                exit;
            }
            $errors[] = '添加文本匹配规则失败';
        }

        $this->renderAuthorManagement('', $errors, [], $ruleForm);
    }

    /**
     * 编辑作者文本匹配规则
     *
     * @return void
     */
    public function editAuthorRule() {
        $this->userModel->requirePermission(2);

        $isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
        if ($isPost) {
            $this->requirePostCsrf('admin_edit_author_rule');
            $ruleId = $this->getPositiveIntegerFormValue($_POST, 'rule_id');
        } else {
            $ruleId = $this->getPositiveIntegerFormValue($_GET, 'id');
        }

        if ($ruleId <= 0) {
            header('Location: ' . BASE_URL . '?controller=admin&action=authors&error=' . urlencode('无效的文本匹配规则ID'));
            exit;
        }

        $rule = $this->cardMatchRuleModel->getRuleById($ruleId);
        if (!$rule) {
            header('Location: ' . BASE_URL . '?controller=admin&action=authors&error=' . urlencode('找不到指定的文本匹配规则'));
            exit;
        }

        $errors = [];
        if ($isPost) {
            $ruleForm = $this->getAuthorRuleFormData($_POST);
            $errors = $this->validateAuthorRuleFormData($ruleForm);

            if (empty($errors)) {
                $result = $this->cardMatchRuleModel->updateRule(
                    $ruleId,
                    $this->normalizeAuthorRuleData($ruleForm)
                );
                if ($result) {
                    header('Location: ' . BASE_URL . '?controller=admin&action=authors&message=' . urlencode('成功更新文本匹配规则'));
                    exit;
                }
                $errors[] = '更新文本匹配规则失败';
            }

            $rule = array_merge($rule, $ruleForm);
        }

        $matchFieldLabels = $this->getAuthorRuleFieldLabels();
        $matchOperatorLabels = $this->getAuthorRuleOperatorLabels();
        $targetTypeLabels = $this->getAuthorRuleTargetTypeLabels();
        include __DIR__ . '/../Views/layout.php';
        include __DIR__ . '/../Views/admin/edit_author_rule.php';
        include __DIR__ . '/../Views/footer.php';
    }

    /**
     * 删除作者文本匹配规则
     *
     * @return void
     */
    public function deleteAuthorRule() {
        $this->userModel->requirePermission(2);
        $this->requirePostCsrf('admin_delete_author_rule');

        $ruleId = $this->getPositiveIntegerFormValue($_POST, 'rule_id');
        if ($ruleId <= 0 || !$this->cardMatchRuleModel->deleteRule($ruleId)) {
            header('Location: ' . BASE_URL . '?controller=admin&action=authors&error=' . urlencode('删除文本匹配规则失败'));
            exit;
        }

        header('Location: ' . BASE_URL . '?controller=admin&action=authors&message=' . urlencode('成功删除文本匹配规则'));
        exit;
    }

    /**
     * 启用或停用作者文本匹配规则
     *
     * @return void
     */
    public function toggleAuthorRule() {
        $this->userModel->requirePermission(2);
        $this->requirePostCsrf('admin_toggle_author_rule');

        $ruleId = $this->getPositiveIntegerFormValue($_POST, 'rule_id');
        $enabledValue = $this->getFormString($_POST, 'is_enabled');
        if ($ruleId <= 0 || !in_array($enabledValue, ['0', '1'], true)) {
            header('Location: ' . BASE_URL . '?controller=admin&action=authors&error=' . urlencode('无效的文本匹配规则状态'));
            exit;
        }

        if (!$this->cardMatchRuleModel->toggleRule($ruleId, (int)$enabledValue)) {
            header('Location: ' . BASE_URL . '?controller=admin&action=authors&error=' . urlencode('更新文本匹配规则状态失败'));
            exit;
        }

        $message = $enabledValue === '1' ? '已启用文本匹配规则' : '已停用文本匹配规则';
        header('Location: ' . BASE_URL . '?controller=admin&action=authors&message=' . urlencode($message));
        exit;
    }

    /**
     * 服务器提示管理
     */
    public function tips() {
        // 要求管理员权限（等级2以上）
        $this->userModel->requirePermission(2);

        // 读取tips文件
        $tips = $this->loadTips();

        // 获取消息
        $message = isset($_GET['message']) ? $_GET['message'] : '';
        $error = isset($_GET['error']) ? $_GET['error'] : '';

        // 渲染视图
        include __DIR__ . '/../Views/layout.php';
        include __DIR__ . '/../Views/admin/tips.php';
        include __DIR__ . '/../Views/footer.php';
    }

    /**
     * 添加提示
     */
    public function addTip() {
        // 要求管理员权限（等级2以上）
        $this->userModel->requirePermission(2);
        $this->requirePostCsrf('admin_add_tip');

        // 检查是否是POST请求
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $tipContent = isset($_POST['tip_content']) ? trim($_POST['tip_content']) : '';

            if (empty($tipContent)) {
                header('Location: ' . BASE_URL . '?controller=admin&action=tips&error=' . urlencode('提示内容不能为空'));
                exit;
            }

            // 读取现有tips
            $tips = $this->loadTips();

            // 添加新tip
            $tips[] = $tipContent;

            // 调试信息
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                Utils::debug('添加Tips调试', [
                    'tip_content' => $tipContent,
                    'tips_count' => count($tips),
                    'tips_file_path' => TIPS_FILE_PATH,
                    'dir_exists' => is_dir(dirname(TIPS_FILE_PATH)),
                    'dir_writable' => is_writable(dirname(TIPS_FILE_PATH)),
                    'file_exists' => file_exists(TIPS_FILE_PATH),
                    'file_writable' => file_exists(TIPS_FILE_PATH) ? is_writable(TIPS_FILE_PATH) : 'N/A'
                ]);
            }

            // 保存tips
            $saveResult = $this->saveTips($tips);
            if ($saveResult === true) {
                header('Location: ' . BASE_URL . '?controller=admin&action=tips&message=' . urlencode('成功添加提示'));
            } else {
                header('Location: ' . BASE_URL . '?controller=admin&action=tips&error=' . urlencode('保存失败: ' . $saveResult));
            }
            exit;
        }

        // 如果不是POST请求，则重定向到tips管理页面
        header('Location: ' . BASE_URL . '?controller=admin&action=tips');
        exit;
    }

    /**
     * 编辑提示
     */
    public function editTip() {
        // 要求管理员权限（等级2以上）
        $this->userModel->requirePermission(2);
        $this->requirePostCsrf('admin_edit_tip');

        // 检查是否是POST请求
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $index = isset($_POST['index']) ? (int)$_POST['index'] : -1;
            $tipContent = isset($_POST['tip_content']) ? trim($_POST['tip_content']) : '';

            if (empty($tipContent)) {
                header('Location: ' . BASE_URL . '?controller=admin&action=tips&error=' . urlencode('提示内容不能为空'));
                exit;
            }

            // 读取现有tips
            $tips = $this->loadTips();

            // 检查索引是否有效
            if ($index < 0 || $index >= count($tips)) {
                header('Location: ' . BASE_URL . '?controller=admin&action=tips&error=' . urlencode('无效的提示索引'));
                exit;
            }

            // 更新tip
            $tips[$index] = $tipContent;

            // 保存tips
            $saveResult = $this->saveTips($tips);
            if ($saveResult === true) {
                header('Location: ' . BASE_URL . '?controller=admin&action=tips&message=' . urlencode('成功更新提示'));
            } else {
                header('Location: ' . BASE_URL . '?controller=admin&action=tips&error=' . urlencode('保存失败: ' . $saveResult));
            }
            exit;
        }

        // 如果不是POST请求，则重定向到tips管理页面
        header('Location: ' . BASE_URL . '?controller=admin&action=tips');
        exit;
    }

    /**
     * 删除提示
     */
    public function deleteTip() {
        // 要求管理员权限（等级2以上）
        $this->userModel->requirePermission(2);
        $this->requirePostCsrf('admin_delete_tip');

        // 检查是否是POST请求
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $index = isset($_POST['index']) ? (int)$_POST['index'] : -1;

            // 读取现有tips
            $tips = $this->loadTips();

            // 检查索引是否有效
            if ($index < 0 || $index >= count($tips)) {
                header('Location: ' . BASE_URL . '?controller=admin&action=tips&error=' . urlencode('无效的提示索引'));
                exit;
            }

            // 删除tip
            array_splice($tips, $index, 1);

            // 保存tips
            $saveResult = $this->saveTips($tips);
            if ($saveResult === true) {
                header('Location: ' . BASE_URL . '?controller=admin&action=tips&message=' . urlencode('成功删除提示'));
            } else {
                header('Location: ' . BASE_URL . '?controller=admin&action=tips&error=' . urlencode('保存失败: ' . $saveResult));
            }
            exit;
        }

        // 如果不是POST请求，则重定向到tips管理页面
        header('Location: ' . BASE_URL . '?controller=admin&action=tips');
        exit;
    }

    /**
     * 读取tips文件
     *
     * @return array tips数组
     */
    private function loadTips() {
        $tipsFile = TIPS_FILE_PATH;
        $originalPath = dirname(__DIR__, 2) . '/data/const/tips.json';

        // 如果当前使用的是临时路径，且临时文件不存在，但原始文件存在，则复制原始文件
        if ($tipsFile !== $originalPath && !file_exists($tipsFile) && file_exists($originalPath) && is_readable($originalPath)) {
            $originalContent = file_get_contents($originalPath);
            if ($originalContent !== false) {
                // 确保临时目录存在
                $tempDir = dirname($tipsFile);
                if (!is_dir($tempDir)) {
                    @mkdir($tempDir, 0755, true);
                }
                // 复制原始文件到临时位置
                file_put_contents($tipsFile, $originalContent);

                if (defined('DEBUG_MODE') && DEBUG_MODE) {
                    Utils::debug('复制原始tips文件到临时位置', [
                        'from' => $originalPath,
                        'to' => $tipsFile
                    ]);
                }
            }
        }

        if (!file_exists($tipsFile)) {
            return [];
        }

        $content = file_get_contents($tipsFile);
        if ($content === false) {
            return [];
        }

        $tips = json_decode($content, true);
        if (!is_array($tips)) {
            return [];
        }

        return $tips;
    }

    /**
     * 保存tips到文件
     *
     * @param array $tips tips数组
     * @return bool|string 保存成功返回true，失败返回错误信息
     */
    private function saveTips($tips) {
        $tipsFile = TIPS_FILE_PATH;

        // 调试信息
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            Utils::debug('saveTips开始', [
                'tips_file' => $tipsFile,
                'tips_count' => count($tips),
                'tips_data' => $tips
            ]);
        }

        // 确保目录存在
        $dir = dirname($tipsFile);
        if (!is_dir($dir)) {
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                Utils::debug('创建目录', ['dir' => $dir]);
            }
            if (!mkdir($dir, 0755, true)) {
                $error = error_get_last();
                $errorMsg = "无法创建目录 {$dir}: " . ($error ? $error['message'] : '未知错误');
                if (defined('DEBUG_MODE') && DEBUG_MODE) {
                    Utils::debug('目录创建失败', ['error' => $errorMsg]);
                }
                return $errorMsg;
            }
        }

        // 检查目录权限
        if (!is_writable($dir)) {
            $errorMsg = "目录 {$dir} 不可写";
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                Utils::debug('目录权限检查失败', ['error' => $errorMsg]);
            }
            return $errorMsg;
        }

        // 保存为格式化的JSON
        $content = json_encode($tips, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($content === false) {
            $errorMsg = "JSON编码失败: " . json_last_error_msg();
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                Utils::debug('JSON编码失败', ['error' => $errorMsg]);
            }
            return $errorMsg;
        }

        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            Utils::debug('JSON编码成功', ['content_length' => strlen($content)]);
        }

        // 尝试写入文件
        $result = file_put_contents($tipsFile, $content);
        if ($result === false) {
            $error = error_get_last();
            $errorMsg = "文件写入失败: " . ($error ? $error['message'] : '未知错误');
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                Utils::debug('文件写入失败', ['error' => $errorMsg]);
            }
            return $errorMsg;
        }

        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            Utils::debug('文件写入成功', ['bytes_written' => $result]);
        }

        return true;
    }

    /**
     * 投票者封禁管理
     */
    public function voterBans() {
        // 要求管理员权限（等级2以上）
        $this->userModel->requirePermission(2);

        // 获取所有活跃的封禁记录
        $bans = $this->voterBanModel->getAllActiveBans();

        // 获取消息
        $message = isset($_GET['message']) ? $_GET['message'] : '';
        $error = isset($_GET['error']) ? $_GET['error'] : '';

        // 渲染视图
        include __DIR__ . '/../Views/layout.php';
        include __DIR__ . '/../Views/admin/voter_bans.php';
        include __DIR__ . '/../Views/footer.php';
    }

    /**
     * 添加投票者封禁
     */
    public function addVoterBan() {
        // 要求管理员权限（等级2以上）
        $this->userModel->requirePermission(2);
        $this->requirePostCsrf('admin_add_voter_ban');

        // 检查是否是POST请求
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // 获取表单数据
            $voterIdentifier = isset($_POST['voter_identifier']) ? trim($_POST['voter_identifier']) : '';
            $banLevel = isset($_POST['ban_level']) ? (int)$_POST['ban_level'] : 0;
            $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';

            // 验证数据
            $errors = [];

            if (empty($voterIdentifier)) {
                $errors[] = '请输入投票者标识符';
            } elseif (strlen($voterIdentifier) !== 9) {
                $errors[] = '投票者标识符必须为9位字符';
            }

            if ($banLevel < 1 || $banLevel > 2) {
                $errors[] = '请选择有效的封禁等级';
            }

            if (empty($reason)) {
                $errors[] = '请输入封禁理由';
            }

            // 如果没有错误，则添加封禁
            if (empty($errors)) {
                $operator = 'Admin:' . $this->userModel->getCurrentUsername();
                $result = $this->voterBanModel->addBan($voterIdentifier, $banLevel, $reason, $operator);

                if ($result) {
                    // 记录日志
                    $this->voterBanModel->logBanAction('ban', $voterIdentifier, $banLevel, $reason, $operator);

                    // 重定向到封禁管理页面
                    header('Location: ' . BASE_URL . '?controller=admin&action=voterBans&message=' . urlencode('封禁添加成功'));
                    exit;
                } else {
                    $errors[] = '封禁添加失败';
                }
            }

            // 如果有错误，则显示错误信息
            if (!empty($errors)) {
                $error = implode('<br>', $errors);
                header('Location: ' . BASE_URL . '?controller=admin&action=voterBans&error=' . urlencode($error));
                exit;
            }
        }

        // 如果不是POST请求，则重定向到封禁管理页面
        header('Location: ' . BASE_URL . '?controller=admin&action=voterBans');
        exit;
    }

    /**
     * 解除投票者封禁
     */
    public function removeVoterBan() {
        // 要求管理员权限（等级2以上）
        $this->userModel->requirePermission(2);
        $this->requirePostCsrf('admin_remove_voter_ban');

        // 检查是否是POST请求
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // 获取参数
            $voterIdentifier = isset($_POST['voter_identifier']) ? trim($_POST['voter_identifier']) : '';

            if (empty($voterIdentifier)) {
                header('Location: ' . BASE_URL . '?controller=admin&action=voterBans&error=' . urlencode('参数错误'));
                exit;
            }

            // 获取封禁记录（用于日志）
            $ban = $this->voterBanModel->getBanByIdentifier($voterIdentifier);

            // 解除封禁
            $result = $this->voterBanModel->removeBan($voterIdentifier);

            if ($result) {
                // 记录日志
                $operator = 'Admin:' . $this->userModel->getCurrentUsername();
                $this->voterBanModel->logBanAction('unban', $voterIdentifier, 0, '管理员解封', $operator);

                header('Location: ' . BASE_URL . '?controller=admin&action=voterBans&message=' . urlencode('解封成功'));
            } else {
                header('Location: ' . BASE_URL . '?controller=admin&action=voterBans&error=' . urlencode('解封失败'));
            }
        } else {
            header('Location: ' . BASE_URL . '?controller=admin&action=voterBans');
        }
        exit;
    }

    /**
     * 系统配置管理
     */
    public function config() {
        // 要求管理员权限（等级2以上）
        $this->userModel->requirePermission(2);

        $canEdit = $this->userModel->getCurrentGroup() >= 3;
        $message = '';

        // 获取配置项
        $configs = $this->getConfigItems();

        // 处理保存请求
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canEdit) {
            $this->requirePostCsrf('admin_config');
            $newValues = isset($_POST['config']) ? $_POST['config'] : [];
            $this->saveConfigItems($configs, $newValues);
            $configs = $this->getConfigItems();
            $message = '配置已保存';
        }

        // 渲染视图
        include __DIR__ . '/../Views/layout.php';
        include __DIR__ . '/../Views/admin/config.php';
        include __DIR__ . '/../Views/footer.php';
    }

    /**
     * 渲染作者映射与文本规则管理页面
     *
     * @param string $message 成功消息
     * @param array $errors 错误消息
     * @param array $authorForm 作者映射表单数据
     * @param array $ruleForm 文本规则表单数据
     * @return void
     */
    private function renderAuthorManagement($message = '', $errors = [], $authorForm = [], $ruleForm = []) {
        $authorMappings = $this->authorMappingModel->getAllAuthorMappings();
        $authorRules = $this->cardMatchRuleModel->getAllRules();
        $matchFieldLabels = $this->getAuthorRuleFieldLabels();
        $matchOperatorLabels = $this->getAuthorRuleOperatorLabels();
        $targetTypeLabels = $this->getAuthorRuleTargetTypeLabels();
        $authorForm = array_merge([
            'card_prefix' => '',
            'author_name' => '',
            'card_id_length' => '',
            'card_id_start' => '',
            'card_id_end' => '',
            'priority' => '100',
            'alias' => '',
            'contact' => '',
            'notes' => ''
        ], $authorForm);
        $ruleForm = array_merge([
            'database_file' => '',
            'match_field' => 'desc',
            'match_operator' => 'contains',
            'match_value' => '',
            'target_type' => 'author',
            'target_value' => '',
            'priority' => '100',
            'is_case_sensitive' => 0,
            'is_enabled' => 1,
            'notes' => ''
        ], $ruleForm);

        include __DIR__ . '/../Views/layout.php';
        include __DIR__ . '/../Views/admin/authors.php';
        include __DIR__ . '/../Views/footer.php';
    }

    /**
     * 读取作者映射表单
     *
     * @param array $source 表单来源
     * @return array
     */
    private function getAuthorMappingFormData($source) {
        return [
            'card_prefix' => $this->getFormString($source, 'card_prefix'),
            'author_name' => $this->getFormString($source, 'author_name'),
            'card_id_length' => $this->getFormString($source, 'card_id_length'),
            'card_id_start' => $this->getFormString($source, 'card_id_start'),
            'card_id_end' => $this->getFormString($source, 'card_id_end'),
            'priority' => $this->getFormString($source, 'priority', '100'),
            'alias' => $this->getFormString($source, 'alias'),
            'contact' => $this->getFormString($source, 'contact'),
            'notes' => $this->getFormString($source, 'notes')
        ];
    }

    /**
     * 验证作者映射表单
     *
     * @param array $data 表单数据
     * @return array 错误消息
     */
    private function validateAuthorMappingFormData($data) {
        $errors = [];

        if ($data['card_prefix'] === '') {
            $errors[] = '请输入卡片前缀';
        } elseif (!preg_match('/^\d{1,16}$/', $data['card_prefix'])) {
            $errors[] = '卡片前缀必须为1至16位数字，并请保留有意义的前导零';
        }

        if ($data['author_name'] === '') {
            $errors[] = '请输入作者名称';
        }

        if ($data['card_id_length'] !== '') {
            if (!preg_match('/^\d{1,2}$/', $data['card_id_length']) ||
                (int)$data['card_id_length'] < 1 || (int)$data['card_id_length'] > 16) {
                $errors[] = '卡号总位数必须是1至16之间的整数';
            } elseif ($data['card_prefix'] !== '' && preg_match('/^\d{1,16}$/', $data['card_prefix']) &&
                (int)$data['card_id_length'] < max(3, strlen($data['card_prefix']))) {
                $errors[] = '卡号总位数不能短于规范化后的卡片前缀';
            }
        }

        $hasStart = $data['card_id_start'] !== '';
        $hasEnd = $data['card_id_end'] !== '';
        if ($hasStart xor $hasEnd) {
            $errors[] = '显式卡号区间的起始值和结束值必须同时填写';
        } elseif ($hasStart && $hasEnd) {
            if (!$this->isUnsignedIntegerString($data['card_id_start']) ||
                !$this->isUnsignedIntegerString($data['card_id_end'])) {
                $errors[] = '显式卡号区间必须为不超过16位的非负整数';
            } elseif ((int)$data['card_id_start'] > (int)$data['card_id_end']) {
                $errors[] = '显式卡号区间的起始值不能大于结束值';
            }
        }

        if (!$this->isIntegerString($data['priority'])) {
            $errors[] = '优先级必须是有效整数';
        }

        return $errors;
    }

    /**
     * 读取作者文本规则表单
     *
     * @param array $source 表单来源
     * @return array
     */
    private function getAuthorRuleFormData($source) {
        return [
            'database_file' => $this->getFormString($source, 'database_file'),
            'match_field' => $this->getFormString($source, 'match_field', 'desc'),
            'match_operator' => $this->getFormString($source, 'match_operator', 'contains'),
            'match_value' => $this->getFormString($source, 'match_value'),
            'target_type' => $this->getFormString($source, 'target_type', 'author'),
            'target_value' => $this->getFormString(
                $source,
                'target_value',
                $this->getFormString($source, 'author_name')
            ),
            'priority' => $this->getFormString($source, 'priority', '100'),
            'is_case_sensitive' => isset($source['is_case_sensitive']) &&
                is_scalar($source['is_case_sensitive']) && (string)$source['is_case_sensitive'] === '1' ? 1 : 0,
            'is_enabled' => isset($source['is_enabled']) &&
                is_scalar($source['is_enabled']) && (string)$source['is_enabled'] === '1' ? 1 : 0,
            'notes' => $this->getFormString($source, 'notes')
        ];
    }

    /**
     * 验证作者文本规则表单
     *
     * @param array $data 表单数据
     * @return array 错误消息
     */
    private function validateAuthorRuleFormData($data) {
        $errors = [];
        $validFields = array_keys($this->getAuthorRuleFieldLabels());
        $validOperators = array_keys($this->getAuthorRuleOperatorLabels());
        $validTargetTypes = array_keys($this->getAuthorRuleTargetTypeLabels());

        if (strlen($data['database_file']) > 255 ||
            strpos($data['database_file'], '/') !== false || strpos($data['database_file'], '\\') !== false) {
            $errors[] = 'CDB来源只能填写文件名，不能包含路径';
        } elseif ($data['database_file'] !== '' && !preg_match('/^[^\/]+\.cdb$/iu', $data['database_file'])) {
            $errors[] = 'CDB来源必须是以.cdb结尾的文件名';
        }
        if (!in_array($data['match_field'], $validFields, true)) {
            $errors[] = '请选择有效的CDB文本字段';
        }
        if (!in_array($data['match_operator'], $validOperators, true)) {
            $errors[] = '请选择有效的匹配方式';
        }
        if ($data['match_value'] === '') {
            $errors[] = '请输入匹配值';
        }
        if (!in_array($data['target_type'], $validTargetTypes, true)) {
            $errors[] = '请选择有效的规则目标类型';
        }
        if ($data['target_value'] === '') {
            $errors[] = '请输入规则对应的作者或系列分组名称';
        }
        if (!$this->isIntegerString($data['priority'])) {
            $errors[] = '优先级必须是有效整数';
        }

        return $errors;
    }

    /**
     * 获取文本规则字段选项
     *
     * @return array 字段值与界面标签
     */
    private function getAuthorRuleFieldLabels() {
        $labels = [
            'name' => '卡名（name）',
            'desc' => '描述（desc）',
            'any' => '任意文本字段'
        ];
        for ($index = 1; $index <= 16; $index++) {
            $labels['str' . $index] = '附加文本 str' . $index;
        }
        return $labels;
    }

    /**
     * 获取文本规则匹配方式选项
     *
     * @return array 运算符值与界面标签
     */
    private function getAuthorRuleOperatorLabels() {
        return [
            'contains' => '包含',
            'equals' => '整个字段相等',
            'line_equals' => '其中一行相等'
        ];
    }

    /**
     * 获取文本规则目标类型选项
     *
     * @return array 类型值与界面标签
     */
    private function getAuthorRuleTargetTypeLabels() {
        return [
            'author' => '作者归属',
            'series' => '人工系列分组'
        ];
    }

    /**
     * 规范化文本规则数据以供模型保存
     *
     * @param array $data 表单数据
     * @return array
     */
    private function normalizeAuthorRuleData($data) {
        $data['database_file'] = $data['database_file'] === '' ? null : $data['database_file'];
        $data['priority'] = (int)$data['priority'];
        $data['is_case_sensitive'] = (int)$data['is_case_sensitive'];
        $data['is_enabled'] = (int)$data['is_enabled'];
        return $data;
    }

    /**
     * 安全读取标量表单字符串
     *
     * @param array $source 表单来源
     * @param string $key 字段名
     * @param string $default 默认值
     * @return string
     */
    private function getFormString($source, $key, $default = '') {
        if (!isset($source[$key]) || !is_scalar($source[$key])) {
            return $default;
        }
        return trim((string)$source[$key]);
    }

    /**
     * 严格读取正整数表单值
     *
     * @param array $source 表单来源
     * @param string $key 字段名
     * @return int 无效时返回0
     */
    private function getPositiveIntegerFormValue($source, $key) {
        $value = $this->getFormString($source, $key);
        if (preg_match('/^[1-9]\d*$/', $value) !== 1) {
            return 0;
        }

        $integer = filter_var($value, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1]
        ]);
        return $integer === false ? 0 : (int)$integer;
    }

    /**
     * 判断是否为PHP可表示的整数文本
     *
     * @param string $value 待检查文本
     * @return bool
     */
    private function isIntegerString($value) {
        return preg_match('/^-?\d+$/', $value) === 1 &&
            filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    /**
     * 判断是否为安全的非负卡号整数文本
     *
     * @param string $value 待检查文本
     * @return bool
     */
    private function isUnsignedIntegerString($value) {
        return preg_match('/^\d{1,16}$/', $value) === 1;
    }

    /**
     * 将可选整数表单值转为整数或null
     *
     * @param string $value 表单值
     * @return int|null
     */
    private function nullableInteger($value) {
        return $value === '' ? null : (int)$value;
    }

    /**
     * 管理后台 POST / CSRF 校验
     *
     * @param string $context 上下文
     */
    private function requirePostCsrf($context) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Utils::abort(405, 'Method Not Allowed');
        }

        $csrfToken = Utils::getSafeParam($_POST, 'csrf_token', 'string', '', 128);
        if (empty($csrfToken) || !Utils::validateCsrfToken($context, $csrfToken, false)) {
            Utils::abort(403, 'CSRF 校验失败');
        }
    }

    /**
     * 从配置文件解析配置项
     *
     * @return array
     */
    private function getConfigItems() {
        $configFile = dirname(__DIR__, 2) . '/config.php';
        $lines = @file($configFile);
        $items = [];
        $description = '';

        if ($lines) {
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || strpos($line, '/*') === 0 || strpos($line, '*') === 0) {
                    continue;
                }
                if (strpos($line, '//') === 0) {
                    $description = substr($line, 2);
                    continue;
                }
                if (preg_match("/define\(['\"]([A-Z0-9_]+)['\"]/", $line, $matches)) {
                    $name = $matches[1];
                    if (in_array($name, $this->getSensitiveConfigNames(), true)) {
                        $description = '';
                        continue;
                    }
                    $value = defined($name) ? constant($name) : '';
                    if (is_bool($value)) {
                        $value = $value ? 'true' : 'false';
                    } elseif (is_array($value) || is_object($value)) {
                        $value = json_encode($value);
                    }
                    $items[$name] = [
                        'value' => (string)$value,
                        'description' => $description
                    ];
                    $description = '';
                }
            }
        }

        return $items;
    }

    /**
     * 保存配置到用户配置文件
     *
     * @param array $configItems
     * @param array $newValues
     * @return void
     */
    private function saveConfigItems($configItems, $newValues) {
        $configPath = dirname(__DIR__, 2) . '/config.user.php';
        $lines = [
            '<?php',
            '/**',
            ' * RAMSAY 用户配置文件 - 由系统生成',
            ' * 请勿手动修改此文件',
            ' */',
            ''
        ];

        foreach ($configItems as $name => $info) {
            if (in_array($name, $this->getSensitiveConfigNames(), true)) {
                continue;
            }
            if (!isset($newValues[$name]) || $newValues[$name] === '') {
                continue;
            }
            $value = trim($newValues[$name]);
            if (strtolower($value) === 'true' || strtolower($value) === 'false') {
                $valueExpr = strtolower($value);
            } elseif (is_numeric($value)) {
                $valueExpr = $value;
            } else {
                $valueExpr = var_export($value, true);
            }
            $lines[] = "define('{$name}', {$valueExpr});";
        }

        // 敏感值不在后台表单中回显，但保存其他配置时需要原样保留。
        foreach ($this->getSensitiveConfigNames() as $name) {
            if ($name === 'ADMIN_CONFIG' || !defined($name)) {
                continue;
            }
            $value = constant($name);
            if ($value === '') {
                continue;
            }
            $lines[] = "define('{$name}', " . var_export($value, true) . ');';
        }

        $content = implode("\n", $lines) . "\n";
        file_put_contents($configPath, $content);
    }

    /**
     * 获取不能在后台回显的敏感配置项
     *
     * @return array 敏感配置名
     */
    private function getSensitiveConfigNames() {
        return [
            'ADMIN_CONFIG',
            'SRVPRO2_API_PASSWORD',
            'SRVPRO2_DB_PASSWORD'
        ];
    }
}
