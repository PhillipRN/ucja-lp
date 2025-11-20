<?php
define('ADMIN_PAGE', true);
$pageTitle = '一斉メール送信';
$pageDescription = 'メールテンプレートを使って申込者に一斉メールを送信します';

include __DIR__ . '/components/header.php';
?>

<div class="max-w-4xl">
    <!-- ステップ表示 -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div id="step1" class="flex items-center">
                    <div class="w-10 h-10 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold">
                        1
                    </div>
                    <span class="ml-2 font-semibold text-blue-600">テンプレート選択</span>
                </div>
                <i class="ri-arrow-right-line text-gray-400"></i>
                <div id="step2" class="flex items-center">
                    <div class="w-10 h-10 bg-gray-300 text-gray-600 rounded-full flex items-center justify-center font-bold">
                        2
                    </div>
                    <span class="ml-2 text-gray-600">受信者選択</span>
                </div>
                <i class="ri-arrow-right-line text-gray-400"></i>
                <div id="step3" class="flex items-center">
                    <div class="w-10 h-10 bg-gray-300 text-gray-600 rounded-full flex items-center justify-center font-bold">
                        3
                    </div>
                    <span class="ml-2 text-gray-600">確認・送信</span>
                </div>
            </div>
        </div>
    </div>

    <!-- ステップ1: テンプレート選択 -->
    <div id="templateStep" class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">メールテンプレートを選択</h3>
        <div id="templateList" class="space-y-3">
            <!-- JavaScriptで生成 -->
            <div class="text-center py-8 text-gray-400">
                <i class="ri-loader-4-line text-3xl animate-spin"></i>
                <p class="mt-2">読み込み中...</p>
            </div>
        </div>
        <div class="mt-6 flex justify-end">
            <button onclick="goToStep(2)" id="nextToStep2" disabled
                class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                次へ <i class="ri-arrow-right-line ml-2"></i>
            </button>
        </div>
    </div>

    <!-- ステップ2: 受信者選択 -->
    <div id="recipientStep" class="hidden bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">受信者を選択</h3>
        
        <div class="space-y-4">
            <!-- 受信者タイプ -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">送信対象</label>
                <div class="space-y-2">
                    <label class="flex items-center p-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                        <input type="radio" name="recipientType" value="all" checked 
                            class="w-4 h-4 text-blue-600" onchange="updateRecipientType()">
                        <span class="ml-3 font-medium text-gray-800">全申込者</span>
                    </label>
                    <label class="flex items-center p-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                        <input type="radio" name="recipientType" value="filter" 
                            class="w-4 h-4 text-blue-600" onchange="updateRecipientType()">
                        <span class="ml-3 font-medium text-gray-800">条件で絞り込み</span>
                    </label>
                </div>
            </div>

            <!-- フィルター条件 -->
            <div id="filterOptions" class="hidden pl-4 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">ステータス</label>
                    <select id="filterStatus" 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">すべて</option>
                        <option value="submitted">申込完了</option>
                        <option value="card_pending">カード登録待ち</option>
                        <option value="kyc_pending">本人確認待ち</option>
                        <option value="payment_pending">決済待ち</option>
                        <option value="payment_completed">決済完了</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">参加形式</label>
                    <select id="filterType" 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">すべて</option>
                        <option value="individual">個人戦</option>
                        <option value="team">チーム戦</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">決済状況</label>
                    <select id="filterPayment" 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">すべて</option>
                        <option value="pending">未決済</option>
                        <option value="card_registered">カード登録済</option>
                        <option value="completed">完了</option>
                    </select>
                </div>
            </div>

            <!-- テストモード -->
            <div class="flex items-center p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                <input type="checkbox" id="testMode" checked
                    class="w-4 h-4 text-blue-600">
                <label for="testMode" class="ml-3 text-sm font-medium text-gray-800">
                    <i class="ri-test-tube-line text-yellow-600 mr-1"></i>
                    テストモード（実際には送信しません）
                </label>
            </div>
        </div>

        <div class="mt-6 flex items-center justify-between">
            <button onclick="goToStep(1)" 
                class="px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium">
                <i class="ri-arrow-left-line mr-2"></i>
                戻る
            </button>
            <button onclick="goToStep(3)" 
                class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium">
                次へ <i class="ri-arrow-right-line ml-2"></i>
            </button>
        </div>
    </div>

    <!-- ステップ3: 確認・送信 -->
    <div id="confirmStep" class="hidden bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">送信内容の確認</h3>
        
        <div class="space-y-6">
            <!-- 選択したテンプレート -->
            <div class="bg-gray-50 rounded-lg p-4">
                <h4 class="font-semibold text-gray-800 mb-2">テンプレート</h4>
                <p id="confirmTemplateName" class="text-gray-700">-</p>
            </div>

            <!-- 受信者 -->
            <div class="bg-gray-50 rounded-lg p-4">
                <h4 class="font-semibold text-gray-800 mb-2">受信者</h4>
                <p id="confirmRecipient" class="text-gray-700">-</p>
            </div>

            <!-- 送信予約 -->
            <div id="scheduleSummary" class="hidden bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h4 class="font-semibold text-blue-800 mb-2 flex items-center">
                    <i class="ri-calendar-line mr-2"></i>
                    送信予約
                </h4>
                <p id="confirmScheduleAt" class="text-blue-800 text-sm mb-1">-</p>
                <p id="confirmDeadline" class="text-blue-700 text-xs">-</p>
            </div>

            <!-- テストモード -->
            <div id="confirmTestMode" class="hidden bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <h4 class="font-semibold text-yellow-800 mb-2">
                    <i class="ri-alert-line mr-2"></i>
                    テストモード
                </h4>
                <p class="text-yellow-700 text-sm">
                    メールログは作成されますが、実際のメール送信は行われません。
                </p>
            </div>

            <!-- 警告 -->
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <h4 class="font-semibold text-red-800 mb-2">
                    <i class="ri-error-warning-line mr-2"></i>
                    送信前の確認
                </h4>
                <ul class="text-red-700 text-sm space-y-1 list-disc list-inside">
                    <li>テンプレートの内容を確認しましたか？</li>
                    <li>送信対象は正しいですか？</li>
                    <li>テストモードを解除していますか？（本番送信の場合）</li>
                </ul>
            </div>
        </div>

        <div class="mt-6 flex items-center justify-between">
            <button onclick="goToStep(2)" 
                class="px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium">
                <i class="ri-arrow-left-line mr-2"></i>
                戻る
            </button>
            <button onclick="sendBulkEmail()" id="sendButton"
                class="px-8 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium flex items-center">
                <i class="ri-mail-send-line mr-2 text-xl"></i>
                送信する
            </button>
        </div>
    </div>
