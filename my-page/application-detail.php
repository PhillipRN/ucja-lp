<?php
/**
 * Application Detail Page
 * 申込詳細画面
 */

require_once __DIR__ . '/../lib/AuthHelper.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../lib/SupabaseClient.php';

// ログインチェック
AuthHelper::requireLogin();

// ユーザー情報取得
$userId = AuthHelper::getUserId();
$applicationNumber = AuthHelper::getApplicationNumber();
$participationType = AuthHelper::getParticipationType();
$loginEmail = trim(strtolower(AuthHelper::getUserEmail() ?? ''));

// Supabaseから申込情報を取得
$supabase = new SupabaseClient(SUPABASE_URL, SUPABASE_SERVICE_KEY);

// 保護者かどうかを判定する変数
$isGuardian = false;

try {
    // 申込情報取得
    $applicationResult = $supabase->from('applications')
        ->select('*')
        ->eq('id', $userId)
        ->single();
    
    if (!$applicationResult['success'] || empty($applicationResult['data'])) {
        throw new Exception('申込情報の取得に失敗しました');
    }
    
    $application = $applicationResult['data'];
    
    // 個人戦またはチーム戦の詳細情報取得
    if ($participationType === 'individual') {
        $detailResult = $supabase->from('individual_applications')
            ->select('*')
            ->eq('application_id', $userId)
            ->single();
        
        $detail = $detailResult['data'] ?? null;
        
        // 個人戦: 保護者メールとログインメールを比較
        if ($detail) {
            $guardianEmail = trim(strtolower($detail['guardian_email'] ?? ''));
            $isGuardian = ($loginEmail === $guardianEmail);
        }
    } else {
        $detailResult = $supabase->from('team_applications')
            ->select('*')
            ->eq('application_id', $userId)
            ->single();
        
        $detail = $detailResult['data'] ?? null;
        
        // チーム戦: 保護者メールとログインメールを比較
        if ($detail) {
            $guardianEmail = trim(strtolower($detail['guardian_email'] ?? ''));
            $isGuardian = ($loginEmail === $guardianEmail);
        }
        
        // チームメンバー情報も取得
        if ($detail) {
            $membersResult = $supabase->from('team_members')
                ->select('*')
                ->eq('team_application_id', $detail['id'])
                ->order('member_number', 'asc')
                ->execute();
            
            $teamMembers = $membersResult['data'] ?? [];
        }
    }
    
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- 検索エンジンインデックス防止（Stripe審査中） -->
    <meta name="robots" content="noindex, nofollow">
    <meta name="googlebot" content="noindex, nofollow">
    
    <title>申込詳細 - マイページ</title>
    
    <!-- Remix Icon CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.5.0/remixicon.min.css">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        /* カスタムブランドカラー */
        .bg-brand-teal { background-color: #6BBBAE; }
        .bg-brand-pink { background-color: #E5007D; }
        .bg-brand-blue { background-color: #007bff; }
        .text-brand-teal { color: #6BBBAE; }
        .text-brand-pink { color: #E5007D; }
        .text-brand-blue { color: #007bff; }
        
        /* グラデーション */
        .bg-gradient-teal-pink {
            background: linear-gradient(to right, #6BBBAE, #E5007D);
        }
        .bg-gradient-blue-teal {
            background: linear-gradient(to right, #007bff, #6BBBAE);
        }
        .bg-gradient-blue-teal-radial {
            background: radial-gradient(circle at top right, #007bff, #6BBBAE);
        }
    </style>
</head>
<body class="antialiased bg-gray-50">

    <!-- Header -->
    <header class="bg-white shadow-sm border-b border-gray-200">
        <div class="container mx-auto px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <a href="../index.php">
                        <img src="../images/cambridge-logo.png" alt="Cambridge Logo" class="h-10">
                    </a>
                    <div class="border-l border-gray-300 pl-4">
                        <h1 class="text-xl font-bold text-gray-900">マイページ</h1>
                        <p class="text-sm text-gray-600">申込番号: <?php echo htmlspecialchars($applicationNumber); ?></p>
                    </div>
                </div>
                <a
                    href="../logout.php"
                    class="inline-flex items-center text-gray-600 hover:text-gray-900 transition-colors"
                >
                    <i class="ri-logout-box-line mr-2"></i>
                    ログアウト
                </a>
            </div>
        </div>
    </header>

    <main class="container mx-auto px-6 py-8">
        
        <?php if (isset($error)): ?>
        <!-- エラー表示 -->
        <div class="mb-6 bg-red-50 border border-red-200 rounded-xl p-6">
            <div class="flex items-center text-red-800">
                <i class="ri-error-warning-line text-2xl mr-3"></i>
                <div>
                    <div class="font-semibold">エラーが発生しました</div>
                    <div class="text-sm mt-1"><?php echo htmlspecialchars($error); ?></div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- 左側：ナビゲーション -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl shadow-lg p-6 sticky top-6">
                    <h3 class="font-bold text-gray-900 mb-4 flex items-center">
                        <i class="ri-menu-line text-blue-600 mr-2"></i>
                        メニュー
                    </h3>
                    <nav class="space-y-2">
                        <a href="dashboard.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-lg transition-colors">
                            <i class="ri-dashboard-line mr-3"></i>
                            ダッシュボード
                        </a>
                        <a href="application-detail.php" class="flex items-center px-4 py-3 bg-blue-50 text-blue-600 rounded-lg font-medium">
                            <i class="ri-file-text-line mr-3"></i>
                            申込詳細
                        </a>
                        <?php if ($participationType === 'team'): ?>
                        <a href="team-status.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-lg transition-colors">
                            <i class="ri-team-line mr-3"></i>
                            チーム管理
                        </a>
                        <?php endif; ?>
                        <a href="payment-status.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-lg transition-colors">
                            <i class="ri-bank-card-line mr-3"></i>
                            支払い状況
                        </a>
                        <?php if (!$isGuardian): ?>
                        <a href="kyc-status.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-lg transition-colors">
                            <i class="ri-shield-check-line mr-3"></i>
                            本人確認状況
                        </a>
                        <?php endif; ?>
                        <a href="profile.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-lg transition-colors">
                            <i class="ri-user-settings-line mr-3"></i>
                            プロフィール
                        </a>
                    </nav>

                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <a href="../index.php" class="flex items-center text-gray-600 hover:text-gray-900 transition-colors text-sm">
                            <i class="ri-arrow-left-line mr-2"></i>
                            トップページに戻る
                        </a>
                    </div>
                </div>
            </div>

            <!-- 右側：メインコンテンツ -->
            <div class="lg:col-span-2 space-y-6">
                
                <!-- ページタイトル -->
                <div>
                    <h2 class="text-3xl font-bold text-gray-900 mb-2">申込詳細</h2>
                    <p class="text-gray-600">申込内容の確認</p>
                </div>

                <!-- 申込基本情報 -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="bg-gradient-blue-teal px-6 py-4">
                        <h3 class="text-xl font-bold text-white flex items-center">
                            <i class="ri-information-line mr-2"></i>
                            申込基本情報
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <div class="text-sm text-gray-600 mb-1">申込番号</div>
                                <div class="font-semibold text-gray-900 font-mono"><?php echo htmlspecialchars($application['application_number'] ?? ''); ?></div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-600 mb-1">申込日時</div>
                                <div class="font-semibold text-gray-900"><?php echo date('Y年m月d日 H:i', strtotime($application['submitted_at'] ?? 'now')); ?></div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-600 mb-1">参加形式</div>
                                <div class="font-semibold text-gray-900">
                                    <?php echo $participationType === 'individual' ? '個人戦' : 'チーム戦'; ?>
                                </div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-600 mb-1">料金プラン</div>
                                <div class="font-semibold text-gray-900"><?php echo htmlspecialchars($application['pricing_type'] ?? ''); ?></div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-600 mb-1">参加費</div>
                                <div class="font-bold text-2xl text-blue-600">¥<?php echo number_format($application['amount'] ?? 0); ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($participationType === 'individual'): ?>
                <!-- 個人戦：生徒情報 -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="bg-gradient-to-r from-green-600 to-emerald-600 px-6 py-4">
                        <h3 class="text-xl font-bold text-white flex items-center">
                            <i class="ri-user-line mr-2"></i>
                            生徒情報
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <div class="text-sm text-gray-600 mb-1">生徒氏名</div>
                                <div class="font-semibold text-gray-900"><?php echo htmlspecialchars($detail['student_name'] ?? ''); ?></div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-600 mb-1">学校名</div>
                                <div class="font-semibold text-gray-900"><?php echo htmlspecialchars($detail['school'] ?? ''); ?></div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-600 mb-1">学年</div>
                                <div class="font-semibold text-gray-900"><?php echo htmlspecialchars($detail['grade'] ?? ''); ?></div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-600 mb-1">生徒メールアドレス</div>
                                <div class="font-semibold text-gray-900"><?php echo htmlspecialchars($detail['student_email'] ?? ''); ?></div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-600 mb-1">生徒電話番号</div>
                                <div class="font-semibold text-gray-900"><?php echo htmlspecialchars($detail['student_phone'] ?? ''); ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 個人戦：保護者情報 -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="bg-gradient-to-r from-orange-600 to-red-600 px-6 py-4">
                        <h3 class="text-xl font-bold text-white flex items-center">
                            <i class="ri-parent-line mr-2"></i>
                            保護者情報
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <div class="text-sm text-gray-600 mb-1">保護者氏名</div>
                                <div class="font-semibold text-gray-900"><?php echo htmlspecialchars($detail['guardian_name'] ?? ''); ?></div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-600 mb-1">保護者メールアドレス</div>
                                <div class="font-semibold text-gray-900"><?php echo htmlspecialchars($detail['guardian_email'] ?? ''); ?></div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-600 mb-1">保護者電話番号</div>
                                <div class="font-semibold text-gray-900"><?php echo htmlspecialchars($detail['guardian_phone'] ?? ''); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <!-- チーム戦：チーム情報 -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="bg-brand-pink px-6 py-4">
                        <h3 class="text-xl font-bold text-white flex items-center">
                            <i class="ri-team-line mr-2"></i>
                            チーム情報
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <div class="text-sm text-gray-600 mb-1">チーム名</div>
                                <div class="font-bold text-xl text-gray-900"><?php echo htmlspecialchars($detail['team_name'] ?? ''); ?></div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-600 mb-1">学校名</div>
                                <div class="font-semibold text-gray-900"><?php echo htmlspecialchars($detail['school'] ?? ''); ?></div>
                            </div>
                        </div>

                        <!-- チームメンバー一覧 -->
                        <div class="border-t border-gray-200 pt-6">
                            <h4 class="font-bold text-gray-900 mb-4 flex items-center">
                                <i class="ri-group-line mr-2"></i>
                                チームメンバー（5名）
                            </h4>
                            <div class="space-y-3">
                                <?php if (!empty($teamMembers)): ?>
                                    <?php foreach ($teamMembers as $member): ?>
                                    <div class="flex items-center justify-between bg-gray-50 rounded-lg p-4">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 bg-gradient-blue-teal-radial rounded-full flex items-center justify-center text-white font-bold mr-4">
                                                <?php echo $member['member_number']; ?>
                                            </div>
                                            <div>
                                                <div class="font-semibold text-gray-900">
                                                    <?php echo htmlspecialchars($member['member_name'] ?? ''); ?>
                                                    <?php if ($member['is_representative']): ?>
                                                    <span class="ml-2 inline-flex items-center bg-blue-100 text-blue-800 px-2 py-0.5 rounded text-xs font-medium">
                                                        代表
                                                    </span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-sm text-gray-600"><?php echo htmlspecialchars($member['member_email'] ?? ''); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-gray-600">メンバー情報が見つかりません</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- チーム戦：代表者（保護者）情報 -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="bg-gradient-to-r from-orange-600 to-red-600 px-6 py-4">
                        <h3 class="text-xl font-bold text-white flex items-center">
                            <i class="ri-parent-line mr-2"></i>
                            代表者（保護者）情報
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <div class="text-sm text-gray-600 mb-1">保護者氏名</div>
                                <div class="font-semibold text-gray-900"><?php echo htmlspecialchars($detail['guardian_name'] ?? ''); ?></div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-600 mb-1">保護者メールアドレス</div>
                                <div class="font-semibold text-gray-900"><?php echo htmlspecialchars($detail['guardian_email'] ?? ''); ?></div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-600 mb-1">保護者電話番号</div>
                                <div class="font-semibold text-gray-900"><?php echo htmlspecialchars($detail['guardian_phone'] ?? ''); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- 特記事項 -->
                <?php if (!empty($application['special_requests'])): ?>
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="bg-gradient-to-r from-gray-600 to-gray-700 px-6 py-4">
                        <h3 class="text-xl font-bold text-white flex items-center">
                            <i class="ri-chat-3-line mr-2"></i>
                            特記事項・ご質問
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="text-gray-900 whitespace-pre-wrap"><?php echo htmlspecialchars($application['special_requests']); ?></div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- 戻るボタン -->
                <div class="flex justify-end">
                    <a
                        href="dashboard.php"
                        class="inline-flex items-center bg-gray-200 text-gray-700 px-6 py-3 rounded-lg font-semibold hover:bg-gray-300 transition-colors"
                    >
                        <i class="ri-arrow-left-line mr-2"></i>
                        ダッシュボードに戻る
                    </a>
                </div>

            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 mt-12">
        <div class="container mx-auto px-6 py-8">
            <div class="text-center text-sm text-gray-600">
                <p>お問い合わせ: <a href="mailto:contact@univ-cambridge-japan.academy" class="text-blue-600 hover:underline">contact@univ-cambridge-japan.academy</a></p>
                <p class="mt-2">© 2025 UNIVERSITY CAMBRIDGE JAPAN CONSULTING SUPERVISOR Co., Ltd. All rights reserved.</p>
            </div>
        </div>
    </footer>

</body>
</html>

