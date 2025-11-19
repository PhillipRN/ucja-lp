# 環境別 config 切り替えガイド

本リポジトリの `config/config.php` で、開発・本番を切り替える際に更新すべき値をまとめます。

## 1. 最低限切り替える項目

| 定数                   | 目的／影響                                                                 | 開発環境例                                    | 本番環境例                                  |
| ---------------------- | -------------------------------------------------------------------------- | --------------------------------------------- | ------------------------------------------- |
| `APP_ENV`              | アプリケーション全体の環境識別。申込番号プレフィックスやログ出力設定等に影響 | `development`                                 | `production`                                |
| `APP_NAME`             | 画面タイトルやメールフッターに利用                                        | `Cambridge Exam Application (Dev)` など       | `Cambridge Exam Application`                |
| `APP_URL`              | メール内リンク・カード登録URL等のベースURL                                 | `https://dev.example.com/ucja_test`           | `https://challenge.univ-cambridge...`       |
| `EMAIL_SANDBOX_MODE`   | true の場合すべてのメールを `EMAIL_SANDBOX_RECIPIENT` に強制送信           | `true`                                        | `false`                                     |
| `EMAIL_SANDBOX_RECIPIENT` | サンドボックス送信先（開発時のみ有効）                                 | テスト用メールアドレス                        | 任意（通常は未使用）                        |
| `STRIPE_PUBLISHABLE_KEY` / `STRIPE_SECRET_KEY` / `STRIPE_WEBHOOK_SECRET` | Stripe のテスト鍵／本番鍵                   | テスト用 `pk_test_...` / `sk_test_...`        | 本番用 `pk_live_...` / `sk_live_...`        |
| `USE_SCHEDULED_CHARGES` | `true` で後日課金バッチ、`false` で即時決済フロー                       | 開発環境の検証方針に合わせて選択              | 通常 `true`                                 |

## 2. 状況に応じて切り替える項目

| 定数                         | 用途                                                    | 備考                                                                                    |
| ---------------------------- | ------------------------------------------------------- | --------------------------------------------------------------------------------------- |
| `SENDGRID_FROM_EMAIL` / `SENDGRID_FROM_NAME` | 送信元メールアドレス・差出人名                       | 本番では公式ドメインのアドレスを使用                                                     |
| `SENDGRID_API_KEY`           | SendGrid API キー                                       | テスト／本番で別キーを利用する場合                                                       |
| `KYC_ENABLED`, `KYC_PROVIDER`, `KYC_API_KEY`, `KYC_API_SECRET` | eKYC を使用する際に true に切り替え、本番 API 情報を設定 | 現状は `false`（手動）運用。リリース時に Liquid 側の本番情報を設定                       |
| `CORS_ALLOWED_ORIGINS`       | API を呼び出す許可ドメイン                              | 本番ドメインを追加                                                                      |
| `UPLOAD_DIR`, `RATE_LIMIT_MAX_REQUESTS` など | 必要に応じて個別に調整                                | 通常は共通のままで問題なし                                                               |

## 3. 補足

- `APP_ENV` を `production` にすると、申込番号が `UCJA-YYYY-MM-XXXXXX` 形式で採番されます。`development` の場合は `DEV-...`。本番リリース時には `APP_ENV` と `APP_URL` を忘れずに切り替えてください。
- `APP_URL` はメールに記載されるマイページ URL やカード登録リンク（`card_registration_url`）に直結します。環境ごとに正しいドメインに更新してください。
- メール送信の検証時には `EMAIL_SANDBOX_MODE = true` にしておけば、実ユーザーには届かず `EMAIL_SANDBOX_RECIPIENT` にのみ転送されます。
- Stripe はテスト鍵で実行するとテストダッシュボード側に、ライブ鍵で実行すると本番側にトランザクションが記録されます。鍵の切り替え忘れに注意してください。

