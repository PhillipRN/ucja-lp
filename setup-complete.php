<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- 検索エンジンインデックス防止（Stripe審査中） -->
    <meta name="robots" content="noindex, nofollow">
    <meta name="googlebot" content="noindex, nofollow">
    
    <title>クレジットカード登録完了 - Cambridge English Challenge</title>
    
    <!-- Remix Icon CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.5.0/remixicon.min.css">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="antialiased bg-gradient-to-br from-blue-50 to-purple-50">

    <div class="min-h-screen flex items-center justify-center px-4 py-12">
        <div class="max-w-2xl w-full">
            
            <!-- メインカード -->
            <div class="bg-white rounded-2xl shadow-2xl p-8 md:p-12 text-center">
                
                <!-- チェックマークアイコン -->
                <div class="flex justify-center mb-6">
                    <div class="w-20 h-20 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center shadow-lg animate-bounce">
                        <i class="ri-check-line text-white text-4xl"></i>
                    </div>
                </div>

                <!-- タイトル -->
                <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                    クレジットカード情報の登録が<br>完了しました！
                </h1>

                <!-- サブタイトル -->
                <p class="text-lg text-gray-600 mb-8">
                    ご登録いただきありがとうございます
                </p>

                <!-- 申込番号 -->
                <div class="bg-blue-50 rounded-xl p-6 mb-8">
                    <div class="text-sm text-gray-600 mb-2">申込番号</div>
                    <div class="font-mono text-2xl font-bold text-blue-600" id="application-number">-</div>
                    <p class="text-xs text-gray-500 mt-2">
                        ※ この番号はマイページログイン時に必要です
                    </p>
                </div>

                <!-- 次のステップ -->
                <div class="bg-yellow-50 border-l-4 border-yellow-500 rounded-lg p-6 mb-8 text-left">
                    <div class="flex items-start">
                        <i class="ri-time-line text-yellow-600 text-2xl mr-3 mt-1"></i>
                        <div>
                            <h3 class="font-bold text-yellow-900 text-lg mb-2">次のステップ</h3>
                            <ol class="space-y-2 text-sm text-yellow-800">
                                <li class="flex items-start">
                                    <span class="font-bold mr-2">1.</span>
                                    <span>本人確認（eKYC）を完了してください</span>
                                </li>
                                <li class="flex items-start">
                                    <span class="font-bold mr-2">2.</span>
                                    <span>本人確認完了後、クレジットカード決済が実行されます</span>
                                </li>
                                <li class="flex items-start">
                                    <span class="font-bold mr-2">3.</span>
                                    <span>決済完了後、受験情報がメールで送付されます</span>
                                </li>
                            </ol>
                        </div>
                    </div>
                </div>

                <!-- 重要な注意事項 -->
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-8 text-left">
                    <div class="flex items-start">
                        <i class="ri-error-warning-line text-red-600 text-xl mr-3 mt-0.5"></i>
                        <div>
                            <h4 class="font-bold text-red-900 text-sm mb-1">重要な注意事項</h4>
                            <ul class="text-xs text-red-800 space-y-1">
                                <li>• 本人確認が完了していない場合、決済は実行されません</li>
                                <li>• クレジットカード情報は安全に保管され、本人確認完了時にのみ使用されます</li>
                                <li>• 登録したクレジットカードは後日マイページから変更可能です</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- ナビゲーションボタン -->
                <div class="space-y-4">
                    <a
                        href="my-page/dashboard.php"
                        class="block w-full bg-gradient-to-r from-blue-600 to-purple-600 text-white px-8 py-4 rounded-full text-lg font-semibold hover:from-blue-700 hover:to-purple-700 transition-all shadow-lg hover:shadow-xl"
                    >
                        <i class="ri-dashboard-line mr-2"></i>
                        マイページへ
                    </a>
                    
                    <a
                        href="index.php"
                        class="block w-full bg-gray-200 text-gray-700 px-8 py-4 rounded-full text-lg font-semibold hover:bg-gray-300 transition-all"
                    >
                        <i class="ri-home-line mr-2"></i>
                        トップページへ
                    </a>
                </div>

                <!-- サポート情報 -->
                <div class="mt-8 pt-8 border-t border-gray-200">
                    <p class="text-sm text-gray-600 mb-2">お困りの際はお気軽にお問い合わせください</p>
                    <a href="mailto:contact@univ-cambridge-japan.academy" class="text-blue-600 hover:underline text-sm">
                        contact@univ-cambridge-japan.academy
                    </a>
                </div>
            </div>

            <!-- フッター情報 -->
            <div class="text-center mt-6 text-sm text-gray-600">
                <p>このメールアドレスとパスワード（申込番号）は大切に保管してください</p>
            </div>
        </div>
    </div>

    <script>
    // SessionStorageから申込番号を取得して表示
    const applicationNumber = sessionStorage.getItem('application_number');
    if (applicationNumber) {
        document.getElementById('application-number').textContent = applicationNumber;
    }
    </script>

</body>
</html>

