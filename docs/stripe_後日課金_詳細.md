# Stripe後日課金の実装詳細（エンジニア向け）

## 目次
1. [概要](#概要)
2. [技術仕様](#技術仕様)
3. [実装フロー](#実装フロー)
4. [コード例](#コード例)
5. [エラーハンドリング](#エラーハンドリング)
6. [テスト方法](#テスト方法)
7. [セキュリティ考慮事項](#セキュリティ考慮事項)

---

## 概要

### 要件
- 申込み日（11月11日）：カード情報登録のみ（課金なし）
- 課金日（12月1日）：自動で決済実行
- 期間：約20日間（月をまたぐ）

### 採用方式
**SetupIntent + PaymentMethod方式**

---

## 技術仕様

### 使用するStripe API
1. **SetupIntent API**：カード情報の保存
2. **Customer API**：顧客管理
3. **PaymentMethod API**：支払い方法の管理
4. **PaymentIntent API**：実際の決済実行

### データフロー
```
[ユーザー] → [SetupIntent] → [PaymentMethod作成]
                                    ↓
                              [Customer紐付け]
                                    ↓
                            [DB保存: PaymentMethod ID]
                                    ↓
                          [指定日にcron job実行]
                                    ↓
                    [PaymentIntent作成・即実行]
```

---

## 実装フロー

### Phase 1：申込み日（11月11日）

#### Step 1: Customerの作成（初回のみ）
```javascript
const customer = await stripe.customers.create({
  email: 'customer@example.com',
  name: '顧客名',
  metadata: {
    user_id: 'internal_user_123'
  }
});
```

#### Step 2: SetupIntentの作成
```javascript
const setupIntent = await stripe.setupIntents.create({
  customer: customer.id,
  payment_method_types: ['card'],
  usage: 'off_session', // オフセッション利用を明示
});

// フロントエンドにclient_secretを渡す
return { clientSecret: setupIntent.client_secret };
```

#### Step 3: フロントエンドでカード情報収集
```javascript
// Stripe.jsを使用
const stripe = Stripe('pk_test_...');

// Stripe Elementsでカード入力フォーム作成
const elements = stripe.elements();
const cardElement = elements.create('card');
cardElement.mount('#card-element');

// SetupIntentの確認
const { setupIntent, error } = await stripe.confirmCardSetup(
  clientSecret,
  {
    payment_method: {
      card: cardElement,
      billing_details: {
        name: 'カード名義人',
        email: 'customer@example.com',
      },
    },
  }
);

if (error) {
  // エラー処理
  console.error(error.message);
} else if (setupIntent.status === 'succeeded') {
  // 成功：payment_method_idをサーバーに送信
  await fetch('/api/save-payment-method', {
    method: 'POST',
    body: JSON.stringify({
      payment_method_id: setupIntent.payment_method
    })
  });
}
```

#### Step 4: PaymentMethodをCustomerに紐付け（サーバー側）
```javascript
// PaymentMethodがCustomerに自動紐付けされているか確認
const paymentMethod = await stripe.paymentMethods.retrieve(
  setupIntent.payment_method
);

// 必要に応じて明示的に紐付け
if (!paymentMethod.customer) {
  await stripe.paymentMethods.attach(
    setupIntent.payment_method,
    { customer: customer.id }
  );
}

// DBに保存
await db.orders.create({
  user_id: userId,
  customer_id: customer.id,
  payment_method_id: setupIntent.payment_method,
  amount: 10000,
  currency: 'jpy',
  scheduled_charge_date: '2025-12-01',
  status: 'pending'
});
```

### Phase 2：課金日（12月1日）

#### Step 5: cron jobで自動実行
```javascript
// 例：毎日午前9時に実行
// cron: "0 9 * * *"

async function processScheduledCharges() {
  const today = new Date().toISOString().split('T')[0];
  
  // 本日課金予定の注文を取得
  const orders = await db.orders.findMany({
    where: {
      scheduled_charge_date: today,
      status: 'pending'
    }
  });

  for (const order of orders) {
    try {
      // PaymentIntentで課金実行
      const paymentIntent = await stripe.paymentIntents.create({
        amount: order.amount,
        currency: order.currency,
        customer: order.customer_id,
        payment_method: order.payment_method_id,
        off_session: true,  // オフセッション決済
        confirm: true,       // 即座に決済確定
        description: `Order #${order.id}`,
        metadata: {
          order_id: order.id
        }
      });

      // 成功時の処理
      await db.orders.update({
        where: { id: order.id },
        data: {
          status: 'completed',
          payment_intent_id: paymentIntent.id
        }
      });

      // ユーザーへメール送信
      await sendEmail(order.user_email, {
        subject: '決済完了のお知らせ',
        body: `ご注文の決済が完了しました。`
      });

    } catch (error) {
      // エラー処理（後述）
      await handlePaymentError(order, error);
    }
  }
}
```

---

## コード例

### 完全なバックエンド実装（Node.js + Express）

```javascript
const express = require('express');
const stripe = require('stripe')(process.env.STRIPE_SECRET_KEY);

const app = express();
app.use(express.json());

// 1. SetupIntent作成
app.post('/api/create-setup-intent', async (req, res) => {
  try {
    const { customerId, email, name } = req.body;
    
    // 既存顧客がいない場合は作成
    let customer;
    if (customerId) {
      customer = await stripe.customers.retrieve(customerId);
    } else {
      customer = await stripe.customers.create({ email, name });
    }

    const setupIntent = await stripe.setupIntents.create({
      customer: customer.id,
      payment_method_types: ['card'],
      usage: 'off_session',
    });

    res.json({
      clientSecret: setupIntent.client_secret,
      customerId: customer.id
    });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

// 2. PaymentMethod保存
app.post('/api/save-payment-method', async (req, res) => {
  try {
    const { paymentMethodId, customerId, amount, scheduledDate } = req.body;

    // DBに保存
    const order = await db.orders.create({
      customer_id: customerId,
      payment_method_id: paymentMethodId,
      amount: amount,
      currency: 'jpy',
      scheduled_charge_date: scheduledDate,
      status: 'pending'
    });

    res.json({ success: true, orderId: order.id });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

// 3. 課金実行（cron jobから呼ばれる）
app.post('/api/process-scheduled-charges', async (req, res) => {
  // 認証チェック（内部APIなのでトークン検証など）
  
  await processScheduledCharges();
  res.json({ success: true });
});
```

### フロントエンド実装（React）

```javascript
import { loadStripe } from '@stripe/stripe-js';
import {
  Elements,
  CardElement,
  useStripe,
  useElements,
} from '@stripe/react-stripe-js';

const stripePromise = loadStripe('pk_test_...');

function CheckoutForm({ amount, scheduledDate }) {
  const stripe = useStripe();
  const elements = useElements();
  const [error, setError] = useState(null);
  const [processing, setProcessing] = useState(false);

  const handleSubmit = async (event) => {
    event.preventDefault();
    setProcessing(true);

    // 1. SetupIntent作成
    const response = await fetch('/api/create-setup-intent', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        email: 'customer@example.com',
        name: '顧客名'
      })
    });
    const { clientSecret, customerId } = await response.json();

    // 2. カード情報確認
    const { setupIntent, error } = await stripe.confirmCardSetup(
      clientSecret,
      {
        payment_method: {
          card: elements.getElement(CardElement),
          billing_details: {
            name: '顧客名',
          },
        },
      }
    );

    if (error) {
      setError(error.message);
      setProcessing(false);
      return;
    }

    // 3. PaymentMethod保存
    await fetch('/api/save-payment-method', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        paymentMethodId: setupIntent.payment_method,
        customerId: customerId,
        amount: amount,
        scheduledDate: scheduledDate
      })
    });

    setProcessing(false);
    alert(`登録完了！${scheduledDate}に自動で決済されます。`);
  };

  return (
    <form onSubmit={handleSubmit}>
      <CardElement />
      <button disabled={!stripe || processing}>
        {processing ? '処理中...' : '登録する'}
      </button>
      {error && <div className="error">{error}</div>}
    </form>
  );
}

function App() {
  return (
    <Elements stripe={stripePromise}>
      <CheckoutForm amount={10000} scheduledDate="2025-12-01" />
    </Elements>
  );
}
```

---

## エラーハンドリング

### 想定されるエラー

#### 1. authentication_required
3Dセキュア認証が必要だが、ユーザー不在のため完了できない。

```javascript
async function handlePaymentError(order, error) {
  if (error.code === 'authentication_required') {
    // ユーザーに再認証を依頼
    await db.orders.update({
      where: { id: order.id },
      data: { status: 'requires_authentication' }
    });
    
    await sendEmail(order.user_email, {
      subject: '決済に認証が必要です',
      body: 'カード認証のため、こちらのリンクから手続きをお願いします。',
      link: `https://yourdomain.com/complete-payment/${order.id}`
    });
  }
}
```

#### 2. card_declined（カード拒否）
残高不足、カード無効など。

```javascript
if (error.code === 'card_declined') {
  await db.orders.update({
    where: { id: order.id },
    data: { 
      status: 'failed',
      error_message: error.message 
    }
  });
  
  await sendEmail(order.user_email, {
    subject: '決済に失敗しました',
    body: 'カード情報をご確認の上、再度お試しください。'
  });
}
```

#### 3. expired_card（カード期限切れ）

```javascript
if (error.code === 'expired_card') {
  await db.orders.update({
    where: { id: order.id },
    data: { status: 'requires_new_card' }
  });
  
  await sendEmail(order.user_email, {
    subject: 'カード情報の更新が必要です',
    body: 'カードの有効期限が切れています。'
  });
}
```

### リトライロジック

```javascript
async function processWithRetry(order, maxRetries = 3) {
  for (let attempt = 1; attempt <= maxRetries; attempt++) {
    try {
      const paymentIntent = await stripe.paymentIntents.create({
        amount: order.amount,
        currency: order.currency,
        customer: order.customer_id,
        payment_method: order.payment_method_id,
        off_session: true,
        confirm: true,
      });
      
      // 成功
      return { success: true, paymentIntent };
      
    } catch (error) {
      if (attempt === maxRetries) {
        // 最終試行でも失敗
        return { success: false, error };
      }
      
      // リトライ可能なエラーかチェック
      const retryableErrors = ['card_declined', 'processing_error'];
      if (!retryableErrors.includes(error.code)) {
        return { success: false, error };
      }
      
      // 待機してリトライ
      await new Promise(resolve => setTimeout(resolve, 1000 * attempt));
    }
  }
}
```

---

## テスト方法

### テストカード番号

```javascript
// 成功するカード
4242424242424242

// 3DS認証が必要なカード（off_sessionで失敗をテスト）
4000002500003155

// カード拒否
4000000000000002

// 残高不足
4000000000009995

// 期限切れ
4000000000000069
```

### テストシナリオ

#### 1. 正常系テスト
```bash
# 1. SetupIntent作成
curl -X POST http://localhost:3000/api/create-setup-intent \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","name":"Test User"}'

# 2. フロントエンドでカード登録（手動）

# 3. 課金実行をテスト
curl -X POST http://localhost:3000/api/process-scheduled-charges
```

#### 2. エラー系テスト
- 3DS認証が必要なカードでoff_session決済をテスト
- 拒否されるカードで決済エラーをテスト
- 存在しないPaymentMethod IDでエラーをテスト

#### 3. 日付テスト
```javascript
// テスト用：scheduled_charge_dateを今日に設定
await db.orders.update({
  where: { id: testOrderId },
  data: { scheduled_charge_date: new Date().toISOString().split('T')[0] }
});

// cron jobを手動実行
await processScheduledCharges();
```

---

## セキュリティ考慮事項

### 1. PCI DSS準拠
- ✅ カード情報は直接サーバーに送信しない（Stripe.jsを使用）
- ✅ SetupIntentのclient_secretのみサーバーで扱う
- ✅ PaymentMethod IDのみDBに保存（カード番号は保存しない）

### 2. APIキーの管理
```javascript
// 環境変数で管理
const stripe = require('stripe')(process.env.STRIPE_SECRET_KEY);

// 本番環境では必ずsk_live_を使用
// テスト環境ではsk_test_を使用
```

### 3. Webhook署名検証
```javascript
app.post('/webhook', express.raw({type: 'application/json'}), (req, res) => {
  const sig = req.headers['stripe-signature'];
  let event;

  try {
    event = stripe.webhooks.constructEvent(
      req.body,
      sig,
      process.env.STRIPE_WEBHOOK_SECRET
    );
  } catch (err) {
    return res.status(400).send(`Webhook Error: ${err.message}`);
  }

  // イベント処理
  if (event.type === 'payment_intent.succeeded') {
    // 処理
  }

  res.json({received: true});
});
```

### 4. アクセス制御
```javascript
// cron job APIは内部からのみアクセス可能に
app.post('/api/process-scheduled-charges', authenticateInternalAPI, async (req, res) => {
  // ...
});

function authenticateInternalAPI(req, res, next) {
  const token = req.headers['x-internal-token'];
  if (token !== process.env.INTERNAL_API_TOKEN) {
    return res.status(403).json({ error: 'Forbidden' });
  }
  next();
}
```

### 5. データ暗号化
```javascript
// 機密情報はDBで暗号化して保存
const encryptedData = encrypt(sensitiveData, process.env.ENCRYPTION_KEY);
await db.save({ encrypted_field: encryptedData });
```

---

## 運用上の注意点

### 1. Webhookの設定
Stripe Dashboardで以下のイベントを設定：
- `setup_intent.succeeded`
- `payment_intent.succeeded`
- `payment_intent.payment_failed`

### 2. ログ・モニタリング
```javascript
// すべての決済処理をログに記録
console.log(`[Payment] Order ${order.id}: Processing charge`);
console.log(`[Payment] Order ${order.id}: ${paymentIntent.status}`);

// エラーはアラート送信
if (error) {
  await sendAlert(`Payment failed for order ${order.id}: ${error.message}`);
}
```

### 3. cron jobの冗長化
- 複数のサーバーで同時実行されないよう排他制御
- 処理が失敗した場合のリトライ機構

```javascript
// 分散ロックの例（Redisを使用）
const lock = await redis.set(`lock:charge:${order.id}`, '1', 'NX', 'EX', 300);
if (!lock) {
  console.log('Already processing by another server');
  return;
}
// 処理実行
```

---

## 参考リンク

### 公式ドキュメント
- SetupIntent: https://docs.stripe.com/api/setup_intents
- PaymentIntent: https://docs.stripe.com/api/payment_intents
- Save and reuse: https://docs.stripe.com/payments/save-and-reuse
- Off-session payments: https://docs.stripe.com/payments/save-and-reuse#off-session

### サンプルコード
- GitHub: https://github.com/stripe-samples
- Stripe Samples: https://stripe.com/docs/samples

---

**作成日：2025年10月31日**
**対象バージョン：Stripe API 2025-09-30**
