<?php
/**
 * RAMSAY 配置文件
 *
 * 该文件包含系统的所有配置项
 */

// 检查是否存在用户配置文件，如果存在则加载
$userConfigFile = __DIR__ . '/config.user.php';
if (file_exists($userConfigFile)) {
    include_once($userConfigFile);
}

// 调试模式（如果在config.user.php中已定义，则不会重复定义）
if (!defined('DEBUG_MODE')) {
    define('DEBUG_MODE', true);
}

// 数据库配置
if (!defined('DB_PATH')) {
    define('DB_PATH', __DIR__ . '/data/ramsay.db');
}

// 根据DEBUG_MODE判断环境类型
// DEBUG_MODE为false时为生产环境，否则为测试环境
$isProduction = !DEBUG_MODE;

// SQLite可执行程序位置
if (!defined('SQLITE_PATH')) {
    if ($isProduction) {
        define('SQLITE_PATH', 'C:\\soft\\sqlite');
    } else {
        define('SQLITE_PATH', 'D:\\soft\\sqlite');
    }
}

// 卡片数据位置
if (!defined('CARD_DATA_PATH')) {
    if ($isProduction) {
        define('CARD_DATA_PATH', __DIR__ . '/expansions');
    } else {
        define('CARD_DATA_PATH', __DIR__ . '/example');
    }
}

// TCG卡片数据位置
if (!defined('TCG_CARD_DATA_PATH')) {
    define('TCG_CARD_DATA_PATH', __DIR__ . '/assets/cards.cdb');
}

// 是否允许对TCG卡发起禁卡投票
if (!defined('ALLOW_TCG_CARD_VOTING')) {
    define('ALLOW_TCG_CARD_VOTING', false);
}

// 服务器记录卡组文件存放位置
if (!defined('DECK_LOG_PATH')) {
    define('DECK_LOG_PATH', __DIR__ . '/deck_log');
}

// 数据库字符串位置
if (!defined('DB_STRINGS_PATH')) {
    define('DB_STRINGS_PATH', __DIR__ . '/res/cardinfo_chinese.txt');
}

// 临时文件目录
if (!defined('TMP_DIR')) {
    define('TMP_DIR', __DIR__ . '/tmp');
}

// 卡片环境配置
if (!defined('CARD_ENVIRONMENTS')) {
    define('CARD_ENVIRONMENTS', json_encode([
        ["id" => 1, "header" => "!THE STANDARD LIST", "text" => "标准环境"],
        ["id" => 2, "header" => "!THE WILD LIST", "text" => "狂野环境"],
        ["id" => 3, "header" => "!THE WILDEST LIST", "text" => "狂野禁止"]
    ]));
}

// 环境覆盖配置
if (!defined('ENVIRONMENT_OVERRIDE')) {
    define('ENVIRONMENT_OVERRIDE', true);
}

// 管理员配置
if (!defined('ADMIN_CONFIG')) {
    define('ADMIN_CONFIG', json_encode([
        ["username" => "admin", "group" => 255, "password" => password_hash("admin123", PASSWORD_DEFAULT)],
        ["username" => "moderator", "group" => 2, "password" => password_hash("mod123", PASSWORD_DEFAULT)],
        ["username" => "editor", "group" => 1, "password" => password_hash("edit123", PASSWORD_DEFAULT)]
    ]));
}

// 网站基本信息
if (!defined('SITE_TITLE')) {
    define('SITE_TITLE', 'RAMSAY - no81游戏王DIY服务器管理系统');
}
if (!defined('SITE_DESCRIPTION')) {
    define('SITE_DESCRIPTION', '管理no81游戏王DIY服务器的各种运营事务');
}

// 首页配置：home=主页, card=卡片检索, vote=卡片投票
if (!defined('HOME_PAGE')) {
    define('HOME_PAGE', 'home');
}

