<?php
/**
 * 召唤词模型
 *
 * 处理召唤词相关的数据操作
 */
class DialogueModel {
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
     * 读取召唤词文件
     *
     * @return array 召唤词数组
     */
    public function loadDialogues() {
        $dialoguesFile = DIALOGUES_FILE_PATH;
        $originalPath = dirname(__DIR__, 2) . '/data/const/dialogues-custom.json';

        // 如果当前使用的是临时路径，且临时文件不存在，但原始文件存在，则复制原始文件
        if ($dialoguesFile !== $originalPath && !file_exists($dialoguesFile) && file_exists($originalPath) && is_readable($originalPath)) {
            $originalContent = file_get_contents($originalPath);
            if ($originalContent !== false) {
                // 确保临时目录存在
                $tempDir = dirname($dialoguesFile);
                if (!is_dir($tempDir)) {
                    @mkdir($tempDir, 0755, true);
                }
                // 复制原始文件到临时位置
                file_put_contents($dialoguesFile, $originalContent);

                if (defined('DEBUG_MODE') && DEBUG_MODE) {
                    Utils::debug('复制原始dialogues文件到临时位置', [
                        'from' => $originalPath,
                        'to' => $dialoguesFile
                    ]);
                }
            }
        }

        if (!file_exists($dialoguesFile)) {
            return [];
        }

        $content = file_get_contents($dialoguesFile);
        if ($content === false) {
            return [];
        }

        $dialogues = json_decode($content, true);
        if (!is_array($dialogues)) {
            return [];
        }

        return $dialogues;
    }

    /**
     * 保存召唤词到文件
     *
     * @param array $dialogues 召唤词数组
     * @return bool|string 保存成功返回true，失败返回错误信息
     */
    public function saveDialogues($dialogues) {
        $dialoguesFile = DIALOGUES_FILE_PATH;

        // 调试信息
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            Utils::debug('saveDialogues开始', [
                'dialogues_file' => $dialoguesFile,
                'dialogues_count' => count($dialogues)
            ]);
        }

