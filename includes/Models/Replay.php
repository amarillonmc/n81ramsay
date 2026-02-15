<?php
/**
 * 录像模型
 *
 * 处理录像相关的数据操作
 */
class Replay {
    /**
     * 数据库实例
     * @var Database
     */
    private $db;

    /**
     * 录像文件目录
     * @var string
     */
    private $replayPath;

    /**
     * 构造函数
     */
    public function __construct() {
        $this->db = Database::getInstance();
        $this->replayPath = defined('REPLAY_PATH') ? REPLAY_PATH : __DIR__ . '/../../replay';
    }

    /**
     * 扫描录像目录并获取所有录像文件
     *
     * @param int $page 页码
     * @param int $perPage 每页数量
     * @return array 录像列表和分页信息
     */
    public function getReplayList($page = 1, $perPage = 20) {
        $replays = [];
        $offset = ($page - 1) * $perPage;

        if (!is_dir($this->replayPath)) {
            return [
                'replays' => [],
                'total' => 0,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => 0
            ];
        }

        $files = glob($this->replayPath . '/*.yrp');
        $yrp2Files = glob($this->replayPath . '/*.yrp2');
        $files = array_merge($files, $yrp2Files);

        usort($files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        $total = count($files);
        $totalPages = $total > 0 ? ceil($total / $perPage) : 0;

        if ($page > $totalPages && $totalPages > 0) {
            $page = $totalPages;
            $offset = ($page - 1) * $perPage;
        }

        $pagedFiles = array_slice($files, $offset, $perPage);

        foreach ($pagedFiles as $file) {
            $replayInfo = $this->parseReplayHeader($file);
            if ($replayInfo) {
                $replays[] = $replayInfo;
            }
        }

        return [
            'replays' => $replays,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => $totalPages
        ];
    }

    /**
     * 解析 YRP 文件头获取基本信息
     *
     * @param string $filePath 文件路径
     * @return array|null 录像信息
     */
    public function parseReplayHeader($filePath) {
        if (!file_exists($filePath)) {
            return null;
        }

        $fp = fopen($filePath, 'rb');
        if (!$fp) {
            return null;
        }

        try {
            $header = fread($fp, 1);
            if (strlen($header) < 1) {
                return null;
            }

            $version = ord($header[0]);
            $isYrp2 = ($version === 0x87);

            $id = fread($fp, 4);
            if (strlen($id) < 4) {
                return null;
            }
            $id = unpack('V', $id)[1];

            $versionBytes = fread($fp, 4);
            if (strlen($versionBytes) < 4) {
                return null;
            }
            $versionFlag = unpack('V', $versionBytes)[1];

            $flagBytes = fread($fp, 4);
            if (strlen($flagBytes) < 4) {
                return null;
            }
            $flag = unpack('V', $flagBytes)[1];

            $duelRule = $this->getDuelRule($flag);

            $playerNames = [];
            for ($i = 0; $i < 4; $i++) {
                $nameLen = fread($fp, 1);
                if (strlen($nameLen) < 1) {
                    break;
                }
                $len = ord($nameLen);
                if ($len > 0) {
                    $name = fread($fp, $len * 2);
                    $name = mb_convert_encoding($name, 'UTF-8', 'UTF-16LE');
                    $name = rtrim($name, "\0");
                    $playerNames[] = $name;
                } else {
                    $playerNames[] = '';
                }
            }

            fclose($fp);

            return [
                'id' => md5(basename($filePath)),
                'filename' => basename($filePath),
                'file_path' => $filePath,
                'player_names' => array_filter($playerNames),
                'duel_rule' => $duelRule,
                'is_yrp2' => $isYrp2,
                'file_size' => filesize($filePath),
                'modified_time' => date('Y-m-d H:i:s', filemtime($filePath))
            ];
        } catch (Exception $e) {
            fclose($fp);
            Utils::debug('解析录像头失败', ['错误' => $e->getMessage(), '文件' => $filePath]);
            return null;
        }
    }

    /**
     * 根据标志获取决斗规则
     *
     * @param int $flag 标志位
     * @return string 决斗规则名称
     */
    private function getDuelRule($flag) {
        $rules = [
            0 => 'OCG',
            1 => 'TCG',
            2 => 'OCG (单局)',
            3 => 'TCG (单局)',
            4 => 'OCG (匹配)',
            5 => 'TCG (匹配)',
            6 => 'AI'
        ];

        $ruleIndex = $flag & 0x7;
        return isset($rules[$ruleIndex]) ? $rules[$ruleIndex] : '未知';
    }

    /**
     * 获取录像文件内容
     *
     * @param string $filename 文件名
     * @return string|null 文件内容
     */
    public function getReplayContent($filename) {
        $filePath = $this->replayPath . '/' . basename($filename);

        if (!file_exists($filePath)) {
            return null;
        }

        if (!preg_match('/\.yrp2?$/', $filename)) {
            return null;
        }

        return file_get_contents($filePath);
    }

    /**
     * 获取录像文件路径
     *
     * @param string $filename 文件名
     * @return string|null 文件路径
     */
    public function getReplayPath($filename) {
        $filePath = $this->replayPath . '/' . basename($filename);

        if (!file_exists($filePath)) {
            return null;
        }

        if (!preg_match('/\.yrp2?$/', $filename)) {
            return null;
        }

        return $filePath;
    }

    /**
     * 获取所有卡片数据库文件路径
     *
     * @return array 数据库文件列表
     */
    public function getCardDatabaseFiles() {
        $dbs = [];

        if (defined('CARD_DATA_PATH') && is_dir(CARD_DATA_PATH)) {
            $diyDbs = glob(CARD_DATA_PATH . '/*.cdb');
            if ($diyDbs) {
                foreach ($diyDbs as $db) {
                    $excluded = defined('EXCLUDED_CARD_DATABASES') ? json_decode(EXCLUDED_CARD_DATABASES, true) : [];
                    if (!in_array(basename($db), $excluded)) {
                        $dbs[] = [
                            'path' => $db,
                            'type' => 'diy',
                            'name' => basename($db)
                        ];
                    }
                }
            }
        }

        if (defined('TCG_CARD_DATA_PATH') && file_exists(TCG_CARD_DATA_PATH)) {
            $dbs[] = [
                'path' => TCG_CARD_DATA_PATH,
                'type' => 'tcg',
                'name' => basename(TCG_CARD_DATA_PATH)
            ];
        }

        return $dbs;
    }

    /**
     * 合并所有卡片数据库为一个 SQL.js 兼容的 ArrayBuffer
     * 实际上返回每个数据库的信息，前端分别加载后合并
     *
     * @return array 数据库信息列表
     */
    public function getCardDatabasesInfo() {
        return $this->getCardDatabaseFiles();
    }

    /**
     * 获取单个卡片数据库内容
     *
     * @param string $name 数据库文件名
     * @return string|null 数据库内容
     */
    public function getCardDatabaseContent($name) {
        $dbs = $this->getCardDatabaseFiles();
        foreach ($dbs as $db) {
            if ($db['name'] === $name) {
                return file_get_contents($db['path']);
            }
        }
        return null;
    }

    /**
     * 获取卡图路径信息
     *
     * @return array 卡图路径配置
     */
    public function getCardImagePaths() {
        return [
            'diy' => defined('CARD_DATA_PATH') ? CARD_DATA_PATH . '/pics' : null,
            'tcg' => defined('TCG_CARD_IMAGE_PATH') && TCG_CARD_IMAGE_PATH ? TCG_CARD_IMAGE_PATH : null
        ];
    }

    /**
     * 获取卡图URL配置
     *
     * @return array 卡图URL配置
     */
    public function getCardImageUrls() {
        $baseUrl = defined('BASE_URL') ? BASE_URL : '/';
        return [
            'diy' => $baseUrl . 'api/cardimage?type=diy&id=',
            'tcg' => $baseUrl . 'api/cardimage?type=tcg&id='
        ];
    }
}
