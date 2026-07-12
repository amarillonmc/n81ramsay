<?php
/**
 * 卡片字段匹配规则模型
 *
 * 管理按CDB来源及文本字段手工指定作者或系列分组的高优先级规则。
 */
class CardMatchRule {
    /**
     * 数据库实例
     * @var Database
     */
    private $db;

    /**
     * 允许写入的字段
     * @var array
     */
    private $writableFields = [
        'database_file',
        'match_field',
        'match_operator',
        'match_value',
        'target_type',
        'target_value',
        'author_name',
        'priority',
        'is_case_sensitive',
        'is_enabled',
        'notes'
    ];

    /**
     * 允许匹配的CDB文本字段
     * @var array
     */
    private $allowedMatchFields = [
        'name',
        'desc',
        'str1',
        'str2',
        'str3',
        'str4',
        'str5',
        'str6',
        'str7',
        'str8',
        'str9',
        'str10',
        'str11',
        'str12',
        'str13',
        'str14',
        'str15',
        'str16',
        'any'
    ];

    /**
     * 允许的匹配运算符
     * @var array
     */
    private $allowedOperators = ['contains', 'equals', 'line_equals'];

    /**
     * 允许的规则目标类型
     * @var array
     */
    private $allowedTargetTypes = ['author', 'series'];

    /**
     * 构造函数
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * 获取全部匹配规则。
     *
     * @return array 匹配规则列表
     */
    public function getAllRules() {
        return $this->db->getRows(
            'SELECT * FROM card_match_rules ORDER BY priority DESC, id ASC'
        );
    }

    /**
     * 获取全部启用的匹配规则。
     *
     * @return array 启用的匹配规则列表
     */
    public function getEnabledRules() {
        return $this->db->getRows(
            'SELECT * FROM card_match_rules WHERE is_enabled = 1 ORDER BY priority DESC, id ASC'
        );
    }

    /**
     * 根据ID获取匹配规则。
     *
     * @param int $id 规则ID
     * @return array|false 匹配规则，不存在时返回false
     */
    public function getRuleById($id) {
        $id = $this->normalizeId($id);
        if ($id === false) {
            return false;
        }

        return $this->db->getRow(
            'SELECT * FROM card_match_rules WHERE id = :id',
            [':id' => $id]
        );
    }

    /**
     * 添加匹配规则。
     *
     * @param array $data 规则字段
     * @return int|false 新规则ID，验证失败时返回false
     */
    public function addRule($data) {
        if (!is_array($data)) {
            return false;
        }

        $normalized = $this->normalizeRuleData($data, true);
        if ($normalized === false) {
            return false;
        }

        $normalized['created_at'] = date('Y-m-d H:i:s');
        $normalized['updated_at'] = date('Y-m-d H:i:s');
        $id = $this->db->insert('card_match_rules', $normalized);

        if ($id !== false) {
            $this->invalidateAuthorCaches();
        }

        return $id;
    }

    /**
     * 更新匹配规则。
     *
     * @param int $id 规则ID
     * @param array $data 要更新的规则字段
     * @return bool 是否成功
     */
    public function updateRule($id, $data) {
        $id = $this->normalizeId($id);
        $existing = $id !== false ? $this->getRuleById($id) : false;
        if ($id === false || !is_array($data) || !$existing) {
            return false;
        }

        // 合并现有记录后按完整规则验证，使toggle等局部更新仍保留目标类型和值。
        $normalized = $this->normalizeRuleData(array_merge($existing, $data), true);
        if ($normalized === false || empty($normalized)) {
            return false;
        }

        $normalized['updated_at'] = date('Y-m-d H:i:s');
        $this->db->update('card_match_rules', $normalized, 'id = :id', [':id' => $id]);
        $this->invalidateAuthorCaches();

        return true;
    }

    /**
     * 删除匹配规则。
     *
     * @param int $id 规则ID
     * @return bool 是否成功
     */
    public function deleteRule($id) {
        $id = $this->normalizeId($id);
        if ($id === false) {
            return false;
        }

        $deleted = $this->db->delete('card_match_rules', 'id = :id', [':id' => $id]) > 0;
        if ($deleted) {
            $this->invalidateAuthorCaches();
        }

        return $deleted;
    }

