-- =============================================
-- 追加メールテンプレート
-- =============================================

-- 6. チームメンバー支払いリンク送信
INSERT INTO email_templates (
    template_type,
    template_name,
    subject,
    body_text,
    body_html,
    use_sendgrid_template,
    category,
    sort_order,
    is_active
) VALUES (
    'team_member_payment',
    'チームメンバー支払いリンク',
    '【Cambridge Exam】お支払いのお願い（チーム：{{team_name}}）',
    '{{member_name}} 様

Cambridge Examのチーム「{{team_name}}」のメンバーとしてご登録いただき、ありがとうございます。

■ お支払いについて
━━━━━━━━━━━━━━━━━━━━━━━━━━━━
チーム代表者：{{representative_name}} 様より、
メンバー分の参加費お支払いのご依頼がございました。

参加費：{{amount}}円
申込番号：{{application_number}}
━━━━━━━━━━━━━━━━━━━━━━━━━━━━

■ お支払い方法
━━━━━━━━━━━━━━━━━━━━━━━━━━━━
以下のリンクより、クレジットカードでお支払いください：
{{payment_link}}

※決済は試験終了後に行われます
※安全なStripe決済システムを使用しています
━━━━━━━━━━━━━━━━━━━━━━━━━━━━

■ お支払い期限
━━━━━━━━━━━━━━━━━━━━━━━━━━━━
{{deadline}}

期限までにお支払いいただけない場合、
チームとしての参加ができなくなる可能性がございます。
━━━━━━━━━━━━━━━━━━━━━━━━━━━━

ご不明な点がございましたら、チーム代表者または事務局までお問い合わせください。

━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Cambridge Exam 事務局
お問い合わせ：info@cambridge-exam.com
━━━━━━━━━━━━━━━━━━━━━━━━━━━━',
    '<html>
<body style="font-family: sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #f97316; border-bottom: 3px solid #f97316; padding-bottom: 10px;">
            お支払いのお願い
        </h2>
        
        <p>{{member_name}} 様</p>
        
        <p>Cambridge Examのチーム「<strong>{{team_name}}</strong>」のメンバーとしてご登録いただき、ありがとうございます。</p>
        
        <div style="background: #fff7ed; border-left: 4px solid #f97316; padding: 15px; margin: 20px 0;">
            <p style="margin: 0;">チーム代表者：<strong>{{representative_name}}</strong> 様より、<br>
            メンバー分の参加費お支払いのご依頼がございました。</p>
        </div>
        
        <div style="background: #f3f4f6; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: center;">
            <h3 style="margin-top: 0; color: #f97316;">参加費</h3>
            <p style="font-size: 2.5em; margin: 10px 0; color: #f97316; font-weight: bold;">{{amount}}円</p>
            <p style="margin: 0; color: #666; font-size: 0.9em;">申込番号：{{application_number}}</p>
        </div>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{payment_link}}" style="display: inline-block; background: #10b981; color: white; padding: 18px 50px; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 18px;">
                お支払いページへ
            </a>
        </div>
        
        <div style="background: #e0f2fe; border-left: 4px solid #0284c7; padding: 15px; margin: 20px 0;">
            <h4 style="margin-top: 0; color: #075985;">ご安心ください</h4>
            <ul style="margin-bottom: 0; line-height: 1.8;">
                <li>決済は試験終了後に行われます</li>
                <li>安全なStripe決済システムを使用しています</li>
                <li>カード情報は暗号化されて保護されます</li>
            </ul>
        </div>
        
        <div style="background: #fee2e2; border-left: 4px solid #ef4444; padding: 15px; margin: 20px 0;">
            <h4 style="margin-top: 0; color: #991b1b;">お支払い期限</h4>
            <p style="font-size: 1.2em; margin: 10px 0; font-weight: bold;">{{deadline}}</p>
            <p style="margin-bottom: 0; font-size: 0.9em;">期限までにお支払いいただけない場合、チームとしての参加ができなくなる可能性がございます。</p>
        </div>
        
        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 20px 0;">
        
        <p style="font-size: 0.9em; color: #666;">
            Cambridge Exam 事務局<br>
            お問い合わせ：info@cambridge-exam.com
        </p>
    </div>
</body>
</html>',
    FALSE, -- use_sendgrid_template
    'application_flow', -- category
    7, -- sort_order
    TRUE -- is_active
);

-- 送信先設定
UPDATE email_templates
SET recipient_type = 'team_members'
WHERE template_type = 'team_member_payment';

