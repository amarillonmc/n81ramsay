/**
 * RAMSAY 录像播放器
 * 纯文本日志模式
 */

import {
    createOcgcoreWrapper,
    playYrpStep,
    SqljsCardReader
} from 'koishipro-core.js';

import initSqlJs from 'sql.js';

const LOCATION = {
    DECK: 0x01,
    HAND: 0x02,
    MZONE: 0x04,
    SZONE: 0x08,
    GRAVE: 0x10,
    REMOVED: 0x20,
    EXTRA: 0x40,
    OVERLAY: 0x80
};

const LOCATION_NAMES = {
    0x01: '卡组',
    0x02: '手牌',
    0x04: '怪兽区',
    0x08: '魔陷区',
    0x10: '墓地',
    0x20: '除外区',
    0x40: '额外卡组',
    0x80: '超量素材'
};

const PHASE_NAMES = {
    0x01: '抽卡阶段',
    0x02: '准备阶段',
    0x04: '主要阶段1',
    0x08: '战斗阶段',
    0x10: '伤害步骤',
    0x20: '伤害计算',
    0x40: '战斗结束',
    0x80: '主要阶段2',
    0x100: '结束阶段'
};

class ReplayPlayer {
    constructor() {
        this.config = window.RAMSAY_REPLAY_CONFIG;
        this.wrapper = null;
        this.yrpData = null;
        this.messages = [];
        this.currentIndex = 0;
        this.isPlaying = false;
        this.playSpeed = 1;
        this.speedOptions = [0.5, 1, 2, 5, 10, 50];
        this.speedIndex = 1;
        this.cardDatabases = [];
        this.cardCache = new Map();
        this.scriptCache = new Map();
        this.scriptUrlBase = null;
        this.duel = null;
        this.playerNames = ['玩家0', '玩家1'];
        this.playerLP = [8000, 8000];
        this.currentTurn = 0;
        
        this.playInterval = null;
        
        this.initUI();
        this.load();
    }

    initUI() {
        this.elements = {
            loadingOverlay: document.getElementById('loadingOverlay'),
            loadingText: document.getElementById('loadingText'),
            loadingProgress: document.getElementById('loadingProgress'),
            loadingDetail: document.getElementById('loadingDetail'),
            playerMain: document.getElementById('playerMain'),
            errorOverlay: document.getElementById('errorOverlay'),
            errorMessage: document.getElementById('errorMessage'),
            btnPlay: document.getElementById('btnPlay'),
            playIcon: document.getElementById('playIcon'),
            btnStepBack: document.getElementById('btnStepBack'),
            btnStepForward: document.getElementById('btnStepForward'),
            btnSpeed: document.getElementById('btnSpeed'),
            speedText: document.getElementById('speedText'),
            playbackProgress: document.getElementById('playbackProgress'),
            progressFill: document.getElementById('progressFill'),
            progressText: document.getElementById('progressText'),
            messageLog: document.getElementById('messageLog')
        };

        this.elements.btnPlay.addEventListener('click', () => this.togglePlay());
        this.elements.btnStepBack.addEventListener('click', () => this.stepBack());
        this.elements.btnStepForward.addEventListener('click', () => this.stepForward());
        this.elements.btnSpeed.addEventListener('click', () => this.cycleSpeed());
        this.elements.playbackProgress.addEventListener('click', (e) => this.seekTo(e));
    }

    setLoadingProgress(percent, text) {
        this.elements.loadingProgress.style.width = `${percent}%`;
        if (text) {
            this.elements.loadingDetail.textContent = text;
        }
    }

    showError(message) {
        this.elements.loadingOverlay.style.display = 'none';
        this.elements.errorMessage.textContent = message;
        this.elements.errorOverlay.style.display = 'flex';
    }

