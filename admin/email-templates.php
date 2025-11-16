<?php
define('ADMIN_PAGE', true);
$pageTitle = 'メールテンプレート管理';
$pageDescription = 'メールテンプレートの閲覧・編集・プレビューができます';

include __DIR__ . '/components/header.php';
?>

<!-- テンプレート一覧 -->
<div class="bg-white rounded-xl shadow-sm">
    <div class="p-6 border-b border-gray-200">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-800">テンプレート一覧</h3>
                <p class="text-sm text-gray-600 mt-1">システムで使用されるメールテンプレートを管理します</p>
            </div>
            <div class="flex items-center space-x-2">
                <input type="text" id="searchInput" placeholder="検索..." 
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
        </div>
    </div>

    <!-- テンプレートリスト -->
    <div id="templatesList" class="divide-y divide-gray-200">
        <!-- JavaScriptで動的に生成 -->
        <div class="p-12 text-center text-gray-400">
            <i class="ri-loader-4-line text-4xl animate-spin"></i>
            <p class="mt-3">読み込み中...</p>
        </div>
    </div>
</div>

<!-- 編集モーダル -->
<div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-hidden flex flex-col">
        <!-- モーダルヘッダー -->
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <div>
                <h3 class="text-xl font-bold text-gray-800">テンプレート編集</h3>
                <p id="modalTemplateName" class="text-sm text-gray-600 mt-1"></p>
            </div>
            <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                <i class="ri-close-line text-2xl"></i>
            </button>
        </div>

        <!-- モーダルコンテンツ -->
        <div class="flex-1 overflow-y-auto p-6">
            <form id="editForm" class="space-y-6">
                <input type="hidden" id="editTemplateId">
                <input type="hidden" id="editTemplateType">

                <!-- 件名 -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        件名 <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="editSubject" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <div class="mt-3 bg-gray-50 border border-gray-200 rounded-lg p-3">
                        <p class="text-xs text-gray-500 mb-2">
                            使用可能な変数（テンプレート種別ごとに変わります）：
                        </p>
                        <div id="variableList" class="flex flex-wrap gap-2 text-xs"></div>
                        <p class="text-[11px] text-gray-400 mt-2">
                            例）本文に <code class="bg-white px-1 py-0.5 rounded border">{{application_number}}</code> と記述すると、申込番号が差し込まれます。
                        </p>
                    </div>
                </div>

                <!-- 送信先選択 -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        送信先
                    </label>
                    <p class="text-xs text-gray-500 mb-3">
                        送信先を1つ以上選択してください（複数選択可能）。選択されたすべての宛先にメールが送信されます。
                    </p>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <label class="flex items-start p-3 border rounded-lg cursor-pointer hover:border-blue-400 transition-colors">
                            <input type="checkbox" id="recipientGuardian" class="mt-1 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            <div class="ml-3">
                                <p class="font-medium text-gray-800">保護者</p>
                                <p class="text-xs text-gray-500">申込時に登録された保護者メールアドレス宛</p>
                            </div>
                        </label>
                        <label class="flex items-start p-3 border rounded-lg cursor-pointer hover:border-blue-400 transition-colors">
                            <input type="checkbox" id="recipientParticipant" class="mt-1 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            <div class="ml-3">
                                <p class="font-medium text-gray-800">参加者本人</p>
                                <p class="text-xs text-gray-500">個人: 申込者本人 / チーム: 操作したメンバー or 代表者</p>
                            </div>
                        </label>
                        <label class="flex items-start p-3 border rounded-lg cursor-pointer hover:border-blue-400 transition-colors">
                            <input type="checkbox" id="recipientTeamMembers" class="mt-1 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            <div class="ml-3">
                                <p class="font-medium text-gray-800">チームメンバー</p>
                                <p class="text-xs text-gray-500">チーム戦の全メンバーに同報送信</p>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- タブ切り替え -->
                <div class="border-b border-gray-200">
                    <div class="flex space-x-4">
                        <button type="button" onclick="switchTab('text')" 
                            id="tabText" 
                            class="px-4 py-2 font-medium border-b-2 border-blue-600 text-blue-600">
                            テキスト版
                        </button>
                        <button type="button" onclick="switchTab('html')" 
                            id="tabHtml" 
                            class="px-4 py-2 font-medium border-b-2 border-transparent text-gray-600 hover:text-gray-800">
                            HTML版
                        </button>
                        <button type="button" onclick="switchTab('preview')" 
                            id="tabPreview" 
                            class="px-4 py-2 font-medium border-b-2 border-transparent text-gray-600 hover:text-gray-800">
                            プレビュー
                        </button>
                    </div>
                </div>

                <!-- テキスト版本文 -->
                <div id="textTab" class="tab-content">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        本文（テキスト版） <span class="text-red-500">*</span>
                    </label>
                    <textarea id="editBodyText" rows="15" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono text-sm"></textarea>
                    <p class="text-xs text-gray-500 mt-1">
                        プレーンテキストメールクライアント向けの本文です
                    </p>
                </div>

                <!-- HTML版本文 -->
                <div id="htmlTab" class="tab-content hidden">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        本文（HTML版）
                    </label>
                    <textarea id="editBodyHtml" rows="15"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono text-sm"></textarea>
                    <p class="text-xs text-gray-500 mt-1">
                        HTMLメール向けの本文です（省略可）
                    </p>
                </div>

                <!-- プレビュー -->
                <div id="previewTab" class="tab-content hidden">
                    <div class="bg-gray-50 border border-gray-300 rounded-lg p-6">
                        <div class="bg-white rounded-lg shadow-sm p-6">
                            <h4 class="font-semibold text-gray-700 mb-2">件名:</h4>
                            <p id="previewSubject" class="text-lg mb-4"></p>
                            
                            <h4 class="font-semibold text-gray-700 mb-2">本文:</h4>
                            <div id="previewBody" class="prose max-w-none whitespace-pre-wrap"></div>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">
                        ※変数は実際の値に置き換えられて表示されます
                    </p>
                </div>

                <!-- ステータス -->
                <div class="flex items-center">
                    <input type="checkbox" id="editIsActive" checked
                        class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <label for="editIsActive" class="ml-2 text-sm font-medium text-gray-700">
                        このテンプレートを有効にする
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
            <button onclick="saveTemplate()" 
                class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors flex items-center">
                <i class="ri-save-line mr-2"></i>
                保存する
            </button>
        </div>
    </div>
