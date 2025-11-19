<?php
namespace System\Services\Mail;

use RuntimeException;

class SendGridMailer implements MailerInterface
{
    protected string $apiKey;
    protected string $apiUrl;

    public function __construct(string $apiKey, string $apiUrl = 'https://api.sendgrid.com/v3/mail/send')
    {
        if (empty($apiKey)) {
            throw new RuntimeException('SendGrid API key is required.');
        }
        $this->apiKey = $apiKey;
        $this->apiUrl = $apiUrl;
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
        $payload = [
            'personalizations' => [[
                'to' => [['email' => $toEmail, 'name' => $toName]],
                'subject' => $subject,
            ]],
            'from' => [
                'email' => $headers['From'] ?? ($headers['from_email'] ?? 'no-reply@example.com'),
                'name'  => $headers['From-Name'] ?? ($headers['from_name'] ?? null)
            ],
            'content' => [
                ['type' => 'text/html', 'value' => $bodyHtml]
            ]
        ];
        if ($bodyText) {
            $payload['content'][] = ['type' => 'text/plain', 'value' => $bodyText];
        }

        // attachments: SendGrid expects array of {content(base64), filename, type}
        if (!empty($attachments)) {
            $payload['attachments'] = [];
            foreach ($attachments as $att) {
                if (!empty($att['path']) && is_file($att['path'])) {
                    $content = base64_encode(file_get_contents($att['path']));
                    $filename = $att['filename'] ?? basename($att['path']);
                    $mime = $att['mime'] ?? mime_content_type($att['path']) ?: 'application/octet-stream';
                } elseif (!empty($att['content']) && !empty($att['filename'])) {
                    // assume content is base64 already or raw — try detect
                    $maybeRaw = $att['content'];
                    // if looks like base64 (contains non-binary) we assume base64, else base64-encode raw
                    $isBase64 = base64_decode($maybeRaw, true) !== false;
                    $content = $isBase64 ? $maybeRaw : base64_encode($maybeRaw);
                    $filename = $att['filename'];
                    $mime = $att['mime'] ?? 'application/octet-stream';
                } else {
                    // skip invalid attachment descriptor
                    continue;
                }

                $payload['attachments'][] = [
                    'content' => $content,
                    'filename' => $filename,
                    'type' => $mime,
                ];
            }
        }

        $ch = curl_init($this->apiUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ($code >= 200 && $code < 300);
    }
}
