/**
 * 管理后台JavaScript文件
 * 版本: 1.0.0
 */

// 全局变量
window.AdminApp = {
    // 配置
    config: {
        apiUrl: 'api/',
        uploadUrl: 'upload.php',
        maxFileSize: 5 * 1024 * 1024, // 5MB
        allowedFileTypes: ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp']
    },
    
    // 工具函数
    utils: {},
    
    // 组件
    components: {},
    
    // 初始化
    init: function() {
        this.initEventListeners();
        this.initComponents();
        this.initTooltips();
    },
    
    // 初始化事件监听器
    initEventListeners: function() {
        // 表单提交
        document.addEventListener('submit', this.handleFormSubmit.bind(this));
        
        // 文件上传
        document.addEventListener('change', this.handleFileUpload.bind(this));
        
        // 确认删除
        document.addEventListener('click', this.handleDeleteConfirm.bind(this));
        
        // 表格排序
        document.addEventListener('click', this.handleTableSort.bind(this));
        
        // 搜索
        document.addEventListener('input', this.handleSearch.bind(this));
        
        // 键盘快捷键
        document.addEventListener('keydown', this.handleKeyboardShortcuts.bind(this));
    },
    
    // 初始化组件
    initComponents: function() {
        this.initColorPickers();
        this.initDatePickers();
        this.initRichTextEditors();
        this.initImagePreview();
        this.initDragAndDrop();
    },
    
    // 初始化提示框
    initTooltips: function() {
        const tooltips = document.querySelectorAll('[data-tooltip]');
        tooltips.forEach(element => {
            element.addEventListener('mouseenter', this.showTooltip.bind(this));
            element.addEventListener('mouseleave', this.hideTooltip.bind(this));
        });
    },
    
    // 处理表单提交
    handleFormSubmit: function(e) {
        const form = e.target;
        if (!form.matches('form[data-ajax]')) return;
        
        e.preventDefault();
        this.submitFormAjax(form);
    },
    
    // 处理文件上传
    handleFileUpload: function(e) {
        const input = e.target;
        if (!input.matches('input[type="file"]')) return;
        
        const files = input.files;
        if (files.length === 0) return;
        
        this.validateAndPreviewFiles(input, files);
    },
    
    // 处理删除确认
    handleDeleteConfirm: function(e) {
        const button = e.target.closest('[data-confirm]');
        if (!button) return;
        
        e.preventDefault();
        
        const message = button.dataset.confirm || '确定要删除吗？';
        if (confirm(message)) {
            if (button.href) {
                window.location.href = button.href;
            } else if (button.form) {
                button.form.submit();
            }
        }
    },
    
    // 处理表格排序
    handleTableSort: function(e) {
        const th = e.target.closest('th[data-sort]');
        if (!th) return;
        
        const table = th.closest('table');
        const column = th.dataset.sort;
        const currentOrder = th.dataset.order || 'asc';
        const newOrder = currentOrder === 'asc' ? 'desc' : 'asc';
        
        this.sortTable(table, column, newOrder);
        th.dataset.order = newOrder;
    },
    
    // 处理搜索
    handleSearch: function(e) {
        const input = e.target;
        if (!input.matches('[data-search]')) return;
        
        const target = input.dataset.search;
        const query = input.value.toLowerCase();
        
        this.filterElements(target, query);
    },
    
    // 处理键盘快捷键
    handleKeyboardShortcuts: function(e) {
        // Ctrl+S 保存
        if (e.ctrlKey && e.key === 's') {
            e.preventDefault();
            const form = document.querySelector('form');
            if (form) form.submit();
        }
        
        // Escape 关闭模态框
        if (e.key === 'Escape') {
            this.closeModal();
        }
    },
    
    // AJAX表单提交
    submitFormAjax: function(form) {
        const formData = new FormData(form);
        const url = form.action || window.location.href;
        const method = form.method || 'POST';
        
        // 显示加载状态
        this.showLoading(form);
        
        fetch(url, {
            method: method,
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            this.hideLoading(form);
            
            if (data.success) {
                this.showToast(data.message || '操作成功', 'success');
                
                // 重定向或刷新
                if (data.redirect) {
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1000);
                } else if (data.reload) {
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                }
            } else {
                this.showToast(data.message || '操作失败', 'error');
                this.showFormErrors(form, data.errors || {});
            }
        })
        .catch(error => {
            this.hideLoading(form);
            this.showToast('网络错误，请重试', 'error');
            console.error('Form submission error:', error);
        });
    },
    
    // 验证和预览文件
    validateAndPreviewFiles: function(input, files) {
        const preview = document.querySelector(`[data-preview="${input.id}"]`);
        
        Array.from(files).forEach(file => {
            // 验证文件类型
            const extension = file.name.split('.').pop().toLowerCase();
            if (!this.config.allowedFileTypes.includes(extension)) {
                this.showToast(`不支持的文件类型: ${extension}`, 'error');
                input.value = '';
                return;
            }
            
            // 验证文件大小
            if (file.size > this.config.maxFileSize) {
                this.showToast(`文件大小超出限制: ${this.formatFileSize(file.size)}`, 'error');
                input.value = '';
                return;
            }
            
            // 预览图片
            if (preview && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });
    },
    
    // 表格排序
    sortTable: function(table, column, order) {
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        
        rows.sort((a, b) => {
            const aValue = a.querySelector(`[data-sort-value="${column}"]`)?.textContent || 
                          a.cells[parseInt(column)]?.textContent || '';
            const bValue = b.querySelector(`[data-sort-value="${column}"]`)?.textContent || 
                          b.cells[parseInt(column)]?.textContent || '';
            
            const comparison = aValue.localeCompare(bValue, 'zh-CN', { numeric: true });
            return order === 'asc' ? comparison : -comparison;
        });
        
        rows.forEach(row => tbody.appendChild(row));
        
        // 更新排序指示器
        table.querySelectorAll('th[data-sort]').forEach(th => {
            th.classList.remove('sort-asc', 'sort-desc');
        });
        
        const currentTh = table.querySelector(`th[data-sort="${column}"]`);
        if (currentTh) {
            currentTh.classList.add(`sort-${order}`);
        }
    },
    
    // 过滤元素
    filterElements: function(target, query) {
        const elements = document.querySelectorAll(target);
        
        elements.forEach(element => {
            const text = element.textContent.toLowerCase();
            const matches = text.includes(query);
            
            element.style.display = matches ? '' : 'none';
        });
    },
    
    // 显示加载状态
    showLoading: function(form) {
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner"></span> 处理中...';
        }
    },
    
    // 隐藏加载状态
    hideLoading: function(form) {
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = submitBtn.dataset.originalText || '提交';
        }
    },
    
    // 显示表单错误
    showFormErrors: function(form, errors) {
        // 清除之前的错误
        form.querySelectorAll('.is-invalid').forEach(el => {
            el.classList.remove('is-invalid');
        });
        form.querySelectorAll('.invalid-feedback').forEach(el => {
            el.remove();
        });
        
        // 显示新错误
        Object.keys(errors).forEach(field => {
            const input = form.querySelector(`[name="${field}"]`);
            if (input) {
                input.classList.add('is-invalid');
                
                const feedback = document.createElement('div');
                feedback.className = 'invalid-feedback';
                feedback.textContent = errors[field];
                input.parentNode.appendChild(feedback);
            }
        });
    },
    
    // 显示Toast消息
    showToast: function(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        
        const icons = {
            success: '✅',
            error: '❌',
            warning: '⚠️',
            info: 'ℹ️'
        };
        
        toast.innerHTML = `
            <div class="toast-content">
                <span class="toast-icon">${icons[type] || icons.info}</span>
                <span class="toast-message">${message}</span>
            </div>
            <button class="toast-close" onclick="this.parentNode.remove()">×</button>
        `;
        
        document.body.appendChild(toast);
        
        // 显示动画
        setTimeout(() => toast.classList.add('show'), 100);
        
        // 自动关闭
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 5000);
    },
    
    // 显示提示框
    showTooltip: function(e) {
        const element = e.target;
        const text = element.dataset.tooltip;
        
        const tooltip = document.createElement('div');
        tooltip.className = 'tooltip';
        tooltip.textContent = text;
        
        document.body.appendChild(tooltip);
        
        const rect = element.getBoundingClientRect();
        tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
        tooltip.style.top = rect.top - tooltip.offsetHeight - 5 + 'px';
        
        element._tooltip = tooltip;
    },
    
    // 隐藏提示框
    hideTooltip: function(e) {
        const element = e.target;
        if (element._tooltip) {
            element._tooltip.remove();
            delete element._tooltip;
        }
    },
    
    // 初始化颜色选择器
    initColorPickers: function() {
        const colorInputs = document.querySelectorAll('input[type="color"]');
        colorInputs.forEach(input => {
            const wrapper = document.createElement('div');
            wrapper.className = 'color-picker-wrapper';
            
            input.parentNode.insertBefore(wrapper, input);
            wrapper.appendChild(input);
            
            const preview = document.createElement('div');
            preview.className = 'color-preview';
            preview.style.backgroundColor = input.value;
            wrapper.appendChild(preview);
            
            input.addEventListener('change', function() {
                preview.style.backgroundColor = this.value;
            });
        });
    },
    
    // 初始化日期选择器
    initDatePickers: function() {
        const dateInputs = document.querySelectorAll('input[type="date"], input[type="datetime-local"]');
        dateInputs.forEach(input => {
            // 这里可以集成第三方日期选择器库
            // 例如 flatpickr 或 date-fns
        });
    },
    
    // 初始化富文本编辑器
    initRichTextEditors: function() {
        const textareas = document.querySelectorAll('textarea[data-editor="rich"]');
        textareas.forEach(textarea => {
            // 这里可以集成富文本编辑器
            // 例如 TinyMCE 或 Quill
        });
    },
    
    // 初始化图片预览
    initImagePreview: function() {
        const imageInputs = document.querySelectorAll('input[type="file"][accept*="image"]');
        imageInputs.forEach(input => {
            const preview = document.createElement('div');
            preview.className = 'image-preview';
            preview.id = `preview-${input.id}`;
            input.parentNode.appendChild(preview);
        });
    },
    
    // 初始化拖拽上传
    initDragAndDrop: function() {
        const dropZones = document.querySelectorAll('[data-drop-zone]');
        
        dropZones.forEach(zone => {
            zone.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.classList.add('drag-over');
            });
            
            zone.addEventListener('dragleave', function(e) {
                e.preventDefault();
                this.classList.remove('drag-over');
            });
            
            zone.addEventListener('drop', function(e) {
                e.preventDefault();
                this.classList.remove('drag-over');
                
                const files = e.dataTransfer.files;
                const input = this.querySelector('input[type="file"]');
                
                if (input && files.length > 0) {
                    input.files = files;
                    input.dispatchEvent(new Event('change'));
                }
            });
        });
    },
    
    // 关闭模态框
    closeModal: function() {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            modal.style.display = 'none';
        });
    },
    
    // 格式化文件大小
    formatFileSize: function(bytes) {
        const units = ['B', 'KB', 'MB', 'GB'];
        let size = bytes;
        let unitIndex = 0;
        
        while (size >= 1024 && unitIndex < units.length - 1) {
            size /= 1024;
            unitIndex++;
        }
        
        return `${size.toFixed(1)} ${units[unitIndex]}`;
    },
    
    // 防抖函数
    debounce: function(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },
    
    // 节流函数
    throttle: function(func, limit) {
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
};

