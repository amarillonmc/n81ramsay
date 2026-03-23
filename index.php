<?php
/**
 * RAMSAY 入口文件
 *
 * 处理所有请求并路由到相应的控制器
 */

require_once __DIR__ . '/config.php';

spl_autoload_register(function ($className) {
    $paths = [
        __DIR__ . '/includes/Core/' . $className . '.php',
        __DIR__ . '/includes/Models/' . $className . '.php',
        __DIR__ . '/includes/Controllers/' . $className . '.php'
    ];

    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

Auth::getInstance();
Utils::rejectInvalidRequestData();

$defaultController = 'card';
if (defined('HOME_PAGE')) {
    switch (HOME_PAGE) {
        case 'home':
        case 'vote':
        case 'card':
            $defaultController = HOME_PAGE;
            break;
    }
}

$routeMap = [
    'card' => ['class' => 'CardController', 'actions' => ['index', 'detail', 'search', 'searchJson']],
    'vote' => ['class' => 'VoteController', 'actions' => ['index', 'create', 'vote', 'createSeries', 'createAdvanced', 'submitAdvanced', 'deleteRecord']],
    'home' => ['class' => 'HomeController', 'actions' => ['index']],
    'admin' => ['class' => 'AdminController', 'actions' => ['login', 'logout', 'votes', 'closeVote', 'banlist', 'generate', 'reset', 'update', 'authors', 'identifyAuthors', 'addAuthor', 'deleteAuthor', 'editAuthor', 'tips', 'addTip', 'editTip', 'deleteTip', 'voterBans', 'addVoterBan', 'removeVoterBan', 'config']],
    'banlist' => ['class' => 'BanlistController', 'actions' => ['index', 'generate', 'update', 'reset', 'reopenVote', 'deleteVote']],
    'author' => ['class' => 'AuthorController', 'actions' => ['index', 'detail', 'update', 'clearCache', 'debug']],
    'card_ranking' => ['class' => 'CardRankingController', 'actions' => ['index', 'update', 'clearCache']],
    'dialogue' => ['class' => 'DialogueController', 'actions' => ['index', 'submit', 'submitDialogue', 'admin', 'reviewSubmission', 'deleteSubmission', 'addDialogue', 'editDialogue', 'deleteDialogue']],
    'api' => ['class' => 'ApiController', 'actions' => ['index', 'test', 'getCardDetail', 'getSeriesCards']],
    'deck' => ['class' => 'DeckController', 'actions' => ['index', 'detail', 'create', 'store', 'storeBatch', 'delete', 'comment', 'download']],
    'replay' => ['class' => 'ReplayController', 'actions' => ['index', 'play', 'list', 'file', 'databases', 'database', 'script', 'cardimage']]
];

$controllerName = Utils::getSafeParam($_GET, 'controller', 'slug', $defaultController, ROUTE_PARAM_MAX_LENGTH);
$methodName = Utils::getSafeParam($_GET, 'action', 'slug', 'index', ROUTE_PARAM_MAX_LENGTH);

if ($controllerName === null || $methodName === null) {
    Utils::abort(400, 'Bad Request');
}

$params = [];
if ($controllerName === 'vote' && !isset($_GET['action']) && isset($_GET['id'])) {
    $voteLink = Utils::getSafeParam($_GET, 'id', 'hex8', null, 8);
    if ($voteLink === null) {
        Utils::abort(400, 'Bad Request');
    }
    $methodName = 'vote';
    $params = [$voteLink];
}

if (!isset($routeMap[$controllerName])) {
    Utils::abort(404, '404 Not Found');
}
if (!in_array($methodName, $routeMap[$controllerName]['actions'], true)) {
    Utils::abort(404, '404 Not Found');
}

$controllerClass = $routeMap[$controllerName]['class'];

try {
    $controller = new $controllerClass();
    if (!method_exists($controller, $methodName)) {
        Utils::abort(404, '404 Not Found');
    }
    call_user_func_array([$controller, $methodName], $params);
} catch (Exception $e) {
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        error_log('Route exception: ' . $e->getMessage());
    }
    Utils::abort(500, '500 Internal Server Error');
} catch (Error $e) {
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        error_log('Route fatal error: ' . $e->getMessage());
    }
    Utils::abort(500, '500 Internal Server Error');
}
