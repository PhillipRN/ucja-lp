# Stripe後日課金フロー - 完全実装ガイド

## 📋 概要

本人確認サービス（Liquid eKYC）の実装が遅れた場合でも、申込を受付可能にするため、**Stripe SetupIntent方式**を採用。

### フロー
```
1. 申込フォーム送信
   ↓
2. Stripeでカード情報登録（SetupIntent）← 課金なし
   ↓
3. 本人確認待ち（Liquid eKYC未実装でもOK）
   ↓
4. 本人確認完了時に自動で PaymentIntent 発火 → 課金
```

---

## 🔄 詳細フロー

### Phase 1: 申込・カード登録（ユーザー操作）

#### Step 1: 申込フォーム送信
```
POST /api/submit-application.php

レスポンス:
{
  "application_id": "uuid",
  "application_number": "APP-2025-00001",
  "amount": 8800
}
```

**DB更新:**
```sql
INSERT INTO applications (
  participation_type,
  amount,
  application_status
) VALUES (
  'individual',
  8800,
  'submitted'  -- または 'card_pending'
);
```

#### Step 2: Stripe Customer作成（初回のみ）
```javascript
// バックエンド
const customer = await stripe.customers.create({
  email: guardianEmail,
  name: guardianName,
  metadata: {
    application_id: applicationId
  }
});
```

**DB更新:**
```sql
UPDATE applications 
SET stripe_customer_id = '${customer.id}'
WHERE id = '${applicationId}';
```

#### Step 3: SetupIntent作成
```javascript
// バックエンド API: /api/create-setup-intent.php
const setupIntent = await stripe.setupIntents.create({
  customer: customer.id,
  payment_method_types: ['card'],
  usage: 'off_session', // オフセッション決済を許可
  metadata: {
    application_id: applicationId
  }
});

// フロントエンドに返す
return {
  clientSecret: setupIntent.client_secret,
  customerId: customer.id
};
```

**DB更新:**
```sql
UPDATE applications 
SET stripe_setup_intent_id = '${setupIntent.id}',
    application_status = 'card_pending'
WHERE id = '${applicationId}';
```

#### Step 4: フロントエンドでカード情報登録
```javascript
// stripe-card-registration.php

const stripe = Stripe('pk_test_...');
const elements = stripe.elements();
const cardElement = elements.create('card');
cardElement.mount('#card-element');

// SetupIntentを確認
const { setupIntent, error } = await stripe.confirmCardSetup(
  clientSecret,
  {
    payment_method: {
      card: cardElement,
      billing_details: {
        name: guardianName,
        email: guardianEmail,
      },
    },
  }
);

if (setupIntent.status === 'succeeded') {
  // サーバーに通知
  await fetch('/api/save-payment-method.php', {
    method: 'POST',
    body: JSON.stringify({
      application_id: applicationId,
      payment_method_id: setupIntent.payment_method,
      setup_intent_id: setupIntent.id
    })
  });
}
```

#### Step 5: PaymentMethod保存
```javascript
// バックエンド: /api/save-payment-method.php

// PaymentMethodの詳細を取得
const paymentMethod = await stripe.paymentMethods.retrieve(
  setupIntent.payment_method
);

// DBに保存
await supabase.update('applications', {
  stripe_payment_method_id: paymentMethod.id,
  card_registered: true,
  card_registered_at: new Date().toISOString(),
  card_last4: paymentMethod.card.last4,
  card_brand: paymentMethod.card.brand,
  payment_status: 'card_registered',
  application_status: 'kyc_pending'
}, {
  id: 'eq.' + applicationId
});

// PaymentTransactionを記録
await supabase.insert('payment_transactions', {
  application_id: applicationId,
  transaction_type: 'setup',
  amount: 0,
  stripe_customer_id: customerId,
  stripe_setup_intent_id: setupIntent.id,
  stripe_payment_method_id: paymentMethod.id,
  status: 'succeeded'
});
```

