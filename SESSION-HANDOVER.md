# セッション引き継ぎドキュメント

最終更新日: 2025-11-10 (22:30)

---

## 🎯 現在の状況

### **現在のフェーズ:**

- Phase 3（メールシステム）: 85% 完了 - バッチ処理のみ残
- Phase 4（Stripe 統合）: 95% 完了 - バッチ処理連携のみ残

### **最新の達成:**

✅ 申込 → カード登録 → 本人確認完了 →`charge_scheduled` まで動作確認済み
✅ `scheduled_charges` テーブルにレコード挿入確認済み
✅ 管理画面（メールテンプレート管理・一斉送信）実装完了
✅ サブディレクトリ（`ucja_test`）対応完了

---

## 🚀 次のタスク（最優先）

### **タスク: バッチ処理実装**

**目的:**

- `scheduled_charges` から課金予定を取得
- Stripe で実際に決済を実行
- 決済完了メールを自動送信
- フロー全体を完結させる

**実装ファイル:**

```
api/batch/process-scheduled-charges.php
```

**実装内容:**

1. `scheduled_charges` テーブルから `status = 'scheduled'` かつ `scheduled_date <= 今日` のレコードを取得
2. 各レコードについて:
   - Stripe PaymentIntent を作成（`stripe_customer_id`, `stripe_payment_method_id` 使用）
   - 決済実行
   - 成功時:
     - `applications.payment_status` → `completed`
     - `applications.application_status` → `confirmed`
     - `scheduled_charges.status` → `completed`
     - 決済完了メール送信（`EmailService::sendEmail()` 使用）
   - 失敗時:
     - `scheduled_charges.status` → `failed`
     - `error_message` 保存
     - `retry_count` インクリメント
     - リトライ機構（最大 3 回）
3. ログ記録

**実行方法:**

- 手動実行: `php api/batch/process-scheduled-charges.php`
- 定期実行: cron または Supabase Functions

---

## 📂 重要なファイル構成

### **データベース関連（最重要）**

#### 現在の DB スキーマ:

```
database/supabase-schema-v3-deferred-payment.sql
```

- 最新の DB 構造定義
- トリガー `schedule_charge_on_kyc_completion` を含む

#### DB 構造ドキュメント:

```
database/CURRENT-DATABASE-STRUCTURE.md
```

- 全テーブルの詳細説明
- カラム定義、制約、インデックス
- 重要なノートとサポート情報

#### メールテンプレート:

```
database/hybrid-email-templates-schema.sql
database/insert-email-templates.sql（基本 5 種類）
database/insert-additional-email-templates.sql（追加 5 種類）
```

#### 管理者アカウント:

```
database/create-default-admin.sql
```

- ユーザー名: `admin`
- メール: `admin@example.com`
- パスワード: `admin123`

---

### **Stripe 統合**

#### API エンドポイント:

```
api/create-setup-intent.php        - Stripe Customer + SetupIntent 作成
api/save-payment-method.php         - PaymentMethod ID 保存
api/kyc/mark-as-completed.php       - 本人確認完了マーク（トリガー発動）
api/execute-deferred-payment.php    - 後日課金実行（手動テスト用）
api/stripe-webhook.php              - Stripe Webhook 処理
```

#### フロントエンド:

```
stripe-checkout-setup.php           - カード登録画面
kyc-verification.php                - 本人確認画面（撮影→API 呼び出し）
kyc-complete.php                    - 本人確認完了画面
setup-complete.php                  - カード登録完了画面
```

#### 設定:

```
config/config.php
```

- `USE_SCHEDULED_CHARGES = true` (トリガー経由のバッチ処理方式)
- Stripe テストキー設定済み

---

### **メールシステム**

#### メール送信クラス:

```
lib/EmailService.php
```

- SendGrid Mail Send API 使用
- `sendEmail($to, $subject, $bodyHtml, $bodyText, $templateId = null)`

#### 管理画面:

```
admin/email-templates.php           - テンプレート管理画面
admin/send-email.php                - 一斉送信画面
admin/email-history.php             - 送信履歴画面
api/admin/update-email-template.php - テンプレート更新 API
api/admin/send-bulk-email.php       - 一斉送信 API
```

---

### **認証・セッション**

#### ユーザー認証:

```
lib/AuthHelper.php                  - ユーザー認証ヘルパー
api/auth/login.php                  - ログイン API
login.php                           - ログイン画面
logout.php                          - ログアウト処理
```

#### 管理者認証:

```
lib/AdminAuthHelper.php             - 管理者認証ヘルパー
api/admin/login.php                 - 管理者ログイン API
admin/login.php                     - 管理者ログイン画面
api/admin/logout.php                - 管理者ログアウト API
```

**重要:** サブディレクトリ対応済み（相対パスでリダイレクト）

---

### **マイページ**

```
my-page/dashboard.php               - ダッシュボード
my-page/application-detail.php      - 申込詳細
my-page/payment-status.php          - 支払い状況
my-page/kyc-status.php              - 本人確認状況
my-page/profile.php                 - プロフィール編集
my-page/team-status.php             - チーム管理（チーム戦専用）
```

