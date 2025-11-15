# 検索エンジンインデックス有効化ガイド

## 概要

Stripe 審査中は検索エンジンのインデックスを防止するため、`robots.txt` と各ページの `<meta>` タグでクローラーをブロックしています。

**審査完了後、以下の手順で SEO を有効化してください。**

---

## 🔧 実施した対策

### 1. robots.txt

- **ファイル**: `/robots.txt`
- **内容**: 全検索エンジンクローラーをブロック

### 2. HTML メタタグ

全ページの `<head>` 内に以下を追加：

```html
<!-- 検索エンジンインデックス防止（Stripe審査中） -->
<meta name="robots" content="noindex, nofollow" />
<meta name="googlebot" content="noindex, nofollow" />
```

**対象ファイル（全 9 ファイル）:**

- `index.php`
- `privacy.php`
- `company.php`
- `team-flow.php`
- `tokusho.php`
- `terms.php`
- `stripe-checkout.php`
- `kyc-verification.php`
- `kyc-complete.php`
- `payment-complete.php`

---

## ✅ Stripe 審査完了後の手順

### STEP 1: robots.txt を更新

`robots.txt` ファイルを以下の内容に置き換えてください：

```txt
# 検索エンジンクローラーを許可
User-agent: *
Allow: /

# クロール不要なディレクトリ
Disallow: /components/
Disallow: /vendor/
Disallow: /node_modules/

# サイトマップ（作成後に有効化）
# Sitemap: https://yourdomain.com/sitemap.xml
```

### STEP 2: 各 PHP ファイルからメタタグを削除

**全 10 ファイル**から以下の 3 行を削除：

```html
<!-- 検索エンジンインデックス防止（Stripe審査中） -->
<meta name="robots" content="noindex, nofollow" />
<meta name="googlebot" content="noindex, nofollow" />
```

**削除対象行の場所:**
各ファイルの `<head>` 内、`<meta name="viewport">` の直後（7〜9 行目付近）

---

## 📝 一括削除スクリプト（オプション）

以下のコマンドで一括削除できます（Linux サーバー上で実行）：

```bash
# robots.txt を更新
cat > robots.txt << 'EOF'
User-agent: *
Allow: /
Disallow: /components/
Disallow: /vendor/
EOF

# 全PHPファイルからメタタグを削除
for file in index.php privacy.php company.php team-flow.php tokusho.php terms.php stripe-checkout.php kyc-verification.php kyc-complete.php payment-complete.php; do
  sed -i '/<meta name="robots" content="noindex, nofollow">/d' $file
  sed -i '/<meta name="googlebot" content="noindex, nofollow">/d' $file
  sed -i '/<!-- 検索エンジンインデックス防止（Stripe審査中） -->/d' $file
done

echo "✅ SEO有効化完了"
```

---

## 🔍 確認方法

### 1. robots.txt の確認

ブラウザで以下にアクセス：

```
https://yourdomain.com/robots.txt
```

### 2. メタタグの確認

各ページの HTML ソースを確認し、`noindex` が削除されていることを確認

### 3. Google Search Console で確認

1. Google Search Console にログイン
2. 「URL 検査」でトップページを検査
3. インデックス登録をリクエスト

---

## ⚠️ 注意事項

1. **審査完了まで削除しない**

   - Stripe 審査が完了するまで、これらの設定は**絶対に削除しないでください**

2. **段階的なインデックス**

   - 設定削除後、検索エンジンにインデックスされるまで**数日〜数週間**かかります

3. **サイトマップの作成**

   - より早くインデックスさせるため、サイトマップ（`sitemap.xml`）の作成を推奨

4. **Google Analytics / Search Console**
   - SEO 有効化後は、これらのツールでアクセス状況を監視してください

---

## 📞 サポート

質問や問題がある場合は、開発者にお問い合わせください。

**作成日**: 2025 年 10 月 31 日
**ステータス**: Stripe 審査中（SEO 無効化済み）
