<?php
// 测试修复后的setcode查询
require_once 'config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h1>测试修复后的Setcode查询</h1>";

$targetSetcode = 13386; // 0x344a
echo "<p>目标setcode: $targetSetcode (0x" . dechex($targetSetcode) . ")</p>";

try {
    require_once 'includes/Core/Utils.php';
    require_once 'includes/Core/Database.php';
    require_once 'includes/Core/CardParser.php';
    require_once 'includes/Models/Card.php';
    
    $cardModel = new Card();
    echo "<p style='color: green;'>✓ Card模型创建成功</p>";
    
    // 测试修复后的getCardsBySetcode方法
    echo "<h2>修复后的查询结果</h2>";
    $cards = $cardModel->getCardsBySetcode($targetSetcode);
    
    echo "<p>找到 " . count($cards) . " 张卡片</p>";
    
    if (!empty($cards)) {
        echo "<h3>卡片列表:</h3>";
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>名称</th><th>Setcode</th><th>Setcode (十六进制)</th><th>匹配类型</th><th>数据库</th></tr>";
        
        foreach ($cards as $card) {
            $matchType = '';
            if ($card['setcode'] == $targetSetcode) {
                $matchType = '精确匹配';
            } elseif (($card['setcode'] & $targetSetcode) == $targetSetcode) {
                $matchType = '位运算匹配';
            } else {
                $matchType = '未知匹配';
            }
            
            echo "<tr>";
            echo "<td>" . $card['id'] . "</td>";
            echo "<td>" . htmlspecialchars($card['name']) . "</td>";
            echo "<td>" . $card['setcode'] . "</td>";
            echo "<td>0x" . dechex($card['setcode']) . "</td>";
            echo "<td>" . $matchType . "</td>";
            echo "<td>" . $card['database_file'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // 验证所有卡片是否都正确匹配
        echo "<h3>匹配验证:</h3>";
        $validMatches = 0;
        $invalidMatches = 0;
        
        foreach ($cards as $card) {
            $cardSetcode = $card['setcode'];
            if ($cardSetcode == $targetSetcode || ($cardSetcode & $targetSetcode) == $targetSetcode) {
                $validMatches++;
            } else {
                $invalidMatches++;
                echo "<p style='color: red;'>无效匹配: 卡片ID " . $card['id'] . " setcode " . $cardSetcode . " (0x" . dechex($cardSetcode) . ")</p>";
            }
        }
        
        echo "<p style='color: green;'>有效匹配: $validMatches 张</p>";
        if ($invalidMatches > 0) {
            echo "<p style='color: red;'>无效匹配: $invalidMatches 张</p>";
        }
        
    } else {
        echo "<p style='color: orange;'>没有找到匹配的卡片</p>";
        
        // 如果没找到，测试数据库中是否真的存在这个setcode
        echo "<h3>数据库验证:</h3>";
        
        $dbFiles = [];
        if (defined('CARD_DATA_PATH') && is_dir(CARD_DATA_PATH)) {
            $files = glob(CARD_DATA_PATH . '/*.cdb');
            foreach ($files as $file) {
                $dbFiles[] = $file;
            }
        }
        
        foreach ($dbFiles as $dbFile) {
            $filename = basename($dbFile);
            
            // 跳过TCG数据库
            if (defined('TCG_CARD_DATA_PATH') && $filename === basename(TCG_CARD_DATA_PATH)) {
                continue;
            }
            
            try {
                $db = new PDO('sqlite:' . $dbFile);
                
                // 检查是否存在精确匹配
                $sql = "SELECT COUNT(*) as count FROM datas WHERE setcode = $targetSetcode";
                $result = $db->query($sql)->fetch(PDO::FETCH_ASSOC);
                
                if ($result['count'] > 0) {
                    echo "<p style='color: blue;'>数据库 $filename 中存在 " . $result['count'] . " 张精确匹配的卡片</p>";
                    
                    // 显示这些卡片
                    $sql = "SELECT d.id, t.name, d.setcode FROM datas d JOIN texts t ON d.id = t.id WHERE d.setcode = $targetSetcode LIMIT 5";
                    $stmt = $db->query($sql);
                    $examples = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    echo "<ul>";
                    foreach ($examples as $example) {
                        echo "<li>ID: " . $example['id'] . " - " . htmlspecialchars($example['name']) . " (setcode: " . $example['setcode'] . ")</li>";
                    }
                    echo "</ul>";
                }
                
            } catch (Exception $e) {
                echo "<p style='color: red;'>数据库 $filename 查询错误: " . $e->getMessage() . "</p>";
            }
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>错误: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<h2>位运算测试</h2>";
echo "<p>测试不同setcode值与目标setcode的位运算关系:</p>";

$testSetcodes = [13386, 1234, 8, 10, 4098, 13387, 13388, 26772]; // 包括一些从调试结果中看到的值

echo "<table border='1'>";
echo "<tr><th>测试Setcode</th><th>十六进制</th><th>(setcode & target) = target</th><th>应该匹配</th></tr>";

foreach ($testSetcodes as $testSetcode) {
    $bitwiseResult = ($testSetcode & $targetSetcode) == $targetSetcode;
    $shouldMatch = $testSetcode == $targetSetcode || $bitwiseResult;
    
    echo "<tr>";
    echo "<td>$testSetcode</td>";
    echo "<td>0x" . dechex($testSetcode) . "</td>";
    echo "<td>" . ($bitwiseResult ? '是' : '否') . "</td>";
    echo "<td>" . ($shouldMatch ? '是' : '否') . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<hr>";
echo "<h2>测试API调用</h2>";
$apiUrl = BASE_URL . '?controller=api&action=getSeriesCards&setcode=0x344a';
echo "<p><a href='$apiUrl' target='_blank'>$apiUrl</a></p>";
?>
