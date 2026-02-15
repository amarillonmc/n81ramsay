<?php
/**
 * 录像播放器页面 - 纯文本日志模式
 */
?>

<div class="replay-player-container">
    <div class="player-header">
        <h2>录像播放</h2>
        <a href="?controller=replay" class="btn btn-secondary">返回列表</a>
    </div>
    
    <div class="player-info">
        <div class="info-item">
            <span class="label">玩家：</span>
            <span class="value"><?php echo htmlspecialchars(implode(' vs ', array_slice($replayInfo['player_names'], 0, 2))); ?></span>
        </div>
        <div class="info-item">
            <span class="label">规则：</span>
            <span class="value"><?php echo htmlspecialchars($replayInfo['duel_rule']); ?></span>
        </div>
        <div class="info-item">
            <span class="label">文件：</span>
            <span class="value"><?php echo htmlspecialchars($replayInfo['filename']); ?></span>
        </div>
    </div>
    
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-content">
            <div class="spinner"></div>
            <p id="loadingText">正在加载录像播放器...</p>
            <div class="loading-progress">
                <div class="progress-bar" id="loadingProgress"></div>
            </div>
            <p class="loading-detail" id="loadingDetail"></p>
        </div>
    </div>
    
    <div class="player-main" id="playerMain" style="display: none;">
        <div class="message-log" id="messageLog">
            <div class="log-entry">准备就绪</div>
        </div>
        
        <div class="controls">
            <div class="playback-controls">
                <button class="btn btn-control" id="btnStepBack" title="后退">
                    <span>⏮</span>
                </button>
                <button class="btn btn-control" id="btnPlay" title="播放/暂停">
                    <span id="playIcon">▶</span>
                </button>
                <button class="btn btn-control" id="btnStepForward" title="前进">
                    <span>⏭</span>
                </button>
                <button class="btn btn-control" id="btnSpeed" title="速度">
                    <span id="speedText">1x</span>
                </button>
            </div>
            <div class="progress-container">
                <div class="progress-bar" id="playbackProgress">
                    <div class="progress-fill" id="progressFill"></div>
                </div>
                <span class="progress-text" id="progressText">0 / 0</span>
            </div>
        </div>
    </div>
    
    <div class="error-overlay" id="errorOverlay" style="display: none;">
        <div class="error-content">
            <h3>加载失败</h3>
            <p id="errorMessage">未知错误</p>
            <a href="?controller=replay" class="btn btn-primary">返回列表</a>
        </div>
    </div>
</div>

<script>
window.RAMSAY_REPLAY_CONFIG = {
    replayFile: <?php echo json_encode($replayInfo['filename']); ?>,
    replayUrl: '?controller=replay&action=file&file=' + encodeURIComponent(<?php echo json_encode($replayInfo['filename']); ?>),
    databasesUrl: '?controller=replay&action=databases',
    playerNames: <?php echo json_encode($replayInfo['player_names']); ?>
};
</script>
<script type="module" src="<?php echo BASE_URL; ?>assets/js/replay-player.bundle.js"></script>

<style>
.replay-player-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.player-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.player-header h2 {
    margin: 0;
    color: #333;
}

.player-info {
    display: flex;
    gap: 30px;
    padding: 15px;
    background: #f5f5f5;
    border-radius: 8px;
    margin-bottom: 20px;
}

.info-item .label {
    color: #666;
    margin-right: 5px;
}

.info-item .value {
    font-weight: 500;
}

.loading-overlay,
.error-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255,255,255,0.95);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.loading-content,
.error-content {
    text-align: center;
    padding: 40px;
}

.spinner {
    width: 50px;
    height: 50px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid #2196f3;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 20px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.loading-progress {
    width: 300px;
    height: 6px;
    background: #e0e0e0;
    border-radius: 3px;
    margin: 15px auto;
    overflow: hidden;
}

.loading-progress .progress-bar {
    height: 100%;
    background: #2196f3;
    width: 0%;
    transition: width 0.3s;
}

.loading-detail {
    font-size: 13px;
    color: #666;
}

.error-content h3 {
    color: #f44336;
    margin-bottom: 15px;
}

.error-content p {
    color: #666;
    margin-bottom: 20px;
}

.player-main {
    background: #1a1a2e;
    border-radius: 12px;
    overflow: hidden;
    padding: 20px;
}

.message-log {
    width: 100%;
    height: 500px;
    background: #0d0d1a;
    border-radius: 8px;
    padding: 15px;
    overflow-y: auto;
    color: #e0e0e0;
    font-size: 14px;
    font-family: 'Consolas', 'Monaco', monospace;
    line-height: 1.6;
}

.log-entry {
    padding: 2px 0;
    border-bottom: 1px solid rgba(255,255,255,0.05);
}

.log-turn {
    color: #ffd700;
    font-weight: bold;
    font-size: 16px;
    padding: 8px 0;
    margin-top: 10px;
    border-bottom: 1px solid rgba(255,215,0,0.3);
}

.log-phase {
    color: #64b5f6;
    padding: 4px 0;
}

.log-draw {
    color: #80deea;
}

.log-lp {
    color: #ef5350;
}

.log-cost {
    color: #ff9800;
}

.log-damage {
    color: #f44336;
}

.log-recover {
    color: #4caf50;
}

.log-summon {
    color: #81c784;
}

.log-chain {
    color: #ce93d8;
}

.log-chain-end {
    color: #90a4ae;
    font-style: italic;
}

.log-attack {
    color: #ff7043;
}

.card-name {
    color: #fff;
    font-weight: bold;
    background: rgba(255,255,255,0.1);
    padding: 1px 6px;
    border-radius: 3px;
}

.controls {
    margin-top: 20px;
    padding: 20px;
    background: rgba(0,0,0,0.3);
    border-radius: 8px;
}

.playback-controls {
    display: flex;
    justify-content: center;
    gap: 15px;
    margin-bottom: 15px;
}

.btn-control {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: rgba(255,255,255,0.1);
    border: 2px solid rgba(255,255,255,0.2);
    color: #fff;
    font-size: 20px;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-control:hover {
    background: rgba(255,255,255,0.2);
    border-color: rgba(255,255,255,0.4);
}

.btn-control.active {
    background: #2196f3;
    border-color: #2196f3;
}

.progress-container {
    display: flex;
    align-items: center;
    gap: 15px;
}

.progress-container .progress-bar {
    flex: 1;
    height: 8px;
    background: rgba(255,255,255,0.1);
    border-radius: 4px;
    cursor: pointer;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: #2196f3;
    width: 0%;
    transition: width 0.1s;
}

.progress-text {
    color: #fff;
    font-size: 14px;
    min-width: 100px;
    text-align: right;
}

.btn {
    display: inline-block;
    padding: 8px 16px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 14px;
    cursor: pointer;
    border: none;
    transition: all 0.2s;
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
}

.btn-secondary:hover {
    background: #e0e0e0;
}

/* 滚动条样式 */
.message-log::-webkit-scrollbar {
    width: 8px;
}

.message-log::-webkit-scrollbar-track {
    background: rgba(255,255,255,0.05);
    border-radius: 4px;
}

.message-log::-webkit-scrollbar-thumb {
    background: rgba(255,255,255,0.2);
    border-radius: 4px;
}

.message-log::-webkit-scrollbar-thumb:hover {
    background: rgba(255,255,255,0.3);
}
</style>
