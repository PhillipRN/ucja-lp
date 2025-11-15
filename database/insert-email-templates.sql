-- =============================================
-- メールテンプレート初期データ
-- =============================================

-- 既存のテンプレートを削除（クリーンインストール）
DELETE FROM email_templates;

-- 1. 申込受付確認メール
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
    'application_confirmation',
    '申込受付確認',
    '【Cambridge Exam】申込受付のお知らせ（申込番号：{{application_number}}）',
    '{{guardian_name}} 様

この度はCambridge Examにお申し込みいただき、誠にありがとうございます。

■ 申込内容
━━━━━━━━━━━━━━━━━━━━━━━━━━━━
申込番号：{{application_number}}
参加形式：{{participation_type}}
参加者名：{{participant_name}}
金額：{{amount}}円
━━━━━━━━━━━━━━━━━━━━━━━━━━━━

■ 今後の流れ
━━━━━━━━━━━━━━━━━━━━━━━━━━━━
1. カード情報の登録
   以下のURLからクレジットカード情報をご登録ください。
   {{card_registration_url}}

2. 本人確認（eKYC）
   カード登録完了後、本人確認手続きをお願いいたします。

3. 決済
   本人確認完了後、ご登録のカードから自動的に決済されます。

4. 試験日
   決済完了後、試験日と詳細をご案内いたします。
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
            申込受付のお知らせ
        </h2>
        
        <p>{{guardian_name}} 様</p>
        
        <p>この度はCambridge Examにお申し込みいただき、誠にありがとうございます。</p>
        
        <div style="background: #f3f4f6; padding: 20px; border-radius: 8px; margin: 20px 0;">
            <h3 style="margin-top: 0;">■ 申込内容</h3>
            <table style="width: 100%;">
                <tr>
                    <td style="padding: 5px 0;"><strong>申込番号：</strong></td>
                    <td style="padding: 5px 0;">{{application_number}}</td>
                </tr>
                <tr>
                    <td style="padding: 5px 0;"><strong>参加形式：</strong></td>
                    <td style="padding: 5px 0;">{{participation_type}}</td>
                </tr>
                <tr>
                    <td style="padding: 5px 0;"><strong>参加者名：</strong></td>
                    <td style="padding: 5px 0;">{{participant_name}}</td>
                </tr>
                <tr>
                    <td style="padding: 5px 0;"><strong>金額：</strong></td>
                    <td style="padding: 5px 0;">{{amount}}円</td>
                </tr>
            </table>
        </div>
        
        <div style="margin: 20px 0;">
            <h3>■ 今後の流れ</h3>
            <ol style="line-height: 2;">
                <li><strong>カード情報の登録</strong><br>
                    <a href="{{card_registration_url}}" style="color: #2563eb;">こちら</a>からクレジットカード情報をご登録ください。
                </li>
                <li><strong>本人確認（eKYC）</strong><br>
                    カード登録完了後、本人確認手続きをお願いいたします。
                </li>
                <li><strong>決済</strong><br>
                    本人確認完了後、ご登録のカードから自動的に決済されます。
                </li>
                <li><strong>試験日</strong><br>
                    決済完了後、試験日と詳細をご案内いたします。
                </li>
            </ol>
        </div>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{card_registration_url}}" style="display: inline-block; background: #2563eb; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">
                カード情報を登録する
            </a>
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
    'application_flow', -- category
    1, -- sort_order
    TRUE -- is_active
);

-- 2. カード登録案内メール
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
    'card_registration',
    'カード登録案内',
    '【重要】カード情報の登録をお願いします（申込番号：{{application_number}}）',
    '{{guardian_name}} 様

Cambridge Examへのお申し込み（申込番号：{{application_number}}）について、
まだクレジットカード情報のご登録が完了しておりません。

■ カード情報登録について
━━━━━━━━━━━━━━━━━━━━━━━━━━━━
決済は試験終了後に行われますが、事前にカード情報のご登録が必要です。

下記URLより、カード情報をご登録ください：
{{card_registration_url}}

※登録時に決済は行われません
※安全なStripe決済システムを使用しています
━━━━━━━━━━━━━━━━━━━━━━━━━━━━

■ ご登録期限
━━━━━━━━━━━━━━━━━━━━━━━━━━━━
お早めのご登録をお願いいたします。
期限までにご登録いただけない場合、申込がキャンセルとなる可能性がございます。
━━━━━━━━━━━━━━━━━━━━━━━━━━━━

