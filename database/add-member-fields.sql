-- team_members テーブルに電話番号と学年カラムを追加

ALTER TABLE team_members
ADD COLUMN IF NOT EXISTS member_phone VARCHAR(20),
ADD COLUMN IF NOT EXISTS member_grade VARCHAR(20);

-- コメント追加
COMMENT ON COLUMN team_members.member_phone IS 'メンバーの電話番号';
COMMENT ON COLUMN team_members.member_grade IS 'メンバーの学年';

