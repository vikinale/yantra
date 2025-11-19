<?php
namespace System\Services\Mail;

use RuntimeException;

class MailManager implements MailerInterface
{
    protected MailerInterface $driver;

    public function __construct(array $config = [])
    {
        $driver = $config['driver'] ?? 'sendmail';

        switch ($driver) {
            case 'smtp':
                $this->driver = new SMTPMailer($config['smtp'] ?? []);
                break;
            case 'sendgrid':
                $sg = $config['sendgrid'] ?? [];
                $this->driver = new SendGridMailer($sg['api_key'] ?? '', $sg['api_url'] ?? null);
                break;
            case 'mailgun':
                $mg = $config['mailgun'] ?? [];
                $this->driver = new MailgunMailer($mg['api_key'] ?? '', $mg['domain'] ?? '');
                break;
            case 'log':
                if (empty($config['log_logger'])) {
                    throw new RuntimeException('log driver requires a PSR logger in config[log_logger]');
                }
                $this->driver = new LogMailer($config['log_logger']);
                break;
            case 'sendmail':
            default:
                $this->driver = new SendmailMailer($config['from_email'] ?? 'no-reply@example.com', $config['from_name'] ?? null);
                break;
        }
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
        return $this->driver->send($toEmail, $toName, $subject, $bodyHtml, $bodyText, $headers, $attachments);
    }
}