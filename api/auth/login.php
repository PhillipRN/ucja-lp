<?php
/**
 * Login API
 * メールアドレスと申込番号でログイン
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../lib/SupabaseClient.php';
require_once __DIR__ . '/../../lib/AuthHelper.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
        exit;
    }
    
    // POSTデータの取得
    $email = $_POST['email'] ?? '';
    $applicationNumber = $_POST['application_number'] ?? '';
    
    // バリデーション
    if (empty($email) || empty($applicationNumber)) {
        throw new Exception('メールアドレスと申込番号を入力してください');
    }
    
    // メールアドレスの形式チェック
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('有効なメールアドレスを入力してください');
    }
    
    // Supabaseから申込情報を検索
    $supabase = new SupabaseClient(SUPABASE_URL, SUPABASE_SERVICE_KEY);
    
    // まず、applicationsテーブルから申込番号で検索
    $applicationResult = $supabase->from('applications')
        ->select('*')
        ->eq('application_number', $applicationNumber)
        ->single();
    
    if (!$applicationResult['success'] || empty($applicationResult['data'])) {
        throw new Exception('申込情報が見つかりません。申込番号を確認してください。');
    }
    
    $application = $applicationResult['data'];
    $applicationType = $application['participation_type'];
    
    // 個人戦の場合
    if ($applicationType === 'individual') {
        $individualResult = $supabase->from('individual_applications')
            ->select('*')
            ->eq('application_id', $application['id'])
            ->single();
        
        if (!$individualResult['success'] || empty($individualResult['data'])) {
            throw new Exception('申込情報が見つかりません');
        }
        
        $individual = $individualResult['data'];
        
        // メールアドレスを正規化して照合（生徒または保護者のメールアドレス）
        $email = trim(strtolower($email));
        $studentEmail = trim(strtolower($individual['student_email']));
        $guardianEmail = trim(strtolower($individual['guardian_email']));
        
        if ($studentEmail !== $email && $guardianEmail !== $email) {
            throw new Exception('メールアドレスが一致しません');
        }
        
        // ログイン成功
        $loginData = [
            'id' => $application['id'],
            'application_number' => $application['application_number'],
            'participation_type' => 'individual',
            'email' => $email,
            'student_name' => $individual['student_name'],
            'school' => $individual['school']
        ];
        
    } else {
        // チーム戦の場合
        $teamResult = $supabase->from('team_applications')
            ->select('*')
            ->eq('application_id', $application['id'])
            ->single();
        
        if (!$teamResult['success'] || empty($teamResult['data'])) {
            throw new Exception('申込情報が見つかりません');
        }
        
        $team = $teamResult['data'];
        
        // メールアドレスの照合（保護者のメールアドレス）
        // またはチームメンバーのメールアドレスでもログイン可能にする
        $email = trim(strtolower($email)); // メールアドレスを正規化
        $guardianEmail = trim(strtolower($team['guardian_email']));
        $isGuardian = ($guardianEmail === $email);
        
        // チームメンバーのメールアドレスもチェック
        // まず、全メンバーを取得してメールアドレスを正規化して比較
        $allMembersResult = $supabase->from('team_members')
            ->select('*')
            ->eq('team_application_id', $team['id'])
            ->execute();
        
        $isMember = false;
        $memberData = null;
        
        if ($allMembersResult['success'] && !empty($allMembersResult['data'])) {
            foreach ($allMembersResult['data'] as $member) {
                $memberEmail = trim(strtolower($member['member_email']));
                if ($memberEmail === $email) {
                    $isMember = true;
                    $memberData = $member;
                    break;
                }
            }
        }
        
        if (!$isGuardian && !$isMember) {
            // デバッグ情報を含めたエラーメッセージ
            $errorMsg = 'メールアドレスが一致しません。';
            $errorMsg .= ' チーム申込ID: ' . $team['id'];
            $errorMsg .= ' 登録されているメンバー数: ' . (isset($allMembersResult['data']) ? count($allMembersResult['data']) : 0);
            throw new Exception($errorMsg);
        }
        
        // ログイン成功
        $loginData = [
            'id' => $application['id'],
            'application_number' => $application['application_number'],
            'participation_type' => 'team',
            'email' => $email,
            'team_name' => $team['team_name'],
            'school' => $team['school'],
            'is_representative' => $isGuardian
        ];
    }
    
    // セッションにログイン情報を保存
    AuthHelper::login($loginData);
    
    // 成功レスポンス
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'ログインに成功しました',
        'data' => [
            'application_number' => $loginData['application_number'],
            'participation_type' => $loginData['participation_type']
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

