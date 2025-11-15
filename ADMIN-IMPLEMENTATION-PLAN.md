# 管理画面実装計画

**作成日**: 2025-11-10  
**最終更新日**: 2025-11-10 (18:00)  
**ステータス**: ✅ Phase 1 完了（85%）

---

## 📋 目次

1. [概要](#概要)
2. [機能一覧](#機能一覧)
3. [画面設計](#画面設計)
4. [データベース](#データベース)
5. [API 設計](#api設計)
6. [実装順序](#実装順序)
7. [セキュリティ](#セキュリティ)

---

## 🎯 概要

### 目的

事務局スタッフが以下を管理できる管理画面を構築：

1. 申込状況の確認・管理
2. メールテンプレートの編集・管理
3. 一斉メール送信
4. 統計情報の閲覧

### 設計方針

- **メールテンプレートはデータベースで管理**（SendGrid Dynamic Templates 不使用）
- **編集機能を重視**（事務局が文面をチェック・修正できる）
- **モダンな UI**（Tailwind CSS + Remix Icon）
- **セキュリティ重視**（管理者認証、CSRF 対策）

---

## 📊 機能一覧

### Phase 1: 基本機能（完了 85%）✅

#### 1. 認証機能 ✅ 完了

- [x] 管理者ログイン
- [x] セッション管理
- [x] ログアウト
- [x] 権限チェック

#### 2. ダッシュボード ✅ 完了

- [x] 統計情報（申込数、決済完了数、本人確認状況）
- [x] 最近の申込一覧
- [x] クイックアクション
- [ ] グラフ（日別申込数、料金プラン別） - 優先度低

#### 3. 申込管理 ✅ 完了

- [x] 申込一覧（ページネーション）
- [x] 検索機能（申込番号、名前、メールアドレス）
- [x] フィルター（参加形式、支払い状況、KYC 状況）
- [x] ソート機能
- [ ] CSV エクスポート - Phase 2

#### 4. 申込詳細 ✅ 完了

- [x] 基本情報表示
- [x] 支払い状況
- [x] 本人確認状況
- [x] チーム情報表示（チーム戦の場合）
- [ ] 履歴タイムライン - Phase 2
- [ ] ステータス手動変更（管理者権限） - Phase 2

#### 5. メールテンプレート管理 ⭐ ✅ 完了

- [x] テンプレート一覧（カテゴリ別・時系列順）
- [x] テンプレート編集（HTML + テキスト）
- [x] リアルタイム編集機能
- [x] テンプレート有効化/無効化
- [x] 10 種類のテンプレート作成完了
- [ ] プレビュー機能 - Phase 2
- [ ] 変数一覧表示 - Phase 2

#### 6. 一斉メール送信 ✅ 完了

- [x] 受信者選択（全員、個人戦のみ、チーム戦のみ）
- [x] テンプレート選択
- [x] 送信実行
- [x] テストモード（実際には送信しない）
- [ ] プレビュー機能 - Phase 2
- [ ] テスト送信（実際に 1 通送信） - Phase 2

#### 7. メール送信履歴 ✅ 完了

- [x] 送信履歴一覧
- [x] フィルター（送信日、種類、ステータス）
- [x] 詳細表示
- [ ] 再送信機能 - Phase 2

---

### Phase 2: 高度な機能（後回し OK）

- [ ] スケジュール送信
- [ ] メール配信状況トラッキング（開封率、クリック率）
- [ ] A/B テスト機能
- [ ] 管理者アカウント管理
- [ ] アクティビティログ閲覧

---

## 🖥️ 画面設計

### 1. ログイン画面（admin/login.php）

```
┌─────────────────────────────────────┐
│                                     │
│        🔐 UCJA 管理画面             │
│                                     │
│   ┌─────────────────────────┐      │
│   │ メールアドレス            │      │
│   └─────────────────────────┘      │
│                                     │
│   ┌─────────────────────────┐      │
│   │ パスワード               │      │
│   └─────────────────────────┘      │
│                                     │
│   [ ログイン ]                      │
│                                     │
└─────────────────────────────────────┘
```

**機能:**

- メールアドレス + パスワード認証
- CSRF トークン
- ログイン失敗時のエラー表示

---

### 2. ダッシュボード（admin/dashboard.php）

```
┌─────────────────────────────────────────────────────────┐
│ 🏠 Dashboard  📋 申込管理  ✉️ メール  👤 Admin          │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  📊 統計情報                                            │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐ │
│  │ 総申込数  │ │ 決済完了  │ │ KYC完了  │ │ 今月申込  │ │
│  │   45     │ │   32     │ │   28     │ │   12     │ │
│  └──────────┘ └──────────┘ └──────────┘ └──────────┘ │
│                                                         │
│  📈 申込推移グラフ                                       │
│  ┌─────────────────────────────────────────────┐       │
│  │         [グラフ表示]                        │       │
│  └─────────────────────────────────────────────┘       │
│                                                         │
│  📋 最近の申込                                          │
│  ┌─────────────────────────────────────────────┐       │
│  │ APP-001 | 山田太郎 | 個人戦 | 決済完了      │       │
│  │ APP-002 | 佐藤花子 | チーム戦 | カード登録済  │       │
│  └─────────────────────────────────────────────┘       │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

**表示情報:**

- 総申込数
- 決済完了数
- 本人確認完了数
- 今月の申込数
- 申込推移グラフ（日別）
- 最近の申込（最新 10 件）

---

### 3. 申込管理（admin/applications.php）

```
┌─────────────────────────────────────────────────────────┐
│ 🏠 Dashboard  📋 申込管理  ✉️ メール  👤 Admin          │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  📋 申込一覧                                            │
│                                                         │
│  検索: [_____________]  🔍                              │
│  フィルター: [全て▾] [支払い状況▾] [KYC状況▾]          │
│  [ CSV エクスポート ]                                   │
│                                                         │
│  ┌─────────────────────────────────────────────┐       │
│  │ 申込番号 | 名前 | 形式 | 支払い | KYC | 申込日 │       │
│  ├─────────────────────────────────────────────┤       │
│  │ APP-001 | 山田太郎 | 個人 | 完了 | 完了 | 11/01 │       │
│  │ APP-002 | 佐藤花子 | チーム | 待ち | 待ち | 11/02 │       │
│  │ APP-003 | 鈴木一郎 | 個人 | 完了 | 進行中 | 11/03│       │
│  └─────────────────────────────────────────────┘       │
│                                                         │
│  ← 1 2 3 4 5 →                                        │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

**機能:**

- 検索（申込番号、名前、メールアドレス）
- フィルター（参加形式、支払い状況、KYC 状況、申込日）
- ソート（申込日、申込番号、名前）
- ページネーション（20 件/ページ）
- CSV エクスポート
- 詳細表示リンク

---

### 4. メールテンプレート管理（admin/email-templates.php）⭐

```
┌─────────────────────────────────────────────────────────┐
│ 🏠 Dashboard  📋 申込管理  ✉️ メール  👤 Admin          │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  ✉️ メールテンプレート管理                              │
│                                                         │
│  ┌─────────────────────────────────────────────┐       │
│  │ [1] 申込完了メール              [編集] [プレビュー]│ │
│  │     件名: 【UCJA】申込受付のお知らせ              │ │
│  │     ステータス: 🟢 有効                          │ │
│  ├─────────────────────────────────────────────┤       │
│  │ [2] カード登録完了メール        [編集] [プレビュー]│ │
│  │     件名: 【UCJA】カード情報登録完了              │ │
│  │     ステータス: 🟢 有効                          │ │
│  ├─────────────────────────────────────────────┤       │
│  │ [3] 決済完了メール              [編集] [プレビュー]│ │
│  │     件名: 【UCJA】お支払い完了・受験票発行        │ │
│  │     ステータス: 🟢 有効                          │ │
│  └─────────────────────────────────────────────┘       │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

#### テンプレート編集画面（admin/email-templates.php?id=1&action=edit）

```
┌─────────────────────────────────────────────────────────┐
│  ✉️ テンプレート編集: 申込完了メール                    │
│                                                         │
│  テンプレート名: [申込完了メール_________________]      │
│  件名: [【UCJA】申込受付のお知らせ____________]         │
│                                                         │
│  利用可能な変数:                                        │
│  {{application_number}} - 申込番号                     │
│  {{student_name}} - 生徒名                             │
│  {{guardian_name}} - 保護者名                          │
│  {{email}} - メールアドレス                            │
│  {{amount}} - 金額                                     │
│                                                         │
│  HTML内容:                                              │
│  ┌─────────────────────────────────────────────┐       │
│  │ <h1>申込ありがとうございます</h1>            │       │
│  │ <p>{{guardian_name}}様</p>                  │       │
│  │ <p>お申し込みを受け付けました。</p>          │       │
│  │ ...                                         │       │
│  └─────────────────────────────────────────────┘       │
│                                                         │
│  [ プレビュー ] [ 保存 ] [ キャンセル ]                │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

**機能:**

- テンプレート一覧
- 新規作成
- 編集（件名、HTML 本文）
- プレビュー（実データで表示）
- 有効化/無効化
- 変数一覧表示（使用可能な変数のリスト）
- バージョン管理（編集履歴）

---

### 5. 一斉メール送信（admin/send-email.php）

```
┌─────────────────────────────────────────────────────────┐
│  ✉️ 一斉メール送信                                      │
│                                                         │
│  ステップ1: 受信者選択                                  │
│  ○ 全員                                                │
│  ○ 個人戦のみ                                          │
│  ○ チーム戦のみ                                        │
│  ○ 条件指定                                            │
│    └─ 支払いステータス: [全て▾]                        │
│       KYCステータス: [全て▾]                           │
│                                                         │
│  予想送信数: 45件                                       │
│                                                         │
│  ステップ2: テンプレート選択                            │
│  ○ 試験案内メール                                      │
│  ○ 一般お知らせメール                                  │
│                                                         │
│  ステップ3: 確認                                        │
│  [ プレビュー ] [ テスト送信 ]                         │
│                                                         │
│  [ 送信する ]                                          │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

**機能:**

- 受信者選択（全員、個人戦、チーム戦、条件指定）
- 予想送信数表示
- テンプレート選択
- プレビュー
- テスト送信（管理者自身に送信）
- 送信実行
- 進捗表示

---

## 🗄️ データベース

### 既存テーブル（使用）

#### email_templates

```sql
CREATE TABLE email_templates (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    name VARCHAR(255) NOT NULL,
    description TEXT,
    subject VARCHAR(255),
    html_content TEXT, -- HTML本文
    variables JSONB, -- 利用可能な変数リスト
    category VARCHAR(50), -- application, payment, kyc, general
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);
```

#### email_logs

```sql
-- 既に作成済み（email-system-schema-fixed.sql）
```

#### email_batches

```sql
-- 既に作成済み（一斉送信用）
```

#### admin_users

```sql
CREATE TABLE admin_users (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    role VARCHAR(20) DEFAULT 'admin' CHECK (role IN ('admin', 'super_admin')),
    is_active BOOLEAN DEFAULT TRUE,
    last_login_at TIMESTAMP WITH TIME ZONE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- デフォルト管理者アカウント
INSERT INTO admin_users (email, password_hash, full_name, role) VALUES
('admin@example.com', '$2y$10$...', 'システム管理者', 'super_admin');
```

---

## 🔌 API 設計

### 管理者認証 API

#### POST /api/admin/login.php

```json
// Request
{
  "email": "admin@example.com",
  "password": "password123"
}

// Response
{
  "success": true,
  "admin": {
    "id": "uuid",
    "email": "admin@example.com",
    "full_name": "システム管理者",
    "role": "super_admin"
  }
}
```

#### POST /api/admin/logout.php

```json
// Response
{
  "success": true
}
```

---

### 申込管理 API

#### GET /api/admin/get-applications.php

```json
// Query Parameters
?page=1&limit=20&search=山田&filter_payment=completed&filter_kyc=pending&sort=created_at&order=desc

// Response
{
  "success": true,
  "data": [
    {
      "id": "uuid",
      "application_number": "APP-2025-00001",
      "participation_type": "individual",
      "student_name": "山田太郎",
      "guardian_email": "parent@example.com",
      "payment_status": "completed",
      "kyc_status": "pending",
      "created_at": "2025-11-01T10:00:00Z"
    }
  ],
  "pagination": {
    "total": 45,
    "page": 1,
    "limit": 20,
    "total_pages": 3
  }
}
```

#### GET /api/admin/get-application-detail.php?id=uuid

```json
// Response
{
  "success": true,
  "data": {
    "application": {...},
    "individual_data": {...},
    "payment_history": [...],
    "email_history": [...]
  }
}
```

#### POST /api/admin/export-applications-csv.php

```
CSV ファイルをダウンロード
```

---

### メールテンプレート管理 API

#### GET /api/admin/get-email-templates.php

```json
// Response
{
  "success": true,
  "data": [
    {
      "id": "uuid",
      "name": "申込完了メール",
      "subject": "【UCJA】申込受付のお知らせ",
      "category": "application",
      "is_active": true,
      "updated_at": "2025-11-01T10:00:00Z"
    }
  ]
}
```

#### GET /api/admin/get-email-template.php?id=uuid

```json
// Response
{
  "success": true,
  "data": {
    "id": "uuid",
    "name": "申込完了メール",
    "subject": "【UCJA】申込受付のお知らせ",
    "html_content": "<html>...</html>",
    "variables": ["application_number", "student_name", "guardian_name"],
    "category": "application",
    "is_active": true
  }
}
```

#### POST /api/admin/update-email-template.php

```json
// Request
{
  "id": "uuid",
  "name": "申込完了メール",
  "subject": "【UCJA】申込受付のお知らせ",
  "html_content": "<html>...</html>"
}

// Response
{
  "success": true,
  "message": "テンプレートを更新しました"
}
```

#### POST /api/admin/preview-email-template.php

```json
// Request
{
  "template_id": "uuid",
  "sample_data": {
    "application_number": "APP-2025-00001",
    "student_name": "山田太郎"
  }
}

// Response
{
  "success": true,
  "html": "<html>... 変数が置換されたHTML ...</html>"
}
```

---

### 一斉メール送信 API

#### POST /api/admin/send-bulk-email.php

```json
// Request
{
  "recipient_type": "all", // all, individual, team, custom
  "filter": {
    "payment_status": "completed",
    "kyc_status": "pending"
  },
  "template_id": "uuid",
  "test_mode": false
}

// Response
{
  "success": true,
  "batch_id": "uuid",
  "total_recipients": 45,
  "message": "送信を開始しました"
}
```

#### GET /api/admin/get-bulk-email-status.php?batch_id=uuid

```json
// Response
{
  "success": true,
  "data": {
    "batch_id": "uuid",
    "status": "processing",
    "total_recipients": 45,
    "sent_count": 20,
    "failed_count": 0,
    "progress_percent": 44
  }
}
```

---

### 統計情報 API

#### GET /api/admin/get-dashboard-stats.php

```json
// Response
{
  "success": true,
  "data": {
    "total_applications": 45,
    "payment_completed": 32,
    "kyc_completed": 28,
    "this_month": 12,
    "daily_applications": [
      {"date": "2025-11-01", "count": 3},
      {"date": "2025-11-02", "count": 5}
    ],
    "recent_applications": [...]
  }
}
```

---

## 📝 実装順序

### Phase 1: 基盤（Day 1-2）

1. **管理者ログイン** ✅ 優先度: 最高

   - `admin/login.php`
   - `api/admin/login.php`
   - `api/admin/logout.php`
   - `lib/AdminAuthHelper.php`（セッション管理）

2. **共通レイアウト**
   - `admin/components/header.php`
   - `admin/components/sidebar.php`
   - `admin/components/footer.php`

---

### Phase 2: ダッシュボード・申込管理（Day 3-4）

3. **ダッシュボード**

   - `admin/dashboard.php`
   - `api/admin/get-dashboard-stats.php`

4. **申込管理**
   - `admin/applications.php`
   - `admin/application-detail.php`
   - `api/admin/get-applications.php`
   - `api/admin/get-application-detail.php`
   - `api/admin/export-applications-csv.php`

---

### Phase 3: メールテンプレート管理（Day 5-6）⭐ 最重要

5. **メールテンプレート管理**
   - `admin/email-templates.php`
   - `api/admin/get-email-templates.php`
   - `api/admin/get-email-template.php`
   - `api/admin/update-email-template.php`
   - `api/admin/preview-email-template.php`
   - デフォルトテンプレートの DB 登録

---

### Phase 4: 一斉メール送信（Day 7-8）

6. **一斉メール送信**

   - `admin/send-email.php`
   - `api/admin/send-bulk-email.php`
   - `api/admin/get-bulk-email-status.php`

7. **メール送信履歴**
   - `admin/email-history.php`
   - `api/admin/get-email-history.php`

---

## 🔒 セキュリティ

### 認証・認可

1. **セッション管理**

   - セッションタイムアウト: 2 時間
   - セッション固定攻撃対策（session_regenerate_id）

2. **CSRF 対策**

   - 全フォームに CSRF トークン
   - トークン検証

3. **XSS 対策**

   - HTML エスケープ（htmlspecialchars）
   - Content Security Policy

4. **SQL インジェクション対策**

   - プリペアドステートメント使用
   - SupabaseClient 経由でのクエリ

5. **パスワード**
   - bcrypt ハッシュ（password_hash）
   - 最小 8 文字

---

## 🎨 デザインガイドライン

### カラーパレット

- **Primary**: `#2563eb` (blue-600)
- **Success**: `#16a34a` (green-600)
- **Warning**: `#ca8a04` (yellow-600)
- **Danger**: `#dc2626` (red-600)
- **Info**: `#0891b2` (cyan-600)

### レイアウト

- **サイドバー**: 固定、幅 250px
- **ヘッダー**: 固定、高さ 64px
- **コンテンツエリア**: スクロール可能

### フォント

- **見出し**: 'Noto Sans JP', sans-serif
- **本文**: システムフォント

---

## 📦 デフォルトメールテンプレート

### 1. 申込完了メール

- カテゴリ: application
- トリガー: 申込フォーム送信成功時
- 変数: application_number, guardian_name, student_name, email, participation_type

### 2. カード登録完了メール

- カテゴリ: payment
- トリガー: SetupIntent 成功時
- 変数: application_number, guardian_name, card_last4, card_brand

### 3. 決済完了メール

- カテゴリ: payment
- トリガー: PaymentIntent 成功時
- 変数: application_number, guardian_name, amount, receipt_url

### 4. 本人確認完了メール

- カテゴリ: kyc
- トリガー: KYC 完了時
- 変数: application_number, student_name, guardian_name

### 5. チームメンバー支払いリンクメール

- カテゴリ: team
- トリガー: チーム申込完了時
- 変数: team_name, member_name, payment_link

### 6. 一般お知らせメール

- カテゴリ: general
- トリガー: 管理画面から手動送信
- 変数: カスタマイズ可能

---

## ✅ テストチェックリスト

### 機能テスト

- [ ] 管理者ログイン・ログアウト
- [ ] ダッシュボードの統計表示
- [ ] 申込一覧の検索・フィルター
- [ ] 申込詳細の表示
- [ ] CSV エクスポート
- [ ] メールテンプレート編集
- [ ] メールプレビュー
- [ ] 一斉メール送信
- [ ] 送信履歴表示

### セキュリティテスト

- [ ] 未ログイン時のアクセス制限
- [ ] CSRF 対策の動作確認
- [ ] XSS 対策の動作確認
- [ ] SQL インジェクション対策

### レスポンシブテスト

- [ ] デスクトップ（1920x1080）
- [ ] タブレット（768x1024）
- [ ] モバイル（375x667）

---

## 📚 参考資料

### 外部ドキュメント

- [Tailwind CSS](https://tailwindcss.com/)
- [Remix Icon](https://remixicon.com/)
- [SendGrid PHP SDK](https://github.com/sendgrid/sendgrid-php)

### プロジェクト内ドキュメント

- `SESSION-HANDOVER.md` - プロジェクト全体概要
- `DEVELOPMENT-STATUS.md` - 開発進捗
- `SENDGRID-SETUP-GUIDE.md` - SendGrid 設定
- `STRIPE-TEST-GUIDE.md` - Stripe 統合ガイド

---

**実装開始日**: 2025-11-10  
**予定完了日**: 2025-11-18（8 日間）

---

## 🎯 成功基準

1. ✅ 管理者が問題なくログインできる
2. ✅ 申込一覧・詳細が正しく表示される
3. ✅ メールテンプレートを GUI で編集できる
4. ⏳ プレビューが正しく動作する（Phase 2）
5. ✅ 一斉メール送信が成功する
6. ✅ 送信履歴が記録される
7. ✅ セキュリティチェックを全て通過

---

## 📝 変更履歴

### 2025-11-10 (18:00) - Phase 1 完了 🎉

**実装完了内容:**

1. **認証システム（100%）**

   - AdminAuthHelper 実装
   - ログイン/ログアウト API
   - ログイン画面

2. **ダッシュボード（90%）**

   - 統計情報表示
   - 最近の申込一覧
   - クイックアクション

3. **申込管理（95%）**

   - 申込一覧（検索・フィルター・ソート）
   - 申込詳細表示
   - チーム情報表示

4. **メールテンプレート管理（100%）✨**

   - 10 種類のテンプレート作成完了
   - カテゴリ別グループ化表示
   - 時系列順での並び替え
   - リアルタイム編集機能
   - テキスト/HTML タブ切替

5. **メール送信機能（95%）**
   - 一斉メール送信
   - 受信者選択機能
   - テストモード
   - 送信履歴表示

**データベース更新:**

- `email_templates` テーブル: `sort_order`と`category`カラム追加
- `email_logs` テーブル: `pending`ステータス追加
- `admin_activity_logs` テーブル: カラム構成確定（`details` JSONB 使用）

**ドキュメント作成:**

- `database/CURRENT-DATABASE-STRUCTURE.md` - 現在の DB 構造完全版
- `ADMIN-IMPLEMENTATION-PLAN.md` - このドキュメント更新
- `DEVELOPMENT-STATUS.md` - Phase 3 を 85%完了に更新

**残タスク（Phase 2）:**

- CSV エクスポート機能
- メールプレビュー機能
- テスト送信機能
- グラフ表示
- 履歴タイムライン

---

**このドキュメントは実装に合わせて随時更新します。**
