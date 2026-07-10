<?php
/**
 * 录像控制器
 *
 * 处理录像相关的请求
 */
class ReplayController {
    /**
     * 录像模型
     * @var Replay
     */
    private $replayModel;

    /**
     * 卡片模型
     * @var Card
     */
    private $cardModel;

    /**
     * 构造函数
     */
    public function __construct() {
        $this->replayModel = new Replay();
        $this->cardModel = new Card();
    }

    /**
     * 录像列表页面
     */
    public function index() {
        if (!defined('REPLAY_ENABLED') || !REPLAY_ENABLED) {
            $_SESSION['error_message'] = '录像功能未启用';
            Utils::redirect('?controller=home');
            return;
        }

        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = defined('REPLAYS_PER_PAGE') ? REPLAYS_PER_PAGE : 20;
        $cursor = Utils::getSafeParam($_GET, 'cursor', 'string', null, 16);

        $replayError = null;
        try {
            $result = $this->replayModel->getReplayList($page, $perPage, $cursor);
        } catch (Exception $e) {
            Utils::debug('获取录像列表失败', ['错误' => $e->getMessage()]);
            $result = $this->getEmptyReplayResult($page, $perPage);
            $replayError = '无法读取 srvpro2 录像数据，请管理员检查 PostgreSQL 与 API 配置。';
        }

        $pageTitle = '录像回放 - ' . SITE_TITLE;
        include __DIR__ . '/../Views/layout.php';
        include __DIR__ . '/../Views/replays/index.php';
        include __DIR__ . '/../Views/footer.php';
    }

    /**
     * 播放器页面
     */
    public function play() {
        if (!defined('REPLAY_ENABLED') || !REPLAY_ENABLED) {
            $_SESSION['error_message'] = '录像功能未启用';
            Utils::redirect('?controller=home');
            return;
        }

        $filename = Utils::getSafeParam($_GET, 'file', 'string', null, 260);

        if (!$filename || !$this->isValidReplayFilename($filename)) {
            $_SESSION['error_message'] = '录像文件不存在';
            Utils::redirect('?controller=replay');
            return;
        }

        try {
            $replayInfo = $this->replayModel->getReplayInfo($filename);
        } catch (Exception $e) {
            Utils::debug('获取录像详情失败', ['错误' => $e->getMessage(), '文件' => $filename]);
            $_SESSION['error_message'] = '无法读取 srvpro2 录像数据，请稍后重试';
            Utils::redirect('?controller=replay');
            return;
        }

        if (!$replayInfo) {
            $_SESSION['error_message'] = '录像不存在、尚未结束或已被删除';
            Utils::redirect('?controller=replay');
            return;
        }

        $cardDbs = $this->replayModel->getCardDatabasesInfo();
        $imageUrls = $this->replayModel->getCardImageUrls();

        $playerNames = array_slice($replayInfo['player_names'], 0, 2);
        $pageTitle = '播放录像 - ' . implode(' vs ', $playerNames) . ' - ' . SITE_TITLE;
        
        include __DIR__ . '/../Views/layout.php';
        include __DIR__ . '/../Views/replays/player.php';
        include __DIR__ . '/../Views/footer.php';
    }