</div>

<!-- 送信予約フィールド（特定テンプレートのみ表示） -->
<div id="scheduleFields" class="hidden bg-blue-50 border border-blue-200 rounded-xl p-6 mt-6">
    <h4 class="font-semibold text-blue-900 mb-3 flex items-center">
        <i class="ri-calendar-check-line mr-2"></i>
        送信日時を指定
    </h4>
    <div class="grid md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-blue-900 mb-2">
                送信予定日時
            </label>
            <input type="datetime-local" id="scheduleAt"
                class="w-full px-4 py-2 border border-blue-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-blue-900 mb-2">
                支払い期限（任意）
            </label>
            <input type="date" id="deadlineDate"
                class="w-full px-4 py-2 border border-blue-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
    </div>
    <p class="text-xs text-blue-800 mt-3">
        指定した日時になると自動的にメールが送信されます。未入力の場合は即時送信されます。
    </p>
</div>

<script>
let currentStep = 1;
let templates = [];
let selectedTemplateId = null;
let selectedTemplate = null;

// テンプレート一覧読み込み
async function loadTemplates() {
    try {
        const response = await fetch('../api/admin/get-email-templates.php');
        const result = await response.json();

        if (!result.success) {
            showMessage(result.error || 'テンプレートの取得に失敗しました', 'error');
            return;
        }

        templates = result.templates.filter(t => t.is_active); // 有効なテンプレートのみ
        renderTemplates();

    } catch (error) {
        console.error('Load error:', error);
        showMessage('エラーが発生しました', 'error');
    }
}

