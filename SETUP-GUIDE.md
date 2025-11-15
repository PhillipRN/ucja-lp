# Cambridge Exam Application System - セットアップガイド

## 概要

このシステムは以下の技術スタックで構築されています：

- **フロントエンド**: PHP + Tailwind CSS
- **データベース**: Supabase (PostgreSQL)
- **決済**: Stripe（後日課金方式 - SetupIntent）
- **本人確認**: Liquid eKYC（予定）

## 📋 開発フェーズ

### Phase 1: 画面・UI 作成 ← **現在ここ**

- ✅ ランディングページ
- ✅ 申込フォーム（入力確認画面付き）
- ✅ DB 設計完了
- ✅ Supabase セットアップ完了
- ⬜ マイページ画面作成
- ⬜ Stripe 決済画面（モック）
- ⬜ 本人確認画面（モック）

### Phase 2: バックエンド API 実装

- ⬜ 申込 API（Supabase 連携）
- ⬜ データ取得 API
- ⬜ ステータス更新 API

### Phase 3: Stripe 統合（画面完成後）

- ⬜ SetupIntent 実装
- ⬜ PaymentIntent 実装
- ⬜ Webhook 実装
- ⬜ 自動課金 cron job

### Phase 4: Liquid eKYC 統合（最後）

- ⬜ Liquid API 連携
- ⬜ Webhook 実装

---

## セットアップ手順（現在の状況）

### 1. Supabase のセットアップ ✅ 完了

#### 1.1 Supabase プロジェクトの作成 ✅

