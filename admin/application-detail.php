<?php
define('ADMIN_PAGE', true);
$pageTitle = '申込詳細';
$pageDescription = '申込内容の詳細を確認できます';

include __DIR__ . '/components/header.php';

// URLからIDを取得
$applicationId = $_GET['id'] ?? '';
if (empty($applicationId)) {
    echo '<div class="text-center py-12"><p class="text-red-600">申込IDが指定されていません</p></div>';
    include __DIR__ . '/components/footer.php';
    exit;
}
?>

<!-- 読み込み中 -->
<div id="loadingIndicator" class="text-center py-12">
    <i class="ri-loader-4-line text-4xl text-gray-400 animate-spin"></i>
    <p class="mt-3 text-gray-600">読み込み中...</p>
</div>

<!-- メインコンテンツ -->
<div id="mainContent" class="hidden">
    <!-- 戻るボタン -->
    <div class="mb-6">
        <a href="applications.php" 
            class="inline-flex items-center text-blue-600 hover:text-blue-700 font-medium">
            <i class="ri-arrow-left-line mr-2"></i>
            申込一覧に戻る
        </a>
    </div>

    <!-- 基本情報カード -->
    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-xl font-bold text-gray-800" id="applicationNumber">-</h3>
                <p class="text-sm text-gray-600 mt-1" id="createdAt">-</p>
            </div>
            <div class="flex items-center space-x-2">
                <span id="statusBadge" class="px-3 py-1 text-sm font-semibold rounded"></span>
                <span id="paymentBadge" class="px-3 py-1 text-sm font-semibold rounded"></span>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <p class="text-sm text-gray-600 mb-1">参加形式</p>
                <p id="participationType" class="text-lg font-semibold text-gray-800">-</p>
            </div>
            <div>
                <p class="text-sm text-gray-600 mb-1">金額</p>
                <p id="amount" class="text-lg font-semibold text-gray-800">-</p>
            </div>
            <div>
                <p class="text-sm text-gray-600 mb-1">カード登録</p>
                <p id="cardRegistered" class="text-lg font-semibold">-</p>
            </div>
        </div>
    </div>

    <!-- タブ -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="border-b border-gray-200">
            <div class="flex">
                <button onclick="switchDetailTab('info')" 
                    id="tabInfo" 
                    class="flex-1 px-6 py-4 font-semibold border-b-2 border-blue-600 text-blue-600 bg-blue-50">
                    <i class="ri-information-line mr-2"></i>
                    申込情報
                </button>
                <button onclick="switchDetailTab('email')" 
                    id="tabEmail" 
                    class="flex-1 px-6 py-4 font-semibold border-b-2 border-transparent text-gray-600 hover:bg-gray-50">
                    <i class="ri-mail-line mr-2"></i>
                    メール履歴
                </button>
                <button onclick="switchDetailTab('payment')" 
                    id="tabPayment" 
                    class="flex-1 px-6 py-4 font-semibold border-b-2 border-transparent text-gray-600 hover:bg-gray-50">
                    <i class="ri-bank-card-line mr-2"></i>
                    決済情報
                </button>
            </div>
        </div>

        <!-- 申込情報タブ -->
        <div id="infoTab" class="p-6">
            <div id="individualInfo" class="hidden space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h4 class="font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="ri-user-line text-blue-600 mr-2"></i>
                            生徒情報
                        </h4>
                        <div class="space-y-3">
                            <div>
                                <p class="text-sm text-gray-600">氏名</p>
                                <p id="studentName" class="font-medium text-gray-800">-</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">氏名（カナ）</p>
                                <p id="studentNameKana" class="font-medium text-gray-800">-</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">生年月日</p>
                                <p id="studentBirthdate" class="font-medium text-gray-800">-</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">学年</p>
                                <p id="studentGrade" class="font-medium text-gray-800">-</p>
                            </div>
                        </div>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="ri-parent-line text-green-600 mr-2"></i>
                            保護者情報
                        </h4>
                        <div class="space-y-3">
                            <div>
                                <p class="text-sm text-gray-600">氏名</p>
                                <p id="guardianName" class="font-medium text-gray-800">-</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">続柄</p>
                                <p id="guardianRelationship" class="font-medium text-gray-800">-</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">メールアドレス</p>
                                <p id="guardianEmail" class="font-medium text-gray-800">-</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">電話番号</p>
                                <p id="guardianPhone" class="font-medium text-gray-800">-</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="teamInfo" class="hidden space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <h4 class="font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="ri-team-line text-orange-600 mr-2"></i>
                            チーム情報
                        </h4>
                        <div class="space-y-3">
                            <div>
                                <p class="text-sm text-gray-600">チーム名</p>
                                <p id="teamName" class="font-medium text-gray-800">-</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">代表者名</p>
                                <p id="representativeName" class="font-medium text-gray-800">-</p>
                            </div>
                        </div>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="ri-parent-line text-green-600 mr-2"></i>
                            保護者情報
                        </h4>
                        <div class="space-y-3">
                            <div>
                                <p class="text-sm text-gray-600">氏名</p>
                                <p id="teamGuardianName" class="font-medium text-gray-800">-</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">メールアドレス</p>
                                <p id="teamGuardianEmail" class="font-medium text-gray-800">-</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">電話番号</p>
                                <p id="teamGuardianPhone" class="font-medium text-gray-800">-</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- チームメンバー -->
                <div>
                    <h4 class="font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="ri-group-line text-purple-600 mr-2"></i>
                        メンバー一覧
                    </h4>
                    <div id="teamMembers" class="space-y-3">
                        <!-- JavaScriptで生成 -->
                    </div>
                </div>
            </div>
        </div>

        <!-- メール履歴タブ -->
        <div id="emailTab" class="hidden p-6">
            <div id="emailLogsList" class="space-y-3">
                <!-- JavaScriptで生成 -->
            </div>
        </div>

        <!-- 決済情報タブ -->
        <div id="paymentTab" class="hidden p-6">
            <div class="space-y-6">
                <div>
                    <h4 class="font-semibold text-gray-800 mb-4">Stripe情報</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-600">Setup Intent ID</p>
                            <p id="stripeSetupIntentId" class="font-mono text-sm text-gray-800">-</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Payment Method ID</p>
                            <p id="stripePaymentMethodId" class="font-mono text-sm text-gray-800">-</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">カード登録日時</p>
                            <p id="cardRegisteredAt" class="text-sm text-gray-800">-</p>
                        </div>
                    </div>
                </div>

                <div id="transactionsList">
                    <!-- JavaScriptで生成 -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentDetailTab = 'info';
