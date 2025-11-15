-- =============================================
-- Update email_logs status constraint
-- email_logsテーブルのstatus制約を更新
-- =============================================

-- 既存のCHECK制約を削除
ALTER TABLE email_logs 
DROP CONSTRAINT IF EXISTS email_logs_status_check;

-- 新しいCHECK制約を追加（'pending'を含む）
ALTER TABLE email_logs 
ADD CONSTRAINT email_logs_status_check 
CHECK (status IN (
    'pending',   -- 送信待ち（NEW）
    'sent',      -- 送信完了
    'failed',    -- 送信失敗
    'delivered', -- 配信完了
    'opened',    -- 開封
    'clicked',   -- リンククリック
    'bounced',   -- バウンス（届かなかった）
    'spam'       -- スパム報告
));

-- 確認
SELECT constraint_name, check_clause
FROM information_schema.check_constraints
WHERE constraint_name = 'email_logs_status_check';

