@echo off
REM Cambridge Exam - 開発サーバー起動スクリプト (Windows用)

echo ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
echo   Cambridge Exam 開発サーバー
echo ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
echo.

REM PHPがインストールされているか確認
where php >nul 2>nul
if %errorlevel% neq 0 (
    echo ❌ PHPがインストールされていません。
    echo PHPをインストールしてから再度実行してください。
    pause
    exit /b 1
)

REM PHPバージョン表示
php -v | findstr /C:"PHP"
echo.

REM ポート番号の設定（デフォルト: 8000）
set PORT=8000
if not "%1"=="" set PORT=%1

echo 🚀 開発サーバーを起動しています...
echo ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
echo.
echo 📱 アクセスURL:
echo    http://localhost:%PORT%/index.php
echo.
echo 📝 ファイル構成:
echo    index.php              - メインページ
echo    components/*.php       - コンポーネント
echo    .htaccess             - Apache設定
echo.
echo ⚡ 開発のヒント:
echo    - ファイルを編集すると自動的に反映されます
echo    - ブラウザをリロードして確認してください
echo    - エラーが出たらブラウザのコンソールを確認
echo.
echo 🛑 サーバーを停止するには Ctrl+C を押してください
echo ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
echo.

REM PHPサーバーを起動
php -S localhost:%PORT%

