-- =============================================
-- Add email_type column to email_logs table
-- email_logsテーブルにemail_typeカラムを追加
-- =============================================

-- email_typeカラムを追加（既に存在する場合はスキップ）
DO $$ 
BEGIN
    IF NOT EXISTS (
        SELECT 1 
        FROM information_schema.columns 
        WHERE table_name = 'email_logs' 
        AND column_name = 'email_type'
    ) THEN
        ALTER TABLE email_logs 
        ADD COLUMN email_type VARCHAR(50);
        
        -- インデックスを作成
        CREATE INDEX idx_email_logs_email_type ON email_logs(email_type);
        
        -- コメントを追加
        COMMENT ON COLUMN email_logs.email_type IS 'メールの種類 (application_confirmation, kyc_complete, payment_confirmation, exam_notification, team_payment_request, general_notice)';
        
        RAISE NOTICE 'email_type column added successfully';
    ELSE
        RAISE NOTICE 'email_type column already exists';
    END IF;
END $$;

-- 既存データにtemplate_idからemail_typeを設定（オプション）
UPDATE email_logs 
SET email_type = template_id 
WHERE email_type IS NULL AND template_id IS NOT NULL;

-- =============================================
-- email_typeの想定値
-- =============================================
-- 'application_confirmation' - 申込完了
-- 'kyc_complete' - 本人確認完了
-- 'payment_confirmation' - 決済完了
-- 'exam_notification' - 試験案内
-- 'team_payment_request' - チームメンバー支払いリンク
-- 'general_notice' - 一般お知らせ

