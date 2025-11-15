# メール送信先の制御（recipient_type）

## 背景
- テンプレートごとに「誰に送るか」を切り替えたいという要望に備えて、`email_templates` テーブルに `recipient_type` カラムを追加しました。
- `EmailTemplateService::sendTemplateToApplication()` はテンプレートの `recipient_type` に従って宛先を自動で解決し、必要に応じて複数の宛先へ同じメールを送信できるようになっています。
- 既存のテンプレートは `guardian`（保護者宛）に設定されており、従来通りの挙動のままです。

## recipient_type の種類

| 値 | 説明 | 取得される宛先 |
| --- | --- | --- |
| `guardian` | 保護者（またはチーム代表者の保護者） | 個人戦: `individual_applications.guardian_email`<br>チーム戦: `team_applications.guardian_email` |
| `participant` | 参加者本人 | 個人戦: `individual_applications.student_email`<br>チーム戦: 代表者メンバー（`team_members.is_representative = true`） |
| `guardian_and_participant` | 保護者 + 参加者 | 上記2宛先をまとめて送信（重複メールアドレスは自動的に除外） |
| `team_members` | チームメンバー全員 | `team_members` テーブルの `member_email`（メールアドレスが存在するメンバーのみ） |
| `custom` | カスタム（今後の拡張用） | 自前で `sendTemplate()` を呼び出す際に指定してください |

> ⚠️ チームメンバー宛テンプレートは現在 `team_member_payment` のみ `team_members` に設定しています。その他のテンプレートは既定で `guardian` です。

## コード側の変更点

- `lib/EmailRecipientResolver.php`  
  Supabase から申込情報を取得し、`recipient_type` に応じて宛先リストを返すヘルパーです。

- `lib/EmailTemplateService.php`  
  - `sendTemplateToApplication($templateType, $applicationId, $variables = [], $options = [])` を追加。  
  - 内部で `recipient_type` を見て、必要な数だけ `sendTemplate()` 相当の処理を実行します。
  - 既存の `sendTemplate()` も引き続き利用可能（明示的に宛先を指定したい場合）。

- `api/submit-application.php` / `api/save-payment-method.php` / `api/kyc/mark-as-completed.php`  
  すべて `sendTemplateToApplication()` を使うように更新済みなので、テンプレートの `recipient_type` を変更するだけで宛先を切り替えられます。

## DB への反映方法

1. 新しい `recipient_type` カラムは `database/add-email-template-recipient-type.sql` で追加できます。
2. 既存のテンプレートはデフォルトで `guardian`。  
   `team_member_payment` のみ `team_members` に更新する SQL を含めています。
3. テンプレートを追加する場合は、`INSERT` 時に `recipient_type` を指定してください（例: `card_registration_completed` は `'guardian'`）。

## 運用フロー（例）

1. 管理画面でテンプレートを編集 → 宛先を保護者から参加者に切り替えたい場合は、DB で `recipient_type` を `participant` に更新。
2. コード変更不要で、以降は同じテンプレが自動的に参加者宛に送信されます。
3. 将来的に管理画面から `recipient_type` を編集できるようにする余地もあります（UI 側でセレクトボックスを追加するなど）。

## 補足

- 宛先の解決は Supabase 経由で都度行われるため、メール送信時点の最新情報（生徒メール変更など）が反映されます。
- サンドボックスモード (`EMAIL_SANDBOX_MODE` = true) の場合は、従来通り全メールが `EMAIL_SANDBOX_RECIPIENT` に集約されます。
- 重複メールアドレス（同じアドレスが複数ロールに設定されている場合）は自動的に除去しています。

これで「保護者向けメール」「生徒向けメール」を切り替える準備が整いました。テンプレートごとの宛先設計が固まり次第、`recipient_type` の値を更新するだけで運用に反映できます。

