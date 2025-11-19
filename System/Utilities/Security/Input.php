<?php 
namespace System\Utilities\Security;

use DateTimeImmutable;
use Exception;

/* -----------------------------
 * Input - escaping & sanitizers
 * ----------------------------- */
class Input
{
    public static function escapeHtml(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function escapeAttribute(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function stripTagsAllow(string $s, string $allowed = '<a><b><i><strong><em><p><ul><ol><li><br>'): string
    {
        return strip_tags($s, $allowed);
    }

    /**
     * Basic input sanitizer for file names, slugs, etc.
     */
    public static function sanitizeSlug(string $s): string
    {
        $s = mb_strtolower($s, 'UTF-8');
        $s = preg_replace('/[^\p{L}\p{N}\-]+/u', '-', $s);
        $s = preg_replace('/-+/', '-', $s);
        return trim($s, '-');
    }
}
