<?php
/**
 * 投票模型
 *
 * 处理投票相关的数据操作
 */
class Vote {
    /**
     * 数据库实例
     * @var Database
     */
    private $db;

    /**
     * 卡片模型
     * @var Card
     */
    private $cardModel;

    /**
     * 构造函数
     */
    public function __construct() {
        $this->db = Database::getInstance();
        $this->cardModel = new Card();
    }

    /**
     * 创建投票
     *
     * @param int $cardId 卡片ID
     * @param int $environmentId 环境ID
     * @param int $status 禁限状态
     * @param string $reason 理由
     * @param string $initiatorId 发起人ID
     * @return string|false 投票链接或失败
     */
    public function createVote($cardId, $environmentId, $status, $reason, $initiatorId) {
        // 获取当前投票周期
        $voteCycle = $this->db->getCurrentVoteCycle();

        // 检查是否已存在相同卡片和环境的投票
        $existingVote = $this->getVoteByCardAndEnvironment($cardId, $environmentId, $voteCycle);

        if ($existingVote) {
            return $existingVote['vote_link'];
        }

        // 生成投票链接
        $voteLink = Utils::generateVoteLink($cardId, $environmentId, $voteCycle);

        // 插入投票数据
        $voteId = $this->db->insert('votes', [
            'card_id' => $cardId,
            'environment_id' => $environmentId,
            'status' => $status,
            'reason' => $reason,
            'initiator_id' => $initiatorId,
            'vote_cycle' => $voteCycle,
            'created_at' => date('Y-m-d H:i:s'),
            'is_closed' => 0,
            'vote_link' => $voteLink
        ]);

        if (!$voteId) {
            return false;
        }

        // 添加发起人的投票记录
        $this->addVoteRecord($voteId, $initiatorId, $status, '');

        return $voteLink;
    }

