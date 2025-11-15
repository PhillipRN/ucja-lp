# SendGrid統合セットアップガイド

このガイドでは、SendGridのメール送信機能を統合する手順を説明します。

---

## 📋 前提条件

- ✅ SendGridアカウント作成済み
- ✅ DNS設定（ドメイン認証）完了・Verify待ち
- ✅ 有料プラン契約予定

---

## 🚀 Phase 1: SendGrid基盤構築（完了）

### ✅ 完了済みの作業

1. **SendGrid PHP SDK追加**
   - `composer.json` に `sendgrid/sendgrid` を追加
   
2. **EmailService クラス作成**
   - `lib/EmailService.php` - メール送信サービス

3. **設定ファイル更新**
   - `config/config.example.php` にSendGrid設定を追加

4. **データベーススキーマ作成**
   - `database/email-system-schema.sql` - メールシステム用テーブル

5. **メールテンプレートサンプル作成**
   - `email-templates/01-application-complete.html` - 申込完了メール
   - `email-templates/06-general-notice.html` - 一般お知らせメール

---

## 📦 セットアップ手順

### Step 1: Composer パッケージのインストール

```bash
cd /Users/phillipr.n./Documents/KUTO/SCAT/dev/camridge_exam
php composer.phar update
```

### Step 2: SendGrid API Key の取得

1. SendGridダッシュボードにログイン
2. **Settings** → **API Keys** へ移動
3. **Create API Key** をクリック
4. **Full Access** を選択（または必要な権限のみ選択）
5. API Keyをコピー（一度しか表示されません！）

### Step 3: 設定ファイルの更新

`config/config.php` を編集：

```php
// SendGrid設定
define('SENDGRID_API_KEY', 'SG.xxxxxxxxxx'); // ← ここにAPI Keyを貼り付け
define('SENDGRID_FROM_EMAIL', 'noreply@univ-cambridge-japan.academy');
define('SENDGRID_FROM_NAME', 'UCJA事務局');
```

### Step 4: Supabaseでデータベーススキーマを実行

1. Supabaseダッシュボードを開く
2. **SQL Editor** へ移動
3. `database/email-system-schema.sql` の内容を貼り付けて実行

これで以下のテーブルが作成されます：
- `email_logs` - メール送信ログ
- `email_batches` - 一斉送信バッチ管理
- `email_templates` - テンプレート管理
- `admin_users` - 管理者アカウント
- `admin_activity_logs` - 管理者アクティビティログ

### Step 5: SendGrid Dynamic Templates の作成

1. SendGridダッシュボードで **Email API** → **Dynamic Templates** へ移動
2. **Create a Dynamic Template** をクリック

#### 必要なテンプレート（6つ）

##### 1. 申込完了メール
- Template Name: `UCJA - 申込完了`
- `email-templates/01-application-complete.html` の内容をコピー
- 変数: `{{name}}`, `{{application_number}}`, `{{participation_type}}`, `{{email}}`, `{{mypage_url}}`, `{{website_url}}`

##### 2. 本人確認完了メール
- Template Name: `UCJA - 本人確認完了`
- 変数: `{{name}}`, `{{application_number}}`, `{{mypage_url}}`

##### 3. 決済完了メール
- Template Name: `UCJA - 決済完了`
- 変数: `{{name}}`, `{{application_number}}`, `{{amount}}`, `{{payment_date}}`

##### 4. 試験案内メール
- Template Name: `UCJA - 試験案内`
- 変数: `{{name}}`, `{{exam_date}}`, `{{exam_time}}`, `{{exam_url}}`

##### 5. チームメンバー支払いリンク
- Template Name: `UCJA - チームメンバー支払いリンク`
- 変数: `{{member_name}}`, `{{team_name}}`, `{{payment_url}}`, `{{amount}}`

##### 6. 一般お知らせ（運営用）
- Template Name: `UCJA - 一般お知らせ`
- `email-templates/06-general-notice.html` の内容をコピー
- 変数: `{{name}}`, `{{subject}}`, `{{message}}`, `{{send_date}}`, `{{mypage_url}}`, `{{website_url}}`

### Step 6: Template ID を config.php に設定

各テンプレート作成後、Template ID（`d-xxxxxxxx` 形式）をコピーして、`config/config.php` に設定：

```php
// SendGrid Dynamic Templates ID
define('TEMPLATE_APPLICATION_COMPLETE', 'd-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
define('TEMPLATE_KYC_COMPLETE', 'd-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
define('TEMPLATE_PAYMENT_COMPLETE', 'd-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
define('TEMPLATE_EXAM_NOTIFICATION', 'd-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
define('TEMPLATE_TEAM_PAYMENT_REQUEST', 'd-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
define('TEMPLATE_GENERAL_NOTICE', 'd-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
```

