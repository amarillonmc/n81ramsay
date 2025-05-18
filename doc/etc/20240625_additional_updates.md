# RAMSAY 系统其他更新 (2024年6月25-27日)

本文档整合了2024年6月25日至27日期间对RAMSAY系统的其他更新，包括投票管理按钮和临时文件处理修复。

## 目录

1. [添加投票管理按钮](#1-添加投票管理按钮)
2. [临时文件处理修复](#2-临时文件处理修复)

## 1. 添加投票管理按钮

### 需求描述
在禁卡表整理的管理页面显示的已关闭的当期投票，每一行添加两个操作按钮：
1. "重新打开"（仅限1级以上管理员）：将该投票重新打开。
2. "删除"（仅限2级以上管理员）：将该投票删除。

### 实现方案
1. 在 Vote 模型中添加重新打开和删除投票的方法
2. 在 BanlistController 中添加对应的控制器方法
3. 在禁卡表管理页面添加操作按钮
4. 添加相关的 CSS 样式

### 修改内容

#### 1.1 修改 Vote 模型
在 `includes\Models\Vote.php` 文件中，添加以下方法：

```php
/**
 * 重新打开投票
 *
 * @param int $voteId 投票ID
 * @return bool 是否成功
 */
public function reopenVote($voteId) {
    return $this->db->update(
        'votes',
        ['is_closed' => 0],
        'id = :vote_id',
        ['vote_id' => $voteId]
    ) !== false;
}

/**
 * 删除投票
 *
 * @param int $voteId 投票ID
 * @return bool 是否成功
 */
public function deleteVote($voteId) {
    // 先删除投票记录
    $this->db->delete(
        'vote_records',
        'vote_id = :vote_id',
        ['vote_id' => $voteId]
    );
    
    // 再删除投票
    return $this->db->delete(
        'votes',
        'id = :vote_id',
        ['vote_id' => $voteId]
    ) !== false;
}
```

#### 1.2 修改 BanlistController
在 `includes\Controllers\BanlistController.php` 文件中，添加以下方法：

```php
/**
 * 重新打开投票
 */
public function reopenVote() {
    // 要求管理员权限（等级1以上）
    $this->userModel->requirePermission(1);
    
    // 检查是否是POST请求
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // 获取表单数据
        $voteId = isset($_POST['vote_id']) ? (int)$_POST['vote_id'] : 0;
        
        // 重新打开投票
        $result = $this->voteModel->reopenVote($voteId);
        
        if ($result) {
            // 设置成功消息
            $_SESSION['success_message'] = '投票已重新打开';
        } else {
            // 设置错误消息
            $_SESSION['error_message'] = '重新打开投票失败';
        }
        
        // 重定向到禁卡表管理页面
        header('Location: ' . BASE_URL . '?controller=admin&action=banlist');
        exit;
    }
    
    // 如果不是POST请求，则重定向到禁卡表管理页面
    header('Location: ' . BASE_URL . '?controller=admin&action=banlist');
    exit;
}

/**
 * 删除投票
 */
public function deleteVote() {
    // 要求管理员权限（等级2以上）
    $this->userModel->requirePermission(2);
    
    // 检查是否是POST请求
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // 获取表单数据
        $voteId = isset($_POST['vote_id']) ? (int)$_POST['vote_id'] : 0;
        
        // 删除投票
        $result = $this->voteModel->deleteVote($voteId);
        
        if ($result) {
            // 设置成功消息
            $_SESSION['success_message'] = '投票已删除';
        } else {
            // 设置错误消息
            $_SESSION['error_message'] = '删除投票失败';
        }
        
        // 重定向到禁卡表管理页面
        header('Location: ' . BASE_URL . '?controller=admin&action=banlist');
        exit;
    }
    
    // 如果不是POST请求，则重定向到禁卡表管理页面
    header('Location: ' . BASE_URL . '?controller=admin&action=banlist');
    exit;
}
```

#### 1.3 修改禁卡表管理页面
在 `includes\Views\admin\banlist.php` 文件中，为每个投票结果添加操作按钮：

```php
<?php if (isset($result['vote_id'])): ?>
    <div class="vote-actions mt-2">
        <?php if ($this->userModel->hasPermission(1)): ?>
            <form action="<?php echo BASE_URL; ?>?controller=banlist&action=reopenVote" method="post" style="display: inline;">
                <input type="hidden" name="vote_id" value="<?php echo $result['vote_id']; ?>">
                <button type="submit" class="btn btn-sm btn-primary">重新打开</button>
            </form>
        <?php endif; ?>
        
        <?php if ($this->userModel->hasPermission(2)): ?>
            <form action="<?php echo BASE_URL; ?>?controller=banlist&action=deleteVote" method="post" style="display: inline;">
                <input type="hidden" name="vote_id" value="<?php echo $result['vote_id']; ?>">
                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('确定要删除此投票吗？此操作不可撤销。')">删除</button>
            </form>
        <?php endif; ?>
    </div>
<?php endif; ?>
```

#### 1.4 添加 CSS 样式
在 `assets\css\style.css` 文件中，添加以下样式：

```css
/* 投票操作按钮样式 */
.vote-actions {
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px dashed #ddd;
}

.vote-actions form {
    display: inline-block;
    margin-right: 5px;
}

.btn-sm {
    padding: 5px 10px;
    font-size: 14px;
}
```

### 效果
- 在禁卡表管理页面的投票结果中，每个投票项目下方显示操作按钮
- 1级以上管理员可以看到"重新打开"按钮，用于重新打开已关闭的投票
- 2级以上管理员可以看到"删除"按钮，用于删除投票
- 点击"删除"按钮会弹出确认对话框，防止误操作

## 2. 临时文件处理修复

### 问题描述

系统在根目录下生成了URL编码的临时文件，如：

```
C%3A%5Cn81ramsay%2Fexpansions%2Fno42.cdb
```

这些文件大小为0KB，不是预期的行为。

### 原因分析

问题出现在卡片数据库连接处理上：

1. 在卡片列表页面，URL中的`db`参数直接使用了完整的文件路径
2. 当这个路径被URL编码后，PHP尝试使用这个编码后的路径创建SQLite连接
3. 这导致在根目录下创建了URL编码的空文件

### 修复方法

#### 2.1 参数处理改进

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

#### 2.2 数据库连接改进

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

#### 2.3 URL参数改进

- 修改了`cards/index.php`视图，在URL中使用文件名而非完整路径
- 所有分页链接和表单都使用`basename($dbFile)`而非`$dbFile`

```php
<a href="<?php echo BASE_URL; ?>?db=<?php echo urlencode(basename($dbFile)); ?>">
    <?php echo $fileName; ?>
</a>
```

#### 2.4 临时文件管理

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

### 修复效果

1. 不再在根目录下生成URL编码的临时文件
2. 所有临时文件都存放在`/tmp`目录中
3. 脚本结束时自动清理无用的临时文件
4. 提高了系统的安全性和稳定性
