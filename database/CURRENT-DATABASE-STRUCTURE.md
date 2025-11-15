# 現在のデータベース構造

**最終更新日**: 2025-11-10  
**ベーススキーマ**: `supabase-schema-v3-deferred-payment.sql` (Stripe 後日課金対応版)

---

## 📋 目次

1. [概要](#概要)
2. [テーブル一覧](#テーブル一覧)
3. [テーブル詳細](#テーブル詳細)
4. [重要な変更履歴](#重要な変更履歴)
5. [SQL 実行順序](#sql実行順序)

---

## 🎯 概要

### データベース構成

- **プラットフォーム**: Supabase (PostgreSQL)
- **スキーマバージョン**: v3.0 (Stripe 後日課金対応)
- **拡張機能**: uuid-ossp (UUID 生成)

### 主要機能

1. **申込管理** - 個人戦・チーム戦の申込情報
2. **Stripe 統合** - SetupIntent 方式による後日課金
3. **本人確認** - Liquid eKYC 統合準備
4. **試験システム** - 問題・回答・採点
5. **メールシステム** - テンプレート管理・送信ログ
6. **管理画面** - 管理者認証・アクティビティログ

---

## 📊 テーブル一覧

### 🔐 認証・ユーザー管理

| テーブル名            | 説明                               | ステータス    |
| --------------------- | ---------------------------------- | ------------- |
| `users`               | ユーザーアカウント（学生・保護者） | ✅ 本番運用中 |
| `admin_users`         | 管理者アカウント                   | ✅ 本番運用中 |
| `user_sessions`       | ユーザーセッション管理             | ✅ 本番運用中 |
| `admin_activity_logs` | 管理者操作ログ                     | ✅ 本番運用中 |

### 📝 申込管理

| テーブル名                | 説明               | ステータス    |
| ------------------------- | ------------------ | ------------- |
| `applications`            | 申込情報（共通）   | ✅ 本番運用中 |
| `individual_applications` | 個人戦詳細         | ✅ 本番運用中 |
| `team_applications`       | チーム戦詳細       | ✅ 本番運用中 |
| `team_members`            | チームメンバー情報 | ✅ 本番運用中 |

### 💳 決済管理

| テーブル名             | 説明                     | ステータス    |
| ---------------------- | ------------------------ | ------------- |
| `payment_transactions` | 決済トランザクション履歴 | ✅ 本番運用中 |
| `scheduled_charges`    | 課金スケジュール管理     | ✅ 本番運用中 |

### 🆔 本人確認

| テーブル名          | 説明                        | ステータス            |
| ------------------- | --------------------------- | --------------------- |
| `kyc_verifications` | 本人確認詳細（Liquid eKYC） | ⏳ 準備済み（未連携） |

### 📧 メールシステム

| テーブル名        | 説明                   | ステータス    |
| ----------------- | ---------------------- | ------------- |
| `email_templates` | メールテンプレート管理 | ✅ 本番運用中 |
| `email_logs`      | メール送信ログ         | ✅ 本番運用中 |

### 📚 試験システム

| テーブル名     | 説明               | ステータス            |
| -------------- | ------------------ | --------------------- |
| `questions`    | 試験問題           | ⏳ 準備済み（未使用） |
| `user_answers` | ユーザー回答       | ⏳ 準備済み（未使用） |
| `exam_results` | 試験結果（個人戦） | ⏳ 準備済み（未使用） |
| `team_scores`  | チームスコア集計   | ⏳ 準備済み（未使用） |

### 🔔 通知

| テーブル名      | 説明           | ステータス            |
| --------------- | -------------- | --------------------- |
| `notifications` | マイページ通知 | ⏳ 準備済み（未使用） |

---

## 📖 テーブル詳細

### 1. users（ユーザーアカウント）

```sql
CREATE TABLE users (
    id UUID PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255),
    user_type VARCHAR(20) NOT NULL, -- 'student', 'guardian', 'admin'
    full_name VARCHAR(100),
    phone VARCHAR(50),
    stripe_customer_id VARCHAR(255),
    email_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP WITH TIME ZONE,
    updated_at TIMESTAMP WITH TIME ZONE,
    last_login TIMESTAMP WITH TIME ZONE
);
```

**重要フィールド:**

- `stripe_customer_id`: Stripe 顧客 ID（決済に使用）
- `user_type`: ユーザータイプ（学生/保護者/管理者）
- `email_verified`: メール認証済みフラグ

---

### 2. admin_users（管理者アカウント）

```sql
CREATE TABLE admin_users (
    id UUID PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(20) DEFAULT 'admin', -- 'admin', 'super_admin', 'viewer'
    is_active BOOLEAN DEFAULT TRUE,
    last_login_at TIMESTAMP WITH TIME ZONE,
    created_at TIMESTAMP WITH TIME ZONE,
    updated_at TIMESTAMP WITH TIME ZONE
);
```

**重要フィールド:**

- `username`: ログインに使用
- `role`: 権限レベル
- `is_active`: アカウント有効/無効

**デフォルト管理者:**

- Username: `admin`
- Email: `admin@example.com`
- Password: `admin123`

---

### 3. applications（申込情報）

```sql
CREATE TABLE applications (
    id UUID PRIMARY KEY,
    application_number VARCHAR(50) UNIQUE NOT NULL, -- 例: APP-2025-00001
    user_id UUID REFERENCES users(id),
    participation_type VARCHAR(20) NOT NULL, -- 'individual', 'team'
    pricing_type VARCHAR(50) NOT NULL,
    amount INTEGER NOT NULL,

    -- Stripe情報（SetupIntent方式）
    stripe_customer_id VARCHAR(255),
    stripe_setup_intent_id VARCHAR(255),
    stripe_payment_method_id VARCHAR(255),
    stripe_payment_intent_id VARCHAR(255),

    -- カード登録状態
    card_registered BOOLEAN DEFAULT FALSE,
    card_registered_at TIMESTAMP WITH TIME ZONE,
    card_last4 VARCHAR(4),
    card_brand VARCHAR(20),

    -- 支払いステータス
    payment_status VARCHAR(20) DEFAULT 'pending',
    -- 'pending', 'card_registered', 'processing', 'completed', 'failed', 'refunded'

    -- 課金スケジュール
    scheduled_charge_date DATE,
    charged_at TIMESTAMP WITH TIME ZONE,

    -- KYC（本人確認）ステータス
    kyc_status VARCHAR(20) DEFAULT 'pending',
    -- 'pending', 'in_progress', 'completed', 'failed', 'rejected'
    kyc_verified_at TIMESTAMP WITH TIME ZONE,

    -- 申込ステータス
    application_status VARCHAR(20) DEFAULT 'draft',
    -- 'draft', 'submitted', 'card_pending', 'kyc_pending',
    -- 'charge_scheduled', 'payment_processing', 'confirmed', 'cancelled'

    exam_status VARCHAR(20) DEFAULT 'not_started',
    admin_notes TEXT,
    created_at TIMESTAMP WITH TIME ZONE,
    updated_at TIMESTAMP WITH TIME ZONE
);
```

**重要フィールド:**

- `application_number`: 自動生成される申込番号
- `card_registered`: カード登録済みフラグ（重要！）
- `payment_status`: 決済状況
- `application_status`: 申込全体のステータス
- `stripe_payment_method_id`: 保存されたカード情報 ID

**ステータスフロー:**

```
draft → submitted → card_pending → kyc_pending →
payment_processing → confirmed
```

---

### 4. individual_applications（個人戦詳細）

```sql
CREATE TABLE individual_applications (
    id UUID PRIMARY KEY,
    application_id UUID UNIQUE NOT NULL REFERENCES applications(id),

    -- 生徒情報
    student_name VARCHAR(100) NOT NULL,
    school VARCHAR(200) NOT NULL,
    grade VARCHAR(50) NOT NULL,
    student_email VARCHAR(255) NOT NULL,
    student_phone VARCHAR(50),

    -- 保護者情報
    guardian_name VARCHAR(100) NOT NULL,
    guardian_email VARCHAR(255) NOT NULL,
    guardian_phone VARCHAR(50) NOT NULL,

    created_at TIMESTAMP WITH TIME ZONE,
    updated_at TIMESTAMP WITH TIME ZONE
);
```

---

### 5. team_applications（チーム戦詳細）

```sql
CREATE TABLE team_applications (
    id UUID PRIMARY KEY,
    application_id UUID UNIQUE NOT NULL REFERENCES applications(id),

    team_name VARCHAR(100) NOT NULL,
    school VARCHAR(200) NOT NULL,

    -- 代表者情報（メンバー1）
    representative_name VARCHAR(100) NOT NULL,
    representative_email VARCHAR(255) NOT NULL,
    representative_phone VARCHAR(50) NOT NULL,
    representative_grade VARCHAR(50),

    -- 支払い管理
    all_members_paid BOOLEAN DEFAULT FALSE,
    paid_members_count INTEGER DEFAULT 0,

    -- 本人確認管理
    all_members_kyc_completed BOOLEAN DEFAULT FALSE,
    kyc_completed_count INTEGER DEFAULT 0,

    created_at TIMESTAMP WITH TIME ZONE,
    updated_at TIMESTAMP WITH TIME ZONE
);
```

---

### 6. team_members（チームメンバー情報）

```sql
CREATE TABLE team_members (
    id UUID PRIMARY KEY,
    team_application_id UUID NOT NULL REFERENCES team_applications(id),

    member_number INTEGER NOT NULL, -- 1-5
    member_name VARCHAR(100) NOT NULL,
    member_email VARCHAR(255) NOT NULL,
    member_phone VARCHAR(50),
    member_grade VARCHAR(50),

    -- Stripe情報（メンバー個別）
    stripe_customer_id VARCHAR(255),
    stripe_setup_intent_id VARCHAR(255),
    stripe_payment_method_id VARCHAR(255),
    stripe_payment_intent_id VARCHAR(255),

    -- 支払い管理
    payment_status VARCHAR(20) DEFAULT 'pending',
    payment_link_sent_at TIMESTAMP WITH TIME ZONE,
    scheduled_charge_date DATE,
    charged_at TIMESTAMP WITH TIME ZONE,

    -- 本人確認
    kyc_status VARCHAR(20) DEFAULT 'pending',
    kyc_verified_at TIMESTAMP WITH TIME ZONE,

    exam_participated BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP WITH TIME ZONE,
    updated_at TIMESTAMP WITH TIME ZONE,

    UNIQUE(team_application_id, member_number),
    UNIQUE(team_application_id, member_email)
);
```

**重要フィールド:**

- `member_number`: メンバー番号（1 が代表者）
- `payment_status`: メンバー個別の決済状況
- `stripe_payment_method_id`: メンバー個別のカード情報

---

### 7. email_templates（メールテンプレート管理）

```sql
CREATE TABLE email_templates (
    id UUID PRIMARY KEY,

    -- テンプレート識別情報
    template_type VARCHAR(50) NOT NULL UNIQUE,
    template_name VARCHAR(255) NOT NULL,
    description TEXT,

    -- SendGrid Dynamic Template（オプション）
    sendgrid_template_id VARCHAR(100),
    use_sendgrid_template BOOLEAN DEFAULT FALSE,

    -- 独自テンプレート
    subject VARCHAR(500),
    body_text TEXT,
    body_html TEXT,

    -- メタ情報
    category VARCHAR(50) DEFAULT 'automatic',
    -- 'application_flow', 'exam_related', 'announcements', 'post_exam'
    sort_order INTEGER DEFAULT 0,
    variables JSONB,

    -- ステータス
    is_active BOOLEAN DEFAULT TRUE,

    -- 管理情報
    created_by UUID REFERENCES admin_users(id),
    updated_by UUID REFERENCES admin_users(id),

    created_at TIMESTAMP WITH TIME ZONE,
    updated_at TIMESTAMP WITH TIME ZONE
);
```

**重要フィールド:**

- `template_type`: システム内部識別子（UNIQUE）
- `category`: カテゴリ（管理画面での表示順に使用）
- `sort_order`: 表示順序
- `use_sendgrid_template`: SendGrid 使用フラグ（現在は全て FALSE）

**カテゴリ構成:**

| category           | 説明                           | sort_order 範囲 |
| ------------------ | ------------------------------ | --------------- |
| `application_flow` | 申込フロー（自動送信）         | 1-6             |
| `exam_related`     | 試験関連（リマインダー）       | 10              |
| `announcements`    | 運営からのお知らせ（手動送信） | 20-21           |
| `post_exam`        | 試験後                         | 30              |

**登録済みテンプレート:**

1. `application_confirmation` - 申込受付確認
2. `card_registration` - カード登録案内
3. `team_member_payment` - チームメンバー支払いリンク
4. `kyc_required` - 本人確認依頼
5. `kyc_completed` - 本人確認完了通知
6. `payment_confirmation` - 決済完了通知
7. `exam_reminder` - 試験日リマインダー
8. `general_announcement` - 汎用お知らせ
9. `schedule_change` - 試験日程変更通知
10. `result_announcement` - 結果発表通知

---

### 8. email_logs（メール送信ログ）

```sql
CREATE TABLE email_logs (
    id UUID PRIMARY KEY,
    application_id UUID REFERENCES applications(id),
    team_member_id UUID REFERENCES team_members(id),
    user_id UUID REFERENCES users(id),

    email_type VARCHAR(50) NOT NULL,
    recipient_email VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body TEXT,

    status VARCHAR(20) NOT NULL,
    -- 'pending', 'sent', 'failed', 'bounced'
    sent_at TIMESTAMP WITH TIME ZONE,

    error_message TEXT,
    created_at TIMESTAMP WITH TIME ZONE
);
```

**重要フィールド:**

- `email_type`: テンプレートタイプに対応
- `status`: 送信状況（pending 追加済み）

---

### 9. payment_transactions（決済トランザクション履歴）

```sql
CREATE TABLE payment_transactions (
    id UUID PRIMARY KEY,
    application_id UUID REFERENCES applications(id),
    team_member_id UUID REFERENCES team_members(id),

    transaction_type VARCHAR(20) NOT NULL,
    -- 'setup', 'payment', 'refund'
    amount INTEGER NOT NULL,
    currency VARCHAR(3) DEFAULT 'JPY',

    -- Stripe情報
    stripe_customer_id VARCHAR(255),
    stripe_setup_intent_id VARCHAR(255),
    stripe_payment_method_id VARCHAR(255),
    stripe_payment_intent_id VARCHAR(255),
    stripe_charge_id VARCHAR(255),
    stripe_refund_id VARCHAR(255),

    status VARCHAR(20) NOT NULL,
    -- 'pending', 'succeeded', 'failed', 'cancelled'

    error_code VARCHAR(50),
    error_message TEXT,
    created_at TIMESTAMP WITH TIME ZONE
);
```

---

### 10. admin_activity_logs（管理者操作ログ）

```sql
CREATE TABLE admin_activity_logs (
    id UUID PRIMARY KEY,
    admin_id UUID REFERENCES admin_users(id),

    action VARCHAR(100) NOT NULL,
    -- 'login', 'update_email_template', 'send_bulk_email', etc.
    description TEXT,

    -- 詳細情報（JSON形式）
    details JSONB,

    -- IPアドレス
    ip_address VARCHAR(45),
    user_agent TEXT,

    created_at TIMESTAMP WITH TIME ZONE
);
```

**重要フィールド:**

- `action`: アクション名
- `details`: 詳細情報（JSON）
- `admin_id`: 操作した管理者の ID

**注意:** `target_type`と`target_id`カラムは存在しません。全ての情報は`details`の JSON に格納されます。

---

## 🔄 重要な変更履歴

### 2025-11-10: メールシステム強化

#### email_templates テーブル

**追加カラム:**

- `category` (VARCHAR): カテゴリ分類
- `sort_order` (INTEGER): 表示順序
- `use_sendgrid_template` (BOOLEAN): SendGrid 使用フラグ
- `created_by` / `updated_by` (UUID): 管理者追跡

**インデックス追加:**

```sql
CREATE INDEX idx_email_templates_sort_order
ON email_templates(category, sort_order);
```

#### email_logs テーブル

**ステータス制約更新:**

```sql
-- 旧: 'sent', 'failed', 'bounced'
-- 新: 'pending', 'sent', 'failed', 'bounced'
```

`pending`ステータスを追加（一斉送信機能のため）

#### admin_activity_logs テーブル

**カラム構成:**

- `target_type`と`target_id`は**存在しない**
- 全ての詳細情報は`details` (JSONB)に格納

---

### 2025-11-10: Stripe 統合完了

#### applications テーブル

**追加フィールド:**

- `card_registered` (BOOLEAN): カード登録済みフラグ
- `card_registered_at` (TIMESTAMP): 登録日時
- `card_last4` (VARCHAR): カード下 4 桁
- `card_brand` (VARCHAR): カードブランド

**payment_status 値の更新:**

- `card_registered`: カード登録済み（課金前）を追加

---

## 📋 SQL 実行順序

### 新規セットアップ時

```bash
# 1. ベーススキーマ
database/supabase-schema-v3-deferred-payment.sql

# 2. 管理者テーブル追加（email-system-schema-fixed.sqlから抽出）
database/create-default-admin.sql

# 3. メールテンプレートテーブル
database/hybrid-email-templates-schema.sql

# 4. メールテンプレート初期データ
database/insert-email-templates.sql
database/insert-additional-email-templates.sql
```

### 既存環境へのアップデート

#### email_templates 更新（カテゴリ・順序追加）

```bash
# オプション1: テーブル再作成
database/hybrid-email-templates-schema.sql
database/insert-email-templates.sql
database/insert-additional-email-templates.sql

# オプション2: 既存データ保持して更新
database/add-template-ordering.sql  # sort_orderカラム追加 + カテゴリ設定
```

#### email_logs 制約更新

```bash
database/update-email-logs-status-constraint.sql
```

---

## 🔍 テーブル関係図

```
users (ユーザー)
  │
  ├─→ applications (申込)
  │     │
  │     ├─→ individual_applications (個人戦詳細)
  │     │
  │     ├─→ team_applications (チーム戦詳細)
  │     │     │
  │     │     └─→ team_members (メンバー)
  │     │           │
  │     │           ├─→ payment_transactions (決済履歴)
  │     │           └─→ kyc_verifications (本人確認)
  │     │
  │     ├─→ payment_transactions (決済履歴)
  │     ├─→ kyc_verifications (本人確認)
  │     ├─→ scheduled_charges (課金スケジュール)
  │     ├─→ email_logs (メール送信ログ)
  │     └─→ exam_results (試験結果)
  │
  ├─→ user_sessions (セッション)
  └─→ notifications (通知)

admin_users (管理者)
  │
  ├─→ email_templates (作成者/更新者)
  └─→ admin_activity_logs (操作ログ)

email_templates (テンプレート)
  │
  └─→ email_logs (送信時に参照)
```

---

## ✅ データ整合性チェックリスト

### 申込データ

```sql
-- 申込番号の重複チェック
SELECT application_number, COUNT(*)
FROM applications
GROUP BY application_number
HAVING COUNT(*) > 1;

-- 個人戦申込に詳細データがあるかチェック
SELECT a.id, a.application_number
FROM applications a
LEFT JOIN individual_applications ia ON a.id = ia.application_id
WHERE a.participation_type = 'individual' AND ia.id IS NULL;

-- チーム戦申込に詳細データがあるかチェック
SELECT a.id, a.application_number
FROM applications a
LEFT JOIN team_applications ta ON a.id = ta.application_id
WHERE a.participation_type = 'team' AND ta.id IS NULL;
```

### メールテンプレート

```sql
-- テンプレート数確認
SELECT COUNT(*) FROM email_templates WHERE is_active = TRUE;
-- 期待値: 10

-- カテゴリ別件数
SELECT category, COUNT(*)
FROM email_templates
WHERE is_active = TRUE
GROUP BY category
ORDER BY MIN(sort_order);
-- application_flow: 6
-- exam_related: 1
-- announcements: 2
-- post_exam: 1
```

### 管理者アカウント

```sql
-- アクティブな管理者数
SELECT COUNT(*) FROM admin_users WHERE is_active = TRUE;

-- デフォルト管理者の存在確認
SELECT username, email, role
FROM admin_users
WHERE username = 'admin';
```

---

## 🚨 注意事項

### 1. admin_activity_logs のカラム構成

❌ **存在しないカラム:**

- `target_type`
- `target_id`

✅ **正しいカラム:**

- `admin_id`
- `action`
- `description`
- `details` (JSONB)

### 2. email_logs のステータス値

✅ **有効な値:**

- `pending`
- `sent`
- `failed`
- `bounced`

### 3. applications のステータス遷移

```
card_registered (BOOLEAN): カード登録済みフラグ
payment_status: 決済状況
application_status: 申込全体のステータス
```

この 3 つは独立して管理されます。

---

## 📞 サポート情報

### スキーマ関連の問題

1. テーブルが見つからない → `supabase-schema-v3-deferred-payment.sql`を実行
2. カラムが見つからない → このドキュメントで正しいカラム名を確認
3. 制約エラー → CHECK 制約で許可されている値を確認

### データ不整合

1. 申込データが見つからない → `applications`テーブルの存在を確認
2. メールテンプレートが表示されない → `is_active = TRUE`を確認
3. 管理者ログインできない → `admin_users`テーブルと`is_active`を確認

---

**このドキュメントは現在のデータベース構造の完全な記録です。**  
**スキーマ変更時は必ずこのファイルを更新してください。**