**DB最終状態:**
```sql
applications テーブル:
- stripe_customer_id: 'cus_xxxxx'
- stripe_setup_intent_id: 'seti_xxxxx'
- stripe_payment_method_id: 'pm_xxxxx'
- card_registered: TRUE
- card_last4: '4242'
- card_brand: 'visa'
- payment_status: 'card_registered'
- application_status: 'kyc_pending'
```

**ユーザーへの表示:**
```
✅ カード情報を登録しました

次のステップ:
1. 本人確認手続きを行ってください
2. 本人確認完了後、自動で決済が行われます

※ 今は課金されていません
※ 本人確認完了時に自動で ¥8,800 が課金されます
```

---

### Phase 2: 本人確認（Liquid eKYC）

#### Step 6: Liquid eKYC開始
```javascript
// マイページまたはリマインダーメールから
// /kyc-verification.php にアクセス

// Liquid eKYC APIを呼び出し
const liquidVerification = await createLiquidVerification({
  application_id: applicationId,
  name: guardianName,
  email: guardianEmail
});

// DBに保存
await supabase.insert('kyc_verifications', {
  application_id: applicationId,
  liquid_verification_id: liquidVerification.id,
  verification_url: liquidVerification.url,
  verification_status: 'pending'
});

// Liquid eKYC URLにリダイレクト
window.location.href = liquidVerification.url;
```

#### Step 7: Liquid eKYC完了（Webhookで受信）
```javascript
// /api/liquid-webhook.php

// Liquid eKYC完了通知を受信
app.post('/api/liquid-webhook', async (req, res) => {
  const { verification_id, status, verified_data } = req.body;
  
  if (status === 'completed') {
    // kyc_verifications を更新
    await supabase.update('kyc_verifications', {
      verification_status: 'completed',
      document_verified: true,
      verified_name: verified_data.name,
      verified_date_of_birth: verified_data.date_of_birth,
      face_verified: true,
      liveness_check_passed: true,
      liquid_response_data: verified_data,
      completed_at: new Date().toISOString()
    }, {
      liquid_verification_id: 'eq.' + verification_id
    });
    
    // applicationsのKYCステータスを更新
    await supabase.update('applications', {
      kyc_status: 'completed',
      kyc_verified_at: new Date().toISOString(),
      application_status: 'charge_scheduled'
    }, {
      id: 'eq.' + applicationId
    });
    
    // ★ ここで自動トリガーが発動 ★
    // schedule_charge_on_kyc_completion() が実行され
    // scheduled_charges テーブルにレコードが挿入される
  }
});
```

**DB自動更新（トリガー）:**
```sql
-- applications.kyc_status が 'completed' になると自動実行
INSERT INTO scheduled_charges (
  application_id,
  amount,
  stripe_customer_id,
  stripe_payment_method_id,
  scheduled_date,  -- NULL or 指定日
  status
) VALUES (
  applicationId,
  8800,
  stripe_customer_id,
  stripe_payment_method_id,
  CURRENT_DATE,  -- 即座に課金 or scheduled_charge_date
  'scheduled'
);
```

---

### Phase 3: 自動課金実行

#### Step 8: cron jobで定期実行（毎日9時など）
```javascript
// /api/process-scheduled-charges.php
// cron: "0 9 * * *"

async function processScheduledCharges() {
  const today = new Date().toISOString().split('T')[0];
  
  // 今日課金予定のレコードを取得
  const charges = await supabase
    .from('scheduled_charges')
    .select('*')
    .eq('scheduled_date', today)
    .eq('status', 'scheduled')
    .execute();
  
  for (const charge of charges.data) {
    try {
      // ステータスを processing に更新
      await supabase.update('scheduled_charges', {
        status: 'processing'
      }, {
        id: 'eq.' + charge.id
      });
      
      // PaymentIntent作成・即実行
      const paymentIntent = await stripe.paymentIntents.create({
        amount: charge.amount,
        currency: charge.currency,
        customer: charge.stripe_customer_id,
        payment_method: charge.stripe_payment_method_id,
        off_session: true,  // オフセッション決済
        confirm: true,       // 即座に決済確定
        description: `Application charge: ${charge.application_id}`,
        metadata: {
          application_id: charge.application_id,
          scheduled_charge_id: charge.id
        }
      });
      
      if (paymentIntent.status === 'succeeded') {
        // 成功
        await handleChargeSuccess(charge, paymentIntent);
      }
      
    } catch (error) {
      // エラー処理
      await handleChargeError(charge, error);
    }
  }
}
```