// 工具函数
AdminApp.utils = {
    // 获取URL参数
    getUrlParam: function(name) {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get(name);
    },
    
    // 设置URL参数
    setUrlParam: function(name, value) {
        const url = new URL(window.location);
        url.searchParams.set(name, value);
        window.history.pushState({}, '', url);
    },
    
    // 复制到剪贴板
    copyToClipboard: function(text) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(() => {
                AdminApp.showToast('已复制到剪贴板', 'success');
            });
        } else {
            // 兼容旧浏览器
            const textarea = document.createElement('textarea');
            textarea.value = text;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            AdminApp.showToast('已复制到剪贴板', 'success');
        }
    },
    
    // 格式化日期
    formatDate: function(date, format = 'YYYY-MM-DD HH:mm:ss') {
        const d = new Date(date);
        const year = d.getFullYear();
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        const hours = String(d.getHours()).padStart(2, '0');
        const minutes = String(d.getMinutes()).padStart(2, '0');
        const seconds = String(d.getSeconds()).padStart(2, '0');
        
        return format
            .replace('YYYY', year)
            .replace('MM', month)
            .replace('DD', day)
            .replace('HH', hours)
            .replace('mm', minutes)
            .replace('ss', seconds);
    },
    
    // 验证邮箱
    validateEmail: function(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    },
    
    // 验证URL
    validateUrl: function(url) {
        try {
            new URL(url);
            return true;
        } catch {
            return false;
        }
    }
};

// 页面加载完成后初始化
document.addEventListener('DOMContentLoaded', function() {
    AdminApp.init();
    
    // 保存提交按钮的原始文本
    document.querySelectorAll('button[type="submit"]').forEach(btn => {
        btn.dataset.originalText = btn.innerHTML;
    });
});

// 导出到全局
window.AdminApp = AdminApp;