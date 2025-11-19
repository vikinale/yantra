<?php

namespace System\Core;

class PageParser
{
    // Properties
    public array $keywords = [];
    public string $slug = "";
    public string $layout = "";
    public string $title = "";
    public string $title_tell = "";
    public string $description = "";
    public string $content = "";
    public string $featured_image = "";
    public string $author = "";
    public string $author_email = "";
    public ?\DateTime $created;
    public ?\DateTime $updated;
    public ?\DateTime $publish_date = null;
    public ?\DateTime $expiry_date = null;
    public array $tags = [];
    public string $category = "";
    public string $status = "published";
    public string $visibility = "public";
    public string $canonical_url = "";
    public string $robots = "index, follow";

    // Assets
    public array $css_files = [];
    public array $header_js_files = [];
    public array $footer_js_files = [];
    public string $favicon = "";
    public string $custom_scripts = "";

    // Tracking & Structured Data
    public string $structured_data = "";

    // Child Pages & Permissions
    public array $child_pages = [];
    public array $permissions = [];

    // Meta Data & Internal Properties
    public array $meta = [];
    private array $cache = [];
    private array $errors = [];
    public ?int $parent_page = null;
    public bool $featured = false;
    public float $priority = 0.5;

    public function __construct()
    {
        $this->created = new \DateTime();
        $this->updated = new \DateTime();
    }

    // Meta Management
    public function meta(string $key, mixed $value = null): mixed
    {
        if ($key === null) return $this->meta;
        if ($value === null) return $this->meta[$key] ?? null;

        $this->meta[$key] = $value;
        return true;
    }

    // OpenGraph Meta Rendering
    public function theOpenGraphMeta(): string
    {
        $defaults = [
            'og:title' => $this->title,
            'og:description' => $this->description,
            'og:image' => '',
            'og:type' => 'website',
            'og:url' => $this->canonical_url,
        ];

        return implode("\n", array_map(
            fn($key, $default) => sprintf(
                '<meta property="%s" content="%s">',
                $key,
                htmlspecialchars($this->meta[$key] ?? $default, ENT_QUOTES, 'UTF-8')
            ),
            array_keys($defaults),
            $defaults
        ));
    }

    // Twitter Meta Rendering
    public function theTwitterMeta(): string
    {
        $defaults = [
            'twitter:title' => $this->title,
            'twitter:description' => $this->description,
            'twitter:card' => 'summary_large_image',
            'twitter:image' => '',
        ];

        return implode("\n", array_map(
            fn($key, $default) => sprintf(
                '<meta name="%s" content="%s">',
                $key,
                htmlspecialchars($this->meta[$key] ?? $default, ENT_QUOTES, 'UTF-8')
            ),
            array_keys($defaults),
            $defaults
        ));
    }

    // Render Methods
    public function theTitle(): void
    {
        echo sprintf('<title>%s</title>', htmlspecialchars($this->meta['meta_title'] ?: "Pawna Camping - Best Lakeside Experience Near Lonavala & Pune", ENT_QUOTES, 'UTF-8'));
    }

    public function thePageMeta(): string
    {
        $metaTags = [];

        foreach (['author', 'description', 'robots'] as $attr) {
            if (!empty($this->$attr)) {
                $metaTags[] = sprintf('<meta name="%s" content="%s">', $attr, htmlspecialchars($this->$attr, ENT_QUOTES, 'UTF-8'));
            }
        }

        if (!empty($this->keywords)) {
            $metaTags[] = sprintf('<meta name="keywords" content="%s">', htmlspecialchars(implode(",", $this->keywords), ENT_QUOTES, 'UTF-8'));
        }

        foreach ($this->meta as $key => $value) {
            $value = is_array($value) ? implode(", ", $value) : $value;
            if (is_string($value)) {
                $metaTags[] = sprintf('<meta name="%s" content="%s">', htmlspecialchars($key, ENT_QUOTES, 'UTF-8'), htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));
            }
        }

        return implode("\n", $metaTags);
    }

    public function theHeaderCss(): string
    {
        return implode("\n", array_map(fn($file) => sprintf('<link rel="stylesheet" href="%s">', htmlspecialchars($file, ENT_QUOTES, 'UTF-8')), $this->css_files));
    }

    public function theHeaderJs(): string
    {
        return implode("\n", array_map(fn($file) => sprintf('<script src="%s" defer></script>', htmlspecialchars($file, ENT_QUOTES, 'UTF-8')), $this->header_js_files));
    }

    public function theFooterJs(): string
    {
        return implode("\n", array_map(fn($file) => sprintf('<script src="%s"></script>', htmlspecialchars($file, ENT_QUOTES, 'UTF-8')), $this->footer_js_files));
    }

    public function theCanonical(): string
    {
        return !empty($this->canonical_url) ? sprintf('<link rel="canonical" href="%s">', htmlspecialchars($this->canonical_url, ENT_QUOTES, 'UTF-8')) : '';
    }


    public function logError(string $error): void
    {
        $this->errors[] = $error;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'keywords' => $this->keywords,
            'slug' => $this->slug,
            'meta'=>$this->meta,
            'layout' => $this->layout,
            'author' => $this->author,
            'canonical_url' => $this->canonical_url,
            'css_files' => $this->css_files,
            'header_js_files' => $this->header_js_files,
            'footer_js_files' => $this->footer_js_files,
        ];
    }
}