<?php
define('ADMIN_PAGE', true);
$pageTitle = 'ダッシュボード';
$pageDescription = '申込状況とシステムの概要を確認できます';

include __DIR__ . '/components/header.php';
?>

<!-- 統計カード -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- 総申込数 -->
    <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-blue-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600 font-medium">総申込数</p>
                <p id="totalApplications" class="text-3xl font-bold text-gray-800 mt-2">-</p>
                <p class="text-xs text-gray-500 mt-1">
                    今日: <span id="todayApplications" class="font-semibold">-</span>件
                </p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                <i class="ri-file-list-3-line text-2xl text-blue-600"></i>
            </div>
        </div>
    </div>

    <!-- 決済完了 -->
    <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-green-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600 font-medium">決済完了</p>
                <p id="paymentCompleted" class="text-3xl font-bold text-gray-800 mt-2">-</p>
                <p class="text-xs text-gray-500 mt-1">
                    売上: <span id="totalRevenue" class="font-semibold">-</span>円
                </p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                <i class="ri-money-dollar-circle-line text-2xl text-green-600"></i>
            </div>
        </div>
    </div>

    <!-- カード登録済み -->
    <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-purple-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600 font-medium">カード登録済み</p>
                <p id="cardRegistered" class="text-3xl font-bold text-gray-800 mt-2">-</p>
                <p class="text-xs text-gray-500 mt-1">
                    個人戦: <span id="individualCount" class="font-semibold">-</span> / チーム戦: <span id="teamCount" class="font-semibold">-</span>
                </p>
            </div>
            <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                <i class="ri-bank-card-line text-2xl text-purple-600"></i>
            </div>
        </div>
    </div>

    <!-- メール送信待ち -->
    <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-orange-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600 font-medium">メール送信待ち</p>
                <p id="emailPending" class="text-3xl font-bold text-gray-800 mt-2">-</p>
                <p class="text-xs text-gray-500 mt-1">
                    送信済: <span id="emailSent" class="font-semibold">-</span> / 失敗: <span id="emailFailed" class="text-red-600 font-semibold">-</span>
                </p>
            </div>
            <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center">
                <i class="ri-mail-line text-2xl text-orange-600"></i>
            </div>
        </div>
    </div>
</div>

<!-- グラフとリスト -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- ステータス別グラフ -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
            <i class="ri-pie-chart-line text-xl mr-2 text-blue-600"></i>
            申込ステータス別
        </h3>
        <canvas id="statusChart" height="200"></canvas>
    </div>

    <!-- 最近の申込 -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
            <i class="ri-time-line text-xl mr-2 text-green-600"></i>
            最近の申込
        </h3>
        <div id="recentApplicationsList" class="space-y-3">
            <!-- JavaScriptで動的に生成 -->
            <div class="text-center py-8 text-gray-400">
                <i class="ri-loader-4-line text-3xl animate-spin"></i>
                <p class="mt-2">読み込み中...</p>
            </div>
        </div>
        <div class="mt-4 text-center">
            <a href="applications.php" class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                すべての申込を見る <i class="ri-arrow-right-line"></i>
            </a>
        </div>
    </div>
</div>

<!-- クイックアクション -->
<div class="bg-white rounded-xl shadow-sm p-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">クイックアクション</h3>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <a href="applications.php" class="flex items-center justify-center px-6 py-4 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors">
            <i class="ri-file-list-3-line text-2xl text-blue-600 mr-3"></i>
            <span class="font-medium text-blue-700">申込一覧を見る</span>
        </a>
        <a href="email-templates.php" class="flex items-center justify-center px-6 py-4 bg-purple-50 hover:bg-purple-100 rounded-lg transition-colors">
            <i class="ri-mail-settings-line text-2xl text-purple-600 mr-3"></i>
            <span class="font-medium text-purple-700">メールテンプレート</span>
        </a>
        <a href="send-email.php" class="flex items-center justify-center px-6 py-4 bg-green-50 hover:bg-green-100 rounded-lg transition-colors">
            <i class="ri-mail-send-line text-2xl text-green-600 mr-3"></i>
            <span class="font-medium text-green-700">一斉メール送信</span>
        </a>
    </div>
