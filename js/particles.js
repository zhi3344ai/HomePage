/**
 * 粒子系统模块
 * 创建动态的背景粒子效果
 */

class ParticleSystem {
    constructor(canvas) {
        this.canvas = canvas;
        this.ctx = canvas.getContext('2d');
        this.particles = [];
        this.animationId = null;
        this.isRunning = false;
        
        // 配置参数
        this.config = {
            particleCount: this.getParticleCount(),
            connectionDistance: 150,
            particleSpeed: 0.5,
            particleSize: { min: 1, max: 3 },
            opacity: { min: 0.1, max: 0.6 },
            colors: {
                cyberpunk: 'rgba(0, 245, 255, ',
                aurora: 'rgba(79, 172, 254, ',
                synthwave: 'rgba(255, 0, 255, ',
                matrix: 'rgba(0, 255, 65, ',
                neon: 'rgba(255, 7, 58, '
            }
        };
        
        this.currentTheme = 'cyberpunk';
        this.mouse = { x: null, y: null, radius: 100 };
        
        this.init();
    }
    
    /**
     * 根据设备性能确定粒子数量
     */
    getParticleCount() {
        const width = window.innerWidth;
        const height = window.innerHeight;
        const area = width * height;
        
        // 根据屏幕面积和设备性能调整粒子数量
        if (area < 500000) return 30; // 小屏幕
        if (area < 1000000) return 50; // 中等屏幕
        if (area < 2000000) return 80; // 大屏幕
        return 100; // 超大屏幕
    }
    
    /**
     * 初始化粒子系统
     */
    init() {
        this.resizeCanvas();
        this.createParticles();
        this.bindEvents();
        this.start();
    }
    
    /**
     * 调整画布大小
     */
    resizeCanvas() {
        this.canvas.width = window.innerWidth;
        this.canvas.height = window.innerHeight;
    }
    
    /**
     * 创建粒子
     */
    createParticles() {
        this.particles = [];
        
        for (let i = 0; i < this.config.particleCount; i++) {
            this.particles.push(new Particle(this.canvas.width, this.canvas.height, this.config));
        }
    }
    
    /**
     * 绑定事件
     */
    bindEvents() {
        // 窗口大小变化
        window.addEventListener('resize', () => {
            this.resizeCanvas();
            this.config.particleCount = this.getParticleCount();
            this.createParticles();
        });
        
        // 鼠标移动
        this.canvas.addEventListener('mousemove', (e) => {
            this.mouse.x = e.clientX;
            this.mouse.y = e.clientY;
        });
        
        // 鼠标离开
        this.canvas.addEventListener('mouseleave', () => {
            this.mouse.x = null;
            this.mouse.y = null;
        });
        
        // 主题变化
        document.addEventListener('themeChanged', (e) => {
            this.currentTheme = e.detail.theme;
        });
        
        // 页面可见性变化
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.pause();
            } else {
                this.resume();
            }
        });
    }
    
    /**
     * 开始动画
     */
    start() {
        if (this.isRunning) return;
        this.isRunning = true;
        this.animate();
    }
    
    /**
     * 暂停动画
     */
    pause() {
        this.isRunning = false;
        if (this.animationId) {
            cancelAnimationFrame(this.animationId);
        }
    }
    
    /**
     * 恢复动画
     */
    resume() {
        if (!this.isRunning) {
            this.start();
        }
    }
    
    /**
     * 动画循环
     */
    animate() {
        if (!this.isRunning) return;
        
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
        
        // 更新和绘制粒子
        this.particles.forEach(particle => {
            particle.update(this.mouse);
            particle.draw(this.ctx, this.getCurrentColor());
        });
        
        // 绘制连接线
        this.drawConnections();
        
        this.animationId = requestAnimationFrame(() => this.animate());
    }
    
    /**
     * 获取当前主题颜色
     */
    getCurrentColor() {
        return this.config.colors[this.currentTheme] || this.config.colors.cyberpunk;
    }
    
    /**
     * 绘制粒子间的连接线
     */
    drawConnections() {
        const color = this.getCurrentColor();
        
        for (let i = 0; i < this.particles.length; i++) {
            for (let j = i + 1; j < this.particles.length; j++) {
                const dx = this.particles[i].x - this.particles[j].x;
                const dy = this.particles[i].y - this.particles[j].y;
                const distance = Math.sqrt(dx * dx + dy * dy);
                
                if (distance < this.config.connectionDistance) {
                    const opacity = (1 - distance / this.config.connectionDistance) * 0.3;
                    
                    this.ctx.beginPath();
                    this.ctx.strokeStyle = color + opacity + ')';
                    this.ctx.lineWidth = 1;
                    this.ctx.moveTo(this.particles[i].x, this.particles[i].y);
                    this.ctx.lineTo(this.particles[j].x, this.particles[j].y);
                    this.ctx.stroke();
                }
            }
        }
    }
    
    /**
     * 添加粒子爆炸效果
     */
    addExplosion(x, y, count = 10) {
        for (let i = 0; i < count; i++) {
            const angle = (Math.PI * 2 * i) / count;
            const velocity = 2 + Math.random() * 3;
            
            const particle = new Particle(this.canvas.width, this.canvas.height, this.config);
            particle.x = x;
            particle.y = y;
            particle.vx = Math.cos(angle) * velocity;
            particle.vy = Math.sin(angle) * velocity;
            particle.life = 60; // 60帧生命周期
            particle.isExplosion = true;
            
            this.particles.push(particle);
        }
        
        // 清理过期的爆炸粒子
        setTimeout(() => {
            this.particles = this.particles.filter(p => !p.isExplosion || p.life > 0);
        }, 1000);
    }
    
    /**
     * 销毁粒子系统
     */
    destroy() {
        this.pause();
        this.particles = [];
        
        // 移除事件监听器
        window.removeEventListener('resize', this.resizeCanvas);
        this.canvas.removeEventListener('mousemove', this.handleMouseMove);
        this.canvas.removeEventListener('mouseleave', this.handleMouseLeave);
    }
}

