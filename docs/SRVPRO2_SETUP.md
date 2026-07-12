# srvpro2 录像与卡片排行榜接入指南

本文说明 RAMSAY 如何接入 srvpro2 的云录像与实战卡组数据。实现依据为
srvpro2 `master` 提交
[`e91df3c3`](https://github.com/purerosefallen/srvpro2/tree/e91df3c3addecfb5ba1bafda1abe8c1662823bdd)。

## 数据流

- 录像列表与详情：RAMSAY 使用只读 PDO 连接分页查询 PostgreSQL
  `duel_record`、`duel_record_player`；对无法由持久化比分确认已经整场结束的房间，
  再用 `/api/duellog` 的 `cloud_replay_id` 精确排除活动 Match 前局。
- 录像播放与下载：RAMSAY 服务端请求 srvpro2
  `/api/replay/{id}.yrp`；srvpro2 从数据库动态重建 YRP2，浏览器不会接触 API 凭据。
- 卡片排行榜：RAMSAY 解码 `duel_record_player."startDeckBuffer"`，按
  `("roomIdentifier", pos)` 取整场比赛的第一份卡组，再沿用原有 alias 与卡片资料逻辑。
- RAMSAY 自身的投票、用户和分享卡组仍使用 SQLite；不要把原 `Database` 类改为 PostgreSQL。

## 1. 确认 srvpro2 数据完整

srvpro2 需要正常连接 PostgreSQL，并建议配置：

```yaml
apiHost: "127.0.0.1"
apiPort: 7922
enableCloudReplay: 1
cloudReplayInstantWrite: 0
```

Docker/环境变量等价项为 `API_HOST`、`API_PORT`、`ENABLE_CLOUD_REPLAY`、
`CLOUD_REPLAY_INSTANT_WRITE`。如果 IIS 与 srvpro2 不在同一主机，必须通过
HTTPS（可由反向代理终止 TLS）访问 API，并通过防火墙只允许 IIS 主机访问。

生产镜像可能落后于源码。部署前在 srvpro2 数据库执行：

```sql
SELECT table_name, column_name, data_type
FROM information_schema.columns
WHERE table_schema = 'public'
  AND table_name IN ('duel_record', 'duel_record_player')
ORDER BY table_name, ordinal_position;
```

至少应存在：

- `duel_record`：`id`、`"startTime"`、`"endTime"`、`name`、
  `"roomIdentifier"`、`"hostInfo"`、`"duelCount"`、`"winReason"`、
  `"deleteTime"`。
- `duel_record_player`：`"duelRecordId"`、`name`、`pos`、`score`、`winner`、
  `"startDeckBuffer"`、`"startDeckMainc"`、`"deleteTime"`。

如果列不存在，应先升级 srvpro2 并让 TypeORM 完成 schema 同步；不要在 RAMSAY
中猜测旧表结构。

同时检查关联索引：

```sql
SELECT indexname, indexdef
FROM pg_indexes
WHERE schemaname = 'public'
  AND tablename IN ('duel_record', 'duel_record_player')
ORDER BY tablename, indexname;
```

srvpro2 当前实体未显式为 `duel_record_player."duelRecordId"` 建索引，而
PostgreSQL 不会自动为外键引用列建索引。数据量较大时，建议由数据库 owner 在事务
外执行：

```sql
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_ramsay_replay_player_record_pos
ON public.duel_record_player ("duelRecordId", pos)
WHERE "deleteTime" IS NULL;

CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_ramsay_finished_replay_id
ON public.duel_record (id DESC)
WHERE "deleteTime" IS NULL AND "winReason" IS NOT NULL;
```

用 `EXPLAIN (ANALYZE, BUFFERS)` 确认列表、玩家关联与排行榜查询使用索引。若
srvpro2 开启 TypeORM `synchronize`，升级后应复查手工索引是否仍存在；长期方案是
在 srvpro2 实体迁移中正式声明相同索引。

## 2. 创建最小权限 API 用户

编辑 srvpro2 的 `data/admin_user.json`，在现有 `users` 对象中合并一个仅供
RAMSAY 使用的用户（不要覆盖已有账号或权限模板）：

```json
{
  "permission_examples": {},
  "users": {
    "ramsay": {
      "password": "替换为高强度随机密码",
      "enabled": true,
      "permissions": {
        "download_replay": true,
        "duel_log": true
      }
    }
  }
}
```

`download_replay` 用于动态生成 YRP；`duel_log` 用于读取 srvpro2 内存中的精确
活动房间状态，防止公开 siding 阶段双方卡组。两项权限都必须授予。

srvpro2 Legacy API 通过查询字符串传递密码。RAMSAY 只允许回环地址使用 HTTP，
任何远程地址都必须使用 HTTPS；同时应在反向代理访问日志中隐藏查询字符串。
绝不能把账号密码直接写进浏览器 JavaScript。

## 3. 创建 PostgreSQL 只读账号

不要让网站使用 srvpro2 的数据库所有者账号。以数据库管理员身份执行：

```sql
CREATE ROLE ramsay_reader LOGIN PASSWORD '替换为高强度随机密码';
GRANT CONNECT ON DATABASE srvpro2 TO ramsay_reader;
GRANT USAGE ON SCHEMA public TO ramsay_reader;
GRANT SELECT (
    id, "startTime", "endTime", name, "roomIdentifier", "hostInfo",
    "duelCount", "winReason", "deleteTime"
) ON TABLE public.duel_record TO ramsay_reader;
GRANT SELECT (
    id, "duelRecordId", name, pos, score, winner,
    "startDeckBuffer", "startDeckMainc", "deleteTime"
) ON TABLE public.duel_record_player TO ramsay_reader;
```

这里使用列级授权，避免 IIS 账号读取代码完全不需要的玩家 IP、`realName`、
`clientKey` 与对局中卡组。`SRVPRO2_DB_USER` 默认留空，部署时必须显式填写上述
只读账号，不能复用 srvpro2 数据库 owner。

如果 PostgreSQL 在 Docker 中，确认端口只发布到需要的接口，并限制
`pg_hba.conf`/防火墙来源。公网连接应使用 TLS，并将
`SRVPRO2_DB_SSLMODE` 设为 `require`、`verify-ca` 或 `verify-full`。

## 4. 配置 RAMSAY

在 `config.user.php` 中定义生产值：

```php
<?php
define('DEBUG_MODE', false);

// true：srvpro2 PostgreSQL + 动态录像 API；false：旧 srvpro 文件模式。
define('SRVPRO2_INTEGRATION_ENABLED', true);

define('SRVPRO2_API_BASE_URL', 'http://127.0.0.1:7922');
define('SRVPRO2_API_USERNAME', 'ramsay');
define('SRVPRO2_API_PASSWORD', '替换为 API 密码');
define('SRVPRO2_API_VERIFY_TLS', true);

define('SRVPRO2_DB_HOST', '127.0.0.1');
define('SRVPRO2_DB_PORT', 5432);
define('SRVPRO2_DB_NAME', 'srvpro2');
define('SRVPRO2_DB_SCHEMA', 'public');
define('SRVPRO2_DB_USER', 'ramsay_reader');
define('SRVPRO2_DB_PASSWORD', '替换为 PostgreSQL 密码');
define('SRVPRO2_DB_SSLMODE', 'prefer');

// 与 srvpro2 WINDBOT_BOTLIST 使用同一份 bots.json；远程部署可复制到 IIS 主机。
define('SRVPRO2_WINDBOT_BOTLIST_PATH', 'C:\\srvpro2\\windbot\\bots.json');

define('REPLAY_ENABLED', true);
define('CARD_RANKING_ENABLED', true);
```

`SRVPRO2_API_PASSWORD` 与 `SRVPRO2_DB_PASSWORD` 不会在 RAMSAY 后台配置页中
回显；后台保存其他设置时会保留现有值。

可选项：

- `SRVPRO2_DB_CONNECT_TIMEOUT`：默认 `10` 秒。
- `SRVPRO2_DB_STATEMENT_TIMEOUT_MS`：默认 `60000`。
- `SRVPRO2_REPLAY_VISIBILITY_CACHE_SECONDS`：默认 `30`；活动状态短缓存，已经确认
  离开 RoomManager 的录像会持久缓存为可公开。
- `SRVPRO2_REPLAY_VISIBILITY_MAX_LOOKUPS`：默认每请求最多查询 10 个不确定房间；
  超出预算会中止并返回可重试错误，不会把未经确认的活动录像当作公开录像。
- `SRVPRO2_DUEL_LOG_TIMEOUT` / `SRVPRO2_DUEL_LOG_MAX_BYTES` /
  `SRVPRO2_DUEL_LOG_MAX_ENTRIES`：状态查询默认 5 秒、8 MiB、10000 条上限。
- `SRVPRO2_REPLAY_SCAN_BATCH_SIZE` / `SRVPRO2_REPLAY_MAX_SCAN_BATCHES` /
  `SRVPRO2_REPLAY_MAX_PAGE`：默认 50 条/批、每请求最多 20 批、最大 1000 页；
  列表按 ID keyset 扫描，在过滤活动房间后再分页。“下一页”链接携带游标，避免
  正常浏览从最新记录重复扫描；直接跳到很深页码可能触发预算保护并返回可重试错误。
- `SRVPRO2_REPLAY_MAX_BYTES`：默认 64 MiB。
- `SRVPRO2_MAX_DECK_CARDS`：默认 200，用于拒绝损坏或恶意 payload。
- `SRVPRO2_DECK_BATCH_SIZE`：默认 500；使用 keyset 分批查询，避免旧版
  PDO_PGSQL 在 `execute()` 时缓冲全量卡组结果。
- `SRVPRO2_WINDBOT_BOTLIST_PATH`：IIS 可读取的 srvpro2 `bots.json` 路径；配置后
  排除任何包含名单中机器人名称的整场比赛。
- `SRVPRO2_WINDBOT_NAMES`：无法共享文件时可写 JSON 名称数组，例如
  `'["Bot A","Bot B"]'`；与 botlist 名称合并。
- `CARD_RANKING_CACHE_DAYS`：排行榜缓存时间。

将 `SRVPRO2_INTEGRATION_ENABLED` 改为 `false` 才会重新启用旧入口：

- 录像扫描 `REPLAY_PATH` 下的 `.yrp/.yrp2` 文件。
- 排行榜扫描 `DECK_LOG_PATH` 下的 `.ydk` 文件。

新逻辑连接失败时不会静默回退旧数据，以免生产页面展示陈旧排行榜。

## 5. IIS + FastCGI 的 PHP 扩展

必须启用：

- `PDO`
- `pdo_pgsql`：查询 srvpro2 PostgreSQL
- `curl`：代理 srvpro2 动态录像 API
- `pdo_sqlite`：RAMSAY 自身数据库与卡片 CDB
- `mbstring`：项目现有文本处理

本次接入新增的硬依赖只有 `pdo_pgsql` 与 `curl`；`pdo_sqlite`、`mbstring` 是
RAMSAY 原有功能仍需保留的扩展。Windows PHP 的 `php.ini` 通常为：

```ini
extension=php_pdo_sqlite.dll
extension=php_pdo_pgsql.dll
extension=php_curl.dll
extension=php_mbstring.dll
```

本实现不直接调用 PHP OpenSSL API，因此 `php_openssl` 不是 cURL/libpq 使用 TLS
的开关。HTTPS 需要 PHP cURL 所用 TLS 后端及 CA 信任链可用；PostgreSQL TLS
则需要 `libpq.dll` 及其匹配的 TLS 依赖可被 IIS 应用池加载。项目其他功能若调用
OpenSSL，仍可单独启用 `php_openssl`。

不需要 `mysqli`、`pdo_mysql`、非 PDO 的 `pgsql`、`zip` 或额外 LZMA 扩展。
srvpro2 负责生成 YRP/ZIP；卡组解析只使用 PHP 核心的 `base64_decode()` 与
`unpack()`。

Windows 注意事项：

1. DLL 必须与 `php-cgi.exe` 的 PHP 版本、x64/x86、NTS/TS 和 VC Runtime 匹配。
2. `pdo_pgsql` 依赖的 `libpq.dll` 及其 OpenSSL 依赖必须能被 IIS 应用池加载。
3. 修改的是 IIS Handler Mapping 实际使用的 `php.ini`，不一定是命令行 PHP 的文件。
4. 修改后回收 IIS 应用程序池。
5. 在 FastCGI 环境确认 `PDO::getAvailableDrivers()` 同时含 `sqlite` 与 `pgsql`，
   并确认 `extension_loaded('curl')`、`extension_loaded('mbstring')`。
6. 当前最坏请求可能先等待 PostgreSQL（连接 10 秒、语句 60 秒），再等待动态
   录像 API 30 秒。应在 IIS FastCGI Settings 将 `activityTimeout` 与
   `requestTimeout`，以及 `php.ini` 的 `max_execution_time`，设为高于完整请求
   预算；按默认值建议至少 120 秒，IIS 可留到 150 秒。

PHP 7.0 已停止维护，而且其旧版 `libpq` 可能不支持 PostgreSQL 17 默认的
SCRAM-SHA-256。生产环境应优先升级到仍受支持的 PHP，并使用随该版本提供的
匹配 DLL；不要把新版 DLL 任意混装进旧 PHP。

## 6. 验证

先运行不依赖数据库的兼容层测试：

```powershell
php tests\srvpro2_compat_test.php
```

然后验证：

1. 访问 `?controller=replay`，确认已经产生 `winReason` 的单局可分页显示，且进行中
   Match 的前局在 siding 阶段不会出现；未结束的即时写入快照也不会显示。
2. 点击“播放”，确认浏览器能取得 RAMSAY 的 `action=file` 响应并完成回放。
3. 点击“下载”，确认得到 `{id}.yrp`，文件内容由 srvpro2 动态生成。
4. 访问 `?controller=card_ranking`，强制更新一周榜单，确认总卡组数与近期对局量相符。
5. 检查 RAMSAY 调试日志中是否有“无效的 srvpro2 卡组快照”或连接错误。

## 已知口径差异

- 旧 srvpro 会排除 Windbot 房；srvpro2 只在内存中的 `client.windbot` /
  `room.windbot` 保存可靠标记，数据库没有对应列。RAMSAY 可读取同一份
  `bots.json`，按精确机器人名称排除整场比赛；若真人故意使用同名仍可能被误排。
  若要完全可靠，需要 srvpro2 将机器人标记持久化到录像表。名单变更后请在后台
  强制更新排行榜。
- srvpro2 的时间字段是无时区 `timestamp`。本项目默认 PHP 时区与 srvpro2 官方
  部署示例相同，均为 `Asia/Shanghai`；若你改过 srvpro2 的 `TZ`，也应同步调整
  RAMSAY 的 PHP 时区，否则排行榜日期边界会按不同的本地日期计算。
- srvpro2 `/api/duellog?roomname=` 使用房间名前缀查询。大量历史房间共用同一前缀
  时，响应可能超过 RAMSAY 的字节/条目上限；系统会保守拒绝公开相关不确定录像，
  而不是冒险泄露活动 Match 卡组。此时应使用更独特的房间名，或为 srvpro2 增加
  按 `roomIdentifier` 批量返回活动状态的专用 API。
