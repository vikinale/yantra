<?php
namespace System\Jobs;

use System\Database\Database;
use Psr\Log\LoggerInterface;

class UnblockExpiredJob
{
    protected $db;
    protected ?LoggerInterface $logger;

    public function __construct($db = null, ?LoggerInterface $logger = null)
    {
        $this->db = $db ?? Database::getInstance();
        $this->logger = $logger;
    }

    public function handle(): void
    {
        $pdo = $this->db->getPDO();
        $stmt = $pdo->prepare("DELETE FROM yt_abuse_blocks WHERE expires_at IS NOT NULL AND expires_at <= NOW()");
        $stmt->execute();
        $count = $stmt->rowCount();
        if ($this->logger) $this->logger->info("UnblockExpiredJob removed {$count} expired blocks");
    }
}
