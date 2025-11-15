<?php
define('ADMIN_PAGE', true);
$pageTitle = 'お知らせ管理';
$pageDescription = 'LPサイトのお知らせを管理・投稿できます';

include __DIR__ . '/components/header.php';
?>

<!-- お知らせ一覧 -->
<div class="bg-white rounded-xl shadow-sm">
    <div class="p-6 border-b border-gray-200">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-800">お知らせ一覧</h3>
                <p class="text-sm text-gray-600 mt-1">LPサイトに表示するお知らせを管理します</p>
            </div>
            <button onclick="openCreateModal()" 
                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors flex items-center">
                <i class="ri-add-line mr-2"></i>
                新規作成
            </button>
        </div>
    </div>

    <!-- お知らせリスト -->
    <div id="announcementsList" class="divide-y divide-gray-200">
        <!-- JavaScriptで動的に生成 -->
        <div class="p-12 text-center text-gray-400">
            <i class="ri-loader-4-line text-4xl animate-spin"></i>
            <p class="mt-3">読み込み中...</p>
        </div>
    </div>
</div>

<!-- 作成・編集モーダル -->
<div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-3xl w-full max-h-[90vh] overflow-hidden flex flex-col">
        <!-- モーダルヘッダー -->
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-xl font-bold text-gray-800" id="modalTitle">お知らせ作成</h3>
            <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                <i class="ri-close-line text-2xl"></i>
            </button>
        </div>

        <!-- モーダルコンテンツ -->
        <div class="flex-1 overflow-y-auto p-6">
            <form id="editForm" class="space-y-6">
                <input type="hidden" id="editAnnouncementId">

                <!-- タイトル -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        タイトル <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="editTitle" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="例: 2025年11月11日または12日付の毎日新聞（朝刊）で広告を掲載">
                </div>

                <!-- 日付 -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        日付 <span class="text-red-500">*</span>
                    </label>
                    <input type="date" id="editAnnouncementDate" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-gray-500 mt-1">
                        LPサイトでは「●月○日」形式で表示されます
                    </p>
                </div>

                <!-- 内容 -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        内容 <span class="text-red-500">*</span>
                    </label>
                    <textarea id="editContent" rows="6" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="お知らせの内容を入力してください"></textarea>
                </div>

                <!-- 外部URL -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        外部リンクURL（プレスリリースなど）
                    </label>
                    <input type="url" id="editExternalUrl"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="https://prtimes.jp/...">
                </div>

                <!-- PDFファイルパス -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        PDFファイルパス（広告など）
                    </label>
                    <input type="text" id="editPdfFilePath"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="/uploads/announcements/advertisement.pdf">
                    <p class="text-xs text-gray-500 mt-1">
                        PDFファイルは事前にサーバーにアップロードしてください
                    </p>
                </div>

                <!-- 表示順序 -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        表示順序
                    </label>
                    <input type="number" id="editDisplayOrder" value="0"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-gray-500 mt-1">
                        小さい数字ほど上に表示されます（同じ日付の場合）
                    </p>
                </div>

                <!-- 公開フラグ -->
                <div class="flex items-center">
                    <input type="checkbox" id="editIsPublished" checked
                        class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <label for="editIsPublished" class="ml-2 text-sm font-medium text-gray-700">
                        公開する
                    </label>
                </div>
            </form>
        </div>

        <!-- モーダルフッター -->
        <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between bg-gray-50">
            <button onclick="closeEditModal()" 
                class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                キャンセル
            </button>
            <div class="flex items-center space-x-2">
                <button id="deleteButton" onclick="deleteAnnouncement()" 
                    class="hidden px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition-colors flex items-center">
                    <i class="ri-delete-bin-line mr-2"></i>
                    削除
                </button>
                <button onclick="saveAnnouncement()" 
                    class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors flex items-center">
                    <i class="ri-save-line mr-2"></i>
                    保存する
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let announcements = [];

// お知らせ一覧読み込み
async function loadAnnouncements() {
    try {
        const response = await fetch('../api/admin/get-announcements.php');
        const result = await response.json();

        if (!result.success) {
            showMessage(result.error || 'お知らせの取得に失敗しました', 'error');
            return;
        }

        announcements = result.announcements || [];
        renderAnnouncements();

    } catch (error) {
        console.error('Load error:', error);
        showMessage('エラーが発生しました', 'error');
    }
}