let applicationData = null;

// 詳細データ読み込み
async function loadApplicationDetail() {
    const applicationId = '<?php echo htmlspecialchars($applicationId); ?>';
    
    try {
        const response = await fetch(`../api/admin/get-application-detail.php?id=${applicationId}`);
        const result = await response.json();

        if (!result.success) {
            showMessage(result.error || 'データの取得に失敗しました', 'error');
            document.getElementById('loadingIndicator').innerHTML = `
                <div class="text-center py-12">
                    <i class="ri-error-warning-line text-4xl text-red-500"></i>
                    <p class="mt-3 text-red-600">${result.error || 'エラーが発生しました'}</p>
                </div>
            `;
            return;
        }

        applicationData = result;
        renderApplicationDetail();

        document.getElementById('loadingIndicator').classList.add('hidden');
        document.getElementById('mainContent').classList.remove('hidden');

    } catch (error) {
        console.error('Load error:', error);
        showMessage('エラーが発生しました', 'error');
    }
}

// 詳細情報描画
function renderApplicationDetail() {
    const app = applicationData.application;
    const details = applicationData.details;

    // 基本情報
    document.getElementById('applicationNumber').textContent = app.application_number;
    
    const createdDate = new Date(app.created_at);
    document.getElementById('createdAt').textContent = `申込日時: ${createdDate.toLocaleString('ja-JP')}`;
    
    document.getElementById('participationType').textContent = app.participation_type === 'individual' ? '個人戦' : 'チーム戦';
    document.getElementById('amount').textContent = `¥${app.amount.toLocaleString()}`;
    document.getElementById('cardRegistered').textContent = app.card_registered ? '登録済み' : '未登録';
    document.getElementById('cardRegistered').className = `text-lg font-semibold ${app.card_registered ? 'text-green-600' : 'text-gray-600'}`;

    // ステータスバッジ
    const statusLabels = {
        'draft': '下書き',
        'submitted': '申込完了',
        'card_pending': 'カード登録待ち',
        'kyc_pending': '本人確認待ち',
        'payment_pending': '決済待ち',
        'payment_completed': '決済完了',
        'cancelled': 'キャンセル'
    };
    const statusColors = {
        'draft': 'bg-gray-100 text-gray-700',
        'submitted': 'bg-blue-100 text-blue-700',
        'card_pending': 'bg-amber-100 text-amber-700',
        'kyc_pending': 'bg-purple-100 text-purple-700',
        'payment_pending': 'bg-cyan-100 text-cyan-700',
        'payment_completed': 'bg-green-100 text-green-700',
        'cancelled': 'bg-red-100 text-red-700'
    };
    
    document.getElementById('statusBadge').textContent = statusLabels[app.application_status];
    document.getElementById('statusBadge').className = `px-3 py-1 text-sm font-semibold rounded ${statusColors[app.application_status]}`;

    const paymentLabels = {
        'pending': '未決済',
        'card_registered': 'カード登録済',
        'processing': '処理中',
        'completed': '完了',
        'failed': '失敗'
    };
    const paymentColors = {
        'pending': 'bg-gray-100 text-gray-600',
        'card_registered': 'bg-blue-100 text-blue-600',
        'processing': 'bg-yellow-100 text-yellow-700',
        'completed': 'bg-green-100 text-green-700',
        'failed': 'bg-red-100 text-red-700'
    };
    
    document.getElementById('paymentBadge').textContent = paymentLabels[app.payment_status];
    document.getElementById('paymentBadge').className = `px-3 py-1 text-sm font-semibold rounded ${paymentColors[app.payment_status]}`;

    // 個人戦/チーム戦詳細
    if (app.participation_type === 'individual' && details) {
        document.getElementById('individualInfo').classList.remove('hidden');
        document.getElementById('teamInfo').classList.add('hidden');
        
        document.getElementById('studentName').textContent = details.student_name || '-';
        document.getElementById('studentNameKana').textContent = details.student_name_kana || '-';
        document.getElementById('studentBirthdate').textContent = details.student_birthdate || '-';
        document.getElementById('studentGrade').textContent = details.student_grade || '-';
        document.getElementById('guardianName').textContent = details.guardian_name || '-';
        document.getElementById('guardianRelationship').textContent = details.guardian_relationship || '-';
        document.getElementById('guardianEmail').textContent = details.guardian_email || '-';
        document.getElementById('guardianPhone').textContent = details.guardian_phone || '-';
    } else if (details) {
        document.getElementById('teamInfo').classList.remove('hidden');
        document.getElementById('individualInfo').classList.add('hidden');
        
        document.getElementById('teamName').textContent = details.team_name || '-';
        document.getElementById('representativeName').textContent = details.representative_name || '-';
        document.getElementById('teamGuardianName').textContent = details.guardian_name || '-';
        document.getElementById('teamGuardianEmail').textContent = details.guardian_email || '-';
        document.getElementById('teamGuardianPhone').textContent = details.guardian_phone || '-';

        // メンバー一覧
        const membersContainer = document.getElementById('teamMembers');
        if (details.members && details.members.length > 0) {
            membersContainer.innerHTML = details.members.map(member => `
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="font-semibold text-gray-800">${member.member_name} （${member.member_name_kana}）</p>
                            <p class="text-sm text-gray-600 mt-1">
                                生年月日: ${member.birthdate || '-'} / 学年: ${member.grade || '-'}
                            </p>
                        </div>
                        <span class="px-2 py-1 bg-purple-100 text-purple-700 text-xs font-semibold rounded">
                            メンバー${member.member_number}
                        </span>
                    </div>
                </div>
            `).join('');
        } else {
            membersContainer.innerHTML = '<p class="text-gray-500">メンバー情報がありません</p>';
        }
    }

    // メール履歴
    renderEmailLogs(applicationData.email_logs);

    // 決済情報
    renderPaymentInfo(app);
}