-- 7. 本人確認完了通知
INSERT INTO email_templates (
    template_type,
    template_name,
    subject,
    body_text,
    body_html,
    use_sendgrid_template,
    category,
    sort_order,
    is_active
) VALUES (
    'kyc_completed',
    '本人確認完了通知',
    '【Cambridge Exam】本人確認が完了しました（申込番号：{{application_number}}）',
    '{{guardian_name}} 様

Cambridge Exam（申込番号：{{application_number}}）の本人確認手続きが完了いたしました。

■ 次のステップ
━━━━━━━━━━━━━━━━━━━━━━━━━━━━
本人確認の完了に伴い、自動的に決済処理を開始いたしました。

決済完了後、改めてメールにてご連絡いたします。
━━━━━━━━━━━━━━━━━━━━━━━━━━━━

■ 決済について
━━━━━━━━━━━━━━━━━━━━━━━━━━━━
金額：{{amount}}円
決済方法：クレジットカード（登録済み）
決済タイミング：試験終了後
━━━━━━━━━━━━━━━━━━━━━━━━━━━━

引き続き、よろしくお願いいたします。

━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Cambridge Exam 事務局
お問い合わせ：info@cambridge-exam.com
━━━━━━━━━━━━━━━━━━━━━━━━━━━━',
    '<html>
<body style="font-family: sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <div style="background: #d1fae5; border-left: 4px solid #10b981; padding: 15px; margin-bottom: 20px;">
            <strong style="color: #065f46;">✓ 本人確認が完了しました</strong>
        </div>
        
        <p>{{guardian_name}} 様</p>
        
        <p>Cambridge Exam（申込番号：{{application_number}}）の<strong>本人確認手続きが完了</strong>いたしました。</p>
        
        <div style="background: #e0f2fe; border-left: 4px solid #0284c7; padding: 15px; margin: 20px 0;">
            <h4 style="margin-top: 0; color: #075985;">■ 次のステップ</h4>
            <p>本人確認の完了に伴い、<strong>自動的に決済処理を開始</strong>いたしました。</p>
            <p style="margin-bottom: 0;">決済完了後、改めてメールにてご連絡いたします。</p>
        </div>
        
        <div style="background: #f3f4f6; padding: 20px; border-radius: 8px; margin: 20px 0;">
            <h3 style="margin-top: 0;">■ 決済について</h3>
            <table style="width: 100%;">
                <tr>
                    <td style="padding: 5px 0;">金額：</td>
                    <td style="padding: 5px 0; font-weight: bold; color: #10b981;">{{amount}}円</td>
                </tr>
                <tr>
                    <td style="padding: 5px 0;">決済方法：</td>
                    <td style="padding: 5px 0;">クレジットカード（登録済み）</td>
                </tr>
                <tr>
                    <td style="padding: 5px 0;">決済タイミング：</td>
                    <td style="padding: 5px 0;">試験終了後</td>
                </tr>
            </table>
        </div>
        
        <p style="text-align: center; margin: 30px 0; color: #10b981; font-size: 1.1em;">
            <strong>引き続き、よろしくお願いいたします。</strong>
        </p>
        
        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 20px 0;">
        
        <p style="font-size: 0.9em; color: #666;">
            Cambridge Exam 事務局<br>
            お問い合わせ：info@cambridge-exam.com
        </p>
    </div>
</body>
</html>',
    FALSE, -- use_sendgrid_template
    'application_flow', -- category
    5, -- sort_order
    TRUE -- is_active
);

-- 8. 汎用お知らせテンプレート
INSERT INTO email_templates (
    template_type,
    template_name,
    subject,
    body_text,
    body_html,
    use_sendgrid_template,
    category,
    sort_order,
    is_active
) VALUES (
    'general_announcement',
    '汎用お知らせ',
    '【Cambridge Exam】{{announcement_title}}',
    '{{guardian_name}} 様

Cambridge Examよりお知らせがございます。

━━━━━━━━━━━━━━━━━━━━━━━━━━━━
{{announcement_content}}
━━━━━━━━━━━━━━━━━━━━━━━━━━━━

ご不明な点がございましたら、お気軽にお問い合わせください。

━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Cambridge Exam 事務局
お問い合わせ：info@cambridge-exam.com
━━━━━━━━━━━━━━━━━━━━━━━━━━━━',
    '<html>
<body style="font-family: sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #2563eb; border-bottom: 3px solid #2563eb; padding-bottom: 10px;">
            {{announcement_title}}
        </h2>
        
        <p>{{guardian_name}} 様</p>
        
        <p>Cambridge Examよりお知らせがございます。</p>
        
        <div style="background: #f3f4f6; padding: 20px; border-radius: 8px; margin: 20px 0;">
            {{announcement_content}}
        </div>
        
        <p style="margin-top: 30px; font-size: 0.9em; color: #666;">
            ご不明な点がございましたら、お気軽にお問い合わせください。
        </p>
        
        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 20px 0;">
        
        <p style="font-size: 0.9em; color: #666;">
            Cambridge Exam 事務局<br>
            お問い合わせ：info@cambridge-exam.com
        </p>
    </div>
</body>
</html>',
    FALSE, -- use_sendgrid_template
    'announcements', -- category
    20, -- sort_order
    TRUE -- is_active
);

