<?php
/**
 * 管理画面共通ヘッダー
 */

if (!defined('ADMIN_PAGE')) {
    die('Direct access not permitted');
}

require_once __DIR__ . '/../../lib/AdminAuthHelper.php';

// ログイン確認
AdminAuthHelper::requireLogin();
$admin = AdminAuthHelper::getAdminInfo();

// 現在のページを判定
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? '管理画面'; ?> | Cambridge Exam 管理システム</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Remix Icons -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    
    <!-- Chart.js (ダッシュボード用) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <style>
        /* カスタムスタイル */
        .sidebar-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            color: #374151;
            transition: all 0.2s;
        }
        .sidebar-link:hover {
            background-color: #eff6ff;
            color: #2563eb;
        }
        .sidebar-link.active {
            background-color: #dbeafe;
            color: #1d4ed8;
            font-weight: 600;
        }
        .sidebar-link i {
            font-size: 1.25rem;
            margin-right: 0.75rem;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- トップバー -->
    <header class="bg-white shadow-sm fixed top-0 left-0 right-0 z-50">
        <div class="flex items-center justify-between px-6 py-4">
            <!-- ロゴ -->
            <div class="flex items-center space-x-4">
                <button id="sidebarToggle" class="lg:hidden text-gray-600 hover:text-gray-900">
                    <i class="ri-menu-line text-2xl"></i>
                </button>
                <h1 class="text-xl font-bold text-blue-600">
                    <i class="ri-admin-line"></i>
                    Cambridge Exam 管理システム
                </h1>
            </div>
            
            <!-- ユーザー情報 -->
            <div class="flex items-center space-x-4">
                <!-- 通知アイコン（将来の拡張用） -->
                <button class="text-gray-600 hover:text-gray-900 relative">
                    <i class="ri-notification-3-line text-2xl"></i>
                    <!-- <span class="absolute top-0 right-0 w-2 h-2 bg-red-500 rounded-full"></span> -->
                </button>
                
                <!-- ユーザーメニュー -->
                <div class="flex items-center space-x-3 border-l pl-4">
                    <div class="text-right hidden sm:block">
                        <p class="text-sm font-semibold text-gray-700"><?php echo htmlspecialchars($admin['username']); ?></p>
                        <p class="text-xs text-gray-500">
                            <?php 
                            switch($admin['role']) {
                                case 'super_admin':
                                    echo 'スーパー管理者';
                                    break;
                                case 'admin':
                                    echo '管理者';
                                    break;
                                case 'viewer':
                                    echo '閲覧者';
                                    break;
                                default:
                                    echo htmlspecialchars($admin['role']);
                            }
                            ?>
                        </p>
                    </div>
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white font-semibold">
                        <?php echo strtoupper(substr($admin['username'], 0, 1)); ?>
                    </div>
                    
                    <!-- ドロップダウンメニュー -->
                    <div class="relative">
                        <button id="userMenuBtn" class="text-gray-600 hover:text-gray-900">
                            <i class="ri-arrow-down-s-line text-xl"></i>
                        </button>
                        <div id="userMenu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-2 z-50">
                            <a href="../index.php" target="_blank" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="ri-home-line mr-2"></i>
                                サイトを見る
                            </a>
                            <a href="#" onclick="logout(); return false;" class="flex items-center px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                <i class="ri-logout-box-line mr-2"></i>
                                ログアウト
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="flex pt-16">
        <!-- サイドバー -->
        <aside id="sidebar" class="fixed left-0 top-16 bottom-0 w-64 bg-white shadow-lg transform -translate-x-full lg:translate-x-0 transition-transform duration-300 z-40 overflow-y-auto">
            <nav class="py-4">
                <!-- ダッシュボード -->
                <a href="dashboard.php" class="sidebar-link <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">
                    <i class="ri-dashboard-line"></i>
                    ダッシュボード
                </a>
                
                <!-- 申込管理 -->
                <div class="mt-4">
                    <p class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase">申込管理</p>
                    <a href="applications.php" class="sidebar-link <?php echo $currentPage === 'applications' ? 'active' : ''; ?>">
                        <i class="ri-file-list-3-line"></i>
                        申込一覧
                    </a>
                </div>
                
                <!-- メール管理 -->
                <div class="mt-4">
                    <p class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase">メール管理</p>
                    <a href="email-templates.php" class="sidebar-link <?php echo $currentPage === 'email-templates' ? 'active' : ''; ?>">
                        <i class="ri-mail-settings-line"></i>
                        テンプレート管理
                    </a>
                    <a href="send-email.php" class="sidebar-link <?php echo $currentPage === 'send-email' ? 'active' : ''; ?>">
                        <i class="ri-mail-send-line"></i>
                        一斉メール送信
                    </a>
                    <a href="email-history.php" class="sidebar-link <?php echo $currentPage === 'email-history' ? 'active' : ''; ?>">
                        <i class="ri-history-line"></i>
                        送信履歴
                    </a>
                </div>
                
                <!-- コンテンツ管理 -->
                <div class="mt-4">
                    <p class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase">コンテンツ管理</p>
                    <a href="announcements.php" class="sidebar-link <?php echo $currentPage === 'announcements' ? 'active' : ''; ?>">
                        <i class="ri-notification-line"></i>
                        お知らせ管理
                    </a>
                </div>
                
                <!-- システム -->
                <div class="mt-4">
                    <p class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase">システム</p>
                    <a href="#" class="sidebar-link">
                        <i class="ri-settings-3-line"></i>
                        設定
                    </a>
                </div>
            </nav>
        </aside>

        <!-- メインコンテンツ -->
        <main class="flex-1 lg:ml-64 p-6 md:p-8">
            <!-- ページヘッダー -->
            <?php if (isset($pageTitle)): ?>
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-gray-800"><?php echo $pageTitle; ?></h2>
                <?php if (isset($pageDescription)): ?>
                <p class="text-gray-600 mt-1"><?php echo $pageDescription; ?></p>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- メッセージ表示エリア -->
            <div id="messageArea"></div>

            <!-- ページコンテンツはここから -->

