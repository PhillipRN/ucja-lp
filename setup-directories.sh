#!/bin/bash

# Cambridge Exam Application System
# ディレクトリとファイルの初期セットアップ

echo "📁 Creating necessary directories..."

# ディレクトリの作成
mkdir -p logs
mkdir -p uploads
mkdir -p cache
mkdir -p sessions
mkdir -p tmp

# .gitkeepファイルの作成（空のディレクトリをGitで管理）
touch logs/.gitkeep
touch uploads/.gitkeep
touch cache/.gitkeep
touch sessions/.gitkeep
touch tmp/.gitkeep

echo "✅ Directories created successfully!"

# パーミッション設定
echo "🔒 Setting permissions..."

chmod 755 logs
chmod 755 uploads
chmod 755 cache
chmod 755 sessions
chmod 755 tmp

echo "✅ Permissions set successfully!"

# config.phpの作成（config.example.phpをコピー）
if [ ! -f config/config.php ]; then
    echo "📝 Creating config.php from config.example.php..."
    cp config/config.example.php config/config.php
    echo "⚠️  Please edit config/config.php with your settings!"
else
    echo "ℹ️  config/config.php already exists, skipping..."
fi

echo ""
echo "🎉 Setup complete!"
echo ""
echo "Next steps:"
echo "1. Edit config/config.php with your Supabase and Stripe credentials"
echo "2. Run 'composer install' to install PHP dependencies"
echo "3. Execute database/supabase-schema.sql in your Supabase project"
echo "4. Run './start-dev.sh' to start the development server"
echo ""