### Step 7: テスト送信

テスト用PHPスクリプトを作成：

```php
<?php
// test-email.php
require_once __DIR__ . '/lib/EmailService.php';

$emailService = new EmailService();

// シンプルなメール送信テスト
$result = $emailService->sendEmail(
    'your-email@example.com',
    'テストメール',
    '<h1>これはテストです</h1><p>メール送信が動作しています！</p>'
);

var_dump($result);

// Dynamic Templateを使ったテスト
$result2 = $emailService->sendTemplateEmail(
    'your-email@example.com',
    TEMPLATE_APPLICATION_COMPLETE,
    [
        'name' => 'テスト太郎',
        'application_number' => 'TEST-001',
        'participation_type' => '個人戦',
        'email' => 'test@example.com',
        'mypage_url' => 'https://challenge.univ-cambridge-japan.academy/my-page/dashboard.php',
        'website_url' => 'https://challenge.univ-cambridge-japan.academy'
    ]
);

var_dump($result2);
```

実行：
```bash
php test-email.php
```

### Step 8: 管理者アカウントの作成

初回管理者アカウントを作成：

```sql
-- Supabase SQL Editor で実行
INSERT INTO admin_users (username, email, password_hash, role, is_active)
VALUES (
    'admin',
    'admin@univ-cambridge-japan.academy',
    '$2y$10$xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx', -- password_hash('your-password', PASSWORD_DEFAULT)
    'super_admin',
    TRUE
);
```

または、PHPスクリプトで作成：

```php
<?php
// create-admin.php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/lib/SupabaseClient.php';

$supabase = new SupabaseClient(SUPABASE_URL, SUPABASE_SERVICE_KEY);

$adminData = [
    'username' => 'admin',
    'email' => 'admin@univ-cambridge-japan.academy',
    'password_hash' => password_hash('your-secure-password', PASSWORD_DEFAULT),
    'role' => 'super_admin',
    'is_active' => true
];

$result = $supabase->insert('admin_users', $adminData);
var_dump($result);
```

---

## 🎯 次のステップ（Phase 2〜4）

### Phase 2: 自動送信メール実装
- [ ] 申込完了時のメール送信
- [ ] KYC完了時のメール送信
- [ ] 決済完了時のメール送信
- [ ] チームメンバーへの支払いリンク送信（Stripe統合後）

### Phase 3: 管理画面構築
- [ ] `admin/login.php` - 管理者ログイン
- [ ] `admin/dashboard.php` - ダッシュボード
- [ ] `admin/send-email.php` - 一斉送信画面
- [ ] `admin/email-history.php` - 送信履歴
- [ ] `admin/email-templates.php` - テンプレート管理

### Phase 4: 高度な機能
- [ ] Webhook統合（配信状況トラッキング）
- [ ] スケジュール送信
- [ ] A/Bテスト機能

---

## 📧 メール送信の使い方

### 基本的な使い方

```php
<?php
require_once __DIR__ . '/lib/EmailService.php';

$emailService = new EmailService();

// シンプルなメール
$emailService->sendEmail(
    'recipient@example.com',
    '件名',
    '<html>本文</html>'
);

// テンプレートメール
$emailService->sendTemplateEmail(
    'recipient@example.com',
    TEMPLATE_APPLICATION_COMPLETE,
    [
        'name' => '山田太郎',
        'application_number' => 'UCJA-12345'
    ]
);

// 一斉送信
$emailService->sendBulkEmail(
    [
        ['email' => 'user1@example.com', 'name' => '太郎'],
        ['email' => 'user2@example.com', 'name' => '花子']
    ],
    '件名',
    '<html>本文</html>'
);
```

---

## 🔍 トラブルシューティング

### エラー: "SENDGRID_API_KEY is not defined"
→ `config/config.php` に API Key が設定されているか確認

### エラー: "Class 'SendGrid\Mail\Mail' not found"
→ `composer update` を実行してSendGrid SDKをインストール

### メールが届かない
1. SendGridダッシュボードで Activity を確認
2. DNS設定（SPF, DKIM）が正しいか確認
3. 送信元アドレスがVerify済みか確認

### テンプレート変数が表示されない
→ SendGrid Dynamic Template で変数名が正しく設定されているか確認（`{{variable_name}}` 形式）

---

## 📞 サポート

質問や問題がある場合：
- SendGridドキュメント: https://docs.sendgrid.com/
- SendGridサポート: サポートチケットを作成

---

**次は Phase 2（自動送信メール実装）に進みますか？**