    /**
     * 启用或停用匹配规则。
     *
     * @param int $id 规则ID
     * @param mixed $isEnabled 启用状态（布尔值或0/1）
     * @return bool 是否成功
     */
    public function toggleRule($id, $isEnabled) {
        $isValid = true;
        $enabled = $this->normalizeBoolean($isEnabled, $isValid);
        if (!$isValid) {
            return false;
        }

        return $this->updateRule($id, ['is_enabled' => $enabled]);
    }

    /**
     * 过滤并验证规则字段。
     *
     * @param array $data 原始规则字段
     * @param bool $isCreate 是否为新增操作
     * @return array|false 规范化字段，验证失败时返回false
     */
    private function normalizeRuleData($data, $isCreate) {
        $input = [];
        foreach ($this->writableFields as $field) {
            if (array_key_exists($field, $data)) {
                $input[$field] = $data[$field];
            }
        }

        if ($isCreate) {
            $hasTargetValue = array_key_exists('target_value', $input) || array_key_exists('author_name', $input);
            if (!array_key_exists('match_value', $input) || !$hasTargetValue) {
                return false;
            }

            $input += [
                'database_file' => null,
                'match_field' => 'desc',
                'match_operator' => 'contains',
                'target_type' => 'author',
                'priority' => 100,
                'is_case_sensitive' => 0,
                'is_enabled' => 1,
                'notes' => null
            ];
        }

        $normalized = [];

        $targetType = isset($input['target_type']) && is_scalar($input['target_type']) && !is_bool($input['target_type'])
            ? strtolower(trim((string)$input['target_type']))
            : 'author';
        if (!in_array($targetType, $this->allowedTargetTypes, true)) {
            return false;
        }
        if ($isCreate || array_key_exists('target_type', $input)) {
            $normalized['target_type'] = $targetType;
        }

        if (array_key_exists('database_file', $input)) {
            $databaseFile = $this->normalizeDatabaseFile($input['database_file']);
            if ($databaseFile === false) {
                return false;
            }
            $normalized['database_file'] = $databaseFile;
        }

        if (array_key_exists('match_field', $input)) {
            if (!is_scalar($input['match_field']) || is_bool($input['match_field'])) {
                return false;
            }
            $matchField = strtolower(trim((string)$input['match_field']));
            if (!in_array($matchField, $this->allowedMatchFields, true)) {
                return false;
            }
            $normalized['match_field'] = $matchField;
        }

        if (array_key_exists('match_operator', $input)) {
            if (!is_scalar($input['match_operator']) || is_bool($input['match_operator'])) {
                return false;
            }
            $operator = strtolower(trim((string)$input['match_operator']));
            if (!in_array($operator, $this->allowedOperators, true)) {
                return false;
            }
            $normalized['match_operator'] = $operator;
        }

        if (array_key_exists('match_value', $input)) {
            $matchValue = $this->normalizeRequiredText($input['match_value']);
            if ($matchValue === false) {
                return false;
            }
            $normalized['match_value'] = $matchValue;
        }

        if (array_key_exists('target_value', $input) || array_key_exists('author_name', $input)) {
            $hasUsableTargetValue = array_key_exists('target_value', $input) &&
                is_scalar($input['target_value']) && !is_bool($input['target_value']) &&
                trim((string)$input['target_value']) !== '';
            $rawTargetValue = $hasUsableTargetValue
                ? $input['target_value']
                : (isset($input['author_name']) ? $input['author_name'] : null);
            $targetValue = $this->normalizeRequiredText($rawTargetValue);
            if ($targetValue === false) {
                return false;
            }
            $normalized['target_value'] = $targetValue;
            // 保留旧列供无迁移窗口中的旧代码读取；系列目标绝不能进入作者消费链。
            $normalized['author_name'] = $targetType === 'author' ? $targetValue : '';
        }

        if (array_key_exists('priority', $input)) {
            $priority = $this->normalizeInteger($input['priority']);
            if ($priority === false) {
                return false;
            }
            $normalized['priority'] = $priority;
        }

        if (array_key_exists('is_case_sensitive', $input)) {
            $isValid = true;
            $caseSensitive = $this->normalizeBoolean($input['is_case_sensitive'], $isValid);
            if (!$isValid) {
                return false;
            }
            $normalized['is_case_sensitive'] = $caseSensitive;
        }

        if (array_key_exists('is_enabled', $input)) {
            $isValid = true;
            $enabled = $this->normalizeBoolean($input['is_enabled'], $isValid);
            if (!$isValid) {
                return false;
            }
            $normalized['is_enabled'] = $enabled;
        }

        if (array_key_exists('notes', $input)) {
            $notes = $this->normalizeOptionalText($input['notes']);
            if ($notes === false) {
                return false;
            }
            $normalized['notes'] = $notes;
        }

        return $normalized;
    }

