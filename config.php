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

// 卡片配置
if (!defined('CARDS_PER_PAGE')) {
    define('CARDS_PER_PAGE', 20); // 默认每页显示卡片数量
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
if (!defined('EXCLUDED_CARD_DATABASES')) {
    define('EXCLUDED_CARD_DATABASES', json_encode(['Pre_Nerf_cards.cdb', 'SoundStageLib.cdb'])); // 需要排除的卡片数据库文件
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
