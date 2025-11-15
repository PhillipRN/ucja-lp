<?php
/**
 * 管理者ログイン詳細デバッグ
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>管理者ログイン詳細デバッグ</h1>";
echo "<hr>";

// 1. 設定ファイルの確認
echo "<h2>1. 設定ファイルの確認</h2>";
require_once __DIR__ . '/config/config.php';

echo "<pre>";
echo "SUPABASE_URL: " . (defined('SUPABASE_URL') ? SUPABASE_URL : '未定義') . "\n";
echo "SUPABASE_SERVICE_KEY: " . (defined('SUPABASE_SERVICE_KEY') ? substr(SUPABASE_SERVICE_KEY, 0, 20) . '...' : '未定義') . "\n";
echo "</pre>";

// 2. SupabaseClient の読み込み確認
echo "<hr>";
echo "<h2>2. SupabaseClient の読み込み</h2>";
require_once __DIR__ . '/lib/SupabaseClient.php';

echo "<p style='color: green;'>✅ SupabaseClient.php が正常に読み込まれました</p>";

// 3. SupabaseClient のインスタンス化
echo "<hr>";
echo "<h2>3. SupabaseClient のインスタンス化</h2>";
try {
    $supabase = new SupabaseClient(SUPABASE_URL, SUPABASE_SERVICE_KEY);
    echo "<p style='color: green;'>✅ SupabaseClient のインスタンス化成功</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ エラー: " . $e->getMessage() . "</p>";
    exit;
}

// 4. 直接SQLでテスト（URLを構築）
echo "<hr>";
echo "<h2>4. 直接API呼び出しテスト</h2>";

$email = 'admin@example.com';
$testUrl = SUPABASE_URL . '/rest/v1/admin_users?select=*&email=eq.' . urlencode($email);

echo "<p><strong>テストURL:</strong></p>";
echo "<pre style='background: #f5f5f5; padding: 10px; overflow-x: auto;'>" . htmlspecialchars($testUrl) . "</pre>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $testUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'apikey: ' . SUPABASE_SERVICE_KEY,
    'Authorization: Bearer ' . SUPABASE_SERVICE_KEY,
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<p><strong>HTTPステータスコード:</strong> $httpCode</p>";

if ($error) {
    echo "<p style='color: red;'>❌ cURLエラー: $error</p>";
} else {
    echo "<p style='color: green;'>✅ cURL実行成功</p>";
}

echo "<p><strong>レスポンス:</strong></p>";
echo "<pre style='background: #f5f5f5; padding: 10px; max-height: 400px; overflow: auto;'>";
echo htmlspecialchars($response);
echo "</pre>";

$result = json_decode($response, true);

if (!empty($result)) {
    echo "<p style='color: green; font-size: 18px;'>✅ レコードが見つかりました！</p>";
    echo "<pre>";
    print_r($result);
    echo "</pre>";
} else {
    echo "<p style='color: red; font-size: 18px;'>❌ レコードが見つかりません</p>";
}

// 5. SupabaseClient経由でのテスト
echo "<hr>";
echo "<h2>5. SupabaseClient経由でのテスト</h2>";

try {
    $queryBuilder = $supabase->from('admin_users');
    echo "<p>✅ QueryBuilder作成成功</p>";
    
    $queryBuilder = $queryBuilder->select('*');
    echo "<p>✅ select()実行成功</p>";
    
    $queryBuilder = $queryBuilder->eq('email', $email);
    echo "<p>✅ eq()実行成功</p>";
    
    echo "<p><strong>クエリを実行中...</strong></p>";
    
    $result = $queryBuilder->execute();
    
    echo "<p><strong>execute()の結果:</strong></p>";
    echo "<pre>";
    print_r($result);
    echo "</pre>";
    
    if ($result['success'] && !empty($result['data'])) {
        echo "<p style='color: green; font-size: 18px;'>✅ データ取得成功！</p>";
    } else {
        echo "<p style='color: red; font-size: 18px;'>❌ データが空です</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ エラー: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// 6. is_activeを含めたテスト
echo "<hr>";
echo "<h2>6. is_active条件を含めたテスト</h2>";

try {
    echo "<p><strong>条件: email='$email' AND is_active=true</strong></p>";
    
    $result = $supabase->from('admin_users')
        ->select('*')
        ->eq('email', $email)
        ->eq('is_active', true)
        ->execute();
    
    echo "<p><strong>結果:</strong></p>";
    echo "<pre>";
    print_r($result);
    echo "</pre>";
    
    if ($result['success'] && !empty($result['data'])) {
        echo "<p style='color: green; font-size: 18px;'>✅ is_active=true条件でもデータ取得成功！</p>";
        
        // パスワード検証
        $admin = $result['data'][0];
        $password = 'admin123';
        
        echo "<hr>";
        echo "<h2>7. パスワード検証</h2>";
        echo "<p>パスワード: <strong>$password</strong></p>";
        
        if (password_verify($password, $admin['password_hash'])) {
            echo "<p style='color: green; font-size: 20px; font-weight: bold;'>✅✅✅ パスワード検証成功！ログインできるはずです！</p>";
        } else {
            echo "<p style='color: red; font-size: 20px;'>❌ パスワードが一致しません</p>";
            
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
</pre>";
        }
    } else {
        echo "<p style='color: red; font-size: 18px;'>❌ is_active=true条件でデータが見つかりません</p>";
        
        // is_activeなしで再試行
        echo "<p><strong>is_active条件なしで再検索...</strong></p>";
        $result2 = $supabase->from('admin_users')
            ->select('*')
            ->eq('email', $email)
            ->execute();
        
        if ($result2['success'] && !empty($result2['data'])) {
            echo "<p style='color: orange;'>⚠️ is_activeなしでは見つかりました</p>";
            echo "<p>is_activeの値: " . ($result2['data'][0]['is_active'] ? 'true' : 'false') . "</p>";
            echo "<p>問題: is_active条件の処理に問題があります</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ エラー: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>";
    echo $e->getTraceAsString();
    echo "</pre>";
}

?>

<style>
    body {
        font-family: sans-serif;
        max-width: 1200px;
        margin: 20px auto;
        padding: 20px;
        background: #f5f5f5;
    }
    h1, h2, h3 {
        color: #333;
    }
    pre {
        white-space: pre-wrap;
        word-wrap: break-word;
    }
</style>

