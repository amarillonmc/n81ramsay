<?php
/**
 * 作者映射模型
 *
 * 处理作者映射相关的数据操作
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
        return $this->db->getRows('SELECT * FROM author_mappings ORDER BY card_prefix ASC');
    }

    /**
     * 根据卡片前缀获取作者映射
     *
     * @param string $cardPrefix 卡片前缀
     * @return array|false 作者映射信息
     */
    public function getAuthorMappingByPrefix($cardPrefix) {
        return $this->db->getRow('SELECT * FROM author_mappings WHERE card_prefix = :card_prefix', [
            ':card_prefix' => $cardPrefix
        ]);
    }

    /**
     * 添加作者映射
     *
     * @param string $cardPrefix 卡片前缀
     * @param string $authorName 作者名称
     * @param string|null $alias 作者别名
     * @param string|null $contact 联系方式
     * @param string|null $notes 备注
     * @return int 新添加的映射ID
     */
    public function addAuthorMapping($cardPrefix, $authorName, $alias = null, $contact = null, $notes = null) {
        // 检查是否已存在相同前缀的映射
        $existing = $this->getAuthorMappingByPrefix($cardPrefix);
        if ($existing) {
            // 如果已存在，则更新
            $this->updateAuthorMapping($cardPrefix, $authorName, $alias, $contact, $notes);
            return $existing['id'];
        }

        // 添加新映射
        return $this->db->insert('author_mappings', [
            'card_prefix' => $cardPrefix,
            'author_name' => $authorName,
            'alias' => $alias,
            'contact' => $contact,
            'notes' => $notes,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * 更新作者映射
     *
     * @param string $cardPrefix 卡片前缀
     * @param string $authorName 作者名称
     * @param string|null $alias 作者别名
     * @param string|null $contact 联系方式
     * @param string|null $notes 备注
     * @return bool 是否成功
     */
    public function updateAuthorMapping($cardPrefix, $authorName, $alias = null, $contact = null, $notes = null) {
        return $this->db->update('author_mappings', [
            'author_name' => $authorName,
            'alias' => $alias,
            'contact' => $contact,
            'notes' => $notes,
            'updated_at' => date('Y-m-d H:i:s')
        ], 'card_prefix = :card_prefix', [
            ':card_prefix' => $cardPrefix
        ]) > 0;
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
     * @return bool 是否成功
     */
    public function updateAuthorMappingWithPrefix($oldCardPrefix, $newCardPrefix, $authorName, $alias = null, $contact = null, $notes = null) {
        // 如果新前缀与旧前缀相同，则使用普通更新方法
        if ($oldCardPrefix === $newCardPrefix) {
            return $this->updateAuthorMapping($oldCardPrefix, $authorName, $alias, $contact, $notes);
        }

        // 检查新前缀是否已存在
        $existing = $this->getAuthorMappingByPrefix($newCardPrefix);
        if ($existing) {
            // 如果新前缀已存在，则返回失败
            return false;
        }

        // 开始事务
        $this->db->beginTransaction();

        try {
            // 获取原映射的ID
            $originalMapping = $this->getAuthorMappingByPrefix($oldCardPrefix);
            if (!$originalMapping) {
                // 如果原映射不存在，则回滚事务并返回失败
                $this->db->rollBack();
                return false;
            }

            // 删除原映射
            $this->deleteAuthorMapping($oldCardPrefix);

            // 添加新映射
            $this->addAuthorMapping($newCardPrefix, $authorName, $alias, $contact, $notes);

            // 提交事务
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            // 发生异常，回滚事务
            $this->db->rollBack();
            return false;
        }
    }

    /**
     * 删除作者映射
     *
     * @param string $cardPrefix 卡片前缀
     * @return bool 是否成功
     */
    public function deleteAuthorMapping($cardPrefix) {
        return $this->db->delete('author_mappings', 'card_prefix = :card_prefix', [
            ':card_prefix' => $cardPrefix
        ]) > 0;
    }

    /**
     * 批量导入作者映射
     *
     * @param array $mappings 作者映射数组
     * @return int 成功导入的数量
     */
    public function importAuthorMappings($mappings) {
        $count = 0;
        foreach ($mappings as $mapping) {
            if (isset($mapping['card_prefix']) && isset($mapping['author_name'])) {
                $this->addAuthorMapping(
                    $mapping['card_prefix'],
                    $mapping['author_name'],
                    $mapping['alias'] ?? null,
                    $mapping['contact'] ?? null,
                    $mapping['notes'] ?? null
                );
                $count++;
            }
        }
        return $count;
    }

    /**
     * 从strings.conf文件中识别作者并导入
     *
     * @return int 成功导入的数量
     */
    public function identifyAuthorsFromStringsConf() {
        // 获取CardParser实例
        $cardParser = CardParser::getInstance();

        // 获取作者信息
        $authors = $cardParser->getAuthorsFromStringsConf();

        // 导入作者映射
        $mappings = [];
        foreach ($authors as $prefix => $authorInfo) {
            $mappings[] = [
                'card_prefix' => $prefix,
                'author_name' => $authorInfo['name']
            ];
        }

        return $this->importAuthorMappings($mappings);
    }
}