/**
 * 粒子类
 */
class Particle {
    constructor(canvasWidth, canvasHeight, config) {
        this.x = Math.random() * canvasWidth;
        this.y = Math.random() * canvasHeight;
        this.vx = (Math.random() - 0.5) * config.particleSpeed;
        this.vy = (Math.random() - 0.5) * config.particleSpeed;
        this.size = config.particleSize.min + Math.random() * (config.particleSize.max - config.particleSize.min);
        this.opacity = config.opacity.min + Math.random() * (config.opacity.max - config.opacity.min);
        this.canvasWidth = canvasWidth;
        this.canvasHeight = canvasHeight;
        this.config = config;
        
        // 爆炸粒子特有属性
        this.isExplosion = false;
        this.life = 0;
        this.maxLife = 60;
    }
    
    /**
     * 更新粒子状态
     */
    update(mouse) {
        // 爆炸粒子处理
        if (this.isExplosion) {
            this.life--;
            this.opacity = (this.life / this.maxLife) * 0.8;
            this.size *= 0.98;
            
            if (this.life <= 0) {
                return; // 粒子死亡
            }
        }
        
        // 鼠标交互
        if (mouse.x !== null && mouse.y !== null) {
            const dx = mouse.x - this.x;
            const dy = mouse.y - this.y;
            const distance = Math.sqrt(dx * dx + dy * dy);
            
            if (distance < mouse.radius) {
                const force = (mouse.radius - distance) / mouse.radius;
                const angle = Math.atan2(dy, dx);
                
                this.vx -= Math.cos(angle) * force * 0.01;
                this.vy -= Math.sin(angle) * force * 0.01;
            }
        }
        
        // 更新位置
        this.x += this.vx;
        this.y += this.vy;
        
        // 边界检测和反弹
        if (this.x <= 0 || this.x >= this.canvasWidth) {
            this.vx *= -1;
            this.x = Math.max(0, Math.min(this.canvasWidth, this.x));
        }
        
        if (this.y <= 0 || this.y >= this.canvasHeight) {
            this.vy *= -1;
            this.y = Math.max(0, Math.min(this.canvasHeight, this.y));
        }
        
        // 速度衰减
        this.vx *= 0.999;
        this.vy *= 0.999;
        
        // 添加微小的随机扰动
        this.vx += (Math.random() - 0.5) * 0.001;
        this.vy += (Math.random() - 0.5) * 0.001;
    }
    
    /**
     * 绘制粒子
     */
    draw(ctx, colorBase) {
        ctx.beginPath();
        ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
        ctx.fillStyle = colorBase + this.opacity + ')';
        ctx.fill();
        
        // 添加发光效果
        if (this.size > 2) {
            ctx.beginPath();
            ctx.arc(this.x, this.y, this.size * 2, 0, Math.PI * 2);
            ctx.fillStyle = colorBase + (this.opacity * 0.1) + ')';
            ctx.fill();
        }
    }
}

// 初始化粒子系统
document.addEventListener('DOMContentLoaded', () => {
    const canvas = document.getElementById('particles-canvas');
    if (canvas) {
        // 检查是否偏好减少动画
        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        
        if (!prefersReducedMotion) {
            window.particleSystem = new ParticleSystem(canvas);
            
            // 为点击事件添加粒子爆炸效果
            document.addEventListener('click', (e) => {
                if (e.target.closest('.nav-item, .social-link')) {
                    window.particleSystem.addExplosion(e.clientX, e.clientY, 8);
                }
            });
        }
    }
});

// 导出粒子系统类
window.ParticleSystem = ParticleSystem;