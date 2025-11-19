<?php
namespace System\Services\Mail;

class SendmailMailer implements MailerInterface
{
    protected string $defaultFromEmail;
    protected ?string $defaultFromName;
    protected string $sendmailPath;

    public function __construct(string $fromEmail = 'no-reply@example.com', ?string $fromName = null, string $sendmailPath = '/usr/sbin/sendmail')
    {
        $this->defaultFromEmail = $fromEmail;
        $this->defaultFromName = $fromName;
        $this->sendmailPath = $sendmailPath;
    }

    /**
     * streaming-friendly send:
     * - If sendmail binary available, use proc_open and stream email parts and attachments (no large-file in-memory).
     * - Else fallback to PHP mail() multipart builder (less memory efficient).
     *
     * Attachments descriptors:
     *  - ['path' => '/full/path/to/file.ext']
     *  - ['filename' => 'name.ext', 'content' => '<base64-or-raw>', 'mime' => '...']
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
        $from = $this->defaultFromEmail;
        if (!empty($headers['From'])) {
            $from = $headers['From'];
            unset($headers['From']);
        }

        $toHeader = $toName ? "{$toName} <{$toEmail}>" : $toEmail;

        // If no attachments, simple HTML route via mail()
        if (empty($attachments)) {
            $defaultHeaders = [
                'MIME-Version' => '1.0',
                'Content-type' => 'text/html; charset=utf-8',
                'From' => $from,
                'X-Mailer' => 'Yantra-Mailer'
            ];
            $all = array_merge($defaultHeaders, $headers);
            $formatted = [];
            foreach ($all as $k => $v) {
                $formatted[] = "{$k}: {$v}";
            }

            $additionalParams = null;
            if (!empty($from) && PHP_OS_FAMILY !== 'Windows') {
                $additionalParams = "-f{$from}";
            }

            return (bool) @mail($toHeader, $subject, $bodyHtml, implode("\r\n", $formatted), $additionalParams);
        }

        // If sendmail binary available and executable, stream using proc_open
        if (is_executable($this->sendmailPath)) {
            return $this->sendUsingSendmail($from, $toHeader, $subject, $bodyHtml, $bodyText, $headers, $attachments);
        }

        // Fallback: build multipart message (previous implementation) — safer for systems without sendmail
        return $this->sendUsingMailFunction($from, $toHeader, $subject, $bodyHtml, $bodyText, $headers, $attachments);
    }

    /**
     * Use proc_open to /usr/sbin/sendmail -t -i and stream the message & attachments.
     * This avoids loading file contents fully into PHP memory.
     */
    protected function sendUsingSendmail(string $from, string $toHeader, string $subject, string $bodyHtml, ?string $bodyText, array $headers, array $attachments): bool
    {
        $descriptorspec = [
            0 => ['pipe', 'r'], // stdin
            1 => ['pipe', 'w'], // stdout
            2 => ['pipe', 'w'], // stderr
        ];

        $cmd = escapeshellcmd($this->sendmailPath) . ' -t -i';
        $proc = @proc_open($cmd, $descriptorspec, $pipes);

        if (!is_resource($proc)) {
            return false;
        }

        // Basic headers
        $outHeaders = [];
        $outHeaders[] = "From: {$from}";
        $outHeaders[] = "To: {$toHeader}";
        $outHeaders[] = "Subject: {$subject}";
        $outHeaders[] = "MIME-Version: 1.0";
        $boundaryMixed = '=_yantra_' . md5(uniqid((string) mt_rand(), true));
        $boundaryAlt = '=_alt_' . md5(uniqid((string) mt_rand(), true));
        $outHeaders[] = "Content-Type: multipart/mixed; boundary=\"{$boundaryMixed}\"";

        // add custom headers
        foreach ($headers as $k => $v) {
            $outHeaders[] = "{$k}: {$v}";
        }

        // write headers
        fwrite($pipes[0], implode("\r\n", $outHeaders) . "\r\n\r\n");

        // start mixed
        fwrite($pipes[0], "This is a multi-part message in MIME format.\r\n");
        fwrite($pipes[0], "--{$boundaryMixed}\r\n");
        // alternative (text + html)
        fwrite($pipes[0], "Content-Type: multipart/alternative; boundary=\"{$boundaryAlt}\"\r\n\r\n");

        // text part
        fwrite($pipes[0], "--{$boundaryAlt}\r\n");
        fwrite($pipes[0], "Content-Type: text/plain; charset=utf-8\r\n");
        fwrite($pipes[0], "Content-Transfer-Encoding: 7bit\r\n\r\n");
        $textPart = $bodyText ?? strip_tags($bodyHtml);
        fwrite($pipes[0], $textPart . "\r\n\r\n");

        // html part
        fwrite($pipes[0], "--{$boundaryAlt}\r\n");
        fwrite($pipes[0], "Content-Type: text/html; charset=utf-8\r\n");
        fwrite($pipes[0], "Content-Transfer-Encoding: 7bit\r\n\r\n");
        fwrite($pipes[0], $bodyHtml . "\r\n\r\n");

        // close alternative
        fwrite($pipes[0], "--{$boundaryAlt}--\r\n\r\n");

        // attachments streaming
        // For base64 correctness, read files in chunks that are multiples of 3 bytes.
        $readChunk = 3 * 1024; // 3072 bytes (multiple of 3)

        foreach ($attachments as $att) {
            $filename = null;
            $mime = 'application/octet-stream';
            $isFilePath = !empty($att['path']) && is_file($att['path']);
            $isInlineContent = !empty($att['filename']) && isset($att['content']);

            if ($isFilePath) {
                $filename = $att['filename'] ?? basename($att['path']);
                $mime = $att['mime'] ?? (function_exists('mime_content_type') ? mime_content_type($att['path']) : $mime);

                fwrite($pipes[0], "--{$boundaryMixed}\r\n");
                fwrite($pipes[0], "Content-Type: {$mime}; name=\"{$filename}\"\r\n");
                fwrite($pipes[0], "Content-Transfer-Encoding: base64\r\n");
                fwrite($pipes[0], "Content-Disposition: attachment; filename=\"{$filename}\"\r\n\r\n");

                $fh = fopen($att['path'], 'rb');
                if ($fh) {
                    while (!feof($fh)) {
                        $chunk = fread($fh, $readChunk);
                        if ($chunk === false || $chunk === '') break;
                        $b64 = base64_encode($chunk);
                        // wrap lines at 76 chars per RFC; chunked base64 here should be concatenated with CRLF after each 76 chars
                        $b64_with_breaks = chunk_split($b64, 76, "\r\n");
                        fwrite($pipes[0], $b64_with_breaks);
                    }
                    fclose($fh);
                }
                fwrite($pipes[0], "\r\n\r\n");
                continue;
            }

            if ($isInlineContent) {
                $filename = $att['filename'];
                $mime = $att['mime'] ?? $mime;
                $content = $att['content'];

                // if content appears to be base64, decode; else treat as raw bytes
                $decoded = base64_decode($content, true);
                if ($decoded === false) {
                    $dataStream = $content; // raw
                    // encode in-memory in chunks
                    fwrite($pipes[0], "--{$boundaryMixed}\r\n");
                    fwrite($pipes[0], "Content-Type: {$mime}; name=\"{$filename}\"\r\n");
                    fwrite($pipes[0], "Content-Transfer-Encoding: base64\r\n");
                    fwrite($pipes[0], "Content-Disposition: attachment; filename=\"{$filename}\"\r\n\r\n");

                    // we have raw string; encode by slicing into multiples-of-3 chunks
                    $len = strlen($dataStream);
                    $pos = 0;
                    while ($pos < $len) {
                        $slice = substr($dataStream, $pos, $readChunk);
                        $pos += strlen($slice);
                        $b64 = base64_encode($slice);
                        $b64_with_breaks = chunk_split($b64, 76, "\r\n");
                        fwrite($pipes[0], $b64_with_breaks);
                    }
                    fwrite($pipes[0], "\r\n\r\n");
                } else {
                    // we have decoded binary data in $decoded
                    fwrite($pipes[0], "--{$boundaryMixed}\r\n");
                    fwrite($pipes[0], "Content-Type: {$mime}; name=\"{$filename}\"\r\n");
                    fwrite($pipes[0], "Content-Transfer-Encoding: base64\r\n");
                    fwrite($pipes[0], "Content-Disposition: attachment; filename=\"{$filename}\"\r\n\r\n");

                    // stream decoded data in multiples of 3
                    $len = strlen($decoded);
                    $pos = 0;
                    while ($pos < $len) {
                        $slice = substr($decoded, $pos, $readChunk);
                        $pos += strlen($slice);
                        $b64 = base64_encode($slice);
                        $b64_with_breaks = chunk_split($b64, 76, "\r\n");
                        fwrite($pipes[0], $b64_with_breaks);
                    }
                    fwrite($pipes[0], "\r\n\r\n");
                }
                continue;
            }

            // skip invalid descriptor
        }

        // final boundary
        fwrite($pipes[0], "--{$boundaryMixed}--\r\n");
        fclose($pipes[0]);

        // collect stderr/out optionally
        $stderr = stream_get_contents($pipes[2]);
        $stdout = stream_get_contents($pipes[1]);

        $status = proc_close($proc);

        if ($status !== 0) {
            error_log("[SendmailMailer] sendmail returned status {$status}. stderr={$stderr} stdout={$stdout}");
            return false;
        }

        return true;
    }

