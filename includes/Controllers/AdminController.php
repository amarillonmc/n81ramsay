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

        // 获取所有作者映射
        $authorMappings = $this->authorMappingModel->getAllAuthorMappings();

        // 获取消息
        $message = isset($_GET['message']) ? $_GET['message'] : '';

        // 渲染视图
        include __DIR__ . '/../Views/layout.php';
        include __DIR__ . '/../Views/admin/authors.php';
        include __DIR__ . '/../Views/footer.php';
    }

    /**
     * 识别作者
     */
    public function identifyAuthors() {
        // 要求管理员权限（等级255以上）
        $this->userModel->requirePermission(255);

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
     */
    public function addAuthor() {
        // 要求管理员权限（等级2以上）
        $this->userModel->requirePermission(2);

        // 检查是否是POST请求
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // 获取表单数据
            $cardPrefix = isset($_POST['card_prefix']) ? trim($_POST['card_prefix']) : '';
            $authorName = isset($_POST['author_name']) ? trim($_POST['author_name']) : '';
            $alias = isset($_POST['alias']) ? trim($_POST['alias']) : null;
            $contact = isset($_POST['contact']) ? trim($_POST['contact']) : null;
            $notes = isset($_POST['notes']) ? trim($_POST['notes']) : null;

            // 验证数据
            $errors = [];

            if (empty($cardPrefix)) {
                $errors[] = '请输入卡片前缀';
            }

            if (empty($authorName)) {
                $errors[] = '请输入作者名称';
            }

            // 如果没有错误，则添加作者映射
            if (empty($errors)) {
                $this->authorMappingModel->addAuthorMapping($cardPrefix, $authorName, $alias, $contact, $notes);

                // 设置消息
                $message = '成功添加作者映射';

                // 重定向到作者管理页面
                header('Location: ' . BASE_URL . '?controller=admin&action=authors&message=' . urlencode($message));
                exit;
            }

            // 如果有错误，则显示错误信息
            if (!empty($errors)) {
                // 获取所有作者映射
                $authorMappings = $this->authorMappingModel->getAllAuthorMappings();

                // 渲染视图
                include __DIR__ . '/../Views/layout.php';
                include __DIR__ . '/../Views/admin/authors.php';
                include __DIR__ . '/../Views/footer.php';
                return;
            }
        }

        // 如果不是POST请求，则重定向到作者管理页面
        header('Location: ' . BASE_URL . '?controller=admin&action=authors');
        exit;
    }

    /**
     * 删除作者映射
     */
    public function deleteAuthor() {
        // 要求管理员权限（等级2以上）
        $this->userModel->requirePermission(2);

        // 检查是否是POST请求
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // 获取表单数据
            $cardPrefix = isset($_POST['card_prefix']) ? trim($_POST['card_prefix']) : '';

            // 删除作者映射
            $this->authorMappingModel->deleteAuthorMapping($cardPrefix);

            // 设置消息
            $message = '成功删除作者映射';

            // 重定向到作者管理页面
            header('Location: ' . BASE_URL . '?controller=admin&action=authors&message=' . urlencode($message));
            exit;
        }

        // 如果不是POST请求，则重定向到作者管理页面
        header('Location: ' . BASE_URL . '?controller=admin&action=authors');
        exit;
    }

    /**
     * 编辑作者映射页面
     */
    public function editAuthor() {
        // 要求管理员权限（等级2以上）
        $this->userModel->requirePermission(2);

        // 获取卡片前缀
        $cardPrefix = isset($_GET['card_prefix']) ? trim($_GET['card_prefix']) : '';

        if (empty($cardPrefix)) {
            // 如果没有提供卡片前缀，则重定向到作者管理页面
            header('Location: ' . BASE_URL . '?controller=admin&action=authors');
            exit;
        }

        // 获取作者映射信息
        $authorMapping = $this->authorMappingModel->getAuthorMappingByPrefix($cardPrefix);

        if (!$authorMapping) {
            // 如果找不到作者映射，则重定向到作者管理页面
            header('Location: ' . BASE_URL . '?controller=admin&action=authors&message=' . urlencode('找不到指定的作者映射'));
            exit;
        }

        // 检查是否是POST请求
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // 获取表单数据
            $newCardPrefix = isset($_POST['card_prefix']) ? trim($_POST['card_prefix']) : '';
            $authorName = isset($_POST['author_name']) ? trim($_POST['author_name']) : '';
            $alias = isset($_POST['alias']) ? trim($_POST['alias']) : null;
            $contact = isset($_POST['contact']) ? trim($_POST['contact']) : null;
            $notes = isset($_POST['notes']) ? trim($_POST['notes']) : null;

            // 验证数据
            $errors = [];

            if (empty($newCardPrefix)) {
                $errors[] = '请输入卡片前缀';
            }

            if (empty($authorName)) {
                $errors[] = '请输入作者名称';
            }

            // 如果没有错误，则更新作者映射
            if (empty($errors)) {
                // 使用新方法更新作者映射（包括卡片前缀）
                $result = $this->authorMappingModel->updateAuthorMappingWithPrefix(
                    $cardPrefix,
                    $newCardPrefix,
                    $authorName,
                    $alias,
                    $contact,
                    $notes
                );

                if ($result) {
                    // 设置成功消息
                    $message = '成功更新作者映射';
                } else {
                    // 设置错误消息
                    if ($cardPrefix !== $newCardPrefix) {
                        $message = '更新失败：新卡片前缀已存在';
                    } else {
                        $message = '更新失败：未知错误';
                    }
                }

                // 重定向到作者管理页面
                header('Location: ' . BASE_URL . '?controller=admin&action=authors&message=' . urlencode($message));
                exit;
            }

            // 如果有错误，则显示错误信息
            if (!empty($errors)) {
                // 更新作者映射信息，以便在表单中显示用户输入的值
                $authorMapping['card_prefix'] = $newCardPrefix;
                $authorMapping['author_name'] = $authorName;
                $authorMapping['alias'] = $alias;
                $authorMapping['contact'] = $contact;
                $authorMapping['notes'] = $notes;

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
}
