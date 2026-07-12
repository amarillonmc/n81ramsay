# RAMSAY - no81游戏王DIY服务器管理系统

[![Ask DeepWiki](https://deepwiki.com/badge.svg)](https://deepwiki.com/amarillonmc/n81ramsay)

RAMSAY是一个专为no81游戏王DIY服务器设计的管理系统，旨在自动化和简化服务器的各种运营事务。该系统提供卡片检索、投票禁卡、禁卡表整理、作者光荣榜、卡片排行榜等功能，帮助服务器管理员和玩家更高效地管理游戏环境。

## 主要功能

### 卡片检索
- 支持按卡片ID或卡名搜索
- 显示卡片详细信息和卡图
- 查看卡片在各环境中的禁限状态
- 支持分页显示，提高大量卡片的浏览效率

### 投票禁卡
- 用户可以发起对特定卡片的禁限投票
- 支持普通投票、系列投票和**高级投票**三种模式
- 生成唯一投票链接供其他玩家参与
- 支持多种禁限状态（禁止、限制、准限制、无限制）
- 同一IP只能投一次票

#### 高级投票功能（新增）
- **多卡片投票**：支持对多张卡片同时发起投票
- **分别投票**：可以对每张卡片选择不同的投票状态
- **直观展示**：卡片网格布局，悬浮预览功能
- **批量操作**：一键设置所有卡片相同状态
- **性能优化**：批量查询，减少数据库访问

### 投票概览
- 显示所有有效投票链接
- 展示投票周期、卡片信息和当前投票状态
- 已关闭的投票以灰色显示

### 禁卡表管理
- 管理员可以查看和管理所有投票
- 统计投票结果，生成禁卡表文本
- 支持生成lflist.conf格式的禁卡表
- 重置投票并推进投票周期

### 作者光荣榜
- 展示所有卡片作者的统计数据
- 按投稿卡片数量从高到低排序
- 显示每位作者的禁卡比例和被禁系列数量
- 高亮显示禁卡比例较高的作者
- 自动识别卡片作者，支持多种作者信息格式

### 作者管理
- 管理员可以通过"识别作者"按钮自动识别并导入作者信息
- 支持手动添加、编辑和删除作者信息
- 可以编辑卡片前缀、作者名称、别名、联系方式和其他备注信息
- 支持修改被错误识别的卡片前缀
- 作者识别优先级：数据库记录 > 卡片描述文本 > strings.conf文件

### 卡片排行榜
- 从srvpro2 PostgreSQL读取双方初始卡组，按每名玩家每场比赛去重统计
- 支持按时间范围筛选：一周内、两周内、一个月内、全部
- 热门卡片展示：支持显示前3名/前7名/前10名
- 详细统计展示：支持显示前10名/前30名/前50名/全部
- 显示卡片ID、卡名、类别、使用情况和使用率
- 支持TCG卡片和DIY卡片的区分处理
- 管理员可以强制更新统计信息

### 录像回放
- 分页浏览srvpro2 PostgreSQL中的云录像记录
- 通过srvpro2 API动态生成YRP2，无需服务器落地录像文件
- 在网页中播放或下载动态生成的YRP录像
- 自动加载多个CDB数据库（DIY卡 + TCG卡）
- 支持双卡图路径（DIY卡图 + TCG卡图）
- 逐步播放/暂停/快进/调速
- 实时显示游戏状态（LP、场上卡片、手牌等）

## 技术实现

- 基于PHP开发，无需额外框架
- 使用SQLite数据库存储卡片和投票数据
- 使用只读PDO PostgreSQL连接读取srvpro2录像与卡组数据
- 响应式设计，适配各种设备
- 支持在IIS服务器上部署
- 录像回放功能使用koishipro-core.js（OCGcore WASM封装）

## 环境要求

### 基础要求
- PHP 7.0+
- PDO SQLite、PDO PostgreSQL、cURL、mbstring
- IIS服务器或其他支持PHP的Web服务器
- 可访问srvpro2 PostgreSQL与Legacy HTTP API

### 仅重新构建录像播放器时需要
- Node.js 16+
- npm

## 部署说明

1. 将项目文件上传到Web服务器
2. 启用 `pdo_sqlite`、`pdo_pgsql`、`curl`、`mbstring`，并确认 cURL/libpq 的 TLS 运行库可用
3. 配置config.php文件中的相关设置
   - 可以创建config.user.php文件覆盖默认配置
   - 当config.php和config.user.php同时存在相同的配置时，config.user.php中的配置值优先
4. 访问网站根目录即可使用系统

### 启用srvpro2录像与排行榜

1. 在 `config.user.php` 中配置srvpro2 API与只读PostgreSQL账号：
   ```php
   <?php
   define('SRVPRO2_INTEGRATION_ENABLED', true);
   define('SRVPRO2_API_BASE_URL', 'http://127.0.0.1:7922');
   define('SRVPRO2_API_USERNAME', 'ramsay');
   define('SRVPRO2_API_PASSWORD', '替换为API密码');
   define('SRVPRO2_DB_HOST', '127.0.0.1');
   define('SRVPRO2_DB_NAME', 'srvpro2');
   define('SRVPRO2_DB_USER', 'ramsay_reader');
   define('SRVPRO2_DB_PASSWORD', '替换为数据库密码');
   define('SRVPRO2_WINDBOT_BOTLIST_PATH', 'C:\\srvpro2\\windbot\\bots.json');
   define('REPLAY_ENABLED', true);
   define('CARD_RANKING_ENABLED', true);
   ```

2. 在srvpro2中启用数据库、API端口与云录像，并创建只有
   `download_replay`、`duel_log` 权限的API用户。

3. 为RAMSAY创建只对 `duel_record`、`duel_record_player` 必需列有 `SELECT`
   权限的PostgreSQL账号。

4. 访问 `?controller=replay` 与 `?controller=card_ranking` 验证功能。

完整的IIS扩展、权限、SQL与回滚说明请参考
[docs/SRVPRO2_SETUP.md](docs/SRVPRO2_SETUP.md)。旧srvpro文件模式说明仍见
[docs/REPLAY_SETUP.md](docs/REPLAY_SETUP.md)。

## 目录结构

```
/
├── index.php               # 入口文件
├── config.php              # 主配置文件
├── config.user.php         # 用户自定义配置文件（可选）
├── package.json            # Node.js 依赖配置
├── vite.config.js          # Vite 构建配置
├── assets/                 # 静态资源
│   ├── css/                # 样式文件
│   ├── js/                 # JavaScript文件
│   │   ├── replay-player.js          # 录像播放器源码
│   │   └── replay-player.bundle.js   # 构建后的播放器（npm run build后生成）
│   ├── sql-wasm.wasm       # SQL.js WASM文件（npm install后生成）
│   └── images/             # 图片资源
├── includes/               # 包含文件目录
│   ├── Core/               # 核心功能
│   ├── Models/             # 数据模型
│   ├── Controllers/        # 控制器
│   └── Views/              # 视图模板
├── data/                   # 数据存储目录
│   └── cache/              # 缓存目录
├── deck_log/               # 旧srvpro卡组文件目录（回滚模式）
├── docs/                   # 文档目录
│   ├── SRVPRO2_SETUP.md    # srvpro2接入与IIS部署说明
│   └── REPLAY_SETUP.md     # 旧文件录像模式说明
└── node_modules/           # Node.js 依赖（不提交到仓库）
```

## 许可证

请参照[LICENSE](LICENSE)文件。
