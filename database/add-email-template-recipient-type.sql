-- =============================================
-- Add recipient_type column to email_templates
-- =============================================

ALTER TABLE email_templates
    ADD COLUMN IF NOT EXISTS recipient_type VARCHAR(30) DEFAULT 'guardian';

-- 既存レコードのNULL値をguardianに統一
UPDATE email_templates
SET recipient_type = 'guardian'
WHERE recipient_type IS NULL;

-- チームメンバー向けテンプレートの既定値を設定
UPDATE email_templates
SET recipient_type = 'team_members'
WHERE template_type = 'team_member_payment';

