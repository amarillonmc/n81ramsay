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
     * 优化版：从文件名提取信息，不解析文件内容
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

        $iterator = new FilesystemIterator($this->replayPath, FilesystemIterator::SKIP_DOTS);
        
        $files = [];
        foreach ($iterator as $file) {
            if ($file->isFile() && preg_match('/\.yrp2?$/i', $file->getFilename())) {
                $files[] = [
                    'filename' => $file->getFilename(),
                    'mtime' => $file->getMTime(),
                    'size' => $file->getSize()
                ];
            }
        }

        usort($files, function($a, $b) {
            return $b['mtime'] - $a['mtime'];
        });

        $total = count($files);
        $totalPages = $total > 0 ? ceil($total / $perPage) : 0;

        if ($page > $totalPages && $totalPages > 0) {
            $page = $totalPages;
            $offset = ($page - 1) * $perPage;
        }

        $pagedFiles = array_slice($files, $offset, $perPage);

        foreach ($pagedFiles as $fileData) {
            $replayInfo = $this->parseReplayInfoFromFilename($fileData);
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
     * 从文件名解析录像信息
     * 文件名格式: 2026-02-13 18-30-45 玩家1 VS 玩家2.yrp
     *
     * @param array $fileData 文件数据
     * @return array|null 录像信息
     */
    public function parseReplayInfoFromFilename($fileData) {
        $filename = $fileData['filename'];
        
        $info = [
            'id' => md5($filename),
            'filename' => $filename,
            'file_path' => $this->replayPath . '/' . $filename,
            'player_names' => [],
            'duel_rule' => '未知',
            'is_yrp2' => preg_match('/\.yrp2$/i', $filename) === 1,
            'file_size' => $fileData['size'],
            'modified_time' => date('Y-m-d H:i:s', $fileData['mtime'])
        ];

        if (preg_match('/\.yrp2$/i', $filename)) {
            $info['duel_rule'] = 'YRP2';
        } else {
            $info['duel_rule'] = '标准';
        }

        if (preg_match('/^(.+?)\s+VS\s+(.+?)(?:\s+\d+)?\.yrp2?$/i', $filename, $matches)) {
            $player1 = trim($matches[1]);
            $player2 = trim($matches[2]);
            
            $datetime = null;
            if (preg_match('/^(\d{4}-\d{2}-\d{2}\s+\d{2}-\d{2}-\d{2})\s+(.+)$/', $player1, $dtMatches)) {
                $datetime = $dtMatches[1];
                $player1 = trim($dtMatches[2]);
            }
            
            $info['player_names'] = [$player1, $player2];
            
            if ($datetime) {
                $info['datetime'] = $datetime;
            }
        } elseif (preg_match('/^(.+?)\s+vs\.?\s+(.+?)\.yrp2?$/i', $filename, $matches)) {
            $info['player_names'] = [trim($matches[1]), trim($matches[2])];
        } else {
            $info['player_names'] = ['未知玩家', '未知玩家'];
        }

        return $info;
    }

    /**
     * 解析 YRP 文件头获取基本信息（仅用于播放页面）
     *
     * @param string $filePath 文件路径
     * @return array|null 录像信息
     */
    public function parseReplayHeader($filePath) {
        if (!file_exists($filePath)) {
            return null;
        }

        $filename = basename($filePath);
        
        $info = [
            'id' => md5($filename),
            'filename' => $filename,
            'file_path' => $filePath,
            'player_names' => [],
            'duel_rule' => '未知',
            'is_yrp2' => preg_match('/\.yrp2$/i', $filename) === 1,
            'file_size' => filesize($filePath),
            'modified_time' => date('Y-m-d H:i:s', filemtime($filePath))
        ];

        $fp = fopen($filePath, 'rb');
        if (!$fp) {
            return $info;
        }

        try {
            $header = fread($fp, 1);
            if (strlen($header) < 1) {
                fclose($fp);
                return $info;
            }

            $isYrp2 = (ord($header[0]) === 0x87);

            $idBytes = fread($fp, 4);
            $versionBytes = fread($fp, 4);
            $flagBytes = fread($fp, 4);

            if (strlen($flagBytes) === 4) {
                $flag = unpack('V', $flagBytes)[1];
                $info['duel_rule'] = $this->getDuelRuleName($flag);
            }

            $playerNames = [];
            for ($i = 0; $i < 4; $i++) {
                $nameLenByte = fread($fp, 1);
                if (strlen($nameLenByte) < 1) {
                    break;
                }
                $len = ord($nameLenByte);
                if ($len > 0) {
                    $nameBytes = fread($fp, $len * 2);
                    if (strlen($nameBytes) >= 2) {
                        $name = @mb_convert_encoding($nameBytes, 'UTF-8', 'UTF-16LE');
                        if ($name === false) {
                            $name = bin2hex($nameBytes);
                        }
                        $name = rtrim($name, "\0");
                        $playerNames[] = $name;
                    }
                } else {
                    $playerNames[] = '';
                }
            }

            fclose($fp);

            if (!empty($playerNames)) {
                $info['player_names'] = array_filter($playerNames);
            }

            if (empty($info['player_names'])) {
                $info['player_names'] = $this->extractPlayerNamesFromFilename($filename);
            }

            return $info;

        } catch (Exception $e) {
            fclose($fp);
            Utils::debug('解析录像头失败', ['错误' => $e->getMessage(), '文件' => $filePath]);
            return $info;
        }
    }

    /**
     * 从文件名提取玩家名
     *
     * @param string $filename 文件名
     * @return array 玩家名数组
     */
    private function extractPlayerNamesFromFilename($filename) {
        if (preg_match('/^(.+?)\s+VS\s+(.+?)(?:\s+\d+)?\.yrp2?$/i', $filename, $matches)) {
            $player1 = trim($matches[1]);
            $player2 = trim($matches[2]);
            
            if (preg_match('/^\d{4}-\d{2}-\d{2}\s+\d{2}-\d{2}-\d{2}\s+(.+)$/', $player1, $dtMatches)) {
                $player1 = trim($dtMatches[1]);
            }
            
            return [$player1, $player2];
        }
        
        return ['未知玩家', '未知玩家'];
    }

    /**
     * 根据标志获取决斗规则名称
     *
     * @param int $flag 标志位
     * @return string 决斗规则名称
     */
    private function getDuelRuleName($flag) {
        $ruleIndex = $flag & 0x7;
        $rules = [
            0 => 'OCG',
            1 => 'TCG',
            2 => 'OCG (单局)',
            3 => 'TCG (单局)',
            4 => 'OCG (匹配)',
            5 => 'TCG (匹配)',
            6 => 'AI'
        ];

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

        if (!preg_match('/\.yrp2?$/i', $filename)) {
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

        if (!preg_match('/\.yrp2?$/i', $filename)) {
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
                $excluded = defined('EXCLUDED_CARD_DATABASES') ? json_decode(EXCLUDED_CARD_DATABASES, true) : [];
                if (!is_array($excluded)) {
                    $excluded = [];
                }
                foreach ($diyDbs as $db) {
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
     * 获取卡片数据库信息列表
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
     * @return string 卡图URL模板
     */
    public function getCardImageUrls() {
        $baseUrl = defined('BASE_URL') ? BASE_URL : '/';
        return $baseUrl . '?controller=replay&action=cardimage&id=';
    }
}
