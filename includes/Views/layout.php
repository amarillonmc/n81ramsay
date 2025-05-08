<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_TITLE; ?></title>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/style.css">
</head>
<body>
    <header>
        <div class="container">
            <h1><a href="<?php echo BASE_URL; ?>"><?php echo SITE_TITLE; ?></a></h1>
            <nav>
                <ul>
                    <li><a href="<?php echo BASE_URL; ?>">卡片检索</a></li>
                    <li><a href="<?php echo BASE_URL; ?>?controller=vote">投票概览</a></li>
                    <?php if (isset($userModel) && $userModel->isLoggedIn()): ?>
                        <?php if ($userModel->hasPermission(1)): ?>
                            <li><a href="<?php echo BASE_URL; ?>?controller=admin&action=votes">投票管理</a></li>
                        <?php endif; ?>
                        <?php if ($userModel->hasPermission(1)): ?>
                            <li><a href="<?php echo BASE_URL; ?>?controller=admin&action=banlist">禁卡表整理</a></li>
                        <?php endif; ?>
                        <li class="user-info">
                            <span><?php echo $userModel->getCurrentUsername(); ?> (<?php echo $userModel->getGroupName($userModel->getCurrentGroup()); ?>)</span>
                            <a href="<?php echo BASE_URL; ?>?controller=admin&action=logout">退出登录</a>
                        </li>
                    <?php else: ?>
                        <li><a href="<?php echo BASE_URL; ?>?controller=admin&action=login">管理员登录</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <div class="container">
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <?php echo $_SESSION['success_message']; ?>
                    <?php unset($_SESSION['success_message']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-error">
                    <?php echo $_SESSION['error_message']; ?>
                    <?php unset($_SESSION['error_message']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($errors) && !empty($errors)): ?>
                <div class="alert alert-error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- 内容将在这里插入 -->
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> RAMSAY - no81游戏王DIY服务器管理系统</p>
        </div>
    </footer>

    <script src="<?php echo ASSETS_URL; ?>js/script.js"></script>
</body>
</html>
