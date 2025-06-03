<?php
/**
 * 投票者封禁模型
 *
 * 处理投票者封禁相关的数据操作
 */
class VoterBan {
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
     * 添加封禁记录
     *
     * @param string $voterIdentifier 投票者标识符
     * @param int $banLevel 封禁等级
     * @param string $reason 封禁理由
     * @param string $bannedBy 封禁操作者
     * @return bool 是否成功
     */
    public function addBan($voterIdentifier, $banLevel, $reason, $bannedBy) {
        // 检查是否已存在封禁记录
        $existingBan = $this->getBanByIdentifier($voterIdentifier);
        
        if ($existingBan && $existingBan['is_active']) {
            // 如果已存在活跃的封禁记录，则更新
            return $this->updateBan($existingBan['id'], $banLevel, $reason, $bannedBy);
        } else {
            // 插入新的封禁记录
            $banId = $this->db->insert('voter_bans', [
                'voter_identifier' => $voterIdentifier,
                'ban_level' => $banLevel,
                'reason' => $reason,
                'banned_by' => $bannedBy,
                'banned_at' => date('Y-m-d H:i:s'),
                'is_active' => 1
            ]);
            
            return $banId !== false;
        }
    }

    /**
     * 更新封禁记录
     *
     * @param int $banId 封禁记录ID
     * @param int $banLevel 封禁等级
     * @param string $reason 封禁理由
     * @param string $bannedBy 封禁操作者
     * @return bool 是否成功
     */
    public function updateBan($banId, $banLevel, $reason, $bannedBy) {
        $result = $this->db->update('voter_bans', [
            'ban_level' => $banLevel,
            'reason' => $reason,
            'banned_by' => $bannedBy,
            'banned_at' => date('Y-m-d H:i:s'),
            'is_active' => 1
        ], 'id = ?', [$banId]);
        
        return $result > 0;
    }

    /**
     * 解除封禁
     *
     * @param string $voterIdentifier 投票者标识符
     * @return bool 是否成功
     */
    public function removeBan($voterIdentifier) {
        $result = $this->db->update('voter_bans', [
            'is_active' => 0
        ], 'voter_identifier = ? AND is_active = 1', [$voterIdentifier]);
        
        return $result > 0;
    }

    /**
     * 根据标识符获取封禁记录
     *
     * @param string $voterIdentifier 投票者标识符
     * @return array|null 封禁记录
     */
    public function getBanByIdentifier($voterIdentifier) {
        return $this->db->getRow(
            'SELECT * FROM voter_bans WHERE voter_identifier = ? AND is_active = 1',
            [$voterIdentifier]
        );
    }

    /**
     * 获取所有活跃的封禁记录
     *
     * @return array 封禁记录列表
     */
    public function getAllActiveBans() {
        return $this->db->getRows(
            'SELECT * FROM voter_bans WHERE is_active = 1 ORDER BY banned_at DESC'
        );
    }

    /**
     * 检查投票者是否被封禁
     *
     * @param string $voterIdentifier 投票者标识符
     * @return array|false 封禁信息或false
     */
    public function checkBan($voterIdentifier) {
        $ban = $this->getBanByIdentifier($voterIdentifier);
        return $ban ? $ban : false;
    }

    /**
     * 获取封禁等级文本
     *
     * @param int $banLevel 封禁等级
     * @return string 封禁等级文本
     */
    public static function getBanLevelText($banLevel) {
        switch ($banLevel) {
            case 1:
                return '限制投票（需要详细理由）';
            case 2:
                return '禁止投票';
            default:
                return '未知等级';
        }
    }

    /**
     * 获取封禁等级CSS类
     *
     * @param int $banLevel 封禁等级
     * @return string CSS类名
     */
    public static function getBanLevelClass($banLevel) {
        switch ($banLevel) {
            case 1:
                return 'ban-level-1';
            case 2:
                return 'ban-level-2';
            default:
                return 'ban-level-unknown';
        }
    }

    /**
     * 记录封禁操作日志
     *
     * @param string $action 操作类型（ban/unban）
     * @param string $voterIdentifier 投票者标识符
     * @param int $banLevel 封禁等级（解封时为0）
     * @param string $reason 理由
     * @param string $operator 操作者
     */
    public function logBanAction($action, $voterIdentifier, $banLevel, $reason, $operator) {
        // 确保日志目录存在
        $logDir = __DIR__ . '/../../logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        // 准备日志内容
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'action' => $action,
            'operator' => $operator,
            'voter_identifier' => $voterIdentifier,
            'ban_level' => $banLevel,
            'reason' => $reason
        ];

        // 写入日志文件
        $logFile = $logDir . '/voterBan_' . date('Y-m-d') . '.txt';
        $logContent = json_encode($logData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
        
        file_put_contents($logFile, $logContent, FILE_APPEND | LOCK_EX);
    }
}
