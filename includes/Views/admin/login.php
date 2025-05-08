<h2>管理员登录</h2>

<div class="card">
    <div class="card-body">
        <form id="login-form" action="<?php echo BASE_URL; ?>admin/login<?php echo isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : ''; ?>" method="post">
            <div class="form-group">
                <label for="username">用户名</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="password">密码</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn">登录</button>
        </form>
    </div>
</div>