ご不明な点がございましたら、お気軽にお問い合わせください。

━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Cambridge Exam 事務局
お問い合わせ：info@cambridge-exam.com
━━━━━━━━━━━━━━━━━━━━━━━━━━━━',
    '<html>
<body style="font-family: sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin-bottom: 20px;">
            <strong style="color: #92400e;">【重要】カード情報の登録をお願いします</strong>
        </div>
        
        <p>{{guardian_name}} 様</p>
        
        <p>Cambridge Examへのお申し込み（申込番号：{{application_number}}）について、<br>
        まだクレジットカード情報のご登録が完了しておりません。</p>
        
        <div style="background: #f3f4f6; padding: 20px; border-radius: 8px; margin: 20px 0;">
            <h3 style="margin-top: 0; color: #2563eb;">■ カード情報登録について</h3>
            <p>決済は試験終了後に行われますが、事前にカード情報のご登録が必要です。</p>
            <ul style="line-height: 1.8;">
                <li>登録時に決済は行われません</li>
                <li>安全なStripe決済システムを使用しています</li>
                <li>情報は厳重に管理されます</li>
            </ul>
        </div>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{card_registration_url}}" style="display: inline-block; background: #10b981; color: white; padding: 15px 40px; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 16px;">
                今すぐカード情報を登録する
            </a>
        </div>
        
        <div style="background: #fee2e2; border-left: 4px solid #ef4444; padding: 15px; margin: 20px 0;">
            <h4 style="margin-top: 0; color: #991b1b;">■ ご登録期限</h4>
            <p style="margin-bottom: 0;">お早めのご登録をお願いいたします。<br>
            期限までにご登録いただけない場合、申込がキャンセルとなる可能性がございます。</p>
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
    'application_flow', -- category
    2, -- sort_order
    TRUE -- is_active
);

-- 3. 本人確認依頼メール
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
    'kyc_required',
    '本人確認依頼',
    '【重要】本人確認手続きをお願いします（申込番号：{{application_number}}）',
    '{{guardian_name}} 様

Cambridge Examへのお申し込み（申込番号：{{application_number}}）について、
カード情報のご登録ありがとうございました。

次のステップとして、本人確認手続き（eKYC）をお願いいたします。

■ 本人確認について
━━━━━━━━━━━━━━━━━━━━━━━━━━━━
マイページより、本人確認手続きを行ってください。

スマートフォンで以下の手順で完了します：
1. 本人確認書類（運転免許証、マイナンバーカード等）の撮影
2. 顔写真の撮影
3. 必要情報の入力

所要時間：約3〜5分
━━━━━━━━━━━━━━━━━━━━━━━━━━━━

■ 本人確認が完了すると
━━━━━━━━━━━━━━━━━━━━━━━━━━━━
本人確認完了後、自動的に決済が行われます。
決済完了後、試験日と詳細についてご案内いたします。
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
            <strong style="color: #1e40af;">【次のステップ】本人確認手続きをお願いします</strong>
        </div>
        
        <p>{{guardian_name}} 様</p>
        
        <p>Cambridge Examへのお申し込み（申込番号：{{application_number}}）について、<br>
        カード情報のご登録ありがとうございました。</p>
        
        <p>次のステップとして、<strong>本人確認手続き（eKYC）</strong>をお願いいたします。</p>
        
        <div style="background: #f3f4f6; padding: 20px; border-radius: 8px; margin: 20px 0;">
            <h3 style="margin-top: 0; color: #2563eb;">■ 本人確認の手順</h3>
            <p>スマートフォンで以下の手順で完了します：</p>
            <ol style="line-height: 2;">
                <li>本人確認書類の撮影<br>
                    <span style="font-size: 0.9em; color: #666;">（運転免許証、マイナンバーカード等）</span>
                </li>
                <li>顔写真の撮影</li>
                <li>必要情報の入力</li>
            </ol>
            <p style="color: #059669; font-weight: bold;">所要時間：約3〜5分</p>
        </div>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{mypage_url}}" style="display: inline-block; background: #8b5cf6; color: white; padding: 15px 40px; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 16px;">
                マイページで本人確認を行う
            </a>
        </div>
        
        <div style="background: #e0f2fe; border-left: 4px solid #0284c7; padding: 15px; margin: 20px 0;">
            <h4 style="margin-top: 0; color: #075985;">■ 本人確認が完了すると</h4>
            <ul style="margin-bottom: 0; line-height: 1.8;">
                <li>自動的に決済が行われます</li>
                <li>試験日と詳細についてご案内いたします</li>
            </ul>
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
    'application_flow', -- category
    4, -- sort_order
    TRUE -- is_active
);

