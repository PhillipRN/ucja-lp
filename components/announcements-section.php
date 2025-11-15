<?php
/**
 * お知らせセクション
 * ニュースやプレスリリース、広告PDFなどを表示
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../lib/SupabaseClient.php';

$supabase = new SupabaseClient(SUPABASE_URL, SUPABASE_ANON_KEY);

// 公開中のお知らせを取得（日付降順、表示順序順）
try {
    $result = $supabase->from('announcements')
        ->select('*')
        ->eq('is_published', true)
        ->order('announcement_date', 'desc')
        ->order('display_order', 'asc')
        ->limit(10)
        ->execute();
    
    $announcements = $result['success'] && !empty($result['data']) ? $result['data'] : [];
} catch (Exception $e) {
    $announcements = [];
    error_log('Announcements fetch error: ' . $e->getMessage());
}

if (empty($announcements)) {
    return;
}
?>

<section id="announcements" class="py-10 bg-gray-50">
    <div class="container mx-auto px-6">
        <div class="text-center mb-4">
            <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-4">お知らせ</h2>
            <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                最新のニュースやプレスリリース、広告情報をお知らせします
            </p>
        </div>
        
        <!-- お知らせリスト（1つのカード内に表示） -->
        <div class="max-w-4xl mx-auto">
            <div class="bg-white rounded-xl shadow-md p-4 md:p-4">
                <div class="divide-y divide-gray-200">
                    <?php foreach ($announcements as $index => $announcement): ?>
                        <?php
                        // 日付を「●月○日」形式に変換
                        $date = new DateTime($announcement['announcement_date']);
                        $month = (int)$date->format('n');
                        $day = (int)$date->format('j');
                        $formattedDate = $month . '月' . $day . '日';
                        ?>
                        
                        <div class="py-4">
                            <div class="flex items-start gap-1.5">
                                <!-- 日付バッジ -->
                                <div class="flex-shrink-0">
                                    <div class="bg-blue-600 text-white rounded px-1.5 py-1 text-center">
                                    <div class="text-xs font-medium leading-tight whitespace-nowrap"><?php echo htmlspecialchars($formattedDate); ?></div>
                                    </div>
                                </div>
                                
                                <!-- コンテンツ -->
                                <div class="flex-1 min-w-0">
                                    <div class="text-gray-900 font-bold text-base md:text-lg mb-2">
                                        <?php echo htmlspecialchars($announcement['title']); ?>
                                    </div>
                                    
                                    <div class="text-gray-700 text-sm md:text-base mb-3">
                                        <?php echo nl2br(htmlspecialchars($announcement['content'])); ?>
                                    </div>
                                    
                                    <!-- リンク・PDF -->
                                    <div class="flex flex-wrap gap-2 mt-3">
                                        <?php if (!empty($announcement['external_url'])): ?>
                                            <a href="<?php echo htmlspecialchars($announcement['external_url']); ?>" 
                                               target="_blank" 
                                               rel="noopener noreferrer"
                                               class="inline-flex items-center px-3 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-md text-sm font-medium transition-colors">
                                                <i class="ri-external-link-line mr-1.5 text-xs"></i>
                                                詳細を見る
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($announcement['pdf_file_path'])): ?>
                                            <a href="<?php echo htmlspecialchars($announcement['pdf_file_path']); ?>" 
                                               target="_blank"
                                               class="inline-flex items-center px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md text-sm font-medium transition-colors">
                                                <i class="ri-file-pdf-line mr-1.5 text-xs"></i>
                                                PDFを表示
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

