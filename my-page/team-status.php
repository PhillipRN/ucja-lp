<?php
/**
 * Team Status Page
 * チーム管理画面（チーム戦専用）
 */

require_once __DIR__ . '/../lib/AuthHelper.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../lib/SupabaseClient.php';

// ログインチェック
AuthHelper::requireLogin();

// ユーザー情報取得
$applicationNumber = AuthHelper::getApplicationNumber();
$participationType = AuthHelper::getParticipationType();
$userId = AuthHelper::getUserId();
$loggedInEmail = trim(strtolower(AuthHelper::getUserEmail() ?? ''));

// 個人戦の場合はダッシュボードにリダイレクト
if ($participationType !== 'team') {
    header('Location: dashboard.php');
    exit;
}

// 代表者かどうかを判定
$isGuardian = false;
try {
    $supabase = new SupabaseClient(SUPABASE_URL, SUPABASE_SERVICE_KEY);
    $teamResult = $supabase->from('team_applications')
        ->select('guardian_email')
        ->eq('application_id', $userId)
        ->single();
    
    if ($teamResult['success'] && !empty($teamResult['data'])) {
        $guardianEmail = trim(strtolower($teamResult['data']['guardian_email']));
        $isGuardian = ($loggedInEmail === $guardianEmail);
    }
} catch (Exception $e) {
    // エラーが発生した場合は代表者ではないとみなす
    $isGuardian = false;
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
    
    <title>チーム管理 - マイページ</title>
    
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
        .bg-gradient-blue-teal-radial {
            background: radial-gradient(circle at top right, #007bff, #6BBBAE);
        }
    </style>
    
    <!-- PHP変数をJavaScriptに渡す -->
    <script>
        const IS_GUARDIAN = <?php echo $isGuardian ? 'true' : 'false'; ?>;
    </script>
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
        
        <!-- ローディング表示 -->
        <div id="loading" class="text-center py-12">
            <div class="inline-block">
                <div class="w-16 h-16 border-4 border-blue-600 border-t-transparent rounded-full animate-spin mb-4"></div>
            </div>
            <p class="text-gray-600">チーム情報を読み込み中...</p>
        </div>

        <!-- エラー表示 -->
        <div id="error" class="hidden mb-6 bg-red-50 border border-red-200 rounded-xl p-6">
            <div class="flex items-center text-red-800">
                <i class="ri-error-warning-line text-2xl mr-3"></i>
                <div>
                    <div class="font-semibold">エラーが発生しました</div>
                    <div id="error-text" class="text-sm mt-1"></div>
                </div>
            </div>
        </div>

        <div id="content" class="hidden grid grid-cols-1 lg:grid-cols-3 gap-6">
            
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
                        <a href="team-status.php" class="flex items-center px-4 py-3 bg-blue-50 text-blue-600 rounded-lg font-medium">
                            <i class="ri-team-line mr-3"></i>
                            チーム管理
                        </a>
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
                    <h2 class="text-3xl font-bold text-gray-900 mb-2">チーム管理</h2>
                    <p class="text-gray-600">チーム全体の状況とメンバー情報</p>
                </div>

                <!-- チーム情報カード -->
                <div class="bg-brand-pink rounded-xl shadow-lg p-6 text-white">
                    <h3 class="text-2xl font-bold mb-4 flex items-center">
                        <i class="ri-team-line mr-2"></i>
                        <span id="team-name">読み込み中...</span>
                    </h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="bg-white/20 backdrop-blur-sm rounded-lg p-4">
                            <div class="text-sm mb-1">学校名</div>
                            <div class="font-semibold text-lg" id="team-school">-</div>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm rounded-lg p-4">
                            <div class="text-sm mb-1">メンバー数</div>
                            <div class="font-bold text-2xl">5名</div>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm rounded-lg p-4">
                            <div class="text-sm mb-1">チーム成立</div>
                            <div class="font-bold text-2xl" id="team-complete-status">-</div>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm rounded-lg p-4">
                            <div class="text-sm mb-1">チーム合計点</div>
                            <div class="font-bold text-3xl" id="team-total-score">-</div>
                        </div>
                    </div>
                </div>

                <!-- チーム全体の進捗 -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                        <i class="ri-bar-chart-line text-blue-600 mr-2"></i>
                        チーム全体の進捗
                    </h3>
                    <div class="space-y-4">
                        <!-- 支払い進捗 -->
                        <div>
                            <div class="flex justify-between mb-2">
                                <span class="text-sm font-medium text-gray-700">支払い完了</span>
                                <span class="text-sm font-semibold text-gray-900"><span id="payment-count">0</span> / 5名</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-3">
                                <div id="payment-progress" class="bg-green-600 h-3 rounded-full transition-all" style="width: 0%"></div>
                            </div>
                        </div>

                        <!-- 本人確認進捗 -->
                        <div>
                            <div class="flex justify-between mb-2">
                                <span class="text-sm font-medium text-gray-700">本人確認完了</span>
                                <span class="text-sm font-semibold text-gray-900"><span id="kyc-count">0</span> / 5名</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-3">
                                <div id="kyc-progress" class="bg-blue-600 h-3 rounded-full transition-all" style="width: 0%"></div>
                            </div>
                        </div>

                        <!-- 試験受験進捗 -->
                        <div>
                            <div class="flex justify-between mb-2">
                                <span class="text-sm font-medium text-gray-700">試験受験完了</span>
                                <span class="text-sm font-semibold text-gray-900"><span id="exam-count">0</span> / 5名</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-3">
                                <div id="exam-progress" class="bg-purple-600 h-3 rounded-full transition-all" style="width: 0%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- チーム合計点詳細 -->
                <div id="team-score-detail" class="hidden bg-gradient-to-r from-yellow-50 to-orange-50 border-2 border-yellow-300 rounded-xl p-6">
                    <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                        <i class="ri-trophy-line text-yellow-600 mr-2"></i>
                        チーム合計点（上位4名）
                    </h3>
                    <div class="mb-4">
                        <div class="text-center">
                            <div class="text-5xl font-bold text-yellow-600 mb-2" id="team-score-large">0</div>
                            <div class="text-sm text-gray-600">チーム戦の順位は上位4名のスコア合計で決まります</div>
                        </div>
                    </div>
                    <div id="top-four-list" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <!-- JavaScriptで動的に生成 -->
                    </div>
                </div>

                <!-- メンバー一覧 -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="bg-gradient-blue-teal px-6 py-4">
                        <h3 class="text-xl font-bold text-white flex items-center">
                            <i class="ri-group-line mr-2"></i>
                            チームメンバー一覧
                        </h3>
                    </div>
                    <div class="p-6">
                        <div id="members-list" class="space-y-4">
                            <!-- JavaScriptで動的に生成 -->
                        </div>
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

    <!-- 編集モーダル -->
    <div id="edit-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="bg-gradient-blue-teal px-6 py-4 flex items-center justify-between">
                <h3 class="text-xl font-bold text-white flex items-center">
                    <i class="ri-edit-line mr-2"></i>
                    メンバー情報編集
                </h3>
                <button onclick="closeEditModal()" class="text-white hover:text-gray-200">
                    <i class="ri-close-line text-2xl"></i>
                </button>
            </div>
            
            <form id="edit-form" class="p-6 space-y-4">
                <input type="hidden" id="edit-member-id">
                
                <!-- 氏名 -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        氏名 <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        id="edit-member-name"
                        required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-teal focus:border-transparent"
                        placeholder="山田 太郎"
                    >
                </div>
                
                <!-- メールアドレス -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        メールアドレス（ログイン用） <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="email"
                        id="edit-member-email"
                        required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-teal focus:border-transparent"
                        placeholder="example@email.com"
                    >
                    <p class="text-xs text-gray-500 mt-1">
                        <i class="ri-information-line mr-1"></i>
                        このメールアドレスはログイン用に使用されます
                    </p>
                </div>
                
                <!-- 電話番号 -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        電話番号
                    </label>
                    <input
                        type="tel"
                        id="edit-member-phone"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-teal focus:border-transparent"
                        placeholder="090-1234-5678"
                    >
                </div>
                
                <!-- 学年 -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        学年 <span class="text-red-500">*</span>
                    </label>
                    <select
                        id="edit-member-grade"
                        required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-teal focus:border-transparent"
                    >
                        <option value="">選択してください</option>
                        <option value="高校1年生">高校1年生</option>
                        <option value="高校2年生">高校2年生</option>
                        <option value="高校3年生">高校3年生</option>
                    </select>
                </div>
                
                <!-- メッセージ表示エリア -->
                <div id="edit-message" class="hidden"></div>
                
                <!-- ボタン -->
                <div class="flex space-x-3 pt-4">
                    <button
                        type="button"
                        onclick="closeEditModal()"
                        class="flex-1 bg-gray-200 text-gray-700 px-6 py-3 rounded-lg font-semibold hover:bg-gray-300 transition-colors"
                    >
                        キャンセル
                    </button>
                    <button
                        type="submit"
                        class="flex-1 bg-gradient-blue-teal hover-gradient-blue-teal text-white px-6 py-3 rounded-lg font-semibold transition-all shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed"
                        id="edit-submit-btn"
                    >
                        <span id="edit-btn-text">
                            <i class="ri-save-line mr-2"></i>
                            保存
                        </span>
                        <span id="edit-btn-spinner" class="hidden">
                            <i class="ri-loader-4-line animate-spin mr-2"></i>
                            保存中...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>

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
    // ページ読み込み時にチーム情報を取得
    document.addEventListener('DOMContentLoaded', async function() {
        try {
            console.log('Fetching team status...');
            const response = await fetch('../api/user/get-team-status.php');
            console.log('Response status:', response.status);
            
            const data = await response.json();
            console.log('Response data:', data);
            
            if (data.success) {
                displayTeamData(data);
                document.getElementById('loading').classList.add('hidden');
                document.getElementById('content').classList.remove('hidden');
            } else {
                // 詳細なエラー情報を表示
                let errorMessage = data.error || 'チーム情報の取得に失敗しました';
                if (data.file && data.line) {
                    errorMessage += '\n\nFile: ' + data.file + '\nLine: ' + data.line;
                }
                if (data.trace) {
                    console.error('Stack trace:', data.trace);
                }
                throw new Error(errorMessage);
            }
        } catch (error) {
            console.error('Error:', error);
            document.getElementById('loading').classList.add('hidden');
            document.getElementById('error').classList.remove('hidden');
            
            // エラーメッセージを整形して表示
            let errorText = error.message;
            if (errorText.includes('\n')) {
                errorText = errorText.split('\n')[0]; // 最初の行だけ表示
            }
            document.getElementById('error-text').textContent = errorText;
            
            // 詳細はコンソールに出力
            console.error('Full error details:', error);
        }
    });

    function formatDateTime(value) {
        if (!value) return '';
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) {
            return value;
        }
        const yyyy = date.getFullYear();
        const mm = String(date.getMonth() + 1).padStart(2, '0');
        const dd = String(date.getDate()).padStart(2, '0');
        const hh = String(date.getHours()).padStart(2, '0');
        const min = String(date.getMinutes()).padStart(2, '0');
        return `${yyyy}年${mm}月${dd}日 ${hh}:${min}`;
    }

    function displayTeamData(data) {
        const { team, members, stats, team_score } = data;
        
        // デバッグ情報
        console.log('IS_GUARDIAN:', IS_GUARDIAN);
        console.log('Members count:', members.length);
        
        // チーム基本情報
        document.getElementById('team-name').textContent = team.team_name;
        document.getElementById('team-school').textContent = team.school;
        document.getElementById('team-complete-status').textContent = stats.is_team_complete ? '成立' : '未成立';
        document.getElementById('team-total-score').textContent = team_score.total_score;
        
        // 進捗バー
        document.getElementById('payment-count').textContent = stats.total_payments;
        document.getElementById('payment-progress').style.width = stats.payment_progress + '%';
        
        document.getElementById('kyc-count').textContent = stats.total_kyc_completed;
        document.getElementById('kyc-progress').style.width = stats.kyc_progress + '%';
        
        document.getElementById('exam-count').textContent = stats.total_exam_completed;
        document.getElementById('exam-progress').style.width = stats.exam_progress + '%';
        
        // チーム合計点詳細（試験結果がある場合のみ表示）
        if (team_score.top_four_scores.length > 0) {
            document.getElementById('team-score-detail').classList.remove('hidden');
            document.getElementById('team-score-large').textContent = team_score.total_score;
            
            const topFourList = document.getElementById('top-four-list');
            topFourList.innerHTML = '';
            team_score.top_four_scores.forEach((scoreData, index) => {
                const rankColors = ['yellow', 'gray', 'orange', 'blue'];
                const rankIcons = ['ri-medal-line', 'ri-medal-2-line', 'ri-award-line', 'ri-star-line'];
                const color = rankColors[index] || 'blue';
                const icon = rankIcons[index] || 'ri-star-line';
                
                const item = document.createElement('div');
                item.className = `bg-white rounded-lg p-4 border-2 border-${color}-200`;
                item.innerHTML = `
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <i class="${icon} text-${color}-600 text-2xl mr-3"></i>
                            <div>
                                <div class="font-semibold text-gray-900">${scoreData.member_name}</div>
                                <div class="text-xs text-gray-600">メンバー${scoreData.member_number}</div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="font-bold text-2xl text-${color}-600">${scoreData.score}</div>
                            <div class="text-xs text-gray-600">${scoreData.time_taken}秒</div>
                        </div>
                    </div>
                `;
                topFourList.appendChild(item);
            });
        }
        
        // メンバー一覧
        const membersList = document.getElementById('members-list');
        membersList.innerHTML = '';
        
        members.forEach(member => {
            console.log('Creating card for member:', member.member_name, 'ID:', member.id);
            const memberCard = createMemberCard(member);
            membersList.appendChild(memberCard);
        });
    }

    function createMemberCard(member) {
        const card = document.createElement('div');
        card.className = 'bg-gray-50 rounded-xl p-6 border border-gray-200';
        
        // ステータスバッジ
        const paymentBadge = getPaymentBadge(member.payment_status);
        const kycBadge = getKycBadge(member.kyc_status);
        const paymentMeta = [];
        if (member.card_registered_at) {
            paymentMeta.push(`カード登録: ${formatDateTime(member.card_registered_at)}`);
        }
        if (member.charged_at) {
            paymentMeta.push(`決済: ${formatDateTime(member.charged_at)}`);
        }
        const paymentMetaHtml = paymentMeta.length ? `<div class="text-xs text-gray-500 mt-2">${paymentMeta.join('<br>')}</div>` : '';
        
        // 試験結果
        let examResultHtml = '';
        if (member.has_exam_result) {
            const result = member.exam_result;
            examResultHtml = `
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <h5 class="font-semibold text-gray-900 mb-3 flex items-center">
                        <i class="ri-file-chart-line text-purple-600 mr-2"></i>
                        試験結果
                    </h5>
                    <div class="grid grid-cols-3 gap-3">
                        <div class="bg-white rounded-lg p-3 text-center">
                            <div class="text-xs text-gray-600 mb-1">スコア</div>
                            <div class="font-bold text-xl text-purple-600">${result.final_score}</div>
                        </div>
                        <div class="bg-white rounded-lg p-3 text-center">
                            <div class="text-xs text-gray-600 mb-1">時間</div>
                            <div class="font-semibold text-gray-900">${result.total_time_seconds}秒</div>
                        </div>
                        <div class="bg-white rounded-lg p-3 text-center">
                            <div class="text-xs text-gray-600 mb-1">正答数</div>
                            <div class="font-semibold text-gray-900">${result.correct_answers}/${result.total_questions}</div>
                        </div>
                    </div>
                </div>
            `;
        } else {
            examResultHtml = `
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <div class="text-center text-gray-500 py-3">
                        <i class="ri-time-line text-2xl mb-2"></i>
                        <div class="text-sm">試験未受験</div>
                    </div>
                </div>
            `;
        }
        
        card.innerHTML = `
            <div class="flex items-start justify-between mb-4">
                <div class="flex items-center flex-1">
                    <div class="w-12 h-12 bg-gradient-blue-teal-radial rounded-full flex items-center justify-center text-white font-bold text-xl mr-4">
                        ${member.member_number}
                    </div>
                    <div>
                        <div class="font-bold text-lg text-gray-900">
                            ${member.member_name}
                            ${member.is_representative ? '<span class="ml-2 inline-flex items-center bg-blue-100 text-blue-800 px-2 py-0.5 rounded text-xs font-medium">代表</span>' : ''}
                        </div>
                        <div class="text-sm text-gray-600">${member.member_email}</div>
                        <div class="text-xs text-gray-500 mt-1">${member.member_phone || '-'}</div>
                    </div>
                </div>
                ${IS_GUARDIAN ? `
                <button 
                    onclick='openEditModal("${member.id}", "${member.member_name.replace(/"/g, '&quot;')}", "${member.member_email}", "${member.member_phone || ''}", "${member.member_grade || '未設定'}")'
                    class="inline-flex items-center bg-brand-teal hover:bg-brand-teal-dark text-white px-3 py-1.5 rounded-lg text-sm font-semibold transition-colors"
                >
                    <i class="ri-edit-line mr-1"></i>
                    編集
                </button>
                ` : ''}
            </div>
            
            <div class="grid grid-cols-3 gap-3 mb-4">
                <div>
                    <div class="text-xs text-gray-600 mb-1">学年</div>
                    <div class="text-sm font-medium text-gray-900">${member.member_grade || '未設定'}</div>
                </div>
                <div>
                    <div class="text-xs text-gray-600 mb-1">支払い状況</div>
                    ${paymentBadge}
                </div>
                <div>
                    <div class="text-xs text-gray-600 mb-1">本人確認</div>
                    ${kycBadge}
                </div>
            </div>
            ${paymentMetaHtml}
            
            ${examResultHtml}
        `;
        
        return card;
    }

    function getPaymentBadge(status) {
        const badges = {
            'pending': '<span class="inline-flex items-center bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full text-xs font-semibold"><i class="ri-time-line mr-1"></i>未払い</span>',
            'card_registered': '<span class="inline-flex items-center bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs font-semibold"><i class="ri-bank-card-line mr-1"></i>カード登録済</span>',
            'processing': '<span class="inline-flex items-center bg-purple-100 text-purple-800 px-2 py-1 rounded-full text-xs font-semibold"><i class="ri-loader-4-line mr-1"></i>処理中</span>',
            'completed': '<span class="inline-flex items-center bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs font-semibold"><i class="ri-check-line mr-1"></i>完了</span>',
            'failed': '<span class="inline-flex items-center bg-red-100 text-red-800 px-2 py-1 rounded-full text-xs font-semibold"><i class="ri-close-line mr-1"></i>失敗</span>',
            'refunded': '<span class="inline-flex items-center bg-gray-100 text-gray-800 px-2 py-1 rounded-full text-xs font-semibold"><i class="ri-restart-line mr-1"></i>返金済</span>'
        };
        return badges[status] || badges['pending'];
    }

    function getKycBadge(status) {
        const badges = {
            'pending': '<span class="inline-flex items-center bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full text-xs font-semibold"><i class="ri-time-line mr-1"></i>未実施</span>',
            'in_progress': '<span class="inline-flex items-center bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs font-semibold"><i class="ri-loader-line mr-1"></i>確認中</span>',
            'completed': '<span class="inline-flex items-center bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs font-semibold"><i class="ri-check-line mr-1"></i>完了</span>',
            'rejected': '<span class="inline-flex items-center bg-red-100 text-red-800 px-2 py-1 rounded-full text-xs font-semibold"><i class="ri-close-line mr-1"></i>却下</span>'
        };
        return badges[status] || badges['pending'];
    }

    // モーダル開く
    function openEditModal(memberId, name, email, phone, grade) {
        console.log('openEditModal called:', { memberId, name, email, phone, grade });
        
        document.getElementById('edit-member-id').value = memberId;
        document.getElementById('edit-member-name').value = name;
        document.getElementById('edit-member-email').value = email;
        document.getElementById('edit-member-phone').value = phone || '';
        document.getElementById('edit-member-grade').value = grade;
        document.getElementById('edit-modal').classList.remove('hidden');
        document.getElementById('edit-message').classList.add('hidden');
    }

    // モーダル閉じる
    function closeEditModal() {
        document.getElementById('edit-modal').classList.add('hidden');
        document.getElementById('edit-form').reset();
    }

    // 編集フォーム送信
    document.getElementById('edit-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const submitBtn = document.getElementById('edit-submit-btn');
        const btnText = document.getElementById('edit-btn-text');
        const btnSpinner = document.getElementById('edit-btn-spinner');
        const messageDiv = document.getElementById('edit-message');
        
        // ボタンを無効化
        submitBtn.disabled = true;
        btnText.classList.add('hidden');
        btnSpinner.classList.remove('hidden');
        
        // フォームデータを取得
        const formData = {
            member_id: document.getElementById('edit-member-id').value,
            member_name: document.getElementById('edit-member-name').value,
            member_email: document.getElementById('edit-member-email').value,
            member_phone: document.getElementById('edit-member-phone').value,
            member_grade: document.getElementById('edit-member-grade').value
        };
        
        try {
            console.log('Sending update request:', formData);
            
            const response = await fetch('../api/user/update-team-member.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            });
            
            console.log('Response status:', response.status);
            
            // レスポンステキストを取得してログに出力
            const responseText = await response.text();
            console.log('Response text:', responseText);
            
            // JSONとしてパース
            let data;
            try {
                data = JSON.parse(responseText);
            } catch (parseError) {
                console.error('JSON parse error:', parseError);
                throw new Error('サーバーからの応答が不正です。コンソールを確認してください。');
            }
            
            if (data.success) {
                // 成功メッセージ表示
                messageDiv.innerHTML = `
                    <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg flex items-center">
                        <i class="ri-check-line text-xl mr-2"></i>
                        <span>メンバー情報を更新しました</span>
                    </div>
                `;
                messageDiv.classList.remove('hidden');
                
                // 2秒後にモーダルを閉じてページをリロード
                setTimeout(() => {
                    closeEditModal();
                    location.reload();
                }, 2000);
            } else {
                throw new Error(data.error || '更新に失敗しました');
            }
        } catch (error) {
            console.error('Error:', error);
            messageDiv.innerHTML = `
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg flex items-center">
                    <i class="ri-error-warning-line text-xl mr-2"></i>
                    <span>${error.message}</span>
                </div>
            `;
            messageDiv.classList.remove('hidden');
        } finally {
            // ボタンを有効化
            submitBtn.disabled = false;
            btnText.classList.remove('hidden');
            btnSpinner.classList.add('hidden');
        }
    });
    </script>

</body>
</html>

