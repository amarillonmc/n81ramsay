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
     * 投票者封禁模型
     * @var VoterBan
     */
    private $voterBanModel;

    /**
     * 构造函数
     */
    public function __construct() {
        $this->voteModel = new Vote();
        $this->cardModel = new Card();
        $this->voterBanModel = new VoterBan();
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

            // 如果是系列投票，获取系列卡片数量
            if ($vote['is_series_vote'] && $card && $card['setcode'] > 0) {
                $seriesCards = $this->cardModel->getCardsBySetcode($card['setcode']);
                $vote['series_card_count'] = count($seriesCards);
            }

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

            // 检查是否为无意义投票
            if (!ALLOW_MEANINGLESS_VOTING && $cardId > 0 && $environmentId > 0) {
                $environment = Utils::getEnvironmentById($environmentId);
                if ($environment) {
                    $currentStatus = $this->cardModel->getCardLimitStatus($cardId, $environment['header']);
                    if ($status == $currentStatus) {
                        $statusText = Utils::getLimitStatusText($status);
                        $errors[] = "无法对已经是{$statusText}的卡片发起{$statusText}投票，这是无意义的投票";
                    }
                }
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

                // 获取卡片在各环境中的禁限状态
                $limitStatus = [];
                foreach ($environments as $env) {
                    $limitStatus[$env['id']] = $this->cardModel->getCardLimitStatus($cardId, $env['header']);
                }

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

        // 获取卡片在各环境中的禁限状态
        $limitStatus = [];
        foreach ($environments as $env) {
            $limitStatus[$env['id']] = $this->cardModel->getCardLimitStatus($cardId, $env['header']);
        }

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

        // 如果是系列投票，获取系列卡片信息
        $seriesCards = [];
        if ($vote['is_series_vote'] && $card['setcode'] > 0) {
            $seriesCards = $this->cardModel->getCardsBySetcode($card['setcode']);
        }

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

            // 检查投票者是否被封禁
            if (empty($errors)) {
                $currentIp = Utils::getClientIp();
                $voterIdentifier = Utils::generateVoterIdentifier($currentIp, $userId);
                $ban = $this->voterBanModel->checkBan($voterIdentifier);

                if ($ban) {
                    if ($ban['ban_level'] == 2) {
                        // 等级2封禁：完全禁止投票
                        $errors[] = '您已被禁止投票，无法进行投票操作';
                    } elseif ($ban['ban_level'] == 1) {
                        // 等级1封禁：检查理由长度
                        $minLength = defined('SERIES_VOTING_REASON_MIN_LENGTH') ? SERIES_VOTING_REASON_MIN_LENGTH : 400;
                        if (strlen($comment) < $minLength) {
                            $errors[] = "由于您的投票受到限制，评论字数不足，至少需要 {$minLength} 个字符，当前为 " . strlen($comment) . " 个字符";
                        }
                    }
                }
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

    /**
     * 系列投票创建
     */
    public function createSeries() {
        // 检查系列投票功能是否启用
        if (!defined('SERIES_VOTING_ENABLED') || !SERIES_VOTING_ENABLED) {
            header('Location: ' . BASE_URL);
            exit;
        }

        // 检查是否是POST请求
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // 获取表单数据
            $cardId = isset($_POST['card_id']) ? (int)$_POST['card_id'] : 0;
            $environmentId = isset($_POST['environment_id']) ? (int)$_POST['environment_id'] : 0;
            $status = isset($_POST['status']) ? (int)$_POST['status'] : 3;
            $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
            $initiatorId = isset($_POST['initiator_id']) ? trim($_POST['initiator_id']) : '';

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

            // 获取卡片信息
            $card = $this->cardModel->getCardById($cardId);
            if (!$card) {
                $errors[] = '卡片不存在';
            }

            // 检查卡片是否为TCG卡片或无系列卡片
            if ($card) {
                $isTcgCard = false;
                if (isset($card['database_file']) && $card['database_file'] === basename(TCG_CARD_DATA_PATH)) {
                    $isTcgCard = true;
                }

                if ($isTcgCard) {
                    $errors[] = '无法对TCG卡片系列发起投票';
                }

                if ($card['setcode'] == 0) {
                    $errors[] = '无法对无系列的卡片发起系列投票';
                }
            }

            // 根据严格度进行额外验证
            $strictness = defined('SERIES_VOTING_STRICTNESS') ? SERIES_VOTING_STRICTNESS : 2;
            $minReasonLength = defined('SERIES_VOTING_REASON_MIN_LENGTH') ? SERIES_VOTING_REASON_MIN_LENGTH : 400;

            if ($strictness >= 1) {
                // 需要填写理由
                if (strlen($reason) < $minReasonLength) {
                    $errors[] = "理由字数不足，至少需要 {$minReasonLength} 个字符，当前为 " . strlen($reason) . " 个字符";
                }
            }

            if ($strictness >= 2) {
                // 需要发起人身份验证
                $isAuthorized = $this->checkInitiatorAuthorization($initiatorId);
                if (!$isAuthorized) {
                    $errors[] = '您的ID不在系统作者列表中，无法发起系列投票';
                }
            }

            if ($strictness >= 3) {
                // 需要额外验证卡片作者
                $cardAuthorId = isset($_POST['card_author_id']) ? trim($_POST['card_author_id']) : '';
                if (empty($cardAuthorId)) {
                    $errors[] = '请填写卡片作者ID';
                } else {
                    $isCardAuthorValid = $this->checkCardAuthorAuthorization($cardAuthorId, $card);
                    if (!$isCardAuthorValid) {
                        $errors[] = '填写的卡片作者ID与该卡片系列的作者信息不匹配';
                    }
                }
            }

            // 如果没有错误，则创建系列投票
            if (empty($errors)) {
                $voteLink = $this->voteModel->createVote($cardId, $environmentId, $status, $reason, $initiatorId, true, $card['setcode']);

                if ($voteLink) {
                    // 重定向到投票页面
                    header('Location: ' . BASE_URL . '?controller=vote&id=' . $voteLink);
                    exit;
                } else {
                    $errors[] = '创建系列投票失败';
                }
            }

            // 如果有错误，则显示错误信息
            if (!empty($errors)) {
                $environments = Utils::getEnvironments();

                // 获取卡片在各环境中的禁限状态
                $limitStatus = [];
                foreach ($environments as $env) {
                    $limitStatus[$env['id']] = $this->cardModel->getCardLimitStatus($cardId, $env['header']);
                }

                // 获取系列中的所有卡片
                $seriesCards = [];
                if ($card && $card['setcode'] > 0) {
                    $seriesCards = $this->cardModel->getCardsBySetcode($card['setcode']);
                }

                include __DIR__ . '/../Views/layout.php';
                include __DIR__ . '/../Views/votes/create_series.php';
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

        // 检查卡片是否为TCG卡片或无系列卡片
        $isTcgCard = false;
        if (isset($card['database_file']) && $card['database_file'] === basename(TCG_CARD_DATA_PATH)) {
            $isTcgCard = true;
        }

        if ($isTcgCard) {
            header('Location: ' . BASE_URL . '?controller=card&action=detail&id=' . $cardId . '&error=' . urlencode('无法对TCG卡片系列发起投票'));
            exit;
        }

        if ($card['setcode'] == 0) {
            header('Location: ' . BASE_URL . '?controller=card&action=detail&id=' . $cardId . '&error=' . urlencode('无法对无系列的卡片发起系列投票'));
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

        // 获取卡片在各环境中的禁限状态
        $limitStatus = [];
        foreach ($environments as $env) {
            $limitStatus[$env['id']] = $this->cardModel->getCardLimitStatus($cardId, $env['header']);
        }

        // 获取系列中的所有卡片
        $seriesCards = $this->cardModel->getCardsBySetcode($card['setcode']);

        // 获取严格度信息
        $strictness = defined('SERIES_VOTING_STRICTNESS') ? SERIES_VOTING_STRICTNESS : 2;
        $minReasonLength = defined('SERIES_VOTING_REASON_MIN_LENGTH') ? SERIES_VOTING_REASON_MIN_LENGTH : 400;

        // 判断是否为TCG卡片（用于视图显示）
        $isTcgCard = false;
        if (isset($card['database_file']) && $card['database_file'] === basename(TCG_CARD_DATA_PATH)) {
            $isTcgCard = true;
        }

        // 渲染视图
        include __DIR__ . '/../Views/layout.php';
        include __DIR__ . '/../Views/votes/create_series.php';
        include __DIR__ . '/../Views/footer.php';
    }

    /**
     * 检查发起人是否在作者列表中
     *
     * @param string $initiatorId 发起人ID
     * @return bool 是否有权限
     */
    private function checkInitiatorAuthorization($initiatorId) {
        $db = Database::getInstance();

        // 检查发起人ID是否在作者映射表中（作为作者名称）
        $authorMapping = $db->getRow(
            'SELECT * FROM author_mappings WHERE author_name = :author_name',
            [':author_name' => $initiatorId]
        );

        if ($authorMapping) {
            return true;
        }

        // 检查是否在别名中（精确匹配逗号分隔的别名）
        $allMappings = $db->getRows('SELECT * FROM author_mappings WHERE alias IS NOT NULL AND alias != ""');
        foreach ($allMappings as $mapping) {
            if (!empty($mapping['alias'])) {
                $aliases = explode(',', $mapping['alias']);
                foreach ($aliases as $alias) {
                    $alias = trim($alias);
                    if ($alias === $initiatorId) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * 检查卡片作者授权
     *
     * @param string $cardAuthorId 卡片作者ID
     * @param array $card 卡片信息
     * @return bool 是否有权限
     */
    private function checkCardAuthorAuthorization($cardAuthorId, $card) {
        // 获取卡片作者信息
        $cardAuthor = $card['author'];

        // 如果卡片没有作者信息，则不允许
        if (empty($cardAuthor) || $cardAuthor === '未知作者') {
            return false;
        }

        // 检查填写的作者ID是否与作者名称或别名匹配
        $db = Database::getInstance();
        $cardPrefix = substr((string)$card['id'], 0, 3);

        // 查询数据库中的作者映射
        $authorMapping = $db->getRow(
            'SELECT * FROM author_mappings WHERE card_prefix = :card_prefix',
            [':card_prefix' => $cardPrefix]
        );

        if ($authorMapping) {
            // 检查是否匹配作者名称或别名
            $authorName = $authorMapping['author_name'];
            $alias = $authorMapping['alias'];

            if ($cardAuthorId === $authorName || (!empty($alias) && $cardAuthorId === $alias)) {
                return true;
            }
        }

        // 如果数据库中没有记录，检查是否与卡片作者信息匹配
        if ($cardAuthorId === $cardAuthor) {
            return true;
        }

        return false;
    }

    /**
     * 删除投票记录
     */
    public function deleteRecord() {
        // 检查是否为POST请求
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => '不支持的请求方法']);
            return;
        }

        // 获取参数
        $recordId = isset($_POST['record_id']) ? (int)$_POST['record_id'] : 0;
        $voteLink = isset($_POST['vote_link']) ? trim($_POST['vote_link']) : '';

        if ($recordId <= 0 || empty($voteLink)) {
            echo json_encode(['success' => false, 'message' => '参数错误']);
            return;
        }

        // 获取投票记录
        $record = $this->voteModel->getVoteRecordById($recordId);
        if (!$record) {
            echo json_encode(['success' => false, 'message' => '投票记录不存在']);
            return;
        }

        // 获取投票信息
        $vote = $this->voteModel->getVoteByLink($voteLink);
        if (!$vote || $vote['id'] != $record['vote_id']) {
            echo json_encode(['success' => false, 'message' => '投票信息不匹配']);
            return;
        }

        // 检查投票是否已关闭
        if ($vote['is_closed']) {
            echo json_encode(['success' => false, 'message' => '投票已关闭，无法删除投票记录']);
            return;
        }

        // 权限检查
        $auth = Auth::getInstance();
        $canDelete = false;
        $operatorInfo = '';

        // 检查是否为管理员（等级1以上）
        if ($auth->isLoggedIn() && $auth->hasPermission(1)) {
            $canDelete = true;
            $operatorInfo = 'Admin:' . $auth->getCurrentUsername();
        }
        // 检查是否为投票者本人（如果配置允许）
        elseif (ALLOW_VOTE_DELETION) {
            $currentIp = Utils::getClientIp();
            $currentIdentifier = Utils::generateVoterIdentifier($currentIp, $record['user_id']);

            if ($currentIdentifier === $record['identifier']) {
                $canDelete = true;
                $operatorInfo = 'User:' . $record['identifier'];
            }
        }

        if (!$canDelete) {
            echo json_encode(['success' => false, 'message' => '您没有权限删除此投票记录']);
            return;
        }

        // 记录删除日志
        $this->logVoteDeletion($record, $vote, $operatorInfo);

        // 删除投票记录
        $result = $this->voteModel->deleteVoteRecord($recordId);

        if ($result) {
            echo json_encode(['success' => true, 'message' => '投票记录已删除']);
        } else {
            echo json_encode(['success' => false, 'message' => '删除失败']);
        }
    }

    /**
     * 记录投票删除日志
     *
     * @param array $record 投票记录
     * @param array $vote 投票信息
     * @param string $operatorInfo 操作者信息
     */
    private function logVoteDeletion($record, $vote, $operatorInfo) {
        // 确保日志目录存在
        $logDir = __DIR__ . '/../../logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        // 准备日志内容
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'operator' => $operatorInfo,
            'vote_info' => [
                'vote_id' => $vote['id'],
                'vote_link' => $vote['vote_link'],
                'card_id' => $vote['card_id'],
                'environment_id' => $vote['environment_id'],
                'vote_cycle' => $vote['vote_cycle']
            ],
            'deleted_record' => [
                'record_id' => $record['id'],
                'user_id' => $record['user_id'],
                'identifier' => $record['identifier'],
                'status' => $record['status'],
                'comment' => $record['comment'],
                'created_at' => $record['created_at']
            ]
        ];

        // 写入日志文件
        $logFile = $logDir . '/deletedVote_' . date('Y-m-d') . '.txt';
        $logContent = json_encode($logData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";

        file_put_contents($logFile, $logContent, FILE_APPEND | LOCK_EX);
    }
}
