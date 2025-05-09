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
        $cardDataPath = CARD_DATA_PATH;
        $stringsFile = $cardDataPath . '/strings.conf';

        if (file_exists($stringsFile)) {
            $content = file_get_contents($stringsFile);
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
                        $this->setcodes[$code] = $name;
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
                }
            }
        }
    }

    /**
     * 获取卡片作者
     *
     * @param array $card 卡片信息
     * @return string|null 作者名称
     */
    public function getCardAuthor($card) {
        // 首先检查卡片描述中是否有作者签名
        $desc = $card['desc'];
        if (preg_match('/(?:DoItYourself|DIY)(?:\s*[-—_:：]+\s*|\s+by\s+)([^\n\r]+)/i', $desc, $matches)) {
            // 清理作者名称，移除可能的额外分隔符
            $authorName = trim($matches[1]);
            // 移除开头可能存在的分隔符
            $authorName = preg_replace('/^[-—_:：\s]+/', '', $authorName);
            return trim($authorName);
        }

        // 如果描述中没有作者信息，则根据卡片ID前缀查找
        $cardId = (string)$card['id'];
        foreach ($this->authors as $prefix => $authorInfo) {
            // 确保 $prefix 是字符串类型
            $prefixStr = (string)$prefix;
            if (strpos($cardId, $prefixStr) === 0) {
                return $authorInfo['name'];
            }
        }

        return null;
    }

    /**
     * 获取卡片数据库连接
     *
     * @param string $dbFile 数据库文件路径
     * @return PDO 数据库连接
     */
    private function getCardDatabase($dbFile) {
        if (!isset($this->cardDatabases[$dbFile])) {
            try {
                $this->cardDatabases[$dbFile] = new PDO('sqlite:' . $dbFile);
                $this->cardDatabases[$dbFile]->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                die('卡片数据库连接失败: ' . $e->getMessage());
            }
        }

        return $this->cardDatabases[$dbFile];
    }

    /**
     * 获取所有卡片数据库文件
     *
     * @return array 数据库文件列表
     */
    public function getCardDatabaseFiles() {
        $cardDataPath = CARD_DATA_PATH;
        $files = glob($cardDataPath . '/*.cdb');

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
            foreach ($dbFiles as $file) {
                if ($countOnly) {
                    $totalCards += $this->countCardsInDatabase($file);
                } else {
                    // 注意：当获取所有数据库的卡片时，分页逻辑会变得复杂
                    // 这里简化处理，先获取所有卡片，然后在内存中分页
                    $cardsFromDb = $this->getCardsFromDatabase($file);
                    $cards = array_merge($cards, $cardsFromDb);
                }
            }

            if (!$countOnly && !empty($cards)) {
                // 在内存中进行分页
                $totalCards = count($cards);
                $offset = ($page - 1) * $perPage;
                $cards = array_slice($cards, $offset, $perPage);
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
                $card['setcode_text'] = $this->getSetcodeText($card['setcode']);
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
    public function getCardById($cardId) {
        $dbFiles = $this->getCardDatabaseFiles();

        foreach ($dbFiles as $dbFile) {
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
                    d.id = :id
            ";

            try {
                $stmt = $db->prepare($sql);
                $stmt->execute(['id' => $cardId]);
                $card = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($card) {
                    $card['setcode_text'] = $this->getSetcodeText($card['setcode']);
                    $card['type_text'] = $this->getTypeText($card['type']);
                    $card['race_text'] = $this->getRaceText($card['race']);
                    $card['attribute_text'] = $this->getAttributeText($card['attribute']);
                    $card['level_text'] = $this->getLevelText($card['level']);
                    $card['image_path'] = $this->getCardImagePath($card['id']);
                    $card['database_file'] = basename($dbFile);
                    $card['author'] = $this->getCardAuthor($card);

                    return $card;
                }
            } catch (PDOException $e) {
                error_log('获取卡片数据失败: ' . $e->getMessage());
            }
        }

        return null;
    }

    /**
     * 搜索卡片
     *
     * @param string $keyword 关键词
     * @return array 卡片列表
     */
    public function searchCards($keyword) {
        $keyword = trim($keyword);

        if (empty($keyword)) {
            return [];
        }

        $cards = [];
        $dbFiles = $this->getCardDatabaseFiles();

        // 检查是否是数字（卡片ID）
        $isId = is_numeric($keyword);

        foreach ($dbFiles as $dbFile) {
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
                $params = ['keyword' => (int)$keyword];
            } else {
                $sql .= "t.name LIKE :keyword OR t.desc LIKE :keyword";
                $params = ['keyword' => '%' . $keyword . '%'];
            }

            $sql .= " ORDER BY d.id";

            try {
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($results as &$card) {
                    $card['setcode_text'] = $this->getSetcodeText($card['setcode']);
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
            } catch (PDOException $e) {
                error_log('搜索卡片失败: ' . $e->getMessage());
            }
        }

        return $cards;
    }

    /**
     * 获取系列文本
     *
     * @param int $setcode 系列代码
     * @return string 系列文本
     */
    public function getSetcodeText($setcode) {
        $hexSetcode = '0x' . dechex($setcode);

        if (isset($this->setcodes[$hexSetcode])) {
            return $this->setcodes[$hexSetcode];
        }

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
}
