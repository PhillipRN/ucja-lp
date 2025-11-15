-- お知らせ管理テーブル
CREATE TABLE IF NOT EXISTS announcements (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    
    -- 基本情報
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    announcement_date DATE NOT NULL,
    
    -- リンク・ファイル情報
    external_url VARCHAR(500), -- プレスリリースURLなど
    pdf_file_path VARCHAR(500), -- PDFファイルのパス
    
    -- 表示設定
    display_order INTEGER DEFAULT 0, -- 表示順序（小さい順）
    is_published BOOLEAN DEFAULT TRUE, -- 公開/非公開
    
    -- 管理情報
    created_by UUID REFERENCES admin_users(id),
    updated_by UUID REFERENCES admin_users(id),
    
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- インデックス
CREATE INDEX IF NOT EXISTS idx_announcements_display_order ON announcements(display_order);
CREATE INDEX IF NOT EXISTS idx_announcements_is_published ON announcements(is_published);
CREATE INDEX IF NOT EXISTS idx_announcements_announcement_date ON announcements(announcement_date DESC);

-- コメント
COMMENT ON TABLE announcements IS 'LPサイトのお知らせ管理テーブル';
COMMENT ON COLUMN announcements.title IS 'お知らせタイトル';
COMMENT ON COLUMN announcements.content IS 'お知らせ内容（HTML可）';
COMMENT ON COLUMN announcements.announcement_date IS 'お知らせ日付（●月○日形式で表示）';
COMMENT ON COLUMN announcements.external_url IS '外部リンクURL（プレスリリースなど）';
COMMENT ON COLUMN announcements.pdf_file_path IS 'PDFファイルパス（広告など）';
COMMENT ON COLUMN announcements.display_order IS '表示順序（小さい順）';
COMMENT ON COLUMN announcements.is_published IS '公開フラグ';

