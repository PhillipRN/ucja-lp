<header class="bg-white shadow-sm sticky top-0 z-50">
    <div class="container mx-auto px-6 py-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <div>
                    <a href="index.php" class="block focus:outline-none">
                        <h1 class="text-xl font-bold text-gray-900">UNIV. CAMBRIDGE JAPAN ACADEMY</h1>
                    </a>
                    <p class="text-sm text-gray-600">英単語高校選手権</p>
                </div>
            </div>
            
            <nav class="hidden md:flex items-center space-x-8">
                <a href="index.php#about" class="text-gray-700 hover:text-blue-600 transition-colors cursor-pointer">英単語高校選手権について</a>
                <a href="index.php#pricing" class="text-gray-700 hover:text-blue-600 transition-colors cursor-pointer">参加費</a>
                <a href="index.php#prizes" class="text-gray-700 hover:text-blue-600 transition-colors cursor-pointer">参加特典・賞品</a>
                <a href="index.php#application" class="bg-blue-600 text-white px-6 py-2 rounded-full hover:bg-blue-700 transition-colors cursor-pointer whitespace-nowrap">申込フォーム</a>
            </nav>
            
            <button id="mobile-menu-button" class="md:hidden p-2 focus:outline-none" onclick="toggleMobileMenu()">
                <i id="menu-icon" class="ri-menu-line text-2xl text-gray-700"></i>
            </button>
        </div>
    </div>
    
    <!-- モバイルメニュー -->
    <div id="mobile-menu" class="hidden md:hidden bg-white border-t border-gray-200 shadow-lg">
        <nav class="container mx-auto px-6 py-4">
            <div class="flex flex-col space-y-4">
                <a href="index.php#about" class="text-gray-700 hover:text-blue-600 transition-colors py-2 border-b border-gray-100" onclick="closeMobileMenu()">英単語高校選手権について</a>
                <a href="index.php#pricing" class="text-gray-700 hover:text-blue-600 transition-colors py-2 border-b border-gray-100" onclick="closeMobileMenu()">参加費</a>
                <a href="index.php#prizes" class="text-gray-700 hover:text-blue-600 transition-colors py-2 border-b border-gray-100" onclick="closeMobileMenu()">参加特典・賞品</a>
                <a href="index.php#application" class="bg-blue-600 text-white px-6 py-3 rounded-full hover:bg-blue-700 transition-colors text-center" onclick="closeMobileMenu()">申込フォーム</a>
            </div>
        </nav>
    </div>
</header>

<script>
function toggleMobileMenu() {
    const mobileMenu = document.getElementById('mobile-menu');
    const menuIcon = document.getElementById('menu-icon');
    
    if (mobileMenu.classList.contains('hidden')) {
        // メニューを開く
        mobileMenu.classList.remove('hidden');
        menuIcon.classList.remove('ri-menu-line');
        menuIcon.classList.add('ri-close-line');
    } else {
        // メニューを閉じる
        mobileMenu.classList.add('hidden');
        menuIcon.classList.remove('ri-close-line');
        menuIcon.classList.add('ri-menu-line');
    }
}

function closeMobileMenu() {
    const mobileMenu = document.getElementById('mobile-menu');
    const menuIcon = document.getElementById('menu-icon');
    
    mobileMenu.classList.add('hidden');
    menuIcon.classList.remove('ri-close-line');
    menuIcon.classList.add('ri-menu-line');
}

// ウィンドウサイズが変更されたときにメニューを閉じる
window.addEventListener('resize', function() {
    if (window.innerWidth >= 768) { // md breakpoint
        closeMobileMenu();
    }
});
</script>