        // 确保目录存在
        $dir = dirname($dialoguesFile);
        if (!is_dir($dir)) {
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                Utils::debug('创建目录', ['dir' => $dir]);
            }
            if (!mkdir($dir, 0755, true)) {
                $error = error_get_last();
                $errorMsg = "无法创建目录 {$dir}: " . ($error ? $error['message'] : '未知错误');
                if (defined('DEBUG_MODE') && DEBUG_MODE) {
                    Utils::debug('目录创建失败', ['error' => $errorMsg]);
                }
                return $errorMsg;
            }
        }

        // 检查目录权限
        if (!is_writable($dir)) {
            $errorMsg = "目录 {$dir} 不可写";
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                Utils::debug('目录权限检查失败', ['error' => $errorMsg]);
            }
            return $errorMsg;
        }

        // 保存为格式化的JSON
        $content = json_encode($dialogues, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($content === false) {
            $errorMsg = "JSON编码失败: " . json_last_error_msg();
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                Utils::debug('JSON编码失败', ['error' => $errorMsg]);
            }
            return $errorMsg;
        }

        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            Utils::debug('JSON编码成功', ['content_length' => strlen($content)]);
        }

        // 尝试写入文件
        $result = file_put_contents($dialoguesFile, $content);
        if ($result === false) {
            $error = error_get_last();
            $errorMsg = "文件写入失败: " . ($error ? $error['message'] : '未知错误');
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                Utils::debug('文件写入失败', ['error' => $errorMsg]);
            }
            return $errorMsg;
        }

        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            Utils::debug('文件写入成功', ['bytes_written' => $result]);
        }

        return true;
    }

    /**
     * 获取所有待审核的召唤词投稿
     *
     * @return array 投稿列表
     */
    public function getPendingSubmissions() {
        return $this->db->getRows('
            SELECT *
            FROM dialogue_submissions
            WHERE status = "pending"
            ORDER BY created_at ASC
        ');
    }

    /**
     * 获取用户的待审核投稿数量
     *
     * @param int $userId 用户ID
     * @return int 待审核投稿数量
     */
    public function getUserPendingCount($userId) {
        $result = $this->db->getRow('
            SELECT COUNT(*) as count
            FROM dialogue_submissions
            WHERE user_id = :user_id AND status = "pending"
        ', [':user_id' => $userId]);

        return $result ? (int)$result['count'] : 0;
    }

    /**
     * 提交召唤词投稿
     *
     * @param int $userId 用户ID
     * @param string $cardId 卡片ID
     * @param string $dialogue 召唤词内容
     * @param string $authorId 作者ID
     * @return int|false 投稿ID或失败
     */
    public function submitDialogue($userId, $cardId, $dialogue, $authorId) {
        return $this->db->insert('dialogue_submissions', [
            'user_id' => $userId,
            'card_id' => $cardId,
            'dialogue' => $dialogue,
            'author_id' => $authorId,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * 审核召唤词投稿
     *
     * @param int $submissionId 投稿ID
     * @param string $action 操作：accept 或 reject
     * @param int $adminId 管理员ID
     * @param string|null $reason 拒绝原因
     * @return bool 是否成功
     */
    public function reviewSubmission($submissionId, $action, $adminId, $reason = null) {
        // 获取投稿信息
        $submission = $this->db->getRow('
            SELECT * FROM dialogue_submissions WHERE id = :id
        ', [':id' => $submissionId]);

        if (!$submission) {
            return false;
        }

        // 开始事务
        $this->db->beginTransaction();

        try {
            // 更新投稿状态
            $this->db->update('dialogue_submissions', [
                'status' => $action === 'accept' ? 'approved' : 'rejected',
                'reviewed_by' => $adminId,
                'reviewed_at' => date('Y-m-d H:i:s'),
                'reject_reason' => $reason
            ], 'id = :id', [':id' => $submissionId]);

            // 如果是接受，则添加到召唤词文件
            if ($action === 'accept') {
                $dialogues = $this->loadDialogues();
                $dialogues[$submission['card_id']] = [$submission['dialogue']];
                $saveResult = $this->saveDialogues($dialogues);

                if ($saveResult !== true) {
                    // 保存失败，回滚事务
                    $this->db->rollBack();
                    return false;
                }
            }

            // 提交事务
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            // 回滚事务
            $this->db->rollBack();
            return false;
        }
    }

    /**
     * 删除召唤词投稿
     *
     * @param int $submissionId 投稿ID
     * @return bool 是否成功
     */
    public function deleteSubmission($submissionId) {
        return $this->db->delete('dialogue_submissions', 'id = :id', [':id' => $submissionId]) > 0;
    }

    /**
     * 验证作者信息
     *
     * @param string $authorId 作者ID
     * @param string $cardId 卡片ID
     * @param int $strictness 严格度
     * @return array 验证结果 ['valid' => bool, 'message' => string, 'warning' => bool]
     */
    public function validateAuthor($authorId, $cardId, $strictness) {
        // 获取作者映射
        $authorMapping = new AuthorMapping();
        $allMappings = $authorMapping->getAllAuthorMappings();

        // 构建作者名称和别名的映射
        $authorMap = [];
        foreach ($allMappings as $mapping) {
            $authorMap[$mapping['author_name']] = $mapping['card_prefix'];
            if (!empty($mapping['alias'])) {
                $aliases = explode(',', $mapping['alias']);
                foreach ($aliases as $alias) {
                    $alias = trim($alias);
                    if (!empty($alias)) {
                        $authorMap[$alias] = $mapping['card_prefix'];
                    }
                }
            }
        }

        // 严格度为0，直接通过
        if ($strictness == 0) {
            return ['valid' => true, 'message' => '', 'warning' => false];
        }

        // 检查作者是否存在
        if (!isset($authorMap[$authorId])) {
            return [
                'valid' => false,
                'message' => '输入的作者ID无法在作者管理处找到',
                'warning' => false
            ];
        }

        // 严格度为1，只检查作者存在
        if ($strictness == 1) {
            // 检查卡片前缀是否匹配（用于警告）
            $cardPrefix = substr($cardId, 0, 3);
            $expectedPrefix = $authorMap[$authorId];

            if ($cardPrefix !== $expectedPrefix) {
                return [
                    'valid' => true,
                    'message' => '',
                    'warning' => true
                ];
            }

            return ['valid' => true, 'message' => '', 'warning' => false];
        }

        // 严格度为2，检查作者存在且卡片前缀匹配
        if ($strictness == 2) {
            $cardPrefix = substr($cardId, 0, 3);
            $expectedPrefix = $authorMap[$authorId];

            if ($cardPrefix !== $expectedPrefix) {
                return [
                    'valid' => false,
                    'message' => "卡片ID前缀({$cardPrefix})与作者({$authorId})的卡片前缀({$expectedPrefix})不匹配",
                    'warning' => false
                ];
            }

            return ['valid' => true, 'message' => '', 'warning' => false];
        }

        return ['valid' => false, 'message' => '未知的严格度设置', 'warning' => false];
    }
}