</div>

<script>
let templates = [];
let currentTab = 'text';
const RECIPIENT_ORDER = ['guardian', 'participant', 'team_members'];
const recipientCheckboxIds = {
    guardian: 'recipientGuardian',
    participant: 'recipientParticipant',
    team_members: 'recipientTeamMembers'
};
const recipientLabelMap = {
    guardian: '保護者',
    participant: '参加者本人',
    team_members: 'チームメンバー'
};
const templateVariableMap = {
    application_confirmation: ['guardian_name', 'application_number', 'participation_type', 'participant_name', 'amount', 'card_registration_url'],
    card_registration: ['guardian_name', 'application_number', 'card_registration_url'],
    card_registration_completed: ['guardian_name', 'participant_name', 'application_number', 'mypage_url'],
    kyc_required: ['guardian_name', 'application_number', 'mypage_url', 'participant_name'],
    kyc_completed: ['guardian_name', 'participant_name', 'application_number', 'amount'],
    payment_confirmation: ['guardian_name', 'participant_name', 'application_number', 'amount', 'payment_date', 'exam_date', 'mypage_url'],
    payment_failed: ['guardian_name', 'participant_name', 'application_number', 'amount', 'error_message', 'support_email', 'mypage_url'],
    exam_reminder: ['guardian_name', 'application_number', 'exam_date', 'meeting_time', 'venue_name', 'venue_address', 'emergency_contact', 'map_url', 'mypage_url'],
    team_member_payment: ['member_name', 'team_name', 'representative_name', 'amount', 'application_number', 'payment_link', 'deadline'],
    general_announcement: ['guardian_name', 'announcement_title', 'announcement_content', 'mypage_url'],
    schedule_change: ['guardian_name', 'application_number', 'old_date', 'new_date', 'venue_name', 'venue_address', 'change_reason', 'contact_email', 'response_deadline'],
    result_announcement: ['guardian_name', 'application_number', 'mypage_url']
};
const commonVariables = ['website_url', 'mypage_url', 'original_recipient_email'];

