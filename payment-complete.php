<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- 検索エンジンインデックス防止（Stripe審査中） -->
    <meta name="robots" content="noindex, nofollow">
    <meta name="googlebot" content="noindex, nofollow">
    
    <title>決済完了 - UNIV.Cambridge Japan Academy</title>
    
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
                        決済が完了しました！
                    </h1>
                    <p class="text-green-100 text-center mt-2">Payment Successful</p>
                </div>

                <div class="p-8">
                    <div class="text-center mb-8">
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">申込が完了しました</h2>
                        <p class="text-gray-600 text-lg">
                            英語検定 2026へのお申し込みありがとうございます。<br>
                            受験票と詳細案内を3営業日以内にメールでお送りいたします。
                        </p>
                    </div>

                    <!-- 取引情報 -->
                    <div class="bg-gray-50 rounded-xl p-6 mb-8">
                        <h3 class="font-bold text-gray-900 mb-4 flex items-center">
                            <i class="ri-file-list-3-line text-blue-600 mr-2"></i>
                            取引情報
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-3">
                                <div>
                                    <div class="text-sm text-gray-600 mb-1">注文番号</div>
                                    <div class="font-semibold text-gray-900">#<?php echo strtoupper(substr(md5(time()), 0, 12)); ?></div>
                                </div>
                                <div>
                                    <div class="text-sm text-gray-600 mb-1">決済日時</div>
                                    <div class="font-semibold text-gray-900"><?php echo date('Y年m月d日 H:i:s'); ?></div>
                                </div>
                                <div>
                                    <div class="text-sm text-gray-600 mb-1">決済方法</div>
                                    <div class="font-semibold text-gray-900 flex items-center">
                                        <i class="ri-visa-line text-blue-600 text-xl mr-2"></i>
                                        Visa ••••1234
                                    </div>
                                </div>
                            </div>
                            <div class="space-y-3">
                                <div>
                                    <div class="text-sm text-gray-600 mb-1">商品名</div>
                                    <div class="font-semibold text-gray-900">英語検定 2026 参加費（早割）</div>
                                </div>
                                <div>
                                    <div class="text-sm text-gray-600 mb-1">決済金額</div>
                                    <div class="font-bold text-2xl text-blue-600">¥8,800</div>
                                </div>
                                <div>
                                    <div class="text-sm text-gray-600 mb-1">ステータス</div>
                                    <div class="inline-flex items-center bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-semibold">
                                        <i class="ri-check-double-line mr-1"></i>
                                        決済完了
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 試験情報 -->
                    <div class="bg-gradient-to-r from-blue-50 to-purple-50 rounded-xl p-6 mb-8 border-2 border-blue-200">
                        <h3 class="font-bold text-gray-900 mb-4 flex items-center">
                            <i class="ri-calendar-event-line text-purple-600 mr-2"></i>
                            試験日程
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-center">
                            <div class="bg-white rounded-lg p-4">
                                <i class="ri-calendar-line text-3xl text-blue-600 mb-2"></i>
                                <div class="text-sm text-gray-600">試験日</div>
                                <div class="font-bold text-gray-900 mt-1">2026年1月4日(日)</div>
                            </div>
                            <div class="bg-white rounded-lg p-4">
                                <i class="ri-time-line text-3xl text-green-600 mb-2"></i>
                                <div class="text-sm text-gray-600">集合時間</div>
                                <div class="font-bold text-gray-900 mt-1">10:00</div>
                            </div>
                            <div class="bg-white rounded-lg p-4">
                                <i class="ri-map-pin-line text-3xl text-purple-600 mb-2"></i>
                                <div class="text-sm text-gray-600">会場</div>
                                <div class="font-bold text-gray-900 mt-1">後日ご案内</div>
                            </div>
                        </div>
                    </div>

                    <!-- 次のステップ -->
                    <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6 mb-8">
                        <h3 class="font-bold text-gray-900 mb-4 flex items-center">
                            <i class="ri-lightbulb-line text-yellow-600 mr-2"></i>
                            次のステップ
                        </h3>
                        <div class="space-y-3 text-sm">
                            <div class="flex items-start space-x-3">
                                <div class="w-6 h-6 bg-blue-600 text-white rounded-full flex items-center justify-center text-xs flex-shrink-0 mt-0.5">1</div>
                                <div>
                                    <div class="font-medium text-gray-900">受験票の確認</div>
                                    <div class="text-gray-600">3営業日以内にメールで受験票をお送りします。印刷してご持参ください。</div>
                                </div>
                            </div>
                            <div class="flex items-start space-x-3">
                                <div class="w-6 h-6 bg-blue-600 text-white rounded-full flex items-center justify-center text-xs flex-shrink-0 mt-0.5">2</div>
                                <div>
                                    <div class="font-medium text-gray-900">試験対策</div>
                                    <div class="text-gray-600">英単語100問、イディオム50問の範囲を復習してください。</div>
                                </div>
                            </div>
                            <div class="flex items-start space-x-3">
                                <div class="w-6 h-6 bg-blue-600 text-white rounded-full flex items-center justify-center text-xs flex-shrink-0 mt-0.5">3</div>
                                <div>
                                    <div class="font-medium text-gray-900">試験当日の持ち物</div>
                                    <div class="text-gray-600">学生証（本人確認用）、受験票、筆記用具をご持参ください。</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 領収書・メール -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
                        <button
                            onclick="window.print()"
                            class="flex items-center justify-center bg-gray-100 text-gray-900 py-3 px-6 rounded-lg font-semibold hover:bg-gray-200 transition-colors"
                        >
                            <i class="ri-printer-line mr-2"></i>
                            領収書を印刷
                        </button>
                        <a
                            href="mailto:info@univ-cambridge-japan.academy"
                            class="flex items-center justify-center bg-gray-100 text-gray-900 py-3 px-6 rounded-lg font-semibold hover:bg-gray-200 transition-colors text-center"
                        >
                            <i class="ri-mail-line mr-2"></i>
                            お問い合わせ
                        </a>
                    </div>

                    <!-- ナビゲーションボタン -->
                    <div class="text-center">
                        <p class="text-sm text-gray-600 mb-4">次のステップに進んでください</p>
                        <div class="space-x-4">
                            <a
                                href="my-page/dashboard.php"
                                class="inline-flex items-center bg-gradient-to-r from-blue-600 to-purple-600 text-white px-8 py-4 rounded-full text-lg font-semibold hover:from-blue-700 hover:to-purple-700 transition-all shadow-lg hover:shadow-xl"
                            >
                                <i class="ri-dashboard-line mr-2"></i>
                                マイページへ
                            </a>
                        <a
                            href="index.php"
                                class="inline-flex items-center bg-gray-200 text-gray-700 px-8 py-4 rounded-full text-lg font-semibold hover:bg-gray-300 transition-all"
                        >
                            <i class="ri-home-line mr-2"></i>
                                トップページ
                        </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 追加情報 -->
            <div class="bg-white rounded-xl p-6 shadow-lg">
                <h3 class="font-bold text-gray-900 mb-3 flex items-center">
                    <i class="ri-information-line text-blue-600 mr-2"></i>
                    ご確認ください
                </h3>
                <ul class="text-sm text-gray-700 space-y-2">
                    <li>• 決済完了メールが届かない場合は、迷惑メールフォルダをご確認ください</li>
                    <li>• お問い合わせ: <a href="mailto:info@univ-cambridge-japan.academy" class="text-blue-600 hover:underline">info@univ-cambridge-japan.academy</a></li>
                    <li>• 電話: 03-4500-1276（平日のみ）</li>
                    <li>• 領収書は決済完了メールに添付されています</li>
                </ul>
            </div>

        </div>
    </div>

    <?php include 'components/footer.php'; ?>

</body>
</html>

