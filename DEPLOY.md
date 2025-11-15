# デプロイ手順書

このディレクトリには、サーバーにアップロードするための本番環境用ファイルが含まれています。

## 📦 含まれるファイル

```
php-production/
├── index.php                      # メインページ
├── .htaccess                      # Apache設定（セキュリティ、GZIP等）
├── README-PHP.md                  # 詳細ドキュメント
├── QUICKSTART-PHP.md              # クイックスタートガイド
├── DEPLOY.md                      # このファイル
└── components/                    # コンポーネント
    ├── header.php
    ├── hero-section.php
    ├── about-section.php
    ├── pricing-section.php
    ├── payment-process-section.php
    ├── prizes-section.php
    ├── application-form.php
    └── footer.php
```

## 🚀 デプロイ方法

### 方法 1: FTP クライアントを使用

1. FTP クライアント（FileZilla、Cyberduck 等）を起動
2. サーバーに接続
3. このディレクトリ内の**全ファイル**をサーバーのドキュメントルートにアップロード
   - 例: `/public_html/` または `/var/www/html/`
4. ファイルのパーミッションを確認:
   - ディレクトリ: `755`
   - ファイル: `644`

### 方法 2: rsync を使用（SSH 接続可能な場合）

```bash
# このディレクトリから実行
rsync -avz --delete \
  --exclude='DEPLOY.md' \
  ./ user@your-server.com:/path/to/webroot/
```

### 方法 3: SCP を使用

```bash
# このディレクトリから実行
scp -r * user@your-server.com:/path/to/webroot/
```

### 方法 4: Git 経由でデプロイ

```bash
# サーバー上で
cd /path/to/webroot/
git clone your-repository
# または
git pull origin main
```

## ✅ デプロイ後のチェックリスト

### 必須確認事項

- [ ] **PHP バージョン確認**: PHP 7.4 以上が動作しているか
- [ ] **ファイル配置**: 全ファイルが正しくアップロードされたか
- [ ] **パーミッション**: ディレクトリ 755、ファイル 644 になっているか
- [ ] **.htaccess**: 正しく配置され、動作しているか（Apache 使用時）
- [ ] **index.php アクセス**: ブラウザでページが表示されるか
- [ ] **コンポーネント読み込み**: 全セクションが表示されるか
- [ ] **フォーム動作**: 申込フォームが正しく送信できるか
- [ ] **レスポンシブ**: モバイル/タブレット/デスクトップで表示確認

### セキュリティチェック

- [ ] **HTTPS 設定**: SSL 証明書が正しく設定されているか
- [ ] **セキュリティヘッダー**: .htaccess の設定が有効か確認
- [ ] **エラー表示**: 本番環境で PHP エラーが表示されないよう設定
- [ ] **デバッグモード**: デバッグモードが OFF になっているか

### パフォーマンスチェック

- [ ] **GZIP 圧縮**: 有効になっているか
- [ ] **キャッシュ設定**: ブラウザキャッシュが有効か
- [ ] **CDN**: 必要に応じて CDN 設定を検討
- [ ] **画像最適化**: 画像が適切に最適化されているか

## 🔧 サーバー設定

### Apache 使用時

`.htaccess`ファイルがすでに含まれているため、追加設定は不要です。

ただし、以下が有効になっていることを確認してください:

```apache
# .htaccessの使用を許可
AllowOverride All

# 必要なモジュールが有効
LoadModule rewrite_module modules/mod_rewrite.so
LoadModule headers_module modules/mod_headers.so
LoadModule deflate_module modules/mod_deflate.so
LoadModule expires_module modules/mod_expires.so
```

### Nginx 使用時

Nginx を使用している場合は、プロジェクトのルートディレクトリにある `nginx.conf.sample` を参考に設定してください。

基本的な設定例:

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/webroot;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

## 🔐 HTTPS 設定（推奨）

### Let's Encrypt を使用

```bash
# Certbotをインストール（Ubuntu/Debian）
sudo apt-get update
sudo apt-get install certbot python3-certbot-apache

# 証明書を取得
sudo certbot --apache -d your-domain.com -d www.your-domain.com

# 自動更新を設定
sudo certbot renew --dry-run
```

## 📝 環境変数設定

もし環境変数が必要な場合:

### Apache (.htaccess)

```apache
SetEnv API_KEY "your-api-key"
SetEnv DB_HOST "localhost"
```

### Nginx

```nginx
location ~ \.php$ {
    fastcgi_param API_KEY "your-api-key";
    fastcgi_param DB_HOST "localhost";
    # ... other fastcgi_params
}
```

## 🐛 トラブルシューティング

### ページが表示されない

1. **PHP バージョンを確認**

   ```bash
   php -v
   ```

2. **エラーログを確認**

   ```bash
   # Apache
   tail -f /var/log/apache2/error.log

   # Nginx
   tail -f /var/log/nginx/error.log
   ```

3. **ファイルパーミッションを確認**
   ```bash
   ls -la
   ```

### .htaccess が動作しない（Apache）

1. `mod_rewrite`が有効か確認:

   ```bash
   apache2ctl -M | grep rewrite
   ```

2. `AllowOverride All`が設定されているか確認

### コンポーネントが読み込まれない

1. `components/`ディレクトリが存在するか確認
2. 全ての PHP ファイルがアップロードされているか確認
3. パスの大文字小文字に注意（Linux は区別します）

## 📊 パフォーマンス最適化

### CDN からローカルへ（オプション）

現在、Tailwind CSS と Remix Icon を以下の CDN から読み込んでいます:

- `https://cdn.tailwindcss.com`
- `https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.5.0/remixicon.min.css`

パフォーマンス向上のため、これらをローカルにダウンロードして配置することを推奨します。

### OPcache の有効化

`php.ini`で以下を設定:

```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000
opcache.revalidate_freq=60
```

## 📞 サポート・問い合わせ

デプロイに関して問題が発生した場合:

1. エラーログを確認
2. ブラウザの開発者ツールでコンソールエラーを確認
3. ネットワークタブで通信エラーを確認
4. サーバーの PHP 設定を確認

## 🎉 デプロイ完了後

デプロイが完了したら:

1. 全ページを確認
2. フォーム送信をテスト
3. モバイルでの表示を確認
4. パフォーマンスをテスト（Google PageSpeed Insights 等）
5. SEO 設定を確認

---

**重要**: 本番環境では必ず HTTPS を使用してください。個人情報を扱うフォームがあるため、セキュリティは最優先事項です。
