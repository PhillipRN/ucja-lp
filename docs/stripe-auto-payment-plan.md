# Stripe自動決済フロー実装計画

## 1. 目的
- 各申込（個人／チームメンバー）でカード登録済み・本人確認完了後に、Stripeの後日課金（PaymentIntent）を自動実行する。
- 決済結果をDBへ反映し、必要な通知メール（決済完了／失敗）を自動送信する。
- 管理画面＆マイページで進捗が正しく可視化できる状態にする。

## 2. 現状整理
- `applications` および `team_members` には `stripe_customer_id`, `stripe_payment_method_id`, `card_registered`, `kyc_status` 等の列あり。
- `scheduled_charges` テーブルと `team_members_status_update_trigger` などのトリガーはスキーマ上は用意済み。
- KYC完了API（`api/kyc/mark-as-completed.php`）はメンバー単位で完了フラグを立てるところまで実装済み。
- 決済実行バッチ／APIは未実装。決済完了メール（`payment_confirmation`）も未配信。

## 3. 応答フロー（理想形）
1. **カード登録**：`team_members.card_registered = TRUE`、PaymentMethod保存。
2. **KYC完了**：`team_members.kyc_status = 'completed'` に更新。
3. **スケジュール作成**：全条件が揃ったら `scheduled_charges` にレコード生成（1人1件）。charge予定日が到来したらPaymentIntentを作成＆confirm。
4. **決済成功**：`team_members.payment_status = 'completed'`、`applications.payment_status` も集計更新。`payment_transactions` に履歴保存。`payment_confirmation` メール送信。
5. **決済失敗**：`team_members.payment_status = 'failed'`、エラーログ保存。必要に応じてリトライ／リマインドメール送信。

## 4. 実装タスク
### A. スケジュール生成ロジック
- トリガーまたはAPIで `scheduled_charges` をInsert。
    - 条件: `card_registered = TRUE` かつ `kyc_status = 'completed'` かつ `payment_status` が `completed` 以外。
    - `scheduled_date` は基本的に即日 or 運営指定日（configで調整できるように）。
- チーム代表への集計用フラグ（`team_applications.all_members_paid` 等）は既存トリガーで更新される前提。

### B. 課金実行ジョブ
- CLI/cron想定のPHPスクリプト（例: `scripts/run-scheduled-charges.php`）を新設。
    - 未処理の `scheduled_charges` を取得。
    - Stripe PaymentIntentを `amount` / `customer` / `payment_method` / `off_session` で作成し `confirm`。
    - 成否に応じて `scheduled_charges.status`・`team_members.payment_status`・`payment_transactions` を更新。
- リトライ戦略：`scheduled_charges` に `retry_count` を使用、一定回数失敗したら `failed` で止める。

### C. メール連動
- 決済成功時：`payment_confirmation` テンプレ送信（宛先はテンプレの設定に従う）。
- 決済失敗時：別テンプレ（未作成）を追加し、支払未完了の通知を送る。

### D. 管理・UI
- 管理画面：決済履歴（`payment_transactions`）と `team_members` の最新ステータスを参照できる画面のブラッシュアップ。
- マイページ：`payment-status.php` でメンバー自身の支払完了ステータスを表示し、失敗時は再登録導線を提示。

## 5. テスト観点
- Stripeテストカードを用いた end-to-end：カード登録→KYC完了→ジョブ実行→決済成功。
- 決済失敗パターン（限度額超過カードなど）でのリトライ・メール通知確認。
- チームメンバー複数人（5人）分の並列決済と、代表側の集計値反映。

## 6. リスクと対策
- **本番と同一DB**：スケジュール作成や決済実行を誤って本番データに適用しないよう、`APP_ENV` と `STRIPE_SECRET_KEY` の組み合わせでドライランモードを用意。
- **Stripe Rate Limit**：一括決済時にレート制限にかからないよう、ループ内での待機やバッチサイズ制御を実装。
- **メール多重送信**：決済結果の状態遷移を厳密に管理し、同じレコードで重複メールが送られないよう `payment_status` の更新順序を統一。

## 7. 次ステップ
1. `scheduled_charges` 生成処理（トリガーorAPI）の実装と既存データの整備。
2. 課金バッチスクリプト実装＆Stripeテスト環境での検証。
3. 決済成功／失敗メールテンプレの整備。
4. 管理画面・マイページでの表示／通知確認。

