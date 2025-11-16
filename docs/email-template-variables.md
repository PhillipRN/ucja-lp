# メールテンプレートで使用できる変数一覧

管理画面（`admin/email-templates.php`）で編集できるテンプレートでは、本文中に `{{variable_name}}` という形式で差し込み変数を利用できます。  
ここでは現在登録済みのテンプレートと、そのテンプレートで利用できる主な変数を整理します。

## 共通（自動で追加される変数）

| 変数名                     | 説明                                             |
| -------------------------- | ------------------------------------------------ |
| `website_url`              | `APP_URL` をベースにした公式サイト URL           |
| `mypage_url`               | `APP_URL` + `/my-page/dashboard.php`（自動生成） |
| `original_recipient_email` | サンドボックス送信時のみ、元の宛先アドレス       |

※ 上記以外にも、送信処理側で追加した変数（例：`email` や `card_registration_url` など）があれば、そのまま利用可能です。  
※ 宛先の切り替えについては `docs/email-recipient-routing.md` を参照してください。

## テンプレート別の変数

| テンプレート種別 (`template_type`)                | 主な差し込み変数                                                                                                                                    |
| ------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------- |
| `application_confirmation`<br>申込受付確認        | `guardian_name`, `application_number`, `participation_type`, `participant_name`, `amount`, `card_registration_url`                                  |
| `card_registration`<br>カード登録案内             | `guardian_name`, `application_number`, `card_registration_url`                                                                                      |
| `card_registration_completed`<br>カード登録完了   | `guardian_name`, `application_number`, `mypage_url`                                                                                                 |
| `kyc_required`<br>本人確認依頼                    | `guardian_name`, `application_number`, `mypage_url`                                                                                                 |
| `payment_confirmation`<br>決済完了通知            | `guardian_name`, `application_number`, `participant_name`, `amount`, `payment_date`, `exam_date`, `mypage_url`                                      |
| `payment_failed`<br>決済エラー通知               | `guardian_name`, `participant_name`, `application_number`, `amount`, `error_message`, `support_email`, `mypage_url`                                  |
| `exam_reminder`<br>試験日リマインダー             | `guardian_name`, `application_number`, `exam_date`, `meeting_time`, `venue_name`, `venue_address`, `emergency_contact`, `map_url`, `mypage_url`     |
| `team_member_payment`<br>チームメンバー支払い依頼 | `member_name`, `team_name`, `representative_name`, `amount`, `application_number`, `payment_link`, `deadline`                                       |
| `kyc_completed`<br>本人確認完了通知               | `guardian_name`, `application_number`, `amount`                                                                                                     |
| `general_announcement`<br>汎用お知らせ            | `guardian_name`, `announcement_title`, `announcement_content`                                                                                       |
| `schedule_change`<br>試験日程変更通知             | `guardian_name`, `application_number`, `old_date`, `new_date`, `venue_name`, `venue_address`, `change_reason`, `contact_email`, `response_deadline` |
| `result_announcement`<br>結果発表通知             | `guardian_name`, `application_number`, `mypage_url`                                                                                                 |

> 💡 **テキスト版のみ更新すれば OK**  
> `EmailTemplateService` が送信時にテキスト版の内容から HTML を自動生成するため、編集部はテキストタブだけを更新すれば最新内容が HTML メールにも反映されます（`EMAIL_AUTO_GENERATE_HTML_FROM_TEXT = true` が前提）。

## 変数の追加方法

1. API やバッチ処理で `EmailTemplateService::sendTemplate()` を呼び出す際に、`$variables` 配列へ任意のキーを追加する。
2. テンプレート本文に `{{your_variable_name}}` を記述する。

テンプレート保存時の DB 更新は不要で、送信時の差し込み内容だけを意識すれば OK です。  
不明点があれば `docs/email-template-variables.md` を参照しつつ、適宜このファイルに追記してください。
