<?php
/**
 * Login Page
 * ログイン画面
 */

require_once __DIR__ . '/lib/AuthHelper.php';

// 既にログインしている場合はダッシュボードにリダイレクト
if (AuthHelper::isLoggedIn()) {
    header('Location: my-page/dashboard.php');
    exit;
}

// ログアウト成功メッセージ
$logoutSuccess = isset($_GET['logout']) && $_GET['logout'] === 'success';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- 検索エンジンインデックス防止（Stripe審査中） -->
    <meta name="robots" content="noindex, nofollow">
    <meta name="googlebot" content="noindex, nofollow">
    
    <title>ログイン - UNIV.Cambridge Japan Academy</title>
    
    <!-- Remix Icon CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.5.0/remixicon.min.css">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="antialiased bg-gradient-to-br from-blue-50 via-purple-50 to-pink-50">

    <div class="min-h-screen flex items-center justify-center px-6 py-12">
        <div class="max-w-md w-full">
            
            <!-- ロゴ・タイトル -->
            <div class="text-center mb-8">
                <a href="index.php" class="inline-block">
                    <img src="images/UCJA_Academy_logo_fin.png" alt="Cambridge Logo" class="h-16 mx-auto mb-4">
                </a>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">マイページログイン</h1>
                <p class="text-gray-600">申込情報の確認・管理</p>
            </div>

            <!-- ログアウト成功メッセージ -->
            <?php if ($logoutSuccess): ?>
            <div class="mb-6 bg-green-50 border border-green-200 rounded-xl p-4">
                <div class="flex items-center text-green-800">
                    <i class="ri-check-line text-xl mr-2"></i>
                    <span class="font-medium">ログアウトしました</span>
                </div>
            </div>
            <?php endif; ?>

            <!-- ログインフォーム -->
            <div class="bg-white rounded-3xl shadow-2xl overflow-hidden">
                <div class="bg-gradient-to-r from-blue-600 to-purple-600 px-8 py-6">
                    <h2 class="text-xl font-bold text-white text-center flex items-center justify-center">
                        <i class="ri-login-circle-line mr-2"></i>
                        ログイン
                    </h2>
                </div>

                <div class="p-8">
                    <!-- エラーメッセージ -->
                    <div id="error-message" class="hidden mb-6 bg-red-50 border border-red-200 rounded-xl p-4">
                        <div class="flex items-start text-red-800">
                            <i class="ri-error-warning-line text-xl mr-2 mt-0.5"></i>
                            <div>
                                <div class="font-medium mb-1">ログインエラー</div>
                                <div id="error-text" class="text-sm"></div>
                            </div>
                        </div>
                    </div>

                    <form id="login-form" method="POST">
                        <!-- メールアドレス -->
                        <div class="mb-6">
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="ri-mail-line mr-1"></i>
                                メールアドレス
                            </label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="example@email.com"
                            >
                            <p class="text-xs text-gray-500 mt-2">
                                申込時に入力されたメールアドレス（生徒または保護者）
                            </p>
                        </div>

                        <!-- 申込番号 -->
                        <div class="mb-6">
                            <label for="application_number" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="ri-file-list-line mr-1"></i>
                                申込番号
                            </label>
                            <input
                                type="text"
                                id="application_number"
                                name="application_number"
                                required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="例: 20260104-ABCD1234"
                            >
                            <p class="text-xs text-gray-500 mt-2">
                                申込完了時に発行された申込番号
                            </p>
                        </div>

                        <!-- ログインボタン -->
                        <button
                            type="submit"
                            id="login-button"
                            class="w-full bg-gradient-to-r from-blue-600 to-purple-600 text-white py-4 rounded-lg font-semibold text-lg hover:from-blue-700 hover:to-purple-700 transition-all shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span id="button-text">
                                <i class="ri-login-circle-line mr-2"></i>
                                ログイン
                            </span>
                            <span id="button-spinner" class="hidden">
                                <i class="ri-loader-4-line animate-spin mr-2"></i>
                                ログイン中...
                            </span>
                        </button>
                    </form>

                    <!-- 補足情報 -->
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <div class="bg-blue-50 rounded-xl p-4">
                            <h3 class="font-semibold text-gray-900 mb-2 flex items-center text-sm">
                                <i class="ri-information-line text-blue-600 mr-2"></i>
                                ログインについて
                            </h3>
                            <ul class="text-xs text-gray-700 space-y-1">
                                <li>• 申込番号は申込完了時のメールに記載されています</li>
                                <li>• メールアドレスは申込時に入力されたものをご使用ください</li>
                                <li>• ログイン情報がわからない場合はお問い合わせください</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- トップに戻る -->
            <div class="mt-6 text-center">
                <a
                    href="index.php"
                    class="inline-flex items-center text-gray-600 hover:text-gray-900 transition-colors"
                >
                    <i class="ri-arrow-left-line mr-1"></i>
                    トップページに戻る
                </a>
            </div>

            <!-- お問い合わせ -->
            <div class="mt-6 text-center text-sm text-gray-600">
                <p>
                    お困りの際は
                    <a href="mailto:contact@univ-cambridge-japan.academy" class="text-blue-600 hover:underline">
                        contact@univ-cambridge-japan.academy
                    </a>
                    までお問い合わせください
                </p>
            </div>
        </div>
    </div>

    <script>
    const form = document.getElementById('login-form');
    const errorMessage = document.getElementById('error-message');
    const errorText = document.getElementById('error-text');
    const loginButton = document.getElementById('login-button');
    const buttonText = document.getElementById('button-text');
    const buttonSpinner = document.getElementById('button-spinner');

    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        // エラーメッセージを非表示
        errorMessage.classList.add('hidden');

        // ローディング状態に
        loginButton.disabled = true;
        buttonText.classList.add('hidden');
        buttonSpinner.classList.remove('hidden');

        // フォームデータを取得
        const formData = new FormData(form);

        try {
            const response = await fetch('api/auth/login.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                // ログイン成功 - ダッシュボードにリダイレクト
                window.location.href = 'my-page/dashboard.php';
            } else {
                // エラー表示
                errorText.textContent = data.error || 'ログインに失敗しました';
                errorMessage.classList.remove('hidden');
                
                // ボタンを元に戻す
                loginButton.disabled = false;
                buttonText.classList.remove('hidden');
                buttonSpinner.classList.add('hidden');
            }
        } catch (error) {
            console.error('Error:', error);
            errorText.textContent = '通信エラーが発生しました。もう一度お試しください。';
            errorMessage.classList.remove('hidden');
            
            // ボタンを元に戻す
            loginButton.disabled = false;
            buttonText.classList.remove('hidden');
            buttonSpinner.classList.add('hidden');
        }
    });
    </script>

</body>
</html>