    async load() {
        try {
            this.setLoadingProgress(5, '初始化 SQL.js...');
            
            this.SQL = await initSqlJs({
                locateFile: file => `https://cdn.jsdelivr.net/npm/sql.js@1.13.0/dist/sql-wasm.wasm`
            });
            
            this.setLoadingProgress(15, '加载卡片数据库...');
            await this.loadCardDatabases(this.SQL);
            
            if (this.cardDatabases.length === 0) {
                console.warn('没有加载卡片数据库，使用空数据库');
                this.cardDatabases.push(new this.SQL.Database());
            }
            
            this.setLoadingProgress(35, '下载 OCGcore WASM...');
            const wasmResponse = await fetch('https://cdn.jsdelivr.net/npm/koishipro-core.js@1.3.4/dist/vendor/wasm_esm/libocgcore.wasm');
            if (!wasmResponse.ok) {
                throw new Error(`下载 OCGcore WASM 失败: ${wasmResponse.status}`);
            }
            const wasmBinary = new Uint8Array(await wasmResponse.arrayBuffer());
            
            this.setLoadingProgress(45, '初始化 OCGcore...');
            this.wrapper = await createOcgcoreWrapper({
                scriptBufferSize: 0x600000,
                logBufferSize: 8192,
                wasmBinary: wasmBinary
            });

            this.setLoadingProgress(55, '配置卡片读取器...');
            this.wrapper.setCardReader(SqljsCardReader(this.SQL, ...this.cardDatabases));

            this.setLoadingProgress(60, '配置脚本读取器...');
            this.wrapper.setScriptReader((scriptPath) => this.loadScript(scriptPath));

            this.setLoadingProgress(65, '下载录像文件...');
            const yrpResponse = await fetch(this.config.replayUrl);
            if (!yrpResponse.ok) {
                throw new Error(`下载录像失败: ${yrpResponse.status}`);
            }
            this.yrpData = new Uint8Array(await yrpResponse.arrayBuffer());

            this.setLoadingProgress(75, '解析录像数据...');
            await this.parseReplay();

            this.setLoadingProgress(100, '准备就绪');
            
            setTimeout(() => {
                this.elements.loadingOverlay.style.display = 'none';
                this.elements.playerMain.style.display = 'block';
                this.updateProgress();
                this.logMessage(`录像加载完成，共 ${this.messages.length} 条消息`);
            }, 300);

        } catch (error) {
            console.error('加载失败:', error);
            this.showError(`加载失败: ${error.message}`);
        }
    }

    loadScript(scriptPath) {
        if (this.scriptCache.has(scriptPath)) {
            return this.scriptCache.get(scriptPath);
        }

        let normalizedPath = scriptPath;
        if (normalizedPath.startsWith('./')) {
            normalizedPath = normalizedPath.substring(2);
        }

        if (this.scriptUrlBase) {
            const scriptUrl = this.scriptUrlBase + encodeURIComponent(normalizedPath);
            
            try {
                const xhr = new XMLHttpRequest();
                xhr.open('GET', scriptUrl, false);
                xhr.send(null);
                
                if (xhr.status === 200) {
                    const content = xhr.responseText;
                    this.scriptCache.set(scriptPath, content);
                    return content;
                }
            } catch (e) {
                console.warn(`加载脚本失败: ${scriptPath}`, e.message);
            }
        }

        return '';
    }

    async loadCardDatabases(SQL) {
        const response = await fetch(this.config.databasesUrl);
        if (!response.ok) {
            throw new Error('获取数据库列表失败');
        }
        
        const data = await response.json();
        const databases = data.databases;
        
        this.cardDatabases = [];
        this.scriptUrlBase = data.script_url || null;
        
        if (!databases || databases.length === 0) {
            console.warn('没有找到卡片数据库');
            return;
        }

        for (let i = 0; i < databases.length; i++) {
            const dbInfo = databases[i];
            const percent = 15 + (i / databases.length) * 20;
            this.setLoadingProgress(Math.round(percent), `加载 ${dbInfo.name}...`);
            
            try {
                const dbResponse = await fetch(dbInfo.url);
                if (!dbResponse.ok) continue;
                
                const dbData = new Uint8Array(await dbResponse.arrayBuffer());
                const db = new SQL.Database(dbData);
                this.cardDatabases.push(db);
                console.log(`已加载数据库: ${dbInfo.name}`);
            } catch (e) {
                console.warn(`加载数据库 ${dbInfo.name} 失败:`, e);
            }
        }
    }

