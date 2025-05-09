<?php
/**
 * RAMSAY 配置文件
 *
 * 该文件包含系统的所有配置项
 */

// 调试模式
define('DEBUG_MODE', true);

// 数据库配置
define('DB_PATH', __DIR__ . '/data/ramsay.db');

// 根据DEBUG_MODE判断环境类型
// DEBUG_MODE为false时为生产环境，否则为测试环境
$isProduction = !DEBUG_MODE;

// SQLite可执行程序位置
if ($isProduction) {
    define('SQLITE_PATH', 'C:\\soft\\sqlite');
} else {
    define('SQLITE_PATH', 'D:\\soft\\sqlite');
}

// 卡片数据位置
if ($isProduction) {
    define('CARD_DATA_PATH', __DIR__ . '/expansions');
} else {
    define('CARD_DATA_PATH', __DIR__ . '/example');
}

// 数据库字符串位置
define('DB_STRINGS_PATH', __DIR__ . '/res/cardinfo_chinese.txt');

// 卡片环境配置
define('CARD_ENVIRONMENTS', json_encode([
    ["id" => 1, "header" => "!THE STANDARD LIST", "text" => "标准环境"],
    ["id" => 2, "header" => "!THE WILD LIST", "text" => "狂野环境"],
    ["id" => 3, "header" => "!THE WILDEST LIST", "text" => "狂野禁止"]
]));

// 环境覆盖配置
define('ENVIRONMENT_OVERRIDE', true);

// 管理员配置
define('ADMIN_CONFIG', json_encode([
    ["username" => "admin", "group" => 255, "password" => password_hash("admin123", PASSWORD_DEFAULT)],
    ["username" => "moderator", "group" => 2, "password" => password_hash("mod123", PASSWORD_DEFAULT)],
    ["username" => "editor", "group" => 1, "password" => password_hash("edit123", PASSWORD_DEFAULT)]
]));

// 网站基本信息
define('SITE_TITLE', 'RAMSAY - no81游戏王DIY服务器管理系统');
define('SITE_DESCRIPTION', '管理no81游戏王DIY服务器的各种运营事务');

// 路径配置
define('BASE_URL', '/');
define('ASSETS_URL', BASE_URL . 'assets/');
define('PICS_URL', BASE_URL . 'pics/');

// 会话配置
define('SESSION_NAME', 'ramsay_session');
define('SESSION_LIFETIME', 3600); // 1小时

// 投票配置
define('VOTES_PER_PAGE', 20);
define('VOTE_LINK_PREFIX', BASE_URL . 'vote/');

// 卡片配置
define('CARDS_PER_PAGE', 20); // 默认每页显示卡片数量

// 错误处理配置
if (DEBUG_MODE) {
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
