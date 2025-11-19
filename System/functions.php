<?php
declare(strict_types=1);

use System\Config;
use System\Database\Database;
use System\Hooks;
use System\Theme;

/**
 * Action wrapper
 *
 * @param string        $hook
 * @param callable      $callback
 * @param int           $priority
 * @param string|null   $name         optional unique name for registration
 * @param int           $accepted_args number of args to pass to callback
 */
function add_action(string $hook, callable $callback, int $priority = 10, ?string $name = null, int $accepted_args = 1): void
{
    Hooks::add_action($hook, $callback, $priority, $name, $accepted_args);
}

/**
 * Fire an action — forwards variadic args correctly.
 *
 * @param string $hook
 * @param mixed  ...$args
 */
function do_action(string $hook, ...$args): void
{
    Hooks::do_action($hook, ...$args);
}

/**
 * Add a "block" action namespace shorthand: yfb_<block_name>
 *
 * @param string   $block_name
 * @param callable $callback
 * @param int      $priority
 * @param string|null $name
 * @param int      $accepted_args
 */
function add_block(string $block_name, callable $callback, int $priority = 10, ?string $name = null, int $accepted_args = 1): void
{
    Hooks::add_action('yfb_' . $block_name, $callback, $priority, $name, $accepted_args);
}

/**
 * Execute a block. By default, passes two args: $default and $append.
 *
 * @param string $block_name
 * @param mixed  $default
 * @param bool   $append
 */
function do_block(string $block_name, $default = null, bool $append = false): void
{
    Hooks::do_action('yfb_' . $block_name, $default, $append);
}

/**
 * Filter wrapper
 *
 * @param string        $hook
 * @param callable      $callback
 * @param int           $priority
 * @param string|null   $name
 * @param int           $accepted_args
 */
function add_filter(string $hook, callable $callback, int $priority = 10, ?string $name = null, int $accepted_args = 1): void
{
    Hooks::add_filter($hook, $callback, $priority, $name, $accepted_args);
}

/**
 * Apply filters to a value.
 *
 * @param string $hook
 * @param mixed  $value
 * @param mixed  ...$args
 * @return mixed
 */
function apply_filter(string $hook, $value, ...$args)
{
    return Hooks::apply_filter($hook, $value, ...$args);
}

/**
 * Load a section (view) through theme loader.
 *
 * @param string $file
 * @param string $template
 * @param array  $data
 */
function the_section(string $file, string $template = "", array $data = []): void
{
    global $env;
    $env->theme->load($template, $file, $data);
}

/**
 * Include the page view.
 */
function the_content(): void
{
    global $env;
    require "{$env->page_view}.php";
} 
/**
 * Theme initialisation filter: ensure theme instance is available
 */
add_filter('get_theme', function ($theme, $name = '') {
    global $env;
    if (!isset($env->theme)) {
        if (!$theme) {
            return new Theme($name);
        }
        return $theme;
    }
    return $env->theme;
}, 10, null, 2);

/**
 * Initialise theme on boot
 */
add_action('init_theme', function ($theme = '') {
    global $env;
    if (!empty($theme)) {
        $env->theme = apply_filter('get_theme', null, $theme);
    }
    if (!isset($env->theme)) {
        $env->theme = apply_filter('get_theme', null, Config::get('app.theme'));
    }
}, 10, null, 1);

/**
 * Provide default view path
 */
add_filter('view_path', function ($path) {
    return 'App/Views';
}, 10, null, 1);

/**
 * Get media absolute URL by id or null if not found
 *
 * @param int $id
 * @return string|null
 */
