<?php
define('ADMIN_PAGE', true);
$pageTitle = 'メール送信履歴';
$pageDescription = 'メールの送信履歴を確認できます';

include __DIR__ . '/components/header.php';
?>

<!-- フィルター -->
<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- 検索 -->
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-2">検索（メールアドレス）</label>
            <input type="text" id="searchInput" placeholder="example@email.com" 
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <!-- ステータスフィルター -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">ステータス</label>
            <select id="statusFilter" 
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">すべて</option>
                <option value="pending">送信待ち</option>
                <option value="sent">送信済み</option>
                <option value="failed">失敗</option>
                <option value="test">テスト</option>
            </select>
        </div>

        <!-- メールタイプフィルター -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">メールタイプ</label>
            <select id="typeFilter" 
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">すべて</option>
                <option value="application_confirmation">申込受付確認</option>
                <option value="card_registration">カード登録案内</option>
                <option value="kyc_required">本人確認依頼</option>
                <option value="payment_confirmation">決済完了通知</option>
                <option value="exam_reminder">試験日リマインダー</option>
                <option value="custom">カスタム</option>
            </select>
        </div>
    </div>

    <div class="mt-4 flex items-center justify-between">
        <button onclick="resetFilters()" 
            class="text-sm text-gray-600 hover:text-gray-800">
            <i class="ri-refresh-line mr-1"></i>
            フィルターをリセット
        </button>
    </div>
</div>

<!-- 統計サマリー -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow-sm p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">総送信数</p>
                <p id="totalCount" class="text-2xl font-bold text-gray-800 mt-1">-</p>
            </div>
            <i class="ri-mail-line text-3xl text-blue-500"></i>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">送信待ち</p>
                <p id="pendingCount" class="text-2xl font-bold text-gray-600 mt-1">-</p>
            </div>
            <i class="ri-time-line text-3xl text-gray-400"></i>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">送信済み</p>
                <p id="sentCount" class="text-2xl font-bold text-green-600 mt-1">-</p>
            </div>
            <i class="ri-checkbox-circle-line text-3xl text-green-500"></i>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">失敗</p>
                <p id="failedCount" class="text-2xl font-bold text-red-600 mt-1">-</p>
            </div>
            <i class="ri-error-warning-line text-3xl text-red-500"></i>
        </div>
    </div>
</div>

<!-- メール履歴テーブル -->
<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <!-- テーブルヘッダー -->
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-800">メール送信履歴</h3>
        <p id="resultCount" class="text-sm text-gray-600 mt-1">読み込み中...</p>
    </div>

    <!-- テーブル -->
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                        ステータス
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                        送信日時
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                        メールタイプ
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                        受信者
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                        件名
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                        申込番号
                    </th>
                    <th class="px-6 py-3 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider">
                        詳細
                    </th>
                </tr>
            </thead>
            <tbody id="emailLogsTableBody" class="bg-white divide-y divide-gray-200">
                <!-- JavaScriptで動的に生成 -->
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center text-gray-400">
                        <i class="ri-loader-4-line text-4xl animate-spin"></i>
                        <p class="mt-3">読み込み中...</p>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- ページネーション -->
    <div id="pagination" class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
        <!-- JavaScriptで動的に生成 -->
    </div>
</div>

<!-- 詳細モーダル -->
<div id="detailModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-3xl w-full max-h-[90vh] overflow-hidden flex flex-col">
        <!-- モーダルヘッダー -->
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-xl font-bold text-gray-800">メール詳細</h3>
            <button onclick="closeDetailModal()" class="text-gray-400 hover:text-gray-600">
                <i class="ri-close-line text-2xl"></i>
            </button>
        </div>

        <!-- モーダルコンテンツ -->
        <div class="flex-1 overflow-y-auto p-6 space-y-4">
            <div>
                <p class="text-sm text-gray-600">件名</p>
                <p id="modalSubject" class="font-semibold text-gray-800 text-lg">-</p>
            </div>
            <div>
                <p class="text-sm text-gray-600">受信者</p>
                <p id="modalRecipient" class="font-medium text-gray-800">-</p>
            </div>
            <div>
                <p class="text-sm text-gray-600">ステータス</p>
                <span id="modalStatus" class="inline-block px-3 py-1 text-sm font-semibold rounded">-</span>
            </div>
            <div>
                <p class="text-sm text-gray-600">送信日時</p>
                <p id="modalSentAt" class="font-medium text-gray-800">-</p>
            </div>
            <div id="modalErrorContainer" class="hidden">
                <p class="text-sm text-gray-600">エラーメッセージ</p>
                <p id="modalError" class="text-red-600 bg-red-50 p-3 rounded-lg">-</p>
            </div>
            <div>
                <p class="text-sm text-gray-600 mb-2">本文</p>
                <div id="modalBody" class="bg-gray-50 p-4 rounded-lg whitespace-pre-wrap font-mono text-sm max-h-96 overflow-y-auto">-</div>
            </div>
        </div>
    </div>
