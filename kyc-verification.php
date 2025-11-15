<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- 検索エンジンインデックス防止（Stripe審査中） -->
    <meta name="robots" content="noindex, nofollow">
    <meta name="googlebot" content="noindex, nofollow">
    
    <title>本人確認 - Liquid eKYC</title>
    
    <!-- Remix Icon CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.5.0/remixicon.min.css">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="antialiased bg-gradient-to-br from-blue-50 to-purple-50">

    <div class="min-h-screen flex items-center justify-center px-6 py-12">
        <div class="max-w-2xl w-full">
            <!-- Liquid ロゴエリア -->
            <div class="text-center mb-8">
                <div class="inline-block bg-white rounded-2xl p-6 shadow-lg mb-4">
                    <div class="text-4xl font-bold text-blue-600">Liquid</div>
                    <div class="text-sm text-gray-600">eKYC Verification System</div>
                </div>
            </div>

            <!-- メインコンテンツ -->
            <div class="bg-white rounded-3xl shadow-2xl overflow-hidden">
                <div class="bg-gradient-to-r from-blue-600 to-purple-600 px-8 py-6">
                    <h1 class="text-2xl font-bold text-white text-center">本人確認手続き</h1>
                    <p class="text-blue-100 text-center mt-2">Identity Verification</p>
                </div>

                <div class="p-8">
                    <!-- 申込情報表示 -->
                    <div class="bg-gray-50 rounded-xl p-6 mb-8">
                        <h2 class="font-bold text-gray-900 mb-4 flex items-center">
                            <i class="ri-information-line text-blue-600 mr-2"></i>
                            申込情報
                        </h2>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-gray-600">申込ID:</span>
                                <span class="font-semibold text-gray-900 ml-2">#20260104-<?php echo strtoupper(substr(md5(time()), 0, 8)); ?></span>
                            </div>
                            <div>
                                <span class="text-gray-600">申込日時:</span>
                                <span class="font-semibold text-gray-900 ml-2"><?php echo date('Y年m月d日 H:i'); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- ステップ表示 -->
                    <div class="mb-8">
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex-1">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold">
                                        <i class="ri-user-line"></i>
                                    </div>
                                    <div class="ml-3">
                                        <div class="font-semibold text-gray-900">本人情報入力</div>
                                        <div class="text-xs text-blue-600">完了</div>
                                    </div>
                                </div>
                            </div>
                            <div class="w-12 h-1 bg-blue-600"></div>
                            <div class="flex-1">
                                <div class="flex items-center justify-center">
                                    <div class="w-10 h-10 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold animate-pulse">
                                        <i class="ri-id-card-line"></i>
                                    </div>
                                    <div class="ml-3">
                                        <div class="font-semibold text-gray-900">身分証撮影</div>
                                        <div class="text-xs text-blue-600">進行中</div>
                                    </div>
                                </div>
                            </div>
                            <div class="w-12 h-1 bg-gray-200"></div>
                            <div class="flex-1">
                                <div class="flex items-center justify-end">
                                    <div class="w-10 h-10 bg-gray-200 text-gray-400 rounded-full flex items-center justify-center font-bold">
                                        <i class="ri-check-line"></i>
                                    </div>
                                    <div class="ml-3">
                                        <div class="font-semibold text-gray-400">確認完了</div>
                                        <div class="text-xs text-gray-400">待機中</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 本人確認コンテンツ -->
                    <div id="verification-content">
                        <div class="text-center mb-8">
                            <h3 class="text-xl font-bold text-gray-900 mb-4">学生証を撮影してください</h3>
                            <p class="text-gray-600 mb-6">
                                カメラで学生証の表面を撮影します。<br>
                                明るい場所で、文字がはっきり読める状態で撮影してください。
                            </p>
                        </div>

                        <!-- カメラプレビューエリア（モック） -->
                        <div class="bg-gray-900 rounded-2xl overflow-hidden mb-6 relative" style="height: 400px;">
                            <div class="absolute inset-0 flex items-center justify-center">
                                <div class="text-center">
                                    <i class="ri-camera-line text-white text-6xl mb-4 animate-pulse"></i>
                                    <p class="text-white text-lg">カメラを起動中...</p>
                                    <p class="text-gray-400 text-sm mt-2">学生証を画面中央に配置してください</p>
                                </div>
                            </div>
                            <!-- ガイドフレーム -->
                            <div class="absolute inset-0 flex items-center justify-center">
                                <div class="border-4 border-dashed border-blue-400 rounded-xl" style="width: 320px; height: 200px;"></div>
                            </div>
                        </div>

                        <!-- 注意事項 -->
                        <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 mb-6">
                            <h4 class="font-bold text-gray-900 mb-2 flex items-center">
                                <i class="ri-error-warning-line text-yellow-600 mr-2"></i>
                                撮影時の注意事項
                            </h4>
                            <ul class="text-sm text-gray-700 space-y-1">
                                <li>• 学生証の全体が写るように撮影してください</li>
                                <li>• 反射や影で文字が読めなくならないようご注意ください</li>
                                <li>• 顔写真、氏名、学校名がはっきり見える必要があります</li>
                                <li>• 有効期限内の学生証をご使用ください</li>
                            </ul>
                        </div>

                        <!-- 撮影ボタン -->
                        <div class="text-center">
                            <button
                                onclick="simulateCapture()"
                                class="bg-blue-600 text-white px-12 py-4 rounded-full text-lg font-semibold hover:bg-blue-700 transition-colors inline-flex items-center"
                            >
                                <i class="ri-camera-fill mr-2"></i>
                                撮影する
                            </button>
                        </div>
                    </div>

                    <!-- 処理中表示（非表示） -->
                    <div id="processing-content" class="hidden">
                        <div class="text-center py-12">
                            <div class="inline-block">
                                <div class="w-20 h-20 border-4 border-blue-600 border-t-transparent rounded-full animate-spin mb-6"></div>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 mb-4">本人確認情報を処理中...</h3>
                            <p class="text-gray-600">
                                学生証の情報を確認しています。<br>
                                このまましばらくお待ちください。
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- セキュリティ情報 -->
            <div class="mt-6 text-center text-sm text-gray-600">
                <i class="ri-shield-check-line text-green-600 mr-1"></i>
                このページはSSL暗号化通信で保護されています
            </div>
        </div>
    </div>

    <script>
    async function simulateCapture() {
        // 撮影ボタンをクリックしたら処理中表示
        document.getElementById('verification-content').classList.add('hidden');
        document.getElementById('processing-content').classList.remove('hidden');
        
        try {
            // application_idを取得
            const applicationId = sessionStorage.getItem('application_id');
            
            if (!applicationId) {
                alert('申込IDが見つかりません。');
                return;
            }

            // 本人確認完了APIを呼び出し（kyc_statusをcompletedに更新）
            // → トリガーが発動し、scheduled_chargesにレコードが挿入される
            const response = await fetch('api/kyc/mark-as-completed.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    application_id: applicationId
                })
            });

            const result = await response.json();
            console.log('本人確認完了API応答:', result);

            if (!result.success) {
                throw new Error(result.error || '本人確認完了処理に失敗しました');
            }

            // 3秒後に完了ページへ遷移
            setTimeout(() => {
                const params = new URLSearchParams(window.location.search);
                window.location.href = 'kyc-complete.php?' + params.toString();
            }, 3000);

        } catch (error) {
            console.error('エラー:', error);
            alert('本人確認処理でエラーが発生しました: ' + error.message);
            
            // エラー表示をリセット
            document.getElementById('verification-content').classList.remove('hidden');
            document.getElementById('processing-content').classList.add('hidden');
        }
    }
    </script>

</body>
</html>

