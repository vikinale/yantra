<?php

declare(strict_types=1);

namespace System\Core;

/**
 * WebPage
 *
 * Lightweight container for a file-based web page in the Yantra framework.
 *
 * Responsibilities:
 *  - Hold page properties (private)
 *  - Provide safe getters/setters
 *  - Provide rendering helpers that return strings by default (echo optional)
 *  - Header management and conditional caching helpers (ETag / Last-Modified)
 *
 * This class intentionally does NOT control request lifecycle (no exit/kill).
 */
class WebPage
{
    /* --------------------
     * Core properties
     * -------------------- */

    private string $title = '';
    private string $slug = '';
    private string $route = '';
    private string $content = '';
    private ?string $summary = null;
    private ?string $layout = 'index';
    private ?string $type = 'page';
    private ?string $featuredImage = null;
    private bool $isPublic = true;
    private bool $noCache = false;
    private ?string $lang = 'en';
    private array $meta = [];
    private array $headers = [];
    private ?string $lastModified = null;

    /**
     * Links: array of link definitions
     *
     * Each link:
     *  [
     *    'rel' => 'stylesheet'|'preconnect'|'canonical'|...,
     *    'href' => '/assets/css/app.css',
     *    'attrs' => [ 'media' => 'all', 'integrity' => '...', ... ],
     *    'position' => 'head'|'footer',
     *    'priority' => int
     *  ]
     *
     * @var array<int,array>
     */
    private array $links = [];

    /**
     * Scripts: array of script definitions
     *
     * Each script:
     *  [
     *    'src' => '/assets/js/app.js', // optional for inline
     *    'inline' => false,            // true for inline script
     *    'content' => 'console.log(1);', // for inline scripts
     *    'attrs' => [ 'defer' => 'defer', 'type' => 'text/javascript' ],
     *    'position' => 'head'|'footer',
     *    'priority' => int
     *  ]
     *
     * @var array<int,array>
     */
    private array $scripts = [];

    /** @var ?string CSP nonce (generated lazily) */
    private ?string $cspNonce = null;

    /** @var bool Toggle sending X-Powered-By header (configurable) */
    private bool $sendPoweredBy = false;

    /* --------------------
     * Construction / hydration
     * -------------------- */

    /**
     * Construct. Optional assoc array to hydrate known properties.
     *
     * Accepts keys matching property names. Extra keys are ignored.
     *
     * @param array<string,mixed> $values
     */
    public function __construct(array $values = [])
    {
        $allowed = [
            'title','slug','route','content','summary','layout','type','featuredImage',
            'isPublic','noCache','lang','meta','headers','lastModified','links','scripts'
        ];

        foreach ($values as $k => $v) {
            if (!in_array($k, $allowed, true)) {
                continue;
            }
            // prefer setters for validation where available
            switch ($k) {
                case 'title':
                    $this->setTitle((string)$v);
                    break;
                case 'slug':
                    $this->setSlug((string)$v);
                    break;
                case 'route':
                    $this->setRoute((string)$v);
                    break;
                case 'content':
                    $this->setContent((string)$v);
                    break;
                case 'summary':
                    $this->setSummary($v === null ? null : (string)$v);
                    break;
                case 'layout':
                    $this->setLayout($v === null ? null : (string)$v);
                    break;
                case 'type':
                    $this->setType($v === null ? null : (string)$v);
                    break;
                case 'featuredImage':
                    $this->setFeaturedImage($v === null ? null : (string)$v);
                    break;
                case 'isPublic':
                    $this->setPublic((bool)$v);
                    break;
                case 'noCache':
                    $this->setNoCache((bool)$v);
                    break;
                case 'lang':
                    $this->setLang($v === null ? null : (string)$v);
                    break;
                case 'meta':
                    $this->meta = is_array($v) ? $v : $this->meta;
                    break;
                case 'headers':
                    $this->headers = is_array($v) ? $v : $this->headers;
                    break;
                case 'lastModified':
                    $this->setLastModified($v === null ? null : (string)$v);
                    break;
                case 'links':
                    $this->setLinks(is_array($v) ? $v : []);
                    break;
                case 'scripts':
                    $this->setScripts(is_array($v) ? $v : []);
                    break;
            }
        }
    }

