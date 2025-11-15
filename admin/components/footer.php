        </main>
    </div>

    <!-- サイドバーオーバーレイ（モバイル用） -->
    <div id="sidebarOverlay" class="hidden fixed inset-0 bg-black bg-opacity-50 z-30 lg:hidden"></div>

    <!-- 共通JavaScript -->
    <script>
        // サイドバートグル
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        function toggleSidebar() {
            sidebar.classList.toggle('-translate-x-full');
            sidebarOverlay.classList.toggle('hidden');
        }

        sidebarToggle.addEventListener('click', toggleSidebar);
        sidebarOverlay.addEventListener('click', toggleSidebar);

        // ユーザーメニュートグル
        const userMenuBtn = document.getElementById('userMenuBtn');
        const userMenu = document.getElementById('userMenu');

        userMenuBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            userMenu.classList.toggle('hidden');
        });

        // メニュー外クリックで閉じる
        document.addEventListener('click', (e) => {
            if (!userMenu.contains(e.target) && !userMenuBtn.contains(e.target)) {
                userMenu.classList.add('hidden');
            }
        });

        // ログアウト処理
        async function logout() {
            if (!confirm('ログアウトしますか？')) {
                return;
            }

            try {
                const response = await fetch('../api/admin/logout.php', {
                    method: 'POST'
                });

                const result = await response.json();

                if (result.success) {
                    window.location.href = 'login.php';
                } else {
                    alert('ログアウトに失敗しました');
                }
            } catch (error) {
                console.error('Logout error:', error);
                alert('エラーが発生しました');
            }
        }

        // メッセージ表示ヘルパー
        function showMessage(message, type = 'success') {
            const messageArea = document.getElementById('messageArea');
            const icons = {
                success: 'ri-checkbox-circle-line',
                error: 'ri-error-warning-line',
                warning: 'ri-alert-line',
                info: 'ri-information-line'
            };
            const colors = {
                success: 'bg-green-50 text-green-800 border-green-200',
                error: 'bg-red-50 text-red-800 border-red-200',
                warning: 'bg-yellow-50 text-yellow-800 border-yellow-200',
                info: 'bg-blue-50 text-blue-800 border-blue-200'
            };

            const messageDiv = document.createElement('div');
            messageDiv.className = `mb-4 p-4 rounded-lg border ${colors[type]} flex items-center animate-fade-in`;
            messageDiv.innerHTML = `
                <i class="${icons[type]} text-xl mr-3"></i>
                <span>${message}</span>
                <button onclick="this.parentElement.remove()" class="ml-auto text-xl hover:opacity-70">
                    <i class="ri-close-line"></i>
                </button>
            `;

            messageArea.appendChild(messageDiv);

            // 5秒後に自動削除
            setTimeout(() => {
                messageDiv.remove();
            }, 5000);
        }

        // URLパラメータからメッセージを取得
        const urlParams = new URLSearchParams(window.location.search);
        const successMsg = urlParams.get('success');
        const errorMsg = urlParams.get('error');

        if (successMsg) {
            showMessage(decodeURIComponent(successMsg), 'success');
        }
        if (errorMsg) {
            showMessage(decodeURIComponent(errorMsg), 'error');
        }

        // セッションタイムアウトチェック（30分）
        let lastActivity = Date.now();
        const SESSION_TIMEOUT = 30 * 60 * 1000; // 30分

        function checkSessionTimeout() {
            if (Date.now() - lastActivity > SESSION_TIMEOUT) {
                alert('セッションがタイムアウトしました。再度ログインしてください。');
                window.location.href = 'login.php';
            }
        }

        // アクティビティ監視
        ['mousedown', 'keydown', 'scroll', 'touchstart'].forEach(event => {
            document.addEventListener(event, () => {
                lastActivity = Date.now();
            });
        });

        // 1分ごとにチェック
        setInterval(checkSessionTimeout, 60000);
    </script>

    <style>
        @keyframes fade-in {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .animate-fade-in {
            animation: fade-in 0.3s ease-out;
        }
    </style>
</body>
</html>

