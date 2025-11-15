-- =============================================
-- email_templates テーブル構造更新
-- SendGrid用 → 独自テンプレート用に変更
-- =============================================

-- 既存のテーブルを削除して再作成
DROP TABLE IF EXISTS email_templates CASCADE;

-- 新しいテーブル構造
CREATE TABLE email_templates (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    
    -- テンプレート識別情報
    template_type VARCHAR(50) NOT NULL, -- application_confirmation, card_registration, etc.
    template_name VARCHAR(255) NOT NULL,
    
    -- メール内容
    subject VARCHAR(500) NOT NULL,
    body_text TEXT NOT NULL, -- プレーンテキスト版
    body_html TEXT, -- HTML版（オプション）
    
    -- メタ情報
    description TEXT,
    
    -- ステータス
    is_active BOOLEAN DEFAULT TRUE,
    
    -- 管理情報
    created_by UUID REFERENCES admin_users(id) ON DELETE SET NULL,
    updated_by UUID REFERENCES admin_users(id) ON DELETE SET NULL,
    
    -- タイムスタンプ
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- インデックス
CREATE INDEX idx_email_templates_type ON email_templates(template_type);
CREATE INDEX idx_email_templates_active ON email_templates(is_active);
CREATE INDEX idx_email_templates_created_at ON email_templates(created_at);

-- コメント
COMMENT ON TABLE email_templates IS 'メールテンプレート管理';
COMMENT ON COLUMN email_templates.template_type IS 'テンプレートタイプ（システム内部での識別子）';
COMMENT ON COLUMN email_templates.template_name IS 'テンプレート表示名';
COMMENT ON COLUMN email_templates.subject IS 'メール件名（変数使用可能）';
COMMENT ON COLUMN email_templates.body_text IS 'メール本文テキスト版（変数使用可能）';
COMMENT ON COLUMN email_templates.body_html IS 'メール本文HTML版（変数使用可能）';

-- 確認
SELECT 
    column_name, 
    data_type, 
    is_nullable,
    column_default
FROM information_schema.columns
WHERE table_name = 'email_templates'
ORDER BY ordinal_position;

