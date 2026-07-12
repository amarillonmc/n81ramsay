<?php
/**
 * srvpro2 录像元数据格式化器
 *
 * 将 PostgreSQL 的 duel_record / duel_record_player 行转换为现有录像视图
 * 可以直接使用的数据结构。
 */
class Srvpro2ReplayFormatter {
    /**
     * 格式化一条录像记录
     *
     * @param array $record duel_record 查询结果
     * @param array $players duel_record_player 查询结果
     * @return array 录像视图数据
     */
    public function format($record, $players) {
        usort($players, function($left, $right) {
            return (int)$left['pos'] - (int)$right['pos'];
        });

        $hostInfo = $this->decodeHostInfo(isset($record['host_info']) ? $record['host_info'] : []);
        $mode = isset($hostInfo['mode']) ? (int)$hostInfo['mode'] : 0;
        $playerNames = [];
        $normalizedPlayers = [];

        foreach ($players as $player) {
            $name = isset($player['name']) ? (string)$player['name'] : '';
            $playerNames[] = $name;
            $normalizedPlayers[] = [
                'pos' => isset($player['pos']) ? (int)$player['pos'] : 0,
                'name' => $name,
                'score' => isset($player['score']) ? (int)$player['score'] : 0,
                'winner' => $this->toBoolean(isset($player['winner']) ? $player['winner'] : false)
            ];
        }

        $id = isset($record['id']) ? (string)$record['id'] : '';
        $modifiedTime = $this->normalizeDateTime(
            isset($record['end_time']) ? $record['end_time'] : ''
        );

        return [
            'id' => $id,
            'filename' => $id . '.yrp',
            'file_path' => null,
            'player_names' => $playerNames,
            'players' => $normalizedPlayers,
            'versus_text' => $this->buildVersusText($normalizedPlayers, $mode),
            'winner_names' => $this->getWinnerNames($normalizedPlayers),
            'duel_rule' => $this->getModeName($mode),
            'is_yrp2' => true,
            'file_size' => null,
            'modified_time' => $modifiedTime,
            'datetime' => $modifiedTime,
            'room_name' => isset($record['room_name']) ? (string)$record['room_name'] : '',
            'duel_count' => isset($record['duel_count']) ? (int)$record['duel_count'] : 0,
            'source' => 'srvpro2'
        ];
    }

    /**
     * 解析 hostInfo JSON
     *
     * @param mixed $hostInfo 原始数据
     * @return array hostInfo 数组
     */
    private function decodeHostInfo($hostInfo) {
        if (is_array($hostInfo)) {
            return $hostInfo;
        }

        if (!is_string($hostInfo) || $hostInfo === '') {
            return [];
        }

        $decoded = json_decode($hostInfo, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * 构建对阵文本
     *
     * @param array $players 玩家列表
     * @param int $mode srvpro2 房间模式
     * @return string 对阵文本
     */
    private function buildVersusText($players, $mode) {
        $isTag = ($mode & 0x2) !== 0;
        $teamOffsetBit = $isTag ? 1 : 0;
        $teams = [[], []];

        foreach ($players as $player) {
            $pos = (int)$player['pos'];
            $team = ($pos & (0x1 << $teamOffsetBit)) >> $teamOffsetBit;
            if ($team !== 0 && $team !== 1) {
                continue;
            }
            $teams[$team][] = $player['name'];
        }

        $left = !empty($teams[0]) ? implode('+', $teams[0]) : '未知玩家';
        $right = !empty($teams[1]) ? implode('+', $teams[1]) : '未知玩家';
        return $left . ' vs ' . $right;
    }

    /**
     * 获取房间模式名称
     *
     * @param int $mode srvpro2 房间模式
     * @return string 模式名称
     */
    private function getModeName($mode) {
        if (($mode & 0x2) !== 0) {
            return '双打';
        }

        if ($mode !== 0) {
            return '比赛';
        }

        return '单局';
    }

    /**
     * 获取胜者名称
     *
     * @param array $players 玩家列表
     * @return array 胜者名称
     */
    private function getWinnerNames($players) {
        $winners = [];
        foreach ($players as $player) {
            if ($player['winner']) {
                $winners[] = $player['name'];
            }
        }
        return $winners;
    }

    /**
     * 标准化 PostgreSQL 布尔值
     *
     * @param mixed $value 原始值
     * @return bool 布尔值
     */
    private function toBoolean($value) {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower((string)$value), ['1', 't', 'true', 'yes'], true);
    }

    /**
     * 标准化日期时间显示
     *
     * @param mixed $value 原始日期时间
     * @return string YYYY-MM-DD HH:MM:SS
     */
    private function normalizeDateTime($value) {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        $text = trim((string)$value);
        if (preg_match('/^(\d{4}-\d{2}-\d{2})[ T](\d{2}:\d{2}:\d{2})/', $text, $matches)) {
            return $matches[1] . ' ' . $matches[2];
        }

        return $text;
    }
}
