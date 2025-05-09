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
     * 构造函数
     */
    public function __construct() {
        $this->userModel = new User();
        $this->authorStatsModel = new AuthorStats();
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
}
