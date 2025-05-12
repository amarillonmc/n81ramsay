# 作者光荣榜调试功能

## 功能概述

在DEBUG_MODE = true的前提下，1级以上的管理员可以在作者光荣榜页面点击"生成调试内容"按钮。该按钮会在logs目录下生成带有时间戳的ranking文件夹，文件夹中按照榜单中的每一行生成一个文件，其中列出系统lflist中读取的禁卡列表。在生成完成后，进行提示反馈。

## 实现细节

### 1. 控制器实现

在`AuthorController.php`中添加了`debug`方法，该方法：
- 检查功能是否启用（AUTHOR_HALL_OF_FAME_ENABLED）
- 检查是否处于调试模式（DEBUG_MODE）
- 要求管理员权限（级别1+）
- 获取作者统计数据
- 创建带有时间戳的ranking文件夹
- 获取标准环境禁卡列表
- 为每个作者生成调试文件，包含作者信息和禁卡列表
- 提供反馈信息

```php
/**
 * 生成作者光荣榜调试内容
 */
public function debug() {
    // 检查功能是否启用
    if (!AUTHOR_HALL_OF_FAME_ENABLED) {
        header('Location: ' . BASE_URL);
        exit;
    }

    // 检查是否处于调试模式
    if (!DEBUG_MODE) {
        header('Location: ' . BASE_URL . '?controller=author');
        exit;
    }

    // 要求管理员权限
    $this->userModel->requirePermission(1);

    // 获取作者统计数据
    $authorStats = $this->authorStatsModel->getAuthorStats();

    // 创建带有时间戳的ranking文件夹
    $timestamp = date('Y-m-d_H-i-s');
    $debugDir = __DIR__ . '/../../logs/ranking_' . $timestamp;
    
    // 确保logs目录存在
    if (!file_exists(__DIR__ . '/../../logs')) {
        mkdir(__DIR__ . '/../../logs', 0777, true);
    }
    
    // 创建ranking目录
    if (!file_exists($debugDir)) {
        mkdir($debugDir, 0777, true);
    }

    // 获取标准环境禁卡列表
    $lflist = $this->cardParser->getLflist();
    
    // 获取环境列表
    $environments = Utils::getEnvironments();
    $standardEnvironment = null;
    
    // 查找标准环境
    foreach ($environments as $env) {
        if ($env['text'] === '标准环境') {
            $standardEnvironment = $env;
            break;
        }
    }
    
    $standardBanlist = [];
    if ($standardEnvironment) {
        $standardBanlist = $lflist[$standardEnvironment['header']] ?? [];
    }

    // 为每个作者生成调试文件
    $fileCount = 0;
    foreach ($authorStats as $author) {
        // 生成文件并写入内容
        // ...
    }

    // 设置会话消息
    $_SESSION['author_debug_message'] = "成功生成{$fileCount}个作者调试文件，保存在logs/ranking_{$timestamp}目录下";
    
    // 重定向到作者光荣榜页面
    header('Location: ' . BASE_URL . '?controller=author');
    exit;
}
```

### 2. 视图实现

在`authors/index.php`中添加了"生成调试内容"按钮，该按钮：
- 仅在DEBUG_MODE = true且用户具有管理员权限（级别1+）时显示
- 点击后调用AuthorController的debug方法

```php
<?php if ($this->userModel->hasPermission(1)): ?>
    <div class="btn-group">
        <a href="<?php echo BASE_URL; ?>?controller=author&action=update" class="btn btn-primary">更新榜单</a>
        <?php if (defined('DEBUG_MODE') && DEBUG_MODE): ?>
            <a href="<?php echo BASE_URL; ?>?controller=author&action=debug" class="btn btn-warning">生成调试内容</a>
        <?php endif; ?>
    </div>
<?php endif; ?>
```

### 3. 调试文件内容

每个作者的调试文件包含以下信息：
- 作者名称
- 卡片总数
- 禁卡数量
- 禁卡比例
- 禁止系列数
- 详细的禁卡列表，包括卡片ID、名称和禁限状态

## 编码问题修复

在实现过程中，发现了与编码相关的问题，主要表现在：

### 1. 文件名编码问题

在Windows系统中，文件名中的中文字符需要特殊处理，否则会导致"failed to open stream: No such file or directory"错误。

修复方法：
- 替换文件名中的非法字符（如`\/:*?"<>|`）
- 对包含非ASCII字符（如中文）的作者名，使用MD5哈希作为文件名前缀，并保留部分原始名称
- 限制文件名长度，避免过长导致的问题

```php
// 生成安全的文件名
// 1. 替换文件名中的非法字符
$safeAuthorName = preg_replace('/[\\\\\/\:\*\?\"\<\>\|]/', '_', $authorName);
// 2. 处理中文和特殊字符，使用MD5哈希确保文件名唯一且安全
if (preg_match('/[^\x20-\x7E]/', $safeAuthorName)) {
    // 如果包含非ASCII字符，使用作者名的MD5哈希作为文件名
    $safeAuthorName = md5($authorName) . '_' . mb_substr($safeAuthorName, 0, 10, 'UTF-8');
}
// 3. 限制文件名长度
if (strlen($safeAuthorName) > 50) {
    $safeAuthorName = substr($safeAuthorName, 0, 50);
}
```

### 2. 文件内容编码问题

为确保文件内容正确显示中文字符，修改了文件写入方式：
- 使用二进制模式打开文件（`fopen($debugFile, 'wb')`）
- 添加UTF-8 BOM标记（`\xEF\xBB\xBF`）
- 使用`fwrite`写入内容，而不是`file_put_contents`

```php
// 使用二进制模式写入，避免编码问题
$fp = fopen($debugFile, 'wb');
if ($fp) {
    // 添加UTF-8 BOM标记
    fwrite($fp, "\xEF\xBB\xBF");
    fwrite($fp, $content);
    fclose($fp);
    $fileCount++;
}
```

### 3. 作者名称编码问题

在榜单生成过程中，也存在与编码相关的问题，导致榜单中出现空行或乱码。修复方法：

- 添加编码检查和转换：使用`mb_check_encoding`和`mb_convert_encoding`确保作者名称使用UTF-8编码
- 为所有字符串操作添加了UTF-8编码参数：如`mb_strpos`、`mb_substr`等
- 为所有正则表达式添加了Unicode支持标志`/u`
- 添加了控制字符过滤：使用`preg_replace('/[\x00-\x1F\x7F]/u', '', $authorName)`移除可能导致问题的控制字符

```php
// 确保作者名使用UTF-8编码
if (!mb_check_encoding($author, 'UTF-8')) {
    // 尝试转换编码
    $author = mb_convert_encoding($author, 'UTF-8', 'auto');
}

// 过滤掉可能导致问题的控制字符
$author = preg_replace('/[\x00-\x1F\x7F]/u', '', $author);
```

## 使用方法

1. 确保系统处于调试模式（DEBUG_MODE = true）
2. 以管理员身份登录（级别1+）
3. 访问作者光荣榜页面
4. 点击"生成调试内容"按钮
5. 系统会在logs目录下创建一个名为"ranking_时间戳"的文件夹
6. 文件夹中包含每个作者的调试文件
7. 生成完成后，系统会显示成功消息，包含生成的文件数量和保存位置

## 注意事项

- 此功能仅在调试模式下可用
- 需要管理员权限（级别1+）
- 调试文件保存在logs目录下，确保该目录具有写入权限
- 文件名中的非法字符会被替换为下划线
- 包含中文或其他非ASCII字符的作者名会使用MD5哈希作为文件名前缀