function parseRecipientType(value) {
    if (!value) {
        return ['guardian'];
    }

    let normalized = value.trim();
    if (!normalized || normalized === 'custom') {
        return ['guardian'];
    }

    const legacyMap = {
        guardian_and_participant: ['guardian', 'participant'],
        guardian_and_participant_and_team_members: ['guardian', 'participant', 'team_members'],
        guardian_and_team_members: ['guardian', 'team_members'],
        participant_and_team_members: ['participant', 'team_members'],
        student: ['participant']
    };

    if (legacyMap[normalized]) {
        return legacyMap[normalized];
    }

    normalized = normalized.replace(/_and_/g, ',');
    const parts = normalized.split(',').map(part => part.trim()).filter(Boolean);

    const tokens = [];
    parts.forEach(part => {
        const resolved = legacyMap[part] || [part];
        resolved.forEach(token => {
            if (!RECIPIENT_ORDER.includes(token)) {
                return;
            }
            if (!tokens.includes(token)) {
                tokens.push(token);
            }
        });
    });

    if (!tokens.length) {
        return ['guardian'];
    }

    return tokens.sort((a, b) => RECIPIENT_ORDER.indexOf(a) - RECIPIENT_ORDER.indexOf(b));
}

function setRecipientSelection(value) {
    const tokens = parseRecipientType(value);
    RECIPIENT_ORDER.forEach(token => {
        const checkbox = document.getElementById(recipientCheckboxIds[token]);
        if (checkbox) {
            checkbox.checked = tokens.includes(token);
        }
    });
}

function getRecipientSelectionValue() {
    const selected = RECIPIENT_ORDER.filter(token => {
        const checkbox = document.getElementById(recipientCheckboxIds[token]);
        return checkbox ? checkbox.checked : false;
    });

    if (!selected.length) {
        return null;
    }

    return selected.join('_and_');
}

function formatRecipientLabel(value) {
    const tokens = parseRecipientType(value);
    const labels = tokens.map(token => recipientLabelMap[token] || token);
    return labels.join(' + ');
}

function updateVariableList(templateType) {
    const wrapper = document.getElementById('variableList');
    if (!wrapper) return;

    const variables = [...commonVariables, ...(templateVariableMap[templateType] || [])];
    if (!variables.length) {
        wrapper.innerHTML = '<span class="text-gray-400">利用可能な変数情報はありません</span>';
        return;
    }

    wrapper.innerHTML = variables.map(v => `
        <code class="bg-white border px-2 py-1 rounded">${'{{' + v + '}}'}</code>
    `).join('');
}

// テンプレート一覧読み込み
async function loadTemplates() {
    try {
        const response = await fetch('../api/admin/get-email-templates.php');
        const result = await response.json();

        if (!result.success) {
            showMessage(result.error || 'テンプレートの取得に失敗しました', 'error');
            return;
        }

        templates = result.templates;
        renderTemplates();

    } catch (error) {
        console.error('Load error:', error);
        showMessage('エラーが発生しました', 'error');
    }
}

