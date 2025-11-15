# Stripe 統合ガイド

## 📋 現在の状態

現在、以下のモックページが実装されています：

- `kyc-verification.php` - Liquid 本人確認ページ（モック）
- `kyc-complete.php` - 本人確認完了ページ
- `stripe-checkout.php` - Stripe Checkout ページ（モック）
- `payment-complete.php` - 決済完了ページ

## 🔄 申込フロー

```
1. 申込フォーム入力（index.php#application）
   ↓
2. Liquid本人確認（kyc-verification.php）
   ↓
3. 本人確認完了（kyc-complete.php）
   ↓
4. Stripe Checkout（stripe-checkout.php）
   ↓
5. 決済完了（payment-complete.php）
```

## 🔧 本番実装に必要な情報

### 1. Stripe API キー

**必要な情報：**

- Publishable Key（公開可能キー）: `pk_live_...` または `pk_test_...`
- Secret Key（秘密キー）: `sk_live_...` または `sk_test_...`

**取得方法：**

1. [Stripe Dashboard](https://dashboard.stripe.com/) にログイン
2. 「開発者」→「API キー」から取得

### 2. 商品設定（Price ID）

Stripe ダッシュボードで商品を作成する必要があります：

**商品 1: 早割価格**

- 商品名: 英語検定 2026 参加費（早割）
- 価格: ¥10,000
- Price ID: `price_xxxxx` ← この ID が必要

**商品 2: 通常価格**

- 商品名: 英語検定 2026 参加費（通常）
- 価格: ¥20,000
- Price ID: `price_yyyyy` ← この ID が必要

### 3. Webhook 設定

**Success URL:**

```
https://yourdomain.com/payment-complete.php?session_id={CHECKOUT_SESSION_ID}
```

**Cancel URL:**

```
https://yourdomain.com/stripe-checkout.php?canceled=true
```

**Webhook Endpoint:**

```
https://yourdomain.com/stripe-webhook.php
```

### 4. Liquid の設定

**必要な情報：**

- Liquid API Key
- Liquid Verification URL
- Redirect URL: `https://yourdomain.com/kyc-complete.php`

## 📝 実装手順

### ステップ 1: Stripe Checkout の実装

`stripe-checkout.php`を以下のように変更：

```php
<?php
require_once 'vendor/autoload.php';

\Stripe\Stripe::setApiKey('sk_test_...');  // 本番環境では sk_live_...

try {
    $session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price' => 'price_xxxxx',  // 商品のPrice ID
            'quantity' => 1,
        ]],
        'mode' => 'payment',
        'success_url' => 'https://yourdomain.com/payment-complete.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => 'https://yourdomain.com/stripe-checkout.php?canceled=true',
        'customer_email' => $_GET['email'] ?? '',
        'metadata' => [
            'application_id' => $_GET['app_id'] ?? '',
        ],
    ]);

    // Checkout Session IDを使ってリダイレクト
    header("Location: " . $session->url);
    exit();
} catch (Exception $e) {
    echo "エラーが発生しました: " . $e->getMessage();
}
?>
```

### ステップ 2: Webhook ハンドラの作成

`stripe-webhook.php`を作成：

```php
<?php
require_once 'vendor/autoload.php';

\Stripe\Stripe::setApiKey('sk_test_...');

$endpoint_secret = 'whsec_...';  // Webhook Signing Secret

$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];

try {
    $event = \Stripe\Webhook::constructEvent(
        $payload, $sig_header, $endpoint_secret
    );

    switch ($event->type) {
        case 'checkout.session.completed':
            $session = $event->data->object;

            // データベースに保存
            // メール送信
            // 受験票生成

            break;
        default:
            echo 'Received unknown event type ' . $event->type;
    }

    http_response_code(200);
} catch(\UnexpectedValueException $e) {
    http_response_code(400);
    exit();
} catch(\Stripe\Exception\SignatureVerificationException $e) {
    http_response_code(400);
    exit();
}
?>
```

### ステップ 3: 決済完了ページの更新

`payment-complete.php`で実際のセッション情報を取得：

```php
<?php
require_once 'vendor/autoload.php';

\Stripe\Stripe::setApiKey('sk_test_...');

$session_id = $_GET['session_id'] ?? '';

if ($session_id) {
    try {
        $session = \Stripe\Checkout\Session::retrieve($session_id);
        $customer_email = $session->customer_details->email;
        $amount_total = $session->amount_total / 100;
        $payment_status = $session->payment_status;
    } catch (Exception $e) {
        // エラー処理
    }
}
?>
```

## 🧪 テスト方法

### テストカード番号

**成功:**

- 番号: `4242 4242 4242 4242`
- 有効期限: 将来の日付（例: 12/34）
- CVC: 任意の 3 桁（例: 123）

**失敗:**

- 番号: `4000 0000 0000 0002`

詳細: https://stripe.com/docs/testing

## 🔐 セキュリティチェックリスト

- [ ] Secret Key は環境変数で管理
- [ ] Webhook 署名の検証を実装
- [ ] HTTPS 通信のみを使用
- [ ] CSRF トークンの実装
- [ ] 入力データのバリデーション
- [ ] SQL インジェクション対策

## 📚 参考リンク

- [Stripe Checkout Documentation](https://stripe.com/docs/payments/checkout)
- [Stripe PHP Library](https://github.com/stripe/stripe-php)
- [Stripe Dashboard](https://dashboard.stripe.com/)

## ❓ よくある質問

**Q: テスト環境と本番環境の切り替え方は？**
A: API キーを切り替えるだけです。`sk_test_...`から`sk_live_...`に変更してください。

**Q: 複数の商品（早割・通常）をどう扱う？**
A: フォームで選択された料金プランに応じて、異なる Price ID を使用します。

**Q: 決済完了後のメール送信は？**
A: Webhook で`checkout.session.completed`イベントを受信した際に、PHPMailer や SendGrid を使ってメールを送信します。

## 💡 次のステップ

1. ✅ Stripe アカウントの商品設定
2. ✅ API キーの取得
3. ✅ Composer で`stripe/stripe-php`をインストール
4. ✅ 環境変数の設定
5. ✅ テスト決済の実行
6. ✅ Webhook 動作確認
7. ✅ 本番環境への移行
