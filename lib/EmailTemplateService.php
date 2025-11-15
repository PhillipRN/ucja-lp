<?php
/**
 * EmailTemplateService
 *
 * - Fetches email templates from Supabase
 * - Renders variables for local templates
 * - Sends via SendGrid through EmailService
 * - Persists email_logs records with status updates
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/SupabaseClient.php';
require_once __DIR__ . '/EmailService.php';
require_once __DIR__ . '/EmailRecipientResolver.php';

class EmailTemplateService {
    private $supabase;
    private $emailService;
    private $templateCache = [];
    private $sandboxMode;
    private $sandboxRecipient;
    private $autoGenerateHtml;
    private $recipientResolver;

    public function __construct(SupabaseClient $supabase) {
        $this->supabase = $supabase;
        $this->emailService = new EmailService();
        $this->sandboxMode = defined('EMAIL_SANDBOX_MODE') ? EMAIL_SANDBOX_MODE : (APP_ENV !== 'production');
        $this->sandboxRecipient = defined('EMAIL_SANDBOX_RECIPIENT') ? EMAIL_SANDBOX_RECIPIENT : null;
        $this->autoGenerateHtml = defined('EMAIL_AUTO_GENERATE_HTML_FROM_TEXT') ? EMAIL_AUTO_GENERATE_HTML_FROM_TEXT : false;
        $this->recipientResolver = new EmailRecipientResolver($supabase);
    }

    /**
     * Send email based on template_type.
     *
     * @param string $templateType
     * @param array $recipient ['email' => string, 'name' => string]
     * @param array $variables key/value for template placeholders / SendGrid dynamic data
     * @param array $options ['application_id' => uuid, 'batch_id' => uuid, 'metadata' => array]
     */
    public function sendTemplate(string $templateType, array $recipient, array $variables = [], array $options = []) {
        $template = $this->getTemplate($templateType);
        if (!$template) {
            throw new Exception('メールテンプレートが見つかりません: ' . $templateType);
        }

        return $this->dispatchTemplate($template, $recipient, $variables, $options);
    }

    /**
     * Resolve recipients based on application info and template settings,
     * then send the template to all resolved recipients.
     */
    public function sendTemplateToApplication(string $templateType, string $applicationId, array $variables = [], array $options = []) {
        $template = $this->getTemplate($templateType);
        if (!$template) {
            throw new Exception('メールテンプレートが見つかりません: ' . $templateType);
        }

        $recipientType = $template['recipient_type'] ?? 'guardian';
        $recipientOptions = $options['recipient_options'] ?? [];
        $recipients = $this->recipientResolver->resolveRecipients($applicationId, $recipientType, $recipientOptions);

        if (empty($recipients)) {
            throw new Exception('メール送信先が見つかりません: ' . $recipientType);
        }

        $results = [];
        foreach ($recipients as $recipient) {
            $results[] = $this->dispatchTemplate(
                $template,
                $recipient,
                $variables,
                array_merge($options, ['application_id' => $applicationId])
            );
        }

        return $results;
    }

    private function dispatchTemplate(array $template, array $recipient, array $variables, array $options = []) {
        $recipientEmail = $recipient['email'] ?? null;
        if (!$recipientEmail) {
            throw new Exception('メール送信先のメールアドレスが指定されていません');
        }

        $recipientName = $recipient['name'] ?? null;
        $sendToEmail = $recipientEmail;
        $variables = $this->appendGlobalVariables($variables);

        if ($this->sandboxMode && !empty($this->sandboxRecipient)) {
            $variables['original_recipient_email'] = $recipientEmail;
            $sendToEmail = $this->sandboxRecipient;
        }

        $renderedSubject = $template['subject'] ?? $template['template_name'] ?? 'Notification';
        $renderedHtml = $template['body_html'] ?? '';
        $renderedText = $template['body_text'] ?? '';

        if (!$template['use_sendgrid_template']) {
            $renderedSubject = $this->renderContent($renderedSubject, $variables);
            $renderedHtml = $this->renderContent($renderedHtml, $variables);
            $renderedText = $this->renderContent($renderedText, $variables);

            if ($this->autoGenerateHtml || empty(trim(strip_tags($renderedHtml ?? '')))) {
                $renderedHtml = $this->convertTextToHtml($renderedText, $renderedSubject);
            }
        }

        $logId = $this->createLog([
            'application_id' => $options['application_id'] ?? null,
            'batch_id' => $options['batch_id'] ?? null,
            'email_type' => $template['template_type'] ?? null,
            'template_id' => $template['id'],
            'recipient_email' => $recipientEmail,
            'recipient_name' => $recipientName,
            'subject' => $renderedSubject,
            'body_text' => $renderedText,
            'body_html' => $renderedHtml,
            'status' => 'pending'
        ]);

        try {
            if ($template['use_sendgrid_template']) {
                if (empty($template['sendgrid_template_id'])) {
                    throw new Exception('SendGridテンプレートIDが設定されていません');
                }
                $sendResult = $this->emailService->sendTemplateEmail($sendToEmail, $template['sendgrid_template_id'], $variables);
            } else {
                $sendResult = $this->emailService->sendEmail($sendToEmail, $renderedSubject, $renderedHtml, $renderedText);
            }

            $this->finalizeLog($logId, $sendResult);
            if (!$sendResult['success']) {
                throw new Exception($sendResult['error'] ?? 'メール送信に失敗しました');
            }

            return $sendResult;
        } catch (Exception $e) {
            if ($logId) {
                $this->updateLog($logId, [
                    'status' => 'failed',
                    'error_message' => $e->getMessage()
                ]);
            }
            throw $e;
        }
    }

    private function getTemplate(string $templateType) {
        if (isset($this->templateCache[$templateType])) {
            return $this->templateCache[$templateType];
        }

        $result = $this->supabase->from('email_templates')
            ->select('id, template_type, template_name, subject, body_text, body_html, sendgrid_template_id, use_sendgrid_template, is_active, recipient_type')
            ->eq('template_type', $templateType)
            ->eq('is_active', true)
            ->single();

        if (!$result['success'] || empty($result['data'])) {
            return null;
        }

        $template = $result['data'];
        $template['use_sendgrid_template'] = (bool)($template['use_sendgrid_template'] ?? false);
        $this->templateCache[$templateType] = $template;
        return $template;
    }

    private function renderContent($content, array $variables) {
        if (!$content) {
            return $content;
        }

        foreach ($variables as $key => $value) {
            if (is_array($value)) {
                continue;
            }
            $content = str_replace('{{' . $key . '}}', $value, $content);
        }
        return $content;
    }

    private function appendGlobalVariables(array $variables) {
        if (!isset($variables['website_url'])) {
            $variables['website_url'] = APP_URL ?? '';
        }
        if (!isset($variables['mypage_url'])) {
            $variables['mypage_url'] = rtrim(APP_URL, '/') . '/my-page/dashboard.php';
        }
        return $variables;
    }

    private function convertTextToHtml(?string $text, ?string $subject = null) {
        $safeSubject = htmlspecialchars($subject ?? '', ENT_QUOTES, 'UTF-8');
        $safeText = htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');

        // 空行で段落分割
        $paragraphs = preg_split("/\n\s*\n/", $safeText);
        $htmlParagraphs = array_map(function ($paragraph) {
            $paragraph = trim($paragraph);
            if ($paragraph === '') {
                return '';
            }
            return '<p style="margin: 0 0 1em 0; line-height: 1.6;">' . nl2br($paragraph) . '</p>';
        }, $paragraphs ?: []);

        $bodyContent = implode("\n", $htmlParagraphs);

        return '<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>' . $safeSubject . '</title>
</head>
<body style="font-family: system-ui, -apple-system, BlinkMacSystemFont, sans-serif; color: #111827; background-color: #f8fafc; padding: 24px;">
    <div style="max-width: 640px; margin: 0 auto; background: #ffffff; border-radius: 12px; padding: 32px; box-shadow: 0 10px 25px rgba(15, 23, 42, 0.08);">
        <h1 style="font-size: 20px; font-weight: 600; color: #1d4ed8; margin-bottom: 24px; line-height: 1.4;">' . $safeSubject . '</h1>
        <div style="font-size: 15px; color: #111827;">' . $bodyContent . '</div>
        <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 32px 0;">
        <p style="font-size: 13px; color: #64748b; margin: 0;">本メールはCambridge Exam Application Systemより自動送信されています。</p>
    </div>
</body>
</html>';
    }

    private function createLog(array $data) {
        try {
            $result = $this->supabase->insert('email_logs', $data);
            if ($result['success'] && !empty($result['data'])) {
                return $result['data'][0]['id'] ?? null;
            }
        } catch (Exception $e) {
            error_log('[EmailTemplateService] email_logs insert failed: ' . $e->getMessage());
        }
        return null;
    }

    private function updateLog($logId, array $data) {
        if (!$logId) {
            return;
        }
        try {
            $conditions = ['id' => 'eq.' . $logId];
            $this->supabase->update('email_logs', $data, $conditions);
        } catch (Exception $e) {
            error_log('[EmailTemplateService] email_logs update failed: ' . $e->getMessage());
        }
    }

    private function finalizeLog($logId, array $sendResult) {
        $status = $sendResult['success'] ? 'sent' : 'failed';
        $messageId = $this->extractMessageId($sendResult['headers'] ?? []);

        $updateData = [
            'status' => $status,
            'sent_at' => date('c'),
            'sendgrid_message_id' => $messageId,
        ];

        if (!$sendResult['success'] && isset($sendResult['error'])) {
            $updateData['error_message'] = $sendResult['error'];
        }

        if (isset($sendResult['body'])) {
            $body = $sendResult['body'];
            $updateData['sendgrid_response'] = is_string($body) ? $body : json_encode($body, JSON_UNESCAPED_UNICODE);
        }

        $this->updateLog($logId, $updateData);
    }

    private function extractMessageId($headers) {
        if (!$headers || !is_array($headers)) {
            return null;
        }
        foreach ($headers as $header) {
            if (stripos($header, 'x-message-id:') === 0) {
                return trim(substr($header, strlen('x-message-id:')));
            }
        }
        return null;
    }
}