// テンプレート一覧描画
function renderTemplates() {
    const container = document.getElementById('templatesList');
    const searchQuery = document.getElementById('searchInput').value.toLowerCase();

    // 検索フィルター
    const filtered = templates.filter(t => {
        return t.template_name.toLowerCase().includes(searchQuery) ||
               t.subject.toLowerCase().includes(searchQuery) ||
               t.template_type.toLowerCase().includes(searchQuery);
    });

    if (filtered.length === 0) {
        container.innerHTML = `
            <div class="p-12 text-center text-gray-400">
                <i class="ri-inbox-line text-4xl"></i>
                <p class="mt-3">テンプレートが見つかりません</p>
            </div>
        `;
        return;
    }

    // カテゴリ定義
    const categories = {
        'application_flow': {
            label: '📝 申込フロー（自動送信）',
            color: 'blue',
            description: '申込から決済までの自動送信メール'
        },
        'exam_related': {
            label: '📅 試験関連（リマインダー）',
            color: 'purple',
            description: '試験日程に関するメール'
        },
        'announcements': {
            label: '📢 運営からのお知らせ（手動送信）',
            color: 'orange',
            description: '管理画面から手動で送信するメール'
        },
        'post_exam': {
            label: '🏆 試験後',
            color: 'green',
            description: '試験終了後に送信するメール'
        }
    };

    const typeLabels = {
        'application_confirmation': '申込受付確認',
        'card_registration': 'カード登録案内',
        'team_member_payment': 'チームメンバー支払いリンク',
        'kyc_required': '本人確認依頼',
        'kyc_completed': '本人確認完了通知',
        'payment_confirmation': '決済完了通知',
        'payment_failed': '決済エラー通知',
        'exam_reminder': '試験日リマインダー',
        'general_announcement': '汎用お知らせ',
        'schedule_change': '試験日程変更通知',
        'result_announcement': '結果発表通知'
    };

    // カテゴリごとにグループ化
    const grouped = {};
    filtered.forEach(template => {
        const cat = template.category || 'application_flow';
        if (!grouped[cat]) {
            grouped[cat] = [];
        }
        grouped[cat].push(template);
    });

    // sort_orderでソート
    Object.keys(grouped).forEach(cat => {
        grouped[cat].sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0));
    });

    // カテゴリ順
    const categoryOrder = ['application_flow', 'exam_related', 'announcements', 'post_exam'];
    
    let html = '';
    
    categoryOrder.forEach(catKey => {
        if (!grouped[catKey] || grouped[catKey].length === 0) return;
        
        const catInfo = categories[catKey];
        const colorClasses = {
            'blue': 'bg-blue-50 border-blue-200 text-blue-800',
            'purple': 'bg-purple-50 border-purple-200 text-purple-800',
            'orange': 'bg-orange-50 border-orange-200 text-orange-800',
            'green': 'bg-green-50 border-green-200 text-green-800'
        };
        
        html += `
            <div class="border-b-4 ${colorClasses[catInfo.color]} p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold">${catInfo.label}</h3>
                        <p class="text-sm opacity-75 mt-1">${catInfo.description}</p>
                    </div>
                    <span class="px-3 py-1 bg-white rounded-full text-sm font-semibold">${grouped[catKey].length}件</span>
                </div>
            </div>
        `;
        
        html += grouped[catKey].map((template, index) => {
        const updatedDate = new Date(template.updated_at || template.created_at);
        const dateStr = `${updatedDate.getFullYear()}/${updatedDate.getMonth() + 1}/${updatedDate.getDate()}`;

        return `
            <div class="p-6 hover:bg-gray-50 transition-colors border-l-4 border-transparent hover:border-${catInfo.color}-400">
                <div class="flex items-start justify-between">
                    <div class="flex items-start space-x-4 flex-1">
                        <!-- 順番表示 -->
                        <div class="flex-shrink-0 w-10 h-10 bg-${catInfo.color}-100 text-${catInfo.color}-700 rounded-full flex items-center justify-center font-bold text-lg">
                            ${index + 1}
                        </div>
                        
                        <div class="flex-1">
                            <div class="flex items-center space-x-3 mb-2">
                                <h4 class="text-lg font-semibold text-gray-800">${template.template_name}</h4>
                                <span class="px-2 py-1 text-xs font-medium rounded ${template.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'}">
                                    ${template.is_active ? '有効' : '無効'}
                                </span>
                                <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-700 rounded">
                                    ${typeLabels[template.template_type] || template.template_type}
                                </span>
                            </div>
                            <p class="text-sm text-gray-600 mb-3">
                                <i class="ri-mail-line mr-1"></i>
                                件名: <strong>${template.subject}</strong>
                            </p>
                            <p class="text-xs text-gray-500 mb-1">
                                宛先: <strong>${formatRecipientLabel(template.recipient_type)}</strong>
                            </p>
                            <p class="text-xs text-gray-500">
                                最終更新: ${dateStr}
                            </p>
                        </div>
                    </div>
                    <button onclick="openEditModal('${template.id}')" 
                        class="ml-4 px-4 py-2 bg-blue-50 hover:bg-blue-100 text-blue-600 rounded-lg font-medium transition-colors flex items-center">
                        <i class="ri-edit-line mr-2"></i>
                        編集
                    </button>
                </div>
            </div>
        `;
    }).join('');
    });
    
    container.innerHTML = html;
}

