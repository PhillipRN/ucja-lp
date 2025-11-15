<?php
/**
 * 管理者ログインテスト
 * このファイルは使用後に削除してください！
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/lib/SupabaseClient.php';

$email = 'admin@example.com';
$password = 'admin123';

echo "<h1>管理者ログインテスト</h1>";
echo "<hr>";

// 1. Supabaseから管理者情報を取得
echo "<h2>1. 管理者情報取得テスト</h2>";
$supabase = new SupabaseClient(SUPABASE_URL, SUPABASE_SERVICE_KEY);

$result = $supabase->from('admin_users')
    ->select('*')
    ->eq('email', $email)
    ->eq('is_active', true)
    ->single();

echo "<pre>";
echo "取得結果:\n";
print_r($result);
echo "</pre>";

if (!$result['success'] || empty($result['data'])) {
    echo "<p style='color: red;'>❌ 管理者が見つかりません</p>";
    exit;
}

$admin = $result['data'];
echo "<p style='color: green;'>✅ 管理者が見つかりました</p>";
echo "<pre>";
echo "ID: " . $admin['id'] . "\n";
echo "Username: " . $admin['username'] . "\n";
echo "Email: " . $admin['email'] . "\n";
echo "Role: " . $admin['role'] . "\n";
echo "Is Active: " . ($admin['is_active'] ? 'true' : 'false') . "\n";
echo "Password Hash: " . substr($admin['password_hash'], 0, 30) . "...\n";
echo "</pre>";

// 2. パスワード検証テスト
echo "<hr>";
echo "<h2>2. パスワード検証テスト</h2>";
echo "パスワード: <strong>$password</strong><br>";
echo "ハッシュ: <strong>" . substr($admin['password_hash'], 0, 50) . "...</strong><br><br>";

if (password_verify($password, $admin['password_hash'])) {
    echo "<p style='color: green; font-size: 20px;'>✅ パスワードが一致しました！</p>";
    echo "<p>ログインに成功するはずです。</p>";
} else {
    echo "<p style='color: red; font-size: 20px;'>❌ パスワードが一致しません</p>";
    echo "<p>パスワードハッシュが正しくありません。</p>";
    
    // 正しいハッシュを生成
    $correctHash = password_hash($password, PASSWORD_BCRYPT);
    echo "<h3>正しいパスワードハッシュ:</h3>";
    echo "<pre style='background: #f5f5f5; padding: 10px;'>$correctHash</pre>";
    echo "<h3>Supabaseで実行するSQL:</h3>";
    echo "<pre style='background: #263238; color: #aed581; padding: 15px;'>
UPDATE admin_users 
SET password_hash = '$correctHash',
    updated_at = CURRENT_TIMESTAMP
WHERE email = '$email';

-- 確認
SELECT username, email, role, is_active 
FROM admin_users 
WHERE email = '$email';
</pre>";
}

// 3. APIテスト
echo "<hr>";
echo "<h2>3. ログインAPIテスト</h2>";
echo "<button onclick=\"testAPI()\">APIをテスト</button>";
echo "<div id=\"apiResult\" style=\"margin-top: 10px;\"></div>";

?>

<script>
async function testAPI() {
    const resultDiv = document.getElementById('apiResult');
    resultDiv.innerHTML = '<p>テスト中...</p>';
    
    try {
        const response = await fetch('api/admin/login.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                email: '<?php echo $email; ?>',
                password: '<?php echo $password; ?>'
            })
        });
        
        const result = await response.json();
        
        resultDiv.innerHTML = `
            <h3>APIレスポンス:</h3>
            <pre style="background: #f5f5f5; padding: 10px;">${JSON.stringify(result, null, 2)}</pre>
        `;
        
        if (result.success) {
            resultDiv.innerHTML += '<p style="color: green; font-size: 18px;">✅ APIログイン成功！</p>';
        } else {
            resultDiv.innerHTML += '<p style="color: red; font-size: 18px;">❌ APIログイン失敗</p>';
        }
    } catch (error) {
        resultDiv.innerHTML = `<p style="color: red;">エラー: ${error.message}</p>`;
    }
}
</script>

<style>
    body {
        font-family: sans-serif;
        max-width: 1000px;
        margin: 20px auto;
        padding: 20px;
    }
    button {
        background: #4CAF50;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 16px;
    }
    button:hover {
        background: #45a049;
    }
</style>

