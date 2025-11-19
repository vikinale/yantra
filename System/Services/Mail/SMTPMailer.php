<?php
namespace System\Services\Mail;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use RuntimeException;

class SMTPMailer implements MailerInterface
{
    protected array $config;

    public function __construct(array $config = [])
    {
        if (!class_exists(\PHPMailer\PHPMailer\PHPMailer::class)) {
            throw new RuntimeException('PHPMailer not found. Install via composer: "composer require phpmailer/phpmailer"');
        }
        $this->config = $config;
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
        $mail = new PHPMailer(true);
        try {
            $useSmtp = !empty($this->config['host']);
            if ($useSmtp) {
                $mail->isSMTP();
                $mail->Host = $this->config['host'];
                $mail->SMTPAuth = !empty($this->config['auth']);
                $mail->Port = $this->config['port'] ?? 587;
                if (!empty($this->config['username'])) {
                    $mail->Username = $this->config['username'];
                }
                if (!empty($this->config['password'])) {
                    $mail->Password = $this->config['password'];
                }
                if (!empty($this->config['smtp_secure'])) {
                    $mail->SMTPSecure = $this->config['smtp_secure'];
                }
                $mail->SMTPAutoTLS = $this->config['smtp_autotls'] ?? true;
            }

            $from = $this->config['from_email'] ?? ($headers['From'] ?? null);
            $fromName = $this->config['from_name'] ?? ($headers['From-Name'] ?? null);
            if ($from) {
                $mail->setFrom($from, $fromName);
            }

            $mail->addAddress($toEmail, $toName ?? '');
            $mail->Subject = $subject;
            $mail->isHTML(true);
            $mail->Body = $bodyHtml;
            if ($bodyText) {
                $mail->AltBody = $bodyText;
            }

            // add headers
            foreach ($headers as $k => $v) {
                if (strtolower($k) === 'from') continue;
                $mail->addCustomHeader($k, (string)$v);
            }

            // attachments
            foreach ($attachments as $att) {
                // attach by path
                if (!empty($att['path'])) {
                    $mail->addAttachment($att['path'], $att['filename'] ?? null);
                    continue;
                }
                // attach by base64 content
                if (!empty($att['content']) && !empty($att['filename'])) {
                    $data = base64_decode($att['content']);
                    if ($data === false) {
                        // skip invalid base64
                        continue;
                    }
                    $mail->addStringAttachment($data, $att['filename'], 'base64', $att['mime'] ?? '');
                }
            }

            return (bool) $mail->send();
        } catch (PHPMailerException $e) {
            // optionally log $e->getMessage()
            return false;
        }
    }
}