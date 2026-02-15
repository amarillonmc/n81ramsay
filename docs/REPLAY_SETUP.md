# 录像回放功能设置指南

## 功能概述

录像回放功能允许在网页中播放 YGOPro 的 YRP/YRP2 录像文件，支持：
- 多 CDB 数据库加载（DIY + TCG）
- 双卡图路径支持
- 逐步播放/暂停/快进
- 游戏状态可视化（LP、场上卡片、手牌等）

## 安装步骤

### 1. 安装 Node.js 依赖

```bash
cd /path/to/n81ramsay
npm install
```

### 2. 复制 SQL.js WASM 文件

```bash
cp node_modules/sql.js/dist/sql-wasm.wasm assets/
```

### 3. 构建前端资源（可选）

如果需要开发调试：
```bash
npm run dev
```

生产环境可以直接使用源文件，Vite 开发服务器会自动处理模块加载。

### 4. 配置录像目录

在 `config.user.php` 中添加：
```php
<?php
// 录像文件目录 - 指向 YGOPro 服务器的 replay 目录
define('REPLAY_PATH', '/path/to/ygopro/replay');

// 启用录像功能
define('REPLAY_ENABLED', true);
```

## 文件结构

```
n81ramsay/
├── includes/
│   ├── Controllers/
│   │   └── ReplayController.php    # 录像控制器
│   ├── Models/
│   │   └── Replay.php              # 录像模型
│   └── Views/
│       └── replays/
│           ├── index.php           # 录像列表页
│           └── player.php          # 播放器页
├── assets/
│   ├── js/
│   │   └── replay-player.js        # 前端播放器
│   ├── sql-wasm.wasm               # SQL.js WASM 文件
│   └── images/
│       └── card_back.jpg           # 默认卡背
├── node_modules/
│   ├── koishipro-core.js/          # OCGcore WASM 封装
│   └── sql.js/                     # SQLite WASM
├── package.json
├── vite.config.js
└── config.php                      # 配置文件
```

## API 端点

| 端点 | 方法 | 描述 |
|------|------|------|
| `?controller=replay` | GET | 录像列表页面 |
| `?controller=replay&action=play&file={filename}` | GET | 播放器页面 |
| `?controller=replay&action=list` | GET | JSON API: 录像列表 |
| `?controller=replay&action=file&file={filename}` | GET | 下载 YRP 文件 |
| `?controller=replay&action=databases` | GET | 获取 CDB 数据库列表 |
| `?controller=replay&action=database&name={name}` | GET | 下载单个 CDB 文件 |
| `?controller=replay&action=cardimage&type={type}&id={id}` | GET | 获取卡图 |

## 多 CDB 加载说明

系统会自动加载以下数据库：
1. `CARD_DATA_PATH` 下的所有 `.cdb` 文件（DIY 卡）
2. `TCG_CARD_DATA_PATH` 指定的 TCG 卡片数据库

在 `config.php` 或 `config.user.php` 中配置：
```php
define('CARD_DATA_PATH', '/path/to/expansions');  // DIY 卡目录
define('TCG_CARD_DATA_PATH', '/path/to/cards.cdb'); // TCG 数据库
```

## 卡图路径配置

```php
// DIY 卡图路径（自动从 CARD_DATA_PATH/pics/ 获取）
// TCG 卡图路径
define('TCG_CARD_IMAGE_PATH', '/path/to/pics');
```

## 故障排除

### WASM 加载失败
- 确保 `assets/sql-wasm.wasm` 文件存在
- 检查 Web 服务器 MIME 类型配置

### 录像解析失败
- 检查录像文件是否完整
- 查看浏览器控制台错误信息
- 某些录像可能包含不支持的卡片效果

### 卡图无法显示
- 检查 `CARD_DATA_PATH/pics/` 目录权限
- 检查 `TCG_CARD_IMAGE_PATH` 配置
- 确认卡图文件名与卡片 ID 匹配

## 开发说明

### 修改前端代码
编辑 `assets/js/replay-player.js`，然后在开发模式下运行 `npm run dev`。

### 添加新的消息处理
在 `ReplayPlayer` 类中添加对应的 `handle*` 方法。

### 自定义样式
在视图文件 `includes/Views/replays/player.php` 中修改 `$extraCss` 变量。