// 路径配置
if (!defined('BASE_URL')) {
    define('BASE_URL', '/');
}
if (!defined('ASSETS_URL')) {
    define('ASSETS_URL', BASE_URL . 'assets/');
}
if (!defined('PICS_URL')) {
    define('PICS_URL', BASE_URL . 'pics/');
}

// 会话配置
if (!defined('SESSION_NAME')) {
    define('SESSION_NAME', 'ramsay_session');
}
if (!defined('SESSION_LIFETIME')) {
    define('SESSION_LIFETIME', 3600); // 1小时
}

// 投票配置
if (!defined('VOTES_PER_PAGE')) {
    define('VOTES_PER_PAGE', 20);
}
if (!defined('VOTE_LINK_PREFIX')) {
    define('VOTE_LINK_PREFIX', BASE_URL . 'vote/');
}
if (!defined('ALLOW_VOTE_DELETION')) {
    define('ALLOW_VOTE_DELETION', true); // 是否允许用户删除自己的投票
}
if (!defined('VOTING_RELAXED_MODE')) {
    define('VOTING_RELAXED_MODE', 0); // 0: 默认模式, 1: 抵消后最高限制, 2: 抵消后最低限制, 3: 得票最多的最低限制
}
if (!defined('ALLOW_MEANINGLESS_VOTING')) {
    define('ALLOW_MEANINGLESS_VOTING', false); // 是否允许无意义投票（对卡片发起与其当前禁限状态相同的投票）
}

// 系列投票配置
if (!defined('SERIES_VOTING_ENABLED')) {
    define('SERIES_VOTING_ENABLED', true); // 是否启用系列投票功能
}
if (!defined('SERIES_VOTING_STRICTNESS')) {
    define('SERIES_VOTING_STRICTNESS', 2); // 系列投票严格度：0=所有用户可用，1=需要填写理由，2=发起人必须在作者列表中，3=发起人必须在作者列表中且验证卡片作者
}
if (!defined('SERIES_VOTING_REASON_MIN_LENGTH')) {
    define('SERIES_VOTING_REASON_MIN_LENGTH', 400); // 系列投票理由最小字节数
}

// 高级投票配置
if (!defined('ADVANCED_VOTING_ENABLED')) {
    define('ADVANCED_VOTING_ENABLED', true); // 是否启用高级投票功能
}

// 卡片配置
if (!defined('CARDS_PER_PAGE')) {
    define('CARDS_PER_PAGE', 20); // 默认每页显示卡片数量
}

// JSON API输出格式配置
// json: 标准JSON格式（Content-Type: application/json）
// pre: HTML页面内<pre>标签包裹JSON（兼容部分LLM浏览工具）
// html: 完整HTML页面包含JSON数据（最大兼容性）
if (!defined('JSON_API_OUTPUT_FORMAT')) {
    define('JSON_API_OUTPUT_FORMAT', 'html');
}

// 网站完整URL（用于JSON API链接复制，需包含协议和域名）
// 例如: https://example.com 或 http://localhost:8080
if (!defined('SITE_FULL_URL')) {
    define('SITE_FULL_URL', '');
}

// 作者光荣榜配置
if (!defined('AUTHOR_HALL_OF_FAME_ENABLED')) {
    define('AUTHOR_HALL_OF_FAME_ENABLED', true); // 是否启用作者光荣榜功能
}
if (!defined('AUTHOR_HALL_OF_FAME_HIGHLIGHT_THRESHOLD')) {
    define('AUTHOR_HALL_OF_FAME_HIGHLIGHT_THRESHOLD', 17); // 禁卡比例高亮阈值（百分比）
}
if (!defined('AUTHOR_HALL_OF_FAME_SIMPLE_MODE')) {
    define('AUTHOR_HALL_OF_FAME_SIMPLE_MODE', false); // 是否启用简略识别模式，仅使用管理员配置的作者列表
}
if (!defined('AUTHOR_HALL_OF_FAME_CACHE_DAYS')) {
    define('AUTHOR_HALL_OF_FAME_CACHE_DAYS', 7); // 作者光荣榜缓存天数，超过此天数将重新生成
}
if (!defined('EXCLUDED_CARD_DATABASES')) {
    define('EXCLUDED_CARD_DATABASES', json_encode(['Pre_Nerf_cards.cdb', 'SoundStageLib.cdb'])); // 需要排除的卡片数据库文件
}