-- 4. 決済完了通知メール
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
    'payment_confirmation',
    '決済完了通知',
    '【Cambridge Exam】決済完了のお知らせ（申込番号：{{application_number}}）',
    '{{guardian_name}} 様

Cambridge Exam（申込番号：{{application_number}}）の決済が完了いたしました。

■ 決済内容
━━━━━━━━━━━━━━━━━━━━━━━━━━━━
申込番号：{{application_number}}
参加者名：{{participant_name}}
金額：{{amount}}円
決済日時：{{payment_date}}
━━━━━━━━━━━━━━━━━━━━━━━━━━━━

■ 試験について
━━━━━━━━━━━━━━━━━━━━━━━━━━━━
試験日：{{exam_date}}
会場：（後日ご案内いたします）

試験の詳細につきましては、別途メールにてご案内いたします。
━━━━━━━━━━━━━━━━━━━━━━━━━━━━

■ 領収書について
━━━━━━━━━━━━━━━━━━━━━━━━━━━━
マイページより領収書をダウンロードいただけます。
━━━━━━━━━━━━━━━━━━━━━━━━━━━━

この度はCambridge Examにお申し込みいただき、誠にありがとうございました。
試験当日をお楽しみに！

━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Cambridge Exam 事務局
お問い合わせ：info@cambridge-exam.com
━━━━━━━━━━━━━━━━━━━━━━━━━━━━',
    '<html>
<body style="font-family: sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <div style="background: #d1fae5; border-left: 4px solid #10b981; padding: 15px; margin-bottom: 20px;">
            <strong style="color: #065f46;">✓ 決済が完了しました</strong>
        </div>
        
        <p>{{guardian_name}} 様</p>
        
        <p>Cambridge Exam（申込番号：{{application_number}}）の決済が完了いたしました。</p>
        
        <div style="background: #f3f4f6; padding: 20px; border-radius: 8px; margin: 20px 0;">
            <h3 style="margin-top: 0; color: #10b981;">■ 決済内容</h3>
            <table style="width: 100%;">
                <tr>
                    <td style="padding: 5px 0;"><strong>申込番号：</strong></td>
                    <td style="padding: 5px 0;">{{application_number}}</td>
                </tr>
                <tr>
                    <td style="padding: 5px 0;"><strong>参加者名：</strong></td>
                    <td style="padding: 5px 0;">{{participant_name}}</td>
                </tr>
                <tr>
                    <td style="padding: 5px 0;"><strong>金額：</strong></td>
                    <td style="padding: 5px 0; font-size: 1.2em; color: #10b981;"><strong>{{amount}}円</strong></td>
                </tr>
                <tr>
                    <td style="padding: 5px 0;"><strong>決済日時：</strong></td>
                    <td style="padding: 5px 0;">{{payment_date}}</td>
                </tr>
            </table>
        </div>
        
        <div style="background: #e0f2fe; border-left: 4px solid #0284c7; padding: 15px; margin: 20px 0;">
            <h4 style="margin-top: 0; color: #075985;">■ 試験について</h4>
            <p style="margin: 10px 0;"><strong>試験日：</strong> {{exam_date}}</p>
            <p style="margin: 10px 0;"><strong>会場：</strong> 後日ご案内いたします</p>
            <p style="margin-bottom: 0; font-size: 0.9em;">試験の詳細につきましては、別途メールにてご案内いたします。</p>
        </div>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{mypage_url}}" style="display: inline-block; background: #2563eb; color: white; padding: 15px 40px; text-decoration: none; border-radius: 8px; font-weight: bold;">
                マイページで詳細を確認
            </a>
        </div>
        
        <p style="margin-top: 30px; text-align: center; font-size: 1.1em; color: #059669;">
            <strong>この度はCambridge Examにお申し込みいただき、<br>
            誠にありがとうございました。</strong>
        </p>
        
        <p style="text-align: center; color: #666;">試験当日をお楽しみに！</p>
        
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
    6, -- sort_order
    TRUE -- is_active
);

