<?php
namespace System\Jobs;

use System\Database\Database;
use Throwable;
use RuntimeException;
use System\Services\Mail\MailerInterface;

class MailQueueJob
{
    protected MailerInterface $mailer;
    protected Database $db;

    public function __construct(
        MailerInterface $mailer,
        ?Database $db = null
    ) {
        $this->mailer = $mailer;
        $this->db = $db ?? Database::getInstance();
    }

    /**
     * $payload can include:
     *   - 'mail_id' => process a specific mail row
     */
    public function handle(array $payload = []): void
    {
        $pdo = $this->db->getPDO();
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        try {
            $pdo->beginTransaction();

            if (!empty($payload['mail_id'])) {
                $stmt = $pdo->prepare("SELECT * FROM yt_mail_queue WHERE mail_id = :id FOR UPDATE");
                $stmt->execute([':id' => (int)$payload['mail_id']]);
            } else {
                $stmt = $pdo->prepare("
                    SELECT * FROM yt_mail_queue
                    WHERE status = 'pending'
                    ORDER BY created_at ASC
                    LIMIT 1
                    FOR UPDATE
                ");
                $stmt->execute();
            }

            $mail = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$mail) {
                $pdo->commit();
                return; // nothing to do
            }

            // mark sending
            $upd = $pdo->prepare("UPDATE yt_mail_queue SET status = 'sending', updated_at = NOW() WHERE mail_id = :id");
            $upd->execute([':id' => $mail['mail_id']]);

            $pdo->commit();

            // decode headers and attachments stored as JSON (if any)
            $headers = [];
            if (!empty($mail['headers'])) {
                $decoded = json_decode($mail['headers'], true);
                if (is_array($decoded)) $headers = $decoded;
            }

            $attachments = [];
            if (!empty($mail['attachments'])) {
                $decoded = json_decode($mail['attachments'], true);
                if (is_array($decoded)) $attachments = $decoded;
            }

            // send
            $success = $this->mailer->send(
                $mail['to_email'],
                $mail['to_name'] ?? null,
                $mail['subject'],
                $mail['body_html'] ?? '',
                $mail['body_text'] ?? null,
                $headers,
                $attachments
            );

            // update post-send
            $pdo->beginTransaction();
            if ($success) {
                $stmtOk = $pdo->prepare("
                    UPDATE yt_mail_queue
                    SET status = 'sent',
                        attempts = attempts + 1,
                        last_error = NULL,
                        updated_at = NOW()
                    WHERE mail_id = :id
                ");
                $stmtOk->execute([':id' => $mail['mail_id']]);
            } else {
                $attempts = (int)($mail['attempts'] ?? 0) + 1;
                $maxAttempts = (int)($mail['max_attempts'] ?? 5);
                $newStatus = $attempts >= $maxAttempts ? 'failed' : 'pending';

                $stmtFail = $pdo->prepare("
                    UPDATE yt_mail_queue
                    SET status = :status,
                        attempts = :attempts,
                        last_error = :err,
                        updated_at = NOW()
                    WHERE mail_id = :id
                ");
                $stmtFail->execute([
                    ':status' => $newStatus,
                    ':attempts' => $attempts,
                    ':err' => 'send_failed',
                    ':id' => $mail['mail_id']
                ]);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            try { if ($pdo->inTransaction()) $pdo->rollBack(); } catch (Throwable $_) {}
            // attempt to record last_error and increment attempts safely (best-effort)
            try {
                $pdo->beginTransaction();
                if (!empty($mail['mail_id'])) {
                    $attempts = (int)($mail['attempts'] ?? 0) + 1;
                    $maxAttempts = (int)($mail['max_attempts'] ?? 5);
                    $status = $attempts >= $maxAttempts ? 'failed' : 'pending';
                    $stmt = $pdo->prepare("
                        UPDATE yt_mail_queue
                        SET status = :status, attempts = :attempts, last_error = :err, updated_at = NOW()
                        WHERE mail_id = :id
                    ");
                    $stmt->execute([
                        ':status' => $status,
                        ':attempts' => $attempts,
                        ':err' => substr($e->getMessage(), 0, 1000),
                        ':id' => $mail['mail_id'] ?? 0
                    ]);
                }
                $pdo->commit();
            } catch (Throwable $_) {
                try { if ($pdo->inTransaction()) $pdo->rollBack(); } catch (Throwable $__){}
            }

            error_log("[MailQueueJob] Exception: " . $e->getMessage());
            throw $e;
        }
    }
}