    /**
     * API: 获取录像列表 (JSON)
     */
    public function list() {
        header('Content-Type: application/json');

        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = isset($_GET['per_page']) ? min(100, max(1, (int)$_GET['per_page'])) : 20;
        $cursor = Utils::getSafeParam($_GET, 'cursor', 'string', null, 16);

        try {
            $result = $this->replayModel->getReplayList($page, $perPage, $cursor);
            foreach ($result['replays'] as &$replay) {
                unset($replay['file_path']);
            }
            unset($replay);
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            Utils::debug('录像列表 API 失败', ['错误' => $e->getMessage()]);
            http_response_code(502);
            echo json_encode(['error' => '无法读取 srvpro2 录像数据'], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * API: 获取录像文件
     */
    public function file() {
        $filename = Utils::getSafeParam($_GET, 'file', 'string', null, 260);

        if (!$filename) {
            header('HTTP/1.0 400 Bad Request');
            echo json_encode(['error' => '缺少文件名参数']);
            return;
        }

        if (!$this->isValidReplayFilename($filename)) {
            header('HTTP/1.0 400 Bad Request');
            echo json_encode(['error' => '无效的文件名']);
            return;
        }

        try {
            $content = $this->replayModel->getReplayContent($filename);
        } catch (Exception $e) {
            Utils::debug('下载动态录像失败', ['错误' => $e->getMessage(), '文件' => $filename]);
            http_response_code(502);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'srvpro2 动态录像服务暂时不可用'], JSON_UNESCAPED_UNICODE);
            return;
        }

        if ($content === null) {
            header('HTTP/1.0 404 Not Found');
            echo json_encode(['error' => '文件不存在']);
            return;
        }

        $mimeType = 'application/octet-stream';

        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . strlen($content));
        header('Content-Disposition: attachment; filename*=UTF-8\'\'' . rawurlencode($filename));
        header('Cache-Control: public, max-age=3600');

        echo $content;
    }

    /**
     * 验证录像文件名是否有效
     *
     * @param string $filename 文件名
     * @return bool 是否有效
     */
    private function isValidReplayFilename($filename) {
        if (preg_match('/\.\./', $filename)) {
            return false;
        }

        if (defined('SRVPRO2_INTEGRATION_ENABLED') && SRVPRO2_INTEGRATION_ENABLED) {
            if (!preg_match('/^([1-9][0-9]{0,15})\.yrp$/', $filename, $matches)) {
                return false;
            }

            return strlen($matches[1]) < 16 || strcmp($matches[1], '9007199254740991') <= 0;
        }

        if (!preg_match('/\.yrp2?$/i', $filename)) {
            return false;
        }
        
        return true;
    }

