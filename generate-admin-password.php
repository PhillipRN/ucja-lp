<?php
/**
 * 管理者パスワードハッシュ生成ツール
 * このファイルを実行後、必ず削除してください！
 */

// パスワードを設定
$password = 'admin123';

// ハッシュを生成
$hash = password_hash($password, PASSWORD_BCRYPT);

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>パスワードハッシュ生成</title>
    <style>
        body {
            font-family: sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #4CAF50;
            padding-bottom: 10px;
        }
        .info {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .hash {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            font-family: monospace;
            word-break: break-all;
            margin: 20px 0;
        }
        .sql {
            background: #263238;
            color: #aed581;
            padding: 20px;
            border-radius: 5px;
            font-family: monospace;
            font-size: 14px;
            overflow-x: auto;
            margin: 20px 0;
        }
        .warning {
            background: #fff3e0;
            border-left: 4px solid #ff9800;
            padding: 15px;
            margin: 20px 0;
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
</head>
<body>
    <div class="container">
        <h1>🔐 管理者パスワードハッシュ生成</h1>
        
        <div class="info">
            <p><strong>生成されたパスワード:</strong> <?php echo htmlspecialchars($password); ?></p>
        </div>

        <div class="hash">
            <strong>パスワードハッシュ:</strong><br><br>
            <?php echo $hash; ?>
        </div>

        <h2>📋 Supabaseで実行するSQL</h2>
        <div class="sql">
UPDATE admin_users 
SET password_hash = '<?php echo $hash; ?>',
    updated_at = CURRENT_TIMESTAMP
WHERE email = 'admin@example.com';

-- 確認
SELECT username, email, role, is_active 
FROM admin_users 
WHERE email = 'admin@example.com';
        </div>

        <div class="warning">
            <strong>⚠️ 重要:</strong>
            <ul>
                <li>上記のSQLをSupabaseで実行してください</li>
                <li>実行後、<strong>このファイル（generate-admin-password.php）を必ず削除してください！</strong></li>
                <li>セキュリティリスクになります</li>
            </ul>
        </div>

        <h2>🧪 テスト</h2>
        <p>SQLを実行した後、以下の情報でログインできます：</p>
        <div class="info">
            <p><strong>メールアドレス:</strong> admin@example.com</p>
            <p><strong>パスワード:</strong> admin123</p>
        </div>

        <button onclick="copySQL()">SQLをコピー</button>
    </div>

    <script>
        function copySQL() {
            const sql = `UPDATE admin_users 
SET password_hash = '<?php echo $hash; ?>',
    updated_at = CURRENT_TIMESTAMP
WHERE email = 'admin@example.com';`;
            
            navigator.clipboard.writeText(sql).then(() => {
                alert('SQLをクリップボードにコピーしました！');
            });
        }
    </script>
</body>
</html>

