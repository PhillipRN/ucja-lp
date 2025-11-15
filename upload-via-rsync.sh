#!/bin/bash

# Cambridge Exam - rsyncアップロードスクリプト
# 
# 使い方:
#   1. このスクリプトを編集して、サーバー情報を設定
#   2. chmod +x upload-via-rsync.sh で実行権限を付与
#   3. ./upload-via-rsync.sh を実行

# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# 設定（以下を編集してください）
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

# サーバーのユーザー名
SERVER_USER="your_username"

# サーバーのホスト名またはIPアドレス
SERVER_HOST="your-server.com"

# サーバー上のアップロード先ディレクトリ
SERVER_PATH="/path/to/webroot"

# SSHポート（デフォルトは22）
SSH_PORT="22"

# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# スクリプト本体（編集不要）
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "  Cambridge Exam - rsyncアップロード"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# 設定確認
if [ "$SERVER_USER" = "your_username" ] || [ "$SERVER_HOST" = "your-server.com" ]; then
    echo "❌ エラー: サーバー情報が設定されていません"
    echo ""
    echo "このスクリプトを編集して、以下の情報を設定してください:"
    echo "  - SERVER_USER: サーバーのユーザー名"
    echo "  - SERVER_HOST: サーバーのホスト名またはIPアドレス"
    echo "  - SERVER_PATH: アップロード先のパス"
    echo ""
    exit 1
fi

echo "📋 アップロード設定:"
echo "  サーバー: $SERVER_USER@$SERVER_HOST:$SSH_PORT"
echo "  アップロード先: $SERVER_PATH"
echo ""

# 確認
read -p "この設定でアップロードしますか？ (y/N): " -n 1 -r
echo ""

if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "キャンセルしました"
    exit 0
fi

echo ""
echo "📤 アップロード中..."
echo ""

# rsyncでアップロード
rsync -avz \
    --progress \
    --delete \
    --exclude='DEPLOY.md' \
    --exclude='UPLOAD-CHECKLIST.txt' \
    --exclude='upload-via-rsync.sh' \
    --exclude='upload-via-scp.sh' \
    --exclude='.DS_Store' \
    -e "ssh -p $SSH_PORT" \
    ./ "$SERVER_USER@$SERVER_HOST:$SERVER_PATH/"

# 結果確認
if [ $? -eq 0 ]; then
    echo ""
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo "✅ アップロード完了！"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo ""
    echo "次のステップ:"
    echo "  1. ブラウザでサイトにアクセスして確認"
    echo "  2. UPLOAD-CHECKLIST.txt の項目を確認"
    echo "  3. パーミッション設定を確認（必要に応じて）"
    echo ""
    echo "パーミッション設定コマンド:"
    echo "  ssh -p $SSH_PORT $SERVER_USER@$SERVER_HOST"
    echo "  cd $SERVER_PATH"
    echo "  find . -type d -exec chmod 755 {} \;"
    echo "  find . -type f -exec chmod 644 {} \;"
    echo ""
else
    echo ""
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo "❌ アップロード失敗"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo ""
    echo "以下を確認してください:"
    echo "  1. サーバーへのSSH接続が可能か"
    echo "  2. ユーザー名、ホスト名が正しいか"
    echo "  3. アップロード先のパスが正しいか"
    echo "  4. 書き込み権限があるか"
    echo ""
    exit 1
fi