// テンプレート一覧描画
function renderTemplates() {
    const container = document.getElementById('templateList');

    if (templates.length === 0) {
        container.innerHTML = `
            <div class="text-center py-8 text-gray-400">
                <i class="ri-inbox-line text-3xl"></i>
                <p class="mt-2">有効なテンプレートがありません</p>
            </div>
        `;
        return;
    }

    const typeLabels = {
        'application_confirmation': '申込受付確認',
        'card_registration': 'カード登録案内',
        'kyc_required': '本人確認依頼',
        'payment_confirmation': '決済完了通知',
        'exam_reminder': '試験日リマインダー',
        'team_member_payment': 'チームメンバー支払い依頼',
        'custom': 'カスタム'
    };

    container.innerHTML = templates.map(template => `
        <label class="flex items-start p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:bg-blue-50 hover:border-blue-300 transition-all ${selectedTemplateId === template.id ? 'bg-blue-50 border-blue-500' : ''}">
            <input type="radio" name="template" value="${template.id}" 
                class="w-4 h-4 text-blue-600 mt-1" 
                onchange="selectTemplate('${template.id}')">
            <div class="ml-3 flex-1">
                <div class="flex items-center space-x-2 mb-1">
                    <h4 class="font-semibold text-gray-800">${template.template_name}</h4>
                    <span class="px-2 py-0.5 text-xs font-medium bg-blue-100 text-blue-700 rounded">
                        ${typeLabels[template.template_type] || template.template_type}
                    </span>
                </div>
                <p class="text-sm text-gray-600">件名: ${template.subject}</p>
            </div>
        </label>
    `).join('');
}

// テンプレート選択
function selectTemplate(templateId) {
    selectedTemplateId = templateId;
    selectedTemplate = templates.find(t => t.id === templateId);
    document.getElementById('nextToStep2').disabled = false;
    renderTemplates();
    updateScheduleVisibility();
}

// 受信者タイプ更新
function updateRecipientType() {
    const type = document.querySelector('input[name="recipientType"]:checked').value;
    const filterOptions = document.getElementById('filterOptions');
    
    if (type === 'filter') {
        filterOptions.classList.remove('hidden');
    } else {
        filterOptions.classList.add('hidden');
    }
}

// ステップ移動
function goToStep(step) {
    currentStep = step;

    // ステップインジケーター更新
    for (let i = 1; i <= 3; i++) {
        const stepDiv = document.getElementById(`step${i}`);
        const circle = stepDiv.querySelector('div');
        const text = stepDiv.querySelector('span');
        
        if (i < step) {
            circle.className = 'w-10 h-10 bg-green-500 text-white rounded-full flex items-center justify-center font-bold';
            circle.innerHTML = '<i class="ri-check-line text-xl"></i>';
            text.className = 'ml-2 font-semibold text-green-600';
        } else if (i === step) {
            circle.className = 'w-10 h-10 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold';
            circle.textContent = i;
            text.className = 'ml-2 font-semibold text-blue-600';
        } else {
            circle.className = 'w-10 h-10 bg-gray-300 text-gray-600 rounded-full flex items-center justify-center font-bold';
            circle.textContent = i;
            text.className = 'ml-2 text-gray-600';
        }
    }

    // コンテンツ表示切り替え
    document.getElementById('templateStep').classList.toggle('hidden', step !== 1);
    document.getElementById('recipientStep').classList.toggle('hidden', step !== 2);
    document.getElementById('confirmStep').classList.toggle('hidden', step !== 3);

    // ステップ3の確認情報を更新
    if (step === 3) {
        updateConfirmation();
    }
}

