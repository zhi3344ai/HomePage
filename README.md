# 🚀 HomePage

一个具有未来科技感设计的个人导航页面，支持PHP后台管理、多主题切换、响应式布局和炫酷的点击特效。

## ✨ 特性

- **未来科技感设计** - 赛博朋克风格的视觉效果
- **PHP管理后台** - 完整的内容管理系统
- **多主题支持** - 内置多种精美主题
- **点击爆炸特效** - 炫酷的交互动画
- **响应式布局** - 完美适配各种设备
- **PWA支持** - 可安装为桌面应用
- **一键安装** - 傻瓜式安装向导

## 🛠️ 技术栈

- **前端**: HTML5, CSS3, JavaScript (ES6+)
- **后端**: PHP 7.4+, MySQL 5.7+
- **特性**: PWA, 响应式设计, RESTful API

## 📦 快速安装

### 环境要求
- PHP 7.4 或更高版本
- MySQL 5.7 或更高版本
- Web服务器 (Apache/Nginx)

### 安装步骤

1. **下载代码**
   ```bash
   git clone https://github.com/your-username/HomePage.git
   cd HomePage
   ```

2. **上传到服务器**
   - 将所有文件上传到网站根目录
   - 确保文件权限正确

3. **运行安装向导**
   - 访问 `http://yourdomain.com/install.php`
   - 按照向导提示完成安装

4. **完成安装**
   - 删除 `install.php` 和 `database/data.sql` 文件
   - 访问前台: `http://yourdomain.com/`
   - 访问后台: `http://yourdomain.com/admin/`

## ⚙️ 配置说明

### 默认账号
- **用户名**: admin
- **密码**: admin123
- **请及时修改默认密码**

### 目录权限
确保以下目录可写：
```bash
chmod 755 admin/uploads admin/logs assets/images
```

### 主要功能
- **个人信息管理** - 编辑头像、简介、状态等
- **社交链接管理** - 管理各种社交平台链接
- **导航管理** - 添加、编辑、删除导航项目
- **分类管理** - 创建和管理导航分类
- **主题设置** - 自定义主题颜色和效果
- **访问统计** - 查看访问量和点击统计

## 🎨 主题

内置4种精美主题：
- **赛博朋克** - 经典的蓝粉配色
- **极光** - 梦幻的极光色彩  
- **日落** - 温暖的日落色调
- **森林** - 清新的绿色主题

## 📁 项目结构

```
HomePage/
├── index.html              # 前台主页
├── install.php             # 安装向导
├── admin/                  # 管理后台
├── api/                    # API接口
├── pwa/                    # PWA文件
├── database/               # 数据库文件
├── assets/                 # 静态资源
├── css/                    # 样式文件
├── js/                     # JavaScript文件
├── config/                 # 配置文件
├── dev/                    # 开发依赖
└── scripts/                # 脚本文件
```

## 🔧 开发

### 开发环境
```bash
cd dev
npm install
npm start
```

### 系统测试
```bash
php scripts/test-system.php
```

## 📱 PWA支持

本项目支持PWA，用户可以：
- 将网站添加到桌面
- 离线访问基本功能
- 获得类似原生应用的体验

## 🔒 安全

- SQL注入防护
- XSS防护  
- CSRF防护
- 文件上传安全
- 密码加密存储

## 📄 许可证

MIT License - 查看 [LICENSE](LICENSE) 文件了解详情

## 🙏 致谢

感谢所有贡献者的支持！

---

⭐ 如果这个项目对你有帮助，请给个Star支持一下！