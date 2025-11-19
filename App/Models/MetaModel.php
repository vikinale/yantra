<?php
// App/Models/MetaModel.php
namespace Models;

use Exception;
use System\FieldException;
use System\Database\Model as BaseModel;

/**
 * MetaModel
 *
 * Handles CRUD for table `yt_meta`
 *
 * Expected structure:
 *  - meta_id     INT AUTO_INCREMENT PRIMARY KEY
 *  - meta_type   VARCHAR(8)
 *  - record_id   INT
 *  - meta_key    VARCHAR(64)
 *  - meta_value  TEXT
 *
 * UNIQUE(meta_type, record_id, meta_key)
 */
class MetaModel extends BaseModel
{
    protected $maxTypeLen = 8;
    protected $maxKeyLen  = 64;

    public function __construct()
    {
        parent::__construct('yt_meta', 'meta_id');
    }

    /**
     * Fetch a meta record (assoc array) by type, record_id and key
     *
     * @param string $type
     * @param int $record_id
     * @param string $key
     * @return array|null
     * @throws FieldException
     * @throws Exception
     */
    public function get_meta(string $type, int $record_id, string $key): ?array
    {
        $type = $this->normalizeType($type);
        $key  = $this->normalizeKey($key);
        $rid  = $this->normalizeRecordId($record_id);

        $row = $this->query()
            ->where('meta_type', '=', $type)
            ->where('record_id', '=', $rid)
            ->where('meta_key', '=', $key)
            ->getResult(\PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Get only meta_value
     *
     * @param string $type
     * @param int $record_id
     * @param string $key
     * @param mixed $default
     * @return mixed
     * @throws FieldException
     * @throws Exception
     */
    public function get_meta_value(string $type, int $record_id, string $key, $default = null)
    {
        $row = $this->get_meta($type, $record_id, $key);
        return $row ? $row['meta_value'] : $default;
    }

    /**
     * Insert or update meta value (upsert)
     *
     * Requires UNIQUE(meta_type, record_id, meta_key)
     *
     * @param string $type
     * @param int $record_id
     * @param string $key
     * @param string|null $value
     * @return int affected rows (save)
     * @throws FieldException
     * @throws Exception
     */
    public function update_meta(string $type, int $record_id, string $key, ?string $value): int
    {
        $type = $this->normalizeType($type);
        $key  = $this->normalizeKey($key);
        $rid  = $this->normalizeRecordId($record_id);

        $data = [
            'meta_type'  => $type,
            'record_id'  => $rid,
            'meta_key'   => $key,
            'meta_value' => $value,
        ];

        return $this->save($data, ['meta_value', 'updated_at']);
    }

    /**
     * Delete a meta entry
     *
     * @param string $type
     * @param int $record_id
     * @param string $key
     * @return int deleted rows
     * @throws FieldException
     * @throws Exception
     */
    public function delete_meta(string $type, int $record_id, string $key): int
    {
        $type = $this->normalizeType($type);
        $key  = $this->normalizeKey($key);
        $rid  = $this->normalizeRecordId($record_id);

        return $this->query('delete')
            ->where('meta_type', '=', $type)
            ->where('record_id', '=', $rid)
            ->where('meta_key', '=', $key)
            ->executeQuery()
            ->rowCount();
    }

    /**
     * List meta for a type and record (paginated)
     *
     * @param string $type
     * @param int $record_id
     * @param int $limit
     * @param int $offset
     * @return array
     * @throws FieldException
     * @throws Exception
     */
    public function get_all_meta(string $type, int $record_id = 0, int $limit = 100, int $offset = 0): array
    {
        $type = $this->normalizeType($type);
        $rid  = $this->normalizeRecordId($record_id);

        $res = $this->query()
            ->where('meta_type', '=', $type)
            ->where('record_id', '=', $rid)
            ->orderBy('meta_id', 'DESC')
            ->limit($limit)
            ->offset($offset)
            ->executeQuery()
            ->fetchAll(\PDO::FETCH_ASSOC);

        return $res ?: [];
    }

    /**
     * Count meta rows for a given type and record
     *
     * @param string $type
     * @param int $record_id
     * @return int
     * @throws FieldException
     * @throws Exception
     */
    public function count_meta(string $type, int $record_id = 0): int
    {
        $type = $this->normalizeType($type);
        $rid  = $this->normalizeRecordId($record_id);

        return $this->query()
            ->where('meta_type', '=', $type)
            ->where('record_id', '=', $rid)
            ->count();
    }

    /* -----------------------
     * Helpers / validation
     * ----------------------*/

    protected function normalizeType(string $type): string
    {
        $type = trim($type);
        if ($type === '') {
            throw new FieldException('meta_type cannot be empty');
        }
        if (mb_strlen($type) > $this->maxTypeLen) {
            $type = mb_substr($type, 0, $this->maxTypeLen);
        }
        return $type;
    }

    protected function normalizeKey(string $key): string
    {
        $key = trim($key);
        if ($key === '') {
            throw new FieldException('meta_key cannot be empty');
        }
        if (mb_strlen($key) > $this->maxKeyLen) {
            $key = mb_substr($key, 0, $this->maxKeyLen);
        }
        return $key;
    }

    protected function normalizeRecordId(?int $record_id): int
    {
        return (int) ($record_id ?? 0);
    }
}
