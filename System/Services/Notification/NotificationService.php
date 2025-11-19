<?php
namespace System\Services\Notification;

use System\Services\Mail\MailerInterface;
use System\Database\Database;
use Psr\Log\LoggerInterface;

class NotificationService
{
    protected MailerInterface $mailer;
    protected Database $db;
    protected ?LoggerInterface $logger;

    public function __construct(
        MailerInterface $mailer,
        ?LoggerInterface $logger = null,
        ?Database $db = null
    ) {
        $this->mailer = $mailer;
        $this->logger = $logger;
        $this->db = $db ?? Database::getInstance();
    }

    public function notify(
        int $userId,
        string $type,
        string $title,
        string $body,
        array $meta = [],
        array $options = []
    ): int {
        $pdo = $this->db->getPDO();

        $stmt = $pdo->prepare("
            INSERT INTO yt_notifications (user_id, type, title, body, meta, created_at, updated_at)
            VALUES (:uid, :type, :title, :body, :meta, NOW(), NOW())
        ");
        $stmt->execute([
            ':uid' => $userId,
            ':type' => $type,
            ':title' => $title,
            ':body' => $body,
            ':meta' => $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null
        ]);

        $id = (int)$pdo->lastInsertId();

        if (!empty($options['email'])) {
            $this->sendOrQueueNotificationEmail($userId, $options['email'], $id);
        }

        return $id;
    }

    protected function sendOrQueueNotificationEmail(int $userId, array $emailOpt, int $notificationId)
    {
        $pdo = $this->db->getPDO();
        $email = $this->resolveEmailOfUser($userId);

        if (!$email) {
            return;
        }

        $subject = $emailOpt['subject'] ?? ("Notification #{$notificationId}");
        $bodyHtml = $emailOpt['body_html'] ?? '';
        $bodyText = $emailOpt['body_text'] ?? null;

        if (!empty($emailOpt['queue'])) {
            $pdo->prepare("
                INSERT INTO yt_mail_queue 
                    (to_email, subject, body_html, body_text, queue, status, created_at, updated_at)
                VALUES 
                    (:email, :subject, :html, :text, 'default', 'pending', NOW(), NOW())
            ")->execute([
                ':email' => $email,
                ':subject' => $subject,
                ':html' => $bodyHtml,
                ':text' => $bodyText,
            ]);
        } else {
            $this->mailer->send($email, null, $subject, $bodyHtml, $bodyText);
        }
    }

    protected function resolveEmailOfUser(int $userId): ?string
    {
        $stmt = $this->db->getPDO()->prepare("
            SELECT email FROM yt_users WHERE user_id = :uid LIMIT 1
        ");
        $stmt->execute([':uid' => $userId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $row['email'] ?? null;
    }
}
