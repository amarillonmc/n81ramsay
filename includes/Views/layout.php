<?php
// 开始输出缓冲，捕获视图内容
ob_start();

// 直接获取Auth实例，检查登录状态
$auth = Auth::getInstance();
$isLoggedIn = $auth->isLoggedIn();
?>
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

                    <?php if (defined('CARD_RANKING_ENABLED') && CARD_RANKING_ENABLED): ?>
                        <li><a href="<?php echo BASE_URL; ?>?controller=card_ranking">卡片排行榜</a></li>
                    <?php endif; ?>

                    <?php if (defined('AUTHOR_HALL_OF_FAME_ENABLED') && AUTHOR_HALL_OF_FAME_ENABLED): ?>
                        <li><a href="<?php echo BASE_URL; ?>?controller=author">作者光荣榜</a></li>
                    <?php endif; ?>

                    <?php if ($isLoggedIn): ?>
                        <!-- 管理员功能链接 -->
                        <?php if ($auth->hasPermission(1)): ?>
                            <li><a href="<?php echo BASE_URL; ?>?controller=admin&action=votes">投票管理</a></li>
                            <li><a href="<?php echo BASE_URL; ?>?controller=admin&action=banlist">禁卡表整理</a></li>
                            <?php if ($auth->hasPermission(2)): ?>
                                <li><a href="<?php echo BASE_URL; ?>?controller=admin&action=authors">作者管理</a></li>
                                <li><a href="<?php echo BASE_URL; ?>?controller=admin&action=tips">服务器提示管理</a></li>
                            <?php endif; ?>
                        <?php endif; ?>

                        <!-- 用户信息和登出链接 -->
                        <li class="user-info">
                            <span><?php echo $auth->getCurrentUsername(); ?> (<?php
                                $group = $auth->getCurrentGroup();
                                switch ($group) {
                                    case 1: echo '编辑员'; break;
                                    case 2: echo '管理员'; break;
                                    case 3: echo '高级管理员'; break;
                                    case 255: echo '超级管理员'; break;
                                    default: echo '未知'; break;
                                }
                            ?>)</span>
                        </li>
                        <li><a href="<?php echo BASE_URL; ?>?controller=admin&action=logout">退出登录</a></li>
                    <?php else: ?>
                        <!-- 管理员登录链接 -->
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
