<?php
/**
 * 禁卡表控制器
 *
 * 处理禁卡表相关的请求
 */
class BanlistController {
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
     * 构造函数
     */
    public function __construct() {
        $this->userModel = new User();
        $this->voteModel = new Vote();
    }

    /**
     * 禁卡表管理
     */
    public function index() {
        // 要求管理员权限
        $this->userModel->requirePermission(1);

        // 获取投票周期
        $db = Database::getInstance();
        $voteCycle = $db->getCurrentVoteCycle();

        // 获取环境列表
        $environments = Utils::getEnvironments();

        // 获取投票结果
        $results = $this->voteModel->getVoteResults($voteCycle);

        // 按环境分组
        $groupedResults = [];
        foreach ($environments as $env) {
            $groupedResults[$env['id']] = [];
        }

        foreach ($results as $result) {
            $environmentId = $result['environment_id'];
            if (isset($groupedResults[$environmentId])) {
                $groupedResults[$environmentId][] = $result;
            }
        }

        // 渲染视图
        include __DIR__ . '/../Views/layout.php';
        include __DIR__ . '/../Views/admin/banlist.php';
        include __DIR__ . '/../Views/footer.php';
    }

    /**
     * 生成禁卡表
     */
    public function generate() {
        // 要求管理员权限
        $this->userModel->requirePermission(1);

        // 检查是否是POST请求
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // 获取表单数据
            $environmentId = isset($_POST['environment_id']) ? (int)$_POST['environment_id'] : 0;

            // 生成禁卡表文本（机器可读格式）
            $lflistText = $this->voteModel->generateLflistText($environmentId);

            // 生成禁卡表文本（人类可读格式）
            $readableText = $this->voteModel->generateReadableBanlistText($environmentId);

            // 渲染视图
            include __DIR__ . '/../Views/layout.php';
            include __DIR__ . '/../Views/admin/generate.php';
            include __DIR__ . '/../Views/footer.php';
            return;
        }

        // 如果不是POST请求，则重定向到禁卡表管理页面
        header('Location: ' . BASE_URL . '?controller=admin&action=banlist');
        exit;
    }

    /**
     * 更新禁卡表
     */
    public function update() {
        // 要求管理员权限
        $this->userModel->requirePermission(2);

        // 检查是否是POST请求
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // 获取表单数据
            $environmentId = isset($_POST['environment_id']) ? (int)$_POST['environment_id'] : 0;
            $lflistText = isset($_POST['lflist_text']) ? trim($_POST['lflist_text']) : '';

            // 获取环境信息
            $environment = Utils::getEnvironmentById($environmentId);

            if (!$environment) {
                // 如果环境不存在，则重定向到禁卡表管理页面
                header('Location: ' . BASE_URL . '?controller=admin&action=banlist');
                exit;
            }

            // 读取lflist.conf文件
            $lflistFile = CARD_DATA_PATH . '/lflist.conf';
            $content = file_get_contents($lflistFile);

            // 查找环境标题
            $pattern = '/(' . preg_quote($environment['header'], '/') . '.*?)(?=!|\z)/s';

            if (preg_match($pattern, $content, $matches)) {
                // 获取投票结果
                $results = $this->voteModel->getVoteResults();
                $cardParser = CardParser::getInstance();

                // 找出需要从禁卡表中移除的卡片（状态变为无限制的卡片）
                $cardsToRemove = [];
                foreach ($results as $result) {
                    if ($result['environment_id'] == $environmentId && $result['final_status'] == 3) {
                        // 获取卡片当前的禁限状态
                        $currentStatus = $cardParser->getCardLimitStatus($result['card_id'], $environment['header']);

                        // 如果当前状态不是无限制，但投票结果是无限制，则需要从禁卡表中移除
                        if ($currentStatus != 3) {
                            $cardsToRemove[] = $result['card_id'];
                        }
                    }
                }

                // 替换环境部分
                $content = str_replace($matches[1], $lflistText . "\n", $content);

                // 写入文件
                file_put_contents($lflistFile, $content);

                // 设置成功消息
                $_SESSION['success_message'] = '禁卡表已更新';
            } else {
                // 如果找不到环境部分，则添加到文件末尾
                $content .= "\n" . $lflistText . "\n";

                // 写入文件
                file_put_contents($lflistFile, $content);

                // 设置成功消息
                $_SESSION['success_message'] = '禁卡表已添加';
            }

            // 重定向到禁卡表管理页面
            header('Location: ' . BASE_URL . '?controller=admin&action=banlist');
            exit;
        }

        // 如果不是POST请求，则重定向到禁卡表管理页面
        header('Location: ' . BASE_URL . '?controller=admin&action=banlist');
        exit;
    }

    /**
     * 重置投票
     */
    public function reset() {
        // 要求管理员权限
        $this->userModel->requirePermission(2);

        // 检查是否是POST请求
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // 关闭所有投票
            $this->voteModel->closeAllVotes();

            // 增加投票周期
            $this->voteModel->incrementVoteCycle();

            // 设置成功消息
            $_SESSION['success_message'] = '投票已重置，投票周期已增加';

            // 重定向到禁卡表管理页面
            header('Location: ' . BASE_URL . '?controller=admin&action=banlist');
            exit;
        }

        // 如果不是POST请求，则重定向到禁卡表管理页面
        header('Location: ' . BASE_URL . '?controller=admin&action=banlist');
        exit;
    }
}
