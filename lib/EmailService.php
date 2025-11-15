<?php
/**
 * Email Service using SendGrid
 * SendGridを使用したメール送信サービス
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';

use SendGrid\Mail\Mail;

class EmailService {
    private $sendgrid;
    private $fromEmail;
    private $fromName;
    
    public function __construct() {
        if (!defined('SENDGRID_API_KEY')) {
            throw new Exception('SENDGRID_API_KEY is not defined');
        }
        
        $this->sendgrid = new \SendGrid(SENDGRID_API_KEY);
        $this->fromEmail = SENDGRID_FROM_EMAIL ?? 'noreply@univ-cambridge-japan.academy';
        $this->fromName = SENDGRID_FROM_NAME ?? 'UCJA 事務局';
    }
    
    /**
     * シンプルなメール送信
     * 
     * @param string $to 送信先メールアドレス
     * @param string $subject 件名
     * @param string $htmlContent HTML本文
     * @param string $textContent テキスト本文（オプション）
     * @return array 送信結果
     */
    public function sendEmail($to, $subject, $htmlContent, $textContent = null) {
        try {
            $email = new Mail();
            $email->setFrom($this->fromEmail, $this->fromName);
            $email->setSubject($subject);
            $email->addTo($to);
            $email->addContent("text/html", $htmlContent);
            
            if ($textContent) {
                $email->addContent("text/plain", $textContent);
            }
            
            $response = $this->sendgrid->send($email);
            
            return [
                'success' => $response->statusCode() >= 200 && $response->statusCode() < 300,
                'status_code' => $response->statusCode(),
                'body' => $response->body(),
                'headers' => $response->headers()
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Dynamic Templateを使用したメール送信
     * 
     * @param string $to 送信先メールアドレス
     * @param string $templateId SendGrid Dynamic Template ID
     * @param array $dynamicData テンプレート変数
     * @return array 送信結果
     */
    public function sendTemplateEmail($to, $templateId, $dynamicData = []) {
        try {
            $email = new Mail();
            $email->setFrom($this->fromEmail, $this->fromName);
            $email->addTo($to);
            $email->setTemplateId($templateId);
            $email->addDynamicTemplateDatas($dynamicData);
            
            $response = $this->sendgrid->send($email);
            
            return [
                'success' => $response->statusCode() >= 200 && $response->statusCode() < 300,
                'status_code' => $response->statusCode(),
                'body' => $response->body(),
                'headers' => $response->headers()
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 一斉送信（バッチ送信）
     * 
     * @param array $recipients 送信先の配列 [['email' => '...', 'name' => '...'], ...]
     * @param string $subject 件名
     * @param string $htmlContent HTML本文
     * @param string $textContent テキスト本文（オプション）
     * @return array 送信結果
     */
    public function sendBulkEmail($recipients, $subject, $htmlContent, $textContent = null) {
        $results = [
            'total' => count($recipients),
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];
        
        foreach ($recipients as $recipient) {
            $email = $recipient['email'] ?? null;
            $name = $recipient['name'] ?? '';
            
            if (!$email) {
                $results['failed']++;
                $results['errors'][] = 'Invalid email address';
                continue;
            }
            
            // 個別に送信変数を置換
            $personalizedHtml = $this->replaceVariables($htmlContent, $recipient);
            $personalizedText = $textContent ? $this->replaceVariables($textContent, $recipient) : null;
            
            $result = $this->sendEmail($email, $subject, $personalizedHtml, $personalizedText);
            
            if ($result['success']) {
                $results['success']++;
            } else {
                $results['failed']++;
                $results['errors'][] = [
                    'email' => $email,
                    'error' => $result['error'] ?? 'Unknown error'
                ];
            }
            
            // レート制限対策（少し待機）
            usleep(100000); // 0.1秒
        }
        
        return $results;
    }
    
    /**
     * Dynamic Templateを使用した一斉送信
     * 
     * @param array $recipients 送信先の配列 [['email' => '...', 'data' => [...]], ...]
     * @param string $templateId SendGrid Dynamic Template ID
     * @return array 送信結果
     */
    public function sendBulkTemplateEmail($recipients, $templateId) {
        $results = [
            'total' => count($recipients),
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];
        
        foreach ($recipients as $recipient) {
            $email = $recipient['email'] ?? null;
            $data = $recipient['data'] ?? [];
            
            if (!$email) {
                $results['failed']++;
                $results['errors'][] = 'Invalid email address';
                continue;
            }
            
            $result = $this->sendTemplateEmail($email, $templateId, $data);
            
            if ($result['success']) {
                $results['success']++;
            } else {
                $results['failed']++;
                $results['errors'][] = [
                    'email' => $email,
                    'error' => $result['error'] ?? 'Unknown error'
                ];
            }
            
            // レート制限対策
            usleep(100000); // 0.1秒
        }
        
        return $results;
    }
    
    /**
     * 変数の置換
     * 
     * @param string $content コンテンツ
     * @param array $variables 置換変数
     * @return string 置換後のコンテンツ
     */
    private function replaceVariables($content, $variables) {
        foreach ($variables as $key => $value) {
            $content = str_replace('{{' . $key . '}}', $value, $content);
        }
        return $content;
    }
}