// 確認情報更新
function updateConfirmation() {
    document.getElementById('confirmTemplateName').textContent = selectedTemplate ? selectedTemplate.template_name : '-';

    const recipientType = document.querySelector('input[name="recipientType"]:checked').value;
    let recipientText = '';
    
    if (recipientType === 'all') {
        recipientText = '全申込者';
    } else if (recipientType === 'filter') {
        const filters = [];
        const status = document.getElementById('filterStatus').value;
        const type = document.getElementById('filterType').value;
        const payment = document.getElementById('filterPayment').value;
        
        if (status) filters.push(`ステータス: ${status}`);
        if (type) filters.push(`参加形式: ${type === 'individual' ? '個人戦' : 'チーム戦'}`);
        if (payment) filters.push(`決済状況: ${payment}`);
        
        recipientText = filters.length > 0 ? `条件で絞り込み (${filters.join(', ')})` : '条件で絞り込み（すべて）';
    }
    
    document.getElementById('confirmRecipient').textContent = recipientText;

    const testMode = document.getElementById('testMode').checked;
    document.getElementById('confirmTestMode').classList.toggle('hidden', !testMode);

    const scheduleSummary = document.getElementById('scheduleSummary');
    if (scheduleSummary) {
        const scheduleFields = document.getElementById('scheduleFields');
        const scheduleInput = document.getElementById('scheduleAt');
        const deadlineInput = document.getElementById('deadlineDate');

        if (!scheduleFields.classList.contains('hidden') && scheduleInput.value) {
            scheduleSummary.classList.remove('hidden');
            document.getElementById('confirmScheduleAt').textContent =
                '送信予定: ' + new Date(scheduleInput.value).toLocaleString();
            document.getElementById('confirmDeadline').textContent =
                deadlineInput.value ? ('支払い期限: ' + deadlineInput.value) : '支払い期限: 指定なし';
        } else {
            scheduleSummary.classList.add('hidden');
        }
    }
}

// 一斉メール送信
async function sendBulkEmail() {
    if (!confirm('メールを送信しますか？')) {
        return;
    }

    const sendButton = document.getElementById('sendButton');
    sendButton.disabled = true;
    sendButton.innerHTML = '<i class="ri-loader-4-line mr-2 text-xl animate-spin"></i>送信中...';

    try {
        const recipientType = document.querySelector('input[name="recipientType"]:checked').value;
        const filters = {
            status: document.getElementById('filterStatus').value,
            participation_type: document.getElementById('filterType').value,
            payment_status: document.getElementById('filterPayment').value
        };
        const testMode = document.getElementById('testMode').checked;
        const scheduleFields = document.getElementById('scheduleFields');
        let scheduleAt = null;
        let deadline = null;

        if (!scheduleFields.classList.contains('hidden')) {
            scheduleAt = document.getElementById('scheduleAt').value || null;
            deadline = document.getElementById('deadlineDate').value || null;
        }

        const response = await fetch('../api/admin/send-bulk-email.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                template_id: selectedTemplateId,
                recipient_type: recipientType,
                filters: filters,
                test_mode: testMode,
                schedule_at: scheduleAt,
                deadline: deadline
            })
        });

        const result = await response.json();

        if (!result.success) {
            showMessage(result.error || '送信に失敗しました', 'error');
            sendButton.disabled = false;
            sendButton.innerHTML = '<i class="ri-mail-send-line mr-2 text-xl"></i>送信する';
            return;
        }

        showMessage(result.message, 'success');
        
        // 成功後、メール履歴ページに移動
        setTimeout(() => {
            window.location.href = 'email-history.php';
        }, 2000);

    } catch (error) {
        console.error('Send error:', error);
        showMessage('エラーが発生しました', 'error');
        sendButton.disabled = false;
        sendButton.innerHTML = '<i class="ri-mail-send-line mr-2 text-xl"></i>送信する';
    }
}

function updateScheduleVisibility() {
    const scheduleFields = document.getElementById('scheduleFields');
    if (selectedTemplate && selectedTemplate.template_type === 'team_member_payment') {
        scheduleFields.classList.remove('hidden');
    } else {
        scheduleFields.classList.add('hidden');
        document.getElementById('scheduleAt').value = '';
        document.getElementById('deadlineDate').value = '';
    }
}

// 初期読み込み
loadTemplates();
</script>

<?php include __DIR__ . '/components/footer.php'; ?>

