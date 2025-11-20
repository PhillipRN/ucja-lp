<section id="application" class="py-20 bg-gray-50">
    <div class="container mx-auto px-6">
        <div class="text-center mb-16">
            <h2 class="text-4xl font-bold text-gray-900 mb-6">申込フォーム</h2>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto">
            チーム戦のお申込は、右側の「チーム戦」を選択<br>個人戦のお申込は、左側の「個人戦」を選択<br>まず基本情報をご入力ください<br>※チーム戦に登録されたメンバーは、全員自動的に個人戦にも登録されます。
            </p>
        </div>
        
        <div class="max-w-4xl mx-auto">
            <!-- ステップインジケーター -->
            <div class="flex items-center justify-center mb-12">
                <div class="flex items-center space-x-8">
                    <div class="flex items-center space-x-3" id="step1-indicator">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold bg-blue-600 text-white">
                            1
                        </div>
                        <span class="font-medium text-blue-600">基本情報入力</span>
                    </div>
                    <div class="w-16 h-1 bg-gray-200" id="step-divider"></div>
                    <div class="flex items-center space-x-3 text-gray-400" id="step2-indicator">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold bg-gray-200 text-gray-400">
                            2
                        </div>
                        <span class="font-medium">本人確認・決済</span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-3xl shadow-lg p-8">
                <!-- Step 1: 基本情報入力 -->
                <div id="step1-form">
                    <!-- タブナビゲーション -->
                    <div class="mb-8">
                        <div class="border-b border-gray-200">
                            <div class="flex justify-center space-x-2">
                                <button
                                    type="button"
                                    id="tab-individual"
                                    onclick="switchTab('individual')"
                                    class="px-6 py-3 font-semibold text-blue-600 border-b-2 border-blue-600 focus:outline-none"
                                >
                                    <div class="flex items-center space-x-2">
                                        <i class="ri-user-line"></i>
                                        <span>個人戦（1名）</span>
                                    </div>
                                </button>
                                <button
                                    type="button"
                                    id="tab-team"
                                    onclick="switchTab('team')"
                                    class="px-6 py-3 font-semibold text-gray-500 border-b-2 border-transparent hover:text-gray-700 focus:outline-none"
                                >
                                    <div class="flex items-center space-x-2">
                                        <i class="ri-team-line"></i>
                                        <span>チーム戦（5名）</span>
                                    </div>
                                </button>
                            </div>
                        </div>
                    </div>

                    <form id="application-form-step1" onsubmit="return handleStep1Submit(event)">
                        <!-- Hidden field for participation type -->
                        <input type="hidden" name="participationType" id="participationType" value="個人戦">

                        <!-- 個人戦タブコンテンツ -->
                        <div id="individual-content" class="tab-content">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    生徒氏名
                                </label>
                            <input
                                type="text"
                                name="studentName"
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                                placeholder="山田太郎"
                                required
                            />
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    学校名
                                </label>
                            <input
                                type="text"
                                name="school"
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                                placeholder="○○高等学校"
                                required
                            />
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    学年
                                </label>
                            <select
                                name="grade"
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm pr-8"
                                required
                            >
                                <option value="" disabled selected>選択してください</option>
                                    <option value="1年生">1年生</option>
                                    <option value="2年生">2年生</option>
                                    <option value="3年生">3年生</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    生徒メールアドレス
                                </label>
                            <input
                                type="email"
                                name="email"
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                                placeholder="student@example.com"
                                required
                            />
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    生徒電話番号
                                </label>
                            <input
                                type="tel"
                                name="phone"
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                                placeholder="090-1234-5678"
                                required
                            />
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    保護者氏名
                                </label>
                            <input
                                type="text"
                                name="guardianName"
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                                placeholder="山田花子"
                                required
                            />
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    保護者メールアドレス
                                </label>
                            <input
                                type="email"
                                name="guardianEmail"
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                                placeholder="parent@example.com"
                                required
                            />
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    保護者電話番号
                                </label>
                            <input
                                type="tel"
                                name="guardianPhone"
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                                placeholder="090-1234-5678"
                                required
                            />
                            </div>
                            
                            
                            <!--
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    特記事項・ご質問
                                </label>
                                <textarea
                                    name="specialRequests"
                                    id="specialRequests-individual"
                                    rows="4"
                                    maxlength="500"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm resize-none"
                                    placeholder="アレルギーや配慮が必要な事項があればご記入ください"
                                    oninput="updateCharCount('specialRequests-individual', 'specialRequestsCount-individual')"
                                ></textarea>
                                <div class="text-right text-sm text-gray-500 mt-1">
                                    <span id="specialRequestsCount-individual">0</span>/500文字
                                </div>
                            </div>-->
                        </div>
                        </div>

                        <!-- チーム戦タブコンテンツ -->
                        <div id="team-content" class="tab-content" style="display: none;">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    チーム名
                                </label>
                                <input
                                    type="text"
                                    name="teamName"
                                    id="teamName"
                                    maxlength="10"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                                    placeholder="○○高校Aチーム"
                                    required
                                />
                                <p class="text-xs text-gray-500 mt-1">※10文字まで</p>
                            </div>

                            <!-- チームメンバー5名分 -->
                            <div class="md:col-span-2 mb-4">
                                <h3 class="text-lg font-bold text-gray-900 mb-4">チームメンバー情報（5名）</h3>
                            </div>

                            <!-- メンバー1 -->
                            <div class="md:col-span-2 bg-gray-50 rounded-xl p-6">
                                <h4 class="font-medium text-gray-900 mb-4">メンバー1（代表者）</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            氏名
                                        </label>
                                        <input
                                            type="text"
                                            name="member1Name"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm bg-white"
                                            placeholder="山田太郎"
                                            required
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            メールアドレス<span class="text-red-600 ml-1">*</span>
                                        </label>
                                        <input
                                            type="email"
                                            name="member1Email"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm bg-white"
                                            placeholder="member1@example.com"
                                            required
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- メンバー2 -->
                            <div class="md:col-span-2 bg-gray-50 rounded-xl p-6">
                                <h4 class="font-medium text-gray-900 mb-4">メンバー2</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            氏名
                                        </label>
                                        <input
                                            type="text"
                                            name="member2Name"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm bg-white"
                                            placeholder="佐藤花子"
                                            required
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            メールアドレス<span class="text-red-600 ml-1">*</span>
                                        </label>
                                        <input
                                            type="email"
                                            name="member2Email"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm bg-white"
                                            placeholder="member2@example.com"
                                            required
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- メンバー3 -->
                            <div class="md:col-span-2 bg-gray-50 rounded-xl p-6">
                                <h4 class="font-medium text-gray-900 mb-4">メンバー3</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            氏名
                                        </label>
                                        <input
                                            type="text"
                                            name="member3Name"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm bg-white"
                                            placeholder="田中次郎"
                                            required
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            メールアドレス<span class="text-red-600 ml-1">*</span>
                                        </label>
                                        <input
                                            type="email"
                                            name="member3Email"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm bg-white"
                                            placeholder="member3@example.com"
                                            required
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- メンバー4 -->
                            <div class="md:col-span-2 bg-gray-50 rounded-xl p-6">
                                <h4 class="font-medium text-gray-900 mb-4">メンバー4</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            氏名
                                        </label>
                                        <input
                                            type="text"
                                            name="member4Name"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm bg-white"
                                            placeholder="鈴木一郎"
                                            required
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            メールアドレス<span class="text-red-600 ml-1">*</span>
                                        </label>
                                        <input
                                            type="email"
                                            name="member4Email"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm bg-white"
                                            placeholder="member4@example.com"
                                            required
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- メンバー5 -->
                            <div class="md:col-span-2 bg-gray-50 rounded-xl p-6">
                                <h4 class="font-medium text-gray-900 mb-4">メンバー5</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            氏名
                                        </label>
                                        <input
                                            type="text"
                                            name="member5Name"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm bg-white"
                                            placeholder="高橋美咲"
                                            required
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            メールアドレス<span class="text-red-600 ml-1">*</span>
                                        </label>
                                        <input
                                            type="email"
                                            name="member5Email"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm bg-white"
                                            placeholder="member5@example.com"
                                            required
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- 代表者情報 -->
                            <div class="md:col-span-2 mt-4 mb-4">
                                <h3 class="text-lg font-bold text-gray-900 mb-4">代表者の保護者情報</h3>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    保護者氏名
                                </label>
                                <input
                                    type="text"
                                    name="guardianName-team"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                                    placeholder="山田花子"
                                    required
                                />
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    保護者メールアドレス
                                </label>
                                <input
                                    type="email"
                                    name="guardianEmail-team"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                                    placeholder="parent@example.com"
                                    required
                                />
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    保護者電話番号
                                </label>
                                <input
                                    type="tel"
                                    name="guardianPhone-team"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                                    placeholder="090-1234-5678"
                                    required
                                />
                            </div>

                            <!-- 学校名（チーム共通） -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    学校名
                                </label>
                                <input
                                    type="text"
                                    name="school-team"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                                    placeholder="○○高等学校"
                                    required
                                />
                            </div>

                            <!--

                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    特記事項・ご質問
                                </label>
                                <textarea
                                    name="specialRequests-team"
                                    id="specialRequests-team"
                                    rows="4"
                                    maxlength="500"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm resize-none"
                                    placeholder="アレルギーや配慮が必要な事項があればご記入ください"
                                    oninput="updateCharCount('specialRequests-team', 'specialRequestsCount-team')"
                                ></textarea>
                                <div class="text-right text-sm text-gray-500 mt-1">
                                    <span id="specialRequestsCount-team">0</span>/500文字
                                </div>
                            </div>-->
                        </div>
                        </div>
                        
                        <div class="text-center">
                            <button
                                type="submit"
                                class="bg-blue-600 text-white px-12 py-4 rounded-full text-lg font-semibold hover:bg-blue-700 transition-colors whitespace-nowrap"
                            >
                                次へ進む（料金選択・決済）
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Step 1.5: 入力内容確認 -->
                <div id="step1-confirm" style="display: none;">
                    <div class="mb-8">
                        <h3 class="text-2xl font-bold text-gray-900 mb-6 text-center">入力内容の確認</h3>
                        <p class="text-center text-gray-600 mb-8">
                            以下の内容でお間違いないかご確認ください
                        </p>
                    </div>

                    <!-- 個人戦の確認内容 -->
                    <div id="confirm-individual" class="space-y-6" style="display: none;">
                        <div class="bg-blue-50 rounded-xl p-6 border border-blue-200">
                            <h4 class="font-bold text-gray-900 mb-4 flex items-center">
                                <i class="ri-user-line text-blue-600 mr-2"></i>
                                参加形式
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <div class="text-sm text-gray-600 mb-1">参加形式</div>
                                    <div class="font-medium text-gray-900" id="confirm-participationType"></div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-xl p-6 border border-gray-200">
                            <h4 class="font-bold text-gray-900 mb-4 flex items-center">
                                <i class="ri-user-line text-blue-600 mr-2"></i>
                                生徒情報
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <div class="text-sm text-gray-600 mb-1">生徒氏名</div>
                                    <div class="font-medium text-gray-900" id="confirm-studentName"></div>
                                </div>
                                <div>
                                    <div class="text-sm text-gray-600 mb-1">学校名</div>
                                    <div class="font-medium text-gray-900" id="confirm-school"></div>
                                </div>
                                <div>
                                    <div class="text-sm text-gray-600 mb-1">学年</div>
                                    <div class="font-medium text-gray-900" id="confirm-grade"></div>
                                </div>
                                <div>
                                    <div class="text-sm text-gray-600 mb-1">メールアドレス</div>
                                    <div class="font-medium text-gray-900" id="confirm-email"></div>
                                </div>
                                <div>
                                    <div class="text-sm text-gray-600 mb-1">電話番号</div>
                                    <div class="font-medium text-gray-900" id="confirm-phone"></div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-xl p-6 border border-gray-200">
                            <h4 class="font-bold text-gray-900 mb-4 flex items-center">
                                <i class="ri-parent-line text-blue-600 mr-2"></i>
                                保護者情報
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <div class="text-sm text-gray-600 mb-1">保護者氏名</div>
                                    <div class="font-medium text-gray-900" id="confirm-guardianName"></div>
                                </div>
                                <div>
                                    <div class="text-sm text-gray-600 mb-1">保護者メールアドレス</div>
                                    <div class="font-medium text-gray-900" id="confirm-guardianEmail"></div>
                                </div>
                                <div>
                                    <div class="text-sm text-gray-600 mb-1">保護者電話番号</div>
                                    <div class="font-medium text-gray-900" id="confirm-guardianPhone"></div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-xl p-6 border border-gray-200" id="confirm-specialRequests-wrapper">
                            <h4 class="font-bold text-gray-900 mb-4 flex items-center">
                                <i class="ri-message-line text-blue-600 mr-2"></i>
                                特記事項・ご質問
                            </h4>
                            <div class="text-gray-900" id="confirm-specialRequests"></div>
                        </div>
                    </div>

                    <!-- チーム戦の確認内容 -->
                    <div id="confirm-team" class="space-y-6" style="display: none;">
                        <div class="bg-blue-50 rounded-xl p-6 border border-blue-200">
                            <h4 class="font-bold text-gray-900 mb-4 flex items-center">
                                <i class="ri-team-line text-blue-600 mr-2"></i>
                                参加形式・チーム情報
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <div class="text-sm text-gray-600 mb-1">参加形式</div>
                                    <div class="font-medium text-gray-900" id="confirm-participationType-team"></div>
                                </div>
                                <div>
                                    <div class="text-sm text-gray-600 mb-1">チーム名</div>
                                    <div class="font-medium text-gray-900" id="confirm-teamName"></div>
                                </div>
                                <div>
                                    <div class="text-sm text-gray-600 mb-1">学校名</div>
                                    <div class="font-medium text-gray-900" id="confirm-school-team"></div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-xl p-6 border border-gray-200">
                            <h4 class="font-bold text-gray-900 mb-4 flex items-center">
                                <i class="ri-team-line text-blue-600 mr-2"></i>
                                チームメンバー情報（5名）
                            </h4>
                            <div class="space-y-4">
                                <div class="border-b border-gray-200 pb-3">
                                    <div class="text-sm font-medium text-gray-700 mb-2">メンバー1（代表者）</div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                        <div>
                                            <span class="text-sm text-gray-600">氏名：</span>
                                            <span class="font-medium text-gray-900" id="confirm-member1Name"></span>
                                        </div>
                                        <div>
                                            <span class="text-sm text-gray-600">メール：</span>
                                            <span class="font-medium text-gray-900" id="confirm-member1Email"></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="border-b border-gray-200 pb-3">
                                    <div class="text-sm font-medium text-gray-700 mb-2">メンバー2</div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                        <div>
                                            <span class="text-sm text-gray-600">氏名：</span>
                                            <span class="font-medium text-gray-900" id="confirm-member2Name"></span>
                                        </div>
                                        <div>
                                            <span class="text-sm text-gray-600">メール：</span>
                                            <span class="font-medium text-gray-900" id="confirm-member2Email"></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="border-b border-gray-200 pb-3">
                                    <div class="text-sm font-medium text-gray-700 mb-2">メンバー3</div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                        <div>
                                            <span class="text-sm text-gray-600">氏名：</span>
                                            <span class="font-medium text-gray-900" id="confirm-member3Name"></span>
                                        </div>
                                        <div>
                                            <span class="text-sm text-gray-600">メール：</span>
                                            <span class="font-medium text-gray-900" id="confirm-member3Email"></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="border-b border-gray-200 pb-3">
                                    <div class="text-sm font-medium text-gray-700 mb-2">メンバー4</div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                        <div>
                                            <span class="text-sm text-gray-600">氏名：</span>
                                            <span class="font-medium text-gray-900" id="confirm-member4Name"></span>
                                        </div>
                                        <div>
                                            <span class="text-sm text-gray-600">メール：</span>
                                            <span class="font-medium text-gray-900" id="confirm-member4Email"></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="pb-3">
                                    <div class="text-sm font-medium text-gray-700 mb-2">メンバー5</div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                        <div>
                                            <span class="text-sm text-gray-600">氏名：</span>
                                            <span class="font-medium text-gray-900" id="confirm-member5Name"></span>
                                        </div>
                                        <div>
                                            <span class="text-sm text-gray-600">メール：</span>
                                            <span class="font-medium text-gray-900" id="confirm-member5Email"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-xl p-6 border border-gray-200">
                            <h4 class="font-bold text-gray-900 mb-4 flex items-center">
                                <i class="ri-parent-line text-blue-600 mr-2"></i>
                                代表者の保護者情報
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <div class="text-sm text-gray-600 mb-1">保護者氏名</div>
                                    <div class="font-medium text-gray-900" id="confirm-guardianName-team"></div>
                                </div>
                                <div>
                                    <div class="text-sm text-gray-600 mb-1">保護者メールアドレス</div>
                                    <div class="font-medium text-gray-900" id="confirm-guardianEmail-team"></div>
                                </div>
                                <div>
                                    <div class="text-sm text-gray-600 mb-1">保護者電話番号</div>
                                    <div class="font-medium text-gray-900" id="confirm-guardianPhone-team"></div>
                                </div>
                            </div>
                        </div>

                        <!--<div class="bg-white rounded-xl p-6 border border-gray-200" id="confirm-specialRequests-team-wrapper">
                            <h4 class="font-bold text-gray-900 mb-4 flex items-center">
                                <i class="ri-message-line text-blue-600 mr-2"></i>
                                特記事項・ご質問
                            </h4>
                            <div class="text-gray-900" id="confirm-specialRequests-team"></div>
                        </div>-->
                    </div>

                    <div class="flex items-center justify-between mt-8">
                        <button
                            type="button"
                            onclick="handleBackToEdit()"
                            class="bg-gray-500 text-white px-8 py-3 rounded-full font-semibold hover:bg-gray-600 transition-colors whitespace-nowrap"
                        >
                            <i class="ri-edit-line mr-2"></i>修正する
                        </button>
                        <button
                            type="button"
                            onclick="handleConfirmAndNext()"
                            class="bg-blue-600 text-white px-12 py-4 rounded-full text-lg font-semibold hover:bg-blue-700 transition-colors whitespace-nowrap"
                        >
                            確認して次へ（料金選択）
                        </button>
                    </div>
                </div>

                <!-- Step 2: 料金選択・決済 -->
                <div id="step2-form" style="display: none;">
                    <div class="mb-8">
                        <h3 class="text-2xl font-bold text-gray-900 mb-6 text-center">料金プラン選択・決済手続き</h3>
                        
                        <form id="application-form-step2" onsubmit="return handleFinalSubmit(event)">
                            <!-- Hidden fields for step 1 data -->
                            <input type="hidden" name="participationType" id="hidden-participationType">
                            <input type="hidden" name="studentName" id="hidden-studentName">
                            <input type="hidden" name="school" id="hidden-school">
                            <input type="hidden" name="grade" id="hidden-grade">
                            <input type="hidden" name="email" id="hidden-email">
                            <input type="hidden" name="phone" id="hidden-phone">
                            <input type="hidden" name="guardianName" id="hidden-guardianName">
                            <input type="hidden" name="guardianEmail" id="hidden-guardianEmail">
                            <input type="hidden" name="guardianPhone" id="hidden-guardianPhone">
                            <input type="hidden" name="cardHolderName" id="hidden-cardHolderName">
                            <input type="hidden" name="cardHolderNameRomaji" id="hidden-cardHolderNameRomaji">
                            <input type="hidden" name="teamName" id="hidden-teamName">
                            <input type="hidden" name="specialRequests" id="hidden-specialRequests">
                            <!-- チーム戦用のhiddenフィールド -->
                            <input type="hidden" name="member1Name" id="hidden-member1Name">
                            <input type="hidden" name="member1Email" id="hidden-member1Email">
                            <input type="hidden" name="member2Name" id="hidden-member2Name">
                            <input type="hidden" name="member2Email" id="hidden-member2Email">
                            <input type="hidden" name="member3Name" id="hidden-member3Name">
                            <input type="hidden" name="member3Email" id="hidden-member3Email">
                            <input type="hidden" name="member4Name" id="hidden-member4Name">
                            <input type="hidden" name="member4Email" id="hidden-member4Email">
                            <input type="hidden" name="member5Name" id="hidden-member5Name">
                            <input type="hidden" name="member5Email" id="hidden-member5Email">
                            <input type="hidden" name="guardianName-team" id="hidden-guardianName-team">
                            <input type="hidden" name="guardianEmail-team" id="hidden-guardianEmail-team">
                            <input type="hidden" name="guardianPhone-team" id="hidden-guardianPhone-team">
                            <input type="hidden" name="school-team" id="hidden-school-team">
                            <input type="hidden" name="cardHolderName-team" id="hidden-cardHolderName-team">
                            <input type="hidden" name="cardHolderNameRomaji-team" id="hidden-cardHolderNameRomaji-team">
                            <input type="hidden" name="specialRequests-team" id="hidden-specialRequests-team">

                            <div class="mb-8">
                                <label class="block text-sm font-medium text-gray-700 mb-4">
                                    料金プラン
                                </label>
                                <div class="space-y-4">
                                    <label class="flex items-center p-4 border-2 border-gray-200 rounded-xl cursor-pointer hover:border-green-300 transition-colors">
                                        <input
                                            type="radio"
                                            name="pricingType"
                                            value="早割 8,800円（2025年12月15日締切）"
                                            class="mr-4"
                                            required
                                        />
                                        <div class="flex-1">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <div class="font-bold text-lg text-gray-900">早割 8,800円</div>
                                                    <div class="text-sm text-gray-600">2025年12月15日締切</div>
                                                </div>
                                                <div class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium">
                                                    60%OFF
                                                </div>
                                            </div>
                                        </div>
                                    </label>
                                    <label class="flex items-center p-4 border-2 border-gray-200 rounded-xl cursor-pointer hover:border-blue-300 transition-colors">
                                        <input
                                            type="radio"
                                            name="pricingType"
                                            value="通常 22,000円（2026年1月1日締切）"
                                            class="mr-4"
                                        />
                                        <div class="flex-1">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <div class="font-bold text-lg text-gray-900">通常 22,000円</div>
                                                    <div class="text-sm text-gray-600">2026年1月1日締切</div>
                                                </div>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <div class="mb-8">
                                <label class="block text-sm font-medium text-gray-700 mb-4">
                                    支払い方法
                                </label>
                                <div class="flex justify-center">
                                    <div class="w-full max-w-md p-6 bg-gradient-to-br from-blue-50 to-purple-50 rounded-2xl border-2 border-blue-300">
                                        <input
                                            type="hidden"
                                            name="paymentMethod"
                                            value="Stripe クレジットカード決済"
                                        />
                                        <div class="text-center">
                                            <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg">
                                                <i class="ri-bank-card-line text-3xl text-blue-600"></i>
                                            </div>
                                            <div class="font-bold text-xl text-gray-900 mb-2">Stripe クレジットカード決済</div>
                                            <div class="text-sm text-gray-700 mb-4">VISA・MasterCard・JCB・American Express</div>
                                            <div class="bg-white rounded-lg p-4 text-left space-y-2">
                                                <div class="flex items-center text-sm text-gray-700">
                                                    <i class="ri-check-double-line text-green-600 mr-2"></i>
                                                    国際セキュリティ基準PCI DSS準拠
                                                </div>
                                                <div class="flex items-center text-sm text-gray-700">
                                                    <i class="ri-check-double-line text-green-600 mr-2"></i>
                                                    カード情報の暗号化処理
                                                </div>
                                                <div class="flex items-center text-sm text-gray-700">
                                                    <i class="ri-check-double-line text-green-600 mr-2"></i>
                                                    決済手数料無料
                                                </div>
                                                <div class="flex items-center text-sm text-gray-700">
                                                    <i class="ri-check-double-line text-green-600 mr-2"></i>
                                                    申込時に即座に決済完了
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-blue-50 border border-blue-200 rounded-xl p-6 mb-8">
                                <h4 class="font-bold text-gray-900 mb-3 flex items-center">
                                    <i class="ri-shield-check-line text-blue-600 mr-2"></i>
                                    本人確認・決済の流れ
                                </h4>
                                <div class="space-y-3 text-sm text-gray-700">
                                    <div class="flex items-start space-x-3">
                                        <div class="w-6 h-6 bg-blue-600 text-white rounded-full flex items-center justify-center text-xs font-bold mt-0.5">1</div>
                                        <div>
                                            <div class="font-medium">申込情報送信</div>
                                            <div class="text-gray-600">基本情報と料金プランを送信します</div>
                                        </div>
                                    </div>
                                    <div class="flex items-start space-x-3">
                                        <div class="w-6 h-6 bg-blue-600 text-white rounded-full flex items-center justify-center text-xs font-bold mt-0.5">2</div>
                                        <div>
                                            <div class="font-medium">本人確認手続き</div>
                                            <div class="text-gray-600">メールで送信される専用リンクから本人確認を実施</div>
                                        </div>
                                    </div>
                                    <div class="flex items-start space-x-3">
                                        <div class="w-6 h-6 bg-blue-600 text-white rounded-full flex items-center justify-center text-xs font-bold mt-0.5">3</div>
                                        <div>
                                            <div class="font-medium">Stripe決済手続き</div>
                                            <div class="text-gray-600">本人確認完了後、カード名義人様にStripe決済リンクをメールで送信いたします</div>
                                        </div>
                                    </div>
                                    <div class="flex items-start space-x-3">
                                        <div class="w-6 h-6 bg-green-600 text-white rounded-full flex items-center justify-center text-xs font-bold mt-0.5">✓</div>
                                        <div>
                                            <div class="font-medium">申込完了</div>
                                            <div class="text-gray-600">参加者ID、パスワードと詳細案内をメールでお送りします</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6 mb-8">
                                <h4 class="font-bold text-gray-900 mb-3 flex items-center">
                                    <i class="ri-information-line text-yellow-600 mr-2"></i>
                                    重要事項・注意事項
                                </h4>
                                <ul class="text-sm text-gray-700 space-y-2">
                                    <li>• 本人確認は外部システム（eKYC）を利用し、身分証明書の撮影が必要です</li>
                                    <li>• 参加費のお支払い確認後、正式な申込完了となります</li>
                                    <li>• 試験当日は身分証明書（学生証等）をご持参ください</li>
                                    <li>• 不正行為が発覚した場合は失格となります</li>
                                    <li>• 天災等やむを得ない事情により試験が中止となった場合は、参加費を返金いたします</li>
                                    <li>• 個人都合によるキャンセルの場合、返金はいたしかねます</li>
                                    <li>• 試験結果の発表は2026年1月末を予定しております</li>
                                    <li>• 優秀者ケンブリッジ研修プログラムの詳細は入賞者に別途ご連絡いたします</li>
                                </ul>
                            </div>
                            
                            <div id="submit-status" class="hidden p-4 rounded-xl mb-6"></div>
                            
                            <div class="flex items-center justify-between">
                                <button
                                    type="button"
                                    onclick="handlePrevStep()"
                                    class="bg-gray-500 text-white px-8 py-3 rounded-full font-semibold hover:bg-gray-600 transition-colors whitespace-nowrap"
                                >
                                    前に戻る
                                </button>
                                <button
                                    type="submit"
                                    id="submit-btn"
                                    class="bg-blue-600 text-white px-12 py-4 rounded-full text-lg font-semibold hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed whitespace-nowrap"
                                >
                                    申し込む
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
const individualRequiredFields = [
    'studentName',
    'school',
    'grade',
    'email',
    'phone',
    'guardianName',
    'guardianEmail',
    'guardianPhone'
];

