<?php
/**
 * Payment Status Page
 * 支払い状況画面
 */

require_once __DIR__ . '/../lib/AuthHelper.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../lib/SupabaseClient.php';

function formatDateTimeLabel(?string $value): ?string {
    if (empty($value)) {
        return null;
    }
    $timestamp = strtotime($value);
    if (!$timestamp) {
        return null;
    }
    return date('Y年m月d日 H:i', $timestamp);
}

// ログインチェック
AuthHelper::requireLogin();

// ユーザー情報取得
$userId = AuthHelper::getUserId();
$applicationNumber = AuthHelper::getApplicationNumber();
$participationType = AuthHelper::getParticipationType();
$loginEmail = trim(strtolower(AuthHelper::getUserEmail() ?? ''));
$teamMemberSessionId = AuthHelper::getTeamMemberId();

// Supabaseから申込情報を取得
$supabase = new SupabaseClient(SUPABASE_URL, SUPABASE_SERVICE_KEY);

// 保護者かどうかを判定する変数
$isGuardian = false;
$teamApplicationRecord = null;
$teamMemberData = null;

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
    
    // 保護者判定のため、詳細情報を取得
    if ($participationType === 'individual') {
        $detailResult = $supabase->from('individual_applications')
            ->select('guardian_email')
            ->eq('application_id', $userId)
            ->single();
        
        if ($detailResult['success'] && !empty($detailResult['data'])) {
            $guardianEmail = trim(strtolower($detailResult['data']['guardian_email'] ?? ''));
            $isGuardian = ($loginEmail === $guardianEmail);
        }
    } else {
        $detailResult = $supabase->from('team_applications')
            ->select('id, guardian_email, team_name')
            ->eq('application_id', $userId)
            ->single();
        
        if ($detailResult['success'] && !empty($detailResult['data'])) {
            $teamApplicationRecord = $detailResult['data'];
            $guardianEmail = trim(strtolower($teamApplicationRecord['guardian_email'] ?? ''));
            $isGuardian = ($loginEmail === $guardianEmail);
        }
    }
    
    if ($teamMemberSessionId && $participationType === 'team' && !empty($teamApplicationRecord)) {
        $memberResult = $supabase->from('team_members')
            ->select('*')
            ->eq('id', $teamMemberSessionId)
            ->single();

        if ($memberResult['success'] && !empty($memberResult['data'])) {
            $member = $memberResult['data'];
            if (($member['team_application_id'] ?? null) === ($teamApplicationRecord['id'] ?? null)) {
                $teamMemberData = $member;
            }
        }
    }

} catch (Exception $e) {
    $error = $e->getMessage();
    $application = [];
}

// 支払いステータスに応じた表示
$paymentStatus = $application['payment_status'] ?? 'pending';
$cardRegistered = $application['card_registered'] ?? false;
$kycStatus = $application['kyc_status'] ?? 'pending';
$isKycCompleted = in_array($kycStatus, ['approved', 'completed'], true);
$kycAvailable = function_exists('isKycAvailable') ? isKycAvailable() : true;
$kycAvailableDateLabel = function_exists('getKycAvailableDateLabel') ? getKycAvailableDateLabel() : null;
$isProductionEnv = (APP_ENV === 'production');
$applicationChargedAtLabel = formatDateTimeLabel($application['charged_at'] ?? null);
$applicationCardRegisteredAtLabel = formatDateTimeLabel($application['card_registered_at'] ?? null);
$supportEmail = 'contact@univ-cambridge-japan.academy';

$kycStatusLabels = [
    'pending' => '未実施',
    'in_progress' => '確認中',
    'completed' => '完了',
    'approved' => '完了',
    'failed' => '失敗',
    'rejected' => '却下'
];
$kycStatusLabel = $kycStatusLabels[$kycStatus] ?? '未実施';

