<?php
/**
 * Logout Page
 * ログアウト処理
 */

require_once __DIR__ . '/lib/AuthHelper.php';

// ログアウト処理
AuthHelper::logout();

// ログインページにリダイレクト
header('Location: login.php?logout=success');
exit;

