-- 既存のチームメンバーにデフォルト値を設定

-- member_gradeのデフォルト値を設定（例：小学1年生）
UPDATE team_members
SET member_grade = '未設定'
WHERE member_grade IS NULL;

-- member_phoneのデフォルト値を設定（空文字列）
UPDATE team_members
SET member_phone = ''
WHERE member_phone IS NULL;

-- 確認用クエリ
SELECT 
    id,
    member_name,
    member_email,
    member_phone,
    member_grade,
    member_number
FROM team_members
ORDER BY team_application_id, member_number;

