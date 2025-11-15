# Stripe統合 テストガイド

**作成日**: 2025-11-10  
**ステータス**: Stripe統合完了 ✅

---

## 📋 目次

1. [実装完了内容](#実装完了内容)
2. [テスト用カード情報](#テスト用カード情報)
3. [テスト手順](#テスト手順)
4. [API エンドポイント一覧](#apiエンドポイント一覧)
5. [Webhook設定](#webhook設定)
6. [トラブルシューティング](#トラブルシューティング)

---

## ✅ 実装完了内容

### 1. SetupIntent（カード登録）
- ✅ `api/create-setup-intent.php` - SetupIntent作成API
- ✅ `api/save-payment-method.php` - PaymentMethod ID保存API
- ✅ `stripe-checkout-setup.php` - カード登録画面（Stripe Elements統合済み）

### 2. PaymentIntent（後日課金）
- ✅ `api/execute-deferred-payment.php` - 後日課金実行API
- ✅ `api/create-payment-intent.php` - 即時決済API（既存）

### 3. Webhook処理
- ✅ `api/stripe-webhook.php` - Webhookイベント処理
  - ✅ `payment_intent.succeeded` - 決済成功
  - ✅ `payment_intent.payment_failed` - 決済失敗
  - ✅ `payment_intent.canceled` - 決済キャンセル
  - ✅ `setup_intent.succeeded` - カード登録成功
  - ✅ `setup_intent.setup_failed` - カード登録失敗
  - ✅ `charge.refunded` - 返金処理

### 4. 設定
- ✅ `config/config.php` - Stripeテストキー設定済み

---

## 💳 テスト用カード情報

Stripeが提供するテストカード番号を使用してテストできます。

### 成功するカード

| カード番号 | ブランド | 用途 |
|-----------|---------|------|
| `4242 4242 4242 4242` | Visa | 通常の成功テスト |
| `5555 5555 5555 4444` | Mastercard | 通常の成功テスト |
| `3782 822463 10005` | American Express | 通常の成功テスト |

### 失敗するカード（エラーテスト用）

| カード番号 | エラー内容 |
|-----------|-----------|
| `4000 0000 0000 0002` | カードが拒否されました |
| `4000 0000 0000 9995` | 残高不足 |
| `4000 0000 0000 0069` | 有効期限切れ |
| `4000 0000 0000 0127` | CVCエラー |

### その他の入力値（どのカードでも共通）

- **有効期限**: 未来の任意の日付（例: `12/25`）
- **CVC**: 任意の3桁（例: `123`）
- **郵便番号**: 任意の値（例: `12345`）

---

## 🧪 テスト手順

### テスト1: カード登録フロー（SetupIntent）

このテストで、後日課金に必要なカード情報を登録します。

#### 手順

1. **申込フォームから申込を完了**
   ```
   http://localhost:8000/index.php#application
   ```
   - 個人戦 or チーム戦を選択
   - 必要情報を入力して申込完了
   - 申込完了画面で「申込番号」と「ログイン情報」をメモ

2. **本人確認画面へ遷移**（モック）
   ```
   http://localhost:8000/kyc-verification.php
   ```
   - 学生証をアップロード（モック）
   - 本人確認完了

3. **カード登録画面へ遷移**
   ```
   http://localhost:8000/stripe-checkout-setup.php
   ```
   - SessionStorageから申込情報が自動取得される
   - テストカード情報を入力:
     - カード番号: `4242 4242 4242 4242`
     - 有効期限: `12/25`
     - CVC: `123`
   - 利用規約に同意
   - 「カード情報を登録する」ボタンをクリック

4. **結果確認**
   - 成功メッセージが表示される
   - `setup-complete.php` に遷移
   - Supabaseの `applications` テーブルを確認:
     - `stripe_setup_intent_id` が設定されている
     - `stripe_payment_method_id` が設定されている
     - `card_setup_status` が `completed` になっている

#### 確認するポイント

- ✅ Stripe Elements が正しく表示される
- ✅ カード情報入力がリアルタイムで検証される
- ✅ エラーメッセージが適切に表示される
- ✅ 成功時に完了画面に遷移する
- ✅ データベースに正しく保存される

---

### テスト2: 後日課金フロー（PaymentIntent）

SetupIntentで保存したPaymentMethod IDを使って、後日課金を実行します。

#### 手順

1. **テスト1でカード登録を完了**しておく

2. **本人確認ステータスを承認済みに変更**（手動）
   - Supabase Dashboardで `applications` テーブルを開く
   - 該当の申込の `kyc_status` を `approved` に変更

3. **後日課金APIを実行**

   **方法A: curlコマンド**
   ```bash
   curl -X POST http://localhost:8000/api/execute-deferred-payment.php \
     -H "Content-Type: application/json" \
     -d '{"application_id": "YOUR_APPLICATION_ID"}'
   ```

   **方法B: ブラウザのコンソールから**
   ```javascript
   fetch('/api/execute-deferred-payment.php', {
     method: 'POST',
     headers: { 'Content-Type': 'application/json' },
     body: JSON.stringify({
       application_id: 'YOUR_APPLICATION_ID'
     })
   })
   .then(res => res.json())
   .then(data => console.log(data));
   ```

4. **結果確認**
   - APIレスポンスが `success: true` を返す
   - Supabaseの `applications` テーブルを確認:
     - `payment_status` が `completed` になっている
     - `stripe_payment_intent_id` が設定されている
     - `payment_completed_at` がタイムスタンプで記録されている
   - Stripe Dashboardで決済が成功していることを確認

#### 確認するポイント

- ✅ 本人確認完了前は課金できない
- ✅ 保存されたPaymentMethodで課金が実行される
- ✅ 決済成功時にデータベースが更新される
- ✅ 決済失敗時にエラーメッセージが返る

---

### テスト3: 即時決済フロー（マイページから）

マイページの「支払い状況」画面から即時決済を実行します。

#### 手順

1. **マイページにログイン**
   ```
   http://localhost:8000/login.php
   ```
   - メールアドレスと申込番号でログイン

2. **支払い状況ページへ**
   ```
   http://localhost:8000/my-page/payment-status.php
   ```
   - 「今すぐ支払う」ボタンをクリック

3. **決済画面で支払い**
   ```
   http://localhost:8000/stripe-checkout-payment.php
   ```
   - テストカード情報を入力
   - 決済実行

4. **結果確認**
   - 決済完了画面に遷移
   - マイページで支払いステータスが「完了」になっている

---

### テスト4: エラーハンドリング

エラーケースのテストを行います。

#### テストケース

1. **カード拒否エラー**
   - カード番号: `4000 0000 0000 0002`
   - エラーメッセージが表示されることを確認

2. **残高不足エラー**
   - カード番号: `4000 0000 0000 9995`
   - 適切なエラーメッセージが表示されることを確認

3. **不正な申込IDでAPI呼び出し**
   ```bash
   curl -X POST http://localhost:8000/api/create-setup-intent.php \
     -H "Content-Type: application/json" \
     -d '{"application_id": "invalid-id"}'
   ```
   - エラーレスポンスが返ることを確認

---

## 🔌 API エンドポイント一覧

### 1. SetupIntent作成

**エンドポイント**: `POST /api/create-setup-intent.php`

**リクエスト**:
```json
{
  "application_id": "uuid"
}
```

**レスポンス**:
```json
{
  "success": true,
  "clientSecret": "seti_xxx_secret_xxx",
  "setupIntentId": "seti_xxx",
  "application_number": "APP-20251110-0001"
}
```

---

### 2. PaymentMethod保存

**エンドポイント**: `POST /api/save-payment-method.php`

**リクエスト**:
```json
{
  "application_id": "uuid",
  "payment_method_id": "pm_xxx",
  "setup_intent_id": "seti_xxx"
}
```

**レスポンス**:
```json
{
  "success": true,
  "message": "PaymentMethod IDが正常に保存されました"
}
```

---

### 3. 後日課金実行

**エンドポイント**: `POST /api/execute-deferred-payment.php`

**リクエスト**:
```json
{
  "application_id": "uuid"
}
```

**レスポンス**:
```json
{
  "success": true,
  "paymentIntentId": "pi_xxx",
  "status": "succeeded",
  "amount": 8800,
  "application_number": "APP-20251110-0001"
}
```

---

### 4. 即時決済（PaymentIntent作成）

**エンドポイント**: `POST /api/create-payment-intent.php`

**リクエスト**:
```json
{
  "application_id": "uuid"
}
```

**レスポンス**:
```json
{
  "success": true,
  "clientSecret": "pi_xxx_secret_xxx",
  "amount": 8800,
  "application_number": "APP-20251110-0001"
}
```

---

### 5. Webhook

**エンドポイント**: `POST /api/stripe-webhook.php`

**ヘッダー**:
```
Stripe-Signature: xxx
```

**処理するイベント**:
- `payment_intent.succeeded`
- `payment_intent.payment_failed`
- `payment_intent.canceled`
- `setup_intent.succeeded`
- `setup_intent.setup_failed`
- `charge.refunded`

---

## 🪝 Webhook設定

### ローカル開発環境でのWebhookテスト

ローカル環境でWebhookをテストするには、Stripe CLIを使用します。

#### 1. Stripe CLIのインストール

**Mac (Homebrew)**:
```bash
brew install stripe/stripe-cli/stripe
```

**Windows**:
```powershell
scoop bucket add stripe https://github.com/stripe/scoop-stripe-cli.git
scoop install stripe
```

#### 2. Stripe CLIでログイン

```bash
stripe login
```

#### 3. Webhookをローカルにフォワード

```bash
stripe listen --forward-to localhost:8000/api/stripe-webhook.php
```

このコマンドを実行すると、**Webhook署名シークレット**（`whsec_xxx`）が表示されます。

#### 4. Webhook署名シークレットを設定

`config/config.php` に設定:
```php
define('STRIPE_WEBHOOK_SECRET', 'whsec_xxx'); // Stripe CLIで表示された値
```

#### 5. テストイベントを送信

```bash
stripe trigger payment_intent.succeeded
stripe trigger setup_intent.succeeded
stripe trigger payment_intent.payment_failed
```

---

### 本番環境でのWebhook設定

1. **Stripeダッシュボード** → **開発者** → **Webhook**
2. **エンドポイントを追加**をクリック
3. エンドポイントURL: `https://your-domain.com/api/stripe-webhook.php`
4. リッスンするイベントを選択:
   - `payment_intent.succeeded`
   - `payment_intent.payment_failed`
   - `payment_intent.canceled`
   - `setup_intent.succeeded`
   - `setup_intent.setup_failed`
   - `charge.refunded`
5. 署名シークレットをコピーして `config/config.php` に設定

---

## 🐛 トラブルシューティング

### 問題1: Stripe Elementsが表示されない

**原因**:
- 公開可能キーが正しく設定されていない
- JavaScriptエラーが発生している

**解決策**:
```javascript
// ブラウザのコンソールでエラーを確認
console.log('Stripe Key:', '<?php echo STRIPE_PUBLISHABLE_KEY; ?>');
```

---

### 問題2: SetupIntentの作成に失敗する

**原因**:
- シークレットキーが正しく設定されていない
- Supabaseとの通信エラー

**解決策**:
```bash
# APIエンドポイントを直接テスト
curl -X POST http://localhost:8000/api/create-setup-intent.php \
  -H "Content-Type: application/json" \
  -d '{"application_id": "YOUR_APPLICATION_ID"}'
```

---

### 問題3: 後日課金が実行されない

**原因**:
- 本人確認ステータスが `approved` になっていない
- PaymentMethod IDが保存されていない
- カード情報が無効

**解決策**:
```sql
-- Supabaseで確認
SELECT 
  id, 
  application_number,
  kyc_status,
  stripe_payment_method_id,
  card_setup_status,
  payment_status
FROM applications
WHERE id = 'YOUR_APPLICATION_ID';
```

---

### 問題4: Webhookが受信されない

**原因**:
- Webhook署名シークレットが間違っている
- エンドポイントURLが間違っている

**解決策**:
```bash
# Stripe CLIでログを確認
stripe logs tail

# Webhookのテスト送信
stripe trigger payment_intent.succeeded
```

---

## 📚 参考資料

### Stripe公式ドキュメント
- [SetupIntent API](https://stripe.com/docs/api/setup_intents)
- [PaymentIntent API](https://stripe.com/docs/api/payment_intents)
- [Webhook](https://stripe.com/docs/webhooks)
- [Test Cards](https://stripe.com/docs/testing)

### プロジェクト内ドキュメント
- `docs/stripe_後日課金_概要.md` - 後日課金の概要
- `docs/stripe_後日課金_詳細.md` - 実装詳細
- `SESSION-HANDOVER.md` - プロジェクト全体の状況
- `DEVELOPMENT-STATUS.md` - 開発進捗

---

## ✅ テスト完了チェックリスト

### SetupIntent（カード登録）
- [ ] カード情報が正しく入力できる
- [ ] カードバリデーションが動作する
- [ ] カード登録が成功する
- [ ] PaymentMethod IDがDBに保存される
- [ ] エラーハンドリングが正しく動作する

### PaymentIntent（後日課金）
- [ ] 本人確認完了後に課金が実行される
- [ ] 決済が成功する
- [ ] DBのステータスが更新される
- [ ] 決済失敗時にエラーが記録される

### Webhook
- [ ] Webhookイベントが受信される
- [ ] 署名検証が動作する
- [ ] 各イベントで適切な処理が実行される
- [ ] DBが正しく更新される

### エラーハンドリング
- [ ] カード拒否エラーが適切に処理される
- [ ] 残高不足エラーが適切に処理される
- [ ] 不正なリクエストがブロックされる

---

**テストが完了したら、本番キーへの切り替えとWebhook設定を行ってください！** 🎉

