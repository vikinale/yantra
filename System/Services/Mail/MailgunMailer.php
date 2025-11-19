<?php
namespace System\Services\Mail;

use RuntimeException;

class MailgunMailer implements MailerInterface
{
    protected string $apiKey;
    protected string $domain;
    protected string $apiBase; // e.g., https://api.mailgun.net/v3/YOUR_DOMAIN/messages

    public function __construct(string $apiKey, string $domain, ?string $apiBase = null)
    {
        if (empty($apiKey) || empty($domain)) {
            throw new RuntimeException('Mailgun apiKey and domain are required.');
        }
        $this->apiKey = $apiKey;
        $this->domain = $domain;
        $this->apiBase = $apiBase ?? "https://api.mailgun.net/v3/{$domain}/messages";
    }

    /**
     * @param array $headers
     * @param array $attachments
     *   Each attachment may be:
     *     - ['path' => '/full/path/to/file.ext'] OR
     *     - ['filename' => 'name.ext', 'content' => base64-or-raw-string, 'mime' => 'mime/type']
     */
    public function send(
        string $toEmail,
        ?string $toName,
        string $subject,
        string $bodyHtml,
        ?string $bodyText = null,
        array $headers = [],
        array $attachments = []
    ): bool {
        $from = $headers['From'] ?? ($headers['from_email'] ?? 'no-reply@' . $this->domain);

        $post = [
            'from' => $from,
            'to' => $toEmail,
            'subject' => $subject,
            'html' => $bodyHtml,
        ];
        if ($bodyText) {
            $post['text'] = $bodyText;
        }

        // Allow setting custom headers for Mailgun via h:Name format in POST fields
        foreach ($headers as $k => $v) {
            // skip From/from_email handled above
            if (in_array(strtolower($k), ['from', 'from_email'])) {
                continue;
            }
            // Mailgun expects custom headers as 'h:Header-Name'
            $post['h:' . $k] = $v;
        }

        $tmpFiles = [];
        $multipart = $post;

        // Attachments: support filesystem or in-memory/base64 content
        $i = 0;
        foreach ($attachments as $att) {
            // filesystem path
            if (!empty($att['path']) && is_file($att['path'])) {
                $mime = $att['mime'] ?? (function_exists('mime_content_type') ? mime_content_type($att['path']) : 'application/octet-stream');
                $name = $att['filename'] ?? basename($att['path']);
                $multipart["attachment[{$i}]"] = curl_file_create($att['path'], $mime, $name);
                $i++;
                continue;
            }

            // in-memory content (base64 or raw)
            if (!empty($att['filename']) && isset($att['content'])) {
                $maybe = $att['content'];
                // if it's base64 already, decode; otherwise treat as raw binary and write as-is
                $decoded = base64_decode($maybe, true);
                if ($decoded === false) {
                    $data = $maybe; // raw
                } else {
                    $data = $decoded;
                }

                // write to tmp file
                $tmp = tempnam(sys_get_temp_dir(), 'mgatt_');
                if ($tmp === false) {
                    // cannot create tmp file; skip this attachment
                    continue;
                }
                file_put_contents($tmp, $data);
                $tmpFiles[] = $tmp;

                $mime = $att['mime'] ?? 'application/octet-stream';
                $multipart["attachment[{$i}]"] = curl_file_create($tmp, $mime, $att['filename']);
                $i++;
                continue;
            }

            // otherwise skip invalid descriptor
        }

        $ch = curl_init($this->apiBase);
        curl_setopt($ch, CURLOPT_USERPWD, 'api:' . $this->apiKey);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        // set multipart/form-data post fields (curl handles curl_file_create items)
        curl_setopt($ch, CURLOPT_POSTFIELDS, $multipart);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        // cleanup temp files if any
        foreach ($tmpFiles as $f) {
            if (is_file($f)) {
                @unlink($f);
            }
        }

        // success: 2xx
        if ($code >= 200 && $code < 300) {
            return true;
        }

        // log failure details for debugging (non-fatal)
        $msg = sprintf(
            '[MailgunMailer] send failed. HTTP=%s, curl_err=%s, resp=%s',
            $code,
            $curlErr ?: 'none',
            is_string($resp) ? $resp : json_encode($resp)
        );
        error_log($msg);

        return false;
    }
}
