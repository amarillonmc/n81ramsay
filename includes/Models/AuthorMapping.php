<?php
/**
 * 作者映射模型
 *
 * 处理作者映射、精确卡号区间及其缓存失效。
 */
class AuthorMapping {
    /**
     * 数据库实例
     * @var Database
     */
    private $db;

    /**
     * 构造函数
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * 获取所有作者映射
     *
     * @return array 作者映射列表
     */
    public function getAllAuthorMappings() {
        return $this->db->getRows(
            'SELECT * FROM author_mappings ORDER BY card_prefix ASC, card_id_start ASC, id ASC'
        );
    }

    /**
     * 根据卡片前缀获取作者映射
     *
     * @param string $cardPrefix 卡片前缀
     * @return array|false 作者映射信息
     */
    public function getAuthorMappingByPrefix($cardPrefix) {
        return $this->db->getRow(
            'SELECT * FROM author_mappings WHERE card_prefix = :card_prefix ORDER BY id ASC LIMIT 1',
            [':card_prefix' => $cardPrefix]
        );
    }

    /**
     * 根据稳定ID获取作者映射。
     *
     * @param int $id 映射ID
     * @return array|false 作者映射，不存在或ID无效时返回false
     */
    public function getAuthorMappingById($id) {
        $id = $this->normalizeId($id);
        if ($id === false) {
            return false;
        }

        return $this->db->getRow(
            'SELECT * FROM author_mappings WHERE id = :id',
            [':id' => $id]
        );
    }

