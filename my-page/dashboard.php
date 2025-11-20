<?php
/**
 * My Page Dashboard
 * マイページ - ダッシュボード
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
$loginEmail = AuthHelper::getUserEmail();

// Supabaseから申込情報を取得
$supabase = new SupabaseClient(SUPABASE_URL, SUPABASE_SERVICE_KEY);

// ログインユーザーの表示名を格納する変数
$displayName = '';
$isStudent = false; // 生徒本人かどうか
$isGuardian = false; // 保護者かどうか
$isMember = false; // チームメンバーかどうか

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
        
        // ログインユーザーの判定
        if ($detail) {
            // メールアドレスを正規化して比較
            $normalizedLoginEmail = trim(strtolower($loginEmail));
            $normalizedStudentEmail = trim(strtolower($detail['student_email']));
            $normalizedGuardianEmail = trim(strtolower($detail['guardian_email']));
            
            if ($normalizedStudentEmail === $normalizedLoginEmail) {
                $displayName = $detail['student_name'];
                $isStudent = true;
            } elseif ($normalizedGuardianEmail === $normalizedLoginEmail) {
                $displayName = $detail['guardian_name'];
                $isGuardian = true;
            }
        }
        
    } else {
        $detailResult = $supabase->from('team_applications')
            ->select('*')
            ->eq('application_id', $userId)
            ->single();
        
        $detail = $detailResult['data'] ?? null;
        
        // チームメンバー情報も取得
        if ($detail) {
            $membersResult = $supabase->from('team_members')
                ->select('*')
                ->eq('team_application_id', $detail['id'])
                ->order('member_number', 'asc')
                ->execute();
            
            $teamMembers = $membersResult['data'] ?? [];
            
            // ログインユーザーの判定
            // メールアドレスを正規化して比較
            $normalizedLoginEmail = trim(strtolower($loginEmail));
            $normalizedGuardianEmail = trim(strtolower($detail['guardian_email']));
            
            if ($normalizedGuardianEmail === $normalizedLoginEmail) {
                $displayName = $detail['guardian_name'];
                $isGuardian = true;
            } else {
                // チームメンバーの中から該当者を探す
                foreach ($teamMembers as $member) {
                    $normalizedMemberEmail = trim(strtolower($member['member_email']));
                    if ($normalizedMemberEmail === $normalizedLoginEmail) {
                        $displayName = $member['member_name'];
                        $isMember = true;
                        break;
                    }
                }
            }
        }
    }
    
} catch (Exception $e) {
    $error = $e->getMessage();
}

// ステータスの日本語変換
function getStatusBadge($status) {
    $badges = [
        'submitted' => '<span class="inline-flex items-center bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-semibold"><i class="ri-time-line mr-1"></i>申込済み</span>',
        'card_pending' => '<span class="inline-flex items-center bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-sm font-semibold"><i class="ri-bank-card-line mr-1"></i>カード登録待ち</span>',
        'kyc_pending' => '<span class="inline-flex items-center bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-sm font-semibold"><i class="ri-alert-line mr-1"></i>本人確認待ち</span>',
        'charge_scheduled' => '<span class="inline-flex items-center bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-semibold"><i class="ri-calendar-check-line mr-1"></i>決済予約済み</span>',
        'payment_processing' => '<span class="inline-flex items-center bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-semibold"><i class="ri-loader-line mr-1"></i>決済処理中</span>',
        'confirmed' => '<span class="inline-flex items-center bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-semibold"><i class="ri-check-line mr-1"></i>確定</span>',
        'completed' => '<span class="inline-flex items-center bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-semibold"><i class="ri-check-line mr-1"></i>完了</span>',
        'cancelled' => '<span class="inline-flex items-center bg-gray-100 text-gray-800 px-3 py-1 rounded-full text-sm font-semibold"><i class="ri-close-line mr-1"></i>キャンセル</span>',
    ];
    return $badges[$status] ?? $status;
}

function getPaymentStatusBadge($status) {
    $badges = [
        'pending' => '<span class="inline-flex items-center bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-sm font-semibold"><i class="ri-time-line mr-1"></i>未払い</span>',
        'card_registered' => '<span class="inline-flex items-center bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-semibold"><i class="ri-bank-card-line mr-1"></i>カード登録済み</span>',
        'completed' => '<span class="inline-flex items-center bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-semibold"><i class="ri-check-line mr-1"></i>支払い完了</span>',
        'failed' => '<span class="inline-flex items-center bg-red-100 text-red-800 px-3 py-1 rounded-full text-sm font-semibold"><i class="ri-error-warning-line mr-1"></i>支払い失敗</span>',
    ];
    return $badges[$status] ?? $status;
}

function getKycStatusBadge($status) {
    $badges = [
        'pending' => '<span class="inline-flex items-center bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-sm font-semibold"><i class="ri-time-line mr-1"></i>未実施</span>',
        'in_progress' => '<span class="inline-flex items-center bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-semibold"><i class="ri-loader-line mr-1"></i>確認中</span>',
        'completed' => '<span class="inline-flex items-center bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-semibold"><i class="ri-check-line mr-1"></i>完了</span>',
        'rejected' => '<span class="inline-flex items-center bg-red-100 text-red-800 px-3 py-1 rounded-full text-sm font-semibold"><i class="ri-close-line mr-1"></i>却下</span>',
    ];
    return $badges[$status] ?? $status;
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
    
    <title>マイページ - UNIV.Cambridge Japan Academy</title>
    
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
        .border-brand-teal { border-color: #6BBBAE; }
        .border-brand-pink { border-color: #E5007D; }
        .border-brand-blue { border-color: #007bff; }
        
        /* グラデーション */
        .bg-gradient-teal-pink {
            background: linear-gradient(to right, #6BBBAE, #E5007D);
        }
        .bg-gradient-blue-teal {
            background: linear-gradient(to right, #007bff, #6BBBAE);
        }
        .hover-gradient-blue-teal:hover {
            background: linear-gradient(to right, #0056b3, #5aaa9e);
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

        <!-- ウェルカムメッセージ -->
        <div class="mb-8">
            <h2 class="text-3xl font-bold text-gray-900 mb-2">
                <?php if ($participationType === 'individual'): ?>
                    ようこそ、<?php echo htmlspecialchars($displayName); ?>さん
                <?php else: ?>
                    ようこそ、<?php echo htmlspecialchars($detail['team_name'] ?? ''); ?> - <?php echo htmlspecialchars($displayName); ?>さん
                <?php endif; ?>
            </h2>
            <p class="text-gray-600">
                <?php if ($isStudent || $isMember): ?>
                    あなたの申込状況と各種手続きの確認ができます
                <?php elseif ($isGuardian): ?>
                    <?php echo $participationType === 'individual' ? 'お子様の' : 'チームの'; ?>申込状況と各種手続きの確認ができます
                <?php else: ?>
                    申込状況と各種手続きの確認ができます
                <?php endif; ?>
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- 左側：ナビゲーション -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl shadow-lg p-6 sticky top-6">
                    <h3 class="font-bold text-gray-900 mb-4 flex items-center">
                        <i class="ri-menu-line text-blue-600 mr-2"></i>
                        メニュー
                    </h3>
                    <nav class="space-y-2">
                        <a href="dashboard.php" class="flex items-center px-4 py-3 bg-blue-50 text-blue-600 rounded-lg font-medium">
                            <i class="ri-dashboard-line mr-3"></i>
                            ダッシュボード
                        </a>
                        <a href="application-detail.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-lg transition-colors">
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
                
                <!-- 申込ステータスカード -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="bg-gradient-blue-teal px-6 py-4">
                        <h3 class="text-xl font-bold text-white flex items-center">
                            <i class="ri-file-list-3-line mr-2"></i>
                            申込ステータス
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-<?php echo $isGuardian ? '2' : '3'; ?> gap-4">
                            <div class="text-center">
                                <div class="text-gray-600 text-sm mb-2">申込状況</div>
                                <?php echo getStatusBadge($application['application_status'] ?? 'submitted'); ?>
                            </div>
                            <div class="text-center">
                                <div class="text-gray-600 text-sm mb-2">支払い状況</div>
                                <?php echo getPaymentStatusBadge($application['payment_status'] ?? 'pending'); ?>
                            </div>
                            <?php if (!$isGuardian): ?>
                            <div class="text-center">
                                <div class="text-gray-600 text-sm mb-2">本人確認状況</div>
                                <?php echo getKycStatusBadge($application['kyc_status'] ?? 'pending'); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- 試験情報カード -->
                <div class="bg-brand-blue rounded-xl shadow-lg p-6">
                    <h3 class="text-xl font-bold mb-4 flex items-center text-white">
                        <i class="ri-calendar-event-line mr-2"></i>
                        選手権日程
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-white rounded-lg p-4 shadow-sm text-brand-blue">
                            <i class="ri-calendar-line text-3xl mb-2 text-brand-blue"></i>
                            <div class="text-sm mb-1 font-medium text-brand-blue">実施日</div>
                            <div class="font-bold text-lg text-brand-blue">2026年1月4日(日)</div>
                        </div>
                        <div class="bg-white rounded-lg p-4 shadow-sm text-brand-blue">
                            <i class="ri-time-line text-3xl mb-2 text-brand-blue"></i>
                            <div class="text-sm mb-1 font-medium text-brand-blue">開始時刻</div>
                            <div class="font-bold text-lg text-brand-blue">10:00</div>
                        </div>
                        <div class="bg-white rounded-lg p-4 shadow-sm text-brand-blue">
                            <i class="ri-timer-line text-3xl mb-2 text-brand-blue"></i>
                            <div class="text-sm mb-1 font-medium text-brand-blue">実施時間</div>
                            <div class="font-bold text-lg text-brand-blue">60分</div>
                        </div>
                    </div>
                </div>

                <!-- 必要なアクション -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                        <i class="ri-todo-line text-orange-600 mr-2"></i>
                        必要なアクション
                    </h3>
                    
                    <?php if (!$isGuardian && $application['kyc_status'] === 'pending'): ?>
                    <div class="mb-4 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <div class="flex items-start justify-between">
                            <div class="flex items-start">
                                <i class="ri-alert-line text-yellow-600 text-xl mr-3 mt-0.5"></i>
                                <div>
                                    <div class="font-semibold text-gray-900">本人確認が必要です</div>
                                    <div class="text-sm text-gray-700 mt-1">学生証による本人確認を実施してください</div>
                                </div>
                            </div>
                            <a
                                href="kyc-status.php"
                                class="ml-4 bg-yellow-600 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-yellow-700 transition-colors whitespace-nowrap"
                            >
                                確認開始
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($application['payment_status'] === 'pending'): ?>
                    <div class="mb-4 bg-orange-50 border border-orange-200 rounded-lg p-4">
                        <div class="flex items-start justify-between">
                            <div class="flex items-start">
                                <i class="ri-bank-card-line text-orange-600 text-xl mr-3 mt-0.5"></i>
                                <div>
                                    <div class="font-semibold text-gray-900">支払いが必要です</div>
                                    <div class="text-sm text-gray-700 mt-1">参加費のお支払い手続きを完了してください</div>
                                </div>
                            </div>
                            <a
                                href="payment-status.php"
                                class="ml-4 bg-orange-600 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-orange-700 transition-colors whitespace-nowrap"
                            >
                                支払う
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($application['application_status'] === 'charge_scheduled'): ?>
                    <!-- 決済予約済み（バッチ処理待ち） -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div class="flex items-center text-blue-800">
                            <i class="ri-calendar-check-line text-2xl mr-3"></i>
                            <div>
                                <div class="font-semibold">すべての手続きが完了しています</div>
                                <div class="text-sm mt-1">決済処理は自動的に実行されます。決済完了後、メールでご連絡いたします。</div>
                                <div class="text-sm mt-1 font-medium">試験開始をお待ちください。</div>
                            </div>
                        </div>
                    </div>
                    <?php elseif ($application['kyc_status'] === 'completed' && $application['payment_status'] === 'completed'): ?>
                    <!-- すべて完了 -->
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <div class="flex items-center text-green-800">
                            <i class="ri-check-double-line text-2xl mr-3"></i>
                            <div>
                                <div class="font-semibold">すべての手続きが完了しています</div>
                                <div class="text-sm mt-1">試験当日をお楽しみに！</div>
                            </div>
                        </div>
                    </div>
                    <?php elseif (
                        $application['kyc_status'] === 'completed' && 
                        $application['card_registered'] === true && 
                        !in_array($application['payment_status'], ['completed', 'failed'])
                    ): ?>
                    <!-- その他の処理中状態 -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div class="flex items-center text-blue-800">
                            <i class="ri-time-line text-2xl mr-3"></i>
                            <div>
                                <div class="font-semibold">決済処理中です</div>
                                <div class="text-sm mt-1">決済処理が完了するまでお待ちください。</div>
                                <div class="text-sm mt-1 font-medium">試験開始をお待ちください。</div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- クイックリンク -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                        <i class="ri-links-line text-blue-600 mr-2"></i>
                        クイックリンク
                    </h3>
                    <div class="grid grid-cols-2 gap-4">
                        <a
                            href="application-detail.php"
                            class="flex items-center justify-center bg-blue-50 text-blue-600 py-4 rounded-lg font-semibold hover:bg-blue-100 transition-colors"
                        >
                            <i class="ri-file-text-line mr-2"></i>
                            申込詳細を見る
                        </a>
                        <?php if ($participationType === 'team'): ?>
                        <a
                            href="team-status.php"
                            class="flex items-center justify-center bg-pink-50 text-pink-600 py-4 rounded-lg font-semibold hover:bg-pink-100 transition-colors"
                        >
                            <i class="ri-team-line mr-2"></i>
                            チーム管理
                        </a>
                        <?php endif; ?>
                        <a
                            href="payment-status.php"
                            class="flex items-center justify-center bg-green-50 text-green-600 py-4 rounded-lg font-semibold hover:bg-green-100 transition-colors"
                        >
                            <i class="ri-bank-card-line mr-2"></i>
                            支払い状況
                        </a>
                        <?php if (!$isGuardian): ?>
                        <a
                            href="kyc-status.php"
                            class="flex items-center justify-center bg-purple-50 text-purple-600 py-4 rounded-lg font-semibold hover:bg-purple-100 transition-colors"
                        >
                            <i class="ri-shield-check-line mr-2"></i>
                            本人確認
                        </a>
                        <?php endif; ?>
                        <a
                            href="profile.php"
                            class="flex items-center justify-center bg-orange-50 text-orange-600 py-4 rounded-lg font-semibold hover:bg-orange-100 transition-colors"
                        >
                            <i class="ri-user-settings-line mr-2"></i>
                            プロフィール
                        </a>
                    </div>
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