const teamRequiredFields = [
    'teamName',
    'member1Name',
    'member1Email',
    'member2Name',
    'member2Email',
    'member3Name',
    'member3Email',
    'member4Name',
    'member4Email',
    'member5Name',
    'member5Email',
    'guardianName-team',
    'guardianEmail-team',
    'guardianPhone-team',
    'school-team'
];

function toggleRequiredFields(mode) {
    individualRequiredFields.forEach(name => {
        const field = document.querySelector(`[name="${name}"]`);
        if (!field) return;
        if (mode === 'individual') {
            field.setAttribute('required', 'required');
        } else {
            field.removeAttribute('required');
        }
    });

    teamRequiredFields.forEach(name => {
        const field = document.querySelector(`[name="${name}"]`);
        if (!field) return;
        if (mode === 'team') {
            field.setAttribute('required', 'required');
        } else {
            field.removeAttribute('required');
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    toggleRequiredFields('individual');
});

// 文字カウント更新
function updateCharCount(textareaId, counterId) {
    const textarea = document.getElementById(textareaId);
    const counter = document.getElementById(counterId);
    if (textarea && counter) {
        counter.textContent = textarea.value.length;
    }
}

// タブ切り替え
function switchTab(tabType) {
    const individualTab = document.getElementById('tab-individual');
    const teamTab = document.getElementById('tab-team');
    const individualContent = document.getElementById('individual-content');
    const teamContent = document.getElementById('team-content');
    const participationTypeInput = document.getElementById('participationType');
    
    if (tabType === 'individual') {
        // 個人戦タブをアクティブに
        individualTab.classList.remove('text-gray-500', 'border-transparent');
        individualTab.classList.add('text-blue-600', 'border-blue-600');
        teamTab.classList.remove('text-blue-600', 'border-blue-600');
        teamTab.classList.add('text-gray-500', 'border-transparent');
        
        // コンテンツの表示切り替え
        individualContent.style.display = 'block';
        teamContent.style.display = 'none';
        
        // 参加形式の値を更新
        participationTypeInput.value = '個人戦';
        toggleRequiredFields('individual');
    } else if (tabType === 'team') {
        // チーム戦タブをアクティブに
        teamTab.classList.remove('text-gray-500', 'border-transparent');
        teamTab.classList.add('text-blue-600', 'border-blue-600');
        individualTab.classList.remove('text-blue-600', 'border-blue-600');
        individualTab.classList.add('text-gray-500', 'border-transparent');
        
        // コンテンツの表示切り替え
        teamContent.style.display = 'block';
        individualContent.style.display = 'none';
        
        // 参加形式の値を更新
        participationTypeInput.value = '団体戦';
        toggleRequiredFields('team');
    }
}

// ステップ1の送信処理（確認画面へ）
function handleStep1Submit(e) {
    e.preventDefault();
    
    const form = document.getElementById('application-form-step1');
    const formData = new FormData(form);
    const participationType = document.getElementById('participationType').value;
    
    // ステップ1のデータを保存
    for (let [key, value] of formData.entries()) {
        const hiddenField = document.getElementById('hidden-' + key);
        if (hiddenField) {
            hiddenField.value = value;
        }
    }
    
    // 確認画面にデータを表示
    if (participationType === '個人戦') {
        displayIndividualConfirmation(formData);
    } else {
        displayTeamConfirmation(formData);
    }
    
    // 確認画面に進む
    document.getElementById('step1-form').style.display = 'none';
    document.getElementById('step1-confirm').style.display = 'block';
    
    // ページをスクロール
    document.getElementById('application').scrollIntoView({ behavior: 'smooth' });
    
    return false;
}

// 個人戦の確認画面表示
function displayIndividualConfirmation(formData) {
    document.getElementById('confirm-individual').style.display = 'block';
    document.getElementById('confirm-team').style.display = 'none';
    
    // データを表示
    document.getElementById('confirm-participationType').textContent = formData.get('participationType') || '';
    document.getElementById('confirm-studentName').textContent = formData.get('studentName') || '';
    document.getElementById('confirm-school').textContent = formData.get('school') || '';
    document.getElementById('confirm-grade').textContent = formData.get('grade') || '';
    document.getElementById('confirm-email').textContent = formData.get('email') || '';
    document.getElementById('confirm-phone').textContent = formData.get('phone') || '';
    document.getElementById('confirm-guardianName').textContent = formData.get('guardianName') || '';
    document.getElementById('confirm-guardianEmail').textContent = formData.get('guardianEmail') || '';
    document.getElementById('confirm-guardianPhone').textContent = formData.get('guardianPhone') || '';
    
    const specialRequests = formData.get('specialRequests') || '';
    if (specialRequests) {
        document.getElementById('confirm-specialRequests').textContent = specialRequests;
        document.getElementById('confirm-specialRequests-wrapper').style.display = 'block';
    } else {
        document.getElementById('confirm-specialRequests').textContent = '（未入力）';
        document.getElementById('confirm-specialRequests-wrapper').style.display = 'none';
    }
}

// チーム戦の確認画面表示
function displayTeamConfirmation(formData) {
    document.getElementById('confirm-individual').style.display = 'none';
    document.getElementById('confirm-team').style.display = 'block';
    
    const safeSetText = (id, value) => {
        const el = document.getElementById(id);
        if (el) {
            el.textContent = value || '';
        }
    };
    
    safeSetText('confirm-participationType-team', formData.get('participationType'));
    safeSetText('confirm-teamName', formData.get('teamName'));
    safeSetText('confirm-school-team', formData.get('school-team'));
    
    for (let i = 1; i <= 5; i++) {
        safeSetText(`confirm-member${i}Name`, formData.get(`member${i}Name`));
        safeSetText(`confirm-member${i}Email`, formData.get(`member${i}Email`));
    }
    
    safeSetText('confirm-guardianName-team', formData.get('guardianName-team'));
    safeSetText('confirm-guardianEmail-team', formData.get('guardianEmail-team'));
    safeSetText('confirm-guardianPhone-team', formData.get('guardianPhone-team'));
    
    const specialRequests = formData.get('specialRequests-team') || '';
    const specialRequestsEl = document.getElementById('confirm-specialRequests-team');
    const specialRequestsWrapper = document.getElementById('confirm-specialRequests-team-wrapper');
    
    if (specialRequestsEl && specialRequestsWrapper) {
        if (specialRequests) {
            specialRequestsEl.textContent = specialRequests;
            specialRequestsWrapper.style.display = 'block';
        } else {
            specialRequestsEl.textContent = '（未入力）';
            specialRequestsWrapper.style.display = 'none';
        }
    }
}

// 確認画面から編集画面に戻る
function handleBackToEdit() {
    document.getElementById('step1-confirm').style.display = 'none';
    document.getElementById('step1-form').style.display = 'block';
    
    // ページをスクロール
    document.getElementById('application').scrollIntoView({ behavior: 'smooth' });
}

// 確認画面から料金選択へ進む
function handleConfirmAndNext() {
    // 料金選択画面に進む
    document.getElementById('step1-confirm').style.display = 'none';
    document.getElementById('step2-form').style.display = 'block';
    
    // インジケーターの更新
    document.getElementById('step1-indicator').classList.remove('text-blue-600');
    document.getElementById('step1-indicator').classList.add('text-gray-400');
    document.getElementById('step2-indicator').classList.remove('text-gray-400');
    document.getElementById('step2-indicator').classList.add('text-blue-600');
    document.getElementById('step2-indicator').querySelector('div').classList.remove('bg-gray-200', 'text-gray-400');
    document.getElementById('step2-indicator').querySelector('div').classList.add('bg-blue-600', 'text-white');
    document.getElementById('step-divider').classList.remove('bg-gray-200');
    document.getElementById('step-divider').classList.add('bg-blue-600');
    
    // ページをスクロール
    document.getElementById('application').scrollIntoView({ behavior: 'smooth' });
}

// 前のステップに戻る（料金選択から確認画面へ）
function handlePrevStep() {
    document.getElementById('step2-form').style.display = 'none';
    document.getElementById('step1-confirm').style.display = 'block';
    
    // インジケーターの更新
    document.getElementById('step1-indicator').classList.remove('text-gray-400');
    document.getElementById('step1-indicator').classList.add('text-blue-600');
    document.getElementById('step2-indicator').classList.remove('text-blue-600');
    document.getElementById('step2-indicator').classList.add('text-gray-400');
    document.getElementById('step2-indicator').querySelector('div').classList.remove('bg-blue-600', 'text-white');
    document.getElementById('step2-indicator').querySelector('div').classList.add('bg-gray-200', 'text-gray-400');
    document.getElementById('step-divider').classList.remove('bg-blue-600');
    document.getElementById('step-divider').classList.add('bg-gray-200');
    
    // ページをスクロール
    document.getElementById('application').scrollIntoView({ behavior: 'smooth' });
}

// 最終送信処理
function handleFinalSubmit(e) {
    e.preventDefault();
    
    const form = document.getElementById('application-form-step2');
    const submitBtn = document.getElementById('submit-btn');
    const statusDiv = document.getElementById('submit-status');
    
    // 料金プラン選択のチェック
    const pricingType = form.querySelector('input[name="pricingType"]:checked');
    if (!pricingType) {
        statusDiv.className = 'p-4 rounded-xl mb-6 bg-red-50 text-red-800 border border-red-200';
        statusDiv.textContent = '料金プランを選択してください';
        statusDiv.classList.remove('hidden');
        return false;
    }
    
    submitBtn.disabled = true;
    submitBtn.textContent = '送信中...';
    
    // フォームデータを送信
    fetch('api/submit-application.php', {
        method: 'POST',
        body: new FormData(form)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // 申込情報送信成功
            statusDiv.className = 'p-4 rounded-xl mb-6 bg-green-50 text-green-800 border border-green-200';
            statusDiv.textContent = '申込を受け付けました。申込完了画面へ移動します...';
            statusDiv.classList.remove('hidden');
            
            // 申込情報をセッションストレージに保存
            console.log('=== API Response ===', data);
            console.log('application_id:', data.application_id);
            console.log('application_number:', data.application_number);
            console.log('amount:', data.amount);
            
            sessionStorage.setItem('application_id', data.application_id);
            sessionStorage.setItem('application_number', data.application_number);
            sessionStorage.setItem('amount', data.amount);
            
            // ログイン情報もセッションストレージに保存
            const loginEmails = [];
            if (data.guardian_email) loginEmails.push(data.guardian_email);
            if (data.student_email) loginEmails.push(data.student_email);
            if (Array.isArray(data.member_emails)) {
                data.member_emails.forEach(email => {
                    if (email) loginEmails.push(email);
                });
            }
            const uniqueEmails = [...new Set(loginEmails.filter(Boolean))];
            sessionStorage.setItem('application_emails', JSON.stringify(uniqueEmails));
            sessionStorage.setItem('application_email', uniqueEmails[0] || '');
            sessionStorage.setItem('participation_type', data.participation_type);
            
            // 確認ログ
            console.log('=== SessionStorage Saved ===');
            console.log('application_id:', sessionStorage.getItem('application_id'));
            console.log('application_number:', sessionStorage.getItem('application_number'));
            console.log('amount:', sessionStorage.getItem('amount'));
            
            // 2秒後に申込完了ページへ遷移
            setTimeout(() => {
                window.location.href = 'application-complete.php';
            }, 2000);
        } else {
            statusDiv.className = 'p-4 rounded-xl mb-6 bg-red-50 text-red-800 border border-red-200';
            statusDiv.textContent = '申込に失敗しました: ' + (data.error || 'もう一度お試しください');
            statusDiv.classList.remove('hidden');
            submitBtn.disabled = false;
            submitBtn.textContent = '申し込む';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        statusDiv.className = 'p-4 rounded-xl mb-6 bg-red-50 text-red-800 border border-red-200';
        statusDiv.textContent = '申込に失敗しました。もう一度お試しください。';
        statusDiv.classList.remove('hidden');
        submitBtn.disabled = false;
        submitBtn.textContent = '申し込む';
    });
    
    return false;
}
</script>