// 卡片排行榜配置
if (!defined('CARD_RANKING_ENABLED')) {
    define('CARD_RANKING_ENABLED', false); // 是否启用卡片排行榜功能
}
if (!defined('CARD_RANKING_CACHE_DAYS')) {
    define('CARD_RANKING_CACHE_DAYS', 7); // 卡片排行榜缓存天数，超过此天数将重新生成
}

// 服务器提示配置
if (!defined('TIPS_FILE_PATH')) {
    // 检查原始路径是否可写
    $originalPath = __DIR__ . '/data/const/tips.json';
    $originalDir = dirname($originalPath);

    if (is_dir($originalDir) && is_writable($originalDir)) {
        define('TIPS_FILE_PATH', $originalPath);
    } else {
        // 使用系统临时目录作为备选
        $tempDir = sys_get_temp_dir() . '/ramsay_tips';
        if (!is_dir($tempDir)) {
            @mkdir($tempDir, 0755, true);
        }
        define('TIPS_FILE_PATH', $tempDir . '/tips.json');
    }
}

// 召唤词配置
if (!defined('DIALOGUES_FILE_PATH')) {
    // 检查原始路径是否可写
    $originalPath = __DIR__ . '/data/const/dialogues-custom.json';
    $originalDir = dirname($originalPath);

    if (is_dir($originalDir) && is_writable($originalDir)) {
        define('DIALOGUES_FILE_PATH', $originalPath);
    } else {
        // 使用系统临时目录作为备选
        $tempDir = sys_get_temp_dir() . '/ramsay_dialogues';
        if (!is_dir($tempDir)) {
            @mkdir($tempDir, 0755, true);
        }
        define('DIALOGUES_FILE_PATH', $tempDir . '/dialogues-custom.json');
    }
}

// 召唤词投稿配置
if (!defined('MAX_PENDING_DIALOGUES_PER_USER')) {
    define('MAX_PENDING_DIALOGUES_PER_USER', 5); // 用户可以同时投稿的召唤词数量
}

if (!defined('DIALOGUE_SUBMISSION_STRICTNESS')) {
    define('DIALOGUE_SUBMISSION_STRICTNESS', 2); // 召唤词投稿严格度：0=无限制，1=仅验证作者存在，2=验证作者和卡片前缀匹配
}

// 卡组分享功能配置
if (!defined('DECK_SHARING_ENABLED')) {
    define('DECK_SHARING_ENABLED', true); // 是否启用卡组分享功能
}
if (!defined('DECK_UPLOAD_PERMISSION')) {
    // 卡组上传权限：0=所有用户可上传, 1=仅参与过投票的用户可上传（默认）, 2=仅管理员可上传
    define('DECK_UPLOAD_PERMISSION', 1);
}
if (!defined('TCG_CARD_IMAGE_PATH')) {
    // TCG卡图存储位置（用于卡组展示）
    // 如果为空或无法读取，使用assets/images/card_back.jpg作为卡图
    define('TCG_CARD_IMAGE_PATH', '');
}
if (!defined('DECKS_PER_PAGE')) {
    define('DECKS_PER_PAGE', 20); // 卡组列表每页显示数量
}

// 错误处理配置
if (defined('DEBUG_MODE') && DEBUG_MODE) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);
}

// 时区设置
date_default_timezone_set('Asia/Shanghai');

// 字符集设置
ini_set('default_charset', 'UTF-8');