    /**
     * Strict page property setter (only known properties allowed).
     *
     * @param string $key
     * @param mixed $value
     */
    public function setPageProperty(string $key, mixed $value): void
    {
        $allowed = [
            'title','slug','route','content','summary','layout','type','featuredImage',
            'isPublic','noCache','lang','meta','headers','lastModified','links','scripts'
        ];
        if (!in_array($key, $allowed, true)) {
            // ignore silently to avoid accidental mutation
            return;
        }

        // delegate to setters when possible
        switch ($key) {
            case 'title':
                $this->setTitle((string)$value);
                return;
            case 'slug':
                $this->setSlug((string)$value);
                return;
            case 'route':
                $this->setRoute((string)$value);
                return;
            case 'content':
                $this->setContent((string)$value);
                return;
            case 'summary':
                $this->setSummary($value === null ? null : (string)$value);
                return;
            case 'layout':
                $this->setLayout($value === null ? null : (string)$value);
                return;
            case 'type':
                $this->setType($value === null ? null : (string)$value);
                return;
            case 'featuredImage':
                $this->setFeaturedImage($value === null ? null : (string)$value);
                return;
            case 'isPublic':
                $this->setPublic((bool)$value);
                return;
            case 'noCache':
                $this->setNoCache((bool)$value);
                return;
            case 'lang':
                $this->setLang($value === null ? null : (string)$value);
                return;
            case 'meta':
                $this->meta = is_array($value) ? $value : $this->meta;
                return;
            case 'headers':
                $this->headers = is_array($value) ? $value : $this->headers;
                return;
            case 'lastModified':
                $this->setLastModified($value === null ? null : (string)$value);
                return;
            case 'links':
                $this->setLinks(is_array($value) ? $value : []);
                return;
            case 'scripts':
                $this->setScripts(is_array($value) ? $value : []);
                return;
        }
    }