    async parseReplay() {
        this.messages = [];
        
        if (!this.yrpData || this.yrpData.length === 0) {
            throw new Error('录像数据为空');
        }
        
        // 先解析录像头部获取玩家名
        this.parseYrpHeader();
        
        console.log(`开始解析录像，数据大小: ${this.yrpData.length} 字节`);
        
        try {
            const generator = playYrpStep(this.wrapper, this.yrpData);
            
            let stepCount = 0;
            for (const { duel, result } of generator) {
                this.duel = duel;
                this.messages.push({
                    duel: duel,
                    result: result
                });
                stepCount++;
            }
            
            console.log(`解析完成，共 ${stepCount} 条消息`);
        } catch (e) {
            console.error('解析录像错误:', e);
            if (e.message && e.message.includes('Aborted')) {
                // 即使崩溃也保留已解析的消息
                if (this.messages.length === 0) {
                    throw new Error('OCGcore WASM 崩溃，无法解析录像');
                }
            } else if (!e.message || !e.message.includes('MSG_RETRY')) {
                throw e;
            }
        }

        if (this.messages.length === 0) {
            throw new Error('录像中没有有效消息');
        }
    }

    parseYrpHeader() {
        // 优先使用从配置传入的玩家名（来自文件名解析）
        if (this.config.playerNames && this.config.playerNames.length >= 2) {
            this.playerNames = this.config.playerNames;
            console.log('从配置获取玩家名:', this.playerNames);
            return;
        }
        
        try {
            const data = this.yrpData;
            let offset = 0;
            
            // 检查是否为 YRP2
            const isYrp2 = data[0] === 0x87;
            offset = 1;
            
            // 跳过 ID (4 bytes) 和 version (4 bytes)
            offset += 8;
            
            // 读取 flag (4 bytes)
            offset += 4;
            
            // 读取玩家名
            const names = [];
            for (let i = 0; i < 4; i++) {
                if (offset >= data.length) break;
                
                const nameLen = data[offset];
                offset++;
                
                if (nameLen > 0 && offset + nameLen * 2 <= data.length) {
                    const nameBytes = data.slice(offset, offset + nameLen * 2);
                    let name = '';
                    
                    // 直接解析 UTF-16LE
                    for (let j = 0; j < nameBytes.length; j += 2) {
                        const charCode = nameBytes[j] | (nameBytes[j + 1] << 8);
                        if (charCode > 0 && charCode < 0x10000) {
                            name += String.fromCharCode(charCode);
                        }
                    }
                    
                    name = name.trim();
                    if (name && name !== ' ' && !/^\s*$/.test(name)) {
                        names.push(name);
                    }
                    offset += nameLen * 2;
                } else {
                    offset += nameLen * 2;
                }
            }
            
            if (names.length >= 2) {
                this.playerNames = [names[0], names[1]];
                console.log('从YRP头部解析到玩家名:', this.playerNames);
            }
        } catch (e) {
            console.warn('解析YRP头部失败:', e);
        }
    }

    getCardName(code) {
        if (!code) return '???';
        
        if (this.cardCache.has(code)) {
            return this.cardCache.get(code);
        }
        
        // 从数据库查询
        for (const db of this.cardDatabases) {
            try {
                const result = db.exec(`SELECT name FROM texts WHERE id = ${code}`);
                if (result && result.length > 0 && result[0].values && result[0].values.length > 0) {
                    const name = result[0].values[0][0];
                    if (name) {
                        this.cardCache.set(code, name);
                        return name;
                    }
                }
            } catch (e) {
                // 继续尝试下一个数据库
            }
        }
        
        return `#${code}`;
    }