    /**
     * 将数据库路径规范化为安全的文件基名。
     *
     * @param mixed $value 数据库文件输入
     * @return string|null|false 文件基名、空限制或验证失败
     */
    private function normalizeDatabaseFile($value) {
        if ($value === null || $value === '') {
            return null;
        }
        if (!is_scalar($value) || is_bool($value)) {
            return false;
        }

        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }
        if (strpos($value, "\0") !== false) {
            return false;
        }

        $normalized = str_replace('\\', '/', $value);
        if ($normalized !== basename($normalized) || !preg_match('/^[^\/]+\.cdb$/iu', $normalized)) {
            return false;
        }

        return $normalized;
    }

    /**
     * 验证必填文本。
     *
     * @param mixed $value 文本输入
     * @return string|false 规范化文本或验证失败
     */
    private function normalizeRequiredText($value) {
        if (!is_scalar($value) || is_bool($value)) {
            return false;
        }

        $value = trim((string)$value);
        return $value === '' ? false : $value;
    }

    /**
     * 验证可选文本。
     *
     * @param mixed $value 文本输入
     * @return string|null|false 规范化文本、null或验证失败
     */
    private function normalizeOptionalText($value) {
        if ($value === null || $value === '') {
            return null;
        }
        if (!is_scalar($value) || is_bool($value)) {
            return false;
        }

        $value = trim((string)$value);
        return $value === '' ? null : $value;
    }

    /**
     * 验证整数。
     *
     * @param mixed $value 整数输入
     * @return int|false 规范化整数或验证失败
     */
    private function normalizeInteger($value) {
        if (is_bool($value) || is_array($value) || is_object($value) || $value === '') {
            return false;
        }

        $integer = filter_var($value, FILTER_VALIDATE_INT);
        return $integer === false ? false : $integer;
    }

    /**
     * 验证规则ID。
     *
     * @param mixed $id 规则ID
     * @return int|false 正整数ID或验证失败
     */
    private function normalizeId($id) {
        $id = $this->normalizeInteger($id);
        return $id === false || $id < 1 ? false : $id;
    }

    /**
     * 规范化布尔字段。
     *
     * @param mixed $value 布尔输入
     * @param bool $isValid 验证结果引用
     * @return int SQLite布尔值（0或1）
     */
    private function normalizeBoolean($value, &$isValid) {
        if ($value === true || $value === 1 || $value === '1') {
            $isValid = true;
            return 1;
        }
        if ($value === false || $value === 0 || $value === '0') {
            $isValid = true;
            return 0;
        }

        if (is_string($value)) {
            $value = strtolower(trim($value));
            if (in_array($value, ['true', 'on', 'yes'], true)) {
                $isValid = true;
                return 1;
            }
            if (in_array($value, ['false', 'off', 'no'], true)) {
                $isValid = true;
                return 0;
            }
        }

        $isValid = false;
        return 0;
    }

    /**
     * 让作者统计文件缓存与解析器内存规则缓存失效。
     *
     * @return void
     */
    private function invalidateAuthorCaches() {
        if (is_callable(['AuthorStats', 'invalidateCacheFiles'])) {
            call_user_func(['AuthorStats', 'invalidateCacheFiles']);
        }

        if (is_callable(['CardParser', 'invalidateAuthorRuleCache'])) {
            call_user_func(['CardParser', 'invalidateAuthorRuleCache']);
        }
    }
}
