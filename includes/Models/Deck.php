<?php
/**
 * 卡组模型
 *
 * 处理卡组相关的数据操作
 */
class Deck {
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
     * 创建卡组
     *
     * @param array $data 卡组数据
     * @return int|false 卡组ID或失败时返回false
     */
    public function createDeck($data) {
        try {
            return $this->db->insert('decks', [
                'name' => $data['name'],
                'main_deck' => json_encode($data['main_deck']),
                'extra_deck' => json_encode($data['extra_deck'] ?? []),
                'side_deck' => json_encode($data['side_deck'] ?? []),
                'uploader_id' => $data['uploader_id'],
                'uploader_name' => $data['uploader_name'] ?? '',
                'is_admin_deck' => $data['is_admin_deck'] ?? 0,
                'deck_group' => $data['deck_group'] ?? null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            Utils::debug('创建卡组失败', ['错误' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * 获取卡组列表（分页）
     *
     * @param int $page 页码
     * @param int $perPage 每页数量
     * @return array 卡组列表和分页信息
     */
    public function getDeckList($page = 1, $perPage = 20) {
        $offset = ($page - 1) * $perPage;
        
        // 获取总数
        $total = $this->db->getValue('SELECT COUNT(*) FROM decks');
        
        // 获取列表
        $decks = $this->db->getRows(
            'SELECT * FROM decks ORDER BY created_at DESC LIMIT ? OFFSET ?',
            [$perPage, $offset]
        );

        // 处理卡组数据
        foreach ($decks as &$deck) {
            $deck['main_deck'] = json_decode($deck['main_deck'], true) ?? [];
            $deck['extra_deck'] = json_decode($deck['extra_deck'], true) ?? [];
            $deck['side_deck'] = json_decode($deck['side_deck'], true) ?? [];
            $deck['card_count'] = count($deck['main_deck']) + count($deck['extra_deck']);
        }

        return [
            'decks' => $decks,
            'total' => (int)$total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }

    /**
     * 根据ID获取卡组
     *
     * @param int $deckId 卡组ID
     * @return array|null 卡组信息
     */
    public function getDeckById($deckId) {
        $deck = $this->db->getRow('SELECT * FROM decks WHERE id = ?', [$deckId]);
        
        if ($deck) {
            $deck['main_deck'] = json_decode($deck['main_deck'], true) ?? [];
            $deck['extra_deck'] = json_decode($deck['extra_deck'], true) ?? [];
            $deck['side_deck'] = json_decode($deck['side_deck'], true) ?? [];
        }
        
        return $deck;
    }

    /**
     * 删除卡组
     *
     * @param int $deckId 卡组ID
     * @return bool 是否成功
     */
    public function deleteDeck($deckId) {
        try {
            $this->db->beginTransaction();
            
            // 删除卡组评论
            $this->db->delete('deck_comments', 'deck_id = ?', [$deckId]);
            
            // 删除卡组
            $this->db->delete('decks', 'id = ?', [$deckId]);
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            Utils::debug('删除卡组失败', ['错误' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * 检查用户是否可以删除卡组
     *
     * @param int $deckId 卡组ID
     * @param string $userId 用户ID
     * @param bool $isAdmin 是否为管理员
     * @return bool 是否可以删除
     */
    public function canDeleteDeck($deckId, $userId, $isAdmin = false) {
        if ($isAdmin) {
            return true;
        }
        
        $deck = $this->getDeckById($deckId);
        return $deck && $deck['uploader_id'] === $userId;
    }

    /**
     * 获取同一deck_group的卡组列表
     *
     * @param string $deckGroup deck_group标识
     * @return array 卡组列表
     */
    public function getDecksByGroup($deckGroup) {
        $decks = $this->db->getRows(
            'SELECT * FROM decks WHERE deck_group = ? ORDER BY id ASC',
            [$deckGroup]
        );

        foreach ($decks as &$deck) {
            $deck['main_deck'] = json_decode($deck['main_deck'], true) ?? [];
            $deck['extra_deck'] = json_decode($deck['extra_deck'], true) ?? [];
            $deck['side_deck'] = json_decode($deck['side_deck'], true) ?? [];
        }

        return $decks;
    }

    /**
     * 添加评论
     *
     * @param int $deckId 卡组ID
     * @param string $userId 用户ID
     * @param string $userName 用户名
     * @param string $comment 评论内容
     * @return int|false 评论ID或失败时返回false
     */
    public function addComment($deckId, $userId, $userName, $comment) {
        try {
            return $this->db->insert('deck_comments', [
                'deck_id' => $deckId,
                'user_id' => $userId,
                'user_name' => $userName,
                'comment' => $comment,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            Utils::debug('添加评论失败', ['错误' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * 获取卡组评论
     *
     * @param int $deckId 卡组ID
     * @return array 评论列表
     */
    public function getComments($deckId) {
        return $this->db->getRows(
            'SELECT * FROM deck_comments WHERE deck_id = ? ORDER BY created_at ASC',
            [$deckId]
        );
    }

    /**
     * 删除评论
     *
     * @param int $commentId 评论ID
     * @return bool 是否成功
     */
    public function deleteComment($commentId) {
        try {
            $this->db->delete('deck_comments', 'id = ?', [$commentId]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * 检查用户是否参与过投票
     *
     * @param string $identifier 用户标识符
     * @return bool 是否参与过投票
     */
    public function hasUserVoted($identifier) {
        $count = $this->db->getValue(
            'SELECT COUNT(*) FROM vote_records WHERE identifier = ?',
            [$identifier]
        );
        return $count > 0;
    }

    /**
     * 检查用户是否可以上传卡组
     *
     * @param string $identifier 用户标识符
     * @param bool $isAdmin 是否为管理员
     * @return bool 是否可以上传
     */
    public function canUploadDeck($identifier, $isAdmin = false) {
        $result = $this->getUploadPermissionInfo($identifier, $isAdmin);
        return $result['can_upload'];
    }

    /**
     * 获取用户上传权限详细信息
     *
     * @param string $identifier 用户标识符
     * @param bool $isAdmin 是否为管理员
     * @return array ['can_upload' => bool, 'reason' => string]
     */
    public function getUploadPermissionInfo($identifier, $isAdmin = false) {
        // 管理员始终可以上传
        if ($isAdmin) {
            return ['can_upload' => true, 'reason' => ''];
        }

        $permission = defined('DECK_UPLOAD_PERMISSION') ? DECK_UPLOAD_PERMISSION : 1;

        switch ($permission) {
            case 0: // 所有用户可上传
                return ['can_upload' => true, 'reason' => ''];
            case 1: // 仅参与过投票的用户可上传
                if ($this->hasUserVoted($identifier)) {
                    return ['can_upload' => true, 'reason' => ''];
                }
                return ['can_upload' => false, 'reason' => '需要先参与投票才能上传卡组'];
            case 2: // 仅管理员可上传
                return ['can_upload' => false, 'reason' => '仅管理员可以上传卡组'];
            default:
                if ($this->hasUserVoted($identifier)) {
                    return ['can_upload' => true, 'reason' => ''];
                }
                return ['can_upload' => false, 'reason' => '需要先参与投票才能上传卡组'];
        }
    }

    /**
     * 解析YDK文本
     *
     * @param string $ydkContent YDK文件内容
     * @return array 解析结果
     */
    public function parseYdkContent($ydkContent) {
        $lines = explode("\n", str_replace("\r", "", $ydkContent));

        $main = [];
        $extra = [];
        $side = [];
        $currentSection = null;

        foreach ($lines as $line) {
            $line = trim($line);

            if (empty($line)) {
                continue;
            }

            // 处理节标题
            if (strpos($line, '#') === 0) {
                if ($line === '#main') {
                    $currentSection = 'main';
                } elseif ($line === '#extra') {
                    $currentSection = 'extra';
                }
                continue;
            }

            if (strpos($line, '!side') === 0 || $line === '#side') {
                $currentSection = 'side';
                continue;
            }

            // 处理卡片ID
            if (is_numeric($line)) {
                $cardId = (int)$line;
                if ($currentSection === 'main') {
                    $main[] = $cardId;
                } elseif ($currentSection === 'extra') {
                    $extra[] = $cardId;
                } elseif ($currentSection === 'side') {
                    $side[] = $cardId;
                }
            }
        }

        // 放宽验证：允许主卡组少于40张，只要有卡片即可
        // 仍然限制主卡组不超过60张，额外和副卡组不超过15张
        $hasCards = count($main) > 0 || count($extra) > 0;
        $isValid = $hasCards && count($main) <= 60 && count($extra) <= 15 && count($side) <= 15;

        return [
            'main' => $main,
            'extra' => $extra,
            'side' => $side,
            'is_valid' => $isValid
        ];
    }

    /**
     * 生成YDK文件内容
     *
     * @param array $deck 卡组数据
     * @return string YDK文件内容
     */
    public function generateYdkContent($deck) {
        $content = "#created by RAMSAY\n";
        $content .= "#main\n";

        foreach ($deck['main_deck'] as $cardId) {
            $content .= $cardId . "\n";
        }

        $content .= "#extra\n";
        foreach ($deck['extra_deck'] as $cardId) {
            $content .= $cardId . "\n";
        }

        $content .= "!side\n";
        foreach ($deck['side_deck'] as $cardId) {
            $content .= $cardId . "\n";
        }

        return $content;
    }

    /**
     * 获取评论数量
     *
     * @param int $deckId 卡组ID
     * @return int 评论数量
     */
    public function getCommentCount($deckId) {
        return (int)$this->db->getValue(
            'SELECT COUNT(*) FROM deck_comments WHERE deck_id = ?',
            [$deckId]
        );
    }
}