-- 9. 試験日程変更通知
INSERT INTO email_templates (
    template_type,
    template_name,
    subject,
    body_text,
    body_html,
    use_sendgrid_template,
    category,
    sort_order,
    is_active
) VALUES (
    'schedule_change',
    '試験日程変更通知',
    '【重要】Cambridge Exam 試験日程変更のお知らせ',
    '{{guardian_name}} 様

Cambridge Exam（申込番号：{{application_number}}）について、
重要なお知らせがございます。

■ 試験日程の変更について
━━━━━━━━━━━━━━━━━━━━━━━━━━━━
誠に申し訳ございませんが、やむを得ない事情により、
試験日程を変更させていただくこととなりました。

【変更前】{{old_date}}
　↓
【変更後】{{new_date}}

会場：{{venue_name}}
住所：{{venue_address}}
━━━━━━━━━━━━━━━━━━━━━━━━━━━━

■ 変更理由
━━━━━━━━━━━━━━━━━━━━━━━━━━━━
{{change_reason}}
━━━━━━━━━━━━━━━━━━━━━━━━━━━━

■ ご都合が悪い場合
━━━━━━━━━━━━━━━━━━━━━━━━━━━━
変更後の日程でご参加が難しい場合は、以下までご連絡ください。
キャンセル料なしでのキャンセル、または別日程への振替を
承らせていただきます。

連絡先：{{contact_email}}
連絡期限：{{response_deadline}}
━━━━━━━━━━━━━━━━━━━━━━━━━━━━

ご迷惑をおかけして誠に申し訳ございません。
何卒ご理解のほど、よろしくお願いいたします。

━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Cambridge Exam 事務局
お問い合わせ：info@cambridge-exam.com
━━━━━━━━━━━━━━━━━━━━━━━━━━━━',
    '<html>
<body style="font-family: sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <div style="background: #fee2e2; border-left: 4px solid #ef4444; padding: 15px; margin-bottom: 20px;">
            <strong style="color: #991b1b;">【重要】試験日程変更のお知らせ</strong>
        </div>
        
        <p>{{guardian_name}} 様</p>
        
        <p>Cambridge Exam（申込番号：{{application_number}}）について、<br>
        <strong style="color: #ef4444;">重要なお知らせ</strong>がございます。</p>
        
        <div style="background: #fef2f2; border: 2px solid #ef4444; padding: 20px; border-radius: 8px; margin: 20px 0;">
            <h3 style="margin-top: 0; color: #991b1b;">試験日程の変更について</h3>
            <p>誠に申し訳ございませんが、やむを得ない事情により、試験日程を変更させていただくこととなりました。</p>
            
            <div style="text-align: center; margin: 20px 0;">
                <div style="background: white; padding: 15px; border-radius: 8px; margin-bottom: 10px;">
                    <p style="margin: 0; font-size: 0.9em; color: #666;">変更前</p>
                    <p style="margin: 5px 0; font-size: 1.2em; font-weight: bold; text-decoration: line-through; color: #999;">{{old_date}}</p>
                </div>
                <div style="font-size: 2em; color: #ef4444; margin: 10px 0;">↓</div>
                <div style="background: #10b981; color: white; padding: 15px; border-radius: 8px;">
                    <p style="margin: 0; font-size: 0.9em;">変更後</p>
                    <p style="margin: 5px 0; font-size: 1.5em; font-weight: bold;">{{new_date}}</p>
                </div>
            </div>
            
            <p style="margin: 10px 0;"><strong>会場：</strong> {{venue_name}}</p>
            <p style="margin: 10px 0;"><strong>住所：</strong> {{venue_address}}</p>
        </div>
        
        <div style="background: #f3f4f6; padding: 15px; border-radius: 8px; margin: 20px 0;">
            <h4 style="margin-top: 0;">変更理由</h4>
            <p style="margin-bottom: 0;">{{change_reason}}</p>
        </div>
        
        <div style="background: #fff7ed; border-left: 4px solid #f97316; padding: 15px; margin: 20px 0;">
            <h4 style="margin-top: 0; color: #9a3412;">ご都合が悪い場合</h4>
            <p>変更後の日程でご参加が難しい場合は、以下までご連絡ください。</p>
            <ul style="line-height: 1.8;">
                <li><strong>キャンセル料なしでのキャンセル</strong></li>
                <li><strong>別日程への振替</strong></li>
            </ul>
            <p style="margin: 10px 0;"><strong>連絡先：</strong> {{contact_email}}</p>
            <p style="margin: 10px 0;"><strong>連絡期限：</strong> <span style="color: #ef4444; font-weight: bold;">{{response_deadline}}</span></p>
        </div>
        
        <p style="text-align: center; margin: 30px 0; font-size: 1.1em;">
            ご迷惑をおかけして誠に申し訳ございません。<br>
            何卒ご理解のほど、よろしくお願いいたします。
        </p>
        
        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 20px 0;">
        
        <p style="font-size: 0.9em; color: #666;">
            Cambridge Exam 事務局<br>
            お問い合わせ：info@cambridge-exam.com
        </p>
    </div>
</body>
</html>',
    FALSE, -- use_sendgrid_template
    'announcements', -- category
    21, -- sort_order
    TRUE -- is_active
);