    togglePlay() {
        if (this.isPlaying) {
            this.pause();
        } else {
            this.play();
        }
    }

    play() {
        if (this.currentIndex >= this.messages.length) {
            this.currentIndex = 0;
            this.clearLog();
        }
        
        this.isPlaying = true;
        this.elements.playIcon.textContent = '⏸';
        this.elements.btnPlay.classList.add('active');
        
        this.scheduleNextStep();
    }

    pause() {
        this.isPlaying = false;
        this.elements.playIcon.textContent = '▶';
        this.elements.btnPlay.classList.remove('active');
        
        if (this.playInterval) {
            clearTimeout(this.playInterval);
            this.playInterval = null;
        }
    }

    scheduleNextStep() {
        if (!this.isPlaying) return;
        
        const delay = 1000 / this.playSpeed;
        
        this.playInterval = setTimeout(() => {
            if (this.currentIndex < this.messages.length) {
                this.stepForward();
                this.scheduleNextStep();
            } else {
                this.pause();
                this.logMessage('=== 录像播放完成 ===');
            }
        }, delay);
    }

    stepBack() {
        if (this.currentIndex > 0) {
            this.currentIndex = Math.max(0, this.currentIndex - 20);
            this.replayToCurrent();
            this.updateProgress();
        }
    }

    stepForward() {
        if (this.currentIndex < this.messages.length) {
            this.processMessage(this.messages[this.currentIndex]);
            this.currentIndex++;
            this.updateProgress();
        }
    }

    replayToCurrent() {
        this.clearLog();
        this.playerLP = [8000, 8000];
        this.currentTurn = 0;
        
        for (let i = 0; i < this.currentIndex; i++) {
            const msg = this.messages[i];
            this.updateStateFromMessage(msg);
        }
    }

    updateStateFromMessage(msgData) {
        const { result } = msgData;
        const message = result.message;
        if (!message) return;
        
        const msgType = message.constructor?.name;
        if (msgType === 'YGOProMsgNewTurn') {
            this.currentTurn++;
        } else if (msgType === 'YGOProMsgChangeLp' && message.player !== undefined) {
            this.playerLP[message.player] = message.lp;
        } else if (msgType === 'YGOProMsgDamage' && message.player !== undefined) {
            this.playerLP[message.player] = Math.max(0, (this.playerLP[message.player] || 8000) - (message.value ?? 0));
        } else if (msgType === 'YGOProMsgRecover' && message.player !== undefined) {
            this.playerLP[message.player] = (this.playerLP[message.player] || 8000) + (message.value ?? 0);
        }
    }

    cycleSpeed() {
        this.speedIndex = (this.speedIndex + 1) % this.speedOptions.length;
        this.playSpeed = this.speedOptions[this.speedIndex];
        this.elements.speedText.textContent = `${this.playSpeed}x`;
    }

    seekTo(event) {
        const rect = this.elements.playbackProgress.getBoundingClientRect();
        const percent = Math.max(0, Math.min(1, (event.clientX - rect.left) / rect.width));
        const newIndex = Math.floor(percent * this.messages.length);
        
        this.currentIndex = Math.max(0, Math.min(newIndex, this.messages.length - 1));
        this.replayToCurrent();
        this.updateProgress();
    }

    updateProgress() {
        const percent = this.messages.length > 0 
            ? (this.currentIndex / this.messages.length) * 100 
            : 0;
        this.elements.progressFill.style.width = `${percent}%`;
        this.elements.progressText.textContent = `${this.currentIndex} / ${this.messages.length}`;
    }

    clearLog() {
        this.elements.messageLog.innerHTML = '';
    }

