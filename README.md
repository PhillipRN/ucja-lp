# Cambridge Japan Academy 英語検定 2026

高校生対象の英語検定試験の申込・決済システム（PHP + Supabase + Stripe）

## 🏗️ アーキテクチャ

- **フロントエンド**: PHP + Tailwind CSS
- **データベース**: Supabase (PostgreSQL)
- **決済**: Stripe Payment Intent API
- **本人確認**: Liquid eKYC（予定）
- **認証**: Supabase Auth（今後実装）

## 🚀 クイックスタート

### 初回セットアップ

```bash
# 1. 必要なディレクトリを作成
chmod +x setup-directories.sh
./setup-directories.sh

# 2. Composer で依存関係をインストール
composer install

# 3. 設定ファイルを編集（Supabase & Stripe のキーを設定）
nano config/config.php

# 4. Supabaseでデータベーススキーマを実行
# database/supabase-schema.sql の内容を Supabase SQL Editor で実行

# 5. 開発サーバーを起動
./start-dev.sh
```

詳細なセットアップ手順は **[SETUP-GUIDE.md](SETUP-GUIDE.md)** を参照してください。

### ローカル開発

```bash
# 開発サーバーを起動
./start-dev.sh

# または、ポート番号を指定
./start-dev.sh 3000
```

ブラウザで http://localhost:8000/index.php にアクセス

### Windows の場合

```cmd
start-dev.bat
```

## 📁 プロジェクト構成

```
/
├── index.php                     # メインページ
├── .htaccess                     # Apache設定
├── composer.json                 # Composer依存関係
├── setup-directories.sh          # 初期セットアップスクリプト
│
├── components/                   # PHPコンポーネント
│   ├── header.php               # ヘッダー
│   ├── hero-section.php         # ヒーローカルーセル（4スライド）
│   ├── about-section.php        # 概要
│   ├── pricing-section.php      # 料金
│   ├── payment-process-section.php  # フロー
│   ├── prizes-section.php       # 賞品
│   ├── application-form.php     # 申込フォーム（入力確認機能付き）
│   └── footer.php               # フッター
│
├── api/                         # バックエンドAPI
│   ├── auth/                    # 認証API
│   │   └── login.php            # ログイン処理
│   ├── submit-application.php   # 申込データ送信
│   ├── create-payment-intent.php # Stripe決済Intent作成
│   └── stripe-webhook.php       # Stripe Webhook受信
│
├── lib/                         # ライブラリ
│   ├── SupabaseClient.php       # Supabase REST APIクライアント
│   └── AuthHelper.php           # 認証・セッション管理
│
├── my-page/                     # マイページ（ログイン後）
│   ├── dashboard.php            # ダッシュボード
│   ├── application-detail.php   # 申込詳細
│   ├── payment-status.php       # 支払い状況
│   ├── kyc-status.php          # 本人確認状況
│   └── profile.php             # プロフィール編集
│
├── login.php                    # ログイン画面
├── logout.php                   # ログアウト処理
│
├── config/                      # 設定ファイル
│   ├── config.example.php       # 設定ファイル例
│   └── config.php               # 実際の設定（gitignore）
│
├── database/                    # データベース
│   └── supabase-schema.sql      # Supabaseスキーマ定義
│
├── kyc-verification.php         # 本人確認ページ
├── kyc-complete.php             # 本人確認完了
├── stripe-checkout.php          # Stripe決済ページ
├── payment-complete.php         # 決済完了
│
├── privacy.php                  # プライバシーポリシー
├── terms.php                    # 利用規約
├── tokusho.php                  # 特定商取引法
├── company.php                  # 会社情報
│
├── SETUP-GUIDE.md               # セットアップガイド
├── README-PHP.md                # 詳細ドキュメント
├── DEPLOY.md                    # デプロイ手順
└── UPLOAD-CHECKLIST.txt         # チェックリスト
```

## 🎨 主な機能

### フロントエンド
- ✅ レスポンシブデザイン（Tailwind CSS）
- ✅ 4 スライドカルーセルヒーロー（自動切替・手動操作対応）
- ✅ 3 ステップ申込フォーム（基本情報 → **入力確認** → 料金選択・決済）
- ✅ 個人戦 / チーム戦対応
- ✅ スムーズスクロール
- ✅ セクション別コンポーネント化
- ✅ 法的ページ完備（プライバシーポリシー、利用規約、特定商取引法）

### バックエンド
- ✅ Supabase データベース統合
- ✅ Stripe Payment Intent API 統合
- ✅ Stripe Webhook ハンドリング
- ✅ 申込データの永続化
- ✅ 決済トランザクション管理
- ✅ Row Level Security (RLS) 対応

### セキュリティ
- ✅ Stripe PCI DSS 準拠決済
- ✅ Supabase RLS ポリシー
- ✅ API キー管理（gitignore）
- ✅ Webhook 署名検証

## 🛠️ 開発

### ファイルの編集

各コンポーネントは `components/` ディレクトリ内の個別 PHP ファイルに分かれています。

```php
// index.php で読み込み
<?php include 'components/header.php'; ?>
```

### スタイリング

Tailwind CSS（CDN 版）を使用しています。クラス名を編集するだけでスタイル変更可能。

```html
<div class="bg-blue-600 text-white p-4">
  <!-- カスタマイズ可能 -->
</div>
```

### フォームのカスタマイズ

`components/application-form.php` を編集してください。

現在の設定：

