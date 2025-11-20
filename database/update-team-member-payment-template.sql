-- =============================================
-- team_member_payment テンプレートを保護者向けに更新
-- =============================================

UPDATE email_templates
SET
    subject = '【Cambridge Exam】チームメンバーのお支払い状況のご確認（{{team_name}}）',
    body_text = '{{guardian_name}} 様

Cambridge Exam（申込番号：{{application_number}}）のチーム「{{team_name}}」について、下記メンバーのお支払い状況をご案内いたします。

■ 未完了のメンバー
━━━━━━━━━━━━━━━━━━━━━━━━━━━━
メンバー名：{{member_name}}
登録メール：{{member_email}}
参加費：{{amount}}円
━━━━━━━━━━━━━━━━━━━━━━━━━━━━

■ お支払い方法
━━━━━━━━━━━━━━━━━━━━━━━━━━━━
チーム管理ページより、該当メンバーに再度ご案内いただくか、代理で決済を完了させることが可能です。
{{payment_link}}

■ お支払い期限
━━━━━━━━━━━━━━━━━━━━━━━━━━━━
{{deadline}}

期限までに完了しない場合、チームとしての参加ができなくなる可能性がございます。ご不明点があれば事務局までお問い合わせください。

━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Cambridge Exam 事務局
お問い合わせ：info@cambridge-exam.com
━━━━━━━━━━━━━━━━━━━━━━━━━━━━',
    body_html = '<html>
<body style="font-family: sans-serif; line-height: 1.7; color: #333;">
    <div style="max-width: 640px; margin: 0 auto; padding: 24px;">
        <p>{{guardian_name}} 様</p>

        <p>Cambridge Exam（申込番号：{{application_number}}）のチーム「<strong>{{team_name}}</strong>」について、<br>
        下記メンバーのお支払い状況をご案内いたします。</p>

        <div style="background: #fef9c3; border-left: 4px solid #ca8a04; padding: 18px; margin: 24px 0;">
            <h3 style="margin-top: 0; color: #92400e;">未完了のメンバー</h3>
            <p style="margin: 6px 0;">メンバー名：<strong>{{member_name}}</strong></p>
            <p style="margin: 6px 0;">登録メール：{{member_email}}</p>
            <p style="margin: 6px 0;">参加費：<strong>{{amount}}円</strong></p>
        </div>

        <div style="background: #f0f9ff; border-left: 4px solid #0284c7; padding: 18px; margin: 24px 0;">
            <h3 style="margin-top: 0; color: #075985;">お支払い方法</h3>
            <p>チーム管理ページより、対象メンバーに再度案内するか、代理で決済を完了させることが可能です。</p>
            <p style="margin-top: 16px;">
                <a href="{{payment_link}}" style="display: inline-block; background: #0284c7; color: #fff; padding: 14px 32px; border-radius: 6px; text-decoration: none; font-weight: bold;">
                    チーム管理ページを開く
                </a>
            </p>
        </div>

        <div style="background: #fee2e2; border-left: 4px solid #ef4444; padding: 18px; margin: 24px 0;">
            <h3 style="margin-top: 0; color: #991b1b;">お支払い期限</h3>
            <p style="font-size: 1.2em; font-weight: bold;">{{deadline}}</p>
            <p style="margin-bottom: 0;">期限までに完了しない場合、チームとしての参加ができなくなる可能性がございます。</p>
        </div>

        <p>ご不明な点がございましたら、お気軽に事務局までお問い合わせください。</p>

        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 32px 0;">
        <p style="font-size: 0.9em; color: #666;">
            Cambridge Exam 事務局<br>
            お問い合わせ：info@cambridge-exam.com
        </p>
    </div>
</body>
</html>',
    recipient_type = 'guardian'
WHERE template_type = 'team_member_payment';

