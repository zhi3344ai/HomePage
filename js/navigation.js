/**
 * 导航功能模块
 * 处理导航项目的交互、搜索和过滤功能
 */

// 导航模块
const NavigationModule = {
    searchInput: null,
    categories: [],
    allItems: [],
    filteredItems: [],
    
    /**
     * 初始化导航模块
     */
    init() {
        // 移除搜索栏功能
        // this.createSearchBar();
        this.bindEvents();
        this.initKeyboardNavigation();
    },
    
    /**
     * 创建搜索栏
     */
    createSearchBar() {
        const navContainer = document.querySelector('.nav-container');
        if (!navContainer) return;
        
        const searchContainer = document.createElement('div');
        searchContainer.className = 'search-container';
        searchContainer.innerHTML = `
            <div class="search-input-wrapper">
                <span class="search-icon">🔍</span>
                <input type="text" 
                       id="nav-search" 
                       class="search-input" 
                       placeholder="搜索导航项目..."
                       autocomplete="off">
                <button class="search-clear" id="search-clear" style="display: none;">×</button>
            </div>
            <div class="search-suggestions" id="search-suggestions"></div>
        `;
        
        // 插入到导航标题后面
        const navHeader = navContainer.querySelector('.nav-header');
        if (navHeader) {
            navHeader.insertAdjacentElement('afterend', searchContainer);
        }
        
        this.searchInput = document.getElementById('nav-search');
        
        // 添加搜索样式
        this.addSearchStyles();
    },
    
    /**
     * 添加搜索相关样式
     */
    addSearchStyles() {
        const style = document.createElement('style');
        style.textContent = `
            .search-container {
                width: 100%;
                max-width: 500px;
                margin: 0 auto var(--space-lg);
                position: relative;
            }
            
            .search-input-wrapper {
                position: relative;
                display: flex;
                align-items: center;
                background: var(--glass-bg);
                backdrop-filter: blur(20px);
                border: 1px solid var(--glass-border);
                border-radius: var(--radius-md);
                padding: var(--space-sm);
                transition: all var(--transition-normal);
            }
            
            .search-input-wrapper:focus-within {
                border-color: var(--primary-color);
                box-shadow: var(--shadow-glow);
            }
            
            .search-icon {
                margin-right: var(--space-sm);
                color: var(--text-muted);
                font-size: 16px;
            }
            
            .search-input {
                flex: 1;
                background: none;
                border: none;
                outline: none;
                color: var(--text-primary);
                font-size: var(--font-size-base);
                font-family: var(--font-family);
            }
            
            .search-input::placeholder {
                color: var(--text-muted);
            }
            
            .search-clear {
                background: none;
                border: none;
                color: var(--text-muted);
                font-size: 20px;
                cursor: pointer;
                padding: 0;
                width: 20px;
                height: 20px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 50%;
                transition: all var(--transition-fast);
            }
            
            .search-clear:hover {
                background: var(--surface-color);
                color: var(--text-primary);
            }
            
            .search-suggestions {
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: var(--glass-bg);
                backdrop-filter: blur(20px);
                border: 1px solid var(--glass-border);
                border-radius: var(--radius-md);
                margin-top: var(--space-xs);
                max-height: 300px;
                overflow-y: auto;
                z-index: 100;
                display: none;
            }
            
            .search-suggestions.active {
                display: block;
            }
            
            .suggestion-item {
                display: flex;
                align-items: center;
                gap: var(--space-sm);
                padding: var(--space-sm);
                cursor: pointer;
                transition: all var(--transition-fast);
                border-bottom: 1px solid var(--glass-border);
            }
            
            .suggestion-item:last-child {
                border-bottom: none;
            }
            
            .suggestion-item:hover,
            .suggestion-item.highlighted {
                background: var(--surface-color);
            }
            
            .suggestion-icon {
                font-size: 16px;
                width: 20px;
                text-align: center;
            }
            
            .suggestion-content {
                flex: 1;
            }
            
            .suggestion-name {
                font-weight: 500;
                margin-bottom: 2px;
            }
            
            .suggestion-description {
                font-size: var(--font-size-sm);
                color: var(--text-muted);
            }
            
            .suggestion-category {
                font-size: var(--font-size-xs);
                color: var(--primary-color);
                background: var(--surface-color);
                padding: 2px 6px;
                border-radius: 4px;
                white-space: nowrap;
            }
            
            .no-results {
                padding: var(--space-lg);
                text-align: center;
                color: var(--text-muted);
            }
            
            .search-highlight {
                background: var(--primary-color);
                color: var(--background-color);
                padding: 1px 2px;
                border-radius: 2px;
            }
            
            @media (max-width: 768px) {
                .search-container {
                    max-width: 100%;
                }
                
                .search-suggestions {
                    max-height: 200px;
                }
            }
        `;
        document.head.appendChild(style);
    },
    
    /**
     * 绑定事件
     */
    bindEvents() {
        if (!this.searchInput) return;
        
        // 搜索输入事件
        this.searchInput.addEventListener('input', (e) => {
            this.handleSearch(e.target.value);
        });
        
        // 搜索框焦点事件
        this.searchInput.addEventListener('focus', () => {
            if (this.searchInput.value.trim()) {
                this.showSuggestions();
            }
        });
        
        // 点击外部关闭建议
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.search-container')) {
                this.hideSuggestions();
            }
        });
        
        // 清除按钮
        const clearBtn = document.getElementById('search-clear');
        if (clearBtn) {
            clearBtn.addEventListener('click', () => {
                this.clearSearch();
            });
        }
        
        // 键盘导航
        this.searchInput.addEventListener('keydown', (e) => {
            this.handleKeyboardNavigation(e);
        });
    },
    
    /**
     * 处理搜索
     */
    handleSearch(query) {
        const clearBtn = document.getElementById('search-clear');
        
        if (query.trim()) {
            clearBtn.style.display = 'block';
            this.performSearch(query);
            this.showSuggestions();
        } else {
            clearBtn.style.display = 'none';
            this.clearSearch();
        }
    },
    
    /**
     * 执行搜索
     */
    performSearch(query) {
        if (!window.futuristicNav.config) return;
        
        const searchTerm = query.toLowerCase();
        const suggestions = [];
        
        // 搜索所有导航项目
        window.futuristicNav.config.navigation.categories.forEach(category => {
            category.items.forEach(item => {
                const score = this.calculateRelevanceScore(item, searchTerm, category.name);
                if (score > 0) {
                    suggestions.push({
                        ...item,
                        category: category.name,
                        categoryColor: category.color,
                        score
                    });
                }
            });
        });
        
        // 按相关性排序
        suggestions.sort((a, b) => b.score - a.score);
        
        this.displaySuggestions(suggestions, searchTerm);
    },
    
    /**
     * 计算相关性分数
     */
    calculateRelevanceScore(item, searchTerm, categoryName) {
        let score = 0;
        const name = item.name.toLowerCase();
        const description = item.description.toLowerCase();
        const tags = item.tags ? item.tags.join(' ').toLowerCase() : '';
        const category = categoryName.toLowerCase();
        
        // 名称完全匹配
        if (name === searchTerm) score += 100;
        // 名称开头匹配
        else if (name.startsWith(searchTerm)) score += 80;
        // 名称包含
        else if (name.includes(searchTerm)) score += 60;
        
        // 描述匹配
        if (description.includes(searchTerm)) score += 40;
        
        // 标签匹配
        if (tags.includes(searchTerm)) score += 30;
        
        // 分类匹配
        if (category.includes(searchTerm)) score += 20;
        
        return score;
    },
    
    /**
     * 显示搜索建议
     */
    displaySuggestions(suggestions, searchTerm) {
        const suggestionsContainer = document.getElementById('search-suggestions');
        if (!suggestionsContainer) return;
        
        if (suggestions.length === 0) {
            suggestionsContainer.innerHTML = `
                <div class="no-results">
                    <div>未找到相关结果</div>
                    <div style="font-size: var(--font-size-sm); margin-top: var(--space-xs);">
                        尝试使用其他关键词搜索
                    </div>
                </div>
            `;
        } else {
            suggestionsContainer.innerHTML = suggestions.slice(0, 8).map((item, index) => `
                <div class="suggestion-item" data-url="${item.url}" data-index="${index}">
                    <span class="suggestion-icon">${item.icon}</span>
                    <div class="suggestion-content">
                        <div class="suggestion-name">${this.highlightText(item.name, searchTerm)}</div>
                        <div class="suggestion-description">${this.highlightText(item.description, searchTerm)}</div>
                    </div>
                    <div class="suggestion-category" style="color: ${item.categoryColor}">
                        ${item.category}
                    </div>
                </div>
            `).join('');
            
            // 绑定点击事件
            suggestionsContainer.querySelectorAll('.suggestion-item').forEach(item => {
                item.addEventListener('click', () => {
                    window.open(item.dataset.url, '_blank');
                    this.hideSuggestions();
                    this.searchInput.blur();
                });
            });
        }
    },
    
    /**
     * 高亮搜索词
     */
    highlightText(text, searchTerm) {
        if (!searchTerm) return text;
        
        const regex = new RegExp(`(${searchTerm})`, 'gi');
        return text.replace(regex, '<span class="search-highlight">$1</span>');
    },
    
    /**
     * 显示建议
     */
    showSuggestions() {
        const suggestionsContainer = document.getElementById('search-suggestions');
        if (suggestionsContainer) {
            suggestionsContainer.classList.add('active');
        }
    },
    
    /**
     * 隐藏建议
     */
    hideSuggestions() {
        const suggestionsContainer = document.getElementById('search-suggestions');
        if (suggestionsContainer) {
            suggestionsContainer.classList.remove('active');
        }
        this.currentHighlight = -1;
    },
    
    /**
     * 清除搜索
     */
    clearSearch() {
        this.searchInput.value = '';
        this.hideSuggestions();
        document.getElementById('search-clear').style.display = 'none';
        this.searchInput.focus();
    },
    
    /**
     * 初始化键盘导航
     */
    initKeyboardNavigation() {
        this.currentHighlight = -1;
        
        // 全局快捷键
        document.addEventListener('keydown', (e) => {
            // Ctrl/Cmd + K 聚焦搜索框
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                this.searchInput.focus();
            }
            
            // / 键聚焦搜索框
            if (e.key === '/' && !e.target.matches('input, textarea')) {
                e.preventDefault();
                this.searchInput.focus();
            }
        });
    },
    
    /**
     * 处理键盘导航
     */
    handleKeyboardNavigation(e) {
        const suggestions = document.querySelectorAll('.suggestion-item');
        
        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                this.currentHighlight = Math.min(this.currentHighlight + 1, suggestions.length - 1);
                this.updateHighlight(suggestions);
                break;
                
            case 'ArrowUp':
                e.preventDefault();
                this.currentHighlight = Math.max(this.currentHighlight - 1, -1);
                this.updateHighlight(suggestions);
                break;
                
            case 'Enter':
                e.preventDefault();
                if (this.currentHighlight >= 0 && suggestions[this.currentHighlight]) {
                    suggestions[this.currentHighlight].click();
                }
                break;
                
            case 'Escape':
                this.hideSuggestions();
                this.searchInput.blur();
                break;
        }
    },
    
    /**
     * 更新高亮状态
     */
    updateHighlight(suggestions) {
        suggestions.forEach((item, index) => {
            item.classList.toggle('highlighted', index === this.currentHighlight);
        });
        
        // 滚动到可见区域
        if (this.currentHighlight >= 0 && suggestions[this.currentHighlight]) {
            suggestions[this.currentHighlight].scrollIntoView({
                block: 'nearest',
                behavior: 'smooth'
            });
        }
    }
};

// 当DOM加载完成后初始化导航模块
document.addEventListener('DOMContentLoaded', () => {
    // 延迟初始化，确保主应用已经渲染完成
    setTimeout(() => {
        NavigationModule.init();
    }, 1000);
});

// 导出模块
window.NavigationModule = NavigationModule;