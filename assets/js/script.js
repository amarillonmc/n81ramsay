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
            const advancedPanel = document.getElementById('advanced-search-panel');
            const hasAdvancedFilters = advancedPanel && advancedPanel.classList.contains('active') && hasAnyAdvancedFilter();

            // 如果没有关键词且没有高级筛选条件，则阻止提交
            if (keyword === '' && !hasAdvancedFilters) {
                e.preventDefault();
                alert('请输入搜索关键词或使用高级检索');
            }
        });
    }

    // 检查是否有任何高级筛选条件
    function hasAnyAdvancedFilter() {
        const filterFields = ['card_type', 'attribute', 'spell_trap_type', 'race', 'type_include', 'type_exclude', 'level', 'scale', 'link_value', 'link_markers', 'atk_min', 'atk_max', 'def_min', 'def_max'];
        for (const field of filterFields) {
            const input = document.getElementById(field);
            if (input && input.value.trim() !== '') {
                return true;
            }
        }
        return false;
    }

    // 高级检索面板切换
    const advancedSearchToggle = document.getElementById('advanced-search-toggle');
    const advancedSearchPanel = document.getElementById('advanced-search-panel');

    if (advancedSearchToggle && advancedSearchPanel) {
        // 检查URL参数，如果有高级筛选参数则自动展开
        const urlParams = new URLSearchParams(window.location.search);
        const advancedParams = ['card_type', 'attribute', 'spell_trap_type', 'race', 'type_include', 'type_exclude', 'level', 'scale', 'link_value', 'link_markers', 'atk_min', 'atk_max', 'def_min', 'def_max'];
        let hasAdvancedParams = false;
        for (const param of advancedParams) {
            if (urlParams.has(param) && urlParams.get(param) !== '') {
                hasAdvancedParams = true;
                break;
            }
        }

        if (hasAdvancedParams) {
            advancedSearchToggle.classList.add('active');
            advancedSearchPanel.classList.add('active');
        }

        advancedSearchToggle.addEventListener('click', function() {
            this.classList.toggle('active');
            advancedSearchPanel.classList.toggle('active');
        });
    }

    // 卡片类型标签切换
    const searchTabs = document.querySelectorAll('.search-tab');
    const cardTypeInput = document.getElementById('card_type');

    searchTabs.forEach(function(tab) {
        tab.addEventListener('click', function() {
            searchTabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');

            const tabType = this.getAttribute('data-tab');
            if (cardTypeInput) {
                cardTypeInput.value = tabType === 'all' ? '' : tabType;
            }

            // 根据标签类型显示/隐藏相关筛选行
            updateFilterRowsVisibility(tabType);
        });
    });

    // 根据URL参数恢复标签状态
    if (cardTypeInput && cardTypeInput.value) {
        const activeTab = document.querySelector('.search-tab[data-tab="' + cardTypeInput.value + '"]');
        if (activeTab) {
            searchTabs.forEach(t => t.classList.remove('active'));
            activeTab.classList.add('active');
            updateFilterRowsVisibility(cardTypeInput.value);
        }
    }

    function updateFilterRowsVisibility(tabType) {
        const allRows = document.querySelectorAll('.search-row[data-filter]');
        allRows.forEach(function(row) {
            const filters = row.getAttribute('data-filter').split(',');
            if (tabType === 'all' || filters.includes(tabType)) {
                row.style.display = 'flex';
            } else {
                row.style.display = 'none';
            }
        });
    }

    // 筛选按钮点击处理
    document.querySelectorAll('.search-btn[data-field]').forEach(function(btn) {
        const field = btn.getAttribute('data-field');
        const value = btn.getAttribute('data-value');
        const hiddenInput = document.getElementById(field);

        // 恢复已选中状态
        if (hiddenInput) {
            const currentValues = hiddenInput.value.split(',').filter(v => v !== '');
            if (currentValues.includes(value)) {
                btn.classList.add('selected');
            }
        }

        btn.addEventListener('click', function() {
            if (!hiddenInput) return;

            this.classList.toggle('selected');

            // 更新隐藏输入框的值
            const selectedBtns = document.querySelectorAll('.search-btn[data-field="' + field + '"].selected');
            const values = Array.from(selectedBtns).map(b => b.getAttribute('data-value'));
            hiddenInput.value = values.join(',');
        });
    });

    // 清除行按钮处理
    document.querySelectorAll('.clear-row-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const row = this.closest('.search-row');
            if (!row) return;

            // 清除该行所有选中的按钮
            row.querySelectorAll('.search-btn.selected').forEach(function(selectedBtn) {
                selectedBtn.classList.remove('selected');
                const field = selectedBtn.getAttribute('data-field');
                const hiddenInput = document.getElementById(field);
                if (hiddenInput) {
                    hiddenInput.value = '';
                }
            });

            // 清除输入框
            row.querySelectorAll('.stat-range-input').forEach(function(input) {
                input.value = '';
            });
        });
    });

    // 连接标记处理
    const linkMarkers = document.querySelectorAll('.link-marker[data-marker]');
    const linkMarkersInput = document.getElementById('link_markers');

    if (linkMarkersInput) {
        // 恢复已选中状态
        const currentMarkers = linkMarkersInput.value.split(',').filter(v => v !== '');
        linkMarkers.forEach(function(marker) {
            const markerValue = marker.getAttribute('data-marker');
            if (currentMarkers.includes(markerValue)) {
                marker.classList.add('selected');
            }
        });
    }

    linkMarkers.forEach(function(marker) {
        marker.addEventListener('click', function() {
            this.classList.toggle('selected');

            // 更新隐藏输入框
            if (linkMarkersInput) {
                const selectedMarkers = document.querySelectorAll('.link-marker.selected[data-marker]');
                const values = Array.from(selectedMarkers).map(m => m.getAttribute('data-marker'));
                linkMarkersInput.value = values.join(',');
            }
        });
    });
    
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

    // 下拉菜单功能
    const dropdowns = document.querySelectorAll('.dropdown');
    dropdowns.forEach(function(dropdown) {
        const toggle = dropdown.querySelector('.dropdown-toggle');
        const menu = dropdown.querySelector('.dropdown-menu');

        if (toggle && menu) {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                // 关闭其他下拉菜单
                dropdowns.forEach(function(otherDropdown) {
                    if (otherDropdown !== dropdown) {
                        otherDropdown.classList.remove('active');
                    }
                });

                // 切换当前下拉菜单
                dropdown.classList.toggle('active');
            });
        }
    });

    // 点击其他地方关闭下拉菜单
    document.addEventListener('click', function(e) {
        dropdowns.forEach(function(dropdown) {
            if (!dropdown.contains(e.target)) {
                dropdown.classList.remove('active');
            }
        });
    });

    // 阻止下拉菜单内部点击事件冒泡
    document.querySelectorAll('.dropdown-menu').forEach(function(menu) {
        menu.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    });

    // JSON API链接复制按钮
    const copyJsonApiBtn = document.getElementById('copy-json-api-btn');
    if (copyJsonApiBtn) {
        copyJsonApiBtn.addEventListener('click', function() {
            const url = this.getAttribute('data-url');

            // 使用现代剪贴板API
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(function() {
                    showCopySuccess(copyJsonApiBtn);
                }).catch(function(err) {
                    fallbackCopyToClipboard(url, copyJsonApiBtn);
                });
            } else {
                fallbackCopyToClipboard(url, copyJsonApiBtn);
            }
        });
    }

    // 复制成功反馈
    function showCopySuccess(btn) {
        const originalText = btn.innerHTML;
        btn.classList.add('copied');
        btn.innerHTML = '<span class="json-api-icon">✓</span> 已复制';

        setTimeout(function() {
            btn.classList.remove('copied');
            btn.innerHTML = originalText;
        }, 2000);
    }

    // 降级复制方法（用于不支持Clipboard API的浏览器）
    function fallbackCopyToClipboard(text, btn) {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-9999px';
        textArea.style.top = '-9999px';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();

        try {
            const successful = document.execCommand('copy');
            if (successful) {
                showCopySuccess(btn);
            } else {
                alert('复制失败，请手动复制链接');
            }
        } catch (err) {
            alert('复制失败，请手动复制链接');
        }

        document.body.removeChild(textArea);
    }
});