    /**
     * API: 获取卡片数据库列表
     */
    public function databases() {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');

        $dbs = $this->replayModel->getCardDatabasesInfo();

        $baseUrl = defined('BASE_URL') ? BASE_URL : '/';
        foreach ($dbs as &$db) {
            $db['url'] = $baseUrl . '?controller=replay&action=database&name=' . urlencode($db['name']);
            unset($db['path']);
        }
        unset($db);

        $scriptUrl = $baseUrl . '?controller=replay&action=script&path=';

        echo json_encode([
            'databases' => $dbs,
            'image_url' => $this->replayModel->getCardImageUrls(),
            'script_url' => $scriptUrl
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * API: 获取单个卡片数据库文件
     */
    public function database() {
        $name = isset($_GET['name']) ? $_GET['name'] : null;

        if (!$name) {
            header('HTTP/1.0 400 Bad Request');
            echo json_encode(['error' => '缺少数据库名参数']);
            return;
        }

        $name = basename($name);
        if (!preg_match('/^[\w\-]+\.cdb$/i', $name)) {
            header('HTTP/1.0 400 Bad Request');
            echo json_encode(['error' => '无效的数据库名']);
            return;
        }

        $content = $this->replayModel->getCardDatabaseContent($name);

        if ($content === null) {
            header('HTTP/1.0 404 Not Found');
            echo json_encode(['error' => '数据库不存在']);
            return;
        }

        header('Content-Type: application/octet-stream');
        header('Content-Length: ' . strlen($content));
        header('Cache-Control: public, max-age=86400');

        echo $content;
    }

    /**
     * API: 获取脚本文件
     */
    public function script() {
        $path = isset($_GET['path']) ? $_GET['path'] : null;

        if (!$path) {
            header('HTTP/1.0 400 Bad Request');
            echo json_encode(['error' => '缺少脚本路径参数']);
            return;
        }

        // 安全检查：防止目录遍历攻击
        $path = str_replace(['../', '..\\', "\0"], '', $path);
        
        // 验证路径格式
        if (!preg_match('#^(script/|single/|patch/|patches/)[\w/\-\.]+\.lua$#i', $path)) {
            header('HTTP/1.0 400 Bad Request');
            echo json_encode(['error' => '无效的脚本路径']);
            return;
        }

        $scriptPath = null;

        // 先在 DIY 脚本目录查找
        if (defined('CARD_DATA_PATH')) {
            $diyPath = CARD_DATA_PATH . '/' . $path;
            if (file_exists($diyPath) && is_file($diyPath)) {
                $scriptPath = $diyPath;
            }
        }

        // 找不到则查找 TCG 脚本目录
        if (!$scriptPath && defined('TCG_SCRIPT_PATH') && TCG_SCRIPT_PATH) {
            $tcgPath = TCG_SCRIPT_PATH . '/' . $path;
            if (file_exists($tcgPath) && is_file($tcgPath)) {
                $scriptPath = $tcgPath;
            }
        }

        // 也支持直接从 TCG_SCRIPT_PATH 根目录查找
        if (!$scriptPath && defined('TCG_SCRIPT_PATH') && TCG_SCRIPT_PATH) {
            $tcgPath = TCG_SCRIPT_PATH . '/' . basename($path);
            if (file_exists($tcgPath) && is_file($tcgPath)) {
                $scriptPath = $tcgPath;
            }
        }

        if (!$scriptPath) {
            header('HTTP/1.0 404 Not Found');
            echo json_encode(['error' => '脚本文件不存在']);
            return;
        }

        $content = file_get_contents($scriptPath);

        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Length: ' . strlen($content));
        header('Cache-Control: public, max-age=86400');

        echo $content;
    }

    /**
     * API: 获取卡图
     * 自动判断卡片类型：先查找DIY卡图路径，找不到再查找TCG卡图路径
     */
    public function cardimage() {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        if ($id <= 0) {
            $this->outputDefaultCardImage();
            return;
        }

        $imagePath = null;

        // 优先查找 DIY 卡图路径
        if (defined('CARD_DATA_PATH')) {
            $diyPath = CARD_DATA_PATH . '/pics/' . $id . '.jpg';
            if (file_exists($diyPath)) {
                $imagePath = $diyPath;
            }
            
            if (!$imagePath) {
                $thumbPath = CARD_DATA_PATH . '/pics/thumbnail/' . $id . '.jpg';
                if (file_exists($thumbPath)) {
                    $imagePath = $thumbPath;
                }
            }
        }

        // 找不到则查找 TCG 卡图路径
        if (!$imagePath && defined('TCG_CARD_IMAGE_PATH') && TCG_CARD_IMAGE_PATH) {
            $tcgPath = TCG_CARD_IMAGE_PATH . '/' . $id . '.jpg';
            if (file_exists($tcgPath)) {
                $imagePath = $tcgPath;
            }
        }

        if ($imagePath && file_exists($imagePath)) {
            header('Content-Type: image/jpeg');
            header('Content-Length: ' . filesize($imagePath));
            header('Cache-Control: public, max-age=604800');
            readfile($imagePath);
        } else {
            $this->outputDefaultCardImage();
        }
    }

    /**
     * 输出默认卡图
     */
    private function outputDefaultCardImage() {
        $defaultPath = __DIR__ . '/../../assets/images/card_back.jpg';
        if (file_exists($defaultPath)) {
            header('Content-Type: image/jpeg');
            header('Content-Length: ' . filesize($defaultPath));
            header('Cache-Control: public, max-age=604800');
            readfile($defaultPath);
        } else {
            header('HTTP/1.0 404 Not Found');
        }
    }

    /**
     * 构造空录像分页结果
     *
     * @param int $page 页码
     * @param int $perPage 每页数量
     * @return array 空结果
     */
    private function getEmptyReplayResult($page, $perPage) {
        return [
            'replays' => [],
            'total' => 0,
            'page' => max(1, (int)$page),
            'per_page' => max(1, (int)$perPage),
            'total_pages' => 0,
            'has_next' => false,
            'next_cursor' => null,
            'source' => defined('SRVPRO2_INTEGRATION_ENABLED') && SRVPRO2_INTEGRATION_ENABLED
                ? 'srvpro2'
                : 'legacy_files'
        ];
    }
}
