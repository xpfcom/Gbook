/**
 * 留言板JavaScript功能模块
 * 统一管理所有前端交互功能
 */

class GuestbookApp {
    constructor() {
        this.init();
    }

    init() {
        // 等待DOM加载完成
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.bindEvents());
        } else {
            this.bindEvents();
        }
    }

    /**
     * 绑定所有事件监听器
     */
    bindEvents() {
        this.initCharacterCounter();
        this.initFormSubmission();
        this.initReplySystem();
        this.initDeleteConfirmation();
    }

    /**
     * 初始化字符计数功能
     */
    initCharacterCounter() {
        const nameInput = document.getElementById('name');
        const messageInput = document.getElementById('message');
        const nameCounter = document.getElementById('nameCounter');
        const messageCounter = document.getElementById('messageCounter');
        const submitBtn = document.getElementById('submitBtn');

        if (!nameInput || !messageInput) return;

        const updateCounter = (input, counter, maxLength) => {
            const currentLength = input.value.length;
            counter.textContent = `${currentLength}/${maxLength}`;
            
            // 添加警告样式
            counter.classList.toggle('warning', currentLength > maxLength * 0.8);
            
            return currentLength <= maxLength && currentLength > 0;
        };

        const validateForm = () => {
            const nameValid = updateCounter(nameInput, nameCounter, 20);
            const messageValid = updateCounter(messageInput, messageCounter, 600);
            const nameNotEmpty = nameInput.value.trim() !== '';
            const messageNotEmpty = messageInput.value.trim() !== '';
            
            if (submitBtn) {
                submitBtn.disabled = !(nameValid && messageValid && nameNotEmpty && messageNotEmpty);
            }
        };

        // 绑定输入事件
        nameInput.addEventListener('input', validateForm);
        messageInput.addEventListener('input', validateForm);
        
        // 初始化验证
        validateForm();
    }

    /**
     * 初始化表单提交功能
     */
    initFormSubmission() {
        const messageForm = document.getElementById('messageForm');
        const clientTimeInput = document.getElementById('client_time');
        const submitBtn = document.getElementById('submitBtn');

        if (!messageForm) return;

        // 表单提交时设置客户端时间
        messageForm.addEventListener('submit', (e) => {
            // 防止重复提交
            if (submitBtn && submitBtn.disabled) {
                e.preventDefault();
                return false;
            }

            // 设置客户端时间
            if (clientTimeInput) {
                clientTimeInput.value = this.getCurrentTime();
            }

            // 禁用提交按钮并显示加载状态
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = '提交中...';
                submitBtn.classList.add('loading');
            }

            return true;
        });
    }

    /**
     * 初始化回复系统
     */
    initReplySystem() {
        // 使用事件委托处理动态按钮
        document.addEventListener('click', (e) => {
            const target = e.target;

            // 处理回复按钮点击
            if (target.classList.contains('reply-toggle-btn')) {
                e.preventDefault();
                this.toggleReplyForm(target);
                return;
            }

            // 处理取消回复
            if (target.classList.contains('cancel-reply-btn')) {
                e.preventDefault();
                this.cancelReply(target);
                return;
            }

            // 处理提交回复
            if (target.classList.contains('submit-reply-btn')) {
                e.preventDefault();
                this.submitReply(target);
                return;
            }
        });
    }

    /**
     * 初始化删除确认功能
     */
    initDeleteConfirmation() {
        document.addEventListener('click', (e) => {
            const target = e.target;
            
            if (target.classList.contains('delete-link') && target.href) {
                e.preventDefault();
                this.confirmDelete(target);
            }
        });
    }

    /**
     * 切换回复表单显示状态
     */
    toggleReplyForm(button) {
        const messageItem = button.closest('.message-item');
        const replyForm = messageItem.querySelector('.reply-form');
        
        if (!replyForm) return;

        const isActive = replyForm.classList.contains('active');
        
        // 关闭所有其他回复表单
        document.querySelectorAll('.reply-form.active').forEach(form => {
            if (form !== replyForm) {
                form.classList.remove('active');
                const textarea = form.querySelector('textarea');
                if (textarea) textarea.value = '';
            }
        });
        
        // 切换当前表单
        if (!isActive) {
            replyForm.classList.add('active');
            const textarea = replyForm.querySelector('textarea');
            if (textarea) {
                textarea.focus();
                // 绑定输入事件来实时验证
                this.bindTextareaValidation(textarea);
            }
        } else {
            replyForm.classList.remove('active');
            const textarea = replyForm.querySelector('textarea');
            if (textarea) textarea.value = '';
        }
    }

    /**
     * 取消回复
     */
    cancelReply(button) {
        const replyForm = button.closest('.reply-form');
        if (replyForm) {
            replyForm.classList.remove('active');
            const textarea = replyForm.querySelector('textarea');
            if (textarea) textarea.value = '';
        }
    }

    /**
     * 提交回复
     */
    async submitReply(button) {
        const replyForm = button.closest('.reply-form');
        const textarea = replyForm.querySelector('textarea');
        const content = textarea.value.trim();
        const messageFile = replyForm.getAttribute('data-file');
        const messageId = replyForm.getAttribute('data-message-id');
        
        // 验证内容
        if (!content) {
            this.showAlert('回复内容不能为空', 'warning');
            textarea.focus();
            return;
        }
        
        if (content.length > 600) {
            this.showAlert('回复内容不能超过600个字符', 'warning');
            textarea.focus();
            return;
        }
        
        // 显示加载状态
        const originalText = button.textContent;
        button.disabled = true;
        button.textContent = '提交中...';
        button.classList.add('loading');
        
        try {
            const formData = new FormData();
            formData.append('file', messageFile);
            formData.append('message_id', messageId);
            formData.append('content', content);
            formData.append('time', this.getCurrentTime());
            
            const response = await fetch('reply.php', {
                method: 'POST',
                body: formData
            });
            
            if (response.ok) {
                // 成功后刷新页面
                window.location.reload();
            } else {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
        } catch (error) {
            console.error('提交回复失败:', error);
            this.showAlert('提交回复失败，请重试', 'error');
            
            // 恢复按钮状态
            button.disabled = false;
            button.textContent = originalText;
            button.classList.remove('loading');
        }
    }

    /**
     * 删除确认
     */
    confirmDelete(link) {
        if (confirm('确定要删除这条内容吗？此操作不可恢复。')) {
            // 显示加载状态
            link.textContent = '删除中...';
            link.classList.add('loading');
            
            // 执行删除
            fetch(link.href)
                .then(response => {
                    if (response.redirected) {
                        window.location.href = response.url;
                    } else {
                        window.location.reload();
                    }
                })
                .catch(error => {
                    console.error('删除失败:', error);
                    this.showAlert('删除失败，请重试', 'error');
                    link.textContent = '删除';
                    link.classList.remove('loading');
                });
        }
    }

    /**
     * 绑定文本域验证
     */
    bindTextareaValidation(textarea) {
        // 移除旧的事件监听（避免重复绑定）
        textarea.removeEventListener('input', this.textareaInputHandler);
        
        // 创建或获取计数器元素
        let counter = textarea.parentNode.querySelector('.char-counter');
        if (!counter) {
            counter = document.createElement('small');
            counter.className = 'char-counter';
            textarea.parentNode.appendChild(counter);
        }

        // 定义输入事件处理函数
        this.textareaInputHandler = () => {
            const length = textarea.value.length;
            const maxLength = 600;
            
            // 更新计数器显示
            counter.textContent = `${length}/${maxLength}`;
            counter.classList.toggle('warning', length > maxLength * 0.8);
            
            // 控制提交按钮状态
            const submitBtn = textarea.closest('.reply-form').querySelector('.submit-reply-btn');
            if (submitBtn) {
                submitBtn.disabled = length === 0 || length > maxLength;
            }
        };

        // 绑定事件并立即执行一次
        textarea.addEventListener('input', this.textareaInputHandler);
        this.textareaInputHandler();
    }

    /**
     * 显示提示消息
     */
    showAlert(message, type = 'info') {
        // 创建提示框
        const alert = document.createElement('div');
        alert.className = `alert alert-${type}`;
        alert.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            padding: 12px 20px;
            border-radius: 6px;
            color: white;
            font-weight: 500;
            max-width: 300px;
            animation: slideIn 0.3s ease-out;
        `;
        
        // 设置颜色
        const colors = {
            success: '#28a745',
            warning: '#ffc107',
            error: '#dc3545',
            info: '#17a2b8'
        };
        alert.style.backgroundColor = colors[type] || colors.info;
        
        alert.textContent = message;
        document.body.appendChild(alert);
        
        // 3秒后自动移除
        setTimeout(() => {
            if (alert.parentNode) {
                alert.style.animation = 'slideOut 0.3s ease-out';
                setTimeout(() => {
                    alert.remove();
                }, 300);
            }
        }, 3000);
        
        // 添加CSS动画
        if (!document.getElementById('alert-animations')) {
            const style = document.createElement('style');
            style.id = 'alert-animations';
            style.textContent = `
                @keyframes slideIn {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
                @keyframes slideOut {
                    from { transform: translateX(0); opacity: 1; }
                    to { transform: translateX(100%); opacity: 0; }
                }
            `;
            document.head.appendChild(style);
        }
    }

    /**
     * 获取当前时间字符串
     */
    getCurrentTime() {
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        
        return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
    }

    /**
     * 防抖函数
     */
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    /**
     * 节流函数
     */
    throttle(func, limit) {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }
}

// 全局函数 - 保持向后兼容性
window.toggleReplyForm = function(button) {
    if (window.guestbookApp) {
        window.guestbookApp.toggleReplyForm(button);
    }
};

window.cancelReply = function(button) {
    if (window.guestbookApp) {
        window.guestbookApp.cancelReply(button);
    }
};

window.submitReply = function(button) {
    if (window.guestbookApp) {
        window.guestbookApp.submitReply(button);
    }
};

window.confirmDelete = function(link) {
    if (window.guestbookApp) {
        window.guestbookApp.confirmDelete(link);
        return false;
    }
    return true;
};

// 初始化应用
window.guestbookApp = new GuestbookApp();

// 页面可见性API - 当页面重新获得焦点时刷新数据
document.addEventListener('visibilitychange', function() {
    if (!document.hidden && window.guestbookApp) {
        // 页面重新可见时，检查是否需要刷新数据
        // 这里可以添加轮询逻辑或者检查更新
    }
});

// 错误处理 - 全局错误捕获
window.addEventListener('error', function(e) {
    console.error('JavaScript Error:', e.error);
    // 在生产环境中，这里可以发送错误报告到服务器
});

// 未处理的Promise拒绝
window.addEventListener('unhandledrejection', function(e) {
    console.error('Unhandled Promise Rejection:', e.reason);
    // 在生产环境中，这里可以发送错误报告到服务器
});

// 导出模块（如果使用模块系统）
if (typeof module !== 'undefined' && module.exports) {
    module.exports = GuestbookApp;
}