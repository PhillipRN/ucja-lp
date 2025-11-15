-- =============================================
-- メールテンプレートに並び順とカテゴリを追加
-- =============================================

-- sort_orderカラムを追加
ALTER TABLE email_templates 
ADD COLUMN IF NOT EXISTS sort_order INTEGER DEFAULT 0;

-- 既存のテンプレートにカテゴリと並び順を設定
-- カテゴリ: 'application_flow', 'exam_related', 'announcements', 'post_exam'

-- 1. 申込フロー
UPDATE email_templates SET category = 'application_flow', sort_order = 1 WHERE template_type = 'application_confirmation';
UPDATE email_templates SET category = 'application_flow', sort_order = 2 WHERE template_type = 'card_registration';
UPDATE email_templates SET category = 'application_flow', sort_order = 3 WHERE template_type = 'team_member_payment';
UPDATE email_templates SET category = 'application_flow', sort_order = 4 WHERE template_type = 'kyc_required';
UPDATE email_templates SET category = 'application_flow', sort_order = 5 WHERE template_type = 'kyc_completed';
UPDATE email_templates SET category = 'application_flow', sort_order = 6 WHERE template_type = 'payment_confirmation';

-- 2. 試験関連
UPDATE email_templates SET category = 'exam_related', sort_order = 10 WHERE template_type = 'exam_reminder';

-- 3. 運営からのお知らせ
UPDATE email_templates SET category = 'announcements', sort_order = 20 WHERE template_type = 'general_announcement';
UPDATE email_templates SET category = 'announcements', sort_order = 21 WHERE template_type = 'schedule_change';

-- 4. 試験後
UPDATE email_templates SET category = 'post_exam', sort_order = 30 WHERE template_type = 'result_announcement';

-- インデックスを追加
CREATE INDEX IF NOT EXISTS idx_email_templates_sort_order ON email_templates(category, sort_order);

-- 確認
SELECT 
    template_type,
    template_name,
    category,
    sort_order,
    is_active
FROM email_templates
ORDER BY sort_order, created_at;

