# 临时文件处理修复

## 问题描述

系统在根目录下生成了URL编码的临时文件，如：

```
C%3A%5Cn81ramsay%2Fexpansions%2Fno42.cdb
```

这些文件大小为0KB，不是预期的行为。

## 原因分析

问题出现在卡片数据库连接处理上：

1. 在卡片列表页面，URL中的`db`参数直接使用了完整的文件路径
2. 当这个路径被URL编码后，PHP尝试使用这个编码后的路径创建SQLite连接
3. 这导致在根目录下创建了URL编码的空文件

## 修复方法

### 1. 参数处理改进

- 修改了`CardController`类，添加了对`db`参数的验证和清理
- 只接受已知的数据库文件路径或文件名
- 确保使用完整的文件路径进行数据库连接

```php
// 验证数据库文件是否在允许的列表中
if ($selectedDb !== null) {
    $isValid = false;
    foreach ($dbFiles as $dbFile) {
        if ($selectedDb === $dbFile || $selectedDb === basename($dbFile)) {
            $selectedDb = $dbFile; // 确保使用完整路径
            $isValid = true;
            break;
        }
    }
    
    // 如果不是有效的数据库文件，则重置为null
    if (!$isValid) {
        $selectedDb = null;
    }
}
```

### 2. 数据库连接改进

- 修改了`CardParser`类中的`getCardDatabase`方法
- 添加了文件存在性检查
- 使用文件路径的哈希值作为键，避免使用完整路径
- 确保临时目录存在

```php
// 验证数据库文件路径
if (!file_exists($dbFile)) {
    Utils::debug('数据库文件不存在', ['文件路径' => $dbFile]);
    die('卡片数据库文件不存在: ' . htmlspecialchars($dbFile));
}

// 使用文件路径的哈希值作为键，避免使用完整路径
$dbKey = md5($dbFile);
```

### 3. URL参数改进

- 修改了`cards/index.php`视图，在URL中使用文件名而非完整路径
- 所有分页链接和表单都使用`basename($dbFile)`而非`$dbFile`

```php
<a href="<?php echo BASE_URL; ?>?db=<?php echo urlencode(basename($dbFile)); ?>">
    <?php echo $fileName; ?>
</a>
```

### 4. 临时文件管理

- 添加了`TMP_DIR`常量，定义临时文件目录
- 添加了清理函数，在脚本结束时自动清理临时文件
- 清理包括根目录下的URL编码文件和临时目录中的0KB文件

```php
// 临时文件目录
if (!defined('TMP_DIR')) {
    define('TMP_DIR', __DIR__ . '/tmp');
}

/**
 * 清理临时文件
 */
function cleanupTempFiles() {
    // 查找根目录下的URL编码文件名的临时文件
    $files = glob(__DIR__ . '/../../C%3A*');
    foreach ($files as $file) {
        if (is_file($file)) {
            @unlink($file);
        }
    }
    
    // 清理临时目录中的0KB文件
    $tmpFiles = glob(TMP_DIR . '/*.cdb');
    foreach ($tmpFiles as $file) {
        if (is_file($file) && filesize($file) === 0) {
            @unlink($file);
        }
    }
}

// 在脚本结束时执行清理
register_shutdown_function('cleanupTempFiles');
```

## 修复效果

1. 不再在根目录下生成URL编码的临时文件
2. 所有临时文件都存放在`/tmp`目录中
3. 脚本结束时自动清理无用的临时文件
4. 提高了系统的安全性和稳定性

## 技术说明

- 使用`basename()`函数提取文件名，避免在URL中使用完整路径
- 使用`md5()`函数生成文件路径的哈希值，作为数据库连接的唯一标识
- 使用`register_shutdown_function()`在脚本结束时自动执行清理函数
- 添加了文件存在性检查，避免尝试打开不存在的文件
