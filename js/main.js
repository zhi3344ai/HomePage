/**
 * 未来科技感个人导航页 - 主要JavaScript文件
 * 版本: 2.0.0 - 支持PHP后台数据
 */

// API配置
const API_BASE = 'api/';

// 全局应用对象
window.futuristicNav = {
    config: {},
    currentTheme: 'cyberpunk',
    isInitialized: false,
    particles: null
};

// DOM加载完成后初始化
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initApp);
} else {
    setTimeout(initApp, 0);
}

/**
 * 初始化应用
 */
async function initApp() {
    try {
        console.log('🚀 开始初始化应用...');

        // 更新加载进度
        updateLoadingProgress(10);

        // 记录访问统计
        recordVisit();

        // 加载配置数据
        await loadAllData();

        updateLoadingProgress(50);

        // 更新网站标题和描述
        updateSiteMetadata();

        // 初始化主题系统
        await initThemeSystem();

        updateLoadingProgress(70);

        // 渲染页面内容
        renderProfile();
        renderNavigation();

        updateLoadingProgress(90);

        // 初始化粒子系统
        initParticles();

        // 初始化动画
        initAnimations();

        // 初始化点击特效
        initClickEffects();

        updateLoadingProgress(100);

        // 隐藏加载屏幕
        setTimeout(hideLoadingScreen, 500);

        window.futuristicNav.isInitialized = true;
        console.log('✅ 应用初始化完成');

    } catch (error) {
        console.error('❌ 应用初始化失败:', error);
        showError('应用加载失败，请刷新页面重试');
    }
}

/**
 * 加载所有数据
 */
async function loadAllData() {
    try {
        // 尝试使用新的导航API
        let navigation = null;
        let categories = null;
        
        try {
            // 尝试从新的API获取导航数据
            const navResponse = await fetch('api/navigation.php');
            if (navResponse.ok) {
                navigation = await navResponse.json();
                console.log('✅ 从新API获取导航数据成功');
            }
            
            // 尝试从新的API获取分类数据
            const catResponse = await fetch('api/navigation.php?action=categories&with_count=true');
            if (catResponse.ok) {
                categories = await catResponse.json();
                console.log('✅ 从新API获取分类数据成功');
            }
        } catch (navError) {
            console.warn('新导航API加载失败，将尝试原始API:', navError);
        }
        
        // 并行加载其他数据
        const [profile, socialLinks, themes, settings] = await Promise.all([
            fetchAPI('profile'),
            fetchAPI('social'),
            fetchAPI('theme'),
            fetchAPI('settings')
        ]);
        
        // 如果新API失败，尝试使用原始API
        if (!navigation) {
            try {
                navigation = await fetchAPI('navigation');
                console.log('✅ 从原始API获取导航数据成功');
            } catch (error) {
                console.error('导航数据加载失败:', error);
                navigation = { success: true, data: [] };
            }
        }
        
        if (!categories) {
            try {
                categories = await fetchAPI('categories', { with_count: true });
                console.log('✅ 从原始API获取分类数据成功');
            } catch (error) {
                console.error('分类数据加载失败:', error);
                categories = { success: true, data: [] };
            }
        }

        // 存储到全局配置
        window.futuristicNav.config = {
            profile: profile.data,
            socialLinks: socialLinks.data,
            categories: categories.data,
            navigation: navigation.data,
            themes: themes.data,
            settings: settings.data
        };

        console.log('📊 数据加载完成:', window.futuristicNav.config);

    } catch (error) {
        console.error('数据加载失败:', error);
        // 使用默认配置
        window.futuristicNav.config = getDefaultConfig();
    }
}

/**
 * API请求封装
 */
async function fetchAPI(action, params = {}) {
    const url = new URL(API_BASE, window.location.origin);
    url.searchParams.set('action', action);

    // 添加参数
    Object.keys(params).forEach(key => {
        url.searchParams.set(key, params[key]);
    });

    const response = await fetch(url);

    if (!response.ok) {
        throw new Error(`API请求失败: ${response.status}`);
    }

    const data = await response.json();

    if (!data.success) {
        throw new Error(data.error || 'API返回错误');
    }

    return data;
}

/**
 * 记录访问统计
 */
async function recordVisit() {
    try {
        await fetch(API_BASE + '?action=visit', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                timestamp: Date.now(),
                user_agent: navigator.userAgent,
                referrer: document.referrer
            })
        });
    } catch (error) {
        console.warn('访问统计记录失败:', error);
    }
}

/**
 * 记录导航点击
 */