    /**
     * 添加投票记录
     *
     * @param int $voteId 投票ID
     * @param string $userId 用户ID
     * @param int $status 禁限状态
     * @param string $comment 评论
     * @return bool 是否成功
     */
    public function addVoteRecord($voteId, $userId, $status, $comment) {
        // 获取客户端IP
        $ipAddress = Utils::getClientIp();

        // 检查是否已投票
        $existingRecord = $this->db->getRow(
            'SELECT id FROM vote_records WHERE vote_id = ? AND ip_address = ?',
            [$voteId, $ipAddress]
        );

        if ($existingRecord) {
            return false;
        }

        // 生成唯一标识符
        $identifier = Utils::generateVoterIdentifier($ipAddress, $userId);

        // 插入投票记录
        $recordId = $this->db->insert('vote_records', [
            'vote_id' => $voteId,
            'user_id' => $userId,
            'ip_address' => $ipAddress,
            'status' => $status,
            'comment' => $comment,
            'identifier' => $identifier,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        return $recordId !== false;
    }

    /**
     * 根据链接获取投票
     *
     * @param string $voteLink 投票链接
     * @return array|null 投票信息
     */
    public function getVoteByLink($voteLink) {
        return $this->db->getRow(
            'SELECT * FROM votes WHERE vote_link = ?',
            [$voteLink]
        );
    }

    /**
     * 根据ID获取投票
     *
     * @param int $voteId 投票ID
     * @return array|null 投票信息
     */
    public function getVoteById($voteId) {
        return $this->db->getRow(
            'SELECT * FROM votes WHERE id = ?',
            [$voteId]
        );
    }

    /**
     * 根据卡片和环境获取投票
     *
     * @param int $cardId 卡片ID
     * @param int $environmentId 环境ID
     * @param int $voteCycle 投票周期
     * @return array|null 投票信息
     */
    public function getVoteByCardAndEnvironment($cardId, $environmentId, $voteCycle) {
        return $this->db->getRow(
            'SELECT * FROM votes WHERE card_id = ? AND environment_id = ? AND vote_cycle = ?',
            [$cardId, $environmentId, $voteCycle]
        );
    }

    /**
     * 获取投票记录
     *
     * @param int $voteId 投票ID
     * @return array 投票记录列表
     */
    public function getVoteRecords($voteId) {
        return $this->db->getRows(
            'SELECT * FROM vote_records WHERE vote_id = ? ORDER BY created_at ASC',
            [$voteId]
        );
    }

    /**
     * 获取投票统计
     *
     * @param int $voteId 投票ID
     * @return array 投票统计
     */
    public function getVoteStats($voteId) {
        $stats = [
            0 => 0, // 禁止
            1 => 0, // 限制
            2 => 0, // 准限制
            3 => 0  // 无限制
        ];

        $records = $this->getVoteRecords($voteId);

        foreach ($records as $record) {
            $status = (int)$record['status'];
            if (isset($stats[$status])) {
                $stats[$status]++;
            }
        }

        return $stats;
    }

    /**
     * 获取所有投票
     *
     * @param bool $includeClosedVotes 是否包含已关闭的投票
     * @param int $page 页码
     * @param int $perPage 每页数量
     * @param bool $groupByCycle 是否按周期分组
     * @return array 投票列表
     */
    public function getAllVotes($includeClosedVotes = true, $page = 1, $perPage = 20, $groupByCycle = false) {
        $offset = ($page - 1) * $perPage;

        $sql = 'SELECT * FROM votes';
        $params = [];

        if (!$includeClosedVotes) {
            $sql .= ' WHERE is_closed = 0';
        }

        if ($groupByCycle) {
            // 按周期和创建时间排序
            $sql .= ' ORDER BY vote_cycle DESC, created_at DESC';
        } else {
            // 仅按创建时间排序
            $sql .= ' ORDER BY created_at DESC';
        }

        $sql .= ' LIMIT ? OFFSET ?';
        $params[] = $perPage;
        $params[] = $offset;

        return $this->db->getRows($sql, $params);
    }

    /**
     * 获取投票总数
     *
     * @param bool $includeClosedVotes 是否包含已关闭的投票
     * @return int 投票总数
     */
    public function getVoteCount($includeClosedVotes = true) {
        $sql = 'SELECT COUNT(*) FROM votes';

        if (!$includeClosedVotes) {
            $sql .= ' WHERE is_closed = 0';
        }

        return $this->db->getValue($sql);
    }

    /**
     * 关闭投票
     *
     * @param int $voteId 投票ID
     * @return bool 是否成功
     */
    public function closeVote($voteId) {
        return $this->db->update(
            'votes',
            ['is_closed' => 1],
            'id = :vote_id',
            ['vote_id' => $voteId]
        ) !== false;
    }

    /**
     * 关闭所有投票
     *
     * @return bool 是否成功
     */
    public function closeAllVotes() {
        return $this->db->update(
            'votes',
            ['is_closed' => 1],
            'is_closed = 0'
        ) !== false;
    }

    /**
     * 重新打开投票
     *
     * @param int $voteId 投票ID
     * @return bool 是否成功
     */
    public function reopenVote($voteId) {
        return $this->db->update(
            'votes',
            ['is_closed' => 0],
            'id = :vote_id',
            ['vote_id' => $voteId]
        ) !== false;
    }

    /**
     * 删除投票
     *
     * @param int $voteId 投票ID
     * @return bool 是否成功
     */
    public function deleteVote($voteId) {
        // 先删除投票记录
        $this->db->delete(
            'vote_records',
            'vote_id = :vote_id',
            ['vote_id' => $voteId]
        );

        // 再删除投票
        return $this->db->delete(
            'votes',
            'id = :vote_id',
            ['vote_id' => $voteId]
        ) !== false;
    }

    /**
     * 增加投票周期
     *
     * @return bool 是否成功
     */
    public function incrementVoteCycle() {
        $currentCycle = $this->db->getCurrentVoteCycle();
        return $this->db->updateVoteCycle($currentCycle + 1);
    }

    /**
     * 根据投票模式确定最终状态
     *
     * @param array $stats 投票统计
     * @return int 最终状态
     */
    private function determineFinalStatus($stats) {
        // 默认无限制
        $finalStatus = 3;

        // 获取投票模式
        $votingMode = defined('VOTING_RELAXED_MODE') ? VOTING_RELAXED_MODE : 0;

        // 根据不同的投票模式处理
        switch ($votingMode) {
            case 0: // 默认模式：票数最多的状态
                $maxVotes = 0;
                foreach ($stats as $status => $count) {
                    if ($count > $maxVotes) {
                        $maxVotes = $count;
                        $finalStatus = $status;
                    }
                }
                break;

            case 1: // 抵消后最高限制
                // 计算各限制级别的净票数（高限制和低限制相互抵消）
                $netVotes = $this->calculateNetVotes($stats);

                // 找出净票数最多的最高限制
                $maxNetVotes = 0;
                $highestRestriction = 3;

                foreach ($netVotes as $status => $votes) {
                    if ($votes > $maxNetVotes || ($votes == $maxNetVotes && $status < $highestRestriction)) {
                        $maxNetVotes = $votes;
                        $highestRestriction = $status;
                    }
                }

                $finalStatus = $highestRestriction;
                break;

            case 2: // 抵消后最低限制
                // 计算各限制级别的净票数（高限制和低限制相互抵消）
                $netVotes = $this->calculateNetVotes($stats);

                // 找出净票数最多的最低限制
                $maxNetVotes = 0;
                $lowestRestriction = 0;

                foreach ($netVotes as $status => $votes) {
                    if ($votes > $maxNetVotes || ($votes == $maxNetVotes && $status > $lowestRestriction)) {
                        $maxNetVotes = $votes;
                        $lowestRestriction = $status;
                    }
                }

                $finalStatus = $lowestRestriction;
                break;

            case 3: // 得票最多的最低限制
                $maxVotes = 0;
                $lowestRestriction = 0;

                foreach ($stats as $status => $count) {
                    if ($count > $maxVotes || ($count == $maxVotes && $status > $lowestRestriction)) {
                        $maxVotes = $count;
                        $lowestRestriction = $status;
                    }
                }

                $finalStatus = $lowestRestriction;
                break;
        }

        return $finalStatus;
    }

    /**
     * 计算各限制级别的净票数
     *
     * @param array $stats 投票统计
     * @return array 净票数
     */
    private function calculateNetVotes($stats) {
        $netVotes = [
            0 => 0, // 禁止
            1 => 0, // 限制
            2 => 0, // 准限制
            3 => 0  // 无限制
        ];

        // 初始化净票数为原始票数
        foreach ($stats as $status => $count) {
            $netVotes[$status] = $count;
        }

        // 禁止(0)和无限制(3)相互抵消
        $cancelVotes = min($netVotes[0], $netVotes[3]);
        $netVotes[0] -= $cancelVotes;
        $netVotes[3] -= $cancelVotes;

        // 限制(1)和准限制(2)相互抵消
        $cancelVotes = min($netVotes[1], $netVotes[2]);
        $netVotes[1] -= $cancelVotes;
        $netVotes[2] -= $cancelVotes;

        return $netVotes;
    }

    /**
     * 获取投票结果
     *
     * @param int $voteCycle 投票周期
     * @return array 投票结果
     */
    public function getVoteResults($voteCycle = null) {
        if ($voteCycle === null) {
            $voteCycle = $this->db->getCurrentVoteCycle();
        }

        $results = [];

        // 获取所有投票
        $votes = $this->db->getRows(
            'SELECT * FROM votes WHERE vote_cycle = ?',
            [$voteCycle]
        );

        foreach ($votes as $vote) {
            $voteId = $vote['id'];
            $cardId = $vote['card_id'];
            $environmentId = $vote['environment_id'];

            // 获取卡片信息
            $card = $this->cardModel->getCardById($cardId);

            if (!$card) {
                continue;
            }

            // 获取环境信息
            $environment = Utils::getEnvironmentById($environmentId);

            if (!$environment) {
                continue;
            }

            // 获取投票统计
            $stats = $this->getVoteStats($voteId);

            // 根据投票模式确定最终状态
            $finalStatus = $this->determineFinalStatus($stats);

            // 添加到结果
            $results[] = [
                'vote_id' => $voteId,
                'card_id' => $cardId,
                'card_name' => $card['name'],
                'environment_id' => $environmentId,
                'environment_header' => $environment['header'],
                'environment_text' => $environment['text'],
                'final_status' => $finalStatus,
                'stats' => $stats,
                'total_votes' => array_sum($stats)
            ];
        }

        return $results;
    }

    /**
     * 生成禁卡表文本
     *
     * @param int $environmentId 环境ID
     * @param int $voteCycle 投票周期
     * @return string 禁卡表文本
     */
    public function generateLflistText($environmentId, $voteCycle = null) {
        if ($voteCycle === null) {
            $voteCycle = $this->db->getCurrentVoteCycle();
        }

        // 获取环境信息
        $environment = Utils::getEnvironmentById($environmentId);

        if (!$environment) {
            return '';
        }

        // 获取投票结果
        $results = $this->getVoteResults($voteCycle);

        // 按状态分组
        $groupedResults = [
            0 => [], // 禁止
            1 => [], // 限制
            2 => []  // 准限制
        ];

        foreach ($results as $result) {
            if ($result['environment_id'] == $environmentId && $result['final_status'] < 3) {
                $groupedResults[$result['final_status']][] = $result;
            }
        }

        // 生成文本
        $text = $environment['header'] . "\n";
        $text .= "#Generated by RAMSAY - Vote Cycle " . $voteCycle . " - " . date('Ymd') . "\n";

        // 添加禁止卡片
        if (!empty($groupedResults[0])) {
            $text .= "#Forbidden\n";
            foreach ($groupedResults[0] as $result) {
                $text .= $result['card_id'] . " 0 --" . $result['card_name'] . "\n";
            }
        }

        // 添加限制卡片
        if (!empty($groupedResults[1])) {
            $text .= "#Limited\n";
            foreach ($groupedResults[1] as $result) {
                $text .= $result['card_id'] . " 1 --" . $result['card_name'] . "\n";
            }
        }

        // 添加准限制卡片
        if (!empty($groupedResults[2])) {
            $text .= "#Semi-Limited\n";
            foreach ($groupedResults[2] as $result) {
                $text .= $result['card_id'] . " 2 --" . $result['card_name'] . "\n";
            }
        }

        return $text;
    }

    /**
     * 生成可读的禁卡表文本
     *
     * @param int $environmentId 环境ID
     * @param int $voteCycle 投票周期
     * @return string 可读的禁卡表文本
     */
    public function generateReadableBanlistText($environmentId, $voteCycle = null) {
        if ($voteCycle === null) {
            $voteCycle = $this->db->getCurrentVoteCycle();
        }

        // 获取环境信息
        $environment = Utils::getEnvironmentById($environmentId);

        if (!$environment) {
            return '';
        }

        // 获取投票结果
        $results = $this->getVoteResults($voteCycle);

        // 获取卡片解析器实例
        $cardParser = CardParser::getInstance();

        // 按状态分组
        $groupedResults = [
            0 => [], // 禁止
            1 => [], // 限制
            2 => [], // 准限制
            3 => []  // 无限制
        ];

        foreach ($results as $result) {
            if ($result['environment_id'] == $environmentId) {
                // 获取卡片当前的禁限状态
                $currentStatus = $cardParser->getCardLimitStatus($result['card_id'], $environment['header']);

                // 只有当新状态与当前状态不同时才添加到结果中
                if ($result['final_status'] != $currentStatus) {
                    // 添加当前状态信息
                    $result['current_status'] = $currentStatus;
                    $groupedResults[$result['final_status']][] = $result;
                }
            }
        }

        // 生成文本
        $text = "";

        // 环境标题
        $text .= $environment['text'] . "：\n\n";

        // 添加禁止卡片
        if (!empty($groupedResults[0])) {
            if ($environment['id'] == 3) { // 狂野禁止环境
                $text .= "狂野禁止：\n";
            } else {
                $text .= "禁止：\n";
            }

            foreach ($groupedResults[0] as $result) {
                $text .= $result['card_name'] . "（" . $result['card_id'] . "）";

                // 如果之前不是禁止状态，则添加之前的状态
                if ($result['current_status'] != 0) {
                    $text .= "\t\t\t\t之前为" . Utils::getLimitStatusText($result['current_status']) . "卡";
                }

                $text .= "\n";
            }
            $text .= "\n";
        }

        // 添加限制卡片
        if (!empty($groupedResults[1])) {
            $text .= "限制：\n";
            foreach ($groupedResults[1] as $result) {
                $text .= $result['card_name'] . "（" . $result['card_id'] . "）";

                // 如果之前不是限制状态，则添加之前的状态
                if ($result['current_status'] != 1) {
                    $text .= "\t\t\t\t之前为" . Utils::getLimitStatusText($result['current_status']) . "卡";
                }

                $text .= "\n";
            }
            $text .= "\n";
        }

        // 添加准限制卡片
        if (!empty($groupedResults[2])) {
            $text .= "准限制：\n";
            foreach ($groupedResults[2] as $result) {
                $text .= $result['card_name'] . "（" . $result['card_id'] . "）";

                // 如果之前不是准限制状态，则添加之前的状态
                if ($result['current_status'] != 2) {
                    $text .= "\t\t\t\t之前为" . Utils::getLimitStatusText($result['current_status']) . "卡";
                }

                $text .= "\n";
            }
            $text .= "\n";
        }

        // 添加无限制卡片（从其他状态解除限制的卡片）
        if (!empty($groupedResults[3])) {
            $text .= "无限制：\n";
            foreach ($groupedResults[3] as $result) {
                $text .= $result['card_name'] . "（" . $result['card_id'] . "）";

                // 添加之前的状态
                $text .= "\t\t\t\t之前为" . Utils::getLimitStatusText($result['current_status']) . "卡";

                $text .= "\n";
            }
        }

        return $text;
    }
}