</div>

<script>
let currentPage = 1;
let currentFilters = {};
let emailLogs = [];

// メール履歴読み込み
async function loadEmailHistory() {
    try {
        // フィルター取得
        currentFilters = {
            page: currentPage,
            search: document.getElementById('searchInput').value.trim(),
            status: document.getElementById('statusFilter').value,
            email_type: document.getElementById('typeFilter').value
        };

        // URLパラメータ構築
        const params = new URLSearchParams();
        Object.entries(currentFilters).forEach(([key, value]) => {
            if (value) params.append(key, value);
        });

        const response = await fetch(`../api/admin/get-email-history.php?${params}`);
        const result = await response.json();

        if (!result.success) {
            showMessage(result.error || 'メール履歴の取得に失敗しました', 'error');
            return;
        }

        emailLogs = result.email_logs;
        renderEmailLogs(emailLogs);
        renderPagination(result.pagination);
        updateStats();

    } catch (error) {
        console.error('Load error:', error);
        showMessage('エラーが発生しました', 'error');
    }
}

// メール履歴描画
function renderEmailLogs(logs) {
    const tbody = document.getElementById('emailLogsTableBody');
    const resultCount = document.getElementById('resultCount');

    if (!logs || logs.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="px-6 py-12 text-center text-gray-400">
                    <i class="ri-inbox-line text-4xl"></i>
                    <p class="mt-3">メール履歴が見つかりません</p>
                </td>
            </tr>
        `;
        resultCount.textContent = '0件';
        return;
    }

    const statusIcons = {
        'pending': 'ri-time-line text-gray-500',
        'sent': 'ri-checkbox-circle-line text-green-500',
        'failed': 'ri-error-warning-line text-red-500',
        'test': 'ri-test-tube-line text-yellow-500'
    };

    const statusLabels = {
        'pending': '送信待ち',
        'sent': '送信済み',
        'failed': '失敗',
        'test': 'テスト'
    };

    const statusColors = {
        'pending': 'bg-gray-100 text-gray-700',
        'sent': 'bg-green-100 text-green-700',
        'failed': 'bg-red-100 text-red-700',
        'test': 'bg-yellow-100 text-yellow-700'
    };

    const typeLabels = {
        'application_confirmation': '申込受付確認',
        'card_registration': 'カード登録案内',
        'kyc_required': '本人確認依頼',
        'payment_confirmation': '決済完了通知',
        'exam_reminder': '試験日リマインダー',
        'custom': 'カスタム'
    };

    tbody.innerHTML = logs.map(log => {
        const date = new Date(log.sent_at || log.created_at);
        const dateStr = date.toLocaleString('ja-JP');

        return `
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                        <i class="${statusIcons[log.status]} text-2xl mr-2"></i>
                        <span class="px-2 py-1 text-xs font-semibold rounded ${statusColors[log.status]}">
                            ${statusLabels[log.status]}
                        </span>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                    ${dateStr}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-700 rounded">
                        ${typeLabels[log.email_type] || log.email_type}
                    </span>
                </td>
                <td class="px-6 py-4">
                    <div class="text-sm font-medium text-gray-800">${log.recipient_name || '-'}</div>
                    <div class="text-xs text-gray-500">${log.recipient_email}</div>
                </td>
                <td class="px-6 py-4">
                    <div class="text-sm text-gray-800 max-w-xs truncate">${log.subject}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    ${log.application ? `
                        <a href="application-detail.php?id=${log.application_id}" 
                            class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                            ${log.application.application_number}
                        </a>
                    ` : '-'}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-center">
                    <button onclick="showDetail('${log.id}')" 
                        class="inline-flex items-center px-3 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm transition-colors">
                        <i class="ri-eye-line mr-1"></i>
                        詳細
                    </button>
                </td>
            </tr>
        `;
    }).join('');

    resultCount.textContent = `${logs.length}件表示`;
}

// ページネーション描画
function renderPagination(pagination) {
    const container = document.getElementById('pagination');

    if (!pagination || pagination.total_pages <= 1) {
        container.innerHTML = `
            <div class="text-sm text-gray-600">
                全${pagination ? pagination.total_count : 0}件
            </div>
        `;
        return;
    }

    const pages = [];
    const maxPages = 5;
    let startPage = Math.max(1, pagination.current_page - Math.floor(maxPages / 2));
    let endPage = Math.min(pagination.total_pages, startPage + maxPages - 1);

    if (endPage - startPage < maxPages - 1) {
        startPage = Math.max(1, endPage - maxPages + 1);
    }

    for (let i = startPage; i <= endPage; i++) {
        pages.push(i);
    }

    container.innerHTML = `
        <div class="text-sm text-gray-600">
            全${pagination.total_count}件 (${pagination.current_page} / ${pagination.total_pages}ページ)
        </div>
        <div class="flex items-center space-x-2">
            <button onclick="goToPage(${pagination.current_page - 1})" 
                ${pagination.current_page === 1 ? 'disabled' : ''}
                class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                <i class="ri-arrow-left-s-line"></i>
            </button>
            
            ${pages.map(page => `
                <button onclick="goToPage(${page})" 
                    class="px-4 py-2 border rounded-lg ${page === pagination.current_page ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-300 hover:bg-gray-50'}">
                    ${page}
                </button>
            `).join('')}
            
            <button onclick="goToPage(${pagination.current_page + 1})" 
                ${pagination.current_page === pagination.total_pages ? 'disabled' : ''}
                class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                <i class="ri-arrow-right-s-line"></i>
            </button>
        </div>
    `;
}

// 統計更新
async function updateStats() {
    try {
        // 各ステータスの件数を取得
        const statuses = ['pending', 'sent', 'failed'];
        const counts = {};
        let total = 0;

        for (const status of statuses) {
            const response = await fetch(`../api/admin/get-email-history.php?status=${status}&limit=1`);
            const result = await response.json();
            if (result.success) {
                counts[status] = result.pagination.total_count;
                total += result.pagination.total_count;
            }
        }

        document.getElementById('totalCount').textContent = total;
        document.getElementById('pendingCount').textContent = counts.pending || 0;
        document.getElementById('sentCount').textContent = counts.sent || 0;
        document.getElementById('failedCount').textContent = counts.failed || 0;

    } catch (error) {
        console.error('Stats error:', error);
    }
}

// 詳細表示
function showDetail(logId) {
    const log = emailLogs.find(l => l.id === logId);
    if (!log) return;

    const statusLabels = {
        'pending': '送信待ち',
        'sent': '送信済み',
        'failed': '失敗',
        'test': 'テスト'
    };

    const statusColors = {
        'pending': 'bg-gray-100 text-gray-700',
        'sent': 'bg-green-100 text-green-700',
        'failed': 'bg-red-100 text-red-700',
        'test': 'bg-yellow-100 text-yellow-700'
    };

    document.getElementById('modalSubject').textContent = log.subject;
    document.getElementById('modalRecipient').textContent = `${log.recipient_name || '-'} (${log.recipient_email})`;
    document.getElementById('modalStatus').textContent = statusLabels[log.status];
    document.getElementById('modalStatus').className = `inline-block px-3 py-1 text-sm font-semibold rounded ${statusColors[log.status]}`;
    
    const date = new Date(log.sent_at || log.created_at);
    document.getElementById('modalSentAt').textContent = date.toLocaleString('ja-JP');
    
    if (log.error_message) {
        document.getElementById('modalErrorContainer').classList.remove('hidden');
        document.getElementById('modalError').textContent = log.error_message;
    } else {
        document.getElementById('modalErrorContainer').classList.add('hidden');
    }
    
    document.getElementById('modalBody').textContent = log.body_text || log.body_html || '本文なし';

    document.getElementById('detailModal').classList.remove('hidden');
}

// 詳細モーダルを閉じる
function closeDetailModal() {
    document.getElementById('detailModal').classList.add('hidden');
}

// ページ移動
function goToPage(page) {
    currentPage = page;
    loadEmailHistory();
}

// フィルターリセット
function resetFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('statusFilter').value = '';
    document.getElementById('typeFilter').value = '';
    currentPage = 1;
    loadEmailHistory();
}

// フィルター変更時に自動再読み込み
['statusFilter', 'typeFilter'].forEach(id => {
    document.getElementById(id).addEventListener('change', () => {
        currentPage = 1;
        loadEmailHistory();
    });
});

// 検索は500ms後に自動実行
let searchTimer;
document.getElementById('searchInput').addEventListener('input', () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        currentPage = 1;
        loadEmailHistory();
    }, 500);
});

// 初期読み込み
loadEmailHistory();
</script>

<?php include __DIR__ . '/components/footer.php'; ?>