- **ステップ数**: 3（基本情報 → 入力確認 → 料金選択・決済）
- **送信先**: `api/submit-application.php`（Supabase に保存）
- **決済**: Stripe Payment Intent API

### データベースのカスタマイズ

`database/supabase-schema.sql` でスキーマ定義を確認・編集できます。

### APIエンドポイントのカスタマイズ

- `api/submit-application.php` - 申込データの処理
- `api/create-payment-intent.php` - Stripe決済の開始
- `api/stripe-webhook.php` - Stripe イベント受信

## 📤 デプロイ

### 方法 1: FTP クライアント

FileZilla、Cyberduck 等で全ファイルをアップロード

### 方法 2: rsync スクリプト

```bash
# スクリプトを編集してサーバー情報を設定
nano upload-via-rsync.sh

# 実行
./upload-via-rsync.sh
```

### 方法 3: SCP スクリプト

```bash
# スクリプトを編集してサーバー情報を設定
nano upload-via-scp.sh

# 実行
./upload-via-scp.sh
```

詳細は `DEPLOY.md` を参照してください。

## 📋 チェックリスト

アップロード前に `UPLOAD-CHECKLIST.txt` を確認してください。

## 🔧 必要な環境

- **PHP**: 7.4 以上（8.x 推奨）
- **Web サーバー**: Apache または Nginx
- **ブラウザ**: モダンブラウザ（Chrome、Safari、Firefox、Edge）

## 📚 ドキュメント

- **README-PHP.md** - 詳細なプロジェクト説明
- **QUICKSTART-PHP.md** - クイックスタートガイド
- **DEPLOY.md** - デプロイ手順とトラブルシューティング
- **UPLOAD-CHECKLIST.txt** - アップロード前後のチェックリスト

## 🐛 トラブルシューティング

### ページが表示されない

```bash
# PHPバージョン確認
php -v

# ポート確認（別のアプリが使用していないか）
lsof -i :8000
```

### コンポーネントが読み込まれない

- `components/` ディレクトリが存在するか確認
- PHP ファイルのパーミッションを確認（644）

### フォームが送信できない

- ブラウザのコンソールでエラー確認
- ネットワークタブで通信確認

## 📞 サポート

問題が発生した場合：

1. エラーログを確認
2. ブラウザの開発者ツールでコンソールエラーを確認
3. ドキュメントのトラブルシューティングセクションを参照

## 🎯 開発状況と今後のロードマップ

### ✅ 完了済み

#### Phase 1: 画面・UI作成 (100% 完了！)
- ✅ React 版から PHP 版への移行
- ✅ 3 ステップ申込フォーム（入力確認機能付き）
- ✅ 個人戦・チーム戦対応
- ✅ 4 スライドカルーセル
- ✅ 法的ページ完備
- ✅ **マイページ機能完成**
  - ✅ ログイン画面（メールアドレス＋申込番号）
  - ✅ ダッシュボード（申込ステータス一覧）
  - ✅ 申込詳細画面（個人戦・チーム戦）
  - ✅ 支払い状況画面（カード登録・決済状況）
  - ✅ 本人確認状況画面（eKYC進捗）
  - ✅ プロフィール編集画面（連絡先更新）
  - ✅ セッション管理機能

#### Phase 2: バックエンドAPI (40% 完了)
- ✅ Supabase データベース統合（v3スキーマ）
- ✅ Supabase PHPクライアント
- ✅ Stripe Payment Intent API 統合
- ✅ Stripe Webhook ハンドラー実装
- ✅ ログイン・認証API
- ✅ 申込データの永続化
- ✅ 決済トランザクション管理
- ✅ **マイページ用API**
  - ✅ プロフィール更新API
  - ✅ 申込詳細取得API
- ✅ **申込完了フロー**
  - ✅ 申込完了画面（ログイン情報通知）
  - ✅ sessionStorage連携

### 🚧 次のステップ

#### Phase 2: バックエンドAPI（続き）
1. **マイページ用API実装**
   - プロフィール更新API
   - 申込履歴取得API
   - ステータス確認API

2. **メール送信機能**
   - 申込確認メール
   - 決済完了メール
   - リマインダーメール

#### Phase 3: Stripe統合
3. **Stripe後日課金実装**
   - SetupIntent（カード登録）
   - PaymentIntent（課金実行）
   - Webhook完全実装
   - 自動課金Cronジョブ

#### Phase 4: 外部サービス統合
4. **本人確認（KYC）統合**
   - Liquid eKYC API 連携
   - 本人確認ステータス管理

5. **管理画面**
   - 申込一覧
   - 決済管理
   - ユーザー管理
   - 試験結果入力

5. **試験結果管理**
   - スコア入力
   - ランキング生成
   - 結果通知

6. **本番デプロイ**
   - 本番環境設定
   - SSL証明書
   - パフォーマンス最適化

## 📄 法的ページ

Stripe 決済に対応するため、以下のページを用意しています：

- **プライバシーポリシー** (`privacy.php`) - 個人情報の取扱いについて
- **利用規約** (`terms.php`) - サービス利用の規約
- **特定商取引法に基づく表記** (`tokusho.php`) - 販売業者情報
- **会社情報** (`company.php`) - 会社概要

これらのページへのリンクはフッターに配置されています。

## 📄 ライセンス

© 2025 University Cambridge Japan Consulting Supervisor. All rights reserved.

---

**開発を始める:**

```bash
./start-dev.sh
```

ブラウザで http://localhost:8000/index.php にアクセスして開発を開始してください！
