<?php
/**
 * 录像列表页面
 */

function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' B';
    }
}
?>

<div class="replay-list-container">
    <h2>录像回放</h2>
    
    <?php if (empty($result['replays'])): ?>
        <div class="no-replays">
            <p>暂无录像文件</p>
            <p class="hint">录像文件目录：<?php echo htmlspecialchars(defined('REPLAY_PATH') ? REPLAY_PATH : '未配置'); ?></p>
        </div>
    <?php else: ?>
        <div class="replay-stats">
            <span>共 <?php echo $result['total']; ?> 个录像</span>
        </div>
        
        <table class="replay-table">
            <thead>
                <tr>
                    <th>玩家</th>
                    <th>规则</th>
                    <th>类型</th>
                    <th>文件大小</th>
                    <th>修改时间</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($result['replays'] as $replay): ?>
                    <tr>
                        <td class="player-names">
                            <?php 
                            $players = $replay['player_names'];
                            echo htmlspecialchars(implode(' vs ', array_slice($players, 0, 2)));
                            if (count($players) > 2) {
                                echo ' <span class="tag-players">(' . count($players) . 'P)</span>';
                            }
                            ?>
                        </td>
                        <td><?php echo htmlspecialchars($replay['duel_rule']); ?></td>
                        <td>
                            <span class="replay-type <?php echo $replay['is_yrp2'] ? 'yrp2' : 'yrp'; ?>">
                                <?php echo $replay['is_yrp2'] ? 'YRP2' : 'YRP'; ?>
                            </span>
                        </td>
                        <td><?php echo formatFileSize($replay['file_size']); ?></td>
                        <td><?php echo htmlspecialchars($replay['modified_time']); ?></td>
                        <td class="actions">
                            <a href="?controller=replay&action=play&file=<?php echo urlencode($replay['filename']); ?>" 
                               class="btn btn-primary btn-sm">
                                播放
                            </a>
                            <a href="?controller=replay&action=file&file=<?php echo urlencode($replay['filename']); ?>" 
                               class="btn btn-secondary btn-sm" download>
                                下载
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php if ($result['total_pages'] > 1): ?>
            <div class="pagination">
                <?php if ($result['page'] > 1): ?>
                    <a href="?controller=replay&page=1" class="btn btn-sm">首页</a>
                    <a href="?controller=replay&page=<?php echo $result['page'] - 1; ?>" class="btn btn-sm">上一页</a>
                <?php endif; ?>
                
                <span class="page-info">
                    第 <?php echo $result['page']; ?> / <?php echo $result['total_pages']; ?> 页
                </span>
                
                <?php if ($result['page'] < $result['total_pages']): ?>
                    <a href="?controller=replay&page=<?php echo $result['page'] + 1; ?>" class="btn btn-sm">下一页</a>
                    <a href="?controller=replay&page=<?php echo $result['total_pages']; ?>" class="btn btn-sm">末页</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<style>
.replay-list-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.replay-list-container h2 {
    margin-bottom: 20px;
    color: #333;
}

.replay-stats {
    margin-bottom: 15px;
    color: #666;
    font-size: 14px;
}

.replay-table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.replay-table th,
.replay-table td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.replay-table th {
    background: #f5f5f5;
    font-weight: 600;
    color: #333;
}

.replay-table tr:hover {
    background: #f9f9f9;
}

.player-names {
    font-weight: 500;
}

.tag-players {
    font-size: 12px;
    color: #888;
}

.replay-type {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 500;
}

.replay-type.yrp {
    background: #e3f2fd;
    color: #1976d2;
}

.replay-type.yrp2 {
    background: #fce4ec;
    color: #c2185b;
}

.actions {
    white-space: nowrap;
}

.btn {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 4px;
    text-decoration: none;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-sm {
    padding: 4px 10px;
    font-size: 12px;
}

.btn-primary {
    background: #2196f3;
    color: #fff;
}

.btn-primary:hover {
    background: #1976d2;
}

.btn-secondary {
    background: #f5f5f5;
    color: #333;
    border: 1px solid #ddd;
}

.btn-secondary:hover {
    background: #eee;
}

.no-replays {
    text-align: center;
    padding: 60px 20px;
    background: #fff;
    border-radius: 8px;
}

.no-replays p {
    color: #666;
    margin-bottom: 10px;
}

.no-replays .hint {
    font-size: 13px;
    color: #999;
}

.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
    margin-top: 20px;
    padding: 15px;
}

.page-info {
    color: #666;
    font-size: 14px;
}
</style>
