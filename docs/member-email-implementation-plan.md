# チームメンバー個別メール実装計画

## 背景
- 申込時点で `team_members` テーブルにメンバー別のStripe/KYCカラムが用意されているが、現状のAPI (`api/save-payment-method.php`, `api/kyc/mark-as-completed.php`) は `applications` テーブルのみ更新しており、どのメンバーが操作したかを記録していない。
- マイページでは各メンバーが自身のメール＋申込番号でログインし、カード登録／本人確認を進める想定のため、**メンバー単位の進捗管理と本人宛メール送信**が必須。
- 既に `EmailTemplateService` に `recipient_type` 制御基盤を実装したので、メンバー単位の判定ロジックを追加すれば、本人＋代表者など複数宛先へ柔軟に通知できる。

## 実装ステップ

### 1. フロントエンド／マイページのID引き渡し
- 各メンバーがログインした際に、自身の `team_member_id`（または等価の識別子）をフロント側で保持。
- カード登録・本人確認APIを叩く際に `team_member_id` をパラメータとして送信する。
  - 例: `POST /api/save-payment-method.php { application_id, team_member_id, payment_method_id, ... }`
  - 代表者が操作する場合は `team_member_id` を省略し、従来の申込単位処理を fallback として維持。

### 2. API側の受け取りとDB更新
- `api/save-payment-method.php` と `api/kyc/mark-as-completed.php` に `team_member_id` を追加し、値がある場合は:
  1. 該当メンバーの `team_members` レコードを取得。
  2. `team_members` の `stripe_*` / `card_registered` / `kyc_status` などを更新。
  3. メンバー専用のステータス更新に伴い、チーム全体の `all_members_*` フラグ（`team_applications`）が必要なら自動更新。
  4. 既存の `applications` テーブル側ステータスも、必要に応じて代表的な状態を反映（全員完了で `application_status` を進める、など）。
- `team_member_id` が無い場合は従来通り代表者（申込単位）を対象に処理して互換性を確保する。

### 3. メール送信の個別化
- 変更後のAPIでは「操作したメンバー」の情報を把握できるので、メール送信時にその人のメールアドレスを宛先として選べる。
  - `EmailTemplateService::sendTemplateToApplication()` は申込IDから宛先を解決する仕組みだが、メンバー単位で送る場合は `sendTemplate()` に直接 `['email' => ..., 'name' => ...]` を渡す、あるいは `recipient_type` に新種別（例: `team_member_individual`）を追加して `team_member_id` をオプションで渡すなど拡張する。
- 代表者にも通知したいテンプレート（例: カード登録完了）は `recipient_type = guardian_and_participant` に設定しておけば、本人＋代表保護者への併送が可能。

### 4. 管理画面の宛先UI
- 既に計画中の「テンプレ編集画面で宛先をチェックボックスで選択」機能を実装し、`recipient_type` をGUIから切り替えられるようにする。
- 将来的に「個別操作時のみ本人宛に送る」「チーム全員に一斉送信する」などテンプレごとの設定が容易になる。

### 5. テストシナリオ
1. チームメンバーAでマイページにログイン → カード登録 →  
   - `team_members.card_registered` がAだけ `true` になる  
   - メールがメンバーA＋代表者に届く（テンプレ設定に応じて）
2. 別メンバーBが本人確認完了 →  
   - `team_members.kyc_status` がBだけ `completed`  
   - 「本人確認完了」メールがB＋代表者に届く
3. 全員完了後、`team_applications.all_members_card_registered` 等が `true` になり、代表者向けの総合メールを送るシナリオを確認。

## 補足
- メンバー毎のステータス反映はトリガーやバッチ（`team_members_status_update_trigger` など）が既に存在するため、実際のDB更新さえ行えば連動します。
- セキュリティ面では、マイページログイン時点で各メンバーに固有のセッション／token を割り当て、他メンバーの `team_member_id` を操作できないようにする必要があります。
- 本実装完了後に、宛先チェックボックス＋メールテンプレ `recipient_type` の管理画面UIを整備することで、編集部が柔軟に運用できるようになります。