    /**
     * 添加作者映射
     *
     * 新增参数均放在旧参数之后，确保既有调用方式继续有效。
     * 五参数以内的旧调用仍按原行为更新同前缀最早记录；提供区间参数的新调用
     * 始终新增稳定ID记录，从而允许同一前缀维护多个互不相同的显式区间。
     *
     * @param string $cardPrefix 卡片前缀
     * @param string $authorName 作者名称
     * @param string|null $alias 作者别名
     * @param string|null $contact 联系方式
     * @param string|null $notes 备注
     * @param int|null $cardIdLength 要求的卡号总位数
     * @param int|null $cardIdStart 卡号区间起点（包含）
     * @param int|null $cardIdEnd 卡号区间终点（包含）
     * @param int $priority 匹配优先级
     * @return int|false 新添加的映射ID，失败时返回false
     */
    public function addAuthorMapping(
        $cardPrefix,
        $authorName,
        $alias = null,
        $contact = null,
        $notes = null,
        $cardIdLength = null,
        $cardIdStart = null,
        $cardIdEnd = null,
        $priority = 100
    ) {
        $argumentCount = func_num_args();
        $existing = $this->getAuthorMappingByPrefix($cardPrefix);

        if ($existing && $argumentCount <= 5) {
            // 旧接口是按前缀upsert；只操作最早ID，绝不连带覆盖同前缀的其他区间。
            call_user_func_array([$this, 'updateAuthorMapping'], func_get_args());
            return $existing['id'];
        }

        $data = [
            'card_prefix' => $cardPrefix,
            'author_name' => $authorName,
            'card_id_length' => $this->normalizeNullableInteger($cardIdLength),
            'card_id_start' => $this->normalizeNullableInteger($cardIdStart),
            'card_id_end' => $this->normalizeNullableInteger($cardIdEnd),
            'priority' => $argumentCount >= 9 ? $this->normalizePriority($priority) : 100,
            'alias' => $alias,
            'contact' => $contact,
            'notes' => $notes,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $id = $this->db->insert('author_mappings', $data);
        if ($id !== false) {
            $this->invalidateAuthorCaches();
        }

        return $id;
    }

    /**
     * 更新作者映射
     *
     * @param string $cardPrefix 卡片前缀
     * @param string $authorName 作者名称
     * @param string|null $alias 作者别名
     * @param string|null $contact 联系方式
     * @param string|null $notes 备注
     * @param int|null $cardIdLength 要求的卡号总位数
     * @param int|null $cardIdStart 卡号区间起点（包含）
     * @param int|null $cardIdEnd 卡号区间终点（包含）
     * @param int $priority 匹配优先级
     * @return bool 是否成功
     */
    public function updateAuthorMapping(
        $cardPrefix,
        $authorName,
        $alias = null,
        $contact = null,
        $notes = null,
        $cardIdLength = null,
        $cardIdStart = null,
        $cardIdEnd = null,
        $priority = 100
    ) {
        $existing = $this->getAuthorMappingByPrefix($cardPrefix);
        if (!$existing) {
            return false;
        }

        $data = $this->buildAuthorMappingUpdateData(
            $authorName,
            $alias,
            $contact,
            $notes,
            $cardIdLength,
            $cardIdStart,
            $cardIdEnd,
            $priority,
            func_num_args()
        );

        $affectedRows = $this->db->update('author_mappings', $data, 'id = :id', [
            ':id' => (int)$existing['id']
        ]);
        $updated = $affectedRows > 0 || (bool)$this->getAuthorMappingById($existing['id']);

        if ($affectedRows > 0) {
            $this->invalidateAuthorCaches();
        }

        return $updated;
    }

    /**
     * 按稳定ID更新作者映射及其前缀。
     *
     * 同一前缀可以对应多个区间，因此管理员写操作不得再以card_prefix作为记录身份。
     *
     * @param int $id 映射ID
     * @param string $cardPrefix 卡片前缀
     * @param string $authorName 作者名称
     * @param string|null $alias 作者别名
     * @param string|null $contact 联系方式
     * @param string|null $notes 备注
     * @param int|null $cardIdLength 要求的卡号总位数
     * @param int|null $cardIdStart 卡号区间起点（包含）
     * @param int|null $cardIdEnd 卡号区间终点（包含）
     * @param int $priority 匹配优先级
     * @return bool 是否成功
     */
    public function updateAuthorMappingById(
        $id,
        $cardPrefix,
        $authorName,
        $alias = null,
        $contact = null,
        $notes = null,
        $cardIdLength = null,
        $cardIdStart = null,
        $cardIdEnd = null,
        $priority = 100
    ) {
        $id = $this->normalizeId($id);
        if ($id === false || !$this->getAuthorMappingById($id)) {
            return false;
        }

        $data = $this->buildAuthorMappingUpdateData(
            $authorName,
            $alias,
            $contact,
            $notes,
            $cardIdLength,
            $cardIdStart,
            $cardIdEnd,
            $priority,
            func_num_args() - 1
        );
        $data['card_prefix'] = $cardPrefix;

        $affectedRows = $this->db->update('author_mappings', $data, 'id = :id', [':id' => $id]);
        if ($affectedRows > 0) {
            $this->invalidateAuthorCaches();
        }

        return $affectedRows > 0 || (bool)$this->getAuthorMappingById($id);
    }

    /**
     * 更新作者映射（包括卡片前缀）
     *
     * @param string $oldCardPrefix 原卡片前缀
     * @param string $newCardPrefix 新卡片前缀
     * @param string $authorName 作者名称
     * @param string|null $alias 作者别名
     * @param string|null $contact 联系方式
     * @param string|null $notes 备注
     * @param int|null $cardIdLength 要求的卡号总位数
     * @param int|null $cardIdStart 卡号区间起点（包含）
     * @param int|null $cardIdEnd 卡号区间终点（包含）
     * @param int $priority 匹配优先级
     * @return bool 是否成功
     */
    public function updateAuthorMappingWithPrefix(
        $oldCardPrefix,
        $newCardPrefix,
        $authorName,
        $alias = null,
        $contact = null,
        $notes = null,
        $cardIdLength = null,
        $cardIdStart = null,
        $cardIdEnd = null,
        $priority = 100
    ) {
        if ($oldCardPrefix === $newCardPrefix) {
            $arguments = func_get_args();
            array_shift($arguments);
            return call_user_func_array([$this, 'updateAuthorMapping'], $arguments);
        }

        if ($this->getAuthorMappingByPrefix($newCardPrefix)) {
            return false;
        }

        $existing = $this->getAuthorMappingByPrefix($oldCardPrefix);
        if (!$existing) {
            return false;
        }

        $data = $this->buildAuthorMappingUpdateData(
            $authorName,
            $alias,
            $contact,
            $notes,
            $cardIdLength,
            $cardIdStart,
            $cardIdEnd,
            $priority,
            func_num_args() - 1
        );
        $data['card_prefix'] = $newCardPrefix;

        $updated = $this->db->update('author_mappings', $data, 'id = :id', [
            ':id' => (int)$existing['id']
        ]) > 0;

        if ($updated) {
            $this->invalidateAuthorCaches();
        }

        return $updated;
    }

    /**
     * 删除作者映射
     *
     * @param string $cardPrefix 卡片前缀
     * @return bool 是否成功
     */
    public function deleteAuthorMapping($cardPrefix) {
        $existing = $this->getAuthorMappingByPrefix($cardPrefix);
        if (!$existing) {
            return false;
        }

        return $this->deleteAuthorMappingById($existing['id']);
    }

    /**
     * 按稳定ID删除作者映射。
     *
     * @param int $id 映射ID
     * @return bool 是否成功
     */
    public function deleteAuthorMappingById($id) {
        $id = $this->normalizeId($id);
        if ($id === false) {
            return false;
        }

        $deleted = $this->db->delete('author_mappings', 'id = :id', [':id' => $id]) > 0;

        if ($deleted) {
            $this->invalidateAuthorCaches();
        }

        return $deleted;
    }

    /**
     * 批量导入作者映射
     *
     * 批量识别属于保守导入：只接受1至4位数字作者码，且绝不覆盖既有人工记录。
     *
     * @param array $mappings 作者映射数组
     * @return int 成功新增的数量
     */
    public function importAuthorMappings($mappings) {
        $count = 0;

        foreach ($mappings as $mapping) {
            if (!is_array($mapping) || !isset($mapping['card_prefix']) || !isset($mapping['author_name'])) {
                continue;
            }

            $cardPrefix = trim((string)$mapping['card_prefix']);
            $authorName = is_scalar($mapping['author_name']) ? trim((string)$mapping['author_name']) : '';

            if (!preg_match('/^\d{1,4}$/D', $cardPrefix) || $authorName === '') {
                continue;
            }

            // strings.conf 只是候选来源，管理员已经确认的同前缀记录始终优先。
            if ($this->getAuthorMappingByPrefix($cardPrefix)) {
                continue;
            }

            $id = $this->addAuthorMapping(
                $cardPrefix,
                $authorName,
                isset($mapping['alias']) ? $mapping['alias'] : null,
                isset($mapping['contact']) ? $mapping['contact'] : null,
                isset($mapping['notes']) ? $mapping['notes'] : null,
                isset($mapping['card_id_length']) ? $mapping['card_id_length'] : null,
                isset($mapping['card_id_start']) ? $mapping['card_id_start'] : null,
                isset($mapping['card_id_end']) ? $mapping['card_id_end'] : null,
                isset($mapping['priority']) ? $mapping['priority'] : 100
            );

            if ($id !== false) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * 从strings.conf文件中识别作者并导入
     *
     * @return int 成功新增的数量
     */
    public function identifyAuthorsFromStringsConf() {
        $authors = CardParser::getInstance()->getAuthorsFromStringsConf();
        $mappings = [];

        foreach ($authors as $prefix => $authorInfo) {
            if (!is_array($authorInfo) || !isset($authorInfo['name'])) {
                continue;
            }

            $mapping = [
                'card_prefix' => (string)$prefix,
                'author_name' => $authorInfo['name']
            ];
            if (isset($authorInfo['card_id_length'])) {
                $mapping['card_id_length'] = $authorInfo['card_id_length'];
            }
            $mappings[] = $mapping;
        }

        return $this->importAuthorMappings($mappings);
    }

    /**
     * 获取可用于作者身份验证的规范作者名与别名
     *
     * 人工系列分组明确排除，避免系列标签被用于发起投票或提交召唤词。
     *
     * @return array 唯一作者标识列表
     */
    public function getAuthorIdentifierWhitelist() {
        $whitelist = [];
        foreach ($this->getAllAuthorMappings() as $mapping) {
            if (!empty($mapping['author_name'])) {
                $whitelist[] = trim((string)$mapping['author_name']);
            }
            if (!empty($mapping['alias'])) {
                foreach (explode(',', $mapping['alias']) as $alias) {
                    $alias = trim($alias);
                    if ($alias !== '') {
                        $whitelist[] = $alias;
                    }
                }
            }
        }

        $rules = $this->db->getRows('SELECT target_type, target_value, author_name, is_enabled FROM card_match_rules');
        foreach ($rules as $rule) {
            if (isset($rule['is_enabled']) && (int)$rule['is_enabled'] !== 1) {
                continue;
            }
            $targetType = isset($rule['target_type']) ? strtolower(trim((string)$rule['target_type'])) : 'author';
            if ($targetType !== 'author') {
                continue;
            }
            $targetValue = isset($rule['target_value']) && trim((string)$rule['target_value']) !== ''
                ? trim((string)$rule['target_value'])
                : (isset($rule['author_name']) ? trim((string)$rule['author_name']) : '');
            if ($targetValue !== '') {
                $whitelist[] = $targetValue;
            }
        }

        return array_values(array_unique($whitelist));
    }

    /**
     * 检查输入作者名或别名是否对应规范作者
     *
     * @param string $identifier 输入作者标识
     * @param string $canonicalAuthor 统一解析得到的规范作者名
     * @return bool 是否匹配
     */
    public function matchesAuthorIdentifier($identifier, $canonicalAuthor) {
        $identifier = trim((string)$identifier);
        $canonicalAuthor = trim((string)$canonicalAuthor);
        if ($identifier === '' || $canonicalAuthor === '') {
            return false;
        }
        if ($identifier === $canonicalAuthor) {
            return true;
        }

        $mappings = $this->db->getRows(
            'SELECT alias FROM author_mappings WHERE author_name = :author_name',
            [':author_name' => $canonicalAuthor]
        );
        foreach ($mappings as $mapping) {
            if (empty($mapping['alias'])) {
                continue;
            }
            foreach (explode(',', $mapping['alias']) as $alias) {
                if (trim($alias) === $identifier) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * 生成作者映射更新字段，并根据实际参数数量保护旧调用。
     *
     * @param string $authorName 作者名称
     * @param string|null $alias 作者别名
     * @param string|null $contact 联系方式
     * @param string|null $notes 备注
     * @param mixed $cardIdLength 卡号总位数
     * @param mixed $cardIdStart 卡号区间起点
     * @param mixed $cardIdEnd 卡号区间终点
     * @param mixed $priority 匹配优先级
     * @param int $argumentCount updateAuthorMapping视角下的实际参数数量
     * @return array 数据库更新字段
     */
    private function buildAuthorMappingUpdateData(
        $authorName,
        $alias,
        $contact,
        $notes,
        $cardIdLength,
        $cardIdStart,
        $cardIdEnd,
        $priority,
        $argumentCount
    ) {
        $data = [
            'author_name' => $authorName,
            'alias' => $alias,
            'contact' => $contact,
            'notes' => $notes,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if ($argumentCount >= 6) {
            $data['card_id_length'] = $this->normalizeNullableInteger($cardIdLength);
        }
        if ($argumentCount >= 7) {
            $data['card_id_start'] = $this->normalizeNullableInteger($cardIdStart);
        }
        if ($argumentCount >= 8) {
            $data['card_id_end'] = $this->normalizeNullableInteger($cardIdEnd);
        }
        if ($argumentCount >= 9) {
            $data['priority'] = $this->normalizePriority($priority);
        }

        return $data;
    }

    /**
     * 将可空整数输入规范化。
     *
     * @param mixed $value 输入值
     * @return int|null 规范化后的整数，无效或空值返回null
     */
    private function normalizeNullableInteger($value) {
        if ($value === null || $value === '') {
            return null;
        }

        $integer = filter_var($value, FILTER_VALIDATE_INT);
        return $integer === false ? null : $integer;
    }

    /**
     * 规范化匹配优先级。
     *
     * @param mixed $priority 优先级
     * @return int 有效优先级，无效时使用100
     */
    private function normalizePriority($priority) {
        $integer = filter_var($priority, FILTER_VALIDATE_INT);
        return $integer === false ? 100 : $integer;
    }

    /**
     * 规范化稳定映射ID。
     *
     * @param mixed $id 映射ID
     * @return int|false 有效正整数ID，无效时返回false
     */
    private function normalizeId($id) {
        $integer = filter_var($id, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1]
        ]);
        return $integer === false ? false : (int)$integer;
    }

    /**
     * 让作者统计文件缓存与解析器内存规则缓存失效。
     *
     * 缓存接口由相应类提供时才调用，以兼容尚未升级的部署。
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
