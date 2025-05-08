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
     * 构造函数
     */
    private function __construct() {
        // 加载卡片信息映射
        $this->loadCardInfoMappings();
        
        // 加载系列信息
        $this->loadSetcodes();
        
        // 加载禁限信息
        $this->loadLflist();
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
        
        $content = file_get_contents($cardInfoFile);
        $lines = explode("\n", $content);
        
        $currentSection = '';
        
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
            
            // 解析数据行
            $parts = explode("\t", $line);
            if (count($parts) >= 2) {
                $code = trim($parts[0]);
                $name = trim($parts[1]);
                
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
     * 获取所有卡片
     * 
     * @param string $dbFile 数据库文件路径，如果为null则获取所有数据库的卡片
     * @return array 卡片列表
     */
    public function getAllCards($dbFile = null) {
        $cards = [];
        
        if ($dbFile !== null) {
            $cards = $this->getCardsFromDatabase($dbFile);
        } else {
            $dbFiles = $this->getCardDatabaseFiles();
            foreach ($dbFiles as $file) {
                $cardsFromDb = $this->getCardsFromDatabase($file);
                $cards = array_merge($cards, $cardsFromDb);
            }
        }
        
        return $cards;
    }
    
    /**
     * 从数据库获取卡片
     * 
     * @param string $dbFile 数据库文件路径
     * @return array 卡片列表
     */
    private function getCardsFromDatabase($dbFile) {
        $db = $this->getCardDatabase($dbFile);
        
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
        ";
        
        try {
            $stmt = $db->query($sql);
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
            }
            
            return $cards;
        } catch (PDOException $e) {
            error_log('获取卡片数据失败: ' . $e->getMessage());
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
        $hexType = '0x' . dechex($type);
        $typeTexts = [];
        
        foreach ($this->types as $code => $name) {
            $hexCode = hexdec($code);
            if (($type & $hexCode) == $hexCode && $hexCode != 0) {
                $typeTexts[] = $name;
            }
        }
        
        return implode('/', $typeTexts);
    }
    
    /**
     * 获取种族文本
     * 
     * @param int $race 种族代码
     * @return string 种族文本
     */
    public function getRaceText($race) {
        $hexRace = '0x' . dechex($race);
        
        if (isset($this->races[$hexRace])) {
            return $this->races[$hexRace];
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
        
        if (isset($this->attributes[$hexAttribute])) {
            return $this->attributes[$hexAttribute];
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
        $picPath = 'pics/c' . $cardId . '.jpg';
        
        if (file_exists(__DIR__ . '/../../' . $picPath)) {
            return $picPath;
        }
        
        $picPath = 'pics/c' . $cardId . '.png';
        
        if (file_exists(__DIR__ . '/../../' . $picPath)) {
            return $picPath;
        }
        
        return 'assets/images/card_back.jpg';
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
        $environments = [];
        
        foreach ($this->lflist as $env => $cards) {
            $environments[] = $env;
        }
        
        return $environments;
    }
}