    /**
     * Hydrate from array (alias).
     *
     * @param array<string,mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    /**
     * Export to array.
     *
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'slug' => $this->slug,
            'route' => $this->route,
            'content' => $this->content,
            'summary' => $this->summary,
            'layout' => $this->layout,
            'type' => $this->type,
            'featuredImage' => $this->featuredImage,
            'isPublic' => $this->isPublic,
            'noCache' => $this->noCache,
            'lang' => $this->lang,
            'meta' => $this->meta,
            'headers' => $this->headers,
            'lastModified' => $this->lastModified,
            'links' => $this->links,
            'scripts' => $this->scripts,
        ];
    }

    /* --------------------
     * Getters / Setters
     * -------------------- */

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $this->sanitizeSlug($slug);
        return $this;
    }

    public function getRoute(): string
    {
        return $this->route;
    }

    public function setRoute(string $route): self
    {
        $this->route = $route;
        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getSummary(): ?string
    {
        return $this->summary;
    }

    public function setSummary(?string $summary): self
    {
        $this->summary = $summary;
        return $this;
    }

    public function getLayout(): ?string
    {
        return $this->layout;
    }

    public function setLayout(?string $layout): self
    {
        $this->layout = $layout;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getFeaturedImage(): ?string
    {
        return $this->featuredImage;
    }

    public function setFeaturedImage(?string $img): self
    {
        $this->featuredImage = $img;
        return $this;
    }

    public function isPublic(): bool
    {
        return $this->isPublic;
    }

    public function setPublic(bool $v = true): self
    {
        $this->isPublic = $v;
        return $this;
    }

    public function isNoCache(): bool
    {
        return $this->noCache;
    }

    public function setNoCache(bool $v = true): self
    {
        $this->noCache = $v;
        return $this;
    }

    public function getLang(): ?string
    {
        return $this->lang;
    }

    public function setLang(?string $lang): self
    {
        $this->lang = $lang;
        return $this;
    }

    /**
     * Get meta item.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getMeta(string $key, $default = null)
    {
        return $this->meta[$key] ?? $default;
    }

    /**
     * Set meta item.
     *
     * @param string $key
     * @param mixed $value
     */
    public function setMeta(string $key, $value): self
    {
        $this->meta[$key] = $value;
        return $this;
    }

    public function hasMeta(string $key): bool
    {
        return array_key_exists($key, $this->meta);
    }

    public function removeMeta(string $key): self
    {
        if (array_key_exists($key, $this->meta)) {
            unset($this->meta[$key]);
        }
        return $this;
    }

    /**
     * Get headers (merged with secure defaults).
     *
     * @param bool $includeDefaults
     * @return array<string,string>
     */
    public function getHeaders(bool $includeDefaults = true): array
    {
        $defaults = [
            'Content-Type' => 'text/html; charset=UTF-8',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'no-referrer-when-downgrade',
        ];

        if ($this->sendPoweredBy) {
            $defaults['X-Powered-By'] = 'Yantra';
        }

        if ($this->noCache) {
            $defaults['Cache-Control'] = 'no-store, no-cache, must-revalidate, max-age=0';
            $defaults['Pragma'] = 'no-cache';
        } else {
            $defaults['Cache-Control'] = $defaults['Cache-Control'] ?? 'public, max-age=600';
        }

        if ($this->lang) {
            $defaults['Content-Language'] = $this->lang;
        }

        if (!$includeDefaults) {
            return $this->headers;
        }

        return array_merge($defaults, $this->headers);
    }

    /**
     * Toggle X-Powered-By emission.
     */
    public function setSendPoweredBy(bool $v = true): self
    {
        $this->sendPoweredBy = $v;
        return $this;
    }

    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function removeHeader(string $name): self
    {
        if (array_key_exists($name, $this->headers)) {
            unset($this->headers[$name]);
        }
        return $this;
    }

    /**
     * Merge headers with existing.
     *
     * @param array<string,string> $headers
     */
    public function mergeHeaders(array $headers): self
    {
        $this->headers = array_merge($this->headers, $headers);
        return $this;
    }

    public function getLastModified(): ?string
    {
        return $this->lastModified;
    }

    public function setLastModified(?string $iso8601): self
    {
        $this->lastModified = $iso8601;
        return $this;
    }

    /* --------------------
     * Links API (head/footer, priority, dedupe)
     * -------------------- */

    /**
     * Replace the entire links list.
     *
     * @param array<int,array> $links
     */
    public function setLinks(array $links): self
    {
        $this->links = [];
        foreach ($links as $l) {
            if (!is_array($l)) {
                continue;
            }
            $rel = $l['rel'] ?? ($l['attrs']['rel'] ?? null);
            $href = $l['href'] ?? null;
            $attrs = is_array($l['attrs'] ?? null) ? $l['attrs'] : [];
            $position = in_array($l['position'] ?? 'head', ['head', 'footer'], true) ? $l['position'] : 'head';
            $priority = isset($l['priority']) ? (int)$l['priority'] : 10;
            if ($href === null || $rel === null) {
                continue;
            }
            $this->addLink((string)$rel, (string)$href, $attrs, $position, $priority);
        }
        return $this;
    }

    /**
     * Add a link. Dedupe by href+rel (keeps higher priority).
     *
     * @param string $rel
     * @param string $href
     * @param array<string,string> $attrs
     * @param string $position 'head'|'footer'
     * @param int $priority
     */
    public function addLink(string $rel, string $href, array $attrs = [], string $position = 'head', int $priority = 10): self
    {
        $href = trim($href);
        // basic validation: forbid javascript: URIs
        if (preg_match('#^\s*javascript:#i', $href)) {
            return $this;
        }

        $entry = [
            'rel' => $rel,
            'href' => $href,
            'attrs' => $attrs,
            'position' => in_array($position, ['head', 'footer'], true) ? $position : 'head',
            'priority' => $priority,
        ];

        // dedupe: if exists with same href+rel replace only if new priority higher
        foreach ($this->links as $i => $l) {
            if (($l['href'] ?? null) === $href && ($l['rel'] ?? null) === $rel) {
                if (($l['priority'] ?? 10) >= $priority) {
                    return $this; // existing is equal or higher priority, ignore
                }
                // replace with new (higher priority)
                $this->links[$i] = $entry;
                return $this;
            }
        }

        $this->links[] = $entry;
        // keep stable ordering by priority (higher first)
        usort($this->links, static function ($a, $b) {
            return ($b['priority'] ?? 0) <=> ($a['priority'] ?? 0);
        });

        return $this;
    }

    /**
     * Remove links by href exact match.
     */
    public function removeLinkByHref(string $href): self
    {
        $this->links = array_values(array_filter($this->links, static function ($l) use ($href) {
            return !isset($l['href']) || (string)$l['href'] !== $href;
        }));
        return $this;
    }

    /**
     * Render link tags for given position (head/footer).
     *
     * @param string $position 'head'|'footer'
     * @param bool $echo
     * @return string|null
     */
    public function renderLinks(string $position = 'head', bool $echo = true): ?string
    {
        $out = '';
        foreach ($this->links as $link) {
            if (($link['position'] ?? 'head') !== $position) {
                continue;
            }
            $out .= $this->renderLinkTag($link) . PHP_EOL;
        }
        if ($echo) {
            echo $out;
            return null;
        }
        return $out;
    }

    /**
     * Internal: build single <link> tag from definition.
     */
    private function renderLinkTag(array $link): string
    {
        $rel = htmlspecialchars((string)($link['rel'] ?? ''), ENT_QUOTES, 'UTF-8');
        $href = htmlspecialchars((string)($link['href'] ?? ''), ENT_QUOTES, 'UTF-8');

        $attrs = $link['attrs'] ?? [];
        $attrs['rel'] = $rel;
        $attrs['href'] = $href;

        $parts = [];
        foreach ($attrs as $k => $v) {
            $parts[] = htmlspecialchars((string)$k, ENT_QUOTES, 'UTF-8')
                . '="' . htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8') . '"';
        }

        return '<link ' . implode(' ', $parts) . '>';
    }

    /* --------------------
     * Scripts API (head/footer, inline, priority, dedupe)
     * -------------------- */

    /**
     * Replace scripts (array of definitions).
     *
     * @param array<int,array> $scripts
     */
    public function setScripts(array $scripts): self
    {
        $this->scripts = [];
        foreach ($scripts as $s) {
            if (!is_array($s)) {
                continue;
            }
            $src = $s['src'] ?? null;
            $inline = !empty($s['inline']);
            $content = $s['content'] ?? null;
            $attrs = is_array($s['attrs'] ?? null) ? $s['attrs'] : [];
            $position = in_array($s['position'] ?? 'footer', ['head', 'footer'], true) ? $s['position'] : 'footer';
            $priority = isset($s['priority']) ? (int)$s['priority'] : 10;
            $this->addScript($src, $attrs, $inline, $content, $position, $priority);
        }
        return $this;
    }

    /**
     * Add a script. Dedupe external scripts by src (keeps higher priority).
     *
     * @param string|null $src
     * @param array<string,string> $attrs
     * @param bool $inline
     * @param string|null $content
     * @param string $position 'head'|'footer'
     * @param int $priority
     */
    public function addScript(?string $src = null, array $attrs = [], bool $inline = false, ?string $content = null, string $position = 'footer', int $priority = 10): self
    {
        if ($src !== null && preg_match('#^\s*javascript:#i', $src)) {
            // refuse javascript: URIs
            return $this;
        }

        $entry = [
            'src' => $src,
            'inline' => $inline,
            'content' => $content,
            'attrs' => $attrs,
            'position' => in_array($position, ['head', 'footer'], true) ? $position : 'footer',
            'priority' => $priority,
        ];

        // dedupe external by src
        if ($src !== null) {
            foreach ($this->scripts as $i => $s) {
                if (($s['src'] ?? null) === $src) {
                    if (($s['priority'] ?? 10) >= $priority) {
                        return $this; // keep existing
                    }
                    $this->scripts[$i] = $entry;
                    usort($this->scripts, static function ($a, $b) {
                        return ($b['priority'] ?? 0) <=> ($a['priority'] ?? 0);
                    });
                    return $this;
                }
            }
        }

        $this->scripts[] = $entry;
        usort($this->scripts, static function ($a, $b) {
            return ($b['priority'] ?? 0) <=> ($a['priority'] ?? 0);
        });

        return $this;
    }

    /**
     * Remove external script by src.
     */
    public function removeScriptBySrc(string $src): self
    {
        $this->scripts = array_values(array_filter($this->scripts, static function ($s) use ($src) {
            return !isset($s['src']) || (string)$s['src'] !== $src;
        }));
        return $this;
    }

    /**
     * Render scripts for position (head/footer).
     *
     * @param string $position
     * @param bool $echo
     * @return string|null
     */
    public function renderScripts(string $position = 'footer', bool $echo = true): ?string
    {
        $out = '';
        foreach ($this->scripts as $s) {
            if (($s['position'] ?? 'footer') !== $position) {
                continue;
            }
            $out .= $this->renderScriptTag($s) . PHP_EOL;
        }
        if ($echo) {
            echo $out;
            return null;
        }
        return $out;
    }

    /**
     * Render single script tag (external or inline).
     *
     * Ensures boolean attributes are handled and supports nonce injection for inline scripts.
     */
    private function renderScriptTag(array $s): string
    {
        $attrs = $s['attrs'] ?? [];
        $inline = !empty($s['inline']);
        $src = $s['src'] ?? null;
        $content = $s['content'] ?? '';

        // inline script: inject nonce if present and not explicitly set
        if ($inline) {
            if (!isset($attrs['nonce']) && $this->getCspNonce() !== null) {
                $attrs['nonce'] = $this->getCspNonce();
            }

            $attrParts = [];
            foreach ($attrs as $k => $v) {
                $attrParts[] = htmlspecialchars((string)$k, ENT_QUOTES, 'UTF-8')
                    . ('' === (string)$v ? '' : '="' . htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8') . '"');
            }
            $attrStr = $attrParts ? ' ' . implode(' ', $attrParts) : '';
            return '<script' . $attrStr . '>' . ($content ?? '') . '</script>';
        }

        // external script
        if ($src === null) {
            return '<script></script>';
        }

        $attrs['src'] = $src;
        $attrParts = [];
        foreach ($attrs as $k => $v) {
            // boolean attribute: allow value '' to render as attribute without value
            if ($v === '') {
                $attrParts[] = htmlspecialchars((string)$k, ENT_QUOTES, 'UTF-8');
            } else {
                $attrParts[] = htmlspecialchars((string)$k, ENT_QUOTES, 'UTF-8')
                    . '="' . htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8') . '"';
            }
        }

        return '<script ' . implode(' ', $attrParts) . '></script>';
    }

    /* --------------------
     * URL helper
     * -------------------- */

    /**
     * Build URL for this page given a site root.
     *
     * @param string $siteRoot
     * @param ?string $prefix
     * @return string
     */
    public function getUrl(string $siteRoot, ?string $prefix = null): string
    {
        $root = rtrim($siteRoot, '/');

        if ($this->route !== '') {
            if (preg_match('#^https?://#i', $this->route)) {
                return $this->route;
            }
            if (strpos($this->route, '/') === 0) {
                return $root . $this->route;
            }
            return $root . '/' . ltrim($this->route, '/');
        }

        $prefixPart = $prefix ? '/' . trim($prefix, '/') : '';
        $slugPart = $this->slug !== '' ? '/' . ltrim($this->slug, '/') : '/';
        return $root . $prefixPart . $slugPart;
    }

    /* --------------------
     * Render helpers (return by default)
     * -------------------- */

    /**
     * Render meta tags (all or single key).
     *
     * @param string|null $key
     * @param bool $echo
     * @return string|null
     */
    public function renderMeta(?string $key = null, bool $echo = true): ?string
    {
        if ($key !== null) {
            $val = $this->getMeta($key, '');
            if ($echo) {
                echo htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8');
                return null;
            }
            return (string)$val;
        }

        $out = '';
        foreach ($this->meta as $name => $value) {
            $out .= sprintf(
                '<meta name="%s" content="%s">%s',
                htmlspecialchars((string)$name, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'),
                PHP_EOL
            );
        }

        if ($echo) {
            echo $out;
            return null;
        }
        return $out;
    }

    public function renderTitle(bool $echo = true): string
    {
        $out = htmlspecialchars($this->title, ENT_QUOTES, 'UTF-8');
        if ($echo) {
            echo $out;
        }
        return $out;
    }

    /**
     * Render content. Content is assumed to be trusted or preprocessed.
     *
     * @param bool $echo
     * @return string
     */
    public function renderContent(bool $echo = true): string
    {
        if ($echo) {
            echo $this->content;
        }
        return $this->content;
    }

    public function renderSummary(bool $echo = true): string
    {
        $out = $this->summary !== null ? htmlspecialchars($this->summary, ENT_QUOTES, 'UTF-8') : '';
        if ($echo) {
            echo $out;
        }
        return $out;
    }

    /**
     * Render featured image (URL or <img> tag).
     *
     * @param bool $asTag
     * @param bool $echo
     * @return string|null
     */
    public function renderFeaturedImage(bool $asTag = false, bool $echo = true): ?string
    {
        $img = $this->featuredImage;
        if ($img === null || $img === '') {
            return null;
        }

        $result = $asTag
            ? sprintf('<img src="%s" alt="%s">', htmlspecialchars($img, ENT_QUOTES, 'UTF-8'), htmlspecialchars($this->title ?? '', ENT_QUOTES, 'UTF-8'))
            : htmlspecialchars($img, ENT_QUOTES, 'UTF-8');

        if ($echo) {
            echo $result;
            return null;
        }
        return $result;
    }

    public function renderLang(bool $echo = true): string
    {
        $out = htmlspecialchars($this->lang ?? 'en', ENT_QUOTES, 'UTF-8');
        if ($echo) {
            echo $out;
        }
        return $out;
    }

    /* --------------------
     * Headers & conditional caching helpers
     * -------------------- */

    /**
     * Prepare and optionally emit headers. Does not exit.
     *
     * Returns an array: ['status' => 200|304, 'headers' => [...]]
     *
     * @param bool $checkConditional If true, check If-None-Match / If-Modified-Since
     * @param bool $emitHeaders If true, call header() for each header
     * @return array{status:int,headers:array<string,string>}
     */
    public function sendHeaders(bool $checkConditional = true, bool $emitHeaders = false): array
    {
        $headers = $this->getHeaders(true);

        $etag = $this->getEtag() ?? ($this->lastModified ? '"' . sha1($this->lastModified) . '"' : null);
        if ($etag !== null) {
            $headers['ETag'] = $etag;
        }

        if ($this->lastModified !== null) {
            $dt = @date('r', strtotime($this->lastModified));
            if ($dt !== false) {
                $headers['Last-Modified'] = $dt;
            }
        }

        // include CSP header if prepared via helper
        if (!empty($this->headers['Content-Security-Policy'])) {
            $headers['Content-Security-Policy'] = $this->headers['Content-Security-Policy'];
        }

        if ($emitHeaders) {
            foreach ($headers as $k => $v) {
                header("$k: $v", true);
            }
        }

        $status = 200;
        if ($checkConditional) {
            $ifNoneMatch = $_SERVER['HTTP_IF_NONE_MATCH'] ?? null;
            $ifModifiedSince = $_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? null;

            $matchedEtag = $ifNoneMatch !== null && $etag !== null && trim($ifNoneMatch) === $etag;
            $matchedModified = false;
            if ($ifModifiedSince !== null && !empty($this->lastModified)) {
                $clientTs = strtotime($ifModifiedSince);
                $serverTs = strtotime($this->lastModified);
                $matchedModified = $clientTs !== false && $serverTs !== false && $clientTs >= $serverTs;
            }

            if ($matchedEtag || $matchedModified) {
                $status = 304;
            }
        }

        return ['status' => $status, 'headers' => $headers];
    }

    /* --------------------
     * ETag & last-modified computation
     * -------------------- */

    /**
     * Compute an ETag for the rendered page.
     *
     * If explicit ETag header set, return it.
     * Otherwise compute based on lastModified / content + assets.
     */
    public function getEtag(): ?string
    {
        if (!empty($this->headers['ETag'])) {
            return (string)$this->headers['ETag'];
        }

        $payload = ($this->lastModified ?? '') . '|' . $this->content
            . '|' . json_encode($this->links, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            . '|' . json_encode($this->scripts, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($payload === '') {
            return null;
        }

        return '"' . sha1($payload) . '"';
    }

    /**
     * Compute lastModified based on provided file path.
     *
     * Strategy:
     *  - sidecar JSON with lastModified
     *  - fallback to filemtime($filePath)
     *
     * @param string $filePath
     * @return string|null ISO 8601 or null
     */
    public function computeLastModifiedFromFile(string $filePath): ?string
    {
        if (!file_exists($filePath)) {
            return null;
        }

        $sidecar = preg_replace('/\.[^.]+$/', '.json', $filePath);
        if ($sidecar && file_exists($sidecar)) {
            $json = @file_get_contents($sidecar);
            if ($json !== false) {
                $data = json_decode($json, true);
                if (is_array($data) && !empty($data['lastModified'])) {
                    $ts = strtotime((string)$data['lastModified']);
                    if ($ts !== false) {
                        $this->lastModified = date(DATE_ATOM, $ts);
                        return $this->lastModified;
                    }
                }
            }
        }

        $mtime = @filemtime($filePath);
        if ($mtime !== false) {
            $this->lastModified = date(DATE_ATOM, $mtime);
            return $this->lastModified;
        }

        return null;
    }

    /* --------------------
     * CSP nonce helpers
     * -------------------- */

    /**
     * Generate or return existing CSP nonce (Base64).
     */
    public function getCspNonce(): ?string
    {
        if ($this->cspNonce !== null) {
            return $this->cspNonce;
        }
        try {
            $this->cspNonce = base64_encode(random_bytes(16));
        } catch (\Throwable $e) {
            // fallback
            $this->cspNonce = bin2hex(random_bytes(8));
        }
        return $this->cspNonce;
    }

    /**
     * Build and set a basic Content-Security-Policy header including nonce for inline scripts.
     *
     * Example:
     * $page->setContentSecurityPolicy([
     *    "default-src" => ["'self'"],
     *    "script-src" => ["'self'", "'nonce-{{nonce}}'", "https://cdn.example.com"]
     * ]);
     *
     * "{{nonce}}" will be replaced with generated nonce.
     *
     * @param array<string,array<int,string>|string> $policyMap
     * @return self
     */
    public function setContentSecurityPolicy(array $policyMap): self
    {
        $nonce = $this->getCspNonce();
        $parts = [];
        foreach ($policyMap as $directive => $values) {
            $vals = is_array($values) ? $values : [$values];
            $replaced = [];
            foreach ($vals as $v) {
                $replaced[] = str_replace('{{nonce}}', $nonce ?? '', $v);
            }
            $parts[] = $directive . ' ' . implode(' ', $replaced);
        }
        $csp = implode('; ', $parts);
        $this->setHeader('Content-Security-Policy', $csp);
        return $this;
    }

    /* --------------------
     * Utilities
     * -------------------- */

    /**
     * Sanitize slug: transliterate then sanitize.
     */
    private function sanitizeSlug(string $slug): string
    {
        $s = trim($slug);

        // Transliterate to ASCII when possible
        if (class_exists('\Transliterator')) {
            try {
                $t = \Transliterator::create('Any-Latin; Latin-ASCII; NFD; [:Nonspacing Mark:] Remove; NFC');
                if ($t !== null) {
                    $s = $t->transliterate($s) ?: $s;
                }
            } catch (\Throwable $e) {
                // ignore
            }
        } elseif (function_exists('iconv')) {
            $try = @iconv('UTF-8', 'ASCII//TRANSLIT', $s);
            if ($try !== false) {
                $s = $try;
            }
        }

        $s = mb_strtolower($s);
        $s = preg_replace('/[ _]+/u', '-', $s);
        $s = preg_replace('/[^\p{L}\p{N}\-]+/u', '', $s);
        $s = preg_replace('/-+/u', '-', $s);
        return trim($s, '-');
    }
}
