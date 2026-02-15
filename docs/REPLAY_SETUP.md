# 录像回放功能设置指南

## 功能概述

录像回放功能允许在网页中播放 YGOPro 的 YRP/YRP2 录像文件，支持：
- 多 CDB 数据库加载（DIY + TCG）
- 双卡图路径支持
- 脚本文件加载（支持卡片效果）
- 逐步播放/暂停/快进
- 游戏状态可视化（LP、场上卡片、手牌等）

## 安装步骤

### 1. 安装 Node.js 依赖

```bash
cd /path/to/n81ramsay
npm install
```

安装完成后会自动将 `sql-wasm.wasm` 复制到 `assets/` 目录。

### 2. 构建前端资源

```bash
npm run build
```

这将使用 Vite 构建 `assets/js/replay-player.js` 并生成 `assets/js/replay-player.bundle.js`。

### 3. 配置

在 `config.user.php` 中添加：
```php
<?php
// 启用录像功能
define('REPLAY_ENABLED', true);

// 录像文件目录 - 指向 YGOPro 服务器的 replay 目录
define('REPLAY_PATH', '/path/to/ygopro/replay');

// TCG 卡图路径（可选）
define('TCG_CARD_IMAGE_PATH', '/path/to/ygopro/pics');

// TCG 脚本路径（录像回放需要）
// DIY 卡脚本会自动从 CARD_DATA_PATH/script 加载
// TCG 卡脚本需要单独配置
define('TCG_SCRIPT_PATH', '/path/to/ygopro/script');
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
│   │   ├── replay-player.js        # 前端播放器源码
│   │   ├── replay-player.bundle.js # 构建后的播放器（需要提交）
│   │   └── shims/                  # Node.js polyfills
│   └── images/
│       └── card_back.jpg           # 默认卡背
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
| `?controller=replay&action=databases` | GET | 获取 CDB 数据库和脚本 URL |
| `?controller=replay&action=database&name={name}` | GET | 下载单个 CDB 文件 |
| `?controller=replay&action=script&path={path}` | GET | 获取脚本文件 |
| `?controller=replay&action=cardimage&id={id}` | GET | 获取卡图 |

## 数据库加载说明

系统会自动加载以下数据库：
1. `CARD_DATA_PATH` 下的所有 `.cdb` 文件（DIY 卡）
2. `TCG_CARD_DATA_PATH` 指定的 TCG 卡片数据库

在 `config.php` 或 `config.user.php` 中配置：
```php
define('CARD_DATA_PATH', '/path/to/expansions');  // DIY 卡目录
define('TCG_CARD_DATA_PATH', '/path/to/cards.cdb'); // TCG 数据库
```

## 脚本加载说明

录像回放需要加载卡片效果脚本才能正确执行卡片效果。

### DIY 卡脚本
自动从 `CARD_DATA_PATH/script/` 目录加载，无需额外配置。

### TCG 卡脚本
需要在 `config.user.php` 中配置：
```php
define('TCG_SCRIPT_PATH', '/path/to/ygopro/script');
```

## 卡图路径配置

系统自动按以下顺序查找卡图：
1. `CARD_DATA_PATH/pics/{id}.jpg` - DIY 卡图
2. `CARD_DATA_PATH/pics/thumbnail/{id}.jpg` - DIY 缩略图
3. `TCG_CARD_IMAGE_PATH/{id}.jpg` - TCG 卡图

```php
define('TCG_CARD_IMAGE_PATH', '/path/to/pics');
```

## 故障排除

### WASM 崩溃 (Aborted)
- 检查脚本路径是否正确配置
- 确保脚本文件可被 Web 服务器读取
- 查看浏览器控制台中的脚本加载错误

### 录像解析失败
- 检查录像文件是否完整
- 查看浏览器控制台错误信息
- 某些录像可能包含不支持的卡片效果

### 卡图无法显示
- 检查 `CARD_DATA_PATH/pics/` 目录权限
- 检查 `TCG_CARD_IMAGE_PATH` 配置
- 确认卡图文件名与卡片 ID 匹配

### 构建失败
- 确保 Node.js 版本 >= 16
- 运行 `npm install` 重新安装依赖
- 删除 `node_modules` 目录后重新安装

## 开发说明

### 修改前端代码
1. 编辑 `assets/js/replay-player.js`
2. 运行 `npm run build` 重新构建
3. 提交 `assets/js/replay-player.bundle.js` 文件

### 开发调试
运行 `npm run dev` 启动 Vite 开发服务器。

### 添加新的消息处理
在 `ReplayPlayer` 类中添加对应的 `handle*` 方法。

### 自定义样式
在视图文件 `includes/Views/replays/player.php` 中修改 `<style>` 部分。