-- 5. 試験日リマインダーメール
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
    'exam_reminder',
    '試験日リマインダー',
    '【リマインダー】Cambridge Exam 試験日のお知らせ（{{exam_date}}）',
    '{{guardian_name}} 様

Cambridge Exam（申込番号：{{application_number}}）の試験日が近づいてまいりました。

■ 試験情報
━━━━━━━━━━━━━━━━━━━━━━━━━━━━
試験日：{{exam_date}}
集合時間：{{meeting_time}}
会場：{{venue_name}}
住所：{{venue_address}}
━━━━━━━━━━━━━━━━━━━━━━━━━━━━

■ 持ち物
━━━━━━━━━━━━━━━━━━━━━━━━━━━━
□ 受験票（マイページからダウンロード可能）
□ 筆記用具（鉛筆・消しゴム）
□ 身分証明書
□ 飲み物（フタ付きのもの）
━━━━━━━━━━━━━━━━━━━━━━━━━━━━

■ 注意事項
━━━━━━━━━━━━━━━━━━━━━━━━━━━━
・試験開始15分前までに会場にお越しください
・遅刻された場合は受験できない場合がございます
・当日の連絡先：{{emergency_contact}}
━━━━━━━━━━━━━━━━━━━━━━━━━━━━

それでは、当日お待ちしております。
皆様のご健闘をお祈りしております！

━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Cambridge Exam 事務局
お問い合わせ：info@cambridge-exam.com
━━━━━━━━━━━━━━━━━━━━━━━━━━━━',
    '<html>
<body style="font-family: sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin-bottom: 20px;">
            <strong style="color: #92400e;">📅 試験日が近づいています</strong>
        </div>
        
        <p>{{guardian_name}} 様</p>
        
        <p>Cambridge Exam（申込番号：{{application_number}}）の<strong>試験日が近づいてまいりました。</strong></p>
        
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: center;">
            <h2 style="margin: 0; font-size: 1.5em;">試験日</h2>
            <p style="font-size: 2em; margin: 10px 0; font-weight: bold;">{{exam_date}}</p>
            <p style="margin: 0;">集合時間：{{meeting_time}}</p>
        </div>
        
        <div style="background: #f3f4f6; padding: 20px; border-radius: 8px; margin: 20px 0;">
            <h3 style="margin-top: 0; color: #2563eb;">■ 会場情報</h3>
            <p style="margin: 5px 0;"><strong>会場名：</strong> {{venue_name}}</p>
            <p style="margin: 5px 0;"><strong>住所：</strong> {{venue_address}}</p>
            <div style="text-align: center; margin-top: 15px;">
                <a href="{{map_url}}" style="display: inline-block; background: #10b981; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-size: 0.9em;">
                    地図を見る
                </a>
            </div>
        </div>
        
        <div style="background: #fef2f2; border-left: 4px solid #ef4444; padding: 15px; margin: 20px 0;">
            <h4 style="margin-top: 0; color: #991b1b;">■ 持ち物チェックリスト</h4>
            <ul style="margin-bottom: 0; line-height: 2;">
                <li>☑ 受験票（マイページからダウンロード可能）</li>
                <li>☑ 筆記用具（鉛筆・消しゴム）</li>
                <li>☑ 身分証明書</li>
                <li>☑ 飲み物（フタ付きのもの）</li>
            </ul>
        </div>
        
        <div style="background: #e0f2fe; border-left: 4px solid #0284c7; padding: 15px; margin: 20px 0;">
            <h4 style="margin-top: 0; color: #075985;">■ 注意事項</h4>
            <ul style="margin-bottom: 0; line-height: 1.8;">
                <li>試験開始<strong>15分前</strong>までに会場にお越しください</li>
                <li>遅刻された場合は受験できない場合がございます</li>
                <li>当日の連絡先：<strong>{{emergency_contact}}</strong></li>
            </ul>
        </div>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{mypage_url}}" style="display: inline-block; background: #2563eb; color: white; padding: 15px 40px; text-decoration: none; border-radius: 8px; font-weight: bold;">
                受験票をダウンロード
            </a>
        </div>
        
        <p style="margin-top: 30px; text-align: center; font-size: 1.2em; color: #2563eb;">
            <strong>それでは、当日お待ちしております。<br>
            皆様のご健闘をお祈りしております！</strong>
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
    'exam_related', -- category
    10, -- sort_order
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

