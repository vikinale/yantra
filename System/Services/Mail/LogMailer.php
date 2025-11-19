<?php
namespace System\Services\Mail;

use Psr\Log\LoggerInterface;

class LogMailer implements MailerInterface
{
    protected LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function send(
        string $toEmail,
        ?string $toName,
        string $subject,
        string $bodyHtml,
        ?string $bodyText = null,
        array $headers = [],
        array $attachments = []
    ): bool {
        $meta = [
            'to_name' => $toName,
            'body_html' => $bodyHtml,
            'body_text' => $bodyText,
            'headers' => $headers,
            'attachments_count' => count($attachments),
            'attachments' => []
        ];

        foreach ($attachments as $att) {
            if (!empty($att['path'])) {
                $meta['attachments'][] = [
                    'type' => 'path',
                    'path' => $att['path'],
                    'filename' => $att['filename'] ?? basename($att['path'] ?? '')
                ];
            } else {
                $meta['attachments'][] = [
                    'type' => 'inline',
                    'filename' => $att['filename'] ?? null,
                    'mime' => $att['mime'] ?? null,
                    'content_preview' => isset($att['content']) ? substr($att['content'], 0, 200) : null
                ];
            }
        }

        $this->logger->info("[MAIL] {$subject} → {$toEmail}", $meta);

        return true;
    }
}