// お知らせ一覧描画
function renderAnnouncements() {
    const container = document.getElementById('announcementsList');

    if (announcements.length === 0) {
        container.innerHTML = `
            <div class="p-12 text-center text-gray-400">
                <i class="ri-inbox-line text-4xl"></i>
                <p class="mt-3">お知らせがありません</p>
                <button onclick="openCreateModal()" 
                    class="mt-4 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors">
                    最初のお知らせを作成
                </button>
            </div>
        `;
        return;
    }

    let html = '';
    announcements.forEach((announcement, index) => {
        const date = new Date(announcement.announcement_date);
        const dateStr = `${date.getFullYear()}年${date.getMonth() + 1}月${date.getDate()}日`;
        
        html += `
            <div class="p-6 hover:bg-gray-50 transition-colors">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center space-x-3 mb-2">
                            <span class="px-3 py-1 text-xs font-medium rounded ${announcement.is_published ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'}">
                                ${announcement.is_published ? '公開中' : '非公開'}
                            </span>
                            <span class="text-sm text-gray-500">${dateStr}</span>
                            <span class="text-sm text-gray-500">表示順: ${announcement.display_order}</span>
                        </div>
                        <h4 class="text-lg font-semibold text-gray-800 mb-2">${escapeHtml(announcement.title)}</h4>
                        <p class="text-sm text-gray-600 mb-3 line-clamp-2">${escapeHtml(announcement.content)}</p>
                        <div class="flex flex-wrap gap-2">
                            ${announcement.external_url ? `<span class="px-2 py-1 text-xs bg-blue-50 text-blue-700 rounded">URLあり</span>` : ''}
                            ${announcement.pdf_file_path ? `<span class="px-2 py-1 text-xs bg-gray-100 text-gray-700 rounded">PDFあり</span>` : ''}
                        </div>
                    </div>
                    <button onclick="openEditModal('${announcement.id}')" 
                        class="ml-4 px-4 py-2 bg-blue-50 hover:bg-blue-100 text-blue-600 rounded-lg font-medium transition-colors flex items-center">
                        <i class="ri-edit-line mr-2"></i>
                        編集
                    </button>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
}

// HTMLエスケープ
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// 作成モーダルを開く
function openCreateModal() {
    document.getElementById('modalTitle').textContent = 'お知らせ作成';
    document.getElementById('editForm').reset();
    document.getElementById('editAnnouncementId').value = '';
    document.getElementById('editIsPublished').checked = true;
    document.getElementById('editDisplayOrder').value = 0;
    document.getElementById('deleteButton').classList.add('hidden');
    document.getElementById('editModal').classList.remove('hidden');
}

// 編集モーダルを開く
async function openEditModal(announcementId) {
    try {
        const announcement = announcements.find(a => a.id === announcementId);
        if (!announcement) {
            showMessage('お知らせが見つかりません', 'error');
            return;
        }

        document.getElementById('modalTitle').textContent = 'お知らせ編集';
        document.getElementById('editAnnouncementId').value = announcement.id;
        document.getElementById('editTitle').value = announcement.title;
        document.getElementById('editAnnouncementDate').value = announcement.announcement_date;
        document.getElementById('editContent').value = announcement.content;
        document.getElementById('editExternalUrl').value = announcement.external_url || '';
        document.getElementById('editPdfFilePath').value = announcement.pdf_file_path || '';
        document.getElementById('editDisplayOrder').value = announcement.display_order || 0;
        document.getElementById('editIsPublished').checked = announcement.is_published;
        document.getElementById('deleteButton').classList.remove('hidden');
        document.getElementById('editModal').classList.remove('hidden');

    } catch (error) {
        console.error('Load error:', error);
        showMessage('エラーが発生しました', 'error');
    }
}

// モーダルを閉じる
function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
    document.getElementById('editForm').reset();
}

// お知らせ保存
async function saveAnnouncement() {
    const id = document.getElementById('editAnnouncementId').value;
    const title = document.getElementById('editTitle').value.trim();
    const announcementDate = document.getElementById('editAnnouncementDate').value;
    const content = document.getElementById('editContent').value.trim();
    const externalUrl = document.getElementById('editExternalUrl').value.trim();
    const pdfFilePath = document.getElementById('editPdfFilePath').value.trim();
    const displayOrder = parseInt(document.getElementById('editDisplayOrder').value) || 0;
    const isPublished = document.getElementById('editIsPublished').checked;

    if (!title) {
        showMessage('タイトルを入力してください', 'error');
        return;
    }
    if (!announcementDate) {
        showMessage('日付を選択してください', 'error');
        return;
    }
    if (!content) {
        showMessage('内容を入力してください', 'error');
        return;
    }

    if (!confirm(id ? 'お知らせを更新しますか？' : 'お知らせを作成しますか？')) {
        return;
    }

    try {
        const url = id ? '../api/admin/update-announcement.php' : '../api/admin/create-announcement.php';
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id: id || undefined,
                title: title,
                announcement_date: announcementDate,
                content: content,
                external_url: externalUrl || null,
                pdf_file_path: pdfFilePath || null,
                display_order: displayOrder,
                is_published: isPublished
            })
        });

        const result = await response.json();

        if (!result.success) {
            showMessage(result.error || '保存に失敗しました', 'error');
            return;
        }

        showMessage(id ? 'お知らせを更新しました' : 'お知らせを作成しました', 'success');
        closeEditModal();
        loadAnnouncements();

    } catch (error) {
        console.error('Save error:', error);
        showMessage('エラーが発生しました', 'error');
    }
}

// お知らせ削除
async function deleteAnnouncement() {
    const id = document.getElementById('editAnnouncementId').value;
    
    if (!id) {
        return;
    }

    if (!confirm('このお知らせを削除しますか？この操作は取り消せません。')) {
        return;
    }

    try {
        const response = await fetch('../api/admin/delete-announcement.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id: id
            })
        });

        const result = await response.json();

        if (!result.success) {
            showMessage(result.error || '削除に失敗しました', 'error');
            return;
        }

        showMessage('お知らせを削除しました', 'success');
        closeEditModal();
        loadAnnouncements();

    } catch (error) {
        console.error('Delete error:', error);
        showMessage('エラーが発生しました', 'error');
    }
}

// 初期読み込み
loadAnnouncements();
</script>

<?php include __DIR__ . '/components/footer.php'; ?>