**重要な改善:**

- `charge_scheduled` 状態の表示対応済み
- 「必要なアクション」セクションで適切なメッセージ表示

---

### **Supabase クライアント**

```
lib/SupabaseClient.php
```

- Supabase REST API ラッパー
- `select()`, `insert()`, `update()`, `delete()` メソッド
- フィルター（`eq()`, `neq()`, `gt()`, `gte()`, `lt()`, `lte()`, `like()`, `in()`）
- ソート（`order()`）

**重要な修正:**

- Boolean 値の正しい処理（`true`/`false` 文字列）
- URL エンコーディングの修正（二重エンコード回避）

---

## 📊 データベース構造（重要テーブル）

### **applications（申込情報）**

**重要カラム:**

```sql
id                          UUID PRIMARY KEY
application_number          VARCHAR(50) UNIQUE (例: APP-2025-00001)
stripe_customer_id          VARCHAR(255) -- Stripe Customer ID ✨ 重要！
stripe_payment_method_id    VARCHAR(255) -- 保存されたカード情報
card_registered             BOOLEAN DEFAULT FALSE
payment_status              VARCHAR(20) DEFAULT 'pending'
  -- 'pending', 'card_registered', 'processing', 'completed', 'failed', 'refunded'
kyc_status                  VARCHAR(20) DEFAULT 'pending'
  -- 'pending', 'in_progress', 'completed', 'failed', 'rejected'
application_status          VARCHAR(20) DEFAULT 'draft'
  -- 'draft', 'submitted', 'card_pending', 'kyc_pending',
  -- 'charge_scheduled', 'payment_processing', 'confirmed', 'cancelled'
```

**ステータスフロー:**

```
draft → submitted → card_pending → kyc_pending →
charge_scheduled → payment_processing → confirmed
```

---

### **scheduled_charges（課金スケジュール）**

**重要カラム:**

```sql
id                          UUID PRIMARY KEY
application_id              UUID REFERENCES applications(id)
amount                      INTEGER NOT NULL
stripe_customer_id          VARCHAR(255) NOT NULL -- ✨ 必須！
stripe_payment_method_id    VARCHAR(255) NOT NULL -- ✨ 必須！
scheduled_date              DATE NOT NULL
status                      VARCHAR(20) DEFAULT 'scheduled'
  -- 'scheduled', 'processing', 'completed', 'failed', 'cancelled'
executed_at                 TIMESTAMP
stripe_payment_intent_id    VARCHAR(255)
error_code                  VARCHAR(50)
error_message               TEXT
retry_count                 INTEGER DEFAULT 0
```

**トリガー:**

```sql
CREATE TRIGGER trigger_schedule_charge_on_kyc_completion
    BEFORE UPDATE OF kyc_status ON applications
    FOR EACH ROW
    EXECUTE FUNCTION schedule_charge_on_kyc_completion();
```

**動作:**

- `kyc_status` が `completed` に更新されると自動発動
- `scheduled_charges` にレコードを自動挿入
- `application_status` を `charge_scheduled` に更新

---

### **email_templates（メールテンプレート）**

**重要カラム:**

```sql
id                          UUID PRIMARY KEY
template_type               VARCHAR(50) NOT NULL UNIQUE
  -- 'application_complete', 'kyc_complete', 'payment_complete', など
template_name               VARCHAR(100) NOT NULL
subject                     VARCHAR(255) NOT NULL
body_html                   TEXT NOT NULL
body_text                   TEXT NOT NULL
category                    VARCHAR(50) DEFAULT 'application_flow'
  -- 'application_flow', 'exam_related', 'announcements', 'post_exam'
sort_order                  INTEGER DEFAULT 0
is_active                   BOOLEAN DEFAULT TRUE
```

---

### **email_logs（メール送信履歴）**

**重要カラム:**

```sql
id                          UUID PRIMARY KEY
application_id              UUID REFERENCES applications(id)
email_type                  VARCHAR(50) NOT NULL -- テンプレートタイプ
recipient_email             VARCHAR(255) NOT NULL
subject                     VARCHAR(255) NOT NULL
status                      VARCHAR(20) DEFAULT 'pending'
  -- 'pending', 'sent', 'failed', 'bounced'
sent_at                     TIMESTAMP
```

---

## 🔐 認証情報

### **管理画面ログイン:**

```
URL: http://uplab.xsrv.jp/ucja_test/admin/login.php
ユーザー名: admin
パスワード: admin123
```

### **Stripe（テストモード）:**

```
公開可能キー: pk_test_51RavjIQpaVSBuBbAQ77ub3e7gpPzmxjOUC8BeMhYyi2yHqufTRHeS9d1Jlz9FHFWMmRQAaYejnUrhmRHbGKZAzme00f2hlwf8M
シークレットキー: （config.phpで設定済み）
```

**テストカード:**