</div>

<script>
let statusChart = null;

// ダッシュボードデータ読み込み
async function loadDashboard() {
    try {
        const response = await fetch('../api/admin/get-dashboard-stats.php');
        const result = await response.json();

        if (!result.success) {
            showMessage(result.error || 'データの取得に失敗しました', 'error');
            return;
        }

        const stats = result.stats;

        // 統計カードを更新
        document.getElementById('totalApplications').textContent = stats.total_applications;
        document.getElementById('todayApplications').textContent = stats.today_applications;
        document.getElementById('paymentCompleted').textContent = stats.payment_completed;
        document.getElementById('totalRevenue').textContent = stats.total_revenue.toLocaleString();
        document.getElementById('cardRegistered').textContent = stats.card_registered;
        document.getElementById('individualCount').textContent = stats.individual_count;
        document.getElementById('teamCount').textContent = stats.team_count;
        document.getElementById('emailPending').textContent = stats.email_stats.pending;
        document.getElementById('emailSent').textContent = stats.email_stats.sent;
        document.getElementById('emailFailed').textContent = stats.email_stats.failed;

        // ステータス別グラフを描画
        renderStatusChart(stats.status_counts);

        // 最近の申込リストを描画
        renderRecentApplications(stats.recent_applications);

    } catch (error) {
        console.error('Dashboard error:', error);
        showMessage('データの取得中にエラーが発生しました', 'error');
    }
}

// ステータス別グラフ描画
function renderStatusChart(statusCounts) {
    const ctx = document.getElementById('statusChart').getContext('2d');
    
    // 既存のチャートがあれば破棄
    if (statusChart) {
        statusChart.destroy();
    }

    const labels = statusCounts.map(s => s.label);
    const data = statusCounts.map(s => s.count);
    const colors = [
        '#94a3b8', // draft - gray
        '#3b82f6', // submitted - blue
        '#f59e0b', // card_pending - amber
        '#8b5cf6', // kyc_pending - purple
        '#06b6d4', // payment_pending - cyan
        '#10b981', // payment_completed - green
        '#ef4444'  // cancelled - red
    ];

    statusChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: colors,
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        padding: 10,
                        font: {
                            size: 11
                        }
                    }
                }
            }
        }
    });
}

// 最近の申込リスト描画
function renderRecentApplications(applications) {
    const container = document.getElementById('recentApplicationsList');
    
    if (!applications || applications.length === 0) {
        container.innerHTML = `
            <div class="text-center py-8 text-gray-400">
                <i class="ri-inbox-line text-3xl"></i>
                <p class="mt-2">まだ申込がありません</p>
            </div>
        `;
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

    container.innerHTML = applications.map(app => {
        const date = new Date(app.created_at);
        const dateStr = `${date.getMonth() + 1}/${date.getDate()} ${date.getHours()}:${String(date.getMinutes()).padStart(2, '0')}`;
        
        return `
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                <div class="flex-1">
                    <p class="font-semibold text-gray-800">${app.application_number}</p>
                    <p class="text-sm text-gray-600">
                        ${app.participation_type === 'individual' ? '個人戦' : 'チーム戦'} / 
                        ${app.amount.toLocaleString()}円
                    </p>
                </div>
                <div class="text-right">
                    <span class="inline-block px-2 py-1 text-xs font-semibold rounded ${statusColors[app.application_status]}">
                        ${statusLabels[app.application_status]}
                    </span>
                    <p class="text-xs text-gray-500 mt-1">${dateStr}</p>
                </div>
            </div>
        `;
    }).join('');
}

// ページ読み込み時にデータ取得
loadDashboard();

// 30秒ごとに自動更新
setInterval(loadDashboard, 30000);
</script>

<?php include __DIR__ . '/components/footer.php'; ?>

