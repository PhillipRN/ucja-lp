<?php
define('ADMIN_PAGE', true);
$pageTitle = '申込一覧';
$pageDescription = '全申込の閲覧、検索、フィルタリングができます';

include __DIR__ . '/components/header.php';
?>

<!-- フィルター -->
<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <!-- 検索 -->
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-2">検索（申込番号）</label>
            <input type="text" id="searchInput" placeholder="CAMB2024-001" 
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <!-- ステータスフィルター -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">ステータス</label>
            <select id="statusFilter" 
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">すべて</option>
                <option value="draft">下書き</option>
                <option value="submitted">申込完了</option>
                <option value="card_pending">カード登録待ち</option>
                <option value="kyc_pending">本人確認待ち</option>
                <option value="payment_pending">決済待ち</option>
                <option value="payment_completed">決済完了</option>
                <option value="cancelled">キャンセル</option>
            </select>
        </div>

        <!-- 参加形式フィルター -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">参加形式</label>
            <select id="typeFilter" 
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">すべて</option>
                <option value="individual">個人戦</option>
                <option value="team">チーム戦</option>
            </select>
        </div>

        <!-- 決済ステータスフィルター -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">決済状況</label>
            <select id="paymentFilter" 
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">すべて</option>
                <option value="pending">未決済</option>
                <option value="card_registered">カード登録済</option>
                <option value="processing">処理中</option>
                <option value="completed">完了</option>
                <option value="failed">失敗</option>
            </select>
        </div>
    </div>

    <div class="mt-4 flex items-center justify-between">
        <button onclick="resetFilters()" 
            class="text-sm text-gray-600 hover:text-gray-800">
            <i class="ri-refresh-line mr-1"></i>
            フィルターをリセット
        </button>
        <button onclick="exportCSV()" 
            class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium">
            <i class="ri-file-excel-line mr-2"></i>
            CSV出力
        </button>
    </div>
</div>

<!-- 申込一覧テーブル -->
<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <!-- テーブルヘッダー -->
    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
        <div>
            <h3 class="text-lg font-semibold text-gray-800">申込一覧</h3>
            <p id="resultCount" class="text-sm text-gray-600 mt-1">読み込み中...</p>
        </div>
    </div>

    <!-- テーブル -->
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                        申込番号
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                        参加者名
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                        形式
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                        保護者
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                        金額
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                        ステータス
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                        決済
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                        申込日時
                    </th>
                    <th class="px-6 py-3 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider">
                        操作
                    </th>
                </tr>
            </thead>
            <tbody id="applicationsTableBody" class="bg-white divide-y divide-gray-200">
                <!-- JavaScriptで動的に生成 -->
                <tr>
                    <td colspan="9" class="px-6 py-12 text-center text-gray-400">
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

<script>
let currentPage = 1;
let currentFilters = {};

// 申込一覧読み込み
async function loadApplications() {
    try {
        // フィルター取得
        currentFilters = {
            page: currentPage,
            search: document.getElementById('searchInput').value.trim(),
            status: document.getElementById('statusFilter').value,
            participation_type: document.getElementById('typeFilter').value,
            payment_status: document.getElementById('paymentFilter').value
        };

        // URLパラメータ構築
        const params = new URLSearchParams();
        Object.entries(currentFilters).forEach(([key, value]) => {
            if (value) params.append(key, value);
        });

        const response = await fetch(`../api/admin/get-applications.php?${params}`);
        const result = await response.json();

        if (!result.success) {
            showMessage(result.error || '申込の取得に失敗しました', 'error');
            return;
        }

        renderApplications(result.applications);
        renderPagination(result.pagination);

    } catch (error) {
        console.error('Load error:', error);
        showMessage('エラーが発生しました', 'error');
    }
}

// 申込一覧描画
function renderApplications(applications) {
    const tbody = document.getElementById('applicationsTableBody');
    const resultCount = document.getElementById('resultCount');

    if (!applications || applications.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="9" class="px-6 py-12 text-center text-gray-400">
                    <i class="ri-inbox-line text-4xl"></i>
                    <p class="mt-3">申込が見つかりません</p>
                </td>
            </tr>
        `;
        resultCount.textContent = '0件';
        return;
    }

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

    tbody.innerHTML = applications.map(app => {
        const createdDate = new Date(app.created_at);
        const dateStr = `${createdDate.getFullYear()}/${createdDate.getMonth() + 1}/${createdDate.getDate()} ${createdDate.getHours()}:${String(createdDate.getMinutes()).padStart(2, '0')}`;

        return `
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="font-semibold text-blue-600">${app.application_number}</div>
                </td>
                <td class="px-6 py-4">
                    <div class="font-medium text-gray-800">${app.participant_name || '-'}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="text-sm ${app.participation_type === 'individual' ? 'text-purple-600' : 'text-orange-600'}">
                        ${app.participation_type === 'individual' ? '個人戦' : 'チーム戦'}
                    </span>
                </td>
                <td class="px-6 py-4">
                    <div class="text-sm text-gray-800">${app.guardian_name || '-'}</div>
                    <div class="text-xs text-gray-500">${app.guardian_email || '-'}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="font-semibold text-gray-800">¥${app.amount.toLocaleString()}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="inline-block px-2 py-1 text-xs font-semibold rounded ${statusColors[app.application_status]}">
                        ${statusLabels[app.application_status]}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="inline-block px-2 py-1 text-xs font-semibold rounded ${paymentColors[app.payment_status]}">
                        ${paymentLabels[app.payment_status]}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                    ${dateStr}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-center">
                    <a href="application-detail.php?id=${app.id}" 
                        class="inline-flex items-center px-3 py-1 bg-blue-50 hover:bg-blue-100 text-blue-600 rounded-lg text-sm font-medium transition-colors">
                        <i class="ri-eye-line mr-1"></i>
                        詳細
                    </a>
                </td>
            </tr>
        `;
    }).join('');

    resultCount.textContent = `${applications.length}件表示`;
}

// ページネーション描画
function renderPagination(pagination) {
    const container = document.getElementById('pagination');

    if (!pagination || pagination.total_pages <= 1) {
        container.innerHTML = `
            <div class="text-sm text-gray-600">
                全${pagination.total_count}件
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

// ページ移動
function goToPage(page) {
    currentPage = page;
    loadApplications();
}

// フィルターリセット
function resetFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('statusFilter').value = '';
    document.getElementById('typeFilter').value = '';
    document.getElementById('paymentFilter').value = '';
    currentPage = 1;
    loadApplications();
}

// CSV出力（将来実装）
function exportCSV() {
    showMessage('CSV出力機能は近日実装予定です', 'info');
}

// フィルター変更時に自動再読み込み
['searchInput', 'statusFilter', 'typeFilter', 'paymentFilter'].forEach(id => {
    document.getElementById(id).addEventListener('change', () => {
        currentPage = 1;
        loadApplications();
    });
});

// 検索は500ms後に自動実行（リアルタイム検索）
let searchTimer;
document.getElementById('searchInput').addEventListener('input', () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        currentPage = 1;
        loadApplications();
    }, 500);
});

// 初期読み込み
loadApplications();
</script>

<?php include __DIR__ . '/components/footer.php'; ?>

