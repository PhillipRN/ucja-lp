<?php
/**
 * Application Complete Page
 * 申込完了画面
 */

// sessionStorageから情報を取得するため、JavaScriptで処理
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- 検索エンジンインデックス防止（Stripe審査中） -->
    <meta name="robots" content="noindex, nofollow">
    <meta name="googlebot" content="noindex, nofollow">
    
    <title>申込完了 - UNIV.CAMBRIDGE JAPAN ACADEMY</title>
    
    <!-- Remix Icon CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.5.0/remixicon.min.css">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="antialiased bg-gradient-to-br from-green-50 via-blue-50 to-purple-50">

    <?php include 'components/header.php'; ?>

    <div class="min-h-screen flex items-center justify-center px-6 py-20">
        <div class="max-w-3xl w-full">
            
            <!-- 成功アニメーション -->
            <div class="text-center mb-8">
                <div class="inline-block relative">
                    <div class="w-32 h-32 bg-gradient-to-br from-green-400 to-emerald-500 rounded-full flex items-center justify-center mx-auto mb-6 shadow-2xl animate-bounce">
                        <i class="ri-checkbox-circle-fill text-white text-7xl"></i>
                    </div>
                    <div class="absolute inset-0 w-32 h-32 bg-green-400 rounded-full mx-auto animate-ping opacity-20"></div>
                </div>
            </div>

            <!-- メインメッセージ -->
            <div class="bg-white rounded-3xl shadow-2xl overflow-hidden mb-8">
                <div class="bg-gradient-to-r from-green-600 to-emerald-600 px-8 py-6">
                    <h1 class="text-3xl font-bold text-white text-center">
                        申込を受け付けました！
                    </h1>
                    <p class="text-green-100 text-center mt-2">Application Submitted Successfully</p>
                </div>

                <div class="p-8">
                    <div class="text-center mb-8">
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">UNIV.CAMBRIDGE JAPAN ACADEMY 英単語高校選手権へのお申し込みありがとうございます</h2>
                        <p class="text-gray-600 text-lg">
                            以下の情報でマイページにログインできます。<br>
                            大切に保管してください。
                        </p>
                    </div>

                    <!-- ログイン情報カード -->
                    <div class="bg-gradient-to-r from-blue-50 to-purple-50 border-2 border-blue-200 rounded-xl p-8 mb-8">
                        <h3 class="font-bold text-gray-900 mb-6 flex items-center justify-center text-xl">
                            <i class="ri-key-line text-blue-600 mr-2"></i>
                            マイページログイン情報
                        </h3>
                        
                        <div class="space-y-6">
                            <!-- 申込番号 -->
                            <div class="bg-white rounded-lg p-6 shadow-md">
                                <div class="text-sm text-gray-600 mb-2">申込番号</div>
                                <div class="font-mono font-bold text-2xl text-blue-600" id="display-application-number">
                                    読み込み中...
                                </div>
                                <button
                                    onclick="copyToClipboard('application_number')"
                                    class="mt-3 inline-flex items-center text-sm text-blue-600 hover:text-blue-700"
                                >
                                    <i class="ri-file-copy-line mr-1"></i>
                                    コピー
                                </button>
                            </div>

                            <!-- メールアドレス -->
                            <div class="bg-white rounded-lg p-6 shadow-md">
                                <div class="text-sm text-gray-600 mb-2">ログイン用メールアドレス</div>
                                <div class="font-semibold text-lg text-gray-900" id="display-email">
                                    読み込み中...
                                </div>
                                <div class="text-xs text-gray-500 mt-2">
                                    申込時に入力されたメールアドレス
                                </div>
                            </div>

                            <!-- 参加形式 -->
                            <div class="bg-white rounded-lg p-6 shadow-md">
                                <div class="text-sm text-gray-600 mb-2">参加形式</div>
                                <div class="font-semibold text-lg text-gray-900" id="display-participation-type">
                                    読み込み中...
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <p class="text-sm text-gray-700">
                                <i class="ri-information-line text-yellow-600 mr-1"></i>
                                <strong>重要：</strong>この情報は今後マイページへのログインに必要です。スクリーンショットを撮るか、メモをとって大切に保管してください。
                            </p>
                        </div>
                    </div>

                    <!-- 次のステップ -->
                    <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
                        <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                            <i class="ri-arrow-right-circle-line text-blue-600 mr-2"></i>
                            次のステップ
                        </h3>
                        <div class="space-y-4">
                            <div class="flex items-start">
                                <div class="w-8 h-8 bg-green-600 text-white rounded-full flex items-center justify-center font-bold mr-4 flex-shrink-0">1</div>
                                <div>
                                    <div class="font-semibold text-gray-900 mb-1 flex items-center">
                                        クレジットカード情報の登録
                                        <span class="ml-2 bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full">今すぐ</span>
                                    </div>
                                    <div class="text-sm text-gray-700">安全にカード情報を登録します（この時点では課金されません）。</div>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <div class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold mr-4 flex-shrink-0">2</div>
                                <div>
                                    <div class="font-semibold text-gray-900 mb-1 flex items-center">
                                        本人確認の実施
                                        <span class="ml-2 bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full">後日実施</span>
                                    </div>
                                    <div class="text-sm text-gray-700">学生証による本人確認を完了させてください（マイページから実施可能）。</div>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <div class="w-8 h-8 bg-purple-600 text-white rounded-full flex items-center justify-center font-bold mr-4 flex-shrink-0">✓</div>
                                <div>
                                    <div class="font-semibold text-gray-900 mb-1">自動決済・申込完了</div>
                                    <div class="text-sm text-gray-700">本人確認完了後、自動的に決済が実行され、受験票をメールでお送りします。</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ボタン -->
                    <div class="text-center space-y-4">
                        <!-- カード登録ボタン（メイン） -->
                        <a
                            href="stripe-checkout-setup.php"
                            class="inline-flex items-center bg-gradient-to-r from-green-600 to-emerald-600 text-white px-12 py-4 rounded-full text-lg font-semibold hover:from-green-700 hover:to-emerald-700 transition-all shadow-lg hover:shadow-xl"
                        >
                            <i class="ri-bank-card-line text-2xl mr-2"></i>
                            今すぐカード情報を登録する
                        </a>
                        
                        <div class="text-sm text-gray-600">
                            または
                        </div>
                        
                        <!-- マイページログインボタン（サブ） -->
                        <a
                            href="login.php"
                            class="inline-flex items-center bg-gradient-to-r from-blue-600 to-purple-600 text-white px-12 py-4 rounded-full text-lg font-semibold hover:from-blue-700 hover:to-purple-700 transition-all shadow-lg hover:shadow-xl"
                        >
                            <i class="ri-login-circle-line text-2xl mr-2"></i>
                            マイページにログイン
                        </a>
                        
                        <div class="mt-4">
                            <a
                                href="index.php"
                                class="inline-flex items-center text-gray-600 hover:text-gray-900 transition-colors"
                            >
                                <i class="ri-home-line mr-2"></i>
                                トップページに戻る
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 確認事項 -->
            <div class="bg-white rounded-xl p-6 shadow-lg">
                <h3 class="font-bold text-gray-900 mb-3 flex items-center">
                    <i class="ri-information-line text-blue-600 mr-2"></i>
                    ご確認ください
                </h3>
                <ul class="text-sm text-gray-700 space-y-2">
                    <li>• カード登録は今すぐこのページから実施できます</li>
                    <li>• マイページで申込状況・支払い状況をいつでも確認できます</li>
                    <li>• 本人確認は後日、マイページから実施してください</li>
                    <li>• ご不明な点がございましたら、お気軽にお問い合わせください</li>
                    <li>• お問い合わせ: <a href="mailto:contact@univ-cambridge-japan.academy" class="text-blue-600 hover:underline">contact@univ-cambridge-japan.academy</a></li>
                </ul>
            </div>

        </div>
    </div>

    <?php include 'components/footer.php'; ?>

    <script>
    // sessionStorageから申込情報を取得
    const applicationNumber = sessionStorage.getItem('application_number');
    const email = sessionStorage.getItem('application_email');
    const participationType = sessionStorage.getItem('participation_type');

    // 情報が存在しない場合はトップページにリダイレクト
    if (!applicationNumber || !email) {
        alert('申込情報が見つかりません。トップページに戻ります。');
        window.location.href = 'index.php';
    } else {
        // 情報を表示
        document.getElementById('display-application-number').textContent = applicationNumber;
        document.getElementById('display-email').textContent = email;
        
        const participationTypeText = participationType === 'individual' ? '個人戦' : 'チーム戦';
        document.getElementById('display-participation-type').textContent = participationTypeText;
    }

    // コピー機能
    function copyToClipboard(type) {
        let textToCopy = '';
        if (type === 'application_number') {
            textToCopy = applicationNumber;
        }
        
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(textToCopy).then(() => {
                showCopyNotification();
            });
        } else {
            // フォールバック
            const textArea = document.createElement('textarea');
            textArea.value = textToCopy;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            document.body.appendChild(textArea);
            textArea.select();
            try {
                document.execCommand('copy');
                showCopyNotification();
            } catch (err) {
                console.error('コピーに失敗しました:', err);
            }
            document.body.removeChild(textArea);
        }
    }

    function showCopyNotification() {
        // 簡易的な通知表示
        const notification = document.createElement('div');
        notification.className = 'fixed top-4 right-4 bg-green-600 text-white px-6 py-3 rounded-lg shadow-lg z-50';
        notification.innerHTML = '<i class="ri-check-line mr-2"></i>コピーしました';
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 2000);
    }
    </script>

</body>
</html>

