# Stripe 自動決済フロー実装計画

## 1. 目的

- 各申込（個人／チームメンバー）でカード登録済み・本人確認完了後に、Stripe の後日課金（PaymentIntent）を自動実行する。
- 決済結果を DB へ反映し、必要な通知メール（決済完了／失敗）を自動送信する。
- 管理画面＆マイページで進捗が正しく可視化できる状態にする。

## 2. 現状整理

- `applications` および `team_members` には `stripe_customer_id`, `stripe_payment_method_id`, `card_registered`, `kyc_status` 等の列あり。
- `scheduled_charges` テーブルと `team_members_status_update_trigger` などのトリガーはスキーマ上は用意済み。
- KYC 完了 API（`api/kyc/mark-as-completed.php`）はメンバー単位で完了フラグを立てるところまで実装済み。
- 決済実行バッチ／API は未実装。決済完了メール（`payment_confirmation`）も未配信。

## 3. 応答フロー（理想形）

1. **カード登録**：`team_members.card_registered = TRUE`、PaymentMethod 保存。
2. **KYC 完了**：`team_members.kyc_status = 'completed'` に更新。
3. **スケジュール作成**：全条件が揃ったら `scheduled_charges` にレコード生成（1 人 1 件）。charge 予定日が到来したら PaymentIntent を作成＆confirm。
4. **決済成功**：`team_members.payment_status = 'completed'`、`applications.payment_status` も集計更新。`payment_transactions` に履歴保存。`payment_confirmation` メール送信。
5. **決済失敗**：`team_members.payment_status = 'failed'`、エラーログ保存。必要に応じてリトライ／リマインドメール送信。

## 4. 実装タスク

### A. スケジュール生成ロジック

- トリガーまたは API で `scheduled_charges` を Insert。
  - `schedule_charge_on_kyc_completion` を applications / team_members 両方にアタッチし、個人・チームメンバー双方の KYC 完了で自動レコード生成。
  - 条件: `card_registered = TRUE` かつ `kyc_status` が `completed` かつ `payment_status` が `completed` 以外。
  - `scheduled_date` は基本的に即日 or 運営指定日（config で調整できるように）。
- チーム代表への集計用フラグ（`team_applications.all_members_paid` 等）は既存トリガーで更新される前提。

### B. 課金実行ジョブ

- CLI/cron 想定の PHP スクリプト `scripts/run-scheduled-charges.php` を新設。
  - `php scripts/run-scheduled-charges.php --dry-run` で Stripe API を呼ばずに挙動確認。
  - `--date=YYYY-MM-DD` や `--limit=10` で処理対象や件数を制御可能。
  - 未処理の `scheduled_charges` を取得。
  - Stripe PaymentIntent を `amount` / `customer` / `payment_method` / `off_session` で作成し `confirm`。
  - 成否に応じて `scheduled_charges.status`・`team_members.payment_status`・`payment_transactions` を更新。
- リトライ戦略：`scheduled_charges` に `retry_count` を使用、一定回数失敗したら `failed` で止める。

### C. メール連動

- 決済成功時：`payment_confirmation` テンプレ送信（宛先はテンプレの設定に従う）。
- 決済失敗時：別テンプレ（未作成）を追加し、支払未完了の通知を送る。

### D. 管理・UI

- 管理画面：決済履歴（`payment_transactions`）と `team_members` の最新ステータスを参照できる画面のブラッシュアップ。
- マイページ：`payment-status.php` でメンバー自身の支払完了ステータスを表示し、失敗時は再登録導線を提示。

## 5. テスト観点

- Stripe テストカードを用いた end-to-end：カード登録 →KYC 完了 → ジョブ実行 → 決済成功。
- 決済失敗パターン（限度額超過カードなど）でのリトライ・メール通知確認。
- チームメンバー複数人（5 人）分の並列決済と、代表側の集計値反映。

## 6. リスクと対策

- **本番と同一 DB**：スケジュール作成や決済実行を誤って本番データに適用しないよう、`APP_ENV` と `STRIPE_SECRET_KEY` の組み合わせでドライランモードを用意。
- **Stripe Rate Limit**：一括決済時にレート制限にかからないよう、ループ内での待機やバッチサイズ制御を実装。
- **メール多重送信**：決済結果の状態遷移を厳密に管理し、同じレコードで重複メールが送られないよう `payment_status` の更新順序を統一。

## 7. 次ステップ

1. `scheduled_charges` 生成処理（トリガー orAPI）の実装と既存データの整備。
2. 課金バッチスクリプト実装＆Stripe テスト環境での検証。
3. 決済成功／失敗メールテンプレの整備。
4. 管理画面・マイページでの表示／通知確認。
