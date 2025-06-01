<?php
// 测试setcode转换
header('Content-Type: text/html; charset=utf-8');

echo "<h1>Setcode转换测试</h1>";

// 测试用例
$testCases = [
    '0x344a',
    '0x1',
    '0x10',
    '0x100',
    '344a',
    '1',
    '10',
    '100',
    '0',
    ''
];

echo "<table border='1'>";
echo "<tr><th>输入</th><th>检测到十六进制</th><th>转换结果</th><th>十六进制显示</th></tr>";

foreach ($testCases as $input) {
    $setcodeParam = trim($input);
    
    // 处理十六进制格式（如 0x344a）
    if (strpos($setcodeParam, '0x') === 0) {
        $setcode = hexdec(substr($setcodeParam, 2));
        $isHex = true;
    } else {
        $setcode = (int)$setcodeParam;
        $isHex = false;
    }
    
    echo "<tr>";
    echo "<td>" . htmlspecialchars($input) . "</td>";
    echo "<td>" . ($isHex ? '是' : '否') . "</td>";
    echo "<td>" . $setcode . "</td>";
    echo "<td>0x" . dechex($setcode) . "</td>";
    echo "</tr>";
}

echo "</table>";

echo "<hr>";
echo "<h2>特定测试：0x344a</h2>";

$testSetcode = '0x344a';
$setcode = hexdec(substr($testSetcode, 2));

echo "<p>输入: $testSetcode</p>";
echo "<p>转换结果: $setcode</p>";
echo "<p>十六进制验证: 0x" . dechex($setcode) . "</p>";
echo "<p>计算验证: 3*16^3 + 4*16^2 + 4*16^1 + 10*16^0 = " . (3*4096 + 4*256 + 4*16 + 10) . "</p>";

echo "<hr>";
echo "<h2>测试API调用</h2>";

$testUrls = [
    '?controller=api&action=getSeriesCards&setcode=0x344a',
    '?controller=api&action=getSeriesCards&setcode=' . $setcode,
    '?controller=api&action=getSeriesCards&setcode=1'
];

foreach ($testUrls as $url) {
    $fullUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/' . $url;
    echo "<p><a href='$fullUrl' target='_blank'>$fullUrl</a></p>";
}
?>