// メール履歴描画
function renderEmailLogs(logs) {
    const container = document.getElementById('emailLogsList');
    
    if (!logs || logs.length === 0) {
        container.innerHTML = '<p class="text-gray-500 text-center py-8">メール送信履歴がありません</p>';
        return;
    }

    const statusIcons = {
        'pending': 'ri-time-line text-gray-500',
        'sent': 'ri-checkbox-circle-line text-green-500',
        'failed': 'ri-error-warning-line text-red-500'
    };

    const statusLabels = {
        'pending': '送信待ち',
        'sent': '送信済み',
        'failed': '失敗'
    };

    container.innerHTML = logs.map(log => {
        const date = new Date(log.created_at);
        const dateStr = date.toLocaleString('ja-JP');
        
        return `
            <div class="bg-gray-50 rounded-lg p-4">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center space-x-2 mb-2">
                            <i class="${statusIcons[log.status]} text-xl"></i>
                            <span class="font-semibold text-gray-800">${log.subject || 'タイトルなし'}</span>
                            <span class="text-xs px-2 py-1 bg-white rounded">${statusLabels[log.status]}</span>
                        </div>
                        <p class="text-sm text-gray-600">宛先: ${log.recipient_email}</p>
                        <p class="text-xs text-gray-500 mt-1">${dateStr}</p>
                        ${log.error_message ? `<p class="text-xs text-red-600 mt-1">エラー: ${log.error_message}</p>` : ''}
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

// 決済情報描画
function renderPaymentInfo(app) {
    document.getElementById('stripeSetupIntentId').textContent = app.stripe_setup_intent_id || '-';
    document.getElementById('stripePaymentMethodId').textContent = app.stripe_payment_method_id || '-';
    document.getElementById('cardRegisteredAt').textContent = app.card_registered_at 
        ? new Date(app.card_registered_at).toLocaleString('ja-JP')
        : '-';
}

// タブ切り替え
function switchDetailTab(tab) {
    currentDetailTab = tab;

    ['info', 'email', 'payment'].forEach(t => {
        const tabBtn = document.getElementById(`tab${t.charAt(0).toUpperCase() + t.slice(1)}`);
        const tabContent = document.getElementById(`${t}Tab`);
        
        if (t === tab) {
            tabBtn.classList.add('border-blue-600', 'text-blue-600', 'bg-blue-50');
            tabBtn.classList.remove('border-transparent', 'text-gray-600');
            tabContent.classList.remove('hidden');
        } else {
            tabBtn.classList.remove('border-blue-600', 'text-blue-600', 'bg-blue-50');
            tabBtn.classList.add('border-transparent', 'text-gray-600');
            tabContent.classList.add('hidden');
        }
    });
}

// 初期読み込み
loadApplicationDetail();
</script>

<?php include __DIR__ . '/components/footer.php'; ?>

