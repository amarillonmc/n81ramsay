<?php
parse_str($argv[1] ?? '', $get);
parse_str($argv[2] ?? '', $post);
parse_str($argv[3] ?? '', $serverArgs);
$_GET = $get;
$_POST = $post;
$_REQUEST = array_merge($get, $post);
$_SERVER['REQUEST_METHOD'] = $serverArgs['method'] ?? ($post ? 'POST' : 'GET');
$_SERVER['HTTP_HOST'] = $serverArgs['host'] ?? 'localhost';
$_SERVER['HTTP_ORIGIN'] = $serverArgs['origin'] ?? '';
$_SERVER['HTTP_REFERER'] = $serverArgs['referer'] ?? '';
$_SERVER['HTTP_USER_AGENT'] = $serverArgs['ua'] ?? 'cli-test';
$_SERVER['REMOTE_ADDR'] = $serverArgs['ip'] ?? '127.0.0.1';
$_SERVER['HTTPS'] = 'off';
if (!empty($serverArgs['sid'])) {
    session_id($serverArgs['sid']);
}

if (!empty($serverArgs['public_votes'])) {
    define('PUBLIC_VOTE_CREATION_ENABLED', true);
}
ob_start();
register_shutdown_function(function () {
    fwrite(STDERR, 'STATUS:' . http_response_code() . PHP_EOL);
});
include __DIR__ . '/../index.php';
$out = ob_get_clean();
file_put_contents('php://stdout', $out);
