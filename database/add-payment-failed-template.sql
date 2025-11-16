-- =============================================
-- Add payment failed template
-- =============================================

BEGIN;

INSERT INTO email_templates (
    template_type,
    template_name,
    subject,
    body_text,
    body_html,
    use_sendgrid_template,
    category,
    sort_order,
    recipient_type,
    is_active
) VALUES (
    'payment_failed',
    '決済エラー通知',
    '【Cambridge Exam】決済エラーのお知らせ（申込番号：{{application_number}}）',
    '{{guardian_name}} 様

Cambridge Exam（申込番号：{{application_number}}）の決済に失敗しました。

■ 決済情報
━━━━━━━━━━━━━━━━━━━━━━━━━━━━
参加者（またはチーム）：{{participant_name}}
金額：{{amount}}円
エラー内容：{{error_message}}
━━━━━━━━━━━━━━━━━━━━━━━━━━━━

■ 対応方法
━━━━━━━━━━━━━━━━━━━━━━━━━━━━
1. マイページへアクセスし、カード情報／本人確認の状況をご確認ください。
2. 必要に応じてカード情報の再登録をお願いします。
3. ご不明点がある場合は、下記メールアドレスまでお問い合わせください。
   {{support_email}}
━━━━━━━━━━━━━━━━━━━━━━━━━━━━

自動的に解消しない場合、代表者宛にも通知されますのでご確認をお願いいたします。

ご不明な点がございましたら、お気軽にお問い合わせください。

━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Cambridge Exam 事務局
お問い合わせ：{{support_email}}
━━━━━━━━━━━━━━━━━━━━━━━━━━━━',
    '<html>
<body style="font-family: sans-serif; line-height: 1.7; color: #333;">
    <div style="max-width: 640px; margin: 0 auto; padding: 24px;">
        <div style="background: #fee2e2; border-left: 4px solid #dc2626; padding: 16px 20px; margin-bottom: 24px;">
            <strong style="color: #b91c1c;">決済に失敗しました</strong>
            <div style="color: #7f1d1d; font-size: 15px; margin-top: 4px;">
                申込番号：{{application_number}} / 参加者：{{participant_name}}
            </div>
        </div>

        <p>{{guardian_name}} 様</p>
        <p>Cambridge Exam（申込番号：{{application_number}}）の決済処理でエラーが発生しました。</p>

        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin: 24px 0;">
            <h3 style="margin: 0 0 12px; font-size: 16px; color: #0f172a;">■ 決済情報</h3>
            <ul style="list-style: none; padding: 0; margin: 0; font-size: 15px;">
                <li style="margin-bottom: 6px;"><strong>参加者 / チーム：</strong> {{participant_name}}</li>
                <li style="margin-bottom: 6px;"><strong>金額：</strong> ¥{{amount}}</li>
                <li><strong>エラー内容：</strong><br><span style="color: #dc2626;">{{error_message}}</span></li>
            </ul>
        </div>

        <div style="background: #fff7ed; border: 1px solid #fed7aa; border-radius: 12px; padding: 20px; margin-bottom: 24px;">
            <h3 style="margin: 0 0 12px; font-size: 16px; color: #9a3412;">■ 対応方法</h3>
            <ol style="margin: 0; padding-left: 18px; color: #7c2d12;">
                <li>マイページへアクセスし、カード登録・本人確認の状態をご確認ください。</li>
                <li>カードの有効期限切れ等が考えられる場合は、再度カード情報の登録をお願いします。</li>
                <li>不明な場合は {{support_email}} までお問い合わせください。</li>
            </ol>
        </div>

        <p>本メールは自動送信されています。ご不明点がございましたら以下よりお問い合わせください。</p>
        <p style="font-size: 14px; color: #475569;">
            Cambridge Exam 事務局<br>
            お問い合わせ：<a href="mailto:{{support_email}}" style="color: #2563eb;">{{support_email}}</a>
        </p>
    </div>
</body>
</html>',
    FALSE,
    'application_flow',
    5,
    'guardian_and_participant',
    TRUE
)
ON CONFLICT (template_type) DO UPDATE SET
    template_name = EXCLUDED.template_name,
    subject = EXCLUDED.subject,
    body_text = EXCLUDED.body_text,
    body_html = EXCLUDED.body_html,
    category = EXCLUDED.category,
    sort_order = EXCLUDED.sort_order,
    recipient_type = EXCLUDED.recipient_type,
    is_active = EXCLUDED.is_active,
    updated_at = NOW();

COMMIT;

