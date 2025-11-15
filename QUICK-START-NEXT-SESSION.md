# 次セッション クイックスタートガイド

最終更新日: 2025-11-10 (22:30)

---

## ⚡ 5分で状況把握

### **現在地:**
```
申込 → カード登録 → 本人確認完了 → ✅ scheduled_chargesに挿入済み
                                     ↓
                              ⏳ 【次はココ】バッチ処理で決済実行
```

### **次にやること:**
**`api/batch/process-scheduled-charges.php` を実装して決済を完了させる**

---

## 📊 現在の状況まとめ

### **✅ 完了していること:**

- ✅ 申込 → カード登録 → 本人確認完了まで動作確認済み
- ✅ Stripe Customer 自動作成
- ✅ SetupIntent でカード登録
- ✅ 本人確認完了時のトリガー発動
- ✅ `scheduled_charges` テーブルにレコード挿入済み
- ✅ `application_status = 'charge_scheduled'` 状態まで到達
- ✅ マイページで適切なメッセージ表示
- ✅ 管理画面（メールテンプレート管理・一斉送信）完成

### **⏳ 次にやること:**

**バッチ処理実装:**
```
scheduled_chargesから課金実行 → 決済完了 → メール送信 → フロー完結
```

**実装ファイル:**
```
api/batch/process-scheduled-charges.php
```

**処理内容:**
1. ✅ `scheduled_charges` から課金予定を取得（`status = 'scheduled'`）
2. ✅ Stripe で決済実行（PaymentIntent 作成）
3. ✅ DB 更新（`payment_status`, `application_status`）
4. ✅ 決済完了メール送信（`EmailService` 使用）
5. ✅ エラーハンドリング・リトライ（最大3回）

---

## 🚀 すぐに始める手順

### **ステップ1: 現状確認（5分）**

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

**期待される結果:**
- ✅ レコードが 1 件以上存在
- ✅ `stripe_customer_id` が `cus_xxxxx` 形式
- ✅ `stripe_payment_method_id` が `pm_xxxxx` 形式
- ✅ `status = 'scheduled'`

### **ステップ2: バッチ処理実装（30分）**

**実装のテンプレート:**

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
            // applications更新
            $supabase->update('applications', [
                'payment_status' => 'completed',
                'application_status' => 'confirmed',
                'charged_at' => date('Y-m-d H:i:s')
            ], ['id' => 'eq.' . $charge['application_id']]);
            
            // scheduled_charges更新
            $supabase->update('scheduled_charges', [
                'status' => 'completed',
                'executed_at' => date('Y-m-d H:i:s'),
                'stripe_payment_intent_id' => $paymentIntent->id
            ], ['id' => 'eq.' . $charge['id']]);
            
            // メール送信
            EmailService::sendEmail(
                $recipientEmail,
                '決済完了のお知らせ',
                $htmlBody,
                $textBody,
                'payment_complete'
            );
        }
        
    } catch (\Stripe\Exception\CardException $e) {
        // 4. 決済失敗時の処理
        $supabase->update('scheduled_charges', [
            'status' => 'failed',
            'error_code' => $e->getError()->code,
            'error_message' => $e->getMessage(),
            'retry_count' => $charge['retry_count'] + 1
        ], ['id' => 'eq.' . $charge['id']]);
    }
}
```

### **ステップ3: テスト実行（10分）**

```bash
# 手動実行
php api/batch/process-scheduled-charges.php
```

**結果確認:**

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

### **ステップ4: マイページで確認（5分）**

マイページにログインして確認:
- ✅ `application_status` が `confirmed` に変わる
- ✅ `payment_status` が `completed` に変わる
- ✅ 「必要なアクション」に「すべての手続きが完了しています。試験当日をお楽しみに！」と表示
- ✅ メールが届く

---

## 📂 重要な引き継ぎファイル

### **必ず読むべき:**
- 📄 `SESSION-HANDOVER.md` - 詳細な引き継ぎドキュメント（本ファイル）
- 📄 `DEVELOPMENT-STATUS.md` - 全体進捗
- 📄 `database/CURRENT-DATABASE-STRUCTURE.md` - DB構造詳細

### **データベース:**
- `database/supabase-schema-v3-deferred-payment.sql` - 現在のDBスキーマ
- `database/hybrid-email-templates-schema.sql` - メールテンプレートスキーマ
- `database/insert-email-templates.sql` - 初期テンプレート（5種類）
- `database/insert-additional-email-templates.sql` - 追加テンプレート（5種類）
- `database/create-default-admin.sql` - 管理者アカウント作成

### **Stripe統合:**
- `api/create-setup-intent.php` - Stripe Customer + SetupIntent作成
- `api/kyc/mark-as-completed.php` - 本人確認完了マーク（トリガー発動）
- `api/execute-deferred-payment.php` - 後日課金実行（参考用）

### **メールシステム:**
- `lib/EmailService.php` - SendGridメール送信クラス
- `admin/email-templates.php` - テンプレート管理画面
- `admin/send-email.php` - 一斉送信画面

### **認証:**
- `lib/AuthHelper.php` - ユーザー認証ヘルパー（サブディレクトリ対応済み）
- `lib/AdminAuthHelper.php` - 管理者認証ヘルパー（サブディレクトリ対応済み）

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
API キー: config/config.phpに設定済み
差出人メール: contact@univ-cambridge-japan.academy
```

