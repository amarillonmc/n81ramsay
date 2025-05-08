/**
 * RAMSAY 脚本文件
 */

document.addEventListener('DOMContentLoaded', function() {
    // 警告框自动关闭
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            alert.style.opacity = '0';
            setTimeout(function() {
                alert.style.display = 'none';
            }, 500);
        }, 5000);
    });
    
    // 卡片搜索表单
    const searchForm = document.getElementById('search-form');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            const keyword = document.getElementById('keyword').value.trim();
            if (keyword === '') {
                e.preventDefault();
                alert('请输入搜索关键词');
            }
        });
    }
    
    // 投票表单
    const voteForm = document.getElementById('vote-form');
    if (voteForm) {
        voteForm.addEventListener('submit', function(e) {
            const userId = document.getElementById('user_id').value.trim();
            if (userId === '') {
                e.preventDefault();
                alert('请输入您的ID');
            }
        });
    }
    
    // 创建投票表单
    const createVoteForm = document.getElementById('create-vote-form');
    if (createVoteForm) {
        createVoteForm.addEventListener('submit', function(e) {
            const initiatorId = document.getElementById('initiator_id').value.trim();
            if (initiatorId === '') {
                e.preventDefault();
                alert('请输入您的ID');
            }
        });
    }
    
    // 管理员登录表单
    const loginForm = document.getElementById('login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            
            if (username === '') {
                e.preventDefault();
                alert('请输入用户名');
            } else if (password === '') {
                e.preventDefault();
                alert('请输入密码');
            }
        });
    }
    
    // 关闭投票确认
    const closeVoteButtons = document.querySelectorAll('.close-vote-btn');
    closeVoteButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            if (!confirm('确定要关闭此投票吗？')) {
                e.preventDefault();
            }
        });
    });
    
    // 重置投票确认
    const resetVoteButton = document.getElementById('reset-vote-btn');
    if (resetVoteButton) {
        resetVoteButton.addEventListener('click', function(e) {
            if (!confirm('确定要重置所有投票并增加投票周期吗？此操作不可撤销！')) {
                e.preventDefault();
            }
        });
    }
    
    // 更新禁卡表确认
    const updateBanlistButton = document.getElementById('update-banlist-btn');
    if (updateBanlistButton) {
        updateBanlistButton.addEventListener('click', function(e) {
            if (!confirm('确定要更新禁卡表吗？')) {
                e.preventDefault();
            }
        });
    }
});
