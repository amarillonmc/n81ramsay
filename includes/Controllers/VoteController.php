<?php
/**
 * 投票控制器
 *
 * 处理投票相关的请求
 */
class VoteController {
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
        $this->voteModel = new Vote();
        $this->cardModel = new Card();
    }

    /**
     * 投票列表
     */
    public function index() {
        // 获取页码
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($page < 1) {
            $page = 1;
        }

        // 获取当前投票周期
        $db = Database::getInstance();
        $currentCycle = $db->getCurrentVoteCycle();

        // 获取投票列表（按周期分组）
        $votes = $this->voteModel->getAllVotes(true, $page, VOTES_PER_PAGE, true);
        $totalVotes = $this->voteModel->getVoteCount(true);

        // 计算总页数
        $totalPages = ceil($totalVotes / VOTES_PER_PAGE);

        // 处理投票数据
        Utils::checkMemoryUsage('投票列表处理开始');

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

            // 检查内存使用情况，如果超过阈值则进行垃圾回收
            if (Utils::checkMemoryUsage('投票数据处理', 2048)) {
                Utils::forceGarbageCollection('投票列表处理');
            }
        }

        // 解除引用，防止后续操作影响数组
        unset($vote);

        Utils::checkMemoryUsage('投票列表处理完成');

        // 渲染视图
        include __DIR__ . '/../Views/layout.php';
        include __DIR__ . '/../Views/votes/index.php';
        include __DIR__ . '/../Views/footer.php';
    }

    /**
     * 创建投票
     */
    public function create() {
        // 检查是否是POST请求
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // 获取表单数据
            $cardId = isset($_POST['card_id']) ? (int)$_POST['card_id'] : 0;
            $environmentId = isset($_POST['environment_id']) ? (int)$_POST['environment_id'] : 0;
            $status = isset($_POST['status']) ? (int)$_POST['status'] : 3;
            $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
            $initiatorId = isset($_POST['initiator_id']) ? trim($_POST['initiator_id']) : '';

            // 检查卡片是否有alias字段，如果有则使用alias对应的卡片ID
            $card = $this->cardModel->getCardById($cardId);
            if ($card && $card['alias'] > 0) {
                $aliasCard = $this->cardModel->getCardById($card['alias']);
                if ($aliasCard) {
                    $cardId = $card['alias'];
                }
            }

            // 验证数据
            $errors = [];

            if ($cardId <= 0) {
                $errors[] = '请选择卡片';
            }

            if ($environmentId <= 0) {
                $errors[] = '请选择环境';
            }

            if ($status < 0 || $status > 3) {
                $errors[] = '请选择有效的禁限状态';
            }

            if (empty($initiatorId)) {
                $errors[] = '请输入您的ID';
            }

            // 如果没有错误，则创建投票
            if (empty($errors)) {
                $voteLink = $this->voteModel->createVote($cardId, $environmentId, $status, $reason, $initiatorId);

                if ($voteLink) {
                    // 重定向到投票页面
                    header('Location: ' . BASE_URL . '?controller=vote&id=' . $voteLink);
                    exit;
                } else {
                    $errors[] = '创建投票失败';
                }
            }

            // 如果有错误，则显示错误信息
            if (!empty($errors)) {
                $card = $this->cardModel->getCardById($cardId);
                $environments = Utils::getEnvironments();

                include __DIR__ . '/../Views/layout.php';
                include __DIR__ . '/../Views/votes/create.php';
                include __DIR__ . '/../Views/footer.php';
                return;
            }
        }

        // 获取卡片ID
        $cardId = isset($_GET['card_id']) ? (int)$_GET['card_id'] : 0;

        // 获取卡片信息
        $card = $this->cardModel->getCardById($cardId);

        // 如果卡片不存在，则重定向到首页
        if (!$card) {
            header('Location: ' . BASE_URL);
            exit;
        }

        // 如果卡片有alias字段，则使用alias对应的卡片ID
        if ($card['alias'] > 0) {
            $aliasCard = $this->cardModel->getCardById($card['alias']);
            if ($aliasCard) {
                $cardId = $card['alias'];
                $card = $aliasCard;
            }
        }

        // 获取环境列表
        $environments = Utils::getEnvironments();

        // 渲染视图
        include __DIR__ . '/../Views/layout.php';
        include __DIR__ . '/../Views/votes/create.php';
        include __DIR__ . '/../Views/footer.php';
    }

    /**
     * 投票详情
     */
    public function vote($voteLink) {
        // 获取投票信息
        $vote = $this->voteModel->getVoteByLink($voteLink);

        // 如果投票不存在，则重定向到首页
        if (!$vote) {
            header('Location: ' . BASE_URL);
            exit;
        }

        // 获取卡片信息
        $card = $this->cardModel->getCardById($vote['card_id']);

        // 获取环境信息
        $environment = Utils::getEnvironmentById($vote['environment_id']);

        // 获取投票统计
        $stats = $this->voteModel->getVoteStats($vote['id']);

        // 获取投票记录
        $records = $this->voteModel->getVoteRecords($vote['id']);

        // 获取卡片在当前环境中的禁限状态
        $currentLimitStatus = $this->cardModel->getCardLimitStatus($vote['card_id'], $environment['header']);

        // 检查是否是POST请求
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $vote['is_closed'] == 0) {
            // 获取表单数据
            $status = isset($_POST['status']) ? (int)$_POST['status'] : 3;
            $userId = isset($_POST['user_id']) ? trim($_POST['user_id']) : '';
            $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

            // 验证数据
            $errors = [];

            if ($status < 0 || $status > 3) {
                $errors[] = '请选择有效的禁限状态';
            }

            if (empty($userId)) {
                $errors[] = '请输入您的ID';
            }

            // 如果没有错误，则添加投票记录
            if (empty($errors)) {
                $result = $this->voteModel->addVoteRecord($vote['id'], $userId, $status, $comment);

                if ($result) {
                    // 重定向到投票页面（刷新）
                    header('Location: ' . BASE_URL . '?controller=vote&id=' . $voteLink);
                    exit;
                } else {
                    $errors[] = '您已经投过票了';
                }
            }

            // 如果有错误，则显示错误信息
            if (!empty($errors)) {
                include __DIR__ . '/../Views/layout.php';
                include __DIR__ . '/../Views/votes/vote.php';
                include __DIR__ . '/../Views/footer.php';
                return;
            }
        }

        // 渲染视图
        include __DIR__ . '/../Views/layout.php';
        include __DIR__ . '/../Views/votes/vote.php';
        include __DIR__ . '/../Views/footer.php';
    }
}