async function recordClick(itemId) {
    try {
        await fetch(API_BASE + '?action=click', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                item_id: itemId
            })
        });
    } catch (error) {
        console.warn('点击统计记录失败:', error);
    }
}

/**
 * 初始化主题系统
 */
async function initThemeSystem() {
    const themes = window.futuristicNav.config.themes || [];

    // 从本地存储获取保存的主题，或使用默认主题
    const defaultTheme = themes.find(t => t.is_default) || themes[0];
    const savedTheme = localStorage.getItem('homepage-theme') || (defaultTheme ? defaultTheme.name : 'cyberpunk');

    await applyTheme(savedTheme);

    // 绑定主题切换按钮
    const themeBtn = document.getElementById('theme-toggle');
    if (themeBtn) {
        themeBtn.addEventListener('click', showThemeModal);
    }

    // 创建主题选择模态框
    createThemeModal();
}

/**
 * 应用主题
 */
async function applyTheme(themeName) {
    const themes = window.futuristicNav.config.themes || [];
    const theme = themes.find(t => t.name === themeName);

    if (theme) {
        // 应用主题颜色
        const root = document.documentElement;
        const colors = theme.colors;

        // 设置基本颜色变量
        root.style.setProperty('--primary-color', colors.primary);
        root.style.setProperty('--secondary-color', colors.secondary);
        root.style.setProperty('--background-color', colors.background);
        root.style.setProperty('--text-color', colors.text);

        if (colors.accent) {
            root.style.setProperty('--accent-color', colors.accent);
        }

        // 应用特效
        if (theme.effects) {
            if (theme.effects.blur) {
                root.style.setProperty('--blur-amount', theme.effects.blur);
            }
            if (theme.effects.glow) {
                root.style.setProperty('--glow-effect', theme.effects.glow);
            }
        }

        // 应用主题特定的样式变量
        switch(themeName) {
            case 'cyberpunk':
                // 赛博朋克主题特效
                root.style.setProperty('--theme-border-style', 'solid');
                root.style.setProperty('--theme-card-shape', 'var(--radius-lg)');
                root.style.setProperty('--theme-animation-intensity', '1');
                root.style.setProperty('--theme-font-family', "'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif");
                break;
                
            case 'aurora':
                // 极光主题特效
                root.style.setProperty('--theme-border-style', 'solid');
                root.style.setProperty('--theme-card-shape', 'var(--radius-xl)');
                root.style.setProperty('--theme-animation-intensity', '0.8');
                root.style.setProperty('--theme-font-family', "'Quicksand', 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif");
                
                // 添加流动渐变背景
                document.body.classList.add('aurora-background');
                break;
                
            case 'synthwave':
                // 合成波主题特效
                root.style.setProperty('--theme-border-style', 'solid');
                root.style.setProperty('--theme-card-shape', '0');
                root.style.setProperty('--theme-animation-intensity', '1.2');
                root.style.setProperty('--theme-font-family', "'VT323', 'Courier New', monospace");
                
                // 添加网格背景
                document.body.classList.add('synthwave-background');
                break;
                
            case 'matrix':
                // 矩阵主题特效
                root.style.setProperty('--theme-border-style', 'dashed');
                root.style.setProperty('--theme-card-shape', '0');
                root.style.setProperty('--theme-animation-intensity', '1.5');
                root.style.setProperty('--theme-font-family', "'Courier New', monospace");
                
                // 添加数字雨效果
                initMatrixEffect();
                break;
                
            case 'neon':
                // 霓虹主题特效
                root.style.setProperty('--theme-border-style', 'double');
                root.style.setProperty('--theme-card-shape', 'var(--radius-sm)');
                root.style.setProperty('--theme-animation-intensity', '1.3');
                root.style.setProperty('--theme-font-family', "'Orbitron', sans-serif");
                
                // 添加霓虹灯效果
                document.body.classList.add('neon-background');
                break;
        }

        // 移除之前的主题特效类
        document.body.classList.remove('aurora-background', 'synthwave-background', 'neon-background');
        
        // 停止之前的矩阵效果
        if (window.matrixInterval) {
            clearInterval(window.matrixInterval);
            const matrixCanvas = document.getElementById('matrix-canvas');
            if (matrixCanvas) matrixCanvas.remove();
        }

        // 如果是矩阵主题，添加矩阵效果
        if (themeName === 'matrix') {
            initMatrixEffect();
        }

        window.futuristicNav.currentTheme = themeName;
        localStorage.setItem('homepage-theme', themeName);

        // 更新主题按钮图标
        updateThemeIcon(themeName);

        console.log(`🎨 主题已切换到: ${theme.display_name}`);
    }

    document.documentElement.setAttribute('data-theme', themeName);
}

