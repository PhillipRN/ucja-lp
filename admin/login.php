<?php
/**
 * Admin Login Page
 * 管理者ログイン画面
 */

require_once __DIR__ . '/../lib/AdminAuthHelper.php';

// 既にログイン済みの場合はダッシュボードへ
if (AdminAuthHelper::isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理者ログイン - UCJA 管理画面</title>
    
    <!-- Remix Icon CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.5.0/remixicon.min.css">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="antialiased bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 min-h-screen flex items-center justify-center">

    <div class="w-full max-w-md px-6">
        
        <!-- ロゴ -->
        <div class="text-center mb-8">
            <div class="inline-block bg-white rounded-2xl p-4 shadow-lg mb-4">
                <i class="ri-shield-user-line text-5xl text-blue-600"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-900">UCJA 管理画面</h1>
            <p class="text-gray-600 mt-2">University of Cambridge Japan Academy</p>
        </div>

        <!-- ログインフォーム -->
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6 text-center">管理者ログイン</h2>
            
            <!-- エラーメッセージ -->
            <div id="error-message" class="hidden mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                <div class="flex items-start">
                    <i class="ri-error-warning-line text-red-600 text-xl mr-2"></i>
                    <p id="error-text" class="text-sm text-red-800"></p>
                </div>
            </div>

            <form id="login-form">
                <!-- メールアドレス -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="ri-mail-line mr-1"></i>
                        メールアドレス
                    </label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                        placeholder="admin@example.com"
                    />
                </div>

                <!-- パスワード -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="ri-lock-line mr-1"></i>
                        パスワード
                    </label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                        placeholder="••••••••"
                    />
                </div>

                <!-- ログインボタン -->
                <button
                    type="submit"
                    id="login-button"
                    class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 text-white py-3 rounded-lg font-semibold hover:from-blue-700 hover:to-indigo-700 transition-all shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <span id="button-text" class="inline-flex items-center justify-center">
                        <i class="ri-login-circle-line mr-2"></i>
                        ログイン
                    </span>
                    <span id="button-spinner" class="hidden">
                        <i class="ri-loader-4-line animate-spin mr-2"></i>
                        処理中...
                    </span>
                </button>
            </form>

            <!-- セキュリティ情報 -->
            <div class="mt-6 text-center text-xs text-gray-500">
                <i class="ri-shield-check-line text-green-600 mr-1"></i>
                SSL暗号化通信で保護されています
            </div>
        </div>

        <!-- フッター -->
        <div class="text-center mt-6 text-sm text-gray-600">
            <p>&copy; 2025 University of Cambridge Japan Academy</p>
        </div>
    </div>

    <script>
    const form = document.getElementById('login-form');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const loginButton = document.getElementById('login-button');
    const buttonText = document.getElementById('button-text');
    const buttonSpinner = document.getElementById('button-spinner');
    const errorMessage = document.getElementById('error-message');
    const errorText = document.getElementById('error-text');

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        // エラーメッセージをクリア
        errorMessage.classList.add('hidden');

        // ボタンを無効化
        loginButton.disabled = true;
        buttonText.classList.add('hidden');
        buttonSpinner.classList.remove('hidden');

        try {
            const response = await fetch('../api/admin/login.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    email: emailInput.value,
                    password: passwordInput.value
                })
            });

            const data = await response.json();

            if (data.success) {
                // ログイン成功 - ダッシュボードへリダイレクト
                window.location.href = 'dashboard.php';
            } else {
                // ログイン失敗
                throw new Error(data.error || 'ログインに失敗しました');
            }

        } catch (error) {
            // エラー表示
            errorText.textContent = error.message;
            errorMessage.classList.remove('hidden');

            // ボタンを再有効化
            loginButton.disabled = false;
            buttonText.classList.remove('hidden');
            buttonSpinner.classList.add('hidden');

            // パスワードフィールドをクリア
            passwordInput.value = '';
            passwordInput.focus();
        }
    });

    // Enterキーでログイン
    passwordInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            form.dispatchEvent(new Event('submit'));
        }
    });
    </script>

</body>
</html>