```
カード番号: 4242 4242 4242 4242
有効期限: 任意の未来の日付
CVC: 任意の3桁
```

### **SendGrid:**

```
API キー: config/config.php に設定済み
差出人メール: contact@univ-cambridge-japan.academy
```

### **Supabase:**

```
URL: https://pxfshwnmmmpxymcqfjbt.supabase.co
Anon Key: config/config.php に設定済み
Service Key: config/config.php に設定済み
```

---

## 🐛 既知の問題と解決済み

### **✅ 解決済み:**

1. **`stripe_customer_id` が NULL でトリガーエラー**

   - 修正: `api/create-setup-intent.php` で Stripe Customer を自動作成

2. **サブディレクトリでログインリダイレクトが `/login.php` に飛ぶ**

   - 修正: `lib/AuthHelper.php` と `lib/AdminAuthHelper.php` で相対パス使用

3. **`charge_scheduled` 状態でアクションが表示されない**

   - 修正: `my-page/dashboard.php` で適切なメッセージ表示

4. **`SupabaseClient` の Boolean 値処理**

   - 修正: `true`/`false` 文字列として処理

5. **管理画面の `@apply` Tailwind CSS エラー**
   - 修正: 標準 CSS プロパティに置き換え

---

## 📝 次のセッションで確認すること

### **1. バッチ処理実装前の確認:**

```sql
-- scheduled_chargesにレコードが入っているか確認
SELECT
    id,
    application_id,
    amount,
    stripe_customer_id,
    stripe_payment_method_id,
    scheduled_date,
    status,
    created_at
FROM scheduled_charges
WHERE status = 'scheduled'
ORDER BY created_at DESC;
```

期待される結果:

- レコードが 1 件以上存在
- `stripe_customer_id` が `cus_xxxxx` 形式で入っている
- `stripe_payment_method_id` が `pm_xxxxx` 形式で入っている
- `status = 'scheduled'`

---

### **2. バッチ処理実装後のテスト:**

1. バッチ処理を手動実行:

   ```bash
   php api/batch/process-scheduled-charges.php
   ```

2. 結果確認:

   ```sql
   -- scheduled_chargesのステータス確認
   SELECT id, application_id, status, executed_at, error_message
   FROM scheduled_charges
   ORDER BY created_at DESC LIMIT 5;

   -- applicationsのステータス確認
   SELECT id, application_number, payment_status, application_status
   FROM applications
   ORDER BY created_at DESC LIMIT 5;

   -- メール送信履歴確認
   SELECT id, recipient_email, email_type, status, sent_at
   FROM email_logs
   ORDER BY created_at DESC LIMIT 5;
   ```

3. マイページで確認:

   - `application_status` が `confirmed` に変わる
   - `payment_status` が `completed` に変わる
   - 「必要なアクション」に「すべての手続きが完了しています」と表示

4. メール受信確認:
   - 決済完了メールが届く

---

## 🎯 実装のヒント

### **バッチ処理の基本構造:**

```php
<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../lib/SupabaseClient.php';
require_once __DIR__ . '/../../lib/EmailService.php';

\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
$supabase = new SupabaseClient(SUPABASE_URL, SUPABASE_SERVICE_KEY);

// 1. scheduled_chargesから課金予定を取得
$charges = $supabase->from('scheduled_charges')
    ->select('*')
    ->eq('status', 'scheduled')
    ->lte('scheduled_date', date('Y-m-d'))
    ->execute();

foreach ($charges['data'] as $charge) {
    try {
        // 2. Stripe PaymentIntent作成・実行
        $paymentIntent = \Stripe\PaymentIntent::create([
            'amount' => $charge['amount'],
            'currency' => $charge['currency'],
            'customer' => $charge['stripe_customer_id'],
            'payment_method' => $charge['stripe_payment_method_id'],
            'off_session' => true,
            'confirm' => true,
        ]);

        // 3. 決済成功時の処理
        if ($paymentIntent->status === 'succeeded') {
            // DBを更新
            // メールを送信
        }

    } catch (\Stripe\Exception\CardException $e) {
        // 4. 決済失敗時の処理
        // エラーを記録
        // リトライカウントを更新
    }
}
```

---

## 📚 参考ドキュメント

### **プロジェクト内:**

```
DEVELOPMENT-STATUS.md           - 全体進捗
ADMIN-IMPLEMENTATION-PLAN.md    - 管理画面実装計画
STRIPE-INTEGRATION-GUIDE.md     - Stripe統合ガイド
STRIPE-TEST-GUIDE.md            - Stripeテストガイド
SENDGRID-SETUP-GUIDE.md         - SendGridセットアップ
database/CURRENT-DATABASE-STRUCTURE.md - DB構造詳細
```

### **外部リソース:**

- Stripe API: https://stripe.com/docs/api
- SendGrid API: https://docs.sendgrid.com/
- Supabase API: https://supabase.com/docs

---

**次のセッションでバッチ処理を実装して、フロー全体を完結させましょう！** 🚀
