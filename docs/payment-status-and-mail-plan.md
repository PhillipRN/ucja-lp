# 決済結果メール＆UI反映タスク計画

## 1. 目的
- 後日課金（scheduled charges）完了時の通知メールを guardian / participant / 個別メンバーへ正しく配信する。
- 決済失敗時にも適切に通知し、再登録や代表者へのアラート導線を用意する。
- マイページ（個人・チーム両方）と管理画面で、メンバー単位の決済ステータスを即時に可視化する。

## 2. 現状メモ
- `scripts/run-scheduled-charges.php` で成功時のみ `payment_confirmation` を送信（SendGridテンプレは未使用、`EmailTemplateService`経由）。
- 失敗時メール無し。`scheduled_charges` の `failed` レコードを作るのみ。
- `my-page/payment-status.php` は申込単位（applications.payment_status）だけを見ており、チームメンバー自身がログインした際の個別状況を表示できない。
- `my-page/team-status.php` は `api/user/get-team-status.php` 経由でメンバーの `payment_status` を取得するものの、UIはカード登録／本人確認／試験にフォーカスしており、決済失敗や決済日時の詳細は表示されない。
- 管理画面にはメンバー個別の決済履歴一覧がまだない（`payment_transactions` を直接見に行く必要がある）。

## 3. 実装ステップ

### A. メールフロー
1. `payment_confirmation` テンプレをguardian＋participant両方に送る設定を確認（必要であれば `recipient_type` を `guardian_and_participant` に更新）。
2. 新テンプレ `payment_failed`（仮）を DB に追加。宛先は `guardian_and_participant` を基本に、メンバー個別の場合は `team_members` も選べるようにする。
3. `scripts/run-scheduled-charges.php`：
   - 成功時メールの変数整備（`payment_date`, `member_name`, `amount`, `exam_date` placeholderなど）。
   - 失敗時 `handleStripeError()` または `markProcessingChargeAsFailed()` のタイミングで `payment_failed` を送信し、`scheduled_charges.retry_count` が一定数以上なら再スケジュールを止める（config化）。
4. 即時課金 API (`api/execute-deferred-payment.php`, `api/create-payment-intent.php` + webhook) でも `EmailTemplateService` を使って同テンプレを発火するよう統一。

### B. マイページ UI
1. `api/user/get-payment-status.php` のレスポンスで、ログイン中メンバーの `team_member_id` がある場合には、該当メンバーの支払情報（`team_members.payment_status`, `card_registered_at`, `charged_at` など）も返す。
2. `my-page/payment-status.php`：
   - 個人参加者またはチーム代表が閲覧→従来通り application 全体のカード。
   - チームメンバー自身がログイン→「あなたの決済状況」カードを追加し、`team_members` のステータスを優先表示（カード登録済みだがKYC待ち、決済完了、失敗など）。
   - 決済失敗の場合はアラートカード＋再登録ボタン（`stripe-checkout-setup.php`）を表示。
3. `my-page/team-status.php`：
   - メンバー一覧カードに `charged_at` や `card_registered_at` を表示。
   - 支払いバッジに `processing` `card_registered` など中間状態を追加。
   - 代表者向けに「失敗しているメンバー一覧」だけをまとめた small card を表示し、再登録依頼のメールリンク（`team_member_payment`テンプレ）を送れるようにする（後続タスクとしてAPI化）。

### C. 管理画面
1. `admin/applications.php` または新規タブに `payment_transactions` の一覧を表示。フィルタ：参加形態、決済ステータス、日付帯など。
2. 代表者向け「支払い未完了のメンバー」チャートを dashboard に追加（`admin/get-dashboard-stats.php` に必要な集計を追加）。

### D. データ整合性 / テスト
1. supabaseで `scheduled_charges` → `payment_transactions` → `team_members` 更新の整合性を確認するため、SQLクエリ例を docs に追記。
2. Stripeテストカード（成功/失敗）で以下を検証：
   - 個人戦: カード登録→KYC→自動課金→メール送信→UI反映。
   - チーム戦: メンバーAのみ完了→メール送信対象がA＋代表→Team statusでAのみ完了表示。
   - 失敗パターン: `card_declined` などで `payment_failed` メール＋UI表示。

## 4. 依存 / 注意点
- DBは本番共用のため、`payment_failed` テンプレ追加や recipient_type 変更はSQL手動実行が必要。順番と影響を docs に残す。
- `EmailTemplateService` で `team_member_id` を使った宛先解決を実装済みだが、`payment_failed` 送信時も `recipient_options` に `team_member_id` を渡すこと。
- UI変更に伴い、`AuthHelper::getTeamMemberId()` が常にセッションへ保存されている前提。ログイン周りの挙動再確認が必要。

## 5. 次アクション
1. `payment_failed` テンプレの SQL 追加・更新手順を用意。
2. `scripts/run-scheduled-charges.php` に失敗メール送信＆retry制御を実装。
3. `api/user/get-payment-status.php` / `my-page/payment-status.php` の改修。
4. UI変更後に QA checklist を作成。

