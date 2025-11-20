<?php
/**
 * Profile Page
 * プロフィール画面（情報表示 + 編集）
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

// ログインユーザーの属性を格納する変数
$isStudent = false; // 生徒本人かどうか
$isGuardian = false; // 保護者かどうか
$isMember = false; // チームメンバーかどうか
$memberInfo = null; // メンバー情報（チームメンバーの場合）

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
                $isStudent = true;
            } elseif ($normalizedGuardianEmail === $normalizedLoginEmail) {
                $isGuardian = true;
            }
        }
        
    } else {
        $detailResult = $supabase->from('team_applications')
            ->select('*')
            ->eq('application_id', $userId)
            ->single();
        
        $detail = $detailResult['data'] ?? null;
        
        // ログインユーザーの判定
        if ($detail) {
            // メールアドレスを正規化して比較
            $normalizedLoginEmail = trim(strtolower($loginEmail));
            $normalizedGuardianEmail = trim(strtolower($detail['guardian_email']));
            
            if ($normalizedGuardianEmail === $normalizedLoginEmail) {
                $isGuardian = true;
            } else {
                // チームメンバーの中から該当者を探す
                // 全メンバーを取得してメールアドレスを正規化して比較
                $allMembersResult = $supabase->from('team_members')
                    ->select('*')
                    ->eq('team_application_id', $detail['id'])
                    ->execute();
                
                if ($allMembersResult['success'] && !empty($allMembersResult['data'])) {
                    foreach ($allMembersResult['data'] as $member) {
                        $normalizedMemberEmail = trim(strtolower($member['member_email']));
                        if ($normalizedMemberEmail === $normalizedLoginEmail) {
                            $isMember = true;
                            $memberInfo = $member;
                            break;
                        }
                    }
                }
            }
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
    
    <title>プロフィール - マイページ</title>
    
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
                        <img src="../images/UCJA_Academy_logo_fin.png" alt="Cambridge Logo" class="h-10">
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
                    <div class="flex items-center justify-between lg:block">
                        <button id="mobileNavToggle" class="flex items-center text-gray-900 font-bold focus:outline-none lg:cursor-default lg:pointer-events-none">
                            <i class="ri-menu-line text-blue-600 mr-2 text-xl"></i>
                            <span class="text-lg">メニュー</span>
                        </button>
                    </div>
                    <nav id="sideNav" class="space-y-2 hidden lg:block">
                        <a href="dashboard.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-lg transition-colors">
                            <i class="ri-dashboard-line mr-3"></i>
                            ダッシュボード
                        </a>
                        <a href="application-detail.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-lg transition-colors">
                            <i class="ri-file-list-3-line mr-3"></i>
                            申込詳細
                        </a>
                        <?php if ($participationType === 'team'): ?>
                        <a href="team-status.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-lg transition-colors">
                            <i class="ri-team-line mr-3"></i>
                            チーム管理
                        </a>
                        <?php endif; ?>
                        <?php if (!($participationType === 'team' && $isGuardian)): ?>
                        <a href="payment-status.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-lg transition-colors">
                            <i class="ri-bank-card-line mr-3"></i>
                            支払い状況
                        </a>
                        <?php endif; ?>
                        <?php if (!$isGuardian): ?>
                        <a href="kyc-status.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-lg transition-colors">
                            <i class="ri-shield-check-line mr-3"></i>
                            本人確認状況
                        </a>
                        <?php endif; ?>
                        <a href="profile.php" class="flex items-center px-4 py-3 bg-gradient-blue-teal text-white rounded-lg transition-colors">
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
                    <h2 class="text-3xl font-bold text-gray-900 mb-2">プロフィール</h2>
                    <p class="text-gray-600">あなたの登録情報を確認・編集できます</p>
                </div>

                <!-- 成功メッセージ -->
                <div id="success-message" class="hidden bg-green-50 border border-green-200 rounded-xl p-6">
                    <div class="flex items-center text-green-800">
                        <i class="ri-check-line text-2xl mr-3"></i>
                        <div>
                            <div class="font-semibold">更新が完了しました</div>
                            <div class="text-sm mt-1">プロフィール情報を更新しました</div>
                        </div>
                    </div>
                </div>

                <!-- エラーメッセージ -->
                <div id="error-message" class="hidden bg-red-50 border border-red-200 rounded-xl p-6">
                    <div class="flex items-start text-red-800">
                        <i class="ri-error-warning-line text-2xl mr-3 mt-0.5"></i>
                        <div>
                            <div class="font-semibold mb-1">更新エラー</div>
                            <div id="error-text" class="text-sm"></div>
                        </div>
                    </div>
                </div>

                <?php if ($participationType === 'individual'): ?>
                <!-- ========== 個人戦のプロフィール ========== -->
                <form id="profile-form" class="space-y-6">
                    
                    <!-- 生徒情報 -->
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                        <div class="bg-gradient-blue-teal px-6 py-4">
                            <h3 class="text-xl font-bold text-white flex items-center">
                                <i class="ri-user-line mr-2"></i>
                                生徒情報
                            </h3>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- 生徒氏名（変更不可） -->
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        生徒氏名
                                    </label>
                                    <div class="bg-gray-50 px-4 py-3 rounded-lg border border-gray-200">
                                        <span class="font-semibold text-gray-900"><?php echo htmlspecialchars($detail['student_name'] ?? ''); ?></span>
                                        <span class="text-xs text-gray-500 ml-2">（変更不可）</span>
                                    </div>
                                </div>
                                
                                <!-- 学校名（変更不可） -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        学校名
                                    </label>
                                    <div class="bg-gray-50 px-4 py-3 rounded-lg border border-gray-200">
                                        <span class="text-gray-900"><?php echo htmlspecialchars($detail['school'] ?? ''); ?></span>
                                    </div>
                                </div>
                                
                                <!-- 学年（変更不可） -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        学年
                                    </label>
                                    <div class="bg-gray-50 px-4 py-3 rounded-lg border border-gray-200">
                                        <span class="text-gray-900"><?php echo htmlspecialchars($detail['grade'] ?? ''); ?></span>
                                    </div>
                                </div>
                                
                                <!-- 生徒メールアドレス（編集可・ログイン用） -->
                                <div>
                                    <label for="student_email" class="block text-sm font-medium text-gray-700 mb-2">
                                        メールアドレス <span class="text-brand-teal text-xs">（ログイン用）</span>
                                    </label>
                                    <input
                                        type="email"
                                        id="student_email"
                                        name="student_email"
                                        value="<?php echo htmlspecialchars($detail['student_email'] ?? ''); ?>"
                                        required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    >
                                </div>
                                
                                <!-- 生徒電話番号（編集可） -->
                                <div>
                                    <label for="student_phone" class="block text-sm font-medium text-gray-700 mb-2">
                                        電話番号
                                    </label>
                                    <input
                                        type="tel"
                                        id="student_phone"
                                        name="student_phone"
                                        value="<?php echo htmlspecialchars($detail['student_phone'] ?? ''); ?>"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        placeholder="080-1234-5678"
                                    >
                                </div>
                            </div>
                            
                            <p class="text-xs text-gray-500 mt-4 flex items-start">
                                <i class="ri-information-line mr-1 mt-0.5"></i>
                                <span>氏名・学校名・学年の変更が必要な場合は、お問い合わせください</span>
                            </p>
                        </div>
                    </div>

                    <!-- 保護者情報 -->
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                        <div class="bg-brand-blue px-6 py-4">
                            <h3 class="text-xl font-bold text-white flex items-center">
                                <i class="ri-parent-line mr-2"></i>
                                保護者情報
                            </h3>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- 保護者氏名（編集可） -->
                                <div class="md:col-span-2">
                                    <label for="guardian_name" class="block text-sm font-medium text-gray-700 mb-2">
                                        保護者氏名 <span class="text-red-600">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        id="guardian_name"
                                        name="guardian_name"
                                        value="<?php echo htmlspecialchars($detail['guardian_name'] ?? ''); ?>"
                                        required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        placeholder="山田 太郎"
                                    >
                                </div>
                                
                                <!-- 保護者メールアドレス（編集可・ログイン用） -->
                                <div>
                                    <label for="guardian_email" class="block text-sm font-medium text-gray-700 mb-2">
                                        メールアドレス <span class="text-red-600">*</span> <span class="text-brand-teal text-xs">（ログイン用）</span>
                                    </label>
                                    <input
                                        type="email"
                                        id="guardian_email"
                                        name="guardian_email"
                                        value="<?php echo htmlspecialchars($detail['guardian_email'] ?? ''); ?>"
                                        required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        placeholder="example@email.com"
                                    >
                                </div>
                                
                                <!-- 保護者電話番号（編集可） -->
                                <div>
                                    <label for="guardian_phone" class="block text-sm font-medium text-gray-700 mb-2">
                                        電話番号 <span class="text-red-600">*</span>
                                    </label>
                                    <input
                                        type="tel"
                                        id="guardian_phone"
                                        name="guardian_phone"
                                        value="<?php echo htmlspecialchars($detail['guardian_phone'] ?? ''); ?>"
                                        required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        placeholder="090-1234-5678"
                                    >
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ボタン -->
                    <div class="flex justify-end space-x-4">
                        <a
                            href="dashboard.php"
                            class="inline-flex items-center bg-gray-200 text-gray-700 px-6 py-3 rounded-lg font-semibold hover:bg-gray-300 transition-colors"
                        >
                            <i class="ri-close-line mr-2"></i>
                            キャンセル
                        </a>
                        <button
                            type="submit"
                            class="inline-flex items-center bg-gradient-blue-teal hover-gradient-blue-teal text-white px-8 py-3 rounded-lg font-semibold transition-all shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed"
                            id="save-button"
                        >
                            <span id="save-button-text">
                                <i class="ri-save-line mr-2"></i>
                                変更を保存
                            </span>
                            <span id="save-button-spinner" class="hidden">
                                <i class="ri-loader-4-line animate-spin mr-2"></i>
                                保存中...
                            </span>
                        </button>
                    </div>
                </form>

                <?php else: ?>
                <!-- ========== チーム戦のプロフィール ========== -->
                <form id="profile-form" class="space-y-6">
                    
                    <!-- チーム情報 -->
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                        <div class="bg-brand-blue px-6 py-4">
                            <h3 class="text-xl font-bold text-white flex items-center">
                                <i class="ri-team-line mr-2"></i>
                                チーム情報
                            </h3>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- チーム名（変更不可） -->
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        チーム名
                                    </label>
                                    <div class="bg-gray-50 px-4 py-3 rounded-lg border border-gray-200">
                                        <span class="font-bold text-xl text-gray-900"><?php echo htmlspecialchars($detail['team_name'] ?? ''); ?></span>
                                        <span class="text-xs text-gray-500 ml-2">（変更不可）</span>
                                    </div>
                                </div>
                                
                                <!-- 学校名（変更不可） -->
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        学校名
                                    </label>
                                    <div class="bg-gray-50 px-4 py-3 rounded-lg border border-gray-200">
                                        <span class="text-gray-900"><?php echo htmlspecialchars($detail['school'] ?? ''); ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <p class="text-xs text-gray-500 mt-4 flex items-start">
                                <i class="ri-information-line mr-1 mt-0.5"></i>
                                <span>チーム名・学校名の変更が必要な場合は、お問い合わせください</span>
                            </p>
                        </div>
                    </div>

                    <?php if ($isGuardian): ?>
                    <!-- 代表者（保護者）情報 -->
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                        <div class="bg-gradient-blue-teal px-6 py-4">
                            <h3 class="text-xl font-bold text-white flex items-center">
                                <i class="ri-user-star-line mr-2"></i>
                                代表者（保護者）情報
                            </h3>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- 代表者氏名（編集可） -->
                                <div class="md:col-span-2">
                                    <label for="guardian_name" class="block text-sm font-medium text-gray-700 mb-2">
                                        代表者氏名 <span class="text-red-600">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        id="guardian_name"
                                        name="guardian_name"
                                        value="<?php echo htmlspecialchars($detail['guardian_name'] ?? ''); ?>"
                                        required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        placeholder="山田 太郎"
                                    >
                                </div>
                                
                                <!-- 代表者メールアドレス（編集可・ログイン用） -->
                                <div>
                                    <label for="guardian_email" class="block text-sm font-medium text-gray-700 mb-2">
                                        メールアドレス <span class="text-red-600">*</span> <span class="text-brand-teal text-xs">（ログイン用）</span>
                                    </label>
                                    <input
                                        type="email"
                                        id="guardian_email"
                                        name="guardian_email"
                                        value="<?php echo htmlspecialchars($detail['guardian_email'] ?? ''); ?>"
                                        required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        placeholder="example@email.com"
                                    >
                                </div>
                                
                                <!-- 代表者電話番号（編集可） -->
                                <div>
                                    <label for="guardian_phone" class="block text-sm font-medium text-gray-700 mb-2">
                                        電話番号 <span class="text-red-600">*</span>
                                    </label>
                                    <input
                                        type="tel"
                                        id="guardian_phone"
                                        name="guardian_phone"
                                        value="<?php echo htmlspecialchars($detail['guardian_phone'] ?? ''); ?>"
                                        required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        placeholder="090-1234-5678"
                                    >
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php elseif ($isMember && $memberInfo): ?>
                    <!-- メンバー本人の情報 -->
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                        <div class="bg-gradient-blue-teal px-6 py-4">
                            <h3 class="text-xl font-bold text-white flex items-center">
                                <i class="ri-user-line mr-2"></i>
                                あなたの情報
                            </h3>
                        </div>
                        <div class="p-6 bg-gray-50">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- メンバー番号（参照のみ） -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        メンバー番号
                                    </label>
                                    <div class="bg-white px-4 py-3 rounded-lg border border-gray-200">
                                        <span class="font-semibold text-gray-900">メンバー<?php echo htmlspecialchars($memberInfo['member_number'] ?? ''); ?></span>
                                    </div>
                                </div>
                                
                                <!-- 学年（参照のみ） -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        学年
                                    </label>
                                    <div class="bg-white px-4 py-3 rounded-lg border border-gray-200">
                                        <span class="text-gray-900"><?php echo htmlspecialchars($memberInfo['member_grade'] ?? ''); ?></span>
                                    </div>
                                </div>
                                
                                <!-- メンバー氏名（参照のみ） -->
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        氏名
                                    </label>
                                    <div class="bg-white px-4 py-3 rounded-lg border border-gray-200">
                                        <span class="font-semibold text-xl text-gray-900"><?php echo htmlspecialchars($memberInfo['member_name'] ?? ''); ?></span>
                                        <span class="text-xs text-gray-500 ml-2">（変更不可）</span>
                                    </div>
                                </div>
                                
                                <!-- メンバーメールアドレス（参照のみ・ログイン用） -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        メールアドレス <span class="text-brand-teal text-xs">（ログイン用）</span>
                                    </label>
                                    <div class="bg-white px-4 py-3 rounded-lg border border-gray-200">
                                        <span class="text-gray-900"><?php echo htmlspecialchars($memberInfo['member_email'] ?? ''); ?></span>
                                    </div>
                                </div>
                                
                                <!-- メンバー電話番号（参照のみ） -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        電話番号
                                    </label>
                                    <div class="bg-white px-4 py-3 rounded-lg border border-gray-200">
                                        <span class="text-gray-900"><?php echo htmlspecialchars($memberInfo['member_phone'] ?? '-'); ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <p class="text-xs text-gray-500 mt-4 flex items-start">
                                <i class="ri-information-line mr-1 mt-0.5"></i>
                                <span>情報の変更が必要な場合は、代表者（<?php echo htmlspecialchars($detail['guardian_name'] ?? ''); ?>）に<a href="team-status.php" class="text-brand-teal font-medium hover:underline">チーム管理画面</a>から編集してもらってください</span>
                            </p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- ボタン -->
                    <?php if ($isGuardian): ?>
                    <!-- 代表者の場合：保存ボタンを表示 -->
                    <div class="flex justify-end space-x-4">
                        <a
                            href="dashboard.php"
                            class="inline-flex items-center bg-gray-200 text-gray-700 px-6 py-3 rounded-lg font-semibold hover:bg-gray-300 transition-colors"
                        >
                            <i class="ri-close-line mr-2"></i>
                            キャンセル
                        </a>
                        <button
                            type="submit"
                            class="inline-flex items-center bg-gradient-blue-teal hover-gradient-blue-teal text-white px-8 py-3 rounded-lg font-semibold transition-all shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed"
                            id="save-button"
                        >
                            <span id="save-button-text">
                                <i class="ri-save-line mr-2"></i>
                                変更を保存
                            </span>
                            <span id="save-button-spinner" class="hidden">
                                <i class="ri-loader-4-line animate-spin mr-2"></i>
                                保存中...
                            </span>
                        </button>
                    </div>
                    <?php else: ?>
                    <!-- メンバーの場合：戻るボタンのみ -->
                    <div class="flex justify-end">
                        <a
                            href="dashboard.php"
                            class="inline-flex items-center bg-gradient-blue-teal hover-gradient-blue-teal text-white px-8 py-3 rounded-lg font-semibold transition-all shadow-lg hover:shadow-xl"
                        >
                            <i class="ri-arrow-left-line mr-2"></i>
                            ダッシュボードに戻る
                        </a>
                    </div>
                    <?php endif; ?>
                </form>
                <?php endif; ?>

            </div>
        </div>
    </main>

    <script>
    // フォーム送信処理
    const form = document.getElementById('profile-form');
    const saveButton = document.getElementById('save-button');
    const buttonText = document.getElementById('save-button-text');
    const buttonSpinner = document.getElementById('save-button-spinner');
    const successMessage = document.getElementById('success-message');
    const errorMessage = document.getElementById('error-message');
    const errorText = document.getElementById('error-text');

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        // ボタンを無効化
        saveButton.disabled = true;
        buttonText.classList.add('hidden');
        buttonSpinner.classList.remove('hidden');

        // メッセージを非表示
        successMessage.classList.add('hidden');
        errorMessage.classList.add('hidden');

        // フォームデータを取得
        const formData = new FormData(form);

        try {
            const response = await fetch('../api/user/update-profile.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                // 成功メッセージを表示
                successMessage.classList.remove('hidden');
                window.scrollTo({ top: 0, behavior: 'smooth' });
            } else {
                throw new Error(data.error || '更新に失敗しました');
            }

        } catch (error) {
            console.error('Error:', error);
            errorText.textContent = error.message;
            errorMessage.classList.remove('hidden');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        } finally {
            // ボタンを再有効化
            saveButton.disabled = false;
            buttonText.classList.remove('hidden');
            buttonSpinner.classList.add('hidden');
        }
    });
    </script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const navToggle = document.getElementById('mobileNavToggle');
    const sideNav = document.getElementById('sideNav');
    if (navToggle && sideNav) {
        navToggle.addEventListener('click', () => {
            sideNav.classList.toggle('hidden');
            navToggle.innerHTML = sideNav.classList.contains('hidden')
                ? '<i class="ri-menu-fold-line mr-2"></i>メニューを表示'
                : '<i class="ri-menu-unfold-line mr-2"></i>メニューを隠す';
        });
    }
});
</script>

</body>
</html>
