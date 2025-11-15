# Cambridge Japan Academy 英語検定 2026 - PHP 版

このプロジェクトは、React/Next.js で作成されたオリジナルの Web サイトを PHP に移行したバージョンです。

## ファイル構成

```
/
├── index.php                  # メインページ
├── components/                # コンポーネントディレクトリ
│   ├── header.php            # ヘッダーコンポーネント
│   ├── hero-section.php      # ヒーローセクション
│   ├── about-section.php     # 検定概要セクション
│   ├── pricing-section.php   # 料金セクション
│   ├── payment-process-section.php  # 申込フローセクション
│   ├── prizes-section.php    # 賞品セクション
│   ├── application-form.php  # 申込フォーム
│   └── footer.php            # フッター
└── README-PHP.md             # このファイル
```

## 特徴

### デザイン

- Tailwind CSS（CDN 経由）を使用したレスポンシブデザイン
- Remix Icon（CDN 経由）によるアイコン表示
- Google Fonts（Pacifico）の使用
- オリジナルの React 版と同じデザインを維持

### 機能

- スムーズスクロール機能
- 2 ステップの申込フォーム
  - ステップ 1: 基本情報入力
  - ステップ 2: 料金選択・決済方法選択
- フォームバリデーション
- Readdy.ai 統合（フォーム送信、AI ウィジェット）
- レスポンシブ対応

## セットアップ方法

### 必要な環境

- PHP 7.4 以上
- Web サーバー（Apache、Nginx、または PHP 内蔵サーバー）

### ローカル開発

#### 方法 1: PHP 内蔵サーバーを使用

```bash
cd /path/to/camridge_exam
php -S localhost:8000
```

ブラウザで `http://localhost:8000/index.php` にアクセス

#### 方法 2: Apache または Nginx を使用

1. Web サーバーのドキュメントルートに本プロジェクトを配置
2. ブラウザで該当 URL にアクセス

### 本番環境への展開

1. すべての PHP ファイルをサーバーにアップロード
2. `index.php` が Web サーバーの適切な場所に配置されていることを確認
3. PHP のバージョンが 7.4 以上であることを確認
4. `.htaccess`（Apache 使用時）または適切なサーバー設定を行う

#### Apache 用 .htaccess サンプル

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [L,QSA]
```

## カスタマイズ

### スタイルの変更

- Tailwind CSS のクラスを直接編集
- CDN 版を使用しているため、カスタムビルドが必要な場合は Tailwind CLI をインストール

### フォーム送信先の変更

`components/application-form.php` の以下の部分を編集：

```php
<form id="application-form-step2" action="https://readdy.ai/api/form/YOUR_FORM_ID" method="POST">
```

### 画像の変更

各コンポーネントファイル内の画像 URL を編集：

```php
<img src="YOUR_IMAGE_URL" alt="説明" />
```

## 注意事項

1. **CDN の使用**: このバージョンは Tailwind CSS と Remix Icon を CDN 経由で読み込んでいます。本番環境では、パフォーマンスのために自己ホスティングを検討してください。

2. **フォームのセキュリティ**: 実際の本番環境では、CSRF 保護やバリデーションの追加実装を推奨します。

3. **データベース**: このバージョンはデータベースを使用していません。フォームデータは Readdy.ai API に送信されます。独自のデータベースを使用する場合は、適切な処理を追加してください。

4. **PHP バージョン**: PHP 7.4 以上を推奨していますが、より新しいバージョン（PHP 8.x）での使用を強く推奨します。

## サポート

問題が発生した場合は、以下を確認してください：

- PHP のバージョン
- Web サーバーの設定
- ファイルのパーミッション
- ブラウザのコンソールで JavaScript エラーがないか確認

## ライセンス

オリジナルプロジェクトと同じライセンスが適用されます。

## 更新履歴

- 2025 年 10 月 30 日: React/Next.js から PHP への初回移行完了
