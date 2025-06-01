<?php
// 简单的API测试
echo "Content-Type: text/html; charset=utf-8\n\n";
echo "<h1>简单API测试</h1>";

// 检查文件是否存在
$apiFile = __DIR__ . '/includes/Controllers/ApiController.php';
echo "<p>ApiController文件存在: " . (file_exists($apiFile) ? '是' : '否') . "</p>";

if (file_exists($apiFile)) {
    echo "<p>文件路径: $apiFile</p>";
    echo "<p>文件大小: " . filesize($apiFile) . " 字节</p>";
}

// 尝试直接包含和测试
try {
    require_once __DIR__ . '/config.php';
    
    // 手动加载必要的类
    require_once __DIR__ . '/includes/Core/Utils.php';
    require_once __DIR__ . '/includes/Core/Database.php';
    require_once __DIR__ . '/includes/Core/CardParser.php';
    require_once __DIR__ . '/includes/Models/Card.php';
    require_once __DIR__ . '/includes/Controllers/ApiController.php';
    
    echo "<p style='color: green;'>所有类文件加载成功</p>";
    
    // 创建ApiController实例
    $api = new ApiController();
    echo "<p style='color: green;'>ApiController实例创建成功</p>";
    
    // 测试test方法
    echo "<h2>测试API test方法:</h2>";
    ob_start();
    $api->test();
    $output = ob_get_clean();
    echo "<pre>" . htmlspecialchars($output) . "</pre>";
    
    // 测试getSeriesCards方法
    echo "<h2>测试API getSeriesCards方法:</h2>";
    $_GET['setcode'] = '1';
    ob_start();
    $api->getSeriesCards();
    $output = ob_get_clean();
    echo "<pre>" . htmlspecialchars($output) . "</pre>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>错误: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
} catch (Error $e) {
    echo "<p style='color: red;'>致命错误: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<h2>直接测试URL访问:</h2>";
$testUrls = [
    BASE_URL . '?controller=api&action=test',
    BASE_URL . '?controller=api&action=getSeriesCards&setcode=1',
    BASE_URL . '?controller=api'
];

foreach ($testUrls as $url) {
    echo "<p><a href='$url' target='_blank'>$url</a></p>";
}
?>
