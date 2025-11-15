<?php
/**
 * Update Profile API
 * プロフィール情報の更新
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../lib/SupabaseClient.php';
require_once __DIR__ . '/../../lib/AuthHelper.php';

try {
    // ログインチェック
    AuthHelper::startSession();
    if (!AuthHelper::isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'ログインが必要です']);
        exit;
    }
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
        exit;
    }
    
    // ユーザー情報取得
    $userId = AuthHelper::getUserId();
    $participationType = AuthHelper::getParticipationType();
    
    // Supabaseクライアント初期化
    $supabase = new SupabaseClient(SUPABASE_URL, SUPABASE_SERVICE_KEY);
    
    // 申込情報の取得（参加形式の確認）
    $applicationResult = $supabase->from('applications')
        ->select('*')
        ->eq('id', $userId)
        ->single();
    
    if (!$applicationResult['success'] || empty($applicationResult['data'])) {
        throw new Exception('申込情報が見つかりません');
    }
    
    $application = $applicationResult['data'];
    $actualParticipationType = $application['participation_type'];
    
    // 個人戦の場合
    if ($actualParticipationType === 'individual') {
        // 更新可能なフィールド
        $updateData = [];
        
        if (isset($_POST['student_email'])) {
            $updateData['student_email'] = $_POST['student_email'];
        }
        if (isset($_POST['student_phone'])) {
            $updateData['student_phone'] = $_POST['student_phone'];
        }
        if (isset($_POST['guardian_name'])) {
            $updateData['guardian_name'] = $_POST['guardian_name'];
        }
        if (isset($_POST['guardian_email'])) {
            $updateData['guardian_email'] = $_POST['guardian_email'];
        }
        if (isset($_POST['guardian_phone'])) {
            $updateData['guardian_phone'] = $_POST['guardian_phone'];
        }
        
        // バリデーション
        if (empty($updateData['guardian_name']) || empty($updateData['guardian_email']) || empty($updateData['guardian_phone'])) {
            throw new Exception('保護者の情報は必須です');
        }
        
        // メールアドレスの形式チェック
        if (!empty($updateData['student_email']) && !filter_var($updateData['student_email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('生徒のメールアドレスの形式が正しくありません');
        }
        if (!empty($updateData['guardian_email']) && !filter_var($updateData['guardian_email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('保護者のメールアドレスの形式が正しくありません');
        }
        
        // 更新実行
        $result = $supabase->from('individual_applications')
            ->select('id')
            ->eq('application_id', $userId)
            ->single();
        
        if ($result['success'] && !empty($result['data'])) {
            $individualId = $result['data']['id'];
            
            $updateResult = $supabase->update('individual_applications', $updateData, [
                'id' => 'eq.' . $individualId
            ]);
            
            if (!$updateResult['success']) {
                throw new Exception('プロフィールの更新に失敗しました');
            }
            
            // セッションのメールアドレスも更新（ログイン用メールアドレスが変更された場合）
            if (isset($updateData['student_email']) || isset($updateData['guardian_email'])) {
                $currentEmail = AuthHelper::getUserEmail();
                // 現在のログインメールアドレスが変更された場合のみセッション更新
                if ($currentEmail === ($_POST['old_student_email'] ?? '') && isset($updateData['student_email'])) {
                    $_SESSION['email'] = $updateData['student_email'];
                } elseif ($currentEmail === ($_POST['old_guardian_email'] ?? '') && isset($updateData['guardian_email'])) {
                    $_SESSION['email'] = $updateData['guardian_email'];
                }
            }
        } else {
            throw new Exception('申込情報が見つかりません');
        }
        
    } else {
        // チーム戦の場合
        // メンバーIDが送信されている場合はメンバー情報の更新
        if (isset($_POST['member_id']) && !empty($_POST['member_id'])) {
            // チームメンバー情報の更新
            $updateData = [];
            
            if (isset($_POST['member_name'])) {
                $updateData['member_name'] = $_POST['member_name'];
            }
            if (isset($_POST['member_email'])) {
                $updateData['member_email'] = $_POST['member_email'];
            }
            if (isset($_POST['member_phone'])) {
                $updateData['member_phone'] = $_POST['member_phone'];
            }
            
            // バリデーション
            if (empty($updateData['member_name']) || empty($updateData['member_email'])) {
                throw new Exception('メンバー情報（氏名・メールアドレス）は必須です');
            }
            
            // メールアドレスの形式チェック
            if (!filter_var($updateData['member_email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception('メールアドレスの形式が正しくありません');
            }
            
            // 更新実行
            $memberId = $_POST['member_id'];
            $updateResult = $supabase->update('team_members', $updateData, [
                'id' => 'eq.' . $memberId
            ]);
            
            if (!$updateResult['success']) {
                throw new Exception('メンバー情報の更新に失敗しました');
            }
            
            // セッションのメールアドレスも更新
            if (isset($updateData['member_email'])) {
                $currentEmail = AuthHelper::getUserEmail();
                if ($currentEmail === ($_POST['old_member_email'] ?? '')) {
                    $_SESSION['email'] = $updateData['member_email'];
                }
            }
            
        } else {
            // 代表者（保護者）情報の更新
            $updateData = [];
            
            if (isset($_POST['guardian_name'])) {
                $updateData['guardian_name'] = $_POST['guardian_name'];
            }
            if (isset($_POST['guardian_email'])) {
                $updateData['guardian_email'] = $_POST['guardian_email'];
            }
            if (isset($_POST['guardian_phone'])) {
                $updateData['guardian_phone'] = $_POST['guardian_phone'];
            }
            
            // バリデーション
            if (empty($updateData['guardian_name']) || empty($updateData['guardian_email']) || empty($updateData['guardian_phone'])) {
                throw new Exception('代表者の情報は必須です');
            }
            
            // メールアドレスの形式チェック
            if (!filter_var($updateData['guardian_email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception('メールアドレスの形式が正しくありません');
            }
            
            // 更新実行
            $result = $supabase->from('team_applications')
                ->select('id')
                ->eq('application_id', $userId)
                ->single();
            
            if ($result['success'] && !empty($result['data'])) {
                $teamId = $result['data']['id'];
                
                $updateResult = $supabase->update('team_applications', $updateData, [
                    'id' => 'eq.' . $teamId
                ]);
                
                if (!$updateResult['success']) {
                    throw new Exception('プロフィールの更新に失敗しました');
                }
                
                // セッションのメールアドレスも更新
                if (isset($updateData['guardian_email'])) {
                    $currentEmail = AuthHelper::getUserEmail();
                    if ($currentEmail === ($_POST['old_guardian_email'] ?? '')) {
                        $_SESSION['email'] = $updateData['guardian_email'];
                    }
                }
            } else {
                throw new Exception('申込情報が見つかりません');
            }
        }
    }
    
    // 成功レスポンス
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'プロフィール情報を更新しました'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

