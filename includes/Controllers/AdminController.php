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
     * 构造函数
     */
    public function __construct() {
        $this->userModel = new User();
        $this->voteModel = new Vote();
        $this->cardModel = new Card();
        $this->authorMappingModel = new AuthorMapping();
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
}