#### Step 9: 課金成功時の処理
```javascript
async function handleChargeSuccess(charge, paymentIntent) {
  // scheduled_charges を更新
  await supabase.update('scheduled_charges', {
    status: 'completed',
    executed_at: new Date().toISOString(),
    stripe_payment_intent_id: paymentIntent.id
  }, {
    id: 'eq.' + charge.id
  });
  
  // applications を更新
  await supabase.update('applications', {
    payment_status: 'completed',
    application_status: 'confirmed',
    stripe_payment_intent_id: paymentIntent.id,
    charged_at: new Date().toISOString()
  }, {
    id: 'eq.' + charge.application_id
  });
  
  // payment_transactions に記録
  await supabase.insert('payment_transactions', {
    application_id: charge.application_id,
    transaction_type: 'payment',
    amount: charge.amount,
    stripe_customer_id: charge.stripe_customer_id,
    stripe_payment_method_id: charge.stripe_payment_method_id,
    stripe_payment_intent_id: paymentIntent.id,
    stripe_charge_id: paymentIntent.latest_charge,
    status: 'succeeded'
  });
  
  // ユーザーにメール送信
  await sendEmail({
    to: guardianEmail,
    subject: '【Cambridge Exam】決済完了のお知らせ',
    body: `
      決済が完了しました。
      
      申込番号: ${applicationNumber}
      決済金額: ¥${charge.amount}
      
      試験日までお待ちください。
    `
  });
  
  // 通知作成
  await supabase.insert('notifications', {
    user_id: userId,
    notification_type: 'payment_completed',
    title: '決済完了',
    message: '参加費の決済が完了しました',
    action_url: '/mypage/applications/' + applicationId
  });
}
```

#### Step 10: 課金失敗時の処理
```javascript
async function handleChargeError(charge, error) {
  const retryableErrors = ['card_declined', 'processing_error'];
  
  // scheduled_charges を更新
  await supabase.update('scheduled_charges', {
    status: 'failed',
    error_code: error.code,
    error_message: error.message,
    retry_count: charge.retry_count + 1
  }, {
    id: 'eq.' + charge.id
  });
  
  // applications を更新
  await supabase.update('applications', {
    payment_status: 'failed',
    application_status: 'payment_pending'
  }, {
    id: 'eq.' + charge.application_id
  });
  
  // エラーメール送信
  await sendEmail({
    to: guardianEmail,
    subject: '【重要】決済エラーのお知らせ',
    body: `
      決済処理中にエラーが発生しました。
      
      エラー: ${error.message}
      
      以下のいずれかをお試しください：
      1. マイページからカード情報を再登録
      2. 別のカードで再度登録
      
      マイページURL: https://yourdomain.com/mypage
    `
  });
  
  // 通知作成
  await supabase.insert('notifications', {
    user_id: userId,
    notification_type: 'payment_failed',
    title: '決済エラー',
    message: 'カード決済に失敗しました。カード情報をご確認ください。',
    action_url: '/mypage/payment/retry/' + applicationId,
    action_label: 'カード情報を再登録'
  });
  
  // リトライ可能なエラーの場合は翌日に再スケジュール
  if (retryableErrors.includes(error.code) && charge.retry_count < 3) {
    await supabase.insert('scheduled_charges', {
      application_id: charge.application_id,
      amount: charge.amount,
      stripe_customer_id: charge.stripe_customer_id,
      stripe_payment_method_id: charge.stripe_payment_method_id,
      scheduled_date: new Date(Date.now() + 86400000).toISOString().split('T')[0], // 翌日
      status: 'scheduled'
    });
  }
}
```

