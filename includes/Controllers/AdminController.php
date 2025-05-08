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
     * 构造函数
     */
    public function __construct() {
        $this->userModel = new User();
        $this->voteModel = new Vote();
        $this->cardModel = new Card();
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
                return;
            }
        }

        // 渲染视图
        include __DIR__ . '/../Views/layout.php';
        include __DIR__ . '/../Views/admin/login.php';
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
        }

        // 渲染视图
        include __DIR__ . '/../Views/layout.php';
        include __DIR__ . '/../Views/admin/votes.php';
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
}
