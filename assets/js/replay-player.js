/**
 * RAMSAY 录像播放器
 * 使用 koishipro-core.js 实现 YRP/YRP2 回放功能
 * 
 * 依赖通过 importmap 在 HTML 中配置
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

const QUERY = {
    CODE: 0x1,
    POSITION: 0x2,
    ALIAS: 0x4,
    TYPE: 0x8,
    LEVEL: 0x10,
    RANK: 0x20,
    ATTRIBUTE: 0x40,
    RACE: 0x80,
    ATTACK: 0x100,
    DEFENSE: 0x200,
    BASE_ATTACK: 0x400,
    BASE_DEFENSE: 0x800,
    REASON: 0x1000,
    REASON_CARD: 0x4000,
    EQUIP_CARD: 0x8000,
    TARGET_CARD: 0x10000,
    OVERLAY_CARD: 0x20000,
    COUNTERS: 0x40000,
    OWNER: 0x80000,
    STATUS: 0x100000,
    LSCALE: 0x200000,
    RSCALE: 0x400000,
    LINK: 0x800000
};

const POS = {
    FACEUP_ATTACK: 0x1,
    FACEDOWN_ATTACK: 0x2,
    FACEUP_DEFENSE: 0x4,
    FACEDOWN_DEFENSE: 0x8,
    FACEUP: 0x5,
    FACEDOWN: 0xA,
    ATTACK: 0x3,
    DEFENSE: 0xC
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
        this.speedOptions = [0.5, 1, 2, 5, 10];
        this.speedIndex = 1;
        this.cardDatabase = null;
        this.cardCache = new Map();
        this.duel = null;
        this.fieldInfo = null;
        
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
            messageLog: document.getElementById('messageLog'),
            player0Name: document.getElementById('player0Name'),
            player1Name: document.getElementById('player1Name'),
            player0Lp: document.getElementById('player0Lp'),
            player1Lp: document.getElementById('player1Lp')
        };

        if (this.config.playerNames && this.config.playerNames.length >= 2) {
            this.elements.player0Name.textContent = this.config.playerNames[0] || '玩家 0';
            this.elements.player1Name.textContent = this.config.playerNames[1] || '玩家 1';
        }

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
            
            const SQL = await initSqlJs({
                locateFile: file => `https://cdn.jsdelivr.net/npm/sql.js@1.13.0/dist/sql-wasm.wasm`
            });
            
            this.setLoadingProgress(15, '加载卡片数据库...');
            await this.loadCardDatabases(SQL);
            
            this.setLoadingProgress(35, '下载 OCGcore WASM...');
            const wasmResponse = await fetch('https://cdn.jsdelivr.net/npm/koishipro-core.js@1.3.4/dist/vendor/wasm_esm/libocgcore.wasm');
            if (!wasmResponse.ok) {
                throw new Error(`下载 OCGcore WASM 失败: ${wasmResponse.status}`);
            }
            const wasmBinary = new Uint8Array(await wasmResponse.arrayBuffer());
            
            this.setLoadingProgress(45, '初始化 OCGcore...');
            this.wrapper = await createOcgcoreWrapper({
                scriptBufferSize: 0x200000,
                logBufferSize: 2048,
                wasmBinary: wasmBinary
            });

            this.setLoadingProgress(55, '配置卡片读取器...');
            this.wrapper.setCardReader(SqljsCardReader(this.cardDatabase));

            // 设置空的脚本读取器，避免 fs 模块调用
            // 录像回放不需要实际的脚本执行
            this.setLoadingProgress(60, '配置脚本读取器...');
            const emptyScriptReader = () => null;
            this.wrapper.setScriptReader(emptyScriptReader);

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
            }, 300);

        } catch (error) {
            console.error('加载失败:', error);
            this.showError(`加载失败: ${error.message}`);
        }
    }

    getBaseUrl() {
        let basePath = window.location.pathname;
        const lastSlash = basePath.lastIndexOf('/');
        if (lastSlash > 0) {
            basePath = basePath.substring(0, lastSlash + 1);
        }
        if (!basePath.endsWith('/')) {
            basePath += '/';
        }
        return basePath;
    }

    async loadCardDatabases(SQL) {
        const response = await fetch(this.config.databasesUrl);
        if (!response.ok) {
            throw new Error('获取数据库列表失败');
        }
        
        const data = await response.json();
        const databases = data.databases;
        
        this.cardDatabase = null;
        this.imageUrlBase = data.image_url;
        
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
                if (!dbResponse.ok) {
                    console.warn(`加载数据库 ${dbInfo.name} 失败: HTTP ${dbResponse.status}`);
                    continue;
                }
                
                const dbData = new Uint8Array(await dbResponse.arrayBuffer());
                const db = new SQL.Database(dbData);
                
                if (this.cardDatabase === null) {
                    this.cardDatabase = db;
                } else {
                    await this.mergeDatabases(this.cardDatabase, db);
                }
            } catch (e) {
                console.warn(`加载数据库 ${dbInfo.name} 失败:`, e);
            }
        }
    }

    async mergeDatabases(mainDb, additionalDb) {
        try {
            const tables = additionalDb.exec(
                "SELECT name FROM sqlite_master WHERE type='table'"
            );
            
            if (tables.length === 0) return;
            
            for (const table of tables[0].values) {
                const tableName = table[0];
                if (tableName === 'datas' || tableName === 'texts') {
                    const rows = additionalDb.exec(`SELECT * FROM ${tableName}`);
                    if (rows.length > 0) {
                        const columns = rows[0].columns;
                        const placeholders = columns.map(() => '?').join(',');
                        const insertSql = `INSERT OR IGNORE INTO ${tableName} (${columns.join(',')}) VALUES (${placeholders})`;
                        
                        for (const row of rows[0].values) {
                            try {
                                mainDb.run(insertSql, row);
                            } catch (e) {
                                // Ignore duplicate key errors
                            }
                        }
                    }
                }
            }
        } catch (e) {
            console.warn('合并数据库失败:', e);
        }
    }

    async parseReplay() {
        this.messages = [];
        
        try {
            const generator = playYrpStep(this.wrapper, this.yrpData);
            
            for (const { duel, result } of generator) {
                this.duel = duel;
                this.messages.push({
                    duel: duel,
                    result: result
                });
            }
        } catch (e) {
            console.error('解析录像错误:', e);
            if (e.message && e.message.includes('MSG_RETRY')) {
                console.warn('录像包含 MSG_RETRY，可能不完整');
            } else {
                throw e;
            }
        }

        if (this.messages.length === 0) {
            throw new Error('录像中没有有效消息');
        }
        
        this.logMessage(`解析完成，共 ${this.messages.length} 条消息`);
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
                this.logMessage('播放完成');
            }
        }, delay);
    }

    stepBack() {
        if (this.currentIndex > 0) {
            this.currentIndex = Math.max(0, this.currentIndex - 10);
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
        this.clearField();
        
        for (let i = 0; i < this.currentIndex; i++) {
            this.processMessage(this.messages[i]);
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

    processMessage(msgData) {
        const { duel, result } = msgData;
        const message = result.message;
        
        if (!message) return;

        const msgType = message.constructor ? message.constructor.name : 'Unknown';
        
        switch (msgType) {
            case 'YGOProMsgNewTurn':
                this.handleNewTurn(message);
                break;
            case 'YGOProMsgNewPhase':
                this.handleNewPhase(message);
                break;
            case 'YGOProMsgFieldFinish':
                this.updateFieldInfo(duel);
                break;
            case 'YGOProMsgChangeLp':
                this.handleChangeLp(message);
                this.updateFieldInfo(duel);
                break;
            case 'YGOProMsgPayLpCost':
            case 'YGOProMsgDamage':
            case 'YGOProMsgRecover':
                this.updateFieldInfo(duel);
                break;
            case 'YGOProMsgSummoning':
            case 'YGOProMsgSpSummoning':
                this.handleSummoning(message);
                this.updateFieldInfo(duel);
                break;
            case 'YGOProMsgMove':
            case 'YGOProMsgSet':
            case 'YGOProMsgSwap':
                this.updateFieldInfo(duel);
                break;
            default:
                this.updateFieldInfo(duel);
                break;
        }
    }

    handleNewTurn(message) {
        this.logMessage(`=== 回合 ${message.turn} - 玩家 ${message.player} ===`);
    }

    handleNewPhase(message) {
        const phases = {
            0x01: '抽卡阶段',
            0x02: '准备阶段',
            0x04: '主要阶段1',
            0x08: '战斗阶段',
            0x10: '伤害步骤',
            0x20: '伤害计算',
            0x40: '战斗阶段结束',
            0x80: '主要阶段2',
            0x100: '结束阶段'
        };
        const phaseName = phases[message.phase] || `阶段(${message.phase})`;
        this.logMessage(`进入 ${phaseName}`);
    }

    handleChangeLp(message) {
        const player = message.player;
        const lpElement = player === 0 ? this.elements.player0Lp : this.elements.player1Lp;
        lpElement.textContent = message.lp;
        this.logMessage(`玩家 ${player} LP: ${message.lp}`);
    }

    handleSummoning(message) {
        const cardName = this.getCardName(message.code);
        this.logMessage(`召唤: ${cardName} (${message.code})`);
    }

    updateFieldInfo(duel) {
        try {
            const fieldInfo = duel.queryFieldInfo();
            if (!fieldInfo || !fieldInfo.field) return;

            const field = fieldInfo.field;
            
            if (field.players && field.players.length >= 2) {
                this.elements.player0Lp.textContent = field.players[0].lp;
                this.elements.player1Lp.textContent = field.players[1].lp;
            }

            this.updateZones(duel, field);
            
        } catch (e) {
            // 静默处理查询错误
        }
    }

    updateZones(duel, field) {
        this.clearFieldCards();

        for (let player = 0; player < 2; player++) {
            for (let seq = 0; seq < 5; seq++) {
                this.updateZoneCard(duel, player, LOCATION.SZONE, seq, `szone${player}-${seq}`);
            }
            
            for (let seq = 0; seq < 5; seq++) {
                this.updateZoneCard(duel, player, LOCATION.MZONE, seq, `mzone${player}-${seq}`);
            }

            this.updateZoneCount(player, LOCATION.DECK, `deck${player}Count`, duel);
            this.updateZoneCount(player, LOCATION.GRAVE, `grave${player}Count`, duel);
            this.updateZoneCount(player, LOCATION.EXTRA, `extra${player}Count`, duel);
            
            this.updateHandCards(duel, player);
        }
    }

    updateZoneCard(duel, player, location, sequence, zoneId) {
        try {
            const queryFlag = QUERY.CODE | QUERY.POSITION;
            const result = duel.queryCard({
                player: player,
                location: location,
                sequence: sequence,
                queryFlag: queryFlag
            });

            if (result && result.card && result.card.code) {
                const zone = document.querySelector(`[data-zone="${zoneId}"]`);
                if (zone) {
                    const card = result.card;
                    const isFaceDown = (card.position & POS.FACEUP) === 0;
                    
                    const img = document.createElement('img');
                    img.className = 'card-image';
                    img.dataset.code = card.code;
                    img.dataset.zone = zoneId;
                    
                    if (isFaceDown) {
                        img.src = this.getCardBackImage();
                        img.style.opacity = '0.7';
                    } else {
                        img.src = this.getCardImageUrl(card.code);
                    }
                    
                    zone.innerHTML = '';
                    zone.appendChild(img);
                }
            }
        } catch (e) {
            // 区域为空
        }
    }

    updateZoneCount(player, location, elementId, duel) {
        try {
            const count = duel.queryFieldCount({
                player: player,
                location: location
            });
            
            const element = document.getElementById(elementId);
            if (element) {
                element.textContent = count;
            }
        } catch (e) {
            // 忽略错误
        }
    }

    updateHandCards(duel, player) {
        const container = document.getElementById(`hand${player}Cards`);
        if (!container) return;
        
        container.innerHTML = '';
        
        try {
            const queryFlag = QUERY.CODE;
            const result = duel.queryFieldCard({
                player: player,
                location: LOCATION.HAND,
                queryFlag: queryFlag
            });

            if (result && result.cards) {
                for (const card of result.cards) {
                    if (card.code) {
                        const img = document.createElement('img');
                        img.className = 'hand-card';
                        img.src = this.getCardImageUrl(card.code);
                        img.dataset.code = card.code;
                        container.appendChild(img);
                    }
                }
            }
        } catch (e) {
            // 手牌为空
        }
    }

    clearField() {
        this.clearFieldCards();
        
        this.elements.player0Lp.textContent = '8000';
        this.elements.player1Lp.textContent = '8000';
        
        for (let player = 0; player < 2; player++) {
            ['deck', 'grave', 'extra'].forEach(zone => {
                const element = document.getElementById(`${zone}${player}Count`);
                if (element) element.textContent = '0';
            });
            
            const handContainer = document.getElementById(`hand${player}Cards`);
            if (handContainer) handContainer.innerHTML = '';
        }
    }

    clearFieldCards() {
        document.querySelectorAll('.mzone, .szone').forEach(zone => {
            zone.innerHTML = '';
        });
    }

    getCardImageUrl(code) {
        return this.imageUrlBase + code;
    }

    getCardBackImage() {
        return `${this.getBaseUrl()}assets/images/card_back.jpg`;
    }

    getCardName(code) {
        if (this.cardCache.has(code)) {
            return this.cardCache.get(code).name;
        }
        return `#${code}`;
    }

    logMessage(text) {
        const log = this.elements.messageLog;
        const entry = document.createElement('div');
        entry.className = 'log-entry';
        entry.textContent = text;
        log.appendChild(entry);
        log.scrollTop = log.scrollHeight;
        
        while (log.children.length > 100) {
            log.removeChild(log.firstChild);
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    if (window.RAMSAY_REPLAY_CONFIG) {
        try {
            new ReplayPlayer();
        } catch (e) {
            console.error('初始化播放器失败:', e);
        }
    }
});
