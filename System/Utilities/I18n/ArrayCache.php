<?php
namespace System\Utilities\I18n;

use Locale;
use NumberFormatter;
use IntlDateFormatter;

/*
 * Simple cache used internally. Swap for PSR-16 if desired.
 */
class ArrayCache
{
    protected array $store = [];

    public function get(string $key, $default = null)
    {
        return $this->store[$key] ?? $default;
    }

    public function set(string $key, $value): void
    {
        $this->store[$key] = $value;
    }

    public function delete(string $key): void
    {
        unset($this->store[$key]);
    }
}

/*
 * Loader contract - implement custom stores (DB, redis, gettext, etc.)
 */
interface LoaderInterface
{
    /**
     * Load translations for given locale and domain (domain == file name / namespace)
     * Return associative array key => translation (strings or plural arrays)
     *
     * @param string $locale e.g. en, en_US
     * @param string $domain e.g. messages, validation
     * @return array
     */
    public function load(string $locale, string $domain): array;
}

/*
 * FileLoader - reads resources/lang/{locale}/{domain}.php or .json
 * PHP files must return an array.
 */
class FileLoader implements LoaderInterface
{
    protected string $basePath;

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, DIRECTORY_SEPARATOR);
    }

    public function load(string $locale, string $domain): array
    {
        // Accept locale variants like en_US -> try en_US then en
        $candidates = [$locale];
        if (strpos($locale, '_') !== false) {
            $parts = explode('_', $locale);
            $candidates[] = $parts[0];
        }

        $result = [];
        foreach ($candidates as $loc) {
            $phpFile = "{$this->basePath}/{$loc}/{$domain}.php";
            $jsonFile = "{$this->basePath}/{$loc}/{$domain}.json";

            if (is_file($phpFile)) {
                $data = include $phpFile;
                if (is_array($data)) $result = array_replace_recursive($result, $data);
            } elseif (is_file($jsonFile)) {
                $json = json_decode(file_get_contents($jsonFile), true);
                if (is_array($json)) $result = array_replace_recursive($result, $json);
            }
        }

        return $result;
    }
}

/*
 * Basic plural rule management: map locale -> callable($count): int (index)
 * Index is used to select plural form from translation arrays or pipe-separated strings.
 */
class PluralRules
{
    protected static array $rules = [];

    public static function initDefaults(): void
    {
        // english-like: 2 forms (0/1 -> 1? but common: singular when count ==1)
        self::set('default', fn($n) => $n == 1 ? 0 : 1);

        // russian: 3 forms (1 -> form0; 2-4 -> form1; others -> form2)
        self::set('ru', function ($n) {
            $n = (int) abs($n);
            if ($n % 10 == 1 && $n % 100 != 11) return 0;
            if ($n % 10 >= 2 && $n % 10 <= 4 && ($n % 100 < 10 || $n % 100 >= 20)) return 1;
            return 2;
        });

        // french: singular for 0 and 1 -> 0 else 1
        self::set('fr', fn($n) => ($n == 0 || $n == 1) ? 0 : 1);

        // japanese: only one form
        self::set('ja', fn($n) => 0);
    }

    public static function set(string $localeOrBase, callable $cb): void
    {
        self::$rules[$localeOrBase] = $cb;
    }

    public static function ruleFor(string $locale): callable
    {
        // try exact match, then base language (en_US -> en), then default
        if (isset(self::$rules[$locale])) return self::$rules[$locale];
        $base = explode('_', $locale)[0];
        if (isset(self::$rules[$base])) return self::$rules[$base];
        return self::$rules['default'];
    }
}

PluralRules::initDefaults();

/*
 * Translator - main facade
 *
 * - locale: current locale
 * - fallback: fallback locale (e.g. en)
 * - domains: translation files grouping (e.g. messages, validation)
 */
class Translator
{
    protected string $locale;
    protected string $fallback;
    protected LoaderInterface $loader;
    protected ArrayCache $cache;
    protected array $loaded = []; // [$locale][$domain] = array

    public function __construct(LoaderInterface $loader, string $locale = 'en', string $fallback = 'en')
    {
        $this->loader = $loader;
        $this->locale = $locale;
        $this->fallback = $fallback;
        $this->cache = new ArrayCache();
    }

    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setFallback(string $locale): void
    {
        $this->fallback = $locale;
    }

    public function loadDomain(string $domain): void
    {
        $key = "{$this->locale}.{$domain}";
        if ($this->cache->get($key) !== null) {
            $this->loaded[$this->locale][$domain] = $this->cache->get($key);
            return;
        }

        $data = $this->loader->load($this->locale, $domain) ?: [];
        // merge fallback if available
        if ($this->fallback && $this->fallback !== $this->locale) {
            $fallbackData = $this->loader->load($this->fallback, $domain) ?: [];
            $data = array_replace_recursive($fallbackData, $data);
        }

        $this->loaded[$this->locale][$domain] = $data;
        $this->cache->set($key, $data);
    }

    protected function getFromDomain(string $domain, string $key)
    {
        if (!isset($this->loaded[$this->locale][$domain])) {
            $this->loadDomain($domain);
        }
        $segments = explode('.', $key);
        $ref = $this->loaded[$this->locale][$domain] ?? [];
        foreach ($segments as $seg) {
            if (!is_array($ref) || !array_key_exists($seg, $ref)) {
                return null;
            }
            $ref = $ref[$seg];
        }
        return $ref;
    }