---

## 📊 ステータス遷移図

### applications.application_status

```
draft (下書き)
  ↓
submitted (申込送信済み)
  ↓
card_pending (カード登録待ち)
  ↓ [カード登録完了]
kyc_pending (本人確認待ち)
  ↓ [本人確認完了]
charge_scheduled (課金予約済み)
  ↓ [cron jobで課金実行]
payment_processing (決済処理中)
  ↓ [成功]
confirmed (確定・参加可能)

  ↓ [失敗]
payment_pending (決済待ち・要再試行)
```

### applications.payment_status

```
pending (未登録)
  ↓
card_registered (カード登録済み・課金前)
  ↓
processing (決済処理中)
  ↓ [成功]
completed (決済完了)

  ↓ [失敗]
failed (決済失敗)
```

---

## 🎯 チーム戦の場合

### 代表者
1. 申込フォーム送信（メンバー5名分）
2. カード登録（SetupIntent）
3. 本人確認
4. **本人確認完了時に自動課金** ← 代表者のみ

### メンバー2〜5
1. メールで支払い依頼を受信
2. 専用リンクからカード登録（SetupIntent）
3. 本人確認
4. **本人確認完了時に自動課金** ← 各自

**全員の処理が完了したら:**
```sql
team_applications:
- all_members_card_registered = TRUE
- all_members_kyc_completed = TRUE
- all_members_paid = TRUE
- team_ready = TRUE

applications.application_status = 'confirmed'
```

---

## 🔒 セキュリティ

### PCI DSS準拠
- ✅ カード情報は直接サーバーに送信しない
- ✅ Stripe.jsでカード情報を暗号化
- ✅ PaymentMethod IDのみDBに保存

### off_session決済
- ユーザー不在でも決済可能
- 3DS認証が必要な場合はエラー
  - → ユーザーに再認証を依頼

### Webhook署名検証
```javascript
const sig = req.headers['stripe-signature'];
const event = stripe.webhooks.constructEvent(
  req.body,
  sig,
  process.env.STRIPE_WEBHOOK_SECRET
);
```

---

## 📅 実装スケジュール

### フェーズ1: SetupIntent実装（3〜5日）
- [ ] カード登録フォーム作成
- [ ] SetupIntent API実装
- [ ] PaymentMethod保存処理

### フェーズ2: 本人確認連携（2〜3日）
- [ ] Liquid eKYC API実装
- [ ] Webhook受信処理
- [ ] 自動トリガー実装

### フェーズ3: 自動課金システム（3〜5日）
- [ ] cron job実装
- [ ] PaymentIntent実行処理
- [ ] エラーハンドリング・リトライ

### フェーズ4: テスト（3〜5日）
- [ ] 正常系テスト
- [ ] エラー系テスト
- [ ] 日付またぎテスト

---

## ✅ この実装のメリット

1. **本人確認が遅れてもOK**
   - カード登録だけ先に完了
   - 本人確認サービス未実装でも申込受付可能

2. **ユーザーの手間が少ない**
   - カード登録は1回だけ
   - 後は自動で課金

3. **決済失敗リスクを軽減**
   - カードの有効性を事前確認
   - SetupIntent成功 = そのカードで課金可能

4. **柔軟なスケジューリング**
   - 本人確認完了後すぐ課金
   - または指定日に課金
   - scheduled_charge_date で制御

5. **トランザクション管理が明確**
   - scheduled_charges テーブルで課金予定を管理
   - リトライ機構も実装可能

---

これでDB設計は完璧です！ご確認ください。

