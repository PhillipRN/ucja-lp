#!/bin/bash

# Cambridge Exam - 開発サーバー起動スクリプト

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "  Cambridge Exam 開発サーバー"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# PHPがインストールされているか確認
if ! command -v php &> /dev/null
then
    echo "❌ PHPがインストールされていません。"
    echo "PHPをインストールしてから再度実行してください。"
    exit 1
fi

# PHPバージョン表示
PHP_VERSION=$(php -v | head -n 1)
echo "✓ $PHP_VERSION"
echo ""

# ポート番号の設定
PORT=${1:-8000}

echo "🚀 開発サーバーを起動しています..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "📱 アクセスURL:"
echo "   http://localhost:$PORT/index.php"
echo ""
echo "📝 ファイル構成:"
echo "   index.php              - メインページ"
echo "   components/*.php       - コンポーネント"
echo "   .htaccess             - Apache設定"
echo ""
echo "⚡ 開発のヒント:"
echo "   - ファイルを編集すると自動的に反映されます"
echo "   - ブラウザをリロードして確認してください"
echo "   - エラーが出たらブラウザのコンソールを確認"
echo ""
echo "🛑 サーバーを停止するには Ctrl+C を押してください"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# PHPサーバーを起動
php -S localhost:$PORT