    processMessage(msgData) {
        const { result } = msgData;
        const message = result.message;
        
        if (!message) return;

        const msgType = message.constructor?.name || 'Unknown';
        
        // 调试：输出前10条消息的详细结构
        if (this.currentIndex < 10) {
            console.log(`消息 #${this.currentIndex} [${msgType}]:`, message);
        }
        
        switch (msgType) {
            case 'YGOProMsgNewTurn':
                this.handleNewTurn(message);
                break;
            case 'YGOProMsgNewPhase':
                this.handleNewPhase(message);
                break;
            case 'YGOProMsgDraw':
                this.handleDraw(message);
                break;
            case 'YGOProMsgChangeLp':
                this.handleChangeLp(message);
                break;
            case 'YGOProMsgPayLpCost':
                this.handlePayLpCost(message);
                break;
            case 'YGOProMsgDamage':
                this.handleDamage(message);
                break;
            case 'YGOProMsgRecover':
                this.handleRecover(message);
                break;
            case 'YGOProMsgSummoning':
            case 'YGOProMsgSpSummoning':
                this.handleSummoning(message);
                break;
            case 'YGOProMsgChaining':
                this.handleChaining(message);
                break;
            case 'YGOProMsgChainEnd':
                this.handleChainEnd(message);
                break;
            case 'YGOProMsgMove':
                this.handleMove(message);
                break;
            case 'YGOProMsgAttack':
                this.handleAttack(message);
                break;
            case 'YGOProMsgBattle':
                this.handleBattle(message);
                break;
            case 'YGOProMsgHint':
            case 'YGOProMsgCardHint':
            case 'YGOProMsgPlayerHint':
                this.handleHint(message);
                break;
            case 'YGOProMsgSelectCard':
            case 'YGOProMsgSelectChain':
            case 'YGOProMsgSelectPlace':
            case 'YGOProMsgSelectPosition':
            case 'YGOProMsgSelectBattleCmd':
            case 'YGOProMsgSelectIdleCmd':
            case 'YGOProMsgSelectYesNo':
            case 'YGOProMsgSelectOption':
            case 'YGOProMsgSelectEffectYn':
                break;
            default:
                break;
        }
    }

    getPlayerName(player) {
        if (player === undefined || player === null || isNaN(player)) {
            return '玩家?';
        }
        const idx = Math.max(0, Math.min(1, player));
        return (this.playerNames && this.playerNames[idx]) || `玩家${player}`;
    }

    handleNewTurn(message) {
        this.currentTurn++;
        const player = message.player ?? 0;
        const playerName = this.getPlayerName(player);
        this.logMessage(`<div class="log-turn">=== 回合 ${this.currentTurn} - ${playerName} ===</div>`);
    }

    handleNewPhase(message) {
        const phase = message.phase ?? 0;
        const phaseName = PHASE_NAMES[phase] || `阶段(${phase})`;
        this.logMessage(`<div class="log-phase">【${phaseName}】</div>`);
    }

    handleDraw(message) {
        const player = message.player ?? 0;
        const count = message.count ?? 0;
        const cards = message.cards ?? [];
        const playerName = this.getPlayerName(player);
        
        if (count > 0 && cards.length > 0) {
            const cardNames = cards.slice(0, count).map(code => this.getCardName(code));
            this.logMessage(`<div class="log-draw">${playerName} 抽了 ${count} 张卡: <span class="card-name">${cardNames.join(', ')}</span></div>`);
        } else if (count > 0) {
            this.logMessage(`<div class="log-draw">${playerName} 抽了 ${count} 张卡</div>`);
        }
    }

    handleChangeLp(message) {
        const player = message.player ?? message.Player ?? 0;
        const oldLP = this.playerLP[player] || 8000;
        const newLP = message.lp ?? message.LP ?? message.raw?.[1] ?? oldLP;
        const diff = newLP - oldLP;
        
        this.playerLP[player] = newLP;
        
        const playerName = this.getPlayerName(player);
        const diffText = diff >= 0 ? `+${diff}` : `${diff}`;
        this.logMessage(`<div class="log-lp">${playerName} LP: ${newLP} (${diffText})</div>`);
    }

