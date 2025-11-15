<?php
/**
 * KYC Status Page
 * 本人確認状況画面
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
$teamMemberSessionId = AuthHelper::getTeamMemberId();

// Supabaseから申込情報を取得
$supabase = new SupabaseClient(SUPABASE_URL, SUPABASE_SERVICE_KEY);

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
    
} catch (Exception $e) {
    $error = $e->getMessage();
}

$kycStatus = $application['kyc_status'] ?? 'pending';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- 検索エンジンインデックス防止（Stripe審査中） -->
    <meta name="robots" content="noindex, nofollow">
    <meta name="googlebot" content="noindex, nofollow">
    
    <title>本人確認状況 - マイページ</title>
    
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
                        <a href="payment-status.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-lg transition-colors">
                            <i class="ri-bank-card-line mr-3"></i>
                            支払い状況
                        </a>
                        <a href="kyc-status.php" class="flex items-center px-4 py-3 bg-blue-50 text-blue-600 rounded-lg font-medium">
                            <i class="ri-shield-check-line mr-3"></i>
                            本人確認状況
                        </a>
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
                    <h2 class="text-3xl font-bold text-gray-900 mb-2">本人確認状況</h2>
                    <p class="text-gray-600">学生証による本人確認の状況を確認できます</p>
                </div>

                <!-- 本人確認ステータスカード -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="bg-brand-pink px-6 py-4">
                        <h3 class="text-xl font-bold text-white flex items-center">
                            <i class="ri-shield-check-line mr-2"></i>
                            本人確認ステータス
                        </h3>
                    </div>
                    <div class="p-6">
                        <?php if ($kycStatus === 'completed'): ?>
                        <!-- 本人確認完了 -->
                        <div class="bg-green-50 border border-green-200 rounded-xl p-6 text-center">
                            <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="ri-checkbox-circle-line text-green-600 text-4xl"></i>
                            </div>
                            <h4 class="text-2xl font-bold text-gray-900 mb-2">本人確認完了</h4>
                            <p class="text-gray-700">学生証による本人確認が完了しています</p>
                            <?php if (!empty($application['kyc_verified_at'])): ?>
                            <p class="text-sm text-gray-600 mt-2">確認完了日時: <?php echo date('Y年m月d日 H:i', strtotime($application['kyc_verified_at'])); ?></p>
                            <?php endif; ?>
                        </div>

                        <?php elseif ($kycStatus === 'in_progress'): ?>
                        <!-- 本人確認中 -->
                        <div class="bg-blue-50 border border-blue-200 rounded-xl p-6 text-center">
                            <div class="w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="ri-loader-4-line text-blue-600 text-4xl animate-spin"></i>
                            </div>
                            <h4 class="text-2xl font-bold text-gray-900 mb-2">確認処理中</h4>
                            <p class="text-gray-700 mb-4">本人確認情報を確認しています。しばらくお待ちください。</p>
                            <p class="text-sm text-gray-600">通常、1〜2営業日以内に完了します</p>
                        </div>

                        <?php elseif ($kycStatus === 'rejected'): ?>
                        <!-- 本人確認却下 -->
                        <div class="bg-red-50 border border-red-200 rounded-xl p-6 text-center">
                            <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="ri-close-circle-line text-red-600 text-4xl"></i>
                            </div>
                            <h4 class="text-2xl font-bold text-gray-900 mb-2">本人確認ができませんでした</h4>
                            <p class="text-gray-700 mb-4">本人確認に使用された書類に問題があります</p>
                            
                            <a
                                href="../kyc-verification.php"
                                class="inline-flex items-center bg-red-600 text-white px-8 py-4 rounded-lg font-semibold hover:bg-red-700 transition-colors"
                            >
                                <i class="ri-refresh-line mr-2"></i>
                                再度本人確認を行う
                            </a>

                            <div class="mt-6 bg-white rounded-lg p-4 text-left">
                                <h5 class="font-semibold text-gray-900 mb-2 text-sm">よくある却下理由：</h5>
                                <ul class="text-xs text-gray-700 space-y-1">
                                    <li>• 学生証の文字が不鮮明</li>
                                    <li>• 学生証の一部が欠けている</li>
                                    <li>• 有効期限が切れている</li>
                                    <li>• 顔写真が判別できない</li>
                                </ul>
                            </div>
                        </div>

                        <?php else: ?>
                        <!-- 未実施 -->
                        <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6 text-center">
                            <div class="w-20 h-20 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="ri-alert-line text-yellow-600 text-4xl"></i>
                            </div>
                            <h4 class="text-2xl font-bold text-gray-900 mb-2">本人確認が必要です</h4>
                            <p class="text-gray-700 mb-6">学生証による本人確認を実施してください</p>
                            
                            <a
                                href="../kyc-verification.php"
                                class="inline-flex items-center bg-gradient-blue-teal hover-gradient-blue-teal text-white px-8 py-4 rounded-lg font-semibold text-lg transition-all shadow-lg hover:shadow-xl"
                            >
                                <i class="ri-shield-check-line mr-2"></i>
                                本人確認を開始する
                            </a>

                            <p class="text-sm text-gray-600 mt-4">
                                <i class="ri-time-line mr-1"></i>
                                所要時間：約3分
                            </p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 本人確認の流れ -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                        <i class="ri-information-line text-blue-600 mr-2"></i>
                        本人確認の流れ
                    </h3>
                    <div class="space-y-4">
                        <div class="flex items-start">
                            <div class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold mr-4 flex-shrink-0">1</div>
                            <div>
                                <div class="font-semibold text-gray-900 mb-1">学生証を準備</div>
                                <div class="text-sm text-gray-700">有効期限内の学生証をご用意ください。顔写真、氏名、学校名が明確に記載されているものが必要です。</div>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <div class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold mr-4 flex-shrink-0">2</div>
                            <div>
                                <div class="font-semibold text-gray-900 mb-1">学生証を撮影</div>
                                <div class="text-sm text-gray-700">カメラまたはスマートフォンで学生証の表面を撮影します。文字がはっきり読める状態で撮影してください。</div>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <div class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold mr-4 flex-shrink-0">3</div>
                            <div>
                                <div class="font-semibold text-gray-900 mb-1">確認完了を待つ</div>
                                <div class="text-sm text-gray-700">システムが自動的に確認を行います。通常1〜2営業日以内に完了します。</div>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <div class="w-8 h-8 bg-green-600 text-white rounded-full flex items-center justify-center font-bold mr-4 flex-shrink-0">✓</div>
                            <div>
                                <div class="font-semibold text-gray-900 mb-1">自動決済</div>
                                <div class="text-sm text-gray-700">本人確認完了後、登録されたカードで自動的に決済が実行されます。</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 注意事項 -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                        <i class="ri-error-warning-line text-orange-600 mr-2"></i>
                        撮影時の注意事項
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- 良い例 -->
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                            <h4 class="font-semibold text-green-800 mb-2 flex items-center text-sm">
                                <i class="ri-check-line mr-2"></i>
                                良い例
                            </h4>
                            <ul class="text-xs text-green-700 space-y-1">
                                <li>✓ 明るい場所で撮影</li>
                                <li>✓ 学生証全体が写っている</li>
                                <li>✓ 文字がはっきり読める</li>
                                <li>✓ 反射や影がない</li>
                                <li>✓ ピントが合っている</li>
                            </ul>
                        </div>
                        
                        <!-- 悪い例 -->
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                            <h4 class="font-semibold text-red-800 mb-2 flex items-center text-sm">
                                <i class="ri-close-line mr-2"></i>
                                悪い例
                            </h4>
                            <ul class="text-xs text-red-700 space-y-1">
                                <li>✗ 暗くて文字が読めない</li>
                                <li>✗ 学生証の一部が欠けている</li>
                                <li>✗ 反射で文字が見えない</li>
                                <li>✗ ピンボケしている</li>
                                <li>✗ 斜めから撮影している</li>
                            </ul>
                        </div>
                    </div>

                    <div class="mt-4 bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <h4 class="font-semibold text-gray-900 mb-2 flex items-center text-sm">
                            <i class="ri-lightbulb-line text-blue-600 mr-2"></i>
                            ヒント
                        </h4>
                        <ul class="text-xs text-gray-700 space-y-1">
                            <li>• 白い紙の上に学生証を置いて撮影すると、背景との区別がつきやすくなります</li>
                            <li>• フラッシュは使用せず、自然光や室内照明を利用してください</li>
                            <li>• 真上から撮影すると反射を避けられます</li>
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
    // ページ読み込み時にKYC情報をAPIから取得
    document.addEventListener('DOMContentLoaded', async function() {
        try {
            const response = await fetch('../api/user/get-kyc-status.php');
            const data = await response.json();
            
            if (data.success) {
                // APIから取得したデータで画面を更新
                console.log('KYC Status:', data);
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