// 編集モーダルを開く
async function openEditModal(templateId) {
    try {
        const response = await fetch(`../api/admin/get-email-template.php?id=${templateId}`);
        const result = await response.json();

        if (!result.success) {
            showMessage(result.error || 'テンプレートの取得に失敗しました', 'error');
            return;
        }

        const template = result.template;

        // フォームに値を設定
        document.getElementById('editTemplateId').value = template.id;
        document.getElementById('editTemplateType').value = template.template_type;
        document.getElementById('editSubject').value = template.subject;
        document.getElementById('editBodyText').value = template.body_text || '';
        document.getElementById('editBodyHtml').value = template.body_html || '';
        document.getElementById('editIsActive').checked = template.is_active;
        updateVariableList(template.template_type);
        setRecipientSelection(template.recipient_type);
        document.getElementById('modalTemplateName').textContent = template.template_name;

        // モーダル表示
        document.getElementById('editModal').classList.remove('hidden');
        switchTab('text');
        updatePreview();

    } catch (error) {
        console.error('Load error:', error);
        showMessage('エラーが発生しました', 'error');
    }
}

// 編集モーダルを閉じる
function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
    document.getElementById('editForm').reset();
}

// タブ切り替え
function switchTab(tab) {
    currentTab = tab;

    // タブボタンのスタイル更新
    ['text', 'html', 'preview'].forEach(t => {
        const tabBtn = document.getElementById(`tab${t.charAt(0).toUpperCase() + t.slice(1)}`);
        const tabContent = document.getElementById(`${t}Tab`);
        
        if (t === tab) {
            tabBtn.classList.add('border-blue-600', 'text-blue-600');
            tabBtn.classList.remove('border-transparent', 'text-gray-600');
            tabContent.classList.remove('hidden');
        } else {
            tabBtn.classList.remove('border-blue-600', 'text-blue-600');
            tabBtn.classList.add('border-transparent', 'text-gray-600');
            tabContent.classList.add('hidden');
        }
    });

    if (tab === 'preview') {
        updatePreview();
    }
}

// プレビュー更新
function updatePreview() {
    const subject = document.getElementById('editSubject').value;
    const bodyText = document.getElementById('editBodyText').value;

    // サンプルデータで変数を置き換え
    const sampleData = {
        '{{application_number}}': 'CAMB2024-001',
        '{{student_name}}': '山田太郎',
        '{{team_name}}': 'チームα',
        '{{guardian_name}}': '山田花子',
        '{{amount}}': '5,000',
        '{{exam_date}}': '2024年12月15日',
        '{{card_registration_url}}': 'https://example.com/card-registration'
    };

    let previewSubject = subject;
    let previewBody = bodyText;

    Object.entries(sampleData).forEach(([key, value]) => {
        previewSubject = previewSubject.replace(new RegExp(key, 'g'), value);
        previewBody = previewBody.replace(new RegExp(key, 'g'), value);
    });

    document.getElementById('previewSubject').textContent = previewSubject;
    document.getElementById('previewBody').textContent = previewBody;
}

// テンプレート保存
async function saveTemplate() {
    const templateId = document.getElementById('editTemplateId').value;
    const subject = document.getElementById('editSubject').value.trim();
    const bodyText = document.getElementById('editBodyText').value.trim();
    const bodyHtml = document.getElementById('editBodyHtml').value.trim();
    const isActive = document.getElementById('editIsActive').checked;
    const recipientType = getRecipientSelectionValue();

    if (!subject) {
        showMessage('件名を入力してください', 'error');
        return;
    }

    if (!bodyText) {
        showMessage('本文（テキスト版）を入力してください', 'error');
        return;
    }

    if (!recipientType) {
        showMessage('送信先を1つ以上選択してください', 'error');
        return;
    }

    if (!confirm('テンプレートを更新しますか？')) {
        return;
    }

    try {
        const response = await fetch('../api/admin/update-email-template.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id: templateId,
                subject: subject,
                body_text: bodyText,
                body_html: bodyHtml,
                is_active: isActive,
                recipient_type: recipientType
            })
        });

        const result = await response.json();

        if (!result.success) {
            showMessage(result.error || '保存に失敗しました', 'error');
            return;
        }

        showMessage('テンプレートを更新しました', 'success');
        closeEditModal();
        loadTemplates();

    } catch (error) {
        console.error('Save error:', error);
        showMessage('エラーが発生しました', 'error');
    }
}

// 検索
document.getElementById('searchInput').addEventListener('input', renderTemplates);

// 初期読み込み
loadTemplates();
</script>

<?php include __DIR__ . '/components/footer.php'; ?>

