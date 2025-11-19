<?php
namespace System\Utilities;

use PDO;
use PDOException;
use System\Database\Database;
use System\FormException;

/**
 * MediaManager
 *
 * Full-featured Media utility for Yantra.
 *
 * NOTE: This implementation expects a DB table `yt_media` with (at least) the columns:
 * media_id (PK, auto increment), file_name, file_path, mime_type, file_size, hash,
 * width, height, uploaded_by, status, created_at, updated_at, reference_count, title, alt_text, caption
 *
 * Add indexes on hash, status and created_at for performance.
 *
 * Storage is pluggable via StorageAdapterInterface. Default is LocalFilesystemAdapter.
 */
class MediaManager
{
    /* --------------------
     * Configuration
     * -------------------- */
    protected static $storageAdapter = null;
    public static array $defaultSizes = [
        'small'  => [100, 100],
        'medium' => [400, 400],
        'large'  => [1200, 1200],
    ];

    // Default allowed mimes and max file size (10 MB)
    public static array $defaultValidate = [
        'allowed_mimes' => [
            'image/jpeg', 'image/png', 'image/gif', 'image/webp',
            'video/mp4', 'audio/mpeg', 'application/pdf'
        ],
        'max_size' => 10 * 1024 * 1024,
    ];

    /* --------------------
     * Storage adapter handling
     * -------------------- */
    public static function setStorageAdapter(StorageAdapterInterface $adapter): void
    {
        self::$storageAdapter = $adapter;
    }

    protected static function getAdapter(): StorageAdapterInterface
    {
        if (self::$storageAdapter === null) {
            self::$storageAdapter = new LocalFilesystemAdapter(); // default adapter
        }
        return self::$storageAdapter;
    }

    /* --------------------
     * Public API
     * -------------------- */

    /**
     * Saves a single uploaded file (single $_FILES style array).
     *
     * @param array $file    Uploaded file array; e.g. ['name'=>..., 'tmp_name'=>..., 'type'=>..., 'size'=>..., 'error'=>...]
     * @param array $options Options:
     *                       - file_name: override filename (string)
     *                       - uploaded_by: int
     *                       - alt|alt_text
     *                       - title
     *                       - caption
     *                       - created_at (Y-m-d H:i:s)
     *                       - base_path (storage relative base path, default 'media')
     *                       - validate_rules (array override)
     * @return int media_id
     * @throws FormException
     */
    public static function saveUploadedFile(array $file, array $options = []): int
    {
        if (empty($file) || !isset($file['tmp_name'])) {
            throw new FormException('Invalid file array.');
        }
        if (!is_uploaded_file($file['tmp_name']) && !file_exists($file['tmp_name'])) {
            // allow copy-imported files too (e.g. bulk import)
            // but warn if not actually uploaded (still proceed)
        }
        $validateRules = $options['validate_rules'] ?? self::$defaultValidate;
        $validation = self::validateFile($file, $validateRules);
        if ($validation !== true) {
            throw new FormException($validation['error'] ?? 'Validation failed');
        }

        $basePath = $options['base_path'] ?? 'media';
        $originalFileName = self::sanitizeFileName($file['name'] ?? 'file');
        $origExt = pathinfo($originalFileName, PATHINFO_EXTENSION);

        // override name
        if (!empty($options['file_name'])) {
            $override = self::sanitizeFileName((string)$options['file_name']);
            if (pathinfo($override, PATHINFO_EXTENSION) === '' && $origExt !== '') {
                $override .= '.' . $origExt;
            }
            $fileName = $override;
            $originalFileName = $fileName;
        } else {
            $fileName = $originalFileName;
        }

        // date folder
        $dateFolder = date('Ymd');
        $relativeFolder = rtrim($basePath, '/') . '/' . $dateFolder;
        $adapter = self::getAdapter();
        // ensure dir on adapter
        if (!$adapter->exists($relativeFolder)) {
            $adapter->makeDir($relativeFolder);
        }

        // ensure unique filename
        $destinationRelative = $relativeFolder . '/' . $fileName;
        $fileIndex = 1;
        while ($adapter->exists($destinationRelative)) {
            $fileName = self::appendNumberToFileName($originalFileName, $fileIndex);
            $destinationRelative = $relativeFolder . '/' . $fileName;
            $fileIndex++;
        }

        // Put file via adapter (adapter handles moving / copying)
        $putSuccess = false;
        try {
            $putSuccess = $adapter->put($destinationRelative, $file['tmp_name']);
        } catch (\Throwable $e) {
            error_log('MediaManager::saveUploadedFile - adapter put error: ' . $e->getMessage());
            throw new FormException('Failed to store uploaded file.');
        }
        if (!$putSuccess) {
            throw new FormException('Failed to store uploaded file.');
        }

        // Calculate hash on stored file (adapter read path)
        // We ask adapter to provide local temp copy path for hashing if possible
        $localPathForHash = $adapter->getLocalPath($destinationRelative);
        $sha = $localPathForHash && file_exists($localPathForHash) ? sha1_file($localPathForHash) : null;
        if ($sha && self::getByHash($sha) !== null) {
            // Duplicate, remove new and return existing id
            $adapter->delete($destinationRelative);
            return (int)self::getByHash($sha)['media_id'];
        }

        // For images, compute dimensions
        $width = null; $height = null;
        if ($localPathForHash && self::isImage($localPathForHash)) {
            [$width, $height] = self::detectDimensions($localPathForHash);
        }

        // Create thumbnails (we download to local temp, generate thumbs and upload)
        try {
            if ($localPathForHash && self::isImage($localPathForHash)) {
                foreach (self::$defaultSizes as $sizeName => [$w, $h]) {
                    $thumbLocal = self::createThumbnailLocal($localPathForHash, $w, $h, $sizeName);
                    if ($thumbLocal && file_exists($thumbLocal)) {
                        $thumbRelative = $relativeFolder . '/' . $sizeName . '_' . basename($destinationRelative);
                        $adapter->put($thumbRelative, $thumbLocal);
                        @unlink($thumbLocal);
                    }
                }
            }
        } catch (\Throwable $e) {
            error_log('MediaManager::saveUploadedFile - thumbnail error: ' . $e->getMessage());
        }

        // Insert metadata into DB
        $insertOptions = [
            'file_name' => $fileName,
            'file_path' => $relativeFolder,
            'mime_type' => $file['type'] ?? self::detectMime($localPathForHash ?? ''),
            'file_size' => (int)($file['size'] ?? 0),
            'uploaded_by' => $options['uploaded_by'] ?? null,
            'alt_text' => $options['alt'] ?? ($options['alt_text'] ?? null),
            'title' => $options['title'] ?? null,
            'caption' => $options['caption'] ?? null,
            'hash' => $sha,
            'width' => $width,
            'height' => $height,
            'created_at' => $options['created_at'] ?? self::nowIso(),
        ];

        $mediaId = self::insertFileRecord($insertOptions);

        return (int)$mediaId;
    }

