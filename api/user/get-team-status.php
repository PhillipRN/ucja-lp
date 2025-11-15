<?php
/**
 * Get Team Status API
 * チームステータス取得（メンバー情報・支払い・試験結果）
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
    
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
        exit;
    }
    
    // ユーザー情報取得
    $userId = AuthHelper::getUserId();
    $participationType = AuthHelper::getParticipationType();
    
    // 個人戦の場合はエラー
    if ($participationType !== 'team') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'この機能はチーム戦のみ利用できます']);
        exit;
    }
    
    // Supabaseクライアント初期化
    $supabase = new SupabaseClient(SUPABASE_URL, SUPABASE_SERVICE_KEY);
    
    // 申込情報取得
    $applicationResult = $supabase->from('applications')
        ->select('*')
        ->eq('id', $userId)
        ->single();
    
    if (!$applicationResult['success'] || empty($applicationResult['data'])) {
        throw new Exception('申込情報が見つかりません');
    }
    
    $application = $applicationResult['data'];
    
    // チーム申込情報取得
    $teamResult = $supabase->from('team_applications')
        ->select('*')
        ->eq('application_id', $userId)
        ->single();
    
    if (!$teamResult['success'] || empty($teamResult['data'])) {
        throw new Exception('チーム情報が見つかりません');
    }
    
    $team = $teamResult['data'];
    $teamApplicationId = $team['id'];
    
    // チームメンバー情報取得
    $membersResult = $supabase->from('team_members')
        ->select('*')
        ->eq('team_application_id', $teamApplicationId)
        ->order('member_number', 'asc')
        ->execute();
    
    $members = $membersResult['data'] ?? [];
    
    // 各メンバーの試験結果を取得
    $membersWithResults = [];
    $totalPayments = 0;
    $totalKycCompleted = 0;
    $totalExamCompleted = 0;
    $examScores = [];
    
    foreach ($members as $member) {
        $memberData = $member;
        
        // 支払い状況カウント
        if ($member['payment_status'] === 'completed') {
            $totalPayments++;
        }
        
        // 本人確認完了カウント
        if ($member['kyc_status'] === 'completed') {
            $totalKycCompleted++;
        }
        
        // 試験結果を取得（team_member_idで検索）
        try {
            $examResult = $supabase->from('exam_results')
                ->select('*')
                ->eq('team_member_id', $member['id'])
                ->single();
            
            if ($examResult['success'] && !empty($examResult['data'])) {
                $result = $examResult['data'];
                $memberData['exam_result'] = $result;
                $memberData['has_exam_result'] = true;
                $totalExamCompleted++;
                
                // スコアを配列に追加（チーム合計点計算用）
                $examScores[] = [
                    'member_number' => $member['member_number'],
                    'member_name' => $member['member_name'],
                    'score' => $result['final_score'],
                    'time_taken' => $result['total_time_seconds']
                ];
            } else {
                $memberData['exam_result'] = null;
                $memberData['has_exam_result'] = false;
            }
        } catch (Exception $e) {
            // 試験結果がない場合はスキップ
            $memberData['exam_result'] = null;
            $memberData['has_exam_result'] = false;
        }
        
        $membersWithResults[] = $memberData;
    }
    
    // チーム合計点を計算（上位4名の合計）
    $teamTotalScore = 0;
    $topFourScores = [];
    if (!empty($examScores)) {
        // スコアの高い順にソート
        usort($examScores, function($a, $b) {
            return $b['score'] - $a['score'];
        });
        
        // 上位4名を取得
        $topFourScores = array_slice($examScores, 0, 4);
        
        // 合計点を計算
        foreach ($topFourScores as $scoreData) {
            $teamTotalScore += $scoreData['score'];
        }
    }
    
    // チーム成立判定（全員の支払いが完了しているか）
    $isTeamComplete = ($totalPayments === 5);
    
    // レスポンスデータ
    $responseData = [
        'success' => true,
        'team' => $team,
        'application' => $application,
        'members' => $membersWithResults,
        'stats' => [
            'total_members' => count($members),
            'total_payments' => $totalPayments,
            'total_kyc_completed' => $totalKycCompleted,
            'total_exam_completed' => $totalExamCompleted,
            'is_team_complete' => $isTeamComplete,
            'payment_progress' => round(($totalPayments / 5) * 100),
            'kyc_progress' => round(($totalKycCompleted / 5) * 100),
            'exam_progress' => round(($totalExamCompleted / 5) * 100)
        ],
        'team_score' => [
            'total_score' => $teamTotalScore,
            'top_four_scores' => $topFourScores,
            'all_scores' => $examScores
        ]
    ];
    
    // 成功レスポンス
    http_response_code(200);
    echo json_encode($responseData);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ], JSON_UNESCAPED_UNICODE);
}