/**
 * 初始化矩阵数字雨效果
 */
function initMatrixEffect() {
    // 移除旧的矩阵画布
    const oldCanvas = document.getElementById('matrix-canvas');
    if (oldCanvas) oldCanvas.remove();
    
    // 创建新的矩阵画布
    const canvas = document.createElement('canvas');
    canvas.id = 'matrix-canvas';
    canvas.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: -1;
        opacity: 0.15;
        pointer-events: none;
    `;
    document.body.appendChild(canvas);
    
    const ctx = canvas.getContext('2d');
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
    
    // 字符集
    const chars = '01アイウエオカキクケコサシスセソタチツテトナニヌネノハヒフヘホマミムメモヤユヨラリルレロワヲン';
    const columns = Math.floor(canvas.width / 20);
    const drops = [];
    
    // 初始化每列的Y位置
    for (let i = 0; i < columns; i++) {
        drops[i] = Math.random() * -100;
    }
    
    // 绘制矩阵效果
    function drawMatrix() {
        ctx.fillStyle = 'rgba(0, 0, 0, 0.05)';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        
        ctx.fillStyle = '#00ff41';
        ctx.font = '15px monospace';
        
        for (let i = 0; i < drops.length; i++) {
            const text = chars[Math.floor(Math.random() * chars.length)];
            ctx.fillText(text, i * 20, drops[i] * 20);
            
            if (drops[i] * 20 > canvas.height && Math.random() > 0.975) {
                drops[i] = 0;
            }
            
            drops[i]++;
        }
    }
    
    // 设置定时器
    window.matrixInterval = setInterval(drawMatrix, 50);
    
    // 窗口大小改变时重新调整画布
    window.addEventListener('resize', () => {
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
    });
}

/**
 * 更新主题图标
 */
function updateThemeIcon(themeName) {
    const themeIcon = document.querySelector('.theme-icon');
    if (themeIcon) {
        const themeIcons = {
            cyberpunk: '🌐',
            aurora: '🌌',
            sunset: '🌅',
            forest: '🌲',
            synthwave: '🌆',
            matrix: '💚',
            neon: '🔴'
        };
        themeIcon.textContent = themeIcons[themeName] || '🎨';
    }
}

/**
 * 创建主题选择模态框
 */
function createThemeModal() {
    const themes = window.futuristicNav.config.themes || [];

    const modal = document.createElement('div');
    modal.className = 'theme-modal';
    modal.innerHTML = `
        <div class="theme-modal-content">
            <div class="theme-modal-header">
                <h3 class="theme-modal-title">选择主题</h3>
                <button class="theme-modal-close">&times;</button>
            </div>
            <div class="theme-options">
                ${themes.map(theme => {
                    // 根据主题名称设置不同的预览样式
                    let previewStyle = '';
                    let previewContent = '';
                    
                    switch(theme.name) {
                        case 'cyberpunk':
                            previewStyle = `background: linear-gradient(135deg, ${theme.colors.primary}, ${theme.colors.secondary});`;
                            previewContent = '<div class="preview-neon-line"></div>';
                            break;
                        case 'aurora':
                            previewStyle = `background: linear-gradient(135deg, ${theme.colors.primary}, ${theme.colors.secondary}); backdrop-filter: blur(5px);`;
                            previewContent = '<div class="preview-aurora-waves"></div>';
                            break;
                        case 'synthwave':
                            previewStyle = `background: linear-gradient(180deg, ${theme.colors.background}, ${theme.colors.primary});`;
                            previewContent = '<div class="preview-grid"></div>';
                            break;
                        case 'matrix':
                            previewStyle = `background: ${theme.colors.background}; border: 1px dashed ${theme.colors.primary};`;
                            previewContent = '<div class="preview-code">01</div>';
                            break;
                        case 'neon':
                            previewStyle = `background: ${theme.colors.background}; border: 2px double ${theme.colors.primary};`;
                            previewContent = '<div class="preview-neon-glow"></div>';
                            break;
                        default:
                            previewStyle = `background: linear-gradient(135deg, ${theme.colors.primary}, ${theme.colors.secondary});`;
                    }
                    
                    // 根据主题名称设置不同的描述
                    let description = theme.description || '精美的主题设计';
                    switch(theme.name) {
                        case 'cyberpunk':
                            description = '霓虹灯效果，锐利的边缘，高对比度的青色和品红色';
                            break;
                        case 'aurora':
                            description = '流动的渐变效果，柔和的曲线，梦幻般的蓝色和青色调';
                            break;
                        case 'synthwave':
                            description = '复古网格背景，80年代风格，紫色和橙色的强烈对比';
                            break;
                        case 'matrix':
                            description = '数字雨效果，终端风格，黑客美学，绿色代码';
                            break;
                        case 'neon':
                            description = '明亮的对比色，城市夜景风格，红色和绿色的霓虹灯效果';
                            break;
                    }
                    
                    return `
                        <div class="theme-option" data-theme="${theme.name}">
                            <div class="theme-preview" style="${previewStyle}">
                                ${previewContent}
                            </div>
                            <div class="theme-info">
                                <h3>${theme.display_name}</h3>
                                <p>${description}</p>
                                ${theme.is_default ? '<span class="theme-badge">默认</span>' : ''}
                            </div>
                        </div>
                    `;
                }).join('')}
            </div>
        </div>
    `;

    document.body.appendChild(modal);

    // 绑定事件
    modal.querySelector('.theme-modal-close').addEventListener('click', hideThemeModal);
    modal.addEventListener('click', (e) => {
        if (e.target === modal) hideThemeModal();
    });

    // 绑定主题选择事件
    modal.querySelectorAll('.theme-option').forEach(option => {
        option.addEventListener('click', async () => {
            const theme = option.dataset.theme;
            await applyTheme(theme);
            updateThemeSelection(theme);
        });
    });

    // 初始化选中状态
    updateThemeSelection(window.futuristicNav.currentTheme);
}

/**
 * 显示主题模态框
 */
function showThemeModal() {
    const modal = document.querySelector('.theme-modal');
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

/**
 * 隐藏主题模态框
 */
function hideThemeModal() {
    const modal = document.querySelector('.theme-modal');
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

/**
 * 更新主题选择状态
 */
function updateThemeSelection(selectedTheme) {
    const options = document.querySelectorAll('.theme-option');
    options.forEach(option => {
        option.classList.toggle('active', option.dataset.theme === selectedTheme);
    });
}

/**
 * 渲染个人信息
 */
function renderProfile() {
    const profile = window.futuristicNav.config.profile;
    if (!profile) return;

    // 更新头像
    const profileImg = document.getElementById('profile-img');
    if (profileImg && profile.avatar) {
        // 修正头像路径问题：API返回的是admin/img/，但实际路径是/admin/admin/img/
        if (profile.avatar.startsWith('admin/img/')) {
            // 将admin/img/替换为/admin/admin/img/
            profileImg.src = '/' + profile.avatar.replace('admin/img/', 'admin/admin/img/');
        } else if (profile.avatar.startsWith('http://') || profile.avatar.startsWith('https://')) {
            // 如果是完整URL，直接使用
            profileImg.src = profile.avatar;
        } else {
            // 其他情况直接使用
            profileImg.src = profile.avatar;
        }
        profileImg.alt = `${profile.name}的头像`;
        console.log("头像URL:", profile.avatar, "处理后:", profileImg.src); // 添加日志以便调试
    }

    // 更新基本信息
    updateTextContent('profile-name', profile.name);
    updateTextContent('profile-title', profile.title);
    updateTextContent('profile-description', profile.description);

    // 更新状态
    updateTextContent('.status-text', profile.status_text);
    const indicator = document.querySelector('.status-indicator');
    if (indicator) {
        indicator.className = `status-indicator status-${profile.status_type}`;
    }

    // 更新统计数据
    updateTextContent('projects-count', profile.projects_count);
    updateTextContent('experience-years', profile.experience_years);
    updateTextContent('skills-count', profile.skills_count);

    // 渲染社交链接
    renderSocialLinks();
}

/**
 * 渲染社交链接
 */
function renderSocialLinks() {
    const container = document.querySelector('.social-links');
    const socialLinks = window.futuristicNav.config.socialLinks;

    if (!container || !socialLinks) return;

    container.innerHTML = socialLinks.map(link => `
        <a href="${link.url}" 
           class="social-link" 
           target="_blank" 
           rel="noopener noreferrer"
           title="${link.platform}"
           style="border-color: ${link.color}20; color: ${link.color}">
            ${link.icon}
        </a>
    `).join('');
}

/**
 * 渲染导航
 */
function renderNavigation() {
    const navGrid = document.getElementById('nav-grid');
    const categories = window.futuristicNav.config.categories;
    const navigation = window.futuristicNav.config.navigation;

    if (!navGrid || !categories || !navigation) return;

    // 按分类组织导航项目
    const itemsByCategory = {};
    navigation.forEach(item => {
        if (!itemsByCategory[item.category_id]) {
            itemsByCategory[item.category_id] = [];
        }
        itemsByCategory[item.category_id].push(item);
    });

    // 渲染分类和项目
    navGrid.innerHTML = categories.map(category => {
        const items = itemsByCategory[category.id] || [];

        return `
                <div class="nav-category" data-category="${category.name}">
                    <div class="category-header">
                        <span class="category-icon">
                            ${category.icon.startsWith('/admin/') || category.icon.startsWith('admin/') || category.icon.includes('/admin/admin/') ? 
                              `<img src="${category.icon}" alt="${category.name}" style="width: 24px; height: 24px; object-fit: contain;">` : 
                              category.icon}
                        </span>
                        <h3 class="category-name" style="color: ${category.color}">${category.name}</h3>
                    </div>
                <div class="nav-items">
                    ${items.map(item => {
                        // 处理图标显示
                        let iconDisplay = '';
                        if (typeof item.icon === 'string') {
                            if (item.icon.startsWith('/admin/') || item.icon.startsWith('admin/')) {
                                // 如果是图片路径
                                iconDisplay = `<img src="${item.icon}" alt="${item.name}" style="width: 24px; height: 24px; object-fit: contain;">`;
                            } else if (item.icon.length <= 2) {
                                // 如果是emoji或单个字符
                                iconDisplay = item.icon;
                            } else {
                                // 其他情况，可能是HTML或长文本，直接使用
                                iconDisplay = item.icon;
                            }
                        }
                        
                        return `
                        <a href="${item.url}" 
                           class="nav-item" 
                           data-item-id="${item.id}"
                           target="${item.target || '_blank'}" 
                           rel="noopener noreferrer"
                           title="${item.description}">
                            <span class="nav-item-icon">${iconDisplay}</span>
                            <div class="nav-item-content">
                                <div class="nav-item-name">${item.name}</div>
                                <div class="nav-item-description">${item.description}</div>
                                ${item.tags && Array.isArray(item.tags) && item.tags.length > 0 ? `
                                    <div class="nav-item-tags">
                                        ${item.tags.map(tag => `<span class="tag">${tag}</span>`).join('')}
                                    </div>
                                ` : ''}
                            </div>
                            ${item.is_featured ? '<span class="featured-badge">推荐</span>' : ''}
                            ${item.click_count > 0 ? `<span class="click-count">${item.click_count}</span>` : ''}
                        </a>
                    `}).join('')}
                </div>
            </div>
        `;
    }).join('');

    // 添加点击统计和特效
    addClickTracking();
}

/**
 * 初始化点击特效
 */
function initClickEffects() {
    // 为所有可点击元素添加爆炸特效
    document.addEventListener('click', createExplosionEffect);
}

/**
 * 创建爆炸特效
 */
function createExplosionEffect(e) {
    // 只对特定元素添加特效
    const target = e.target.closest('.nav-item, .social-link, .theme-option, .btn, .quick-action');
    if (!target) return;

    const rect = target.getBoundingClientRect();
    const x = e.clientX;
    const y = e.clientY;

    // 创建爆炸容器
    const explosion = document.createElement('div');
    explosion.className = 'click-explosion';
    explosion.style.cssText = `
        position: fixed;
        left: ${x}px;
        top: ${y}px;
        pointer-events: none;
        z-index: 9999;
        transform: translate(-50%, -50%);
    `;

    document.body.appendChild(explosion);

    // 根据目标类型选择不同的特效
    if (target.classList.contains('social-link')) {
        createHeartExplosion(explosion);
    } else if (target.classList.contains('theme-option') || target.classList.contains('theme-btn')) {
        createStarExplosion(explosion);
    } else {
        createDefaultExplosion(explosion);
    }

    // 添加中心闪光
    const flash = document.createElement('div');
    flash.className = 'explosion-flash';
    flash.style.cssText = `
        position: absolute;
        width: 20px;
        height: 20px;
        background: radial-gradient(circle, rgba(255,255,255,0.9) 0%, transparent 70%);
        border-radius: 50%;
        transform: translate(-50%, -50%);
        animation: flash 0.3s ease-out forwards;
    `;

    explosion.appendChild(flash);

    // 添加彩虹粒子效果
    createRainbowParticles(explosion);

    // 清理
    setTimeout(() => {
        explosion.remove();
    }, 1500);
}

/**
 * 创建默认爆炸效果
 */
function createDefaultExplosion(container) {
    const particleCount = 15;
    const colors = ['#00f5ff', '#ff0080', '#00ff41', '#ffff00', '#ff4500', '#8a2be2', '#ff1493'];

    for (let i = 0; i < particleCount; i++) {
        const particle = document.createElement('div');
        const angle = (360 / particleCount) * i + Math.random() * 30;
        const velocity = 60 + Math.random() * 40;
        const size = 3 + Math.random() * 5;
        const color = colors[Math.floor(Math.random() * colors.length)];
        const life = 0.8 + Math.random() * 0.6;

        particle.className = 'explosion-particle';
        const endX = Math.cos(angle * Math.PI / 180) * velocity + 'px';
        const endY = Math.sin(angle * Math.PI / 180) * velocity + 'px';
        
        particle.style.cssText = `
            position: absolute;
            width: ${size}px;
            height: ${size}px;
            background: ${color};
            border-radius: 50%;
            box-shadow: 0 0 8px ${color}, 0 0 16px ${color}40;
            animation: explode ${life}s ease-out forwards;
            --end-x: ${endX};
            --end-y: ${endY};
        `;

        container.appendChild(particle);
    }
}

/**
 * 创建星星爆炸效果
 */
function createStarExplosion(container) {
    const starCount = 8;
    const colors = ['#ffd700', '#ffff00', '#ff69b4', '#00ffff', '#ff6347'];

    for (let i = 0; i < starCount; i++) {
        const star = document.createElement('div');
        const angle = (360 / starCount) * i;
        const velocity = 50 + Math.random() * 30;
        const size = 6 + Math.random() * 4;
        const color = colors[Math.floor(Math.random() * colors.length)];

        star.className = 'explosion-star';
        star.innerHTML = '★';
        const endX = Math.cos(angle * Math.PI / 180) * velocity + 'px';
        const endY = Math.sin(angle * Math.PI / 180) * velocity + 'px';
        const midX = Math.cos(angle * Math.PI / 180) * velocity * 0.5 + 'px';
        const midY = Math.sin(angle * Math.PI / 180) * velocity * 0.5 + 'px';
        
        star.style.cssText = `
            position: absolute;
            font-size: ${size}px;
            color: ${color};
            text-shadow: 0 0 10px ${color};
            animation: explodeStar 1.2s ease-out forwards;
            --end-x: ${endX};
            --end-y: ${endY};
            --mid-x: ${midX};
            --mid-y: ${midY};
        `;

        container.appendChild(star);
    }
}

/**
 * 创建爱心爆炸效果
 */
function createHeartExplosion(container) {
    const heartCount = 6;
    const colors = ['#ff69b4', '#ff1493', '#dc143c', '#ff6b6b', '#ff8e8e'];

    for (let i = 0; i < heartCount; i++) {
        const heart = document.createElement('div');
        const angle = (360 / heartCount) * i + Math.random() * 60;
        const velocity = 40 + Math.random() * 30;
        const size = 8 + Math.random() * 6;
        const color = colors[Math.floor(Math.random() * colors.length)];

        heart.className = 'explosion-heart';
        heart.innerHTML = '♥';
        const endX = Math.cos(angle * Math.PI / 180) * velocity + 'px';
        const endY = Math.sin(angle * Math.PI / 180) * velocity + 'px';
        const midX = Math.cos(angle * Math.PI / 180) * velocity * 0.3 + 'px';
        const midY = Math.sin(angle * Math.PI / 180) * velocity * 0.3 + 'px';
        
        heart.style.cssText = `
            position: absolute;
            font-size: ${size}px;
            color: ${color};
            text-shadow: 0 0 8px ${color};
            animation: explodeHeart 1s ease-out forwards;
            --end-x: ${endX};
            --end-y: ${endY};
            --mid-x: ${midX};
            --mid-y: ${midY};
        `;

        container.appendChild(heart);
    }
}

/**
 * 创建彩虹粒子效果
 */
function createRainbowParticles(container) {
    const particleCount = 20;
    const rainbowColors = [
        '#ff0000', '#ff7f00', '#ffff00', '#00ff00',
        '#0000ff', '#4b0082', '#9400d3', '#ff69b4'
    ];

    for (let i = 0; i < particleCount; i++) {
        const particle = document.createElement('div');
        const angle = Math.random() * 360;
        const velocity = 20 + Math.random() * 40;
        const size = 1 + Math.random() * 3;
        const color = rainbowColors[Math.floor(Math.random() * rainbowColors.length)];
        const delay = Math.random() * 0.2;

        particle.className = 'rainbow-particle';
        const endX = Math.cos(angle * Math.PI / 180) * velocity + 'px';
        const endY = Math.sin(angle * Math.PI / 180) * velocity + 'px';
        const midX = Math.cos(angle * Math.PI / 180) * velocity * 0.5 + 'px';
        const midY = Math.sin(angle * Math.PI / 180) * velocity * 0.5 + 'px';
        
        particle.style.cssText = `
            position: absolute;
            width: ${size}px;
            height: ${size}px;
            background: ${color};
            border-radius: 50%;
            box-shadow: 0 0 4px ${color};
            animation: rainbowExplode 1s ease-out ${delay}s forwards;
            --end-x: ${endX};
            --end-y: ${endY};
            --mid-x: ${midX};
            --mid-y: ${midY};
        `;

        container.appendChild(particle);
    }
}

/**
 * 创建涟漪效果
 */
function createRippleEffect(element, e) {
    const rect = element.getBoundingClientRect();
    const size = Math.max(rect.width, rect.height);
    const x = e.clientX - rect.left - size / 2;
    const y = e.clientY - rect.top - size / 2;

    const ripple = document.createElement('div');
    ripple.className = 'ripple-effect';
    ripple.style.cssText = `
        position: absolute;
        width: ${size}px;
        height: ${size}px;
        left: ${x}px;
        top: ${y}px;
        background: radial-gradient(circle, rgba(0, 245, 255, 0.3) 0%, transparent 70%);
        border-radius: 50%;
        transform: scale(0);
        animation: ripple 0.6s ease-out;
        pointer-events: none;
        z-index: 1;
    `;

    // 确保元素有相对定位
    if (getComputedStyle(element).position === 'static') {
        element.style.position = 'relative';
    }

    element.appendChild(ripple);

    setTimeout(() => {
        ripple.remove();
    }, 600);
}

/**
 * 添加点击统计
 */
function addClickTracking() {
    document.querySelectorAll('.nav-item').forEach(item => {
        item.addEventListener('click', (e) => {
            const itemId = item.dataset.itemId;
            const name = item.querySelector('.nav-item-name').textContent;

            console.log(`导航项目被点击: ${name}`);

            // 创建涟漪效果
            createRippleEffect(item, e);

            // 记录点击统计
            if (itemId) {
                recordClick(itemId);
            }

            // Google Analytics
            if (typeof gtag !== 'undefined') {
                gtag('event', 'click', {
                    event_category: 'navigation',
                    event_label: name
                });
            }
        });
    });

    // 为社交链接添加涟漪效果
    document.querySelectorAll('.social-link').forEach(link => {
        link.addEventListener('click', (e) => {
            createRippleEffect(link, e);
        });
    });
}

/**
 * 初始化粒子系统
 */
function initParticles() {
    const settings = window.futuristicNav.config.settings;
    if (!settings.particles_enabled) return;

    const canvas = document.getElementById('particles-canvas');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    const particles = [];
    // 直接使用设置中的粒子数量，不再设置上限
    const particleCount = parseInt(settings.particle_count) || 50;
    
    console.log('粒子数量设置为:', particleCount);

    // 设置画布大小
    function resizeCanvas() {
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
    }

    resizeCanvas();
    window.addEventListener('resize', resizeCanvas);

    // 粒子类
    class Particle {
        constructor() {
            this.x = Math.random() * canvas.width;
            this.y = Math.random() * canvas.height;
            this.vx = (Math.random() - 0.5) * 0.5;
            this.vy = (Math.random() - 0.5) * 0.5;
            this.size = Math.random() * 2 + 1;
            this.opacity = Math.random() * 0.5 + 0.2;
        }

        update() {
            this.x += this.vx;
            this.y += this.vy;

            if (this.x < 0 || this.x > canvas.width) this.vx *= -1;
            if (this.y < 0 || this.y > canvas.height) this.vy *= -1;
        }

        draw() {
            ctx.beginPath();
            ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
            ctx.fillStyle = `rgba(0, 245, 255, ${this.opacity})`;
            ctx.fill();
        }
    }

    // 创建粒子
    for (let i = 0; i < particleCount; i++) {
        particles.push(new Particle());
    }

    // 动画循环
    function animate() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        particles.forEach(particle => {
            particle.update();
            particle.draw();
        });

        requestAnimationFrame(animate);
    }

    animate();
    window.futuristicNav.particles = particles;
}

/**
 * 初始化动画
 */
function initAnimations() {
    const settings = window.futuristicNav.config.settings;
    if (!settings.animations_enabled) return;

    // 观察器选项
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    // 创建观察器
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
            }
        });
    }, observerOptions);

    // 观察所有需要动画的元素
    document.querySelectorAll('.animate-on-load').forEach(el => {
        observer.observe(el);
    });

    // 错开动画
    document.querySelectorAll('.stagger-container > *').forEach((el, index) => {
        el.style.animationDelay = `${index * 0.1}s`;
    });
}

/**
 * 更新加载进度
 */
function updateLoadingProgress(progress) {
    const progressBar = document.querySelector('.loading-progress');
    if (progressBar) {
        progressBar.style.width = `${progress}%`;
    }
}

/**
 * 隐藏加载屏幕
 */
function hideLoadingScreen() {
    const loadingScreen = document.getElementById('loading-screen');
    if (loadingScreen) {
        loadingScreen.classList.add('hidden');
        setTimeout(() => {
            loadingScreen.style.display = 'none';
        }, 600);
    }
}

/**
 * 显示错误信息
 */
function showError(message) {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.textContent = message;
    errorDiv.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: #ff3b30;
        color: white;
        padding: 15px 20px;
        border-radius: 8px;
        z-index: 10000;
        font-size: 14px;
        max-width: 300px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    `;

    document.body.appendChild(errorDiv);

    setTimeout(() => {
        errorDiv.remove();
    }, 5000);
}

