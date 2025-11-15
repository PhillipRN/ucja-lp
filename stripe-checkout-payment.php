<?php
require_once __DIR__ . '/config/config.php';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- 検索エンジンインデックス防止（Stripe審査中） -->
    <meta name="robots" content="noindex, nofollow">
    <meta name="googlebot" content="noindex, nofollow">
    
    <title>お支払い - Stripe Checkout</title>
    
    <!-- Remix Icon CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.5.0/remixicon.min.css">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Stripe.js -->
    <script src="https://js.stripe.com/v3/"></script>
</head>
<body class="antialiased bg-gray-100">

    <div class="min-h-screen py-12 px-4">
        <div class="max-w-5xl mx-auto">
            
            <!-- Stripeヘッダー -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center bg-white rounded-xl px-6 py-3 shadow-sm">
                    <svg class="w-16 h-6" viewBox="0 0 60 25" xmlns="http://www.w3.org/2000/svg">
                        <path fill="#635BFF" d="M59.64 14.28h-8.06c.19 1.93 1.6 2.55 3.2 2.55 1.64 0 2.96-.37 4.05-.95v3.32a8.33 8.33 0 0 1-4.56 1.1c-4.01 0-6.83-2.5-6.83-7.48 0-4.19 2.39-7.52 6.3-7.52 3.92 0 5.96 3.28 5.96 7.5 0 .4-.04 1.26-.06 1.48zm-5.92-5.62c-1.03 0-2.17.73-2.17 2.58h4.25c0-1.85-1.07-2.58-2.08-2.58zM40.95 20.3c-1.44 0-2.32-.6-2.9-1.04l-.02 4.63-4.12.87V5.57h3.76l.08 1.02a4.7 4.7 0 0 1 3.23-1.29c2.9 0 5.62 2.6 5.62 7.4 0 5.23-2.7 7.6-5.65 7.6zM40 8.95c-.95 0-1.54.34-1.97.81l.02 6.12c.4.44.98.78 1.95.78 1.52 0 2.54-1.65 2.54-3.87 0-2.15-1.04-3.84-2.54-3.84zM28.24 5.57h4.13v14.44h-4.13V5.57zm0-4.7L32.37 0v3.36l-4.13.88V.88zm-4.32 9.35v9.79H19.8V5.57h3.7l.12 1.22c1-1.77 3.07-1.41 3.62-1.22v3.79c-.52-.17-2.29-.43-3.32.86zm-8.55 4.72c0 2.43 2.6 1.68 3.12 1.46v3.36c-.55.3-1.54.54-2.89.54a4.15 4.15 0 0 1-4.27-4.24l.01-13.17 4.02-.86v3.54h3.14V9.1h-3.13v5.85zm-4.91.7c0 2.97-2.31 4.66-5.73 4.66a11.2 11.2 0 0 1-4.46-.93v-3.93c1.38.75 3.1 1.31 4.46 1.31.92 0 1.53-.24 1.53-1C6.26 13.77 0 14.51 0 9.95 0 7.04 2.28 5.3 5.62 5.3c1.36 0 2.72.2 4.09.75v3.88a9.23 9.23 0 0 0-4.1-1.06c-.86 0-1.44.25-1.44.9 0 1.85 6.29.97 6.29 5.88z"/>
                    </svg>
                    <span class="ml-3 text-gray-400">|</span>
                    <span class="ml-3 text-sm text-gray-600">Powered by Stripe</span>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-5 gap-8">
                
                <!-- 左側：決済フォーム -->
                <div class="lg:col-span-3">
                    <div class="bg-white rounded-xl shadow-lg p-8">
                        
                        <!-- 戻るリンク -->
                        <a href="my-page/payment-status.php" class="inline-flex items-center text-blue-600 hover:text-blue-700 mb-6">
                            <i class="ri-arrow-left-line mr-1"></i>
                            支払い状況に戻る
                        </a>

                        <h1 class="text-2xl font-bold text-gray-900 mb-2">お支払い情報</h1>
                        <p class="text-gray-600 mb-2">安全な決済のため、以下の情報をご入力ください</p>
                        
                        <!-- 即時決済の説明 -->
                        <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6">
                            <div class="flex items-start">
                                <i class="ri-check-line text-green-600 text-xl mr-3 mt-0.5"></i>
                                <div>
                                    <p class="font-semibold text-green-900 mb-1">即時決済が行われます</p>
                                    <p class="text-sm text-green-800">
                                        カード情報を入力後、すぐに決済が実行されます。
                                    </p>
                                </div>
                            </div>
                        </div>

                        <form id="payment-form">
                            <!-- カード情報 (Stripe Elements) -->
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    カード情報
                                </label>
                                <div id="card-element" class="border border-gray-300 rounded-lg p-4">
                                    <!-- Stripe Card Element がここに挿入されます -->
                                </div>
                                <div id="card-errors" class="text-red-600 text-sm mt-2"></div>
                                <p class="text-xs text-gray-500 mt-2">
                                    <i class="ri-lock-line mr-1"></i>
                                    カード情報は暗号化され安全に処理されます
                                </p>
                            </div>

                            <!-- 利用規約同意 -->
                            <div class="mb-6">
                                <label class="flex items-start">
                                    <input
                                        type="checkbox"
                                        id="agree-terms"
                                        class="mt-1 mr-2"
                                    />
                                    <span class="text-sm text-gray-700">
                                        <a href="terms.php" target="_blank" class="text-blue-600 hover:underline">利用規約</a>および<a href="privacy.php" target="_blank" class="text-blue-600 hover:underline">プライバシーポリシー</a>に同意します
                                    </span>
                                </label>
                            </div>

                            <!-- 支払いボタン -->
                            <button
                                type="submit"
                                class="w-full bg-green-600 text-white py-4 rounded-lg font-semibold text-lg hover:bg-green-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                id="submit-button"
                            >
                                <span id="button-text">¥<span id="display-amount">0</span>を今すぐ支払う</span>
                                <span id="button-spinner" class="hidden">
                                    <i class="ri-loader-4-line animate-spin"></i> 処理中...
                                </span>
                            </button>

                            <!-- セキュリティ表示 -->
                            <div class="mt-6 text-center text-sm text-gray-600">
                                <i class="ri-shield-check-line text-green-600 mr-1"></i>
                                SSL暗号化通信で保護されています
                            </div>
                        </form>
                    </div>

                    <!-- 補足情報 -->
                    <div class="mt-6 text-center text-xs text-gray-500">
                        Powered by <span class="font-semibold">Stripe</span> | 
                        <a href="terms.php" class="text-blue-600 hover:underline">利用規約</a> | 
                        <a href="privacy.php" class="text-blue-600 hover:underline">プライバシーポリシー</a>
                    </div>
                </div>

                <!-- 右側：支払いサマリー -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-xl shadow-lg p-6 sticky top-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">お支払い内容</h2>
                        
                        <div class="space-y-4">
                            <!-- 参加プラン -->
                            <div class="pb-4 border-b border-gray-200">
                                <div class="text-sm text-gray-600 mb-2">参加プラン</div>
                                <div class="font-semibold text-gray-900" id="summary-plan">-</div>
                            </div>

                            <!-- 料金 -->
                            <div class="pb-4 border-b border-gray-200">
                                <div class="flex justify-between items-baseline">
                                    <span class="text-gray-600">参加費</span>
                                    <span class="text-2xl font-bold text-gray-900">
                                        ¥<span id="summary-amount">0</span>
                                    </span>
                                </div>
                                <div class="text-xs text-gray-500 mt-1">（税込）</div>
                            </div>

                            <!-- 決済タイミング -->
                            <div class="bg-green-50 rounded-lg p-4">
                                <div class="flex items-start">
                                    <i class="ri-flashlight-line text-green-600 text-xl mr-2 mt-0.5"></i>
                                    <div>
                                        <div class="font-semibold text-green-900 text-sm mb-1">即時決済</div>
                                        <div class="text-xs text-green-800">
                                            すぐに決済が実行されます
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- サポート情報 -->
                        <div class="mt-6 pt-6 border-t border-gray-200">
                            <div class="text-sm text-gray-600 mb-2">
                                <i class="ri-question-line mr-1"></i>
                                お困りの際は
                            </div>
                            <a href="mailto:contact@univ-cambridge-japan.academy" class="text-sm text-blue-600 hover:underline">
                                contact@univ-cambridge-japan.academy
                            </a>
                        </div>

                        <!-- 返金ポリシー -->
                        <div class="mt-4 text-xs text-gray-500">
                            <a href="tokusho.php" target="_blank" class="text-blue-600 hover:underline">返品・キャンセルポリシー</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    // マイページからの情報を取得（セッションまたはURL）
    // TODO: 実装時はマイページから必要な情報を渡す
    const amount = 22000; // TODO: 実際の金額を取得
    const participationType = 'individual'; // TODO: 実際の参加形式を取得

    // 金額を表示
    document.getElementById('display-amount').textContent = parseInt(amount).toLocaleString();
    document.getElementById('summary-amount').textContent = parseInt(amount).toLocaleString();
    document.getElementById('summary-plan').textContent = participationType === 'team' ? 'チーム戦' : '個人戦';

    // Stripe初期化
    const stripe = Stripe('<?php echo STRIPE_PUBLISHABLE_KEY; ?>');
    const elements = stripe.elements();

    // Card Elementの作成
    const cardElement = elements.create('card', {
        hidePostalCode: true, // 郵便番号フィールドを非表示
        style: {
            base: {
                fontSize: '16px',
                color: '#32325d',
                fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
                '::placeholder': {
                    color: '#aab7c4'
                }
            },
            invalid: {
                color: '#dc2626'
            }
        }
    });

    cardElement.mount('#card-element');

    // カードエラーの表示
    cardElement.on('change', (event) => {
        const displayError = document.getElementById('card-errors');
        if (event.error) {
            displayError.textContent = event.error.message;
        } else {
            displayError.textContent = '';
        }
    });

    // フォーム送信処理
    const form = document.getElementById('payment-form');
    const submitButton = document.getElementById('submit-button');
    const buttonText = document.getElementById('button-text');
    const buttonSpinner = document.getElementById('button-spinner');
    const agreeTerms = document.getElementById('agree-terms');

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        // 利用規約同意チェック
        if (!agreeTerms.checked) {
            alert('利用規約とプライバシーポリシーに同意してください');
            return;
        }

        // ボタンを無効化
        submitButton.disabled = true;
        buttonText.classList.add('hidden');
        buttonSpinner.classList.remove('hidden');

        try {
            // TODO: PaymentIntent用のAPIエンドポイントを作成
            // const response = await fetch('api/stripe/create-payment-intent.php', {
            //     method: 'POST',
            //     headers: { 'Content-Type': 'application/json' },
            //     body: JSON.stringify({
            //         amount: amount
            //     })
            // });
            // const data = await response.json();
            // const { client_secret } = data;

            // PaymentIntent確認 (本番実装時)
            // const { paymentIntent, error } = await stripe.confirmCardPayment(client_secret, {
            //     payment_method: {
            //         card: cardElement,
            //     }
            // });

            // if (error) {
            //     throw new Error(error.message);
            // }

            // モック：成功として処理
            console.log('PaymentIntent Success (Mock)');
            
            // 成功メッセージ表示
            alert('決済が完了しました！');
            
            // 決済完了ページへ遷移
            setTimeout(() => {
                window.location.href = 'payment-complete.php';
            }, 2000);

        } catch (error) {
            console.error('Error:', error);
            alert('エラーが発生しました: ' + error.message);
            
            // ボタンを再有効化
            submitButton.disabled = false;
            buttonText.classList.remove('hidden');
            buttonSpinner.classList.add('hidden');
        }
    });
    </script>

</body>
</html>

