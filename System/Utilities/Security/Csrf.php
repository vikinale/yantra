<?php 
namespace System\Utilities\Security;

use DateTimeImmutable;
use Exception;


/* -----------------------------
 * CSRF helper - session-backed
 * ----------------------------- */
class Csrf
{
    protected $sessionGet;
    protected $sessionSet;
    protected string $key;

    /**
     * $sessionGet, $sessionSet can be closures or callables that interact with your SessionStore.
     * e.g. fn($k, $d=null)=>SessionStore::get($k,$d) and fn($k,$v)=>SessionStore::set($k,$v)
     */
    public function __construct(callable $sessionGet, callable $sessionSet, string $key = '_csrf_token')
    {
        $this->sessionGet = $sessionGet;
        $this->sessionSet = $sessionSet;
        $this->key = $key;
    }

    public function token(): string
    {
        $get = $this->sessionGet;
        $t = $get($this->key);
        if (!is_string($t) || $t === '') {
            $t = Crypto::randomBase64Url(32);
            $set = $this->sessionSet;
            $set($this->key, $t);
        }
        return $t;
    }

    public function validate(string $incoming): bool
    {
        $get = $this->sessionGet;
        $t = $get($this->key);
        if (!is_string($t) || $t === '') return false;
        return Crypto::hashEquals($t, $incoming);
    }

    /**
     * Use in forms: <input type="hidden" name="_csrf" value="<?= $csrf->token() ?>">
     */
}


/**
 * CspBuilder - build Content-Security-Policy header
 */
class CspBuilder
{
    protected array $directives = [];

    public function set(string $directive, string $value): self
    {
        $this->directives[$directive] = $value;
        return $this;
    }

    public function add(string $directive, string $value): self
    {
        if (!isset($this->directives[$directive])) $this->directives[$directive] = $value;
        else $this->directives[$directive] .= ' ' . $value;
        return $this;
    }

    public function buildHeader(): string
    {
        $parts = [];
        foreach ($this->directives as $k => $v) {
            $parts[] = "{$k} {$v}";
        }
        return implode('; ', $parts);
    }

    public function sendHeader(bool $reportOnly = false): void
    {
        $hdr = $this->buildHeader();
        header(($reportOnly ? 'Content-Security-Policy-Report-Only: ' : 'Content-Security-Policy: ') . $hdr);
    }
}

/**
 * Cors helper - minimal
 */
class Cors
{
    public static function allowOrigins(array $origins = [], array $methods = ['GET', 'POST', 'OPTIONS'], array $headers = ['Content-Type', 'Authorization']): void
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        if (in_array('*', $origins) || in_array($origin, $origins)) {
            header("Access-Control-Allow-Origin: " . ($origin ?: '*'));
            header('Access-Control-Allow-Methods: ' . implode(', ', $methods));
            header('Access-Control-Allow-Headers: ' . implode(', ', $headers));
            header('Access-Control-Allow-Credentials: true');
        }
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }
}