-- 10. 結果発表通知
INSERT INTO email_templates (
    template_type,
    template_name,
    subject,
    body_text,
    body_html,
    use_sendgrid_template,
    category,
    sort_order,
    is_active
) VALUES (
    'result_announcement',
    '結果発表通知',
    '【Cambridge Exam】試験結果発表のお知らせ（申込番号：{{application_number}}）',
    '{{guardian_name}} 様

Cambridge Exam（申込番号：{{application_number}}）の試験結果が発表されました。

■ 結果確認について
━━━━━━━━━━━━━━━━━━━━━━━━━━━━
マイページより試験結果をご確認いただけます。

マイページURL：{{mypage_url}}

結果には以下の情報が記載されています：
・総合得点
・各セクションの得点
・順位（参加形式別）
・評価コメント
━━━━━━━━━━━━━━━━━━━━━━━━━━━━

■ 受賞者の方へ
━━━━━━━━━━━━━━━━━━━━━━━━━━━━
賞品の受賞対象となった方には、別途ご連絡いたします。
━━━━━━━━━━━━━━━━━━━━━━━━━━━━

この度はCambridge Examにご参加いただき、誠にありがとうございました。
皆様の健闘を心よりお祈りしております。

━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Cambridge Exam 事務局
お問い合わせ：info@cambridge-exam.com
━━━━━━━━━━━━━━━━━━━━━━━━━━━━',
    '<html>
<body style="font-family: sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 8px; text-align: center; margin-bottom: 20px;">
            <h2 style="margin: 0; font-size: 1.8em;">🎉 試験結果発表 🎉</h2>
        </div>
        
        <p>{{guardian_name}} 様</p>
        
        <p>Cambridge Exam（申込番号：{{application_number}}）の<br>
        <strong style="color: #8b5cf6; font-size: 1.2em;">試験結果が発表されました。</strong></p>
        
        <div style="background: #f3f4f6; padding: 20px; border-radius: 8px; margin: 20px 0;">
            <h3 style="margin-top: 0; color: #2563eb;">■ 結果確認について</h3>
            <p>マイページより試験結果をご確認いただけます。</p>
            
            <div style="text-align: center; margin: 20px 0;">
                <a href="{{mypage_url}}" style="display: inline-block; background: #8b5cf6; color: white; padding: 15px 40px; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 16px;">
                    結果を確認する
                </a>
            </div>
            
            <h4 style="color: #666; margin: 20px 0 10px 0;">結果に含まれる情報：</h4>
            <ul style="line-height: 2;">
                <li>総合得点</li>
                <li>各セクションの得点</li>
                <li>順位（参加形式別）</li>
                <li>評価コメント</li>
            </ul>
        </div>
        
        <div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin: 20px 0;">
            <h4 style="margin-top: 0; color: #92400e;">🏆 受賞者の方へ</h4>
            <p style="margin-bottom: 0;">賞品の受賞対象となった方には、別途ご連絡いたします。</p>
        </div>
        
        <p style="text-align: center; margin: 30px 0; font-size: 1.1em; color: #8b5cf6;">
            <strong>この度はCambridge Examにご参加いただき、<br>
            誠にありがとうございました。</strong>
        </p>
        
        <p style="text-align: center; color: #666;">
            皆様の健闘を心よりお祈りしております。
        </p>
        
        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 20px 0;">
        
        <p style="font-size: 0.9em; color: #666;">
            Cambridge Exam 事務局<br>
            お問い合わせ：info@cambridge-exam.com
        </p>
    </div>
</body>
</html>',
    FALSE, -- use_sendgrid_template
    'post_exam', -- category
    30, -- sort_order
    TRUE -- is_active
);

-- 確認
SELECT 
    template_type,
    template_name,
    subject,
    is_active,
    created_at
FROM email_templates
ORDER BY created_at;

