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
     * 作者归属解析器
     * @var AuthorResolver|null
     */
    private $authorResolver;

    /**
     * 预加载的管理员作者区间
     * @var array|null
     */
    private $manualAuthorMappings;

    /**
     * 预加载的管理员文本规则
     * @var array|null
     */
    private $authorTextRules;

    /**
     * 作者码对应卡号总位数推断缓存
     * @var array
     */
    private $authorPrefixLengthCache = [];

    /**
     * 构造函数
     */
    private function __construct() {
        $this->authorResolver = new AuthorResolver();

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
     *
     * @return void
     */
    private function loadAuthors() {
        $cardDataPath = CARD_DATA_PATH;
        $stringsFile = $cardDataPath . '/strings.conf';

        if (!file_exists($stringsFile)) {
            return;
        }

        $content = file_get_contents($stringsFile);
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        $claims = [];

        foreach (explode("\n", $content) as $line) {
            $line = trim($line);
            if (strpos($line, '#') !== 0 || strpos($line, '#!') === 0) {
                continue;
            }

            $body = trim(substr($line, 1));
            if ($body === '' || preg_match('/^(?:No81DIY|Moved|victory\s+reason|counters?|カテゴリ|cm\b|AddCodeList|TYGOC\s+Submissions)/iu', $body)) {
                continue;
            }

            // 十六进制值是setcode命名空间，不是卡号区间；9-10位数字通常是QQ号。
            $body = preg_replace('/0x[0-9a-f]+(?:\s*(?:-|--|~)\s*0x[0-9a-f]+)?/iu', ' ', $body);
            $body = preg_replace('/(?:qq\s*[:：]?\s*)?\b\d{9,10}\b/iu', ' ', $body);
            $body = preg_replace('/\s+/u', ' ', trim($body));

            // 数字作者名（例如“#01 822”）无法在无schema文本中可靠消歧，交由后台人工维护。
            if (preg_match('/^\d{1,4}(?:&\d{1,4})?\s+\d{1,4}(?:&\d{1,4})?(?:\s|$)/u', $body)) {
                continue;
            }

            $match = [];
            if (!preg_match('/^(.+?)(?:\s+|\s*:\s*)(\d{1,4}(?:&\d{1,4})*)(?:\s|$)/u', $body, $match)) {
                continue;
            }

            $authorName = trim($match[1]);
            if ($authorName === '' || preg_match('/^\d+$/', $authorName)) {
                continue;
            }

            foreach (explode('&', $match[2]) as $cardPrefix) {
                $cardPrefix = trim($cardPrefix);
                if (!preg_match('/^\d{1,4}$/', $cardPrefix)) {
                    continue;
                }
                if (!isset($claims[$cardPrefix])) {
                    $claims[$cardPrefix] = [];
                }
                $claims[$cardPrefix][$authorName] = true;
            }
        }

        foreach ($claims as $cardPrefix => $authors) {
            $authorNames = array_keys($authors);
            if (count($authorNames) !== 1) {
                Utils::debug('跳过存在冲突的strings.conf作者码', [
                    'card_prefix' => $cardPrefix,
                    'authors' => $authorNames
                ]);
                continue;
            }

            $this->authors[] = [
                'author_name' => $authorNames[0],
                'name' => $authorNames[0],
                'card_prefix' => (string)$cardPrefix,
                'card_id_length' => $this->inferAuthorCardIdLength((string)$cardPrefix),
                'priority' => 0,
                'setcode_ranges' => []
            ];
        }

        Utils::debug('加载作者信息完成', ['作者数量' => count($this->authors)]);
    }

    /**
     * 获取卡片作者
     *
     * @param array $card 卡片信息
     * @return string 作者名称
     */
    public function getCardAuthor($card) {
        $resolution = $this->getCardAuthorResolution($card);
        return $resolution['author'];
    }

    /**
     * 获取带判定来源的卡片作者信息
     *
     * @param array $card 卡片信息
     * @param bool $manualOnly 是否只使用管理员规则
     * @return array 作者、来源与命中规则
     */
    public function getCardAuthorResolution($card, $manualOnly = false) {
        if (!($this->authorResolver instanceof AuthorResolver)) {
            $this->authorResolver = new AuthorResolver();
        }

        $this->loadRuntimeAuthorRules();

        return $this->authorResolver->resolve(
            $card,
            $this->manualAuthorMappings,
            $this->authorTextRules,
            $this->authors,
            $manualOnly
        );
    }

    /**
     * 将作者与可解释来源写入卡片数组
     *
     * @param array $card 卡片信息
     * @return void
     */
    private function applyAuthorResolution(&$card) {
        $resolution = $this->getCardAuthorResolution($card);
        $card['author'] = $resolution['author'];
        $card['author_source'] = $resolution['source'];
        $card['author_source_label'] = $resolution['source_label'];
        $card['author_rule_id'] = $resolution['rule_id'];
        $card['author_matched_on'] = $resolution['matched_on'];
        $card['author_matched_value'] = $resolution['matched_value'];

        $seriesResolution = $this->authorResolver->resolveSeries($card, $this->authorTextRules);
        $card['manual_series_name'] = $seriesResolution !== null ? $seriesResolution['series_name'] : null;
        $card['manual_series_source'] = $seriesResolution !== null ? $seriesResolution['source'] : null;
        $card['manual_series_source_label'] = $seriesResolution !== null ? $seriesResolution['source_label'] : null;
        $card['manual_series_rule_id'] = $seriesResolution !== null ? $seriesResolution['rule_id'] : null;
        $card['manual_series_matched_on'] = $seriesResolution !== null ? $seriesResolution['matched_on'] : null;
        $card['manual_series_matched_value'] = $seriesResolution !== null ? $seriesResolution['matched_value'] : null;
    }

    /**
     * 一次性预加载管理员规则，避免排行榜逐卡查询RAMSAY数据库
     *
     * @return void
     */
    private function loadRuntimeAuthorRules() {
        if ($this->manualAuthorMappings !== null && $this->authorTextRules !== null) {
            return;
        }

        $db = Database::getInstance();
        $this->manualAuthorMappings = $db->getRows(
            'SELECT * FROM author_mappings ORDER BY priority DESC, card_prefix ASC, id ASC'
        );
        foreach ($this->manualAuthorMappings as &$mapping) {
            $hasExplicitRange = isset($mapping['card_id_start'], $mapping['card_id_end']) &&
                $mapping['card_id_start'] !== null && $mapping['card_id_start'] !== '' &&
                $mapping['card_id_end'] !== null && $mapping['card_id_end'] !== '';
            if (!$hasExplicitRange &&
                (!isset($mapping['card_id_length']) || (int)$mapping['card_id_length'] <= 0)) {
                $mapping['card_id_length'] = $this->inferAuthorCardIdLength($mapping['card_prefix']);
            }
        }
        unset($mapping);

        $this->authorTextRules = $db->getRows(
            'SELECT * FROM card_match_rules WHERE is_enabled = 1 ORDER BY priority DESC, id ASC'
        );
    }

    /**
     * 使当前请求中的管理员规则缓存失效
     *
     * @return void
     */
    public static function invalidateAuthorRuleCache() {
        if (self::$instance !== null) {
            self::$instance->manualAuthorMappings = null;
            self::$instance->authorTextRules = null;
        }
    }

    /**
     * 根据实际CDB分布推断作者码对应的卡号总位数
     *
     * 一至三位作者码按传统八位卡号处理。四位作者码可能对应八位或九位卡号，
     * 因此比较两个候选区间的实际卡片数量；管理员可用card_id_length覆盖推断。
     *
     * @param string $cardPrefix 作者码
     * @return int 卡号总位数
     */
    public function inferAuthorCardIdLength($cardPrefix) {
        $cardPrefix = trim((string)$cardPrefix);
        if (isset($this->authorPrefixLengthCache[$cardPrefix])) {
            return $this->authorPrefixLengthCache[$cardPrefix];
        }

        if (!preg_match('/^\d{1,16}$/', $cardPrefix)) {
            return 8;
        }

        $canonicalPrefix = str_pad($cardPrefix, max(3, strlen($cardPrefix)), '0', STR_PAD_LEFT);
        if (strlen($canonicalPrefix) <= 3) {
            $this->authorPrefixLengthCache[$cardPrefix] = 8;
            return 8;
        }

        if (strlen($canonicalPrefix) !== 4) {
            $inferredLength = max(8, min(16, strlen($canonicalPrefix) + 5));
            $this->authorPrefixLengthCache[$cardPrefix] = $inferredLength;
            return $inferredLength;
        }

        $defaultLength = 9;
        $bestLength = $defaultLength;
        $bestCount = -1;
        foreach ([8, 9] as $candidateLength) {
            $suffixLength = $candidateLength - strlen($canonicalPrefix);
            $rangeStart = (int)($canonicalPrefix . str_repeat('0', $suffixLength));
            $rangeEnd = (int)($canonicalPrefix . str_repeat('9', $suffixLength));
            $count = 0;

            foreach ($this->getCardDatabaseFiles() as $dbFile) {
                try {
                    $db = $this->getCardDatabase($dbFile);
                    $stmt = $db->prepare('SELECT COUNT(*) FROM texts WHERE id BETWEEN :start AND :end');
                    $stmt->bindValue(':start', $rangeStart, PDO::PARAM_INT);
                    $stmt->bindValue(':end', $rangeEnd, PDO::PARAM_INT);
                    $stmt->execute();
                    $count += (int)$stmt->fetchColumn();
                } catch (PDOException $e) {
                    Utils::debug('推断作者卡号位数失败', [
                        'card_prefix' => $cardPrefix,
                        'database' => basename($dbFile),
                        'error' => $e->getMessage()
                    ]);
                }
            }

            if ($count > $bestCount || ($count === $bestCount && $candidateLength === $defaultLength)) {
                $bestCount = $count;
                $bestLength = $candidateLength;
            }
        }

        $this->authorPrefixLengthCache[$cardPrefix] = $bestLength;
        return $bestLength;
    }

    /**
     * 获取strings.conf文件中的作者信息
     *
     * @return array 作者信息数组
     */
    public function getAuthorsFromStringsConf() {
        $authors = [];
        foreach ($this->authors as $author) {
            $authors[(string)$author['card_prefix']] = [
                'name' => $author['author_name'],
                'card_prefix' => (string)$author['card_prefix'],
                'card_id_length' => isset($author['card_id_length']) ? (int)$author['card_id_length'] : null,
                'setcode_ranges' => isset($author['setcode_ranges']) ? $author['setcode_ranges'] : []
            ];
        }
        return $authors;
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
        if (!is_array($files)) {
            $files = [];
        }

        // 如果需要排除特定文件
        if ($excludeFiles && defined('EXCLUDED_CARD_DATABASES')) {
            $excludedFiles = json_decode(EXCLUDED_CARD_DATABASES, true);

            if (is_array($excludedFiles) && !empty($excludedFiles)) {
                $files = array_values(array_filter($files, function($file) use ($excludedFiles) {
                    return !in_array(basename($file), $excludedFiles, true);
                }));
            }
        }

        // glob顺序不是业务契约；显式排序后，重复ID的来源选择在所有环境都一致。
        $priority = [];
        if (defined('CARD_DATABASE_PRIORITY')) {
            $configuredPriority = json_decode(CARD_DATABASE_PRIORITY, true);
            if (is_array($configuredPriority)) {
                foreach ($configuredPriority as $index => $fileName) {
                    $priority[strtolower(basename((string)$fileName))] = (int)$index;
                }
            }
        }

        usort($files, function($left, $right) use ($priority) {
            $leftName = strtolower(basename($left));
            $rightName = strtolower(basename($right));
            $leftPriority = isset($priority[$leftName]) ? $priority[$leftName] : PHP_INT_MAX;
            $rightPriority = isset($priority[$rightName]) ? $priority[$rightName] : PHP_INT_MAX;
            if ($leftPriority !== $rightPriority) {
                return $leftPriority <=> $rightPriority;
            }
            return strnatcasecmp($leftName, $rightName);
        });

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
                " . $this->getCardTextSelectColumns() . "
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
                $card['image_path'] = $this->getCardImagePath($card['id'], $isTcgCard);
                $card['database_file'] = basename($dbFile);
                $this->applyAuthorResolution($card);
            }
            unset($card);

            return $cards;
        } catch (PDOException $e) {
            Utils::debug('获取卡片数据失败', ['错误' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * 分批读取作者榜所需的最小卡片投影
     *
     * 跳过图片路径、类型文本等作者统计不使用的水合步骤，避免强制重建排行榜时
     * 对每张卡执行多次文件系统检查。
     *
     * @param string $dbFile CDB文件路径
     * @param int $page 页码
     * @param int $perPage 每批数量
     * @return array 已带统一作者解析结果的卡片
     */
    public function getCardsForAuthorStats($dbFile, $page = 1, $perPage = 1000) {
        $db = $this->getCardDatabase($dbFile);
        $page = max(1, (int)$page);
        $perPage = max(1, (int)$perPage);
        $offset = ($page - 1) * $perPage;
        $sql = "
            SELECT
                d.id, d.setcode,
                " . $this->getCardTextSelectColumns() . "
            FROM datas d
            JOIN texts t ON d.id = t.id
            ORDER BY d.id
            LIMIT :limit OFFSET :offset
        ";

        try {
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $cards = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($cards as &$card) {
                $card['database_file'] = basename($dbFile);
                $resolution = $this->getCardAuthorResolution($card);
                $card['author'] = $resolution['author'];
            }
            unset($card);
            return $cards;
        } catch (PDOException $e) {
            Utils::debug('读取作者榜卡片批次失败', [
                'database' => basename($dbFile),
                'page' => $page,
                'error' => $e->getMessage()
            ]);
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
                    " . $this->getCardTextSelectColumns() . "
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
                    $card['image_path'] = $this->getCardImagePath($card['id'], $isTcgCard);
                    $card['database_file'] = basename($dbFile);
                    $this->applyAuthorResolution($card);

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
                        " . $this->getCardTextSelectColumns() . "
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
                    $card['image_path'] = $this->getCardImagePath($card['id'], $isTcgCard);
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
                    " . $this->getCardTextSelectColumns() . "
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
                    $card['image_path'] = $this->getCardImagePath($card['id'], $isTcgCard);
                    $card['database_file'] = basename($dbFile);
                    $this->applyAuthorResolution($card);
                }
                unset($card);

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
     * 搜索卡片（带分页）
     *
     * @param string $keyword 关键词
     * @param int $page 页码，从1开始
     * @param int $perPage 每页数量
     * @return array {cards, total, page, per_page, total_pages}
     */
    public function searchCardsPaginated($keyword, $page = 1, $perPage = 20) {
        $keyword = trim($keyword);
        $page = max(1, (int)$page);
        $perPage = max(1, (int)$perPage);

        if ($keyword === '') {
            return [
                'cards' => [],
                'total' => 0,
                'page' => 1,
                'per_page' => $perPage,
                'total_pages' => 0,
            ];
        }

        $dbFiles = $this->getCardDatabaseFiles();
        $isId = is_numeric($keyword);

        // ID搜索：直接返回精确结果
        if ($isId) {
            foreach ($dbFiles as $dbFile) {
                $db = $this->getCardDatabase($dbFile);
                $sql = "
                    SELECT d.id, d.ot, d.alias, d.setcode, d.type, d.atk, d.def, d.level, d.race, d.attribute,
                           " . $this->getCardTextSelectColumns() . "
                    FROM datas d
                    JOIN texts t ON d.id = t.id
                    WHERE d.id = :keyword
                    ORDER BY d.id
                    LIMIT 1
                ";
                try {
                    $stmt = $db->prepare($sql);
                    $stmt->bindValue(':keyword', (int)$keyword, PDO::PARAM_INT);
                    $stmt->execute();
                    $card = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($card) {
                        $isTcgCard = (basename($dbFile) === basename(TCG_CARD_DATA_PATH));
                        $card['setcode_text'] = $this->getSetcodeText($card['setcode'], $isTcgCard);
                        $card['type_text'] = $this->getTypeText($card['type']);
                        $card['race_text'] = $this->getRaceText($card['race']);
                        $card['attribute_text'] = $this->getAttributeText($card['attribute']);
                        $card['level_text'] = $this->getLevelText($card['level']);
                        $card['image_path'] = $this->getCardImagePath($card['id'], $isTcgCard);
                        $card['database_file'] = basename($dbFile);
                        $this->applyAuthorResolution($card);
                        return [
                            'cards' => [$card],
                            'total' => 1,
                            'page' => 1,
                            'per_page' => $perPage,
                            'total_pages' => 1,
                        ];
                    }
                } catch (PDOException $e) {
                    Utils::debug('ID搜索失败', ['错误' => $e->getMessage()]);
                }
            }
            return [
                'cards' => [],
                'total' => 0,
                'page' => 1,
                'per_page' => $perPage,
                'total_pages' => 0,
            ];
        }

        // 非ID搜索：先统计总数
        $counts = [];
        $total = 0;
        foreach ($dbFiles as $dbFile) {
            try {
                $db = $this->getCardDatabase($dbFile);
                $countSql = "
                    SELECT COUNT(*) AS cnt
                    FROM datas d
                    JOIN texts t ON d.id = t.id
                    WHERE t.name LIKE :keyword OR t.desc LIKE :keyword
                ";
                $stmt = $db->prepare($countSql);
                $stmt->bindValue(':keyword', '%' . $keyword . '%', PDO::PARAM_STR);
                $stmt->execute();
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $cnt = $row ? (int)$row['cnt'] : 0;
                $counts[] = $cnt;
                $total += $cnt;
            } catch (PDOException $e) {
                $counts[] = 0;
                Utils::debug('搜索计数失败', ['错误' => $e->getMessage(), 'db' => basename($dbFile)]);
            }
        }

        $totalPages = $total > 0 ? (int)ceil($total / $perPage) : 0;
        if ($totalPages > 0 && $page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $perPage;

        // 获取当前页的数据，跨库合并
        $cards = [];
        $remaining = $perPage;
        foreach ($dbFiles as $i => $dbFile) {
            if ($remaining <= 0) break;
            $dbCount = $counts[$i];
            if ($offset >= $dbCount) {
                $offset -= $dbCount;
                continue;
            }
            $db = $this->getCardDatabase($dbFile);
            $limit = $remaining;
            try {
                $sql = "
                    SELECT d.id, d.ot, d.alias, d.setcode, d.type, d.atk, d.def, d.level, d.race, d.attribute,
                           " . $this->getCardTextSelectColumns() . "
                    FROM datas d
                    JOIN texts t ON d.id = t.id
                    WHERE t.name LIKE :keyword OR t.desc LIKE :keyword
                    ORDER BY d.id
                    LIMIT :limit OFFSET :offset
                ";
                $stmt = $db->prepare($sql);
                $stmt->bindValue(':keyword', '%' . $keyword . '%', PDO::PARAM_STR);
                $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
                $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
                $stmt->execute();
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($results as &$card) {
                    $isTcgCard = (basename($dbFile) === basename(TCG_CARD_DATA_PATH));
                    $card['setcode_text'] = $this->getSetcodeText($card['setcode'], $isTcgCard);
                    $card['type_text'] = $this->getTypeText($card['type']);
                    $card['race_text'] = $this->getRaceText($card['race']);
                    $card['attribute_text'] = $this->getAttributeText($card['attribute']);
                    $card['level_text'] = $this->getLevelText($card['level']);
                    $card['image_path'] = $this->getCardImagePath($card['id'], $isTcgCard);
                    $card['database_file'] = basename($dbFile);
                    $this->applyAuthorResolution($card);
                }
                unset($card);

                $cards = array_merge($cards, $results);
                $remaining -= count($results);
                $offset = 0; // 后续库从0偏移开始

            } catch (PDOException $e) {
                Utils::debug('分页查询失败', ['错误' => $e->getMessage(), 'db' => basename($dbFile)]);
            }
        }

        return [
            'cards' => $cards,
            'total' => $total,
            'page' => $totalPages > 0 ? $page : 1,
            'per_page' => $perPage,
            'total_pages' => $totalPages,
        ];
    }

    /**
     * 高级搜索卡片（带分页）
     *
     * @param string $keyword 关键词
     * @param array $filters 过滤条件
     * @param int $page 页码，从1开始
     * @param int $perPage 每页数量
     * @return array {cards, total, page, per_page, total_pages}
     */
    public function advancedSearchCardsPaginated($keyword, $filters = [], $page = 1, $perPage = 20) {
        $keyword = trim($keyword);
        $page = max(1, (int)$page);
        $perPage = max(1, (int)$perPage);

        $dbFiles = $this->getCardDatabaseFiles();

        // 检查是否只有ID搜索
        $isId = is_numeric($keyword) && empty(array_filter($filters, function($v) {
            return $v !== null && $v !== '' && $v !== [] && $v !== 'and' && $v !== 'or';
        }));

        // ID搜索：直接返回精确结果
        if ($isId && !empty($keyword)) {
            foreach ($dbFiles as $dbFile) {
                $db = $this->getCardDatabase($dbFile);
                $sql = "
                    SELECT d.id, d.ot, d.alias, d.setcode, d.type, d.atk, d.def, d.level, d.race, d.attribute,
                           " . $this->getCardTextSelectColumns() . "
                    FROM datas d
                    JOIN texts t ON d.id = t.id
                    WHERE d.id = :keyword
                    ORDER BY d.id
                    LIMIT 1
                ";
                try {
                    $stmt = $db->prepare($sql);
                    $stmt->bindValue(':keyword', (int)$keyword, PDO::PARAM_INT);
                    $stmt->execute();
                    $card = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($card) {
                        $isTcgCard = (basename($dbFile) === basename(TCG_CARD_DATA_PATH));
                        $card['setcode_text'] = $this->getSetcodeText($card['setcode'], $isTcgCard);
                        $card['type_text'] = $this->getTypeText($card['type']);
                        $card['race_text'] = $this->getRaceText($card['race']);
                        $card['attribute_text'] = $this->getAttributeText($card['attribute']);
                        $card['level_text'] = $this->getLevelText($card['level']);
                        $card['image_path'] = $this->getCardImagePath($card['id'], $isTcgCard);
                        $card['database_file'] = basename($dbFile);
                        $this->applyAuthorResolution($card);
                        return [
                            'cards' => [$card],
                            'total' => 1,
                            'page' => 1,
                            'per_page' => $perPage,
                            'total_pages' => 1,
                        ];
                    }
                } catch (PDOException $e) {
                    Utils::debug('ID搜索失败', ['错误' => $e->getMessage()]);
                }
            }
            return [
                'cards' => [],
                'total' => 0,
                'page' => 1,
                'per_page' => $perPage,
                'total_pages' => 0,
            ];
        }

        // 构建WHERE条件
        $whereConditions = [];
        $params = [];

        // 关键词搜索
        if (!empty($keyword)) {
            $whereConditions[] = "(t.name LIKE :keyword OR t.desc LIKE :keyword)";
            $params[':keyword'] = '%' . $keyword . '%';
        }

        // 卡片类型过滤 - 使用十进制值
        if (!empty($filters['card_type'])) {
            switch ($filters['card_type']) {
                case 'monster':
                    $whereConditions[] = "(d.type & 1) = 1"; // 怪兽卡
                    break;
                case 'spell':
                    $whereConditions[] = "(d.type & 2) = 2"; // 魔法卡
                    break;
                case 'trap':
                    $whereConditions[] = "(d.type & 4) = 4"; // 陷阱卡
                    break;
            }
        }

        // 属性过滤 (OR逻辑) - 属性是单一值，使用直接比较
        if (!empty($filters['attribute']) && is_array($filters['attribute'])) {
            $attrConditions = [];
            foreach ($filters['attribute'] as $i => $attr) {
                $attrConditions[] = "d.attribute = :attr{$i}";
                $params[":attr{$i}"] = $attr;
            }
            if (!empty($attrConditions)) {
                $whereConditions[] = "(" . implode(" OR ", $attrConditions) . ")";
            }
        }

        // 魔法/陷阱类型过滤 (OR逻辑) - 类型是位掩码，使用位运算
        if (!empty($filters['spell_trap_type']) && is_array($filters['spell_trap_type'])) {
            $stConditions = [];
            foreach ($filters['spell_trap_type'] as $i => $stType) {
                $stConditions[] = "(d.type & " . intval($stType) . ") = " . intval($stType);
            }
            if (!empty($stConditions)) {
                $whereConditions[] = "(" . implode(" OR ", $stConditions) . ")";
            }
        }

        // 种族过滤 (OR逻辑) - 种族是位掩码，使用位运算
        if (!empty($filters['race']) && is_array($filters['race'])) {
            $raceConditions = [];
            foreach ($filters['race'] as $i => $race) {
                $raceConditions[] = "(d.race & " . intval($race) . ") = " . intval($race);
            }
            if (!empty($raceConditions)) {
                $whereConditions[] = "(" . implode(" OR ", $raceConditions) . ")";
            }
        }

        // 包含类型过滤 - 使用内联整数值避免SQLite位运算绑定问题
        if (!empty($filters['type_include']) && is_array($filters['type_include'])) {
            $logic = isset($filters['type_logic']) && $filters['type_logic'] === 'or' ? 'OR' : 'AND';
            $typeConditions = [];
            foreach ($filters['type_include'] as $type) {
                $typeVal = intval($type);
                $typeConditions[] = "(d.type & {$typeVal}) = {$typeVal}";
            }
            if (!empty($typeConditions)) {
                $whereConditions[] = "(" . implode(" {$logic} ", $typeConditions) . ")";
            }
        }

        // 排除类型过滤
        if (!empty($filters['type_exclude']) && is_array($filters['type_exclude'])) {
            foreach ($filters['type_exclude'] as $type) {
                $typeVal = intval($type);
                $whereConditions[] = "(d.type & {$typeVal}) = 0";
            }
        }

        // 等级/阶级过滤 (OR逻辑)
        if (!empty($filters['level']) && is_array($filters['level'])) {
            $levelConditions = [];
            foreach ($filters['level'] as $level) {
                $levelVal = intval($level);
                $levelConditions[] = "(d.level & 255) = {$levelVal}";
            }
            if (!empty($levelConditions)) {
                $whereConditions[] = "(" . implode(" OR ", $levelConditions) . ")";
            }
        }

        // 灵摆刻度过滤 (OR逻辑) - 刻度存储在level的高位
        if (!empty($filters['scale']) && is_array($filters['scale'])) {
            $scaleConditions = [];
            foreach ($filters['scale'] as $scale) {
                $scaleVal = intval($scale);
                // 灵摆刻度存储在level字段的第24-27位（左刻度）或第16-19位（右刻度）
                $scaleConditions[] = "(((d.level >> 24) & 15) = {$scaleVal} OR ((d.level >> 16) & 15) = {$scaleVal})";
            }
            if (!empty($scaleConditions)) {
                // 同时确保是灵摆怪兽 (0x1000000 = 16777216)
                $whereConditions[] = "((d.type & 16777216) = 16777216 AND (" . implode(" OR ", $scaleConditions) . "))";
            }
        }

        // 连接值过滤 (连接数 = level & 0xFF，对于连接怪兽)
        if (!empty($filters['link_value']) && is_array($filters['link_value'])) {
            $linkConditions = [];
            foreach ($filters['link_value'] as $linkVal) {
                $linkValInt = intval($linkVal);
                $linkConditions[] = "(d.level & 255) = {$linkValInt}";
            }
            if (!empty($linkConditions)) {
                // 确保是连接怪兽 (0x4000000 = 67108864)
                $whereConditions[] = "((d.type & 67108864) = 67108864 AND (" . implode(" OR ", $linkConditions) . "))";
            }
        }

        // 连接标记过滤 (连接标记存储在def字段)
        if (!empty($filters['link_markers']) && is_array($filters['link_markers'])) {
            $logic = isset($filters['link_logic']) && $filters['link_logic'] === 'and' ? 'AND' : 'OR';
            $markerConditions = [];
            foreach ($filters['link_markers'] as $marker) {
                $markerVal = intval($marker);
                $markerConditions[] = "(d.def & {$markerVal}) = {$markerVal}";
            }
            if (!empty($markerConditions)) {
                // 确保是连接怪兽 (0x4000000 = 67108864)
                $whereConditions[] = "((d.type & 67108864) = 67108864 AND (" . implode(" {$logic} ", $markerConditions) . "))";
            }
        }

        // 攻击力范围
        if (isset($filters['atk_min'])) {
            $whereConditions[] = "d.atk >= :atk_min";
            $params[':atk_min'] = $filters['atk_min'];
        }
        if (isset($filters['atk_max'])) {
            $whereConditions[] = "d.atk <= :atk_max";
            $params[':atk_max'] = $filters['atk_max'];
        }

        // 守备力范围
        if (isset($filters['def_min'])) {
            $whereConditions[] = "d.def >= :def_min";
            $params[':def_min'] = $filters['def_min'];
        }
        if (isset($filters['def_max'])) {
            $whereConditions[] = "d.def <= :def_max";
            $params[':def_max'] = $filters['def_max'];
        }

        // 如果没有任何条件，返回空结果
        if (empty($whereConditions)) {
            return [
                'cards' => [],
                'total' => 0,
                'page' => 1,
                'per_page' => $perPage,
                'total_pages' => 0,
            ];
        }

        $whereClause = implode(" AND ", $whereConditions);

        // 先统计总数
        $counts = [];
        $total = 0;
        foreach ($dbFiles as $dbFile) {
            try {
                $db = $this->getCardDatabase($dbFile);
                $countSql = "
                    SELECT COUNT(*) AS cnt
                    FROM datas d
                    JOIN texts t ON d.id = t.id
                    WHERE {$whereClause}
                ";
                $stmt = $db->prepare($countSql);
                foreach ($params as $key => $value) {
                    $stmt->bindValue($key, $value);
                }
                $stmt->execute();
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $cnt = $row ? (int)$row['cnt'] : 0;
                $counts[] = $cnt;
                $total += $cnt;
            } catch (PDOException $e) {
                $counts[] = 0;
                Utils::debug('高级搜索计数失败', ['错误' => $e->getMessage(), 'db' => basename($dbFile)]);
            }
        }

        $totalPages = $total > 0 ? (int)ceil($total / $perPage) : 0;
        if ($totalPages > 0 && $page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $perPage;

        // 获取当前页的数据
        $cards = [];
        $remaining = $perPage;
        foreach ($dbFiles as $i => $dbFile) {
            if ($remaining <= 0) break;
            $dbCount = $counts[$i];
            if ($offset >= $dbCount) {
                $offset -= $dbCount;
                continue;
            }
            $db = $this->getCardDatabase($dbFile);
            $limit = $remaining;
            try {
                $sql = "
                    SELECT d.id, d.ot, d.alias, d.setcode, d.type, d.atk, d.def, d.level, d.race, d.attribute,
                           " . $this->getCardTextSelectColumns() . "
                    FROM datas d
                    JOIN texts t ON d.id = t.id
                    WHERE {$whereClause}
                    ORDER BY d.id
                    LIMIT :limit OFFSET :offset
                ";
                $stmt = $db->prepare($sql);
                foreach ($params as $key => $value) {
                    $stmt->bindValue($key, $value);
                }
                $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
                $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
                $stmt->execute();
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($results as &$card) {
                    $isTcgCard = (basename($dbFile) === basename(TCG_CARD_DATA_PATH));
                    $card['setcode_text'] = $this->getSetcodeText($card['setcode'], $isTcgCard);
                    $card['type_text'] = $this->getTypeText($card['type']);
                    $card['race_text'] = $this->getRaceText($card['race']);
                    $card['attribute_text'] = $this->getAttributeText($card['attribute']);
                    $card['level_text'] = $this->getLevelText($card['level']);
                    $card['image_path'] = $this->getCardImagePath($card['id'], $isTcgCard);
                    $card['database_file'] = basename($dbFile);
                    $this->applyAuthorResolution($card);
                }
                unset($card);

                $cards = array_merge($cards, $results);
                $remaining -= count($results);
                $offset = 0;

            } catch (PDOException $e) {
                Utils::debug('高级搜索分页查询失败', ['错误' => $e->getMessage(), 'db' => basename($dbFile)]);
            }
        }

        return [
            'cards' => $cards,
            'total' => $total,
            'page' => $totalPages > 0 ? $page : 1,
            'per_page' => $perPage,
            'total_pages' => $totalPages,
        ];
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
     * @param bool $isTcgCard 是否为TCG卡片
     * @return string 图片路径
     */
    public function getCardImagePath($cardId, $isTcgCard = false) {
        // 如果是TCG卡片，优先使用TCG_CARD_IMAGE_PATH
        if ($isTcgCard) {
            $tcgImagePath = defined('TCG_CARD_IMAGE_PATH') ? TCG_CARD_IMAGE_PATH : '';
            if (!empty($tcgImagePath) && is_dir($tcgImagePath)) {
                // 尝试多种文件名格式
                $formats = [
                    $tcgImagePath . '/' . $cardId . '.jpg',
                    $tcgImagePath . '/' . $cardId . '.png',
                    $tcgImagePath . '/c' . $cardId . '.jpg',
                    $tcgImagePath . '/c' . $cardId . '.png'
                ];

                foreach ($formats as $path) {
                    if (file_exists($path)) {
                        return BASE_URL . 'tcg_pics/' . basename($path);
                    }
                }
            }
            // TCG卡片没找到图片，返回卡背
            return BASE_URL . 'assets/images/card_back.jpg';
        }

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
                        " . $this->getCardTextSelectColumns() . "
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
                            " . $this->getCardTextSelectColumns() . "
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
                    $card['image_path'] = $this->getCardImagePath($card['id'], $isTcgCard);
                    $card['database_file'] = basename($dbFile);
                    $this->applyAuthorResolution($card);
                }
                unset($card);

                $cards = array_merge($cards, $results);

            } catch (PDOException $e) {
                Utils::debug('获取系列卡片数据失败', ['错误' => $e->getMessage(), '数据库' => $dbFile]);
            }
        }

        return $cards;
    }

    /**
     * 随机获取一张卡片
     *
     * @return array|null 卡片信息
     */
    public function getRandomCard() {
        $dbFiles = $this->getCardDatabaseFiles();
        if (empty($dbFiles)) {
            return null;
        }

        // 随机选择数据库文件
        $dbFile = $dbFiles[array_rand($dbFiles)];
        try {
            $db = $this->getCardDatabase($dbFile);
            $stmt = $db->query('SELECT id FROM texts ORDER BY RANDOM() LIMIT 1');
            $cardId = $stmt->fetchColumn();
            if ($cardId) {
                return $this->getCardById($cardId);
            }
        } catch (PDOException $e) {
            Utils::debug('随机获取卡片失败', ['错误' => $e->getMessage(), '数据库' => $dbFile]);
        }
        return null;
    }

    /**
     * 按统一解析结果获取同一作者的卡片
     *
     * 先用人工区间、人工文本规则、strings.conf区间和作者署名中的名称生成候选，
     * 再逐张交给统一解析器复核。这样不会退回到固定截取卡号前三位的旧逻辑，
     * 同时避免在主页请求中完整解析全部CDB。
     *
     * @param string $authorName 规范作者名
     * @param int|null $excludeId 需要排除的卡号
     * @param int $limit 最多返回数量
     * @return array 卡片列表
     */
    public function getCardsByAuthor($authorName, $excludeId = null, $limit = 10) {
        $authorName = trim((string)$authorName);
        $excludeId = $excludeId !== null ? (int)$excludeId : null;
        $limit = max(0, (int)$limit);
        if ($authorName === '' || $authorName === AuthorResolver::UNKNOWN_AUTHOR || $limit === 0) {
            return [];
        }

        $this->loadRuntimeAuthorRules();
        $structuredCandidateIds = [];
        $searchTerms = [$authorName];
        $rangeMappings = [];
        $textRules = [];

        foreach ($this->manualAuthorMappings as $mapping) {
            if (!isset($mapping['author_name']) || trim((string)$mapping['author_name']) !== $authorName) {
                continue;
            }
            $rangeMappings[] = $mapping;
            if (!empty($mapping['alias'])) {
                foreach (explode(',', $mapping['alias']) as $alias) {
                    $alias = trim($alias);
                    if ($alias !== '') {
                        $searchTerms[] = $alias;
                    }
                }
            }
        }

        foreach ($this->authors as $mapping) {
            if (isset($mapping['author_name']) && trim((string)$mapping['author_name']) === $authorName) {
                $rangeMappings[] = $mapping;
            }
        }

        foreach ($this->authorTextRules as $rule) {
            if ($this->getTextRuleTargetType($rule) === 'author' &&
                $this->getTextRuleTargetValue($rule) === $authorName) {
                $textRules[] = $rule;
            }
        }

        $searchTerms = array_values(array_unique($searchTerms));
        foreach ($this->getCardDatabaseFiles() as $dbFile) {
            try {
                $db = $this->getCardDatabase($dbFile);
                foreach ($rangeMappings as $mapping) {
                    $range = $this->getAuthorMappingNumericRange($mapping);
                    if ($range !== null) {
                        $this->collectCandidateIdsByRange($db, $range[0], $range[1], $structuredCandidateIds);
                    }
                }
                foreach ($textRules as $rule) {
                    if ($this->textRuleAppliesToDatabase($rule, $dbFile)) {
                        $this->collectCandidateIdsByTextRule($db, $rule, $structuredCandidateIds);
                    }
                }
            } catch (PDOException $e) {
                Utils::debug('查找同作者卡片候选失败', [
                    'author' => $authorName,
                    'database' => basename($dbFile),
                    'error' => $e->getMessage()
                ]);
            }
        }

        $cards = [];
        $visitedIds = [];
        $this->appendResolvedAuthorCards(
            $structuredCandidateIds,
            $authorName,
            $excludeId,
            $limit,
            $cards,
            $visitedIds
        );
        if (count($cards) >= $limit) {
            return $cards;
        }

        // 仅当结构化人工来源不足时才扫描CDB署名；所有别名合并为每个CDB一次查询。
        $signatureCandidateIds = [];
        foreach ($this->getCardDatabaseFiles() as $dbFile) {
            try {
                $this->collectCandidateIdsBySignatureTerms(
                    $this->getCardDatabase($dbFile),
                    $searchTerms,
                    $signatureCandidateIds
                );
            } catch (PDOException $e) {
                Utils::debug('查找CDB署名候选失败', [
                    'author' => $authorName,
                    'database' => basename($dbFile),
                    'error' => $e->getMessage()
                ]);
            }
        }
        $this->appendResolvedAuthorCards(
            $signatureCandidateIds,
            $authorName,
            $excludeId,
            $limit,
            $cards,
            $visitedIds
        );

        return $cards;
    }

    /**
     * 复核候选卡片并追加统一解析后属于指定作者的卡片
     *
     * @param array $candidateIds 以卡号为键的候选集合
     * @param string $authorName 规范作者名
     * @param int|null $excludeId 排除卡号
     * @param int $limit 最多返回数量
     * @param array $cards 已确认卡片
     * @param array $visitedIds 已复核卡号集合
     * @return void
     */
    private function appendResolvedAuthorCards(
        $candidateIds,
        $authorName,
        $excludeId,
        $limit,
        &$cards,
        &$visitedIds
    ) {
        $ids = array_keys($candidateIds);
        shuffle($ids);
        foreach ($ids as $cardId) {
            $cardId = (int)$cardId;
            if (isset($visitedIds[$cardId]) || ($excludeId !== null && $cardId === $excludeId)) {
                continue;
            }
            $visitedIds[$cardId] = true;
            $card = $this->getCardById($cardId);
            if ($card && isset($card['author']) && $card['author'] === $authorName) {
                $cards[] = $card;
                if (count($cards) >= $limit) {
                    return;
                }
            }
        }
    }

    /**
     * 按人工文本系列分组获取其他卡片
     *
     * @param string $seriesName 人工系列分组名
     * @param int|null $excludeId 需要排除的卡号
     * @param int $limit 最多返回数量
     * @return array 卡片列表
     */
    public function getCardsByManualSeries($seriesName, $excludeId = null, $limit = 10) {
        $seriesName = trim((string)$seriesName);
        $excludeId = $excludeId !== null ? (int)$excludeId : null;
        $limit = max(0, (int)$limit);
        if ($seriesName === '' || $limit === 0) {
            return [];
        }

        $this->loadRuntimeAuthorRules();
        $candidateIds = [];
        foreach ($this->getCardDatabaseFiles() as $dbFile) {
            try {
                $db = $this->getCardDatabase($dbFile);
                foreach ($this->authorTextRules as $rule) {
                    if ($this->getTextRuleTargetType($rule) !== 'series' ||
                        $this->getTextRuleTargetValue($rule) !== $seriesName ||
                        !$this->textRuleAppliesToDatabase($rule, $dbFile)) {
                        continue;
                    }
                    $this->collectCandidateIdsByTextRule($db, $rule, $candidateIds);
                }
            } catch (PDOException $e) {
                Utils::debug('查找人工同系列卡片候选失败', [
                    'series' => $seriesName,
                    'database' => basename($dbFile),
                    'error' => $e->getMessage()
                ]);
            }
        }

        $candidateIds = array_keys($candidateIds);
        shuffle($candidateIds);
        $cards = [];
        foreach ($candidateIds as $cardId) {
            if ($excludeId !== null && (int)$cardId === $excludeId) {
                continue;
            }
            $card = $this->getCardById((int)$cardId);
            if ($card && isset($card['manual_series_name']) && $card['manual_series_name'] === $seriesName) {
                $cards[] = $card;
                if (count($cards) >= $limit) {
                    break;
                }
            }
        }

        return $cards;
    }

    /**
     * 将作者区间转换为SQLite整数卡号区间
     *
     * @param array $mapping 作者映射
     * @return array|null 起止卡号
     */
    private function getAuthorMappingNumericRange($mapping) {
        if (isset($mapping['card_id_start'], $mapping['card_id_end']) &&
            $mapping['card_id_start'] !== null && $mapping['card_id_start'] !== '' &&
            $mapping['card_id_end'] !== null && $mapping['card_id_end'] !== '') {
            return [(int)$mapping['card_id_start'], (int)$mapping['card_id_end']];
        }
        if (!isset($mapping['card_prefix']) || !preg_match('/^\d{1,16}$/', (string)$mapping['card_prefix'])) {
            return null;
        }

        $prefix = trim((string)$mapping['card_prefix']);
        $canonicalPrefix = str_pad($prefix, max(3, strlen($prefix)), '0', STR_PAD_LEFT);
        $cardIdLength = isset($mapping['card_id_length']) && (int)$mapping['card_id_length'] > 0
            ? (int)$mapping['card_id_length']
            : $this->inferAuthorCardIdLength($prefix);
        if ($cardIdLength < strlen($canonicalPrefix) || $cardIdLength > 16) {
            return null;
        }

        $suffixLength = $cardIdLength - strlen($canonicalPrefix);
        return [
            (int)($canonicalPrefix . str_repeat('0', $suffixLength)),
            (int)($canonicalPrefix . str_repeat('9', $suffixLength))
        ];
    }

    /**
     * 收集整数卡号区间内的候选卡号
     *
     * @param PDO $db CDB连接
     * @param int $start 起始卡号
     * @param int $end 结束卡号
     * @param array $candidateIds 候选集合
     * @return void
     */
    private function collectCandidateIdsByRange($db, $start, $end, &$candidateIds) {
        $stmt = $db->prepare('SELECT id FROM texts WHERE id BETWEEN :start AND :end');
        $stmt->bindValue(':start', (int)$start, PDO::PARAM_INT);
        $stmt->bindValue(':end', (int)$end, PDO::PARAM_INT);
        $stmt->execute();
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $cardId) {
            $candidateIds[(int)$cardId] = true;
        }
    }

    /**
     * 一次扫描收集包含作者名/别名且具有署名标记的CDB候选
     *
     * @param PDO $db CDB连接
     * @param array $terms 规范作者名与别名
     * @param array $candidateIds 候选集合
     * @return void
     */
    private function collectCandidateIdsBySignatureTerms($db, $terms, &$candidateIds) {
        $terms = array_values(array_unique(array_filter(array_map('trim', $terms), function($term) {
            return $term !== '';
        })));
        if (empty($terms)) {
            return;
        }

        $params = [];
        $conditions = [];
        $signatureFields = array_values(array_filter($this->getCardTextFieldNames(), function($field) {
            return $field !== 'name';
        }));
        foreach ($signatureFields as $field) {
            $expression = "lower(COALESCE({$field}, ''))";
            $termConditions = [];
            foreach ($terms as $index => $term) {
                $parameter = ':author_term_' . $index;
                $termConditions[] = 'instr(' . $expression . ', lower(' . $parameter . ')) > 0';
                $params[$parameter] = $term;
            }
            // 这里只做宽松超集预筛；最终格式仍由AuthorResolver逐行正则确认。
            // token共现可覆盖普通空格、tab及Unicode空白，避免候选层比解析层更窄。
            $markerCondition = "(instr({$expression}, 'do') > 0"
                . " AND instr({$expression}, 'it') > 0"
                . " AND (instr({$expression}, 'yourself') > 0 OR instr({$expression}, 'youself') > 0))"
                . " OR (instr({$expression}, 'dolt') > 0 AND instr({$expression}, 'yourself') > 0)"
                . " OR instr({$expression}, 'diy') > 0"
                . " OR instr({$expression}, 'dly') > 0"
                . " OR (instr({$expression}, 'card') > 0 AND instr({$expression}, 'design') > 0)";
            $conditions[] = '((' . implode(' OR ', $termConditions) . ') AND (' . $markerCondition . '))';
        }

        $stmt = $db->prepare('SELECT id FROM texts WHERE ' . implode(' OR ', $conditions));
        $stmt->execute($params);
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $cardId) {
            $candidateIds[(int)$cardId] = true;
        }
    }

    /**
     * 收集人工文本规则可能命中的候选卡号
     *
     * SQL只做包含式预筛，最终仍由AuthorResolver按完整运算符和大小写设置复核。
     *
     * @param PDO $db CDB连接
     * @param array $rule 文本规则
     * @param array $candidateIds 候选集合
     * @return void
     */
    private function collectCandidateIdsByTextRule($db, $rule, &$candidateIds) {
        if (!isset($rule['match_value']) || trim((string)$rule['match_value']) === '') {
            return;
        }
        $field = isset($rule['match_field']) ? (string)$rule['match_field'] : 'desc';
        $fields = $field === 'any' ? $this->getCardTextFieldNames() : [$field];
        $validFields = $this->getCardTextFieldNames();
        $conditions = [];
        foreach ($fields as $candidateField) {
            if (in_array($candidateField, $validFields, true)) {
                $conditions[] = 'instr(lower(COALESCE(' . $candidateField . ', "")), lower(:needle)) > 0';
            }
        }
        if (empty($conditions)) {
            return;
        }
        $stmt = $db->prepare('SELECT id FROM texts WHERE ' . implode(' OR ', $conditions));
        $stmt->execute([':needle' => trim((string)$rule['match_value'])]);
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $cardId) {
            $candidateIds[(int)$cardId] = true;
        }
    }

    /**
     * 判断文本规则是否适用于指定CDB
     *
     * @param array $rule 文本规则
     * @param string $dbFile CDB路径
     * @return bool 是否适用
     */
    private function textRuleAppliesToDatabase($rule, $dbFile) {
        $scope = isset($rule['database_file']) ? trim((string)$rule['database_file']) : '';
        return $scope === '' || $scope === '*' || strcasecmp(basename($scope), basename($dbFile)) === 0;
    }

    /**
     * 获取文本规则目标类型，并兼容迁移前规则
     *
     * @param array $rule 文本规则
     * @return string author或series
     */
    private function getTextRuleTargetType($rule) {
        return isset($rule['target_type']) && strtolower(trim((string)$rule['target_type'])) === 'series'
            ? 'series'
            : 'author';
    }

    /**
     * 获取文本规则目标值，并兼容旧author_name列
     *
     * @param array $rule 文本规则
     * @return string 目标名称
     */
    private function getTextRuleTargetValue($rule) {
        if (isset($rule['target_value']) && trim((string)$rule['target_value']) !== '') {
            return trim((string)$rule['target_value']);
        }
        return $this->getTextRuleTargetType($rule) === 'author' && isset($rule['author_name'])
            ? trim((string)$rule['author_name'])
            : '';
    }

    /**
     * 获取CDB可匹配文本字段名
     *
     * @return array 字段名
     */
    private function getCardTextFieldNames() {
        $fields = ['name', 'desc'];
        for ($index = 1; $index <= 16; $index++) {
            $fields[] = 'str' . $index;
        }
        return $fields;
    }

    /**
     * 获取卡片查询中固定的文本字段投影
     *
     * 字段名仅来自CDB固定schema，不接受用户输入。
     *
     * @return string 以t为表别名的SQL字段列表
     */
    private function getCardTextSelectColumns() {
        $columns = [];
        foreach ($this->getCardTextFieldNames() as $field) {
            $columns[] = 't.' . $field;
        }
        return implode(', ', $columns);
    }

    /**
     * 根据卡片前缀获取卡片列表
     *
     * @param int $prefix 卡片前缀（卡号前三位；若为7位卡号则取前两位）
     * @return array 卡片列表
     */
    public function getCardsByPrefix($prefix) {
        $cards = [];
        $dbFiles = $this->getCardDatabaseFiles();
        $start = $prefix * 100000;
        $end = $start + 99999;

        foreach ($dbFiles as $dbFile) {
            try {
                $db = $this->getCardDatabase($dbFile);
                $stmt = $db->prepare('SELECT id FROM texts WHERE id BETWEEN :start AND :end');
                $stmt->execute(['start' => $start, 'end' => $end]);
                $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
                foreach ($ids as $id) {
                    $card = $this->getCardById($id);
                    if ($card) {
                        $cards[] = $card;
                    }
                }
            } catch (PDOException $e) {
                Utils::debug('根据前缀获取卡片失败', ['错误' => $e->getMessage(), '数据库' => $dbFile]);
            }
        }

        return $cards;
    }
}