    /**
     * Translate a key. Domain splits with '::' or domain param can be provided.
     * e.g. 'messages::welcome' or trans('welcome', [], null, 'messages')
     *
     * Params supports named placeholders: :name or {name}
     *
     * @param string $key
     * @param array $params
     * @param int|null $count  optional count for pluralization
     * @param string|null $domain
     * @return string|null
     */
    public function trans(string $key, array $params = [], ?int $count = null, ?string $domain = null): ?string
    {
        [$domain, $shortKey] = $this->splitDomain($key, $domain);
        $raw = $this->getFromDomain($domain, $shortKey);

        // if not found, attempt fallback locale if different
        if ($raw === null && $this->fallback && $this->fallback !== $this->locale) {
            // temporarily load fallback only for this retrieval
            $backupLocale = $this->locale;
            $this->locale = $this->fallback;
            $this->loadDomain($domain);
            $raw = $this->getFromDomain($domain, $shortKey);
            $this->locale = $backupLocale;
        }

        if ($raw === null) {
            return null;
        }

        // If pluralization requested and raw is array or pipe-delimited string
        if ($count !== null) {
            return $this->choiceInternal($raw, $count, $params);
        }

        // raw can be string; interpolated
        if (is_string($raw)) {
            return $this->interpolate($raw, $params);
        }

        // if array but no count provided, try to return first string or json encode
        if (is_array($raw)) {
            // prefer 'one' / 'other' keys or 0 index
            if (isset($raw['one']) && isset($raw['other'])) {
                return $this->interpolate($raw['other'], $params);
            }
            if (isset($raw[0])) {
                return $this->interpolate((string)$raw[0], $params);
            }
            return $this->interpolate(json_encode($raw), $params);
        }

        return (string) $raw;
    }

    /**
     * Chooses a plural form based on $count.
     *
     * @param string|array $raw
     * @param int $count
     * @param array $params
     * @return string
     */
    public function choice(string|array $raw, int $count, array $params = []): string
    {
        return $this->choiceInternal($raw, $count, $params);
    }

    protected function choiceInternal(string|array $raw, int $count, array $params = []): string
    {
        // Normalize raw into array of forms
        if (is_string($raw)) {
            // allow pipe-delimited: "One item|%count% items"
            $forms = explode('|', $raw);
        } elseif (is_array($raw)) {
            // indexed array or associative
            if (array_keys($raw) === range(0, count($raw) - 1)) {
                $forms = $raw;
            } else {
                // convert associative to numeric ordered [0=>'one',1=>'other'] if present
                $forms = [];
                if (isset($raw['one'])) $forms[] = $raw['one'];
                if (isset($raw['other'])) $forms[] = $raw['other'];
            }
        } else {
            $forms = [(string)$raw];
        }

        $rule = PluralRules::ruleFor($this->locale);
        $index = $rule($count);
        $index = max(0, min($index, count($forms) - 1));
        $text = $forms[$index] ?? end($forms);
        // allow :count and {count} placeholders
        $params = array_merge(['count' => $count], $params);
        return $this->interpolate($text, $params);
    }

    protected function interpolate(string $message, array $params = []): string
    {
        // Replace :name and {name} and %name% tokens
        foreach ($params as $k => $v) {
            $kEsc = preg_quote($k, '/');
            $message = preg_replace_callback("/(?::{$kEsc}|\\{{$kEsc}\\}|%{$kEsc}%)/", function () use ($v) {
                return (string)$v;
            }, $message);
        }
        return $message;
    }

    protected function splitDomain(string $key, ?string $domain = null): array
    {
        // allow domain::key notation
        if (strpos($key, '::') !== false) {
            [$domain, $key] = explode('::', $key, 2);
        } elseif ($domain === null) {
            $domain = 'messages';
        }
        return [$domain, $key];
    }
}

/*
 * Formatter utility using PHP intl when available
 */
class Formatter
{
    public static function formatDate(\DateTimeInterface $dt, string $patternOrStyle = 'medium', string $locale = null): string
    {
        $locale = $locale ?? \Locale::getDefault();
        // Accept 'short','medium','long','full' or explicit pattern
        if (class_exists('\IntlDateFormatter')) {
            $styleMap = [
                'short' => IntlDateFormatter::SHORT,
                'medium' => IntlDateFormatter::MEDIUM,
                'long' => IntlDateFormatter::LONG,
                'full' => IntlDateFormatter::FULL,
            ];
            if (isset($styleMap[$patternOrStyle])) {
                $fmt = new IntlDateFormatter($locale, $styleMap[$patternOrStyle], $styleMap[$patternOrStyle]);
                return $fmt->format($dt);
            } else {
                $fmt = new IntlDateFormatter($locale, IntlDateFormatter::NONE, IntlDateFormatter::NONE, $dt->getTimezone()->getName(), null, $patternOrStyle);
                return $fmt->format($dt);
            }
        }

        // fallback - simple PHP formatting based on style
        $map = ['short' => 'Y-m-d', 'medium' => 'M j, Y', 'long' => 'F j, Y', 'full' => 'l, F j, Y'];
        $fmt = $map[$patternOrStyle] ?? $patternOrStyle;
        return $dt->format($fmt);
    }

    public static function formatNumber(float $number, string $locale = null, int $decimals = 0): string
    {
        $locale = $locale ?? \Locale::getDefault();
        if (class_exists('\NumberFormatter')) {
            $nf = new NumberFormatter($locale, NumberFormatter::DECIMAL);
            $nf->setAttribute(NumberFormatter::FRACTION_DIGITS, $decimals);
            return $nf->format($number);
        }

        // fallback: simple number_format (not locale-aware)
        return number_format($number, $decimals, '.', ',');
    }
}
