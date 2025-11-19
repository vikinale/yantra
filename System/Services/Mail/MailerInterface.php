<?php
namespace System\Services\Mail;

interface MailerInterface
{
    /**
     * Send an email.
     *
     * @param string $toEmail
     * @param string|null $toName
     * @param string $subject
     * @param string $bodyHtml
     * @param string|null $bodyText
     * @param array $headers
     * @param array $attachments Array of attachments (see docs). Each attachment may be:
     *    - ['path' => '/full/path/to/file.ext'] OR
     *    - ['filename' => 'name.ext', 'content' => base64-string, 'mime' => 'mime/type']
     * @return bool
     */
    public function send(
        string $toEmail,
        ?string $toName,
        string $subject,
        string $bodyHtml,
        ?string $bodyText = null,
        array $headers = [],
        array $attachments = []
    ): bool;
}