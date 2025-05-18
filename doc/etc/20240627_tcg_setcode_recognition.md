# TCG卡片系列(setcode)识别优化

## 需求描述
在卡片系列(setcode)识别中，需要对TCG卡片和非TCG卡片进行不同的处理：

1. 对于非TCG卡片：如果在已有的数据中无法匹配到对应setcode的setname，则转至`/assets/strings.conf`文件进行匹配
2. 对于TCG卡片：优先在`/assets/strings.conf`文件中匹配对应setcode的setname

## 实现方案

### 1. 修改系列信息加载逻辑
修改`CardParser`类的`loadSetcodes`方法，使其同时从卡片数据目录和`/assets`目录加载系列信息，并将`/assets`目录下的系列信息存储在单独的数组中，以便区分来源。

### 2. 修改系列文本获取逻辑
修改`getSetcodeText`方法，添加`isTcgCard`参数，根据卡片类型采用不同的匹配策略：
- 对于TCG卡片：优先从`/assets/strings.conf`中查找
- 对于非TCG卡片：优先从默认数据中查找，如果找不到再从`/assets/strings.conf`中查找

### 3. 更新调用代码
在所有调用`getSetcodeText`方法的地方，添加`isTcgCard`参数，以便正确处理不同类型的卡片。

## 修改内容

### 1. 系列信息加载 (includes/Core/CardParser.php)
```php
/**
 * 加载系列信息
 */
private function loadSetcodes() {
    // 首先从卡片数据目录加载系列信息
    $cardDataPath = CARD_DATA_PATH;
    $stringsFile = $cardDataPath . '/strings.conf';

    if (file_exists($stringsFile)) {
        $this->loadSetcodesFromFile($stringsFile);
    }

    // 然后加载assets目录下的strings.conf文件
    $assetsStringsFile = __DIR__ . '/../../assets/strings.conf';
    if (file_exists($assetsStringsFile)) {
        // 将这些系列信息存储在单独的数组中，以便区分来源
        $this->loadSetcodesFromFile($assetsStringsFile, 'assets');
    }
}

/**
 * 从文件加载系列信息
 * 
 * @param string $filePath 文件路径
 * @param string $source 来源标识，用于区分不同来源的系列信息
 */
private function loadSetcodesFromFile($filePath, $source = 'default') {
    if (file_exists($filePath)) {
        $content = file_get_contents($filePath);
        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            $line = trim($line);

            // 跳过空行和注释行
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }

            // 解析系列信息
            if (strpos($line, '!setname') === 0) {
                $parts = explode(' ', $line, 3);
                if (count($parts) >= 3) {
                    $code = trim($parts[1]);
                    $name = trim($parts[2]);
                    
                    // 如果是assets来源，则存储在单独的数组中
                    if ($source === 'assets') {
                        if (!isset($this->setcodes['assets'])) {
                            $this->setcodes['assets'] = [];
                        }
                        $this->setcodes['assets'][$code] = $name;
                    } else {
                        $this->setcodes[$code] = $name;
                    }
                }
            }
        }
    }
}
```

### 2. 系列文本获取 (includes/Core/CardParser.php)
```php
/**
 * 获取系列文本
 *
 * @param int $setcode 系列代码
 * @param bool $isTcgCard 是否为TCG卡片
 * @return string 系列文本
 */
public function getSetcodeText($setcode, $isTcgCard = false) {
    $hexSetcode = '0x' . dechex($setcode);
    
    // 对于TCG卡片，优先从assets/strings.conf中查找
    if ($isTcgCard && isset($this->setcodes['assets']) && isset($this->setcodes['assets'][$hexSetcode])) {
        return $this->setcodes['assets'][$hexSetcode];
    }
    
    // 对于普通卡片，优先从默认数据中查找
    if (isset($this->setcodes[$hexSetcode])) {
        return $this->setcodes[$hexSetcode];
    }
    
    // 如果在默认数据中找不到，尝试从assets/strings.conf中查找
    if (isset($this->setcodes['assets']) && isset($this->setcodes['assets'][$hexSetcode])) {
        return $this->setcodes['assets'][$hexSetcode];
    }
    
    // 如果都找不到，返回未知系列
    return '未知系列 (' . $hexSetcode . ')';
}
```

### 3. 更新调用代码 (includes/Core/CardParser.php)
在所有调用`getSetcodeText`方法的地方，添加`isTcgCard`参数：

```php
// 判断是否为TCG卡片
$isTcgCard = (basename($dbFile) === basename(TCG_CARD_DATA_PATH));

$card['setcode_text'] = $this->getSetcodeText($card['setcode'], $isTcgCard);
```

对于TCG卡片数据库的处理：

```php
// TCG卡片设置标志
$isTcgCard = true;

$card['setcode_text'] = $this->getSetcodeText($card['setcode'], $isTcgCard);
```

## 注意事项
1. 确保`/assets/strings.conf`文件存在并包含正确的系列信息
2. 系统会根据卡片所在的数据库文件判断是否为TCG卡片
3. 对于TCG卡片，优先使用`/assets/strings.conf`中的系列信息
4. 对于非TCG卡片，如果在默认数据中找不到对应的系列信息，会尝试从`/assets/strings.conf`中查找
