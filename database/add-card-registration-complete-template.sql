-- =============================================
-- Add card registration completed template
-- =============================================

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
    'card_registration_completed',
    'カード登録完了通知',
    '【Cambridge Exam】カード情報の登録が完了しました（申込番号：{{application_number}}）',
    '{{guardian_name}} 様

カード情報のご登録ありがとうございます。

■ 次のステップ
━━━━━━━━━━━━━━━━━━━━━━━━━━━━
1. 本人確認（eKYC）を実施してください。
   マイページから手続きできます：{{mypage_url}}
2. 本人確認が完了すると、自動的に決済処理が開始されます。
━━━━━━━━━━━━━━━━━━━━━━━━━━━━

ご不明な点がございましたら、お気軽にお問い合わせください。

━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Cambridge Exam 事務局
お問い合わせ：info@cambridge-exam.com
━━━━━━━━━━━━━━━━━━━━━━━━━━━━',
    '<html>
<body style="font-family: sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <div style="background: #dbeafe; border-left: 4px solid #2563eb; padding: 15px; margin-bottom: 20px;">
            <strong style="color: #1e40af;">カード情報の登録が完了しました</strong>
        </div>

        <p>{{guardian_name}} 様</p>
        <p>Cambridge Exam（申込番号：{{application_number}}）のカード情報のご登録ありがとうございます。</p>

        <div style="background: #f3f4f6; padding: 20px; border-radius: 8px; margin: 20px 0;">
            <h3 style="margin-top: 0; color: #2563eb;">■ 次のステップ</h3>
            <ol style="line-height: 2;">
                <li>本人確認（eKYC）をマイページから実施してください。</li>
                <li>本人確認が完了すると自動的に決済処理が開始されます。</li>
            </ol>
            <div style="text-align: center; margin-top: 20px;">
                <a href="{{mypage_url}}" style="display: inline-block; background: #2563eb; color: white; padding: 12px 32px; text-decoration: none; border-radius: 8px;">
                    マイページを開く
                </a>
            </div>
        </div>

        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 20px 0;">
        <p style="font-size: 0.9em; color: #666;">
            Cambridge Exam 事務局<br>
            お問い合わせ：info@cambridge-exam.com
        </p>
    </div>
</body>
</html>',
    FALSE,
    'application_flow',
    3,
    'guardian',
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

UPDATE email_templates
SET sort_order = 7
WHERE template_type = 'team_member_payment';

