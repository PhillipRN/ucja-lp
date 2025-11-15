<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- 検索エンジンインデックス防止（Stripe審査中） -->
    <meta name="robots" content="noindex, nofollow">
    <meta name="googlebot" content="noindex, nofollow">
    
    <title>本人確認完了 - UNIV.Cambridge Japan Academy</title>
    
    <!-- Remix Icon CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.5.0/remixicon.min.css">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="antialiased bg-gradient-to-br from-green-50 to-blue-50">

    <div class="min-h-screen flex items-center justify-center px-6 py-12">
        <div class="max-w-2xl w-full">
            
            <!-- メインコンテンツ -->
            <div class="bg-white rounded-3xl shadow-2xl overflow-hidden">
                <div class="bg-gradient-to-r from-green-600 to-emerald-600 px-8 py-6">
                    <h1 class="text-2xl font-bold text-white text-center flex items-center justify-center">
                        <i class="ri-checkbox-circle-line text-4xl mr-3"></i>
                        本人確認が完了しました
                    </h1>
                </div>

                <div class="p-8">
                    <!-- 成功メッセージ -->
                    <div class="text-center mb-8">
                        <div class="w-24 h-24 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                            <i class="ri-check-line text-green-600 text-5xl"></i>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">本人確認が正常に完了しました</h2>
                        <p class="text-gray-600">
                            学生証の確認が完了いたしました。<br>
                            次のステップで参加費のお支払い手続きを行います。
                        </p>
                    </div>

                    <!-- 確認情報 -->
                    <div class="bg-gray-50 rounded-xl p-6 mb-8">
                        <h3 class="font-bold text-gray-900 mb-4 flex items-center">
                            <i class="ri-information-line text-blue-600 mr-2"></i>
                            確認された情報
                        </h3>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between py-2 border-b border-gray-200">
                                <span class="text-gray-600">本人確認方法</span>
                                <span class="font-semibold text-gray-900">学生証による確認</span>
                            </div>
                            <div class="flex items-center justify-between py-2 border-b border-gray-200">
                                <span class="text-gray-600">確認日時</span>
                                <span class="font-semibold text-gray-900"><?php echo date('Y年m月d日 H:i:s'); ?></span>
                            </div>
                            <div class="flex items-center justify-between py-2">
                                <span class="text-gray-600">ステータス</span>
                                <span class="inline-flex items-center bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium">
                                    <i class="ri-check-line mr-1"></i>
                                    認証済み
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- 次のステップ案内 -->
                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-6 mb-8">
                        <h3 class="font-bold text-gray-900 mb-4 flex items-center">
                            <i class="ri-arrow-right-circle-line text-blue-600 mr-2"></i>
                            次のステップ：参加費のお支払い
                        </h3>
                        <div class="space-y-3 text-sm">
                            <div class="flex items-start space-x-3">
                                <div class="w-6 h-6 bg-green-600 text-white rounded-full flex items-center justify-center text-xs flex-shrink-0 mt-0.5">✓</div>
                                <div>
                                    <div class="font-medium text-gray-900">カード情報登録完了</div>
                                    <div class="text-gray-600">事前に登録いただいたカード情報を使用します</div>
                                </div>
                            </div>
                            <div class="flex items-start space-x-3">
                                <div class="w-6 h-6 bg-green-600 text-white rounded-full flex items-center justify-center text-xs flex-shrink-0 mt-0.5">✓</div>
                                <div>
                                    <div class="font-medium text-gray-900">本人確認完了</div>
                                    <div class="text-gray-600">学生証による本人確認が完了しました</div>
                                </div>
                            </div>
                            <div class="flex items-start space-x-3">
                                <div class="w-6 h-6 bg-blue-600 text-white rounded-full flex items-center justify-center text-xs flex-shrink-0 mt-0.5">
                                    <i class="ri-calendar-check-line"></i>
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900">決済予約済み</div>
                                    <div class="text-gray-600">決済処理がスケジュールされました。決済完了後にメールでご連絡いたします。</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 料金プラン表示 -->
                    <div class="bg-gradient-to-r from-purple-50 to-blue-50 rounded-xl p-6 mb-8 border-2 border-purple-200">
                        <h3 class="font-bold text-gray-900 mb-4 flex items-center">
                            <i class="ri-price-tag-3-line text-purple-600 mr-2"></i>
                            お支払い金額
                        </h3>
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-gray-600 text-sm mb-1">選択プラン</div>
                                <div class="font-bold text-xl text-gray-900">早割価格</div>
                            </div>
                            <div class="text-right">
                                <div class="text-gray-600 text-sm mb-1">合計金額（税込）</div>
                                <div class="font-bold text-3xl text-blue-600">¥8,800</div>
                            </div>
                        </div>
                    </div>

                    <!-- 完了メッセージ -->
                    <div class="text-center">
                        <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="ri-check-line text-green-600 text-4xl"></i>
                        </div>
                        <p class="text-xl font-bold text-gray-900 mb-2">全ての手続きが完了しました</p>
                        <p class="text-sm text-gray-600 mb-4">
                            決済処理は自動的に実行されます。<br>
                            決済完了後、登録いただいたメールアドレスに通知をお送りいたします。
                        </p>
                        <p class="text-sm text-gray-500">
                            <i class="ri-shield-check-line text-green-600 mr-1"></i>
                            PCI DSS準拠の安全な決済システム（Stripe）
                        </p>
                        <p class="text-sm text-gray-400 mt-4">
                            5秒後にマイページへ自動的に移動します...
                        </p>
                    </div>
                </div>
            </div>

            <!-- 注意事項 -->
            <div class="mt-6 bg-white rounded-xl p-6 shadow-lg">
                <h4 class="font-bold text-gray-900 mb-3 flex items-center">
                    <i class="ri-information-line text-blue-600 mr-2"></i>
                    今後の流れ
                </h4>
                <ul class="text-sm text-gray-700 space-y-2">
                    <li>• 決済処理は自動的に実行されます（登録済みカードで課金）</li>
                    <li>• 決済完了後、メールでご連絡いたします</li>
                    <li>• マイページで申込状況・決済状況を確認できます</li>
                    <li>• 決済完了後、3営業日以内に受験票をメールでお送りいたします</li>
                    <li>• 領収書はマイページからダウンロード可能です</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
    // ページ読み込み時に5秒後にマイページへリダイレクト
    document.addEventListener('DOMContentLoaded', () => {
        console.log('本人確認完了画面を表示');
        
        // 5秒後にマイページへ自動遷移
        setTimeout(() => {
            window.location.href = 'my-page/dashboard.php';
        }, 5000);
    });
    </script>

</body>
</html>

