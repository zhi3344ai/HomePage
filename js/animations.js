/**
 * 动画效果模块
 * 处理页面的各种动画效果和交互
 */

// 动画模块
const AnimationModule = {
    observers: [],
    animationQueue: [],
    isReducedMotion: false,
    
    /**
     * 初始化动画模块
     */
    init() {
        this.checkReducedMotion();
        this.initScrollAnimations();
        this.initHoverEffects();
        this.initParallaxEffects();
        this.initTypewriterEffect();
        this.bindEvents();
    },
    
    /**
     * 检查用户是否偏好减少动画
     */
    checkReducedMotion() {
        this.isReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        
        if (this.isReducedMotion) {
            document.documentElement.classList.add('reduced-motion');
        }
    },
    
    /**
     * 初始化滚动动画
     */
    initScrollAnimations() {
        if (this.isReducedMotion) return;
        
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        // 创建交叉观察器
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    this.animateElement(entry.target);
                }
            });
        }, observerOptions);
        
        // 观察所有需要动画的元素
        document.querySelectorAll('.animate-on-load').forEach(el => {
            observer.observe(el);
        });
        
        this.observers.push(observer);
    },
    
    /**
     * 动画元素
     */
    animateElement(element) {
        const animationType = element.dataset.animation || 'animate-fade-in';
        const delay = element.dataset.delay || 0;
        
        setTimeout(() => {
            element.classList.add('visible');
            element.classList.add(animationType);
        }, delay);
    },
    
    /**
     * 初始化悬停效果
     */
    initHoverEffects() {
        if (this.isReducedMotion) return;
        
        // 导航项目悬停效果
        this.addHoverEffect('.nav-item', {
            transform: 'translateX(10px) scale(1.02)',
            boxShadow: 'var(--shadow-glow)'
        });
        
        // 社交链接悬停效果
        this.addHoverEffect('.social-link', {
            transform: 'translateY(-5px) rotate(5deg)',
            boxShadow: 'var(--shadow-glow)'
        });
        
        // 分类卡片悬停效果
        this.addHoverEffect('.nav-category', {
            transform: 'translateY(-8px)',
            boxShadow: 'var(--shadow-glow)'
        });
        
        // 主题按钮悬停效果
        this.addHoverEffect('.theme-btn', {
            transform: 'scale(1.1) rotate(10deg)',
            boxShadow: 'var(--shadow-glow)'
        });
    },
    
    /**
     * 添加悬停效果
     */
    addHoverEffect(selector, hoverStyles) {
        document.querySelectorAll(selector).forEach(element => {
            const originalStyles = {};
            
            element.addEventListener('mouseenter', () => {
                // 保存原始样式
                Object.keys(hoverStyles).forEach(prop => {
                    originalStyles[prop] = element.style[prop];
                });
                
                // 应用悬停样式
                Object.assign(element.style, hoverStyles);
            });
            
            element.addEventListener('mouseleave', () => {
                // 恢复原始样式
                Object.assign(element.style, originalStyles);
            });
        });
    },
    
    /**
     * 初始化视差效果
     */
    initParallaxEffects() {
        if (this.isReducedMotion) return;
        
        const parallaxElements = document.querySelectorAll('.original-circle, .original-star');
        
        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            const rate = scrolled * -0.5;
            
            parallaxElements.forEach((element, index) => {
                const speed = 0.5 + (index * 0.1);
                element.style.transform = `translateY(${rate * speed}px)`;
            });
        });
    },
    
    /**
     * 初始化打字机效果
     */
    initTypewriterEffect() {
        const typewriterElements = document.querySelectorAll('[data-typewriter]');
        
        typewriterElements.forEach(element => {
            const text = element.textContent;
            const speed = parseInt(element.dataset.speed) || 50;
            
            element.textContent = '';
            element.style.borderRight = '2px solid var(--primary-color)';
            
            this.typeWriter(element, text, speed);
        });
    },
    
    /**
     * 打字机效果实现
     */
    typeWriter(element, text, speed, index = 0) {
        if (this.isReducedMotion) {
            element.textContent = text;
            return;
        }
        
        if (index < text.length) {
            element.textContent += text.charAt(index);
            setTimeout(() => this.typeWriter(element, text, speed, index + 1), speed);
        } else {
            // 闪烁光标效果
            setTimeout(() => {
                element.style.borderRight = 'none';
            }, 1000);
        }
    },
    
    /**
     * 绑定事件
     */
    bindEvents() {
        // 监听主题变化
        document.addEventListener('themeChanged', (e) => {
            this.updateAnimationColors(e.detail.theme);
        });
        
        // 监听窗口大小变化
        window.addEventListener('resize', () => {
            this.handleResize();
        });
        
        // 监听滚动事件
        let scrollTimeout;
        window.addEventListener('scroll', () => {
            clearTimeout(scrollTimeout);
            scrollTimeout = setTimeout(() => {
                this.handleScroll();
            }, 16); // 60fps
        });
    },
    
    /**
     * 更新动画颜色
     */
    updateAnimationColors(theme) {
        const root = document.documentElement;
        const computedStyle = getComputedStyle(root);
        
        // 更新粒子颜色
        if (window.futuristicNav.particles) {
            const primaryColor = computedStyle.getPropertyValue('--primary-color').trim();
            // 这里可以更新粒子系统的颜色
        }
    },
    
    /**
     * 处理窗口大小变化
     */
    handleResize() {
        // 重新计算动画参数
        this.recalculateAnimations();
    },
    
    /**
     * 处理滚动事件
     */
    handleScroll() {
        // 更新视差效果
        this.updateParallax();
        
        // 更新导航栏状态
        this.updateNavigationState();
    },
    
    /**
     * 重新计算动画
     */
    recalculateAnimations() {
        // 根据屏幕大小调整动画参数
        const isMobile = window.innerWidth < 768;
        
        if (isMobile) {
            // 移动端减少动画复杂度
            document.documentElement.style.setProperty('--animation-duration', '0.2s');
        } else {
            document.documentElement.style.setProperty('--animation-duration', '0.3s');
        }
    },
    
    /**
     * 更新视差效果
     */
    updateParallax() {
        if (this.isReducedMotion) return;
        
        const scrolled = window.pageYOffset;
        const parallaxElements = document.querySelectorAll('.original-circle');
        
        parallaxElements.forEach((element, index) => {
            const speed = 0.3 + (index * 0.1);
            const yPos = -(scrolled * speed);
            element.style.transform = `translateY(${yPos}px)`;
        });
    },
    
    /**
     * 更新导航栏状态
     */
    updateNavigationState() {
        const scrolled = window.pageYOffset;
        const threshold = 100;
        
        if (scrolled > threshold) {
            document.body.classList.add('scrolled');
        } else {
            document.body.classList.remove('scrolled');
        }
    },
    
    /**
     * 创建粒子爆炸效果
     */
    createParticleExplosion(x, y, color = 'var(--primary-color)') {
        if (this.isReducedMotion) return;
        
        const particleCount = 12;
        const particles = [];
        
        for (let i = 0; i < particleCount; i++) {
            const particle = document.createElement('div');
            particle.className = 'explosion-particle';
            particle.style.cssText = `
                position: fixed;
                width: 4px;
                height: 4px;
                background: ${color};
                border-radius: 50%;
                pointer-events: none;
                z-index: 9999;
                left: ${x}px;
                top: ${y}px;
            `;
            
            document.body.appendChild(particle);
            
            const angle = (i / particleCount) * Math.PI * 2;
            const velocity = 100 + Math.random() * 50;
            const vx = Math.cos(angle) * velocity;
            const vy = Math.sin(angle) * velocity;
            
            particle.animate([
                { transform: 'translate(0, 0) scale(1)', opacity: 1 },
                { transform: `translate(${vx}px, ${vy}px) scale(0)`, opacity: 0 }
            ], {
                duration: 800,
                easing: 'cubic-bezier(0.25, 0.46, 0.45, 0.94)'
            }).onfinish = () => {
                particle.remove();
            };
        }
    },
    
    /**
     * 创建涟漪效果
     */
    createRippleEffect(element, event) {
        if (this.isReducedMotion) return;
        
        const rect = element.getBoundingClientRect();
        const x = event.clientX - rect.left;
        const y = event.clientY - rect.top;
        
        const ripple = document.createElement('div');
        ripple.className = 'ripple-effect';
        ripple.style.cssText = `
            position: absolute;
            width: 20px;
            height: 20px;
            background: var(--primary-color);
            border-radius: 50%;
            opacity: 0.3;
            pointer-events: none;
            left: ${x - 10}px;
            top: ${y - 10}px;
            transform: scale(0);
        `;
        
        element.style.position = 'relative';
        element.style.overflow = 'hidden';
        element.appendChild(ripple);
        
        ripple.animate([
            { transform: 'scale(0)', opacity: 0.3 },
            { transform: 'scale(4)', opacity: 0 }
        ], {
            duration: 600,
            easing: 'ease-out'
        }).onfinish = () => {
            ripple.remove();
        };
    },
    
    /**
     * 销毁动画模块
     */
    destroy() {
        // 清理观察器
        this.observers.forEach(observer => {
            observer.disconnect();
        });
        this.observers = [];
        
        // 清理动画队列
        this.animationQueue = [];
    }
};

// 为导航项目添加点击涟漪效果
document.addEventListener('click', (e) => {
    const navItem = e.target.closest('.nav-item, .social-link, .theme-btn');
    if (navItem) {
        AnimationModule.createRippleEffect(navItem, e);
    }
});

// 为特殊元素添加粒子爆炸效果
document.addEventListener('click', (e) => {
    if (e.target.closest('.profile-avatar')) {
        AnimationModule.createParticleExplosion(e.clientX, e.clientY);
    }
});

// 当DOM加载完成后初始化动画模块
document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {
        AnimationModule.init();
    }, 100);
});

// 导出模块
window.AnimationModule = AnimationModule;