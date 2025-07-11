<?php
/**
 * 卡片解析类
 *
 * 负责解析卡片数据，将原始数据转换为可用的卡片信息
 */
class CardParser {
    /**
     * 单例实例
     * @var CardParser
     */
    private static $instance;

    /**
     * 卡片数据库连接
     * @var array
     */
    private $cardDatabases = [];

    /**
     * 系列信息
     * @var array
     */
    private $setcodes = [];

    /**
     * 类型信息
     * @var array
     */
    private $types = [];

    /**
     * 种族信息
     * @var array
     */
    private $races = [];

    /**
     * 属性信息
     * @var array
     */
    private $attributes = [];

    /**
     * 等级信息
     * @var array
     */
    private $levels = [];

    /**
     * 禁限信息
     * @var array
     */
    private $lflist = [];

    /**
     * 作者信息
     * @var array
     */
    private $authors = [];

    /**
     * 构造函数
     */
    private function __construct() {
        // 加载卡片信息映射
        $this->loadCardInfoMappings();

        // 加载系列信息
        $this->loadSetcodes();

        // 加载禁限信息
        $this->loadLflist();

        // 加载作者信息
        $this->loadAuthors();
    }

    /**
     * 获取单例实例
     *
     * @return CardParser 卡片解析实例
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * 加载卡片信息映射
     */
    private function loadCardInfoMappings() {
        $cardInfoFile = DB_STRINGS_PATH;

        if (!file_exists($cardInfoFile)) {
            die('卡片信息文件不存在: ' . $cardInfoFile);
        }

        // 直接硬编码类型、种族和属性映射
        // 这是一个临时解决方案，确保卡片详情能够正确显示
        $this->hardcodeCardInfoMappings();

        // 尝试从文件加载
        $content = file_get_contents($cardInfoFile);

        // 确保使用统一的行结束符
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        $lines = explode("\n", $content);

        $currentSection = '';

        // 调试信息
        Utils::debug('开始加载卡片信息映射', ['文件路径' => $cardInfoFile]);

        foreach ($lines as $line) {
            $line = trim($line);

            // 跳过空行和注释行
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }

            // 检查是否是节标题
            if (strpos($line, '##') === 0) {
                $currentSection = trim(substr($line, 2));
                continue;
            }

            // 尝试多种分隔符解析数据行
            // 首先尝试使用制表符分隔
            $parts = explode("\t", $line);

            // 如果没有成功分隔（只有一个元素），尝试使用空格分隔
            if (count($parts) < 2) {
                // 使用正则表达式匹配第一个十六进制数
                if (preg_match('/^(0x[0-9a-fA-F]+|-\d+)\s*(.*)$/', $line, $matches)) {
                    $parts = [$matches[1], $matches[2]];
                }
            }

            // 如果仍然没有成功分隔，但是是一个有效的十六进制数，则将其作为代码，名称设为空字符串
            if (count($parts) < 2 && preg_match('/^(0x[0-9a-fA-F]+|-\d+)$/', $line)) {
                $parts = [$line, ''];
            }

            if (count($parts) >= 1) {
                $code = trim($parts[0]);
                $name = count($parts) >= 2 ? trim($parts[1]) : '';

                // 跳过link marker和category节，它们不是我们需要的
                if ($currentSection == 'link marker' || $currentSection == 'category') {
                    continue;
                }

                switch ($currentSection) {
                    case 'type':
                        $this->types[$code] = $name;
                        break;
                    case 'race':
                        $this->races[$code] = $name;
                        break;
                    case 'attribute':
                        $this->attributes[$code] = $name;
                        break;
                    case 'level':
                        $this->levels[$code] = $name;
                        break;
                    case 'setname':
                        $this->setcodes[$code] = $name;
                        break;
                }
            }
        }