function get_media_url(int $id): ?string
{
    try {
        $pdo = Database::getInstance()->getPDO();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare("SELECT path, name FROM yt_media WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);

        $file = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($file && isset($file['path'], $file['name'])) {
            return site_url(rtrim($file['path'], '/') . '/' . ltrim($file['name'], '/'));
        }
        return null;
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * Get media thumbnail path (relative)
 *
 * @param int $id
 * @param string $size
 * @return string|null
 */
function get_media_thumb(int $id, string $size = 'thumb'): ?string
{
    try {
        $pdo = Database::getInstance()->getPDO();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare("SELECT path, name FROM yt_media WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);

        $file = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($file && isset($file['path'], $file['name'])) {
            return rtrim($file['path'], '/') . '/' . $size . '_' . ltrim($file['name'], '/');
        }
        return null;
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * Find media id by hash/code
 *
 * @param string $code
 * @return int|null
 */
function get_media_by_code(string $code): ?int
{
    try {
        $pdo = Database::getInstance()->getPDO();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare("SELECT id FROM yt_media WHERE hash = :hash LIMIT 1");
        $stmt->execute([':hash' => $code]);

        $id = $stmt->fetchColumn();
        return $id !== false ? (int)$id : null;
    } catch (PDOException $e) {
        return null;
    }
}


/* ------------------------------------------------------------------
 * Asset renderers (head/footer) — expects Core\WebPage-like $page object
 * ------------------------------------------------------------------ */

function yantra_render_head_links(object $page): void
{
    if (!method_exists($page, 'renderLinks')) {
        return;
    }
    $html = (string)$page->renderLinks('head', false);
    if ($html !== '') {
        echo $html;
    }
}
add_action('head_links', 'yantra_render_head_links', 10, null, 1);

function yantra_render_head_scripts(object $page): void
{
    if (!method_exists($page, 'renderScripts')) {
        return;
    }
    $html = (string)$page->renderScripts('head', false);
    if ($html !== '') {
        echo $html;
    }
}
add_action('head_scripts', 'yantra_render_head_scripts', 10, null, 1);

function yantra_render_footer_scripts(object $page): void
{
    if (!method_exists($page, 'renderScripts')) {
        return;
    }
    $html = (string)$page->renderScripts('footer', false);
    if ($html !== '') {
        echo $html;
    }
}
add_action('footer_scripts', 'yantra_render_footer_scripts', 10, null, 1);

/* -------------------------
 * Page meta rendering
 * ------------------------- */
function yantra_render_page_meta(object $page): void
{
    if (!is_object($page)) {
        return;
    }

    // Prefer WebPage::renderMeta (safe HTML), fallback to optional helpers
    if (method_exists($page, 'renderMeta')) {
        $page->renderMeta(null, true);
    }

    foreach (['thePageMeta', 'theOpenGraphMeta', 'theTwitterMeta'] as $fn) {
        if (method_exists($page, $fn)) {
            echo (string)$page->$fn();
        }
    }
}
add_action('page_meta', 'yantra_render_page_meta', 10, null, 1);

/* -------------------------
 * URL helpers
 * ------------------------- */
function site_url(string $append = ""): string
{
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? 'localhost');
    $base = rtrim((string) (Config::get('app.site') ?? ''), '/');

    $url = rtrim($protocol . $host . '/' . ltrim($base, '/'), '/');

    if ($append === '' || $append === null) {
        return $url;
    }

    return $url . '/' . ltrim($append, '/');
}

function content_url(string $append = ""): string
{
    $contentBase = Config::get('app.content') ?? '';
    return site_url(rtrim($contentBase, '/') . ($append !== '' ? '/' . ltrim($append, '/') : ''));
}

function get_current_url(): string
{
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);

    $protocol = $isHttps ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? 'localhost');
    $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
    return $protocol . $host . $requestUri;
}

function theme_url(string $url = ""): string
{
    global $env;
    return rtrim($env->theme->url ?? '', '/') . '/' . ltrim($url, '/');
}

/* -------------------------
 * Theme loader convenience
 * ------------------------- */
function load_html(string $file, array $data = [])
{
    global $env;
    return $env->theme->load_html($file, $data);
}

/* ------------------------------------------------------------------
 * Theme initialization hooks (keep existing behavior)
 * ------------------------------------------------------------------ */
add_filter('get_theme', function ($theme, $name = '') {
    global $env;
    if (!isset($env->theme)) {
        if (!$theme) {
            return new Theme($name);
        }
        return $theme;
    }
    return $env->theme;
}, 10, null, 2);

add_action('init_theme', function ($theme = '') {
    global $env;
    if (!empty($theme)) {
        $env->theme = apply_filter('get_theme', null, $theme);
    }
    if (!isset($env->theme)) {
        $env->theme = apply_filter('get_theme', null, Config::get('app.theme'));
    }
}, 10, null, 1);

add_filter('view_path', function ($path) {
    return 'App/Views';
}, 10, null, 1);

/* ------------------------------------------------------------------
 * Buffered UI block renderer + CSRF helpers (Yantra-friendly)
 * ------------------------------------------------------------------ */

/**
 * Render a UI block (template) and return generated HTML (does not echo).
 *
 * Usage:
 *   $html = yantra_render_block($theme_path . 'ui-blocks/sidebar.php', ['foo' => 'bar']);
 */
function yantra_render_block(string $file, array $vars = []): string
{
    if (!file_exists($file)) {
        return "<!-- yantra_render_block: file not found: " . htmlspecialchars($file, ENT_QUOTES, 'UTF-8') . " -->";
    }

    extract($vars, EXTR_SKIP);
    ob_start();
    try {
        include $file;
    } catch (\Throwable $e) {
        ob_end_clean();
        return "<!-- yantra_render_block: error in {$file}: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . " -->";
    }

    return (string) ob_get_clean();
}
 
/* ------------------------------------------------------------------
 * DB-backed media helpers — guarded for portability
 *
 * These previously assumed Database::getInstance(); we now check before using.
 * If the DB subsystem is unavailable, the functions return null (safe fallback).
 * ------------------------------------------------------------------ */

if (!function_exists('get_media_url')) {
    function get_media_url(int $id): ?string
    {
        if (!class_exists('\\System\\Database')) {
            // DB not available — theme can still function without media records
            return null;
        }

        try {
            $pdo = \System\Database\Database::getInstance()->getPDO();
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            $stmt = $pdo->prepare("SELECT path, name FROM yt_media WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $id]);

            $file = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($file && isset($file['path'], $file['name'])) {
                return site_url(rtrim($file['path'], '/') . '/' . ltrim($file['name'], '/'));
            }
            return null;
        } catch (\PDOException $e) {
            // swallow DB errors for portability — consider logging in debug mode
            return null;
        }
    }
}

if (!function_exists('get_media_thumb')) {
    function get_media_thumb(int $id, string $size = 'thumb'): ?string
    {
        if (!class_exists('\\System\\Database')) {
            return null;
        }

        try {
            $pdo = \System\Database\Database::getInstance()->getPDO();
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            $stmt = $pdo->prepare("SELECT path, name FROM yt_media WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $id]);

            $file = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($file && isset($file['path'], $file['name'])) {
                return rtrim($file['path'], '/') . '/' . $size . '_' . ltrim($file['name'], '/');
            }
            return null;
        } catch (\PDOException $e) {
            return null;
        }
    }
}

if (!function_exists('get_media_by_code')) {
    function get_media_by_code(string $code): ?int
    {
        if (!class_exists('\\System\\Database')) {
            return null;
        }

        try {
            $pdo = \System\Database\Database::getInstance()->getPDO();
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            $stmt = $pdo->prepare("SELECT id FROM yt_media WHERE hash = :hash LIMIT 1");
            $stmt->execute([':hash' => $code]);

            $id = $stmt->fetchColumn();
            return $id !== false ? (int)$id : null;
        } catch (\PDOException $e) {
            return null;
        }
    }
}

function autoload_controllers(){
    // Define the base namespace and directory for your Controllers
    $baseNamespace = 'Controllers\\';
    $controllersDirectory = realpath(BASEPATH . '/App/Controllers'); // Normalize the path

    // Get all PHP files from the Controllers directory
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($controllersDirectory));
    foreach ($files as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            // Derive the fully qualified class name from the file path
            $relativePath = str_replace($controllersDirectory, '', $file->getRealPath());
            $relativePath = trim($relativePath, DIRECTORY_SEPARATOR); // Remove leading/trailing slashes
            $relativePath = str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath); // Convert to namespace format
            $relativePath = str_replace('.php', '', $relativePath); // Remove .php extension

            $className = $baseNamespace . $relativePath;
            if (class_exists($className)) {
                // Use reflection to check if the class has the runOnce method
                $reflector = new ReflectionClass($className);
                if ($reflector->hasMethod('runOnce') && $reflector->getMethod('runOnce')->isStatic()) {
                    // Call the static runOnce method
                    $className::runOnce();
                }
            }
        }
    }
}