    /**
     * Fallback that builds the entire multipart message in memory and calls PHP mail().
     * Use only when sendmail binary isn't available.
     */
    protected function sendUsingMailFunction(string $from, string $toHeader, string $subject, string $bodyHtml, ?string $bodyText, array $headers, array $attachments): bool
    {
        $boundaryMixed = '=_yantra_' . md5(uniqid((string) mt_rand(), true));
        $boundaryAlt = '=_alt_' . md5(uniqid((string) mt_rand(), true));

        $headerLines = [
            "MIME-Version: 1.0",
            "From: {$from}",
            "X-Mailer: Yantra-Mailer",
            "Content-Type: multipart/mixed; boundary=\"{$boundaryMixed}\""
        ];
        foreach ($headers as $k => $v) $headerLines[] = "{$k}: {$v}";

        $eol = "\r\n";
        $body = [];
        $body[] = "This is a multi-part message in MIME format.";
        $body[] = "--{$boundaryMixed}";
        $body[] = "Content-Type: multipart/alternative; boundary=\"{$boundaryAlt}\"{$eol}";
        $body[] = "--{$boundaryAlt}";
        $body[] = "Content-Type: text/plain; charset=utf-8";
        $body[] = "Content-Transfer-Encoding: 7bit{$eol}";
        $body[] = $bodyText ?? strip_tags($bodyHtml);
        $body[] = "";
        $body[] = "--{$boundaryAlt}";
        $body[] = "Content-Type: text/html; charset=utf-8";
        $body[] = "Content-Transfer-Encoding: 7bit{$eol}";
        $body[] = $bodyHtml;
        $body[] = "";
        $body[] = "--{$boundaryAlt}--";
        $body[] = "";

        foreach ($attachments as $att) {
            $filename = null;
            $mime = 'application/octet-stream';
            $data = null;
            if (!empty($att['path']) && is_file($att['path'])) {
                $filename = $att['filename'] ?? basename($att['path']);
                $mime = $att['mime'] ?? (function_exists('mime_content_type') ? mime_content_type($att['path']) : $mime);
                $data = @file_get_contents($att['path']);
                if ($data === false) continue;
            } elseif (!empty($att['filename']) && isset($att['content'])) {
                $filename = $att['filename'];
                $mime = $att['mime'] ?? $mime;
                $maybe = $att['content'];
                $decoded = base64_decode($maybe, true);
                $data = $decoded === false ? $maybe : $decoded;
            } else {
                continue;
            }

            $base64 = chunk_split(base64_encode($data));
            $dispositionName = addcslashes($filename, "\"\\");
            $body[] = "--{$boundaryMixed}";
            $body[] = "Content-Type: {$mime}; name=\"{$dispositionName}\"";
            $body[] = "Content-Transfer-Encoding: base64";
            $body[] = "Content-Disposition: attachment; filename=\"{$dispositionName}\"{$eol}";
            $body[] = $base64;
            $body[] = "";
        }

        $body[] = "--{$boundaryMixed}--";
        $message = implode($eol, $body);

        $additionalParams = null;
        if (!empty($from) && PHP_OS_FAMILY !== 'Windows') {
            $additionalParams = "-f{$from}";
        }

        $headers = implode($eol, $headerLines);
        return (bool) @mail($toHeader, $subject, $message, $headers, $additionalParams);
    }
}