$isTeamMemberView = ($participationType === 'team' && !empty($teamMemberData));
$memberPaymentStatus = $isTeamMemberView ? ($teamMemberData['payment_status'] ?? 'pending') : null;
$memberCardRegistered = $isTeamMemberView ? (bool)($teamMemberData['card_registered'] ?? false) : false;
$memberKycStatus = $isTeamMemberView ? ($teamMemberData['kyc_status'] ?? 'pending') : 'pending';
$memberKycLabel = $kycStatusLabels[$memberKycStatus] ?? '未実施';
$memberChargedAtLabel = $isTeamMemberView ? formatDateTimeLabel($teamMemberData['charged_at'] ?? null) : null;
$memberCardRegisteredAtLabel = $isTeamMemberView ? formatDateTimeLabel($teamMemberData['card_registered_at'] ?? null) : null;
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- 検索エンジンインデックス防止（Stripe審査中） -->
    <meta name="robots" content="noindex, nofollow">
    <meta name="googlebot" content="noindex, nofollow">
    
    <title>支払い状況 - マイページ</title>
    
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
                        <a href="payment-status.php" class="flex items-center px-4 py-3 bg-blue-50 text-blue-600 rounded-lg font-medium">
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
                    <h2 class="text-3xl font-bold text-gray-900 mb-2">支払い状況</h2>
                    <p class="text-gray-600">参加費のお支払い状況を確認できます</p>
                    <?php if ($isProductionEnv && !$kycAvailable && !$isKycCompleted): ?>
                    <div class="mt-4 bg-yellow-50 border border-yellow-200 text-yellow-900 rounded-lg px-4 py-3 text-sm">
                        本人確認は<?php echo htmlspecialchars($kycAvailableDateLabel ?? '近日中'); ?>より開始予定です。準備が整うまでお待ちください。
                    </div>
                    <?php endif; ?>
                </div>

                <?php if ($isTeamMemberView && $teamMemberData): ?>
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4">
                        <h3 class="text-xl font-bold text-white flex items-center">
                            <i class="ri-user-star-line mr-2"></i>
                            あなた（メンバー<?php echo htmlspecialchars($teamMemberData['member_number']); ?>）の支払い状況
                        </h3>
                        <p class="text-indigo-100 text-sm mt-1">
                            <?php echo htmlspecialchars($teamMemberData['member_name'] ?? ''); ?> / <?php echo htmlspecialchars($teamApplicationRecord['team_name'] ?? ''); ?>
                        </p>
                    </div>
                    <div class="p-6 space-y-6">
                        <?php if ($memberPaymentStatus === 'completed'): ?>
                        <div class="bg-green-50 border border-green-200 rounded-xl p-5">
                            <div class="flex items-center mb-2 text-green-700">
                                <i class="ri-check-line text-2xl mr-3"></i>
                                <div>
                                    <div class="text-lg font-bold">決済が完了しました</div>
                                    <?php if ($memberChargedAtLabel): ?>
                                    <div class="text-sm text-green-800 mt-1">決済日時: <?php echo $memberChargedAtLabel; ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <p class="text-sm text-green-900">
                                このまま試験本番までご案内メールをお待ちください。
                            </p>
                        </div>
                        <?php elseif ($memberPaymentStatus === 'failed'): ?>
                        <div class="bg-red-50 border border-red-200 rounded-xl p-5">
                            <div class="flex items-center mb-3 text-red-700">
                                <i class="ri-error-warning-line text-2xl mr-3"></i>
                                <div>
                                    <div class="text-lg font-bold">決済に失敗しました</div>
                                    <p class="text-sm text-red-800 mt-1">カード情報を再登録のうえ、別のカードでお試しください。</p>
                                </div>
                            </div>
                            <a
                                href="../stripe-checkout-setup.php"
                                class="inline-flex items-center bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg font-semibold transition-colors shadow-lg"
                            >
                                <i class="ri-refresh-line mr-2"></i>
                                カード情報を再登録する
                            </a>
                            <p class="text-xs text-red-800 mt-3">
                                <i class="ri-mail-line mr-1"></i>
                                解決しない場合は <?php echo htmlspecialchars($supportEmail); ?> までお問い合わせください。
                            </p>
                        </div>
                        <?php elseif ($memberPaymentStatus === 'processing'): ?>
                        <div class="bg-indigo-50 border border-indigo-200 rounded-xl p-5">
                            <div class="flex items-center mb-2 text-indigo-700">
                                <i class="ri-time-line text-2xl mr-3"></i>
                                <div>
                                    <div class="text-lg font-bold">決済処理中です</div>
                                    <p class="text-sm text-indigo-900 mt-1">数分後に自動で反映されます。しばらくお待ちください。</p>
                                </div>
                            </div>
                        </div>
                        <?php elseif ($memberCardRegistered): ?>
                        <div class="bg-blue-50 border border-blue-200 rounded-xl p-5">
                            <div class="flex items-center mb-2 text-blue-700">
                                <i class="ri-information-line text-2xl mr-3"></i>
                                <div>
                                    <div class="text-lg font-bold">カード登録済み</div>
                                    <p class="text-sm text-blue-900 mt-1">本人確認が完了すると自動的に決済されます。</p>
                                </div>
                            </div>
                            <?php if ($memberCardRegisteredAtLabel): ?>
                            <p class="text-sm text-blue-800">登録日時: <?php echo $memberCardRegisteredAtLabel; ?></p>
                            <?php endif; ?>
                        </div>
                        <?php else: ?>
                        <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-5">
                            <div class="flex items-center mb-3 text-yellow-700">
                                <i class="ri-bank-card-line text-2xl mr-3"></i>
                                <div>
                                    <div class="text-lg font-bold">カード登録が必要です</div>
                                    <p class="text-sm text-yellow-900 mt-1">以下のボタンからカード情報を登録してください。</p>
                                </div>
                            </div>
                            <a
                                href="../stripe-checkout-setup.php"
                                class="inline-flex items-center bg-gradient-blue-teal text-white px-6 py-3 rounded-lg font-semibold transition-all shadow-lg hover:shadow-xl"
                            >
                                <i class="ri-bank-card-line mr-2"></i>
                                カード情報を登録する
                            </a>
                            <?php if (!in_array($memberKycStatus, ['approved', 'completed'], true)): ?>
                            <div class="bg-white border border-yellow-200 rounded-lg p-4 mt-4 text-sm text-yellow-800">
                                <i class="ri-shield-check-line mr-2"></i>
                                本人確認（eKYC）も忘れずに完了させてください。
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                                <div class="text-xs text-gray-500 mb-1">本人確認ステータス</div>
                                <div class="text-base font-semibold text-gray-900"><?php echo htmlspecialchars($memberKycLabel); ?></div>
                            </div>
                            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                                <div class="text-xs text-gray-500 mb-1">登録カード</div>
                                <?php if (!empty($teamMemberData['card_last4']) && !empty($teamMemberData['card_brand'])): ?>
                                    <div class="text-sm font-semibold text-gray-900">
                                        <?php echo htmlspecialchars($teamMemberData['card_brand']); ?> •••• <?php echo htmlspecialchars($teamMemberData['card_last4']); ?>
                                    </div>
                                    <?php if ($memberCardRegisteredAtLabel): ?>
                                    <div class="text-xs text-gray-500 mt-1">登録日: <?php echo $memberCardRegisteredAtLabel; ?></div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="text-sm text-gray-500">未登録</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- 支払いステータスカード -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="bg-gradient-to-r from-green-600 to-emerald-600 px-6 py-4">
                        <h3 class="text-xl font-bold text-white flex items-center">
                            <i class="ri-bank-card-line mr-2"></i>
                            支払いステータス
                        </h3>
                    </div>
                    <div class="p-6">
                        <?php if ($paymentStatus === 'completed'): ?>
                        <!-- 支払い完了 -->
                        <div class="bg-green-50 border border-green-200 rounded-xl p-6 text-center">
                            <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="ri-check-line text-green-600 text-4xl"></i>
                            </div>
                            <h4 class="text-2xl font-bold text-gray-900 mb-2">支払い完了</h4>
                            <p class="text-gray-700">参加費のお支払いが完了しています</p>
                            <?php if ($applicationChargedAtLabel): ?>
                            <p class="text-sm text-gray-600 mt-2">決済日時: <?php echo $applicationChargedAtLabel; ?></p>
                            <?php endif; ?>
                        </div>

                        <?php elseif ($paymentStatus === 'failed'): ?>
                        <div class="bg-red-50 border border-red-200 rounded-xl p-6 text-center">
                            <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="ri-close-line text-red-600 text-4xl"></i>
                            </div>
                            <h4 class="text-2xl font-bold text-gray-900 mb-2">決済に失敗しました</h4>
                            <p class="text-gray-700 mb-4">カード情報を再登録し、別のカードでお試しください。</p>
                            <a
                                href="../stripe-checkout-setup.php"
                                class="inline-flex items-center bg-red-600 hover:bg-red-700 text-white px-8 py-4 rounded-lg font-semibold text-lg transition-all shadow-lg hover:shadow-xl"
                            >
                                <i class="ri-refresh-line mr-2"></i>
                                カード情報を再登録する
                            </a>
                            <p class="text-sm text-red-700 mt-4">
                                <i class="ri-mail-line mr-1"></i>
                                解決しない場合は <?php echo htmlspecialchars($supportEmail); ?> までご連絡ください。
                            </p>
                        </div>

                        <?php elseif ($paymentStatus === 'processing'): ?>
                        <div class="bg-indigo-50 border border-indigo-200 rounded-xl p-6 text-center">
                            <div class="w-20 h-20 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="ri-loader-4-line text-indigo-600 text-4xl animate-spin"></i>
                            </div>
                            <h4 class="text-2xl font-bold text-gray-900 mb-2">決済処理中です</h4>
                            <p class="text-gray-700">数分後にステータスが更新されます。反映までしばらくお待ちください。</p>
                        </div>

                        <?php elseif ($cardRegistered): ?>
                        <!-- カード登録済み・課金待ち -->
                        <div class="bg-blue-50 border border-blue-200 rounded-xl p-6 text-center">
                            <div class="w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="ri-time-line text-blue-600 text-4xl"></i>
                            </div>
                            <h4 class="text-2xl font-bold text-gray-900 mb-2">カード登録済み</h4>
                            <p class="text-gray-700 mb-4">本人確認完了後、自動的に決済が実行されます</p>
                            <?php if (!empty($application['card_last4']) && !empty($application['card_brand'])): ?>
                            <div class="bg-white rounded-lg p-4 inline-block">
                                <div class="text-sm text-gray-600 mb-1">登録カード</div>
                                <div class="font-semibold text-gray-900">
                                    <?php echo htmlspecialchars($application['card_brand']); ?> •••• <?php echo htmlspecialchars($application['card_last4']); ?>
                                </div>
                                <?php if ($applicationCardRegisteredAtLabel): ?>
                                <div class="text-xs text-gray-500 mt-1">登録日: <?php echo $applicationCardRegisteredAtLabel; ?></div>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>

                        <?php else: ?>
                        <!-- 未払い -->
                        <?php
                        if ($isKycCompleted) {
                            $checkoutUrl = '../stripe-checkout-payment.php';
                            $buttonText = '今すぐ支払う';
                            $buttonIcon = 'ri-flashlight-line';
                            $description = '参加費をお支払いいただくと、すぐに受験資格が確定します';
                            $noteText = '決済は即座に実行されます';
                        } else {
                            $checkoutUrl = '../stripe-checkout-setup.php';
                            $buttonText = 'カード情報を登録する';
                            $buttonIcon = 'ri-bank-card-line';
                            $description = '参加費のお支払いのため、クレジットカード情報を登録してください';
                            $noteText = '本人確認完了後に自動的に決済されます';
                        }
                        ?>

                        <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6 text-center">
                            <div class="w-20 h-20 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="ri-alert-line text-yellow-600 text-4xl"></i>
                            </div>
                            <h4 class="text-2xl font-bold text-gray-900 mb-2">
                                <?php echo $isKycCompleted ? 'お支払いが必要です' : 'カード登録が必要です'; ?>
                            </h4>
                            <p class="text-gray-700 mb-6"><?php echo $description; ?></p>
                            
                        <?php if (!$isKycCompleted): ?>
                            <?php if ($isProductionEnv && !$kycAvailable): ?>
                            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6 text-left">
                                <div class="flex items-start">
                                    <i class="ri-time-line text-gray-600 text-xl mr-3 mt-0.5"></i>
                                    <div class="text-sm text-gray-700">
                                        <p class="font-semibold mb-1">本人確認は<?php echo htmlspecialchars($kycAvailableDateLabel ?? '近日中'); ?>開始予定です</p>
                                        <p>カード登録のみ先に完了させていただき、本人確認は開始日以降に改めてご案内いたします。</p>
                                    </div>
                                </div>
                            </div>
                            <?php else: ?>
                            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6 text-left">
                                <div class="flex items-start">
                                    <i class="ri-information-line text-red-600 text-xl mr-3 mt-0.5"></i>
                                    <div class="text-sm text-red-800">
                                        <p class="font-semibold mb-1">本人確認が完了していません</p>
                                        <p>カード情報を登録後、本人確認（eKYC）が完了した時点で自動的に決済が実行されます。</p>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        <?php endif; ?>
                            
                            <a
                                href="<?php echo $checkoutUrl; ?>"
                                class="inline-flex items-center bg-gradient-blue-teal hover-gradient-blue-teal text-white px-8 py-4 rounded-lg font-semibold text-lg transition-all shadow-lg hover:shadow-xl"
                            >
                                <i class="<?php echo $buttonIcon; ?> mr-2"></i>
                                <?php echo $buttonText; ?>
                            </a>

                            <p class="text-sm text-gray-600 mt-4">
                                <i class="ri-information-line mr-1"></i>
                                <?php echo $noteText; ?>
                            </p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 料金詳細 -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="bg-brand-pink px-6 py-4">
                        <h3 class="text-xl font-bold text-white flex items-center">
                            <i class="ri-price-tag-3-line mr-2"></i>
                            料金詳細
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <div class="flex items-center justify-between py-3 border-b border-gray-200">
                                <span class="text-gray-700">料金プラン</span>
                                <span class="font-semibold text-gray-900"><?php echo htmlspecialchars($application['pricing_type'] ?? ''); ?></span>
                            </div>
                            <div class="flex items-center justify-between py-3 border-b border-gray-200">
                                <span class="text-gray-700">参加費</span>
                                <span class="font-bold text-2xl text-blue-600">¥<?php echo number_format($application['amount'] ?? 0); ?></span>
                            </div>
                            <div class="flex items-center justify-between py-3 border-b border-gray-200">
                                <span class="text-gray-700">消費税</span>
                                <span class="text-gray-900">含む</span>
                            </div>
                            <div class="flex items-center justify-between py-3">
                                <span class="text-gray-700">決済手数料</span>
                                <span class="text-green-600 font-semibold">¥0（当社負担）</span>
                            </div>
                        </div>

                        <div class="mt-6 pt-6 border-t-2 border-gray-300">
                            <div class="flex items-center justify-between">
                                <span class="text-lg font-bold text-gray-900">合計金額</span>
                                <span class="text-3xl font-bold text-blue-600">¥<?php echo number_format($application['amount'] ?? 0); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 後日課金について -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                        <i class="ri-information-line text-blue-600 mr-2"></i>
                        お支払いの流れ
                    </h3>
                    <div class="space-y-4">
                        <div class="flex items-start">
                            <div class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold mr-4 flex-shrink-0">1</div>
                            <div>
                                <div class="font-semibold text-gray-900 mb-1">クレジットカード情報の登録</div>
                                <div class="text-sm text-gray-700">Stripe決済システムで安全にカード情報を登録します。この時点では課金されません。</div>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <div class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold mr-4 flex-shrink-0">2</div>
                            <div>
                                <div class="font-semibold text-gray-900 mb-1">本人確認の実施</div>
                                <div class="text-sm text-gray-700">学生証による本人確認を完了させてください。</div>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <div class="w-8 h-8 bg-green-600 text-white rounded-full flex items-center justify-center font-bold mr-4 flex-shrink-0">✓</div>
                            <div>
                                <div class="font-semibold text-gray-900 mb-1">自動決済</div>
                                <div class="text-sm text-gray-700">本人確認完了後、登録されたカードで自動的に決済されます。</div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <h4 class="font-semibold text-gray-900 mb-2 flex items-center text-sm">
                            <i class="ri-shield-check-line text-blue-600 mr-2"></i>
                            セキュリティについて
                        </h4>
                        <ul class="text-xs text-gray-700 space-y-1">
                            <li>• Stripe決済システムは国際セキュリティ基準PCI DSSに準拠しています</li>
                            <li>• カード情報は暗号化され、安全に保管されます</li>
                            <li>• 決済完了後、メールで通知をお送りします</li>
                        </ul>
                    </div>
                </div>

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

    <script>
    // ページ読み込み時に支払い情報をAPIから取得
    document.addEventListener('DOMContentLoaded', async function() {
        try {
            const response = await fetch('../api/user/get-payment-status.php');
            const data = await response.json();
            
            if (data.success) {
                // APIから取得したデータで画面を更新
                console.log('Payment Status:', data);
                // 既にPHPで表示されているので、追加の処理は不要
                // 将来的にフルJavaScript化する場合はここでDOM操作
            } else {
                console.error('API Error:', data.error);
            }
        } catch (error) {
            console.error('Fetch Error:', error);
        }
    });
    </script>

</body>
<script>
(function() {
    try {
        sessionStorage.setItem('application_id', '<?php echo $application['id'] ?? ''; ?>');
        sessionStorage.setItem('application_number', '<?php echo $application['application_number'] ?? ''; ?>');
        sessionStorage.setItem('amount', '<?php echo $application['amount'] ?? 0; ?>');
        sessionStorage.setItem('participation_type', '<?php echo $application['participation_type'] ?? ''; ?>');
        <?php if (!empty($teamMemberSessionId)): ?>
        sessionStorage.setItem('team_member_id', '<?php echo $teamMemberSessionId; ?>');
        <?php else: ?>
        sessionStorage.removeItem('team_member_id');
        <?php endif; ?>
    } catch (error) {
        console.warn('sessionStorageへの書き込みに失敗しました', error);
    }
})();
</script>
</html>