1. [Supabase](https://supabase.com/)にログイン
2. 新しいプロジェクトを作成
3. プロジェクト名を入力（例: `cambridge-exam`）
4. データベースパスワードを設定
5. リージョンを選択（日本の場合は `Northeast Asia (Tokyo)` を推奨）

**現在の設定:**

- Project URL: `https://pxfshwnmmmpxymcqfjbt.supabase.co`
- 設定ファイル: `docs/supabase.md`
- config.php: 設定済み

#### 1.2 データベースのセットアップ ✅

1. Supabase ダッシュボードの左メニューから「SQL Editor」を選択
2. `database/supabase-schema-v3-deferred-payment.sql` の内容を全てコピー
3. SQL エディタにペーストして実行（Run）

**✅ 完了済み** - 18 テーブル作成完了

これにより以下のテーブルが作成されます：

- `users` - ユーザーアカウント
- `applications` - 申込情報
- `individual_applications` - 個人戦詳細
- `team_applications` - チーム戦詳細
- `team_members` - チームメンバー
- `exam_results` - 試験結果
- `payment_transactions` - 決済トランザクション
- `user_sessions` - セッション管理
- `email_logs` - メール送信ログ

#### 1.3 API キーの取得

1. Supabase ダッシュボードの「Settings」→「API」
2. 以下の情報をメモ：
   - Project URL
   - `anon` `public` key
   - `service_role` `secret` key（バックエンド用）

### 2. Stripe のセットアップ ⏭️ スキップ（後で実施）

**現在の状態:**

- ✅ Stripe アカウント作成済み
- ✅ 審査通過済み
- ⏭️ API キー設定は画面完成後に実施

**理由:**
画面全体の作りを完成させてから、Stripe 統合テストを行います。
現在は決済画面をモックで作成中。

#### 2.1 Stripe アカウント（完了済み）

- ✅ アカウント作成完了
- ✅ 審査通過

#### 2.2 API キーの取得（Phase 3 で実施予定）

後で以下を取得して config.php に設定：

1. 開発者 → API キー
2. テスト環境のキーを取得：
   - 公開可能キー（`pk_test_...`）
   - シークレットキー（`sk_test_...`）

#### 2.3 Webhook の設定（Phase 3 で実施予定）

後で設定：

1. 開発者 → Webhook
2. 「エンドポイントを追加」をクリック
3. エンドポイント URL: `https://yourdomain.com/api/stripe-webhook.php`
4. イベント選択：
   - `setup_intent.succeeded`
   - `payment_intent.succeeded`
   - `payment_intent.payment_failed`
   - `payment_intent.canceled`
   - `charge.refunded`
5. Webhook シークレット（`whsec_...`）をメモ

---

**💡 現在の開発方針:**
Phase 1（画面作成）→ Phase 2（API 実装）→ Phase 3（Stripe 統合）の順で進めます。

### 3. PHP 環境のセットアップ

#### 3.1 必要な要件

- PHP 7.4 以上
- cURL 拡張
- JSON 拡張
- Composer（パッケージ管理）

#### 3.2 Composer のインストール

プロジェクトルートで以下を実行：

```bash
composer init
composer require stripe/stripe-php
```

#### 3.3 設定ファイルの作成

```bash
cp config/config.example.php config/config.php
```

`config/config.php` を編集して、以下の情報を設定：

```php
// Supabase
define('SUPABASE_URL', 'https://your-project.supabase.co');
define('SUPABASE_ANON_KEY', 'your-anon-key');
define('SUPABASE_SERVICE_KEY', 'your-service-role-key');

// Stripe
define('STRIPE_PUBLISHABLE_KEY', 'pk_test_...');
define('STRIPE_SECRET_KEY', 'sk_test_...');
define('STRIPE_WEBHOOK_SECRET', 'whsec_...');

// Application
define('APP_URL', 'http://localhost:8000');
```

### 4. ローカル開発サーバーの起動

#### Mac の場合：

```bash
chmod +x start-dev.sh
./start-dev.sh
```

#### Windows の場合：

```bash
start-dev.bat
```

ブラウザで `http://localhost:8000` にアクセス

### 5. Stripe Webhook のテスト（ローカル環境）

#### Stripe CLI のインストール

```bash
# macOS (Homebrew)
brew install stripe/stripe-cli/stripe

# Windows (Scoop)
scoop bucket add stripe https://github.com/stripe/scoop-stripe-cli.git
scoop install stripe
```

#### Stripe CLI でログイン

```bash
stripe login
```

#### Webhook のフォワーディング

```bash
stripe listen --forward-to localhost:8000/api/stripe-webhook.php
```

表示される Webhook シークレット（`whsec_...`）を `config/config.php` に設定

#### テスト決済の実行

別のターミナルで：

```bash
stripe trigger payment_intent.succeeded
```

### 6. .gitignore の設定

`.gitignore` に以下を追加（既に追加済み）：

```
config/config.php
vendor/
.env
*.log
uploads/*
!uploads/.gitkeep
```

### 7. 本番環境へのデプロイ

#### 7.1 ファイルのアップロード

```bash
./upload-via-rsync.sh
# または
./upload-via-scp.sh
```

#### 7.2 本番環境の設定

1. `config/config.php` を本番環境用に更新：

   - `APP_ENV` を `'production'` に変更
   - `APP_URL` を本番 URL に変更
   - Stripe を本番キーに変更（`pk_live_...`, `sk_live_...`）

2. Supabase RLS ポリシーの確認

3. Stripe Webhook を本番 URL で設定

## トラブルシューティング

### データベース接続エラー

- Supabase URL と API キーを確認
- `lib/SupabaseClient.php` のエラーログを確認

### Stripe 決済エラー

- Stripe API キーを確認
- ブラウザのコンソールで JavaScript エラーを確認
- Stripe Dashboard でイベントログを確認

### Webhook が動作しない

- Webhook URL が正しいか確認
- Webhook シークレットが正しいか確認
- サーバーログで受信を確認

## 開発のヒント

### Supabase のクエリテスト

Supabase ダッシュボードの「Table Editor」または「SQL Editor」で直接クエリをテスト可能

### Stripe 決済のテスト

テストカード番号:

- 成功: `4242 4242 4242 4242`
- 失敗: `4000 0000 0000 0002`
- 3D セキュア: `4000 0025 0000 3155`

有効期限: 未来の任意の日付（例: 12/34）
CVC: 任意の 3 桁（例: 123）

### ログの確認

```bash
tail -f logs/php-errors.log
```

## 🚀 次のステップ（Phase 1: 画面作成）

### 現在の進捗

1. ✅ データベーススキーマの作成
2. ✅ Supabase セットアップ完了
3. ✅ 申込フォーム（入力確認画面付き）
4. ⬜ **マイページ画面作成** ← 次はここ
5. ⬜ 決済画面のモック作成
6. ⬜ 本人確認画面のモック作成

### Phase 1 で実装する画面

#### 1. マイページ（優先度：高）

```
/mypage/
  ├── dashboard.php          - ダッシュボード
  ├── applications.php       - 申込履歴
  ├── application-detail.php - 申込詳細
  ├── payment-status.php     - 決済ステータス
  ├── kyc-status.php         - 本人確認ステータス
  └── profile.php            - プロフィール編集
```

**必要な機能:**

- 申込一覧表示
- 各申込のステータス表示
  - カード登録済み / 未登録
  - 本人確認完了 / 未完了
  - 決済完了 / 未完了
- 「カード登録」「本人確認」へのリンク
- チーム戦の場合、メンバーのステータス表示

#### 2. 決済画面（モック版）

```
/payment/
  ├── card-registration.php  - カード登録（SetupIntent）
  └── payment-complete.php   - 登録完了
```

**モックで実装する内容:**

- カード入力フォーム（見た目のみ）
- 「登録」ボタン → 成功メッセージ表示
- DB の`card_registered`を TRUE に更新

#### 3. 本人確認画面（モック版）

```
/kyc/
  ├── kyc-start.php          - 本人確認開始
  ├── kyc-verification.php   - 本人確認実施（既存）
  └── kyc-complete.php       - 本人確認完了（既存）
```

**モックで実装する内容:**

- 「本人確認を開始する」ボタン
- モックで確認完了として処理
- DB の`kyc_status`を'completed'に更新

---

### Phase 2 で実装（画面完成後）

4. ⬜ 申込 API（Supabase 連携）
5. ⬜ データ取得 API
6. ⬜ ステータス更新 API

### Phase 3 で実装（API 完成後）

7. ⬜ Stripe SetupIntent 実装
8. ⬜ Stripe PaymentIntent 実装
9. ⬜ Webhook 実装
10. ⬜ 自動課金 cron job

### Phase 4 で実装（最後）

11. ⬜ Liquid eKYC API 連携
12. ⬜ メール送信機能
13. ⬜ 管理画面
14. ⬜ 試験システム

## サポート

問題が発生した場合は、以下を確認してください：

1. PHP のエラーログ
2. ブラウザのコンソールログ
3. Supabase のログ
4. Stripe のイベントログ

詳細は各サービスの公式ドキュメントを参照：

- [Supabase Docs](https://supabase.com/docs)
- [Stripe Docs](https://stripe.com/docs)