### **Supabase:**
```
URL: https://pxfshwnmmmpxymcqfjbt.supabase.co
Anon Key: config/config.phpに設定済み
Service Key: config/config.phpに設定済み
```

---

## 🐛 既知の問題（すべて解決済み）

### **✅ 解決済み:**

1. ✅ **`stripe_customer_id` が NULL でトリガーエラー**
   - 修正: `api/create-setup-intent.php` で Stripe Customer を自動作成

2. ✅ **サブディレクトリでログインリダイレクトが `/login.php` に飛ぶ**
   - 修正: `lib/AuthHelper.php` と `lib/AdminAuthHelper.php` で相対パス使用

3. ✅ **`charge_scheduled` 状態でアクションが表示されない**
   - 修正: `my-page/dashboard.php` で適切なメッセージ表示

4. ✅ **`SupabaseClient` の Boolean 値処理**
   - 修正: `true`/`false` 文字列として処理

5. ✅ **管理画面の `@apply` Tailwind CSS エラー**
   - 修正: 標準 CSS プロパティに置き換え

---

## 📋 チェックリスト

### **セッション開始時:**
- [ ] `SESSION-HANDOVER.md` を読む
- [ ] `scheduled_charges` テーブルを確認（SQL実行）
- [ ] 現在のステータスを把握（`charge_scheduled` 状態であることを確認）

### **バッチ処理実装:**
- [ ] `api/batch/` ディレクトリを作成
- [ ] `process-scheduled-charges.php` を作成
- [ ] Stripe PaymentIntent 作成ロジック実装
- [ ] 決済成功時のDB更新実装
- [ ] 決済成功時のメール送信実装
- [ ] 決済失敗時のエラーハンドリング実装
- [ ] リトライ機構実装（最大3回）

### **テスト:**
- [ ] バッチ処理を手動実行
- [ ] `scheduled_charges.status` が `completed` に変わることを確認
- [ ] `applications.payment_status` が `completed` に変わることを確認
- [ ] `applications.application_status` が `confirmed` に変わることを確認
- [ ] メール送信履歴（`email_logs`）を確認
- [ ] マイページで表示を確認
- [ ] 実際にメールが届くことを確認

### **完了後:**
- [ ] `DEVELOPMENT-STATUS.md` を更新
- [ ] Phase 3 を 100% 完了にマーク
- [ ] Phase 4 を 100% 完了にマーク

---

## 🎯 目標

**このセッションで達成すべきこと:**
1. ✅ バッチ処理実装
2. ✅ 決済フロー完全動作確認
3. ✅ メール自動送信動作確認
4. ✅ Phase 3 & Phase 4 完了

**達成後の状態:**
```
申込 → カード登録 → 本人確認 → 決済 → メール送信 → 完了 ✅
```

---

## 💡 ヒント

### **バッチ処理のポイント:**

1. **トランザクション的な処理:**
   - Stripe決済が成功してから DB を更新
   - DB更新が失敗したら、ログに記録して次回リトライ

2. **エラーハンドリング:**
   - カードエラー（`CardException`）
   - ネットワークエラー（`ApiConnectionException`）
   - Stripe API エラー（`ApiErrorException`）

3. **リトライロジック:**
   - `retry_count < 3` の場合のみリトライ
   - 3回失敗したら `status = 'failed'` で確定

4. **ログ記録:**
   - 成功・失敗にかかわらずログを記録
   - エラーメッセージは必ず保存

5. **メール送信:**
   - `EmailService::sendEmail()` を使用
   - テンプレートIDは `'payment_complete'`
   - 失敗してもバッチ処理は続行（エラーログのみ記録）

---

## 📞 困ったら

1. **`SESSION-HANDOVER.md`** を読む（詳細情報）
2. **`database/CURRENT-DATABASE-STRUCTURE.md`** を読む（DB構造）
3. **`DEVELOPMENT-STATUS.md`** を読む（全体像）
4. **既存の実装**（`api/execute-deferred-payment.php`）を参考にする

---

**では、バッチ処理実装を始めましょう！** 🚀

