#!/bin/bash

# 个人导航页部署脚本
# 使用方法: ./deploy.sh

echo "🚀 开始部署个人导航页..."

# 检查必要文件
if [ ! -f "index.html" ]; then
    echo "❌ 错误: 找不到 index.html 文件"
    exit 1
fi

if [ ! -f "database/data.sql" ]; then
    echo "❌ 错误: 找不到 database/data.sql 文件"
    exit 1
fi

# 设置文件权限
echo "📁 设置文件权限..."
find . -type f -name "*.php" -exec chmod 644 {} \;
find . -type f -name "*.html" -exec chmod 644 {} \;
find . -type f -name "*.css" -exec chmod 644 {} \;
find . -type f -name "*.js" -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;

# 创建必要目录
echo "📂 创建必要目录..."
mkdir -p admin/uploads/avatars
mkdir -p admin/uploads/icons
mkdir -p admin/logs
mkdir -p assets/images
mkdir -p assets/icons

# 设置上传目录权限
chmod 755 admin/uploads
chmod 755 admin/uploads/avatars
chmod 755 admin/uploads/icons
chmod 755 admin/logs
chmod 755 assets/images
chmod 755 assets/icons

echo "✅ 部署完成！"
echo "📋 下一步:"
echo "   1. 访问您的网站进行安装"
echo "   2. 运行安装向导"
echo "   3. 配置数据库和管理员账号"
echo "   4. 删除安装文件"