        // 调试信息
        Utils::debug('加载完成', [
            '类型数量' => count($this->types),
            '种族数量' => count($this->races),
            '属性数量' => count($this->attributes)
        ]);
    }

    /**
     * 硬编码卡片信息映射
     * 这是一个临时解决方案，确保卡片详情能够正确显示
     */
    private function hardcodeCardInfoMappings() {
        // 类型映射
        $this->types = [
            '0x1' => '怪兽',
            '0x2' => '魔法',
            '0x4' => '陷阱',
            '0x10' => '通常',
            '0x20' => '效果',
            '0x40' => '融合',
            '0x80' => '仪式',
            '0x200' => '灵魂',
            '0x400' => '同盟',
            '0x800' => '二重',
            '0x1000' => '调整',
            '0x2000' => '同调',
            '0x4000' => '衍生',
            '0x10000' => '速攻',
            '0x20000' => '永续',
            '0x40000' => '装备',
            '0x80000' => '场地',
            '0x100000' => '反击',
            '0x200000' => '反转',
            '0x400000' => '卡通',
            '0x800000' => '超量',
            '0x1000000' => '灵摆',
            '0x2000000' => '特召',
            '0x4000000' => '连接'
        ];

        // 种族映射
        $this->races = [
            '0x1' => '战士',
            '0x2' => '魔法师',
            '0x4' => '天使',
            '0x8' => '恶魔',
            '0x10' => '不死',
            '0x20' => '机械',
            '0x40' => '水',
            '0x80' => '炎',
            '0x100' => '岩石',
            '0x200' => '鸟兽',
            '0x400' => '植物',
            '0x800' => '昆虫',
            '0x1000' => '雷',
            '0x2000' => '龙',
            '0x4000' => '兽',
            '0x8000' => '兽战士',
            '0x10000' => '恐龙',
            '0x20000' => '鱼',
            '0x40000' => '海龙',
            '0x80000' => '爬虫类',
            '0x100000' => '念动力',
            '0x200000' => '幻神兽',
            '0x400000' => '创造神',
            '0x800000' => '幻龙',
            '0x1000000' => '电子界',
            '0x2000000' => '幻想魔族'
        ];

        // 属性映射
        $this->attributes = [
            '0x1' => '地',
            '0x2' => '水',
            '0x4' => '炎',
            '0x8' => '风',
            '0x10' => '光',
            '0x20' => '暗',
            '0x40' => '神'
        ];
    }

    /**
     * 加载系列信息
     */
    private function loadSetcodes() {
        // 首先从卡片数据目录加载系列信息
        $cardDataPath = CARD_DATA_PATH;
        $stringsFile = $cardDataPath . '/strings.conf';

        if (file_exists($stringsFile)) {
            $this->loadSetcodesFromFile($stringsFile);
        }

        // 然后加载assets目录下的strings.conf文件
        $assetsStringsFile = __DIR__ . '/../../assets/strings.conf';
        if (file_exists($assetsStringsFile)) {
            // 将这些系列信息存储在单独的数组中，以便区分来源
            $this->loadSetcodesFromFile($assetsStringsFile, 'assets');
        }
    }

    /**
     * 从文件加载系列信息
     *
     * @param string $filePath 文件路径
     * @param string $source 来源标识，用于区分不同来源的系列信息
     */
    private function loadSetcodesFromFile($filePath, $source = 'default') {
        if (file_exists($filePath)) {
            $content = file_get_contents($filePath);
            $lines = explode("\n", $content);

            foreach ($lines as $line) {
                $line = trim($line);

                // 跳过空行和注释行
                if (empty($line) || strpos($line, '#') === 0) {
                    continue;
                }

                // 解析系列信息
                if (strpos($line, '!setname') === 0) {
                    $parts = explode(' ', $line, 3);
                    if (count($parts) >= 3) {
                        $code = trim($parts[1]);
                        $name = trim($parts[2]);

                        // 如果是assets来源，则存储在单独的数组中
                        if ($source === 'assets') {
                            if (!isset($this->setcodes['assets'])) {
                                $this->setcodes['assets'] = [];
                            }
                            $this->setcodes['assets'][$code] = $name;
                        } else {
                            $this->setcodes[$code] = $name;
                        }
                    }
                }
            }
        }
    }

    /**
     * 加载禁限信息
     */
    private function loadLflist() {
        $cardDataPath = CARD_DATA_PATH;
        $lflistFile = $cardDataPath . '/lflist.conf';

        if (file_exists($lflistFile)) {
            $content = file_get_contents($lflistFile);
            $lines = explode("\n", $content);

            $currentEnvironment = '';

            foreach ($lines as $line) {
                $line = trim($line);

                // 跳过空行和注释行
                if (empty($line) || strpos($line, '#') === 0) {
                    continue;
                }

                // 检查是否是环境标题
                if (strpos($line, '!') === 0) {
                    $currentEnvironment = trim($line);
                    $this->lflist[$currentEnvironment] = [];
                    continue;
                }

                // 解析禁限信息
                if (!empty($currentEnvironment)) {
                    $parts = preg_split('/\s+/', $line, 3);
                    if (count($parts) >= 2) {
                        $cardId = (int)trim($parts[0]);
                        $status = (int)trim($parts[1]);
                        $comment = isset($parts[2]) ? trim($parts[2]) : '';

                        $this->lflist[$currentEnvironment][$cardId] = [
                            'status' => $status,
                            'comment' => $comment
                        ];
                    }
                }
            }
        }
    }

    /**
     * 加载作者信息
     */
    private function loadAuthors() {
        $cardDataPath = CARD_DATA_PATH;
        $stringsFile = $cardDataPath . '/strings.conf';

        if (file_exists($stringsFile)) {
            $content = file_get_contents($stringsFile);
            $lines = explode("\n", $content);

            foreach ($lines as $line) {
                $line = trim($line);

                // 只处理注释行中的作者信息
                if (strpos($line, '#') === 0) {
                    // 尝试匹配作者信息格式
                    // 格式1: #作者名 卡片前缀 系列区间
                    if (preg_match('/#([^\s]+)\s+(\d+)\s+(0x[0-9a-fA-F]+-0x[0-9a-fA-F]+)/', $line, $matches)) {
                        $authorName = $matches[1];
                        $cardPrefix = $matches[2];
                        $setcodeRange = $matches[3];

                        // 解析系列区间
                        $setcodeRanges = [];
                        $rangeParts = explode(' ', $setcodeRange);
                        foreach ($rangeParts as $rangePart) {
                            if (strpos($rangePart, '-') !== false) {
                                list($start, $end) = explode('-', $rangePart);
                                $setcodeRanges[] = [
                                    'start' => $start,
                                    'end' => $end
                                ];
                            }
                        }

                        // 存储作者信息
                        $this->authors[$cardPrefix] = [
                            'name' => $authorName,
                            'card_prefix' => $cardPrefix,
                            'setcode_ranges' => $setcodeRanges
                        ];

                        // 如果有多个卡片区间，也添加到映射中
                        if (preg_match_all('/\s(\d+)\s/', $line, $prefixMatches)) {
                            foreach ($prefixMatches[1] as $additionalPrefix) {
                                if ($additionalPrefix != $cardPrefix) {
                                    $this->authors[$additionalPrefix] = [
                                        'name' => $authorName,
                                        'card_prefix' => $additionalPrefix,
                                        'setcode_ranges' => $setcodeRanges
                                    ];
                                }
                            }
                        }
                    }
                    // 格式2: #作者名 卡片前缀
                    else if (preg_match('/#([^\s]+)\s+(\d+)(?:\s|$)/', $line, $matches)) {
                        $authorName = $matches[1];
                        $cardPrefix = $matches[2];

                        // 存储作者信息
                        $this->authors[$cardPrefix] = [
                            'name' => $authorName,
                            'card_prefix' => $cardPrefix,
                            'setcode_ranges' => []
                        ];
                    }
                    // 格式3: #作者名:卡片前缀
                    else if (preg_match('/#([^:]+):(\d+)/', $line, $matches)) {
                        $authorName = trim($matches[1]);
                        $cardPrefix = $matches[2];

                        // 存储作者信息
                        $this->authors[$cardPrefix] = [
                            'name' => $authorName,
                            'card_prefix' => $cardPrefix,
                            'setcode_ranges' => []
                        ];
                    }
                }
            }

            // 调试信息
            Utils::debug('加载作者信息完成', ['作者数量' => count($this->authors)]);
        }
    }

    /**
     * 获取卡片作者
     *
     * @param array $card 卡片信息
     * @return string 作者名称
     */
    public function getCardAuthor($card) {
        // 首先检查数据库中的作者映射
        $cardId = (string)$card['id'];
        $db = Database::getInstance();

        // 尝试使用卡片ID前缀查找数据库中的作者映射
        if (strlen($cardId) >= 3) {
            $cardPrefix = substr($cardId, 0, 3);
            $authorMapping = $db->getRow('SELECT * FROM author_mappings WHERE card_prefix = :card_prefix', [
                ':card_prefix' => $cardPrefix
            ]);

            if ($authorMapping) {
                return $this->normalizeAuthorName($authorMapping['author_name']);
            }
        }

        // 其次检查卡片描述中是否有作者签名
        $desc = $card['desc'];

        // 匹配多种格式的作者签名
        // 1. DoItYourself/DIY 后跟分隔符或by，然后是作者名
        // 2. 分隔符后跟DoItYourself/DIY，然后是空格，然后是作者名
        // 3. DoItYourself/DIY 后直接跟作者名（无分隔符）
        if (
            // 格式1: DoItYourself/DIY 后跟分隔符或by，然后是作者名
            preg_match('/(?:DoItYourself|DIY)(?:\s*[-—_:：]+\s*|\s+by\s+)([^\n\r]+)/iu', $desc, $matches) ||

            // 格式2: 分隔符后跟DoItYourself/DIY，然后是空格，然后是作者名
            preg_match('/[-—_:：]+\s*(?:DoItYourself|DIY)\s+([^\n\r]+)/iu', $desc, $matches) ||

            // 格式3: DoItYourself/DIY 后直接跟作者名（无分隔符）
            preg_match('/(?:DoItYourself|DIY)\s+([^\n\r]+)/iu', $desc, $matches)
        ) {
            // 确保使用UTF-8编码处理
            if (!mb_check_encoding($matches[1], 'UTF-8')) {
                // 尝试转换编码
                $matches[1] = mb_convert_encoding($matches[1], 'UTF-8', 'auto');
            }

            // 清理作者名称，移除可能的额外分隔符
            $authorName = trim($matches[1]);
            // 移除开头可能存在的分隔符
            $authorName = preg_replace('/^[-—_:：\s]+/u', '', $authorName);
            // 提取作者名，去除后面可能的系列名或其他文本（如"图侵删歉"）
            $authorName = $this->normalizeAuthorName($authorName);
            return $authorName;
        }

        // 最后根据strings.conf中的作者信息查找
        // 首先尝试完全匹配
        foreach ($this->authors as $prefix => $authorInfo) {
            // 确保 $prefix 是字符串类型
            $prefixStr = (string)$prefix;
            if (strpos($cardId, $prefixStr) === 0) {
                // 规范化作者名称
                return $this->normalizeAuthorName($authorInfo['name']);
            }
        }

        // 如果完全匹配失败，尝试使用卡片ID的前三位数字进行匹配
        if (strlen($cardId) >= 3) {
            $cardPrefix = substr($cardId, 0, 3);

            // 遍历所有作者信息，查找前三位匹配的作者
            foreach ($this->authors as $prefix => $authorInfo) {
                $prefixStr = (string)$prefix;
                // 如果前缀长度至少为3位，且与卡片ID的前三位匹配
                if (strlen($prefixStr) >= 3 && substr($prefixStr, 0, 3) === $cardPrefix) {
                    return $this->normalizeAuthorName($authorInfo['name']);
                }
            }
        }

        // 如果无法确定作者，返回"未知作者"
        return "未知作者";
    }

    /**
     * 获取strings.conf文件中的作者信息
     *
     * @return array 作者信息数组
     */
    public function getAuthorsFromStringsConf() {
        return $this->authors;
    }

    /**
     * 规范化作者名称
     *
     * @param string $authorName 原始作者名称
     * @return string 规范化后的作者名称
     */
    private function normalizeAuthorName($authorName) {
        // 确保使用UTF-8编码处理
        if (!mb_check_encoding($authorName, 'UTF-8')) {
            // 尝试转换编码
            $authorName = mb_convert_encoding($authorName, 'UTF-8', 'auto');
        }

        // 去除两端空白
        $authorName = trim($authorName);

        // 如果作者名为空，返回"未知作者"
        if (empty($authorName)) {
            return "未知作者";
        }

        // 移除"图侵删歉"等常见附加文本
        $commonSuffixes = ['图侵删歉', '图侵删', '侵删', '图源网络', '图源', '图片来源网络'];
        foreach ($commonSuffixes as $suffix) {
            if (mb_strpos($authorName, $suffix, 0, 'UTF-8') !== false) {
                $authorName = trim(mb_substr($authorName, 0, mb_strpos($authorName, $suffix, 0, 'UTF-8'), 'UTF-8'));
            }
        }

        // 提取方括号、尖括号或引号前的作者名
        if (preg_match('/^([^「」\[\]【】《》\(\)（）『』\<\>]+)/u', $authorName, $matches)) {
            $authorName = trim($matches[1]);
        }

        // 处理作者名中的空格
        // 1. 如果作者名中包含特殊字符（如日文假名、全角符号等），不进行截断
        // 2. 如果作者名是纯英文，且看起来像"名字 系列名"的格式，则截断为第一个单词

        // 检查作者名是否包含特殊字符（非英文字母、数字和基本标点）
        if (preg_match('/[^\x00-\x7F]/u', $authorName)) {
            // 包含特殊字符（如中文、日文等），不进行截断
            // 但仍然需要处理可能的方括号等标记
            if (preg_match('/^([^「」\[\]【】《》\(\)（）『』\<\>]+)(?:\s+[\[「『\(（<《])/u', $authorName, $matches)) {
                $authorName = trim($matches[1]);
            }
        }
        // 只对纯英文名称进行处理
        else if (preg_match('/^[a-zA-Z0-9\s\.\-_]+$/u', $authorName)) {
            // 检查是否有方括号等标记，如果有，则在这些标记前截断
            if (preg_match('/^(\S+)(?:\s+[\[「『\(（<《])/u', $authorName, $matches)) {
                $authorName = $matches[1];
            }
            // 否则，检查是否是"名字 系列名"的格式
            else if (preg_match('/^(\S+)(?:\s+.+)/u', $authorName, $matches)) {
                // 只有当第一个单词看起来像一个完整的名字时才截断
                // 例如，"Justfish Shadow"应该截断为"Justfish"
                // 但"Lin Yanjun"不应该截断
                if (!preg_match('/^[A-Z][a-z]+\s+[A-Z][a-z]+$/u', $authorName)) {
                    $authorName = $matches[1];
                }
            }
        }

        // 如果规范化后的作者名为空，返回"未知作者"
        if (empty(trim($authorName))) {
            return "未知作者";
        }

        // 过滤掉可能导致问题的控制字符
        $authorName = preg_replace('/[\x00-\x1F\x7F]/u', '', $authorName);

        return $authorName;
    }

    /**
     * 清除数据库连接缓存
     */
    public function clearDatabaseCache() {
        $this->cardDatabases = [];
    }

    /**
     * 获取卡片数据库连接
     *
     * @param string $dbFile 数据库文件路径
     * @param bool $forceNew 是否强制创建新连接
     * @return PDO 数据库连接
     */
    private function getCardDatabase($dbFile, $forceNew = false) {
        // 验证数据库文件路径
        if (!file_exists($dbFile)) {
            Utils::debug('数据库文件不存在', ['文件路径' => $dbFile]);
            die('卡片数据库文件不存在: ' . htmlspecialchars($dbFile));
        }

        // 使用文件路径的哈希值作为键，避免使用完整路径
        $dbKey = md5($dbFile);

        if ($forceNew || !isset($this->cardDatabases[$dbKey])) {
            try {
                // 确保临时目录存在
                if (!file_exists(TMP_DIR)) {
                    mkdir(TMP_DIR, 0777, true);
                }

                // 使用临时目录中的连接字符串
                $this->cardDatabases[$dbKey] = new PDO('sqlite:' . $dbFile);
                $this->cardDatabases[$dbKey]->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                Utils::debug('数据库连接失败', ['错误' => $e->getMessage(), '文件路径' => $dbFile]);
                die('卡片数据库连接失败: ' . $e->getMessage());
            }
        }

        return $this->cardDatabases[$dbKey];
    }

    /**
     * 获取所有卡片数据库文件
     *
     * @param bool $excludeFiles 是否排除配置中指定的文件
     * @return array 数据库文件列表
     */
    public function getCardDatabaseFiles($excludeFiles = true) {
        $cardDataPath = CARD_DATA_PATH;
        $files = glob($cardDataPath . '/*.cdb');

        // 如果需要排除特定文件
        if ($excludeFiles && defined('EXCLUDED_CARD_DATABASES')) {
            $excludedFiles = json_decode(EXCLUDED_CARD_DATABASES, true);

            if (is_array($excludedFiles) && !empty($excludedFiles)) {
                $filteredFiles = [];

                foreach ($files as $file) {
                    $fileName = basename($file);
                    if (!in_array($fileName, $excludedFiles)) {
                        $filteredFiles[] = $file;
                    }
                }

                return $filteredFiles;
            }
        }

        return $files;
    }

    /**
     * 获取所有卡片（支持分页）
     *
     * @param string $dbFile 数据库文件路径，如果为null则获取所有数据库的卡片
     * @param int $page 页码，从1开始
     * @param int $perPage 每页显示的卡片数量
     * @param bool $countOnly 是否只返回卡片总数
     * @return array 包含卡片列表和分页信息的数组
     */
    public function getAllCards($dbFile = null, $page = 1, $perPage = 20, $countOnly = false) {
        $cards = [];
        $totalCards = 0;

        if ($dbFile !== null) {
            if ($countOnly) {
                $totalCards = $this->countCardsInDatabase($dbFile);
            } else {
                $cards = $this->getCardsFromDatabase($dbFile, $page, $perPage);
                $totalCards = $this->countCardsInDatabase($dbFile);
            }
        } else {
            $dbFiles = $this->getCardDatabaseFiles();

            if ($countOnly) {
                // 只计算总数
                foreach ($dbFiles as $file) {
                    $totalCards += $this->countCardsInDatabase($file);
                }
            } else {
                // 优化：使用数据库级别的分页，避免加载所有卡片到内存
                Utils::checkMemoryUsage('多数据库卡片获取开始');

                // 首先计算总数
                foreach ($dbFiles as $file) {
                    $totalCards += $this->countCardsInDatabase($file);
                }

                // 计算需要跳过的记录数
                $offset = ($page - 1) * $perPage;
                $remaining = $perPage;
                $currentOffset = 0;

                foreach ($dbFiles as $file) {
                    if ($remaining <= 0) {
                        break;
                    }

                    $dbCardCount = $this->countCardsInDatabase($file);

                    // 如果当前数据库的所有卡片都需要跳过
                    if ($currentOffset + $dbCardCount <= $offset) {
                        $currentOffset += $dbCardCount;
                        continue;
                    }

                    // 计算在当前数据库中需要跳过的记录数
                    $dbOffset = max(0, $offset - $currentOffset);
                    $dbLimit = min($remaining, $dbCardCount - $dbOffset);

                    if ($dbLimit > 0) {
                        // 从当前数据库获取卡片
                        $dbPage = floor($dbOffset / $perPage) + 1;
                        $cardsFromDb = $this->getCardsFromDatabase($file, $dbPage, $dbLimit);

                        // 如果需要进一步调整偏移量
                        if ($dbOffset % $perPage > 0) {
                            $cardsFromDb = array_slice($cardsFromDb, $dbOffset % $perPage, $dbLimit);
                        }

                        $cards = array_merge($cards, $cardsFromDb);
                        $remaining -= count($cardsFromDb);
                    }

                    $currentOffset += $dbCardCount;

                    // 检查内存使用情况
                    if (Utils::checkMemoryUsage('多数据库卡片处理', 2048)) {
                        Utils::forceGarbageCollection('多数据库卡片获取');
                    }
                }

                Utils::checkMemoryUsage('多数据库卡片获取完成');
            }
        }

        if ($countOnly) {
            return $totalCards;
        }

        return [
            'cards' => $cards,
            'total' => $totalCards,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($totalCards / $perPage)
        ];
    }

    /**
     * 计算数据库中的卡片数量
     *
     * @param string $dbFile 数据库文件路径
     * @return int 卡片数量
     */
    private function countCardsInDatabase($dbFile) {
        $db = $this->getCardDatabase($dbFile);

        $sql = "
            SELECT
                COUNT(*) as count
            FROM
                datas d
            JOIN
                texts t ON d.id = t.id
        ";

        try {
            $stmt = $db->query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$result['count'];
        } catch (PDOException $e) {
            Utils::debug('计算卡片数量失败', ['错误' => $e->getMessage()]);
            return 0;
        }
    }

    /**
     * 从数据库获取卡片
     *
     * @param string $dbFile 数据库文件路径
     * @param int $page 页码，从1开始
     * @param int $perPage 每页显示的卡片数量
     * @return array 卡片列表
     */
    private function getCardsFromDatabase($dbFile, $page = 1, $perPage = 20) {
        $db = $this->getCardDatabase($dbFile);
        $offset = ($page - 1) * $perPage;

        $sql = "
            SELECT
                d.id, d.ot, d.alias, d.setcode, d.type, d.atk, d.def, d.level, d.race, d.attribute,
                t.name, t.desc
            FROM
                datas d
            JOIN
                texts t ON d.id = t.id
            ORDER BY
                d.id
            LIMIT :limit OFFSET :offset
        ";

        try {
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $cards = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 处理卡片数据
            foreach ($cards as &$card) {
                // 判断是否为TCG卡片
                $isTcgCard = (basename($dbFile) === basename(TCG_CARD_DATA_PATH));

                $card['setcode_text'] = $this->getSetcodeText($card['setcode'], $isTcgCard);
                $card['type_text'] = $this->getTypeText($card['type']);
                $card['race_text'] = $this->getRaceText($card['race']);
                $card['attribute_text'] = $this->getAttributeText($card['attribute']);
                $card['level_text'] = $this->getLevelText($card['level']);
                $card['image_path'] = $this->getCardImagePath($card['id']);
                $card['database_file'] = basename($dbFile);
                $card['author'] = $this->getCardAuthor($card);
            }

            return $cards;
        } catch (PDOException $e) {
            Utils::debug('获取卡片数据失败', ['错误' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * 根据ID获取卡片
     *
     * @param int $cardId 卡片ID
     * @return array|null 卡片信息
     */
    public function getCardById($cardId, $forceNewConnection = false) {
        // 调试信息
        Utils::debug('CardParser::getCardById 开始', ['cardId' => $cardId, 'forceNew' => $forceNewConnection]);

        // 首先从DIY卡数据库中查找
        $dbFiles = $this->getCardDatabaseFiles();
        $card = null;

        foreach ($dbFiles as $dbFile) {
            $db = $this->getCardDatabase($dbFile, $forceNewConnection);

            $sql = "
                SELECT
                    d.id, d.ot, d.alias, d.setcode, d.type, d.atk, d.def, d.level, d.race, d.attribute,
                    t.name, t.desc
                FROM
                    datas d
                JOIN
                    texts t ON d.id = t.id
                WHERE
                    d.id = :id
            ";

            try {
                // 强制每次都创建新的PDO语句，避免缓存问题
                $stmt = $db->prepare($sql);
                $stmt->bindValue(':id', (int)$cardId, PDO::PARAM_INT);
                $stmt->execute();
                $card = $stmt->fetch(PDO::FETCH_ASSOC);

                // 立即关闭语句，确保不会有缓存问题
                $stmt->closeCursor();
                $stmt = null;

                // 调试信息
                Utils::debug('CardParser::getCardById 查询结果', [
                    'cardId' => $cardId,
                    'dbFile' => basename($dbFile),
                    'found' => $card ? true : false,
                    'cardData' => $card ? ['id' => $card['id'], 'name' => $card['name']] : null
                ]);

                if ($card) {
                    // 判断是否为TCG卡片
                    $isTcgCard = (basename($dbFile) === basename(TCG_CARD_DATA_PATH));

                    $card['setcode_text'] = $this->getSetcodeText($card['setcode'], $isTcgCard);
                    $card['type_text'] = $this->getTypeText($card['type']);
                    $card['race_text'] = $this->getRaceText($card['race']);
                    $card['attribute_text'] = $this->getAttributeText($card['attribute']);
                    $card['level_text'] = $this->getLevelText($card['level']);
                    $card['image_path'] = $this->getCardImagePath($card['id']);
                    $card['database_file'] = basename($dbFile);
                    $card['author'] = $this->getCardAuthor($card);

                    // 调试信息
                    Utils::debug('CardParser::getCardById 返回结果', [
                        'cardId' => $cardId,
                        'returnedCard' => ['id' => $card['id'], 'name' => $card['name']]
                    ]);

                    return $card;
                }
            } catch (PDOException $e) {
                error_log('获取卡片数据失败: ' . $e->getMessage());
            }
        }

        // 如果在DIY卡数据库中找不到，则从TCG卡数据库中查找
        if (!$card && defined('TCG_CARD_DATA_PATH') && file_exists(TCG_CARD_DATA_PATH)) {
            try {
                // 确保临时目录存在
                if (!file_exists(TMP_DIR)) {
                    mkdir(TMP_DIR, 0777, true);
                }

                // 使用文件路径的哈希值作为键
                $tcgDbKey = md5(TCG_CARD_DATA_PATH);

                // 如果已经有连接，则重用
                if (isset($this->cardDatabases[$tcgDbKey])) {
                    $tcgDb = $this->cardDatabases[$tcgDbKey];
                } else {
                    $tcgDb = new PDO('sqlite:' . TCG_CARD_DATA_PATH);
                    $tcgDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    $this->cardDatabases[$tcgDbKey] = $tcgDb;
                }

                $sql = "
                    SELECT
                        d.id, d.ot, d.alias, d.setcode, d.type, d.atk, d.def, d.level, d.race, d.attribute,
                        t.name, t.desc
                    FROM
                        datas d
                    JOIN
                        texts t ON d.id = t.id
                    WHERE
                        d.id = :id
                ";

                $stmt = $tcgDb->prepare($sql);
                $stmt->bindValue(':id', (int)$cardId, PDO::PARAM_INT);
                $stmt->execute();
                $card = $stmt->fetch(PDO::FETCH_ASSOC);

                // 立即关闭语句，确保不会有缓存问题
                $stmt->closeCursor();
                $stmt = null;

                if ($card) {
                    // TCG卡片设置标志
                    $isTcgCard = true;

                    $card['setcode_text'] = $this->getSetcodeText($card['setcode'], $isTcgCard);
                    $card['type_text'] = $this->getTypeText($card['type']);
                    $card['race_text'] = $this->getRaceText($card['race']);
                    $card['attribute_text'] = $this->getAttributeText($card['attribute']);
                    $card['level_text'] = $this->getLevelText($card['level']);
                    $card['image_path'] = $this->getCardImagePath($card['id']);
                    $card['database_file'] = basename(TCG_CARD_DATA_PATH);
                    // TCG卡片不设置作者
                    $card['author'] = '';

                    return $card;
                }
            } catch (PDOException $e) {
                error_log('获取TCG卡片数据失败: ' . $e->getMessage());
            }
        }

        return null;
    }

    /**
     * 搜索卡片
     *
     * @param string $keyword 关键词
     * @param int $limit 限制结果数量，默认100
     * @return array 卡片列表
     */
    public function searchCards($keyword, $limit = 100) {
        $keyword = trim($keyword);

        if (empty($keyword)) {
            return [];
        }

        Utils::checkMemoryUsage('卡片搜索开始');

        $cards = [];
        $dbFiles = $this->getCardDatabaseFiles();

        // 检查是否是数字（卡片ID）
        $isId = is_numeric($keyword);

        foreach ($dbFiles as $dbFile) {
            // 如果已经达到限制数量，停止搜索
            if (count($cards) >= $limit) {
                break;
            }

            $db = $this->getCardDatabase($dbFile);

            $sql = "
                SELECT
                    d.id, d.ot, d.alias, d.setcode, d.type, d.atk, d.def, d.level, d.race, d.attribute,
                    t.name, t.desc
                FROM
                    datas d
                JOIN
                    texts t ON d.id = t.id
                WHERE
            ";

            if ($isId) {
                $sql .= "d.id = :keyword";
            } else {
                $sql .= "t.name LIKE :keyword OR t.desc LIKE :keyword";
            }

            $sql .= " ORDER BY d.id LIMIT :limit";

            try {
                $stmt = $db->prepare($sql);
                $stmt->bindValue(':limit', $limit - count($cards), PDO::PARAM_INT);

                if ($isId) {
                    $stmt->bindValue(':keyword', (int)$keyword, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue(':keyword', '%' . $keyword . '%', PDO::PARAM_STR);
                }

                $stmt->execute();
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($results as &$card) {
                    // 判断是否为TCG卡片
                    $isTcgCard = (basename($dbFile) === basename(TCG_CARD_DATA_PATH));

                    $card['setcode_text'] = $this->getSetcodeText($card['setcode'], $isTcgCard);
                    $card['type_text'] = $this->getTypeText($card['type']);
                    $card['race_text'] = $this->getRaceText($card['race']);
                    $card['attribute_text'] = $this->getAttributeText($card['attribute']);
                    $card['level_text'] = $this->getLevelText($card['level']);
                    $card['image_path'] = $this->getCardImagePath($card['id']);
                    $card['database_file'] = basename($dbFile);
                    $card['author'] = $this->getCardAuthor($card);
                }

                $cards = array_merge($cards, $results);

                // 如果是ID搜索并且已经找到卡片，就不需要继续搜索了
                if ($isId && !empty($results)) {
                    break;
                }

                // 检查内存使用情况
                if (Utils::checkMemoryUsage('卡片搜索处理', 2048)) {
                    Utils::forceGarbageCollection('卡片搜索');
                }

            } catch (PDOException $e) {
                Utils::debug('搜索卡片失败', ['错误' => $e->getMessage()]);
            }
        }

        Utils::checkMemoryUsage('卡片搜索完成');
        return $cards;
    }

    /**
     * 获取系列文本
     *
     * @param int $setcode 系列代码
     * @param bool $isTcgCard 是否为TCG卡片
     * @return string 系列文本
     */
    public function getSetcodeText($setcode, $isTcgCard = false) {
        $hexSetcode = '0x' . dechex($setcode);

        // 对于TCG卡片，优先从assets/strings.conf中查找
        if ($isTcgCard && isset($this->setcodes['assets']) && isset($this->setcodes['assets'][$hexSetcode])) {
            return $this->setcodes['assets'][$hexSetcode];
        }

        // 对于普通卡片，优先从默认数据中查找
        if (isset($this->setcodes[$hexSetcode])) {
            return $this->setcodes[$hexSetcode];
        }

        // 如果在默认数据中找不到，尝试从assets/strings.conf中查找
        if (isset($this->setcodes['assets']) && isset($this->setcodes['assets'][$hexSetcode])) {
            return $this->setcodes['assets'][$hexSetcode];
        }

        // 如果都找不到，返回未知系列
        return '未知系列 (' . $hexSetcode . ')';
    }

    /**
     * 获取类型文本
     *
     * @param int $type 类型代码
     * @return string 类型文本
     */
    public function getTypeText($type) {
        $typeTexts = [];

        // 按位检查每个类型
        foreach ($this->types as $code => $name) {
            // 将十六进制字符串转换为整数
            $hexCode = hexdec($code);

            // 检查该位是否设置
            if (($type & $hexCode) == $hexCode && $hexCode != 0) {
                // 跳过标记为N/A的类型
                if ($name == 'N/A') {
                    continue;
                }
                $typeTexts[] = $name;
            }
        }

        if (empty($typeTexts)) {
            return '未知类别 (0x' . dechex($type) . ')';
        }

        // 对类型进行排序，确保主要类型（怪兽/魔法/陷阱）在前面
        $mainTypes = [];
        $subTypes = [];

        foreach ($typeTexts as $typeText) {
            if (in_array($typeText, ['怪兽', '魔法', '陷阱'])) {
                $mainTypes[] = $typeText;
            } else {
                $subTypes[] = $typeText;
            }
        }

        // 合并主要类型和子类型
        $sortedTypes = array_merge($mainTypes, $subTypes);

        return implode('·', $sortedTypes);
    }

    /**
     * 获取种族文本
     *
     * @param int $race 种族代码
     * @return string 种族文本
     */
    public function getRaceText($race) {
        $hexRace = '0x' . dechex($race);

        // 直接查找完全匹配的种族代码
        foreach ($this->races as $code => $name) {
            // 将十六进制字符串转换为整数
            $decCode = hexdec($code);

            if ($decCode == $race) {
                return $name;
            }
        }

        return '未知种族 (' . $hexRace . ')';
    }

    /**
     * 获取属性文本
     *
     * @param int $attribute 属性代码
     * @return string 属性文本
     */
    public function getAttributeText($attribute) {
        $hexAttribute = '0x' . dechex($attribute);

        // 直接查找完全匹配的属性代码
        foreach ($this->attributes as $code => $name) {
            // 将十六进制字符串转换为整数
            $decCode = hexdec($code);

            if ($decCode == $attribute) {
                return $name;
            }
        }

        return '未知属性 (' . $hexAttribute . ')';
    }

    /**
     * 获取等级文本
     *
     * @param int $level 等级代码
     * @return string 等级文本
     */
    public function getLevelText($level) {
        $hexLevel = '0x' . dechex($level);

        if (isset($this->levels[$hexLevel])) {
            return $this->levels[$hexLevel];
        }

        return 'Lv.' . ($level & 0xff);
    }

    /**
     * 获取卡片图片路径
     *
     * @param int $cardId 卡片ID
     * @return string 图片路径
     */
    public function getCardImagePath($cardId) {
        $cardDataPath = CARD_DATA_PATH;
        $cardDataDirName = basename($cardDataPath); // 获取卡片数据目录的名称（如 'example'）

        // 首先尝试在卡片数据目录下查找
        // 尝试使用c+卡片ID格式
        $physicalPath = $cardDataPath . '/pics/c' . $cardId . '.jpg';

        if (file_exists($physicalPath)) {
            return BASE_URL . $cardDataDirName . '/pics/c' . $cardId . '.jpg';
        }

        $physicalPath = $cardDataPath . '/pics/c' . $cardId . '.png';

        if (file_exists($physicalPath)) {
            return BASE_URL . $cardDataDirName . '/pics/c' . $cardId . '.png';
        }

        // 然后尝试使用卡片ID格式
        $physicalPath = $cardDataPath . '/pics/' . $cardId . '.jpg';

        if (file_exists($physicalPath)) {
            return BASE_URL . $cardDataDirName . '/pics/' . $cardId . '.jpg';
        }

        $physicalPath = $cardDataPath . '/pics/' . $cardId . '.png';

        if (file_exists($physicalPath)) {
            return BASE_URL . $cardDataDirName . '/pics/' . $cardId . '.png';
        }

        // 如果在卡片数据目录下找不到，尝试在根目录下查找
        $rootPhysicalPath = __DIR__ . '/../../pics/c' . $cardId . '.jpg';

        if (file_exists($rootPhysicalPath)) {
            return BASE_URL . 'pics/c' . $cardId . '.jpg';
        }

        $rootPhysicalPath = __DIR__ . '/../../pics/c' . $cardId . '.png';

        if (file_exists($rootPhysicalPath)) {
            return BASE_URL . 'pics/c' . $cardId . '.png';
        }

        $rootPhysicalPath = __DIR__ . '/../../pics/' . $cardId . '.jpg';

        if (file_exists($rootPhysicalPath)) {
            return BASE_URL . 'pics/' . $cardId . '.jpg';
        }

        $rootPhysicalPath = __DIR__ . '/../../pics/' . $cardId . '.png';

        if (file_exists($rootPhysicalPath)) {
            return BASE_URL . 'pics/' . $cardId . '.png';
        }

        return BASE_URL . 'assets/images/card_back.jpg';
    }

    /**
     * 获取卡片禁限状态
     *
     * @param int $cardId 卡片ID
     * @param string $environment 环境名称
     * @return int 禁限状态 (0:禁止, 1:限制, 2:准限制, 3:无限制)
     */
    public function getCardLimitStatus($cardId, $environment) {
        if (isset($this->lflist[$environment][$cardId])) {
            return $this->lflist[$environment][$cardId]['status'];
        }

        return 3; // 默认无限制
    }

    /**
     * 获取所有环境
     *
     * @return array 环境列表
     */
    public function getAllEnvironments() {
        return array_keys($this->lflist);
    }

    /**
     * 获取禁限信息
     *
     * @return array 禁限信息
     */
    public function getLflist() {
        return $this->lflist;
    }

    /**
     * 获取系列信息
     *
     * @return array 系列信息
     */
    public function getSetcodes() {
        return $this->setcodes;
    }

    /**
     * 获取作者信息
     *
     * @return array 作者信息
     */
    public function getAuthors() {
        return $this->authors;
    }

    /**
     * 根据系列代码获取同系列卡片
     *
     * @param int $setcode 系列代码
     * @param bool $excludeTcgCards 是否排除TCG卡片
     * @return array 卡片列表
     */
    public function getCardsBySetcode($setcode, $excludeTcgCards = true) {
        $cards = [];
        $dbFiles = $this->getCardDatabaseFiles();

        foreach ($dbFiles as $dbFile) {
            // 如果需要排除TCG卡片，跳过TCG数据库
            if ($excludeTcgCards && basename($dbFile) === basename(TCG_CARD_DATA_PATH)) {
                continue;
            }

            try {
                $db = $this->getCardDatabase($dbFile);

                // 查询具有指定setcode的卡片
                // 游戏王setcode匹配逻辑：卡片的setcode应该包含目标setcode的所有位
                $results = [];

                // 方式1: 精确匹配 - 最优先
                $sql1 = "
                    SELECT
                        d.id, d.ot, d.alias, d.setcode, d.type, d.atk, d.def, d.level, d.race, d.attribute,
                        t.name, t.desc
                    FROM
                        datas d
                    JOIN
                        texts t ON d.id = t.id
                    WHERE
                        d.setcode = :setcode
                    ORDER BY
                        d.id ASC
                ";

                $stmt = $db->prepare($sql1);
                $stmt->execute(['setcode' => $setcode]);
                $exactResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (!empty($exactResults)) {
                    $results = $exactResults;
                } else {
                    // 方式2: 位运算匹配 - 卡片的setcode包含目标setcode
                    // 只有当卡片setcode的位包含目标setcode的所有位时才匹配
                    $sql2 = "
                        SELECT
                            d.id, d.ot, d.alias, d.setcode, d.type, d.atk, d.def, d.level, d.race, d.attribute,
                            t.name, t.desc
                        FROM
                            datas d
                        JOIN
                            texts t ON d.id = t.id
                        WHERE
                            (d.setcode & 0xfff) = (:setcode & 0xfff)
                            AND d.setcode > 0
                            AND d.setcode != :setcode
                        ORDER BY
                            d.id ASC
                    ";

                    $stmt = $db->prepare($sql2);
                    $stmt->execute(['setcode' => $setcode]);
                    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }

                // 确定使用的查询方式
                $queryMethod = 'none';
                if (!empty($exactResults)) {
                    $queryMethod = 'exact';
                } elseif (!empty($results)) {
                    $queryMethod = 'bitwise';
                }

                // 调试信息
                if (defined('DEBUG_MODE') && DEBUG_MODE) {
                    Utils::debug('查询系列卡片', [
                        'setcode' => $setcode,
                        'setcode_hex' => '0x' . dechex($setcode),
                        'database' => basename($dbFile),
                        'query_method' => $queryMethod,
                        'exact_results' => count($exactResults),
                        'bitwise_results' => count($results) - count($exactResults),
                        'final_results' => count($results)
                    ]);
                }

                foreach ($results as &$card) {
                    // 判断是否为TCG卡片
                    $isTcgCard = (basename($dbFile) === basename(TCG_CARD_DATA_PATH));

                    $card['setcode_text'] = $this->getSetcodeText($card['setcode'], $isTcgCard);
                    $card['type_text'] = $this->getTypeText($card['type']);
                    $card['race_text'] = $this->getRaceText($card['race']);
                    $card['attribute_text'] = $this->getAttributeText($card['attribute']);
                    $card['level_text'] = $this->getLevelText($card['level']);
                    $card['image_path'] = $this->getCardImagePath($card['id']);
                    $card['database_file'] = basename($dbFile);
                    $card['author'] = $this->getCardAuthor($card);
                }

                $cards = array_merge($cards, $results);

            } catch (PDOException $e) {
                Utils::debug('获取系列卡片数据失败', ['错误' => $e->getMessage(), '数据库' => $dbFile]);
            }
        }

        return $cards;
    }
}