    /**
     * Fetch media metadata by id (returns DB row) and include URLs via adapter.
     * @param int $mediaId
     * @return array
     */
    public static function getMedia(int $mediaId): array
    {
        try {
            $pdo = Database::getInstance()->getPDO();
            $stmt = $pdo->prepare("SELECT * FROM yt_media WHERE media_id = :id LIMIT 1");
            $stmt->execute([':id' => $mediaId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) return [];
            $adapter = self::getAdapter();
            $row['url'] = $adapter->getUrl(rtrim($row['file_path'], '/') . '/' . $row['file_name']);
            // build thumb urls
            $row['thumbs'] = [];
            foreach (self::$defaultSizes as $sizeName => $_) {
                $thumbPath = rtrim($row['file_path'], '/') . '/' . $sizeName . '_' . $row['file_name'];
                $row['thumbs'][$sizeName] = $adapter->getUrl($thumbPath);
            }
            return $row;
        } catch (\Throwable $e) {
            error_log('MediaManager::getMedia - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get DB row by hash (returns array or null)
     * @param string $hash
     * @return array|null
     */
    public static function getByHash(string $hash): ?array
    {
        try {
            $pdo = Database::getInstance()->getPDO();
            $stmt = $pdo->prepare("SELECT * FROM yt_media WHERE hash = :h LIMIT 1");
            $stmt->execute([':h' => $hash]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row !== false ? $row : null;
        } catch (\Throwable $e) {
            error_log('MediaManager::getByHash - ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Soft delete (mark status='trashed') or hard delete (delete files + DB row)
     * @param int $mediaId
     * @param bool $soft
     * @return bool
     */
    public static function delete(int $mediaId, bool $soft = true): bool
    {
        try {
            $pdo = Database::getInstance()->getPDO();
            $stmt = $pdo->prepare("SELECT * FROM yt_media WHERE media_id = :id LIMIT 1");
            $stmt->execute([':id' => $mediaId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) return false;

            if ($soft) {
                $u = $pdo->prepare("UPDATE yt_media SET status = 'trashed', updated_at = :u WHERE media_id = :id");
                $u->execute([':u' => self::nowIso(), ':id' => $mediaId]);
                self::logAudit($mediaId, 'soft_delete', null);
                return true;
            }

            // hard delete: remove files from adapter and delete DB row
            $adapter = self::getAdapter();
            $basePath = rtrim($row['file_path'], '/') . '/' . $row['file_name'];
            $adapter->delete($basePath);
            foreach (self::$defaultSizes as $sizeName => $_) {
                $adapter->delete(rtrim($row['file_path'], '/') . '/' . $sizeName . '_' . $row['file_name']);
            }

            $d = $pdo->prepare("DELETE FROM yt_media WHERE media_id = :id");
            $d->execute([':id' => $mediaId]);
            self::logAudit($mediaId, 'hard_delete', null);
            return true;
        } catch (\Throwable $e) {
            error_log('MediaManager::delete - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Restore a soft-deleted media item (status -> active).
     * @param int $mediaId
     * @return bool
     */
    public static function restore(int $mediaId): bool
    {
        try {
            $pdo = Database::getInstance()->getPDO();
            $u = $pdo->prepare("UPDATE yt_media SET status = 'active', updated_at = :u WHERE media_id = :id");
            $u->execute([':u' => self::nowIso(), ':id' => $mediaId]);
            self::logAudit($mediaId, 'restore', null);
            return true;
        } catch (\Throwable $e) {
            error_log('MediaManager::restore - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate a public or signed url for a media
     * Options:
     *  - size: null|'small'|'medium'|'large' (default null -> original)
     *  - download: bool (force Content-Disposition)
     *  - expires: int (seconds) for signed urls (adapter dependent)
     *
     * @param int $mediaId
     * @param array $options
     * @return string
     */
    public static function generateUrl(int $mediaId, array $options = []): string
    {
        $m = self::getMedia($mediaId);
        if (empty($m)) return '';
        $size = $options['size'] ?? null;
        $path = rtrim($m['file_path'], '/') . '/';
        $fileName = $m['file_name'];
        if ($size && isset(self::$defaultSizes[$size])) {
            $path .= $size . '_' . $fileName;
        } else {
            $path .= $fileName;
        }
        return self::getAdapter()->getUrl($path, $options);
    }

    /**
     * Validate file against rules. Returns true or ['error'=>message]
     * Rules:
     *  - allowed_mimes => array
     *  - max_size => int bytes
     *
     * @param array $file
     * @param array $rules
     * @return true|array
     */
    public static function validateFile(array $file, array $rules = []): mixed
    {
        $rules = array_merge(self::$defaultValidate, $rules);
        if (isset($file['error']) && $file['error'] !== UPLOAD_ERR_OK && $file['error'] !== 0) {
            return ['error' => 'Upload error code: ' . $file['error']];
        }
        if (isset($rules['max_size']) && isset($file['size']) && $file['size'] > (int)$rules['max_size']) {
            return ['error' => 'File exceeds maximum allowed size.'];
        }
        if (!empty($rules['allowed_mimes']) && !empty($file['type'])) {
            if (!in_array($file['type'], (array)$rules['allowed_mimes'], true)) {
                // check using finfo if mime detection mismatch
                $tmp = $file['tmp_name'] ?? null;
                if ($tmp && function_exists('finfo_open')) {
                    $f = finfo_open(FILEINFO_MIME_TYPE);
                    $detected = finfo_file($f, $tmp);
                    finfo_close($f);
                    if (!in_array($detected, (array)$rules['allowed_mimes'], true)) {
                        return ['error' => 'File type is not allowed. Detected: ' . $detected];
                    }
                } else {
                    return ['error' => 'File type is not allowed.'];
                }
            }
        }
        return true;
    }

    /**
     * Regenerate thumbnails for a given media id.
     * @param int $mediaId
     * @param array|null $sizes null => use self::$defaultSizes
     * @return bool
     */
    public static function regenerateThumbnails(int $mediaId, ?array $sizes = null): bool
    {
        $sizes = $sizes ?? self::$defaultSizes;
        $row = self::getMedia($mediaId);
        if (empty($row)) return false;
        $adapter = self::getAdapter();
        $relativeOriginal = rtrim($row['file_path'], '/') . '/' . $row['file_name'];
        $local = $adapter->getLocalPath($relativeOriginal);
        if (!$local || !file_exists($local)) {
            // try to download to temp
            $tmp = tempnam(sys_get_temp_dir(), 'mm_');
            if (!$adapter->downloadTo($relativeOriginal, $tmp)) return false;
            $local = $tmp;
        }
        foreach ($sizes as $name => [$w, $h]) {
            try {
                $thumbLocal = self::createThumbnailLocal($local, $w, $h, $name);
                if ($thumbLocal && file_exists($thumbLocal)) {
                    $destRel = rtrim($row['file_path'], '/') . '/' . $name . '_' . $row['file_name'];
                    $adapter->put($destRel, $thumbLocal);
                    @unlink($thumbLocal);
                }
            } catch (\Throwable $e) {
                error_log('MediaManager::regenerateThumbnails - ' . $e->getMessage());
            }
        }
        // cleanup downloaded temp if downloaded
        if (isset($tmp) && file_exists($tmp)) @unlink($tmp);
        self::logAudit($mediaId, 'regenerate_thumbs', null);
        return true;
    }

    /**
     * Optimize image: compress/convert to webp if requested, strip exif etc.
     * Options:
     * - quality: int (1-100)
     * - convert: 'webp'|'jpeg'|null
     * - inplace: bool
     *
     * @param int $mediaId
     * @param array $options
     * @return bool
     */
    public static function optimizeImage(int $mediaId, array $options = []): bool
    {
        $row = self::getMedia($mediaId);
        if (empty($row)) return false;
        $adapter = self::getAdapter();
        $rel = rtrim($row['file_path'], '/') . '/' . $row['file_name'];
        $local = $adapter->getLocalPath($rel);
        if (!$local || !file_exists($local)) {
            $tmp = tempnam(sys_get_temp_dir(), 'mmopt_');
            if (!$adapter->downloadTo($rel, $tmp)) return false;
            $local = $tmp;
            $cleanupLocal = true;
        } else {
            $cleanupLocal = false;
        }

        $quality = $options['quality'] ?? 85;
        $convert = $options['convert'] ?? null;

        try {
            // GD fallback (basic)
                $mime = $row['mime_type'] ?? self::detectMime($local);
                [$w, $h] = self::detectDimensions($local);
                $src = null;
                switch ($mime) {
                    case 'image/jpeg':
                    case 'image/pjpeg':
                        $src = imagecreatefromjpeg($local);
                        break;
                    case 'image/png':
                        $src = imagecreatefrompng($local);
                        break;
                    case 'image/gif':
                        $src = imagecreatefromgif($local);
                        break;
                    case 'image/webp':
                        if (function_exists('imagecreatefromwebp')) $src = imagecreatefromwebp($local);
                        break;
                }
                if ($src) {
                    // re-save to temp as jpeg or webp
                    $tmpOut = $local . '.opt';
                    if ($convert === 'webp' && function_exists('imagewebp')) {
                        imagewebp($src, $tmpOut, (int)$quality);
                    } else {
                        imagejpeg($src, $tmpOut, (int)$quality);
                    }
                    imagedestroy($src);
                    $adapter->put($rel, $tmpOut);
                    @unlink($tmpOut);
                } else {
                    // not an image we can optimize easily
                    if ($cleanupLocal) @unlink($local);
                    return false;
                }
        } catch (\Throwable $e) {
            error_log('MediaManager::optimizeImage - ' . $e->getMessage());
            if ($cleanupLocal) @unlink($local);
            return false;
        }

        if ($cleanupLocal) @unlink($local);

        self::logAudit($mediaId, 'optimize', json_encode($options));
        return true;
    }

    /**
     * Return HTML srcset string for the given media id and list of widths (e.g. [400,800])
     *
     * @param int $mediaId
     * @param array $sizes list of widths or keys (if strings map to defaultSizes, if ints treated as widths)
     * @return string
     */
    public static function getResponsiveSrcset(int $mediaId, array $sizes): string
    {
        $srcs = [];
        foreach ($sizes as $s) {
            if (is_string($s) && isset(self::$defaultSizes[$s])) {
                $url = self::generateUrl($mediaId, ['size' => $s]);
                $dim = self::$defaultSizes[$s][0];
                $srcs[] = "{$url} {$dim}w";
            } elseif (is_int($s)) {
                // Best-effort: look for matching file name pattern width_{w}_filename or serve original
                $url = self::generateUrl($mediaId, []);
                $srcs[] = "{$url} {$s}w";
            }
        }
        return implode(', ', $srcs);
    }

    /**
     * Update metadata fields: alt_text, title, caption, uploaded_by, tags (if present)
     * @param int $mediaId
     * @param array $data
     * @return bool
     */
    public static function updateMetadata(int $mediaId, array $data): bool
    {
        $allowed = ['alt_text', 'title', 'caption', 'uploaded_by', 'reference_count'];
        $set = [];
        $params = [':id' => $mediaId];
        foreach ($allowed as $col) {
            if (array_key_exists($col, $data)) {
                $set[] = "{$col} = :{$col}";
                $params[":{$col}"] = $data[$col];
            }
        }
        if (empty($set)) return false;
        $sql = "UPDATE yt_media SET " . implode(', ', $set) . ", updated_at = :u WHERE media_id = :id";
        $params[':u'] = self::nowIso();
        try {
            $pdo = Database::getInstance()->getPDO();
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            self::logAudit($mediaId, 'update_metadata', json_encode($data));
            return true;
        } catch (\Throwable $e) {
            error_log('MediaManager::updateMetadata - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Search media by criteria (filename, mime, date range, status)
     * Returns array of rows
     *
     * Criteria example:
     * [
     *   'q' => 'cat', // search filename
     *   'mime' => 'image/jpeg',
     *   'status' => 'active',
     *   'date_from' => '2025-01-01',
     *   'date_to' => '2025-12-31'
     * ]
     *
     * @param array $criteria
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public static function search(array $criteria = [], int $limit = 50, int $offset = 0): array
    {
        $where = [];
        $params = [];
        if (!empty($criteria['q'])) {
            $where[] = "(file_name LIKE :q OR title LIKE :q OR alt_text LIKE :q)";
            $params[':q'] = '%' . $criteria['q'] . '%';
        }
        if (!empty($criteria['mime'])) {
            $where[] = "mime_type = :mime";
            $params[':mime'] = $criteria['mime'];
        }
        if (!empty($criteria['status'])) {
            $where[] = "status = :status";
            $params[':status'] = $criteria['status'];
        }
        if (!empty($criteria['date_from'])) {
            $where[] = "created_at >= :df";
            $params[':df'] = $criteria['date_from'];
        }
        if (!empty($criteria['date_to'])) {
            $where[] = "created_at <= :dt";
            $params[':dt'] = $criteria['date_to'];
        }
        $sql = "SELECT * FROM yt_media";
        if (!empty($where)) $sql .= " WHERE " . implode(' AND ', $where);
        $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        try {
            $pdo = Database::getInstance()->getPDO();
            $stmt = $pdo->prepare($sql);
            foreach ($params as $k => $v) $stmt->bindValue($k, $v);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            error_log('MediaManager::search - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Bulk import all files in a folder into media storage & DB
     * @param string $folderPath local filesystem folder
     * @param array $options
     * @return array summary ['imported' => [...], 'errors' => [...]]
     */
    public static function bulkImportFromFolder(string $folderPath, array $options = []): array
    {
        $result = ['imported' => [], 'errors' => []];
        if (!is_dir($folderPath)) {
            $result['errors'][] = "Folder not found: {$folderPath}";
            return $result;
        }
        $files = scandir($folderPath);
        foreach ($files as $f) {
            if ($f === '.' || $f === '..') continue;
            $path = rtrim($folderPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $f;
            if (!is_file($path)) continue;
            $fileArr = [
                'name' => $f,
                'tmp_name' => $path,
                'type' => mime_content_type($path) ?: null,
                'size' => filesize($path),
                'error' => 0,
            ];
            try {
                $id = self::saveUploadedFile($fileArr, $options);
                $result['imported'][] = $id;
            } catch (FormException $e) {
                $result['errors'][] = "{$f}: " . $e->getMessage();
            } catch (\Throwable $e) {
                $result['errors'][] = "{$f}: " . $e->getMessage();
            }
        }
        return $result;
    }

    /**
     * Fetch remote image by URL, validate and save it.
     * Options: same as saveUploadedFile plus 'timeout' and 'max_size'
     * Returns media_id
     */
    public static function fetchRemoteImage(string $url, array $options = []): int
    {
        // Basic validation of URL
        $parts = parse_url($url);
        if (empty($parts['scheme']) || !in_array($parts['scheme'], ['http', 'https'])) {
            throw new FormException('Invalid URL scheme.');
        }

        $timeout = $options['timeout'] ?? 10;
        $tmp = tempnam(sys_get_temp_dir(), 'mmfetch_');
        $ch = curl_init($url);
        $fp = fopen($tmp, 'w+b');
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        fclose($fp);
        if ($err) {
            @unlink($tmp);
            throw new FormException('Failed to download remote file: ' . $err);
        }
        $mime = mime_content_type($tmp);
        $fileArr = [
            'name' => basename(parse_url($url, PHP_URL_PATH) ?: 'remote'),
            'tmp_name' => $tmp,
            'type' => $mime,
            'size' => filesize($tmp),
            'error' => 0,
        ];
        try {
            $mediaId = self::saveUploadedFile($fileArr, $options);
            @unlink($tmp);
            return $mediaId;
        } catch (\Throwable $e) {
            @unlink($tmp);
            throw $e;
        }
    }

    /**
     * Migrate assets to target adapter (returns summary)
     * @param StorageAdapterInterface $targetAdapter
     * @param array $criteria optional selection
     * @return array summary ['migrated'=>[], 'failed'=>[]]
     */
    public static function migrateToAdapter(StorageAdapterInterface $targetAdapter, array $criteria = []): array
    {
        $summary = ['migrated' => [], 'failed' => []];
        $rows = self::search($criteria, 10000, 0);
        $src = self::getAdapter();
        foreach ($rows as $r) {
            $rel = rtrim($r['file_path'], '/') . '/' . $r['file_name'];
            $local = $src->getLocalPath($rel);
            $tmpDownloaded = false;
            if (!$local || !file_exists($local)) {
                $tmp = tempnam(sys_get_temp_dir(), 'mm_mig_');
                if (!$src->downloadTo($rel, $tmp)) {
                    $summary['failed'][] = $r['media_id'];
                    continue;
                }
                $local = $tmp;
                $tmpDownloaded = true;
            }
            $destRel = $rel; // preserve same path
            try {
                if (!$targetAdapter->exists(dirname($destRel))) {
                    $targetAdapter->makeDir(dirname($destRel));
                }
                $ok = $targetAdapter->put($destRel, $local);
                if ($ok) {
                    // update DB path if target adapter uses different base; for now we assume same relative path
                    $summary['migrated'][] = $r['media_id'];
                } else {
                    $summary['failed'][] = $r['media_id'];
                }
            } catch (\Throwable $e) {
                $summary['failed'][] = $r['media_id'];
            }
            if ($tmpDownloaded && file_exists($local)) @unlink($local);
        }
        return $summary;
    }

    /**
     * Increment / decrement reference count helpers for safe purge operations
     */
    public static function incrementReference(int $mediaId): void
    {
        try {
            $pdo = Database::getInstance()->getPDO();
            $pdo->prepare("UPDATE yt_media SET reference_count = COALESCE(reference_count,0)+1 WHERE media_id = :id")
                ->execute([':id' => $mediaId]);
        } catch (\Throwable $e) {
            error_log('MediaManager::incrementReference - ' . $e->getMessage());
        }
    }

    public static function decrementReference(int $mediaId): void
    {
        try {
            $pdo = Database::getInstance()->getPDO();
            $pdo->prepare("UPDATE yt_media SET reference_count = GREATEST(COALESCE(reference_count,1)-1,0) WHERE media_id = :id")
                ->execute([':id' => $mediaId]);
        } catch (\Throwable $e) {
            error_log('MediaManager::decrementReference - ' . $e->getMessage());
        }
    }

    /**
     * Purge unused media: criteria ['older_than_days'=>int, 'dry_run'=>bool]
     * Returns summary
     */
    public static function purgeUnused(array $options = []): array
    {
        $days = $options['older_than_days'] ?? 30;
        $dry = $options['dry_run'] ?? true;
        $cut = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        $pdo = Database::getInstance()->getPDO();
        $stmt = $pdo->prepare("SELECT media_id FROM yt_media WHERE COALESCE(reference_count,0) = 0 AND created_at < :cut AND status != 'trashed'");
        $stmt->execute([':cut' => $cut]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $ids = array_map(fn($r) => (int)$r['media_id'], $rows);
        $result = ['to_purge' => $ids, 'deleted' => [], 'failed' => []];
        if ($dry) return $result;
        foreach ($ids as $id) {
            if (self::delete($id, false)) $result['deleted'][] = $id; else $result['failed'][] = $id;
        }
        return $result;
    }

    /**
     * Replace media content for an existing media id with a new file
     * Keeps media_id but updates file and derived thumbnails.
     *
     * @param int $oldMediaId
     * @param array $newFile $_FILES like array
     * @return int mediaId
     * @throws FormException
     */
    public static function replaceMedia(int $oldMediaId, array $newFile): int
    {
        $row = self::getMedia($oldMediaId);
        if (empty($row)) {
            throw new FormException('Media not found.');
        }
        // store new file to same folder (and possibly same name or unique)
        $options = [
            'base_path' => $row['file_path'],
            'file_name' => $row['file_name'],
            'uploaded_by' => $row['uploaded_by'] ?? null
        ];
        // Save as temp to get new file saved (this will create a new DB row normally)
        $newId = self::saveUploadedFile($newFile, $options);
        // Now remove old file content but keep row id: we'll copy new file content into old row file path and delete the new row
        $adapter = self::getAdapter();
        $newRow = self::getMedia($newId);
        $oldRel = rtrim($row['file_path'], '/') . '/' . $row['file_name'];
        $newRel = rtrim($newRow['file_path'], '/') . '/' . $newRow['file_name'];

        // copy new to old (adapter-level)
        $tempLocal = $adapter->getLocalPath($newRel);
        if (!$tempLocal || !file_exists($tempLocal)) {
            $tmp = tempnam(sys_get_temp_dir(), 'mmrep_');
            if (!$adapter->downloadTo($newRel, $tmp)) {
                throw new FormException('Failed to copy new media for replacement.');
            }
            $tempLocal = $tmp;
            $cleanup = true;
        } else $cleanup = false;

        // put into old location
        $adapter->put($oldRel, $tempLocal);

        // put thumbs
        foreach (self::$defaultSizes as $k => $_) {
            $newThumbRel = rtrim($newRow['file_path'], '/') . '/' . $k . '_' . $newRow['file_name'];
            $tmpThumb = $adapter->getLocalPath($newThumbRel);
            if (!$tmpThumb || !file_exists($tmpThumb)) {
                $tmp2 = tempnam(sys_get_temp_dir(), 'mmrepthumb_');
                $adapter->downloadTo($newThumbRel, $tmp2);
                $tmpThumb = $tmp2;
                $cleanupThumb = true;
            } else {
                $cleanupThumb = false;
            }
            $oldThumbRel = rtrim($row['file_path'], '/') . '/' . $k . '_' . $row['file_name'];
            if ($tmpThumb && file_exists($tmpThumb)) {
                $adapter->put($oldThumbRel, $tmpThumb);
            }
            if ($cleanupThumb && isset($tmp2) && file_exists($tmp2)) @unlink($tmp2);
        }

        // cleanup
        if ($cleanup && isset($tmp) && file_exists($tmp)) @unlink($tmp);

        // update DB metadata for old row using newRow values (hash, size, mime, width/height)
        $meta = [
            'file_size' => $newRow['file_size'] ?? null,
            'mime_type' => $newRow['mime_type'] ?? null,
            'hash' => $newRow['hash'] ?? null,
            'width' => $newRow['width'] ?? null,
            'height' => $newRow['height'] ?? null,
        ];
        self::updateMetadata($oldMediaId, $meta);

        // remove the new row completely
        self::delete($newId, false);

        self::logAudit($oldMediaId, 'replace', json_encode(['replaced_with' => $newId]));
        return $oldMediaId;
    }

    /**
     * Returns thumbnail relative path for a given size (not URL)
     * @param int $mediaId
     * @param string $size
     * @return string|null
     */
    public static function thumbPath(int $mediaId, string $size): ?string
    {
        $row = self::getMedia($mediaId);
        if (empty($row)) return null;
        if (!isset(self::$defaultSizes[$size])) return null;
        return rtrim($row['file_path'], '/') . '/' . $size . '_' . $row['file_name'];
    }

    /**
     * Stream file to current HTTP response (with headers).
     * Note: This function will exit after streaming.
     *
     * @param int $mediaId
     * @param array $options (force_download => bool)
     */
    public static function streamFile(int $mediaId, array $options = []): void
    {
        $row = self::getMedia($mediaId);
        if (empty($row)) {
            http_response_code(404);
            exit;
        }
        $rel = rtrim($row['file_path'], '/') . '/' . $row['file_name'];
        $adapter = self::getAdapter();
        $local = $adapter->getLocalPath($rel);
        $download = $options['force_download'] ?? false;
        if ($local && file_exists($local)) {
            header('Content-Type: ' . ($row['mime_type'] ?? 'application/octet-stream'));
            header('Content-Length: ' . filesize($local));
            if ($download) header('Content-Disposition: attachment; filename="' . basename($row['file_name']) . '"');
            readfile($local);
            exit;
        }
        // else stream through adapter
        $temp = tempnam(sys_get_temp_dir(), 'mmstream_');
        if (!$adapter->downloadTo($rel, $temp)) {
            http_response_code(500);
            exit;
        }
        header('Content-Type: ' . ($row['mime_type'] ?? 'application/octet-stream'));
        header('Content-Length: ' . filesize($temp));
        if ($download) header('Content-Disposition: attachment; filename="' . basename($row['file_name']) . '"');
        readfile($temp);
        @unlink($temp);
        exit;
    }

    /**
     * Rotate an image by degrees and regenerate thumbnails
     * @param int $mediaId
     * @param int $degrees (clockwise)
     * @return bool
     */
    public static function rotateImage(int $mediaId, int $degrees): bool
    {
        $row = self::getMedia($mediaId);
        if (empty($row)) return false;
        $rel = rtrim($row['file_path'], '/') . '/' . $row['file_name'];
        $adapter = self::getAdapter();
        $local = $adapter->getLocalPath($rel);
        $tempDownloaded = false;
        if (!$local || !file_exists($local)) {
            $tmp = tempnam(sys_get_temp_dir(), 'mmrot_');
            if (!$adapter->downloadTo($rel, $tmp)) return false;
            $local = $tmp;
            $tempDownloaded = true;
        }
        try {
                // GD fallback
                $mime = $row['mime_type'] ?? self::detectMime($local);
                $src = null;
                switch ($mime) {
                    case 'image/jpeg': $src = imagecreatefromjpeg($local); break;
                    case 'image/png': $src = imagecreatefrompng($local); break;
                    case 'image/gif': $src = imagecreatefromgif($local); break;
                    case 'image/webp': if (function_exists('imagecreatefromwebp')) $src = imagecreatefromwebp($local); break;
                }
                if (!$src) throw new \Exception('Unsupported image for rotate');
                // rotate
                $bg = imagecolorallocatealpha($src, 255, 255, 255, 127);
                $rot = imagerotate($src, 360 - ($degrees % 360), $bg);
                $tmpOut = $local . '.rot';
                imagejpeg($rot, $tmpOut, 90);
                imagedestroy($src); imagedestroy($rot);
                $adapter->put($rel, $tmpOut);
                @unlink($tmpOut);
            // regenerate thumbs
            self::regenerateThumbnails($mediaId);
            if ($tempDownloaded && isset($tmp) && file_exists($tmp)) @unlink($tmp);
            self::logAudit($mediaId, 'rotate', json_encode(['degrees' => $degrees]));
            return true;
        } catch (\Throwable $e) {
            error_log('MediaManager::rotateImage - ' . $e->getMessage());
            if ($tempDownloaded && isset($tmp) && file_exists($tmp)) @unlink($tmp);
            return false;
        }
    }

    /**
     * Get dimensions for an image media
     * @param int $mediaId
     * @return array [width,height] or [null,null]
     */
    public static function getDimensions(int $mediaId): array
    {
        $row = self::getMedia($mediaId);
        if (empty($row)) return [null, null];
        return [(int)$row['width'] ?: null, (int)$row['height'] ?: null];
    }

    /**
     * Return audit trail for media_id (reads yt_media_audit if exists)
     * @param int $mediaId
     * @return array
     */
    public static function auditTrail(int $mediaId): array
    {
        try {
            $pdo = Database::getInstance()->getPDO();
            $stmt = $pdo->prepare("SELECT * FROM yt_media_audit WHERE media_id = :id ORDER BY created_at DESC");
            $stmt->execute([':id' => $mediaId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /* --------------------
     * Internal helpers
     * -------------------- */

    protected static function insertFileRecord(array $data): int
    {
        try {
            $pdo = Database::getInstance()->getPDO();
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            // Build columns
            $columns = [
                'file_name','file_path','mime_type','file_size','uploaded_by','alt_text','title','caption','hash','width','height','created_at'
            ];
            $cols = [];
            $vals = [];
            $params = [];
            foreach ($columns as $c) {
                if (isset($data[$c])) {
                    $cols[] = $c;
                    $vals[] = ':' . $c;
                    $params[':' . $c] = $data[$c];
                }
            }
            // default status active
            $cols[] = 'status'; $vals[] = ':status'; $params[':status'] = $data['status'] ?? 'active';
            // reference_count default 0
            $cols[] = 'reference_count'; $vals[] = ':reference_count'; $params[':reference_count'] = $data['reference_count'] ?? 0;
            // created_at provided already
            $sql = "INSERT INTO yt_media (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $vals) . ")";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $id = (int)$pdo->lastInsertId();
            self::logAudit($id, 'insert', json_encode($data));
            return $id;
        } catch (PDOException $e) {
            error_log('MediaManager::insertFileRecord - ' . $e->getMessage());
            throw new FormException('Database error: ' . $e->getMessage());
        }
    }

    protected static function detectMime(string $path): ?string
    {
        if (empty($path) || !file_exists($path)) return null;
        if (function_exists('finfo_open')) {
            $f = finfo_open(FILEINFO_MIME_TYPE);
            $m = finfo_file($f, $path);
            finfo_close($f);
            return $m;
        }
        return mime_content_type($path) ?: null;
    }

    protected static function detectDimensions(string $path): array
    {
        if (!file_exists($path)) return [null, null];
        $i = @getimagesize($path);
        if (!$i) return [null, null];
        return [$i[0], $i[1]];
    }

    protected static function isImage(string $path): bool
    {
        $m = self::detectMime($path);
        return $m && str_starts_with($m, 'image/');
    }

    protected static function appendNumberToFileName(string $fileName, int $index): string
    {
        $p = pathinfo($fileName);
        $name = $p['filename'];
        $ext = isset($p['extension']) && $p['extension'] !== '' ? '.' . $p['extension'] : '';
        return $name . '-' . $index . $ext;
    }

    protected static function sanitizeFileName(string $fileName): string
    {
        $fileName = trim($fileName);
        $fileName = preg_replace('/\s+/', '_', $fileName);
        $fileName = preg_replace('/[^A-Za-z0-9_\.\-]/', '', $fileName);
        return $fileName ?: 'file';
    }

    protected static function nowIso(): string
    {
        return date('Y-m-d H:i:s');
    }

    protected static function createThumbnailLocal(string $sourcePath, int $width, int $height, string $name = 'thumb'): ?string
    {
        if (!file_exists($sourcePath)) return null;
        [$origW, $origH] = self::detectDimensions($sourcePath);
        if (!$origW || !$origH) return null;
        $ratio = $origW / $origH;
        if ($width / $height > $ratio) {
            $width = (int)ceil($height * $ratio);
        } else {
            $height = (int)ceil($width / $ratio);
        }
        $tmp = tempnam(sys_get_temp_dir(), 'mmthumb_') . '.' . pathinfo($sourcePath, PATHINFO_EXTENSION);
        try {
            // GD fallback
            $mime = self::detectMime($sourcePath);
            switch ($mime) {
                case 'image/jpeg':
                case 'image/pjpeg':
                    $srcImg = imagecreatefromjpeg($sourcePath); break;
                case 'image/png':
                    $srcImg = imagecreatefrompng($sourcePath); break;
                case 'image/gif':
                    $srcImg = imagecreatefromgif($sourcePath); break;
                case 'image/webp':
                    if (function_exists('imagecreatefromwebp')) $srcImg = imagecreatefromwebp($sourcePath); else $srcImg = null; break;
                default:
                    $srcImg = null;
            }
            if (!$srcImg) return null;
            $thumb = imagecreatetruecolor($width, $height);
            // preserve alpha
            if (in_array($mime, ['image/png', 'image/webp'])) {
                imagealphablending($thumb, false);
                imagesavealpha($thumb, true);
                $transparent = imagecolorallocatealpha($thumb, 255, 255, 255, 127);
                imagefilledrectangle($thumb, 0, 0, $width, $height, $transparent);
            }
            imagecopyresampled($thumb, $srcImg, 0, 0, 0, 0, $width, $height, $origW, $origH);
            imagejpeg($thumb, $tmp, 90);
            imagedestroy($thumb);
            imagedestroy($srcImg);
            return $tmp;
        } catch (\Throwable $e) {
            error_log('MediaManager::createThumbnailLocal - ' . $e->getMessage());
            return null;
        }
    }

    protected static function logAudit(int $mediaId, string $action, $detail = null): void
    {
        try {
            $pdo = Database::getInstance()->getPDO();
            // attempt to insert into yt_media_audit if table exists
            $stmt = $pdo->prepare("INSERT INTO yt_media_audit (media_id, action, detail, created_at) VALUES (:id, :action, :detail, :created_at)");
            $stmt->execute([':id' => $mediaId, ':action' => $action, ':detail' => $detail, ':created_at' => self::nowIso()]);
        } catch (\Throwable $e) {
            // audit table might not exist; that's fine - don't break main flow
        }
    }
}

/* =========================
 * Storage Adapter Interface and Local Adapter (default)
 * ========================= */

/**
 * StorageAdapterInterface
 * Implementors must operate using relative paths; adapter may map them to local filesystem or cloud.
 */
interface StorageAdapterInterface
{
    /**
     * Put a file into storage.
     * @param string $relativePath
     * @param string $localSourcePath Path to a local file to upload (or in-memory stream path)
     * @return bool
     */
    public function put(string $relativePath, string $localSourcePath): bool;

    /**
     * Delete a file at relative path
     */
    public function delete(string $relativePath): bool;

    /**
     * Does a file/dir exist?
     */
    public function exists(string $relativePath): bool;

    /**
     * Make directory (recursive)
     */
    public function makeDir(string $relativePath): bool;

    /**
     * Return a public or signed url for the relative path
     */
    public function getUrl(string $relativePath, array $options = []): string;

    /**
     * For local adapter: return a local absolute path if available, else null
     */
    public function getLocalPath(string $relativePath): ?string;

    /**
     * Download remote storage file to a local path
     */
    public function downloadTo(string $relativePath, string $localDest): bool;
}

/**
 * LocalFilesystemAdapter - stores files under a configured base dir (default ./public)
 */
class LocalFilesystemAdapter implements StorageAdapterInterface
{
    protected string $baseDir;
    protected string $baseUrl; // public accessible URL prefix for getUrl

    public function __construct(string $baseDir = null, string $baseUrl = null)
    {
        $this->baseDir = rtrim($baseDir ?? (getcwd() . DIRECTORY_SEPARATOR . 'public'), DIRECTORY_SEPARATOR);
        $this->baseUrl = rtrim($baseUrl ?? '/', '/');
        if (!is_dir($this->baseDir)) {
            @mkdir($this->baseDir, 0777, true);
        }
    }

    protected function absolute(string $relative): string
    {
        $relative = ltrim($relative, '/');
        return $this->baseDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    }

    public function put(string $relativePath, string $localSourcePath): bool
    {
        $abs = $this->absolute($relativePath);
        $dir = dirname($abs);
        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0777, true) && !is_dir($dir)) return false;
        }
        // If $localSourcePath is path to actual file - copy, else try to read stream
        if (file_exists($localSourcePath)) {
            return (bool)@copy($localSourcePath, $abs);
        }
        // attempt to read contents
        $contents = @file_get_contents($localSourcePath);
        if ($contents === false) return false;
        return file_put_contents($abs, $contents) !== false;
    }

    public function delete(string $relativePath): bool
    {
        $abs = $this->absolute($relativePath);
        if (file_exists($abs)) {
            return @unlink($abs);
        }
        return true;
    }

    public function exists(string $relativePath): bool
    {
        $abs = $this->absolute($relativePath);
        return file_exists($abs);
    }

    public function makeDir(string $relativePath): bool
    {
        $abs = $this->absolute($relativePath);
        if (!is_dir($abs)) {
            return @mkdir($abs, 0777, true);
        }
        return true;
    }

    public function getUrl(string $relativePath, array $options = []): string
    {
        $relativePath = ltrim($relativePath, '/');
        // basic: return baseUrl + relativePath
        // for download/signed options, server must implement signed urls if needed
        return rtrim($this->baseUrl, '/') . '/' . str_replace('//', '/', $relativePath);
    }

    public function getLocalPath(string $relativePath): ?string
    {
        $abs = $this->absolute($relativePath);
        return file_exists($abs) ? $abs : null;
    }

    public function downloadTo(string $relativePath, string $localDest): bool
    {
        $abs = $this->absolute($relativePath);
        if (!file_exists($abs)) return false;
        return copy($abs, $localDest);
    }
}
