<?php
/**
 * 录像播放器页面
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
        <div class="duel-field">
            <div class="player-area player-1">
                <div class="player-info-bar">
                    <span class="player-name" id="player1Name">玩家 1</span>
                    <span class="lp-display">LP: <span id="player1Lp">8000</span></span>
                </div>
                <div class="field-row">
                    <div class="card-zone grave" data-zone="grave1">
                        <span class="zone-label">墓地</span>
                        <span class="card-count" id="grave1Count">0</span>
                    </div>
                    <div class="card-zone deck" data-zone="deck1">
                        <span class="zone-label">卡组</span>
                        <span class="card-count" id="deck1Count">0</span>
                    </div>
                    <div class="card-zone extra" data-zone="extra1">
                        <span class="zone-label">额外</span>
                        <span class="card-count" id="extra1Count">0</span>
                    </div>
                </div>
                <div class="field-row szone-row">
                    <div class="szone" data-zone="szone1-0"></div>
                    <div class="szone" data-zone="szone1-1"></div>
                    <div class="szone" data-zone="szone1-2"></div>
                    <div class="szone" data-zone="szone1-3"></div>
                    <div class="szone" data-zone="szone1-4"></div>
                </div>
                <div class="field-row mzone-row">
                    <div class="mzone" data-zone="mzone1-0"></div>
                    <div class="mzone" data-zone="mzone1-1"></div>
                    <div class="mzone" data-zone="mzone1-2"></div>
                    <div class="mzone" data-zone="mzone1-3"></div>
                    <div class="mzone" data-zone="mzone1-4"></div>
                </div>
            </div>
            
            <div class="center-area">
                <div class="message-log" id="messageLog">
                    <div class="log-entry">准备就绪</div>
                </div>
            </div>
            
            <div class="player-area player-0">
                <div class="field-row mzone-row">
                    <div class="mzone" data-zone="mzone0-0"></div>
                    <div class="mzone" data-zone="mzone0-1"></div>
                    <div class="mzone" data-zone="mzone0-2"></div>
                    <div class="mzone" data-zone="mzone0-3"></div>
                    <div class="mzone" data-zone="mzone0-4"></div>
                </div>
                <div class="field-row szone-row">
                    <div class="szone" data-zone="szone0-0"></div>
                    <div class="szone" data-zone="szone0-1"></div>
                    <div class="szone" data-zone="szone0-2"></div>
                    <div class="szone" data-zone="szone0-3"></div>
                    <div class="szone" data-zone="szone0-4"></div>
                </div>
                <div class="field-row">
                    <div class="card-zone grave" data-zone="grave0">
                        <span class="zone-label">墓地</span>
                        <span class="card-count" id="grave0Count">0</span>
                    </div>
                    <div class="card-zone deck" data-zone="deck0">
                        <span class="zone-label">卡组</span>
                        <span class="card-count" id="deck0Count">0</span>
                    </div>
                    <div class="card-zone extra" data-zone="extra0">
                        <span class="zone-label">额外</span>
                        <span class="card-count" id="extra0Count">0</span>
                    </div>
                </div>
                <div class="player-info-bar">
                    <span class="player-name" id="player0Name">玩家 0</span>
                    <span class="lp-display">LP: <span id="player0Lp">8000</span></span>
                </div>
            </div>
        </div>
        
        <div class="hand-area">
            <div class="hand" id="hand0">
                <h4>玩家 0 手牌</h4>
                <div class="hand-cards" id="hand0Cards"></div>
            </div>
            <div class="hand" id="hand1">
                <h4>玩家 1 手牌</h4>
                <div class="hand-cards" id="hand1Cards"></div>
            </div>
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
    imageUrlTemplate: '?controller=replay&action=cardimage&type={type}&id={id}',
    playerNames: <?php echo json_encode($replayInfo['player_names']); ?>
};
</script>
<script type="module" src="<?php echo BASE_URL; ?>assets/js/replay-player.js"></script>

<style>
.replay-player-container {
    max-width: 1400px;
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

.duel-field {
    display: flex;
    flex-direction: column;
    gap: 10px;
    min-height: 500px;
}

.player-area {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.player-info-bar {
    display: flex;
    justify-content: space-between;
    padding: 8px 15px;
    background: rgba(255,255,255,0.1);
    border-radius: 6px;
    color: #fff;
}

.player-name {
    font-weight: 600;
}

.lp-display {
    font-size: 18px;
    color: #ffd700;
}

.field-row {
    display: flex;
    gap: 8px;
    justify-content: center;
}

.card-zone {
    width: 70px;
    height: 90px;
    background: rgba(255,255,255,0.05);
    border: 2px dashed rgba(255,255,255,0.2);
    border-radius: 6px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: rgba(255,255,255,0.5);
    font-size: 12px;
}

.card-zone .zone-label {
    margin-bottom: 5px;
}

.card-zone .card-count {
    font-size: 18px;
    font-weight: bold;
}

.mzone,
.szone {
    width: 70px;
    height: 90px;
    background: rgba(255,255,255,0.05);
    border: 2px solid rgba(255,255,255,0.1);
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.mzone {
    border-color: rgba(255,100,100,0.3);
}

.szone {
    border-color: rgba(100,100,255,0.3);
}

.mzone .card-image,
.szone .card-image {
    width: 100%;
    height: 100%;
    border-radius: 4px;
    object-fit: cover;
}

.center-area {
    display: flex;
    justify-content: center;
    padding: 10px;
}

.message-log {
    width: 100%;
    max-width: 600px;
    height: 80px;
    background: rgba(0,0,0,0.3);
    border-radius: 6px;
    padding: 10px;
    overflow-y: auto;
    color: #fff;
    font-size: 13px;
}

.log-entry {
    padding: 3px 0;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.hand-area {
    display: flex;
    gap: 20px;
    margin-top: 15px;
    padding: 15px;
    background: rgba(255,255,255,0.05);
    border-radius: 8px;
}

.hand {
    flex: 1;
}

.hand h4 {
    color: #fff;
    margin: 0 0 10px;
    font-size: 14px;
}

.hand-cards {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.hand-card {
    width: 60px;
    height: 80px;
    border-radius: 4px;
    object-fit: cover;
    border: 2px solid transparent;
    transition: transform 0.2s, border-color 0.2s;
}

.hand-card:hover {
    transform: translateY(-10px);
    border-color: #ffd700;
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
    min-width: 80px;
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

.card-tooltip {
    position: absolute;
    z-index: 100;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    padding: 10px;
    max-width: 300px;
}

.card-tooltip img {
    width: 100px;
    float: left;
    margin-right: 10px;
    border-radius: 4px;
}

.card-tooltip .card-name {
    font-weight: 600;
    margin-bottom: 5px;
}

.card-tooltip .card-desc {
    font-size: 12px;
    color: #666;
}
</style>
