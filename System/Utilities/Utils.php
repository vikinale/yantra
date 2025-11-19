<?php
namespace System\Utilities;

use PDO;
use InvalidArgumentException;
use DateTimeImmutable;
use Exception;

/**
 * Small helpers
 */
class Utils
{
    public static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public static function base64UrlDecode(string $b64): string
    {
        $pad = 4 - (strlen($b64) % 4);
        if ($pad < 4) $b64 .= str_repeat('=', $pad);
        return base64_decode(strtr($b64, '-_', '+/'));
    }

    public static function clampLimit(int $limit, int $max = 100): int
    {
        $limit = max(1, $limit);
        return min($limit, $max);
    }
}