/**
 * 更新网站元数据（标题和描述）
 */
function updateSiteMetadata() {
    const settings = window.futuristicNav.config.settings;
    if (!settings) return;
    
    // 更新页面标题
    const pageTitle = document.getElementById('page-title');
    if (pageTitle && settings.site_title) {
        pageTitle.textContent = settings.site_title;
        document.title = settings.site_title;
    }
    
    // 更新Open Graph标题
    const ogTitle = document.getElementById('og-title');
    if (ogTitle && settings.site_title) {
        ogTitle.setAttribute('content', settings.site_title);
    }
    
    // 更新页面描述
    const metaDescription = document.querySelector('meta[name="description"]');
    if (metaDescription && settings.site_description) {
        metaDescription.setAttribute('content', settings.site_description);
    }
    
    // 更新Open Graph描述
    const ogDescription = document.getElementById('og-description');
    if (ogDescription && settings.site_description) {
        ogDescription.setAttribute('content', settings.site_description);
    }
    
    console.log('✅ 网站元数据已更新:', settings.site_title, settings.site_description);
}

/**
 * 更新文本内容的辅助函数
 */
function updateTextContent(selector, content) {
    const element = typeof selector === 'string'
        ? document.querySelector(selector) || document.getElementById(selector)
        : selector;

    if (element && content !== undefined) {
        element.textContent = content;
    }
}