    handlePayLpCost(message) {
        const player = message.player ?? message.Player ?? 0;
        const cost = message.cost ?? message.Cost ?? message.raw?.[1] ?? '?';
        const playerName = this.getPlayerName(player);
        this.logMessage(`<div class="log-cost">${playerName} 支付 ${cost} LP 作为代价</div>`);
    }

    handleDamage(message) {
        const player = message.player ?? 0;
        const value = message.value ?? 0;
        const playerName = this.getPlayerName(player);
        this.playerLP[player] = Math.max(0, (this.playerLP[player] || 8000) - value);
        this.logMessage(`<div class="log-damage">${playerName} 受到 ${value} 点伤害 (LP: ${this.playerLP[player]})</div>`);
    }

    handleRecover(message) {
        const player = message.player ?? 0;
        const value = message.value ?? 0;
        const playerName = this.getPlayerName(player);
        this.playerLP[player] = (this.playerLP[player] || 8000) + value;
        this.logMessage(`<div class="log-recover">${playerName} 回复 ${value} LP (LP: ${this.playerLP[player]})</div>`);
    }

    handleSummoning(message) {
        const code = message.code ?? 0;
        const player = message.controller ?? 0;
        const cardName = this.getCardName(code);
        const playerName = this.getPlayerName(player);
        this.logMessage(`<div class="log-summon">${playerName} 召唤: <span class="card-name">${cardName}</span></div>`);
    }

    handleChaining(message) {
        const code = message.code ?? 0;
        const player = message.controller ?? 0;
        const cardName = this.getCardName(code);
        const playerName = this.getPlayerName(player);
        const chainCount = message.chainCount ?? 0;
        this.logMessage(`<div class="log-chain">[连锁 ${chainCount}] ${playerName} 发动: <span class="card-name">${cardName}</span></div>`);
    }

    handleChainEnd(message) {
        this.logMessage(`<div class="log-chain-end">连锁结束</div>`);
    }

    handleMove(message) {
        // 移动消息，可选记录
    }

    handleAttack(message) {
        const attacker = message.attacker;
        const defender = message.defender;
        
        if (!attacker) return;
        
        const attackerPlayer = attacker.controller ?? 0;
        const attackerLocation = LOCATION_NAMES[attacker.location] ?? '未知区域';
        const attackerSeq = attacker.sequence ?? 0;
        
        const playerName = this.getPlayerName(attackerPlayer);
        
        if (defender && defender.location > 0) {
            const defPlayer = defender.controller ?? 0;
            const defLocation = LOCATION_NAMES[defender.location] ?? '未知区域';
            const defSeq = defender.sequence ?? 0;
            const defPlayerName = this.getPlayerName(defPlayer);
            
            if (defender.location === LOCATION.MZONE) {
                this.logMessage(`<div class="log-attack">${playerName} 的 [${attackerLocation}${attackerSeq + 1}] 攻击 ${defPlayerName} 的 [${defLocation}${defSeq + 1}]</div>`);
            } else {
                this.logMessage(`<div class="log-attack">${playerName} 的 [${attackerLocation}${attackerSeq + 1}] 直接攻击 ${defPlayerName}</div>`);
            }
        } else {
            const targetPlayer = this.getPlayerName(1 - attackerPlayer);
            this.logMessage(`<div class="log-attack">${playerName} 的 [${attackerLocation}${attackerSeq + 1}] 直接攻击 ${targetPlayer}</div>`);
        }
    }

    handleBattle(message) {
        // 战斗结果
    }

    handleHint(message) {
        // 提示消息，通常不记录
    }

    logMessage(html) {
        const log = this.elements.messageLog;
        const entry = document.createElement('div');
        entry.className = 'log-entry';
        entry.innerHTML = html;
        log.appendChild(entry);
        log.scrollTop = log.scrollHeight;
        
        // 保持最多500条记录
        while (log.children.length > 500) {
            log.removeChild(log.firstChild);
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    if (window.RAMSAY_REPLAY_CONFIG) {
        new ReplayPlayer();
    }
});
