<?php
/**
 * 作者控制器
 *
 * 处理作者相关的请求
 */
class AuthorController {
    /**
     * 用户模型
     * @var User
     */
    private $userModel;

    /**
     * 作者统计模型
     * @var AuthorStats
     */
    private $authorStatsModel;

    /**
     * 卡片解析器
     * @var CardParser
     */
    private $cardParser;

    /**
     * 构造函数
     */
    public function __construct() {
        $this->userModel = new User();
        $this->authorStatsModel = new AuthorStats();
        $this->cardParser = CardParser::getInstance();
    }

    /**
     * 作者光荣榜首页
     */
    public function index() {
        // 检查功能是否启用
        if (!AUTHOR_HALL_OF_FAME_ENABLED) {
            header('Location: ' . BASE_URL);
            exit;
        }

        // 获取作者统计数据
        $authorStats = $this->authorStatsModel->getAuthorStats();

        // 高亮阈值
        $highlightThreshold = AUTHOR_HALL_OF_FAME_HIGHLIGHT_THRESHOLD;

        // 生成时间
        $generatedTime = date('Y-m-d H:i:s');

        // 渲染视图
        include __DIR__ . '/../Views/layout.php';
        include __DIR__ . '/../Views/authors/index.php';
        include __DIR__ . '/../Views/footer.php';
    }

    /**
     * 更新作者光荣榜
     */
    public function update() {
        // 检查功能是否启用
        if (!AUTHOR_HALL_OF_FAME_ENABLED) {
            header('Location: ' . BASE_URL);
            exit;
        }

        // 要求管理员权限
        $this->userModel->requirePermission(1);

        // 更新作者光荣榜
        $success = $this->authorStatsModel->updateAuthorHallOfFame();

        // 设置消息
        $message = $success ? '作者光荣榜更新成功' : '作者光荣榜更新失败';

        // 重定向到作者光荣榜页面
        header('Location: ' . BASE_URL . '?controller=author&message=' . urlencode($message));
        exit;
    }

    /**
     * 生成作者光荣榜调试内容
     */
    public function debug() {
        // 检查功能是否启用
        if (!AUTHOR_HALL_OF_FAME_ENABLED) {
            header('Location: ' . BASE_URL);
            exit;
        }

        // 检查是否处于调试模式
        if (!DEBUG_MODE) {
            header('Location: ' . BASE_URL . '?controller=author');
            exit;
        }

        // 要求管理员权限
        $this->userModel->requirePermission(1);

        // 获取作者统计数据
        $authorStats = $this->authorStatsModel->getAuthorStats();

        // 创建带有时间戳的ranking文件夹
        $timestamp = date('Y-m-d_H-i-s');
        $debugDir = __DIR__ . '/../../logs/ranking_' . $timestamp;

        // 确保logs目录存在
        if (!file_exists(__DIR__ . '/../../logs')) {
            mkdir(__DIR__ . '/../../logs', 0777, true);
        }

        // 创建ranking目录
        if (!file_exists($debugDir)) {
            mkdir($debugDir, 0777, true);
        }

        // 获取标准环境禁卡列表
        $lflist = $this->cardParser->getLflist();

        // 获取环境列表
        $environments = Utils::getEnvironments();
        $standardEnvironment = null;

        // 查找标准环境
        foreach ($environments as $env) {
            if ($env['text'] === '标准环境') {
                $standardEnvironment = $env;
                break;
            }
        }

        $standardBanlist = [];
        if ($standardEnvironment) {
            $standardBanlist = $lflist[$standardEnvironment['header']] ?? [];
        }

        // 为每个作者生成调试文件
        $fileCount = 0;
        foreach ($authorStats as $author) {
            $authorName = $author['name'];

            // 生成安全的文件名
            // 1. 替换文件名中的非法字符
            $safeAuthorName = preg_replace('/[\\\\\/\:\*\?\"\<\>\|]/', '_', $authorName);
            // 2. 处理中文和特殊字符，使用MD5哈希确保文件名唯一且安全
            if (preg_match('/[^\x20-\x7E]/', $safeAuthorName)) {
                // 如果包含非ASCII字符，使用作者名的MD5哈希作为文件名
                $safeAuthorName = md5($authorName) . '_' . mb_substr($safeAuthorName, 0, 10, 'UTF-8');
            }
            // 3. 限制文件名长度
            if (strlen($safeAuthorName) > 50) {
                $safeAuthorName = substr($safeAuthorName, 0, 50);
            }

            $debugFile = $debugDir . '/' . $safeAuthorName . '.txt';

            // 使用UTF-8编码保存内容
            $content = "作者: {$authorName}\n";
            $content .= "卡片总数: {$author['total_cards']}\n";
            $content .= "禁卡数量: {$author['banned_cards']}\n";
            $content .= "禁卡比例: {$author['banned_percentage']}%\n";
            $content .= "禁止系列数: {$author['banned_series']}\n\n";

            $content .= "禁卡列表:\n";
            if (!empty($author['banned_cards_list'])) {
                foreach ($author['banned_cards_list'] as $bannedCard) {
                    // 确保banned_cards_list中的元素是数组并且包含id和name
                    if (is_array($bannedCard) && isset($bannedCard['id'])) {
                        $cardId = $bannedCard['id'];
                        $cardName = isset($bannedCard['name']) ? $bannedCard['name'] : '';

                        $status = isset($standardBanlist[$cardId]) ? $standardBanlist[$cardId]['status'] : 3;
                        $statusText = '';
                        switch ($status) {
                            case 0:
                                $statusText = '禁止';
                                break;
                            case 1:
                                $statusText = '限制';
                                break;
                            case 2:
                                $statusText = '准限制';
                                break;
                            default:
                                $statusText = '无限制';
                                break;
                        }

                        $content .= "卡片ID: {$cardId}, 名称: {$cardName}, 状态: {$statusText}\n";
                    } else {
                        // 如果banned_cards_list中的元素不是预期的格式，记录错误信息
                        $content .= "错误的卡片数据格式: " . print_r($bannedCard, true) . "\n";
                    }
                }
            } else {
                $content .= "无禁卡\n";
            }

            // 使用UTF-8编码写入文件
            // 先检查目录是否存在
            $fileDir = dirname($debugFile);
            if (!file_exists($fileDir)) {
                mkdir($fileDir, 0777, true);
            }

            // 使用二进制模式写入，避免编码问题
            $fp = fopen($debugFile, 'wb');
            if ($fp) {
                // 添加UTF-8 BOM标记
                fwrite($fp, "\xEF\xBB\xBF");
                fwrite($fp, $content);
                fclose($fp);
                $fileCount++;
            } else {
                // 记录错误
                Utils::debug('无法创建调试文件', [
                    '文件路径' => $debugFile,
                    '作者名' => $authorName,
                    '安全文件名' => $safeAuthorName
                ]);
            }
        }

        // 记录调试信息
        Utils::debug('生成作者光荣榜调试内容', [
            '时间戳' => $timestamp,
            '文件数量' => $fileCount,
            '保存目录' => $debugDir
        ]);

        // 设置会话消息，避免使用URL参数传递长消息
        $_SESSION['author_debug_message'] = "成功生成{$fileCount}个作者调试文件，保存在logs/ranking_{$timestamp}目录下";

        // 确保没有输出缓冲区中的内容
        if (ob_get_length()) {
            ob_end_clean();
        }

        // 重定向到作者光荣榜页面
        header('Location: ' . BASE_URL . '?controller=author');
        exit;
    }
}
