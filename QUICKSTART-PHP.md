# クイックスタートガイド - PHP 版

## 🚀 すぐに始める

### ステップ 1: PHP がインストールされていることを確認

```bash
php -v
```

PHP 7.4 以上が必要です。

### ステップ 2: 開発サーバーを起動

#### macOS / Linux の場合:

```bash
./start-server.sh
```

または、ポート番号を指定:

```bash
./start-server.sh 3000
```

#### Windows の場合:

```cmd
start-server.bat
```

または、ポート番号を指定:

```cmd
start-server.bat 3000
```

#### 手動で起動する場合:

```bash
php -S localhost:8000
```

### ステップ 3: ブラウザでアクセス

- **メインページ**: http://localhost:8000/index.php
- **テストページ**: http://localhost:8000/test.php
- **PHP 情報**: http://localhost:8000/phpinfo.php

## 📁 ファイル構成

```
camridge_exam/
├── index.php                      # メインページ
├── test.php                       # 動作確認ページ（確認後削除）
├── phpinfo.php                    # PHP環境確認（確認後削除）
├── .htaccess                      # Apache設定
├── nginx.conf.sample              # Nginx設定サンプル
├── start-server.sh                # 起動スクリプト（Mac/Linux）
├── start-server.bat               # 起動スクリプト（Windows）
├── README-PHP.md                  # 詳細ドキュメント
├── QUICKSTART-PHP.md              # このファイル
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

## ✅ 動作確認チェックリスト

1. [ ] PHP バージョンが 7.4 以上である
2. [ ] test.php で全ての項目が ✓ になっている
3. [ ] index.php が正常に表示される
4. [ ] フォームが動作する
5. [ ] 各セクションへのスムーズスクロールが機能する
6. [ ] レスポンシブデザインが正しく表示される（モバイル/タブレット/デスクトップ）

## 🔐 セキュリティチェックリスト（本番環境）

- [ ] test.php を削除
- [ ] phpinfo.php を削除
- [ ] .htaccess または nginx.conf でセキュリティヘッダーを設定
- [ ] HTTPS 証明書を設定
- [ ] フォームに CSRF 保護を追加
- [ ] エラーログの設定を確認

## 🎨 カスタマイズ

### 色の変更

Tailwind CSS のクラスを編集:

- `bg-blue-600` → 他の色に変更
- `text-gray-900` → 他のテキスト色に変更

### フォーム送信先の変更

`components/application-form.php` の 79 行目:

```php
<form id="application-form-step2" action="YOUR_API_ENDPOINT" method="POST">
```

### 画像の変更

各コンポーネント内の`<img src="...">` を編集

## 🆘 トラブルシューティング

### Q: ページが表示されない

A:

1. PHP が正しくインストールされているか確認
2. ポート 8000 が他のアプリケーションで使用されていないか確認
3. エラーメッセージを確認

### Q: コンポーネントが読み込まれない

A:

1. components ディレクトリが存在するか確認
2. ファイルのパーミッションを確認（読み取り権限が必要）

### Q: フォームが送信できない

A:

1. ブラウザのコンソールでエラーを確認
2. ネットワークタブで送信先 URL を確認
3. CORS エラーがないか確認

## 📚 詳細ドキュメント

より詳しい情報は `README-PHP.md` をご覧ください。

## 🚀 本番環境へのデプロイ

### 推奨手順

1. **全ファイルをサーバーにアップロード**

   ```bash
   rsync -avz --exclude='test.php' --exclude='phpinfo.php' ./ user@server:/path/to/webroot/
   ```

2. **test.php と phpinfo.php を削除**

   ```bash
   rm test.php phpinfo.php
   ```

3. **パーミッションを設定**

   ```bash
   find . -type f -exec chmod 644 {} \;
   find . -type d -exec chmod 755 {} \;
   ```

4. **Web サーバーの設定**

   - Apache: `.htaccess`がすでに設定済み
   - Nginx: `nginx.conf.sample`を参考に設定

5. **HTTPS 証明書の設定**

   - Let's Encrypt の使用を推奨

6. **動作確認**
   - 全てのページが正常に表示されるか確認
   - フォームが正しく動作するか確認
   - モバイル表示を確認

## 💡 ヒント

- 開発中は `php -S localhost:8000` で簡易サーバーを使用
- 本番環境では Apache または Nginx + PHP-FPM を推奨
- CDN からのリソース読み込みを自己ホスティングに変更することでパフォーマンス向上
- データベース連携が必要な場合は PDO を使用

## 📞 サポート

問題が解決しない場合は、以下を確認してください:

- PHP のエラーログ
- Web サーバーのエラーログ
- ブラウザの開発者ツール（コンソール/ネットワーク）

---

**注意**: この PHP 版は、オリジナルの React/Next.js プロジェクトと同じデザインと機能を維持していますが、サーバーサイドレンダリングの実装方法が異なります。