/**
 * 默认配置（备用）
 */
function getDefaultConfig() {
    return {
        profile: {
            name: "个人导航",
            title: "欢迎使用",
            description: "这是一个个人导航页面",
            avatar: "assets/images/avatar.svg",
            status_text: "在线",
            status_type: "online",
            projects_count: 0,
            experience_years: 0,
            skills_count: 0
        },
        socialLinks: [
            { platform: "GitHub", url: "https://github.com", icon: "🐙", color: "#333" },
            { platform: "Email", url: "mailto:example@email.com", icon: "📧", color: "#D44638" }
        ],
        categories: [
            {
                id: 1,
                name: "常用工具",
                color: "#00f5ff",
                icon: "🔧"
            }
        ],
        navigation: [
            {
                id: 1,
                category_id: 1,
                name: "Google",
                url: "https://google.com",
                icon: "🔍",
                description: "搜索引擎",
                tags: ["搜索"],
                target: "_blank"
            }
        ],
        themes: [
            {
                name: "cyberpunk",
                display_name: "赛博朋克",
                colors: {
                    primary: "#00f5ff",
                    secondary: "#ff0080",
                    background: "#0a0a0a",
                    text: "#ffffff"
                },
                is_default: true
            }
        ],
        settings: {
            animations_enabled: true,
            particles_enabled: true,
            particle_count: 50
        }
    };
}

// 键盘快捷键支持
document.addEventListener('keydown', (e) => {
    // Ctrl/Cmd + K 打开主题选择器
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        showThemeModal();
    }

    // Escape 关闭模态框
    if (e.key === 'Escape') {
        hideThemeModal();
    }


});

// 导出到全局作用域（用于调试）
window.futuristicNav.applyTheme = applyTheme;
window.futuristicNav.showThemeModal = showThemeModal;
window.futuristicNav.fetchAPI = fetchAPI;