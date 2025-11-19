<?php
// App/Models/UserMetaModel.php
namespace Models;

use Exception;
use System\FieldException;
use System\Database\Model as BaseModel;

class UserMetaModel extends BaseModel
{
    protected $table = 'yt_user_meta';
    protected $primaryKey = 'meta_id';

    public function __construct()
    {
        parent::__construct($this->table, $this->primaryKey);
    }

    /**
     * Get a meta record for a user by user_id + meta_key
     *
     * @param int $user_id
     * @param string $meta_key
     * @return array|null
     * @throws Exception
     */
    public function get_meta(int $user_id, string $meta_key): ?array
    {
        $res = $this->query()
            ->where('user_id', '=', (int)$user_id)
            ->where('meta_key', '=', trim($meta_key))
            ->getResult(\PDO::FETCH_ASSOC);

        return $res ?: null;
    }

    /**
     * Get only meta value
     *
     * @param int $user_id
     * @param string $meta_key
     * @param mixed $default
     * @return mixed
     * @throws Exception
     */
    public function get_meta_value(int $user_id, string $meta_key, $default = null)
    {
        $row = $this->get_meta($user_id, $meta_key);
        return $row ? $row['meta_value'] : $default;
    }

    /**
     * Insert or update user meta (upsert). Requires UNIQUE(user_id, meta_key)
     *
     * @param int $user_id
     * @param string $meta_key
     * @param string|null $meta_value
     * @return int affected rows (save result)
     * @throws Exception
     */
    public function update_meta(int $user_id, string $meta_key, ?string $meta_value): int
    {
        $user_id = (int)$user_id;
        $meta_key = trim($meta_key);
        if ($meta_key === '') throw new FieldException('meta_key cannot be empty');

        $data = [
            'user_id'    => $user_id,
            'meta_key'   => $meta_key,
            'meta_value' => $meta_value,
        ];

        return $this->save($data, ['meta_value']);
    }

    /**
     * Delete meta by user_id + key
     *
     * @param int $user_id
     * @param string $meta_key
     * @return int deleted rows
     * @throws Exception
     */
    public function delete_meta(int $user_id, string $meta_key): int
    {
        return $this->query('delete')
            ->where('user_id', '=', (int)$user_id)
            ->where('meta_key', '=', trim($meta_key))
            ->executeQuery()
            ->rowCount();
    }

    /**
     * Get all meta for a user
     *
     * @param int $user_id
     * @return array
     * @throws Exception
     */
    public function get_all_meta(int $user_id): array
    {
        $res = $this->query()
            ->where('user_id', '=', (int)$user_id)
            ->orderBy('meta_id', 'ASC')
            ->executeQuery()
            ->fetchAll(\PDO::FETCH_ASSOC);

        return $res ?: [];
    }

    /**
     * Delete all meta for a user (bulk)
     *
     * @param int $user_id
     * @return int deleted rows
     * @throws Exception
     */
    public function delete_all_meta(int $user_id): int
    {
        return $this->query('delete')
            ->where('user_id', '=', (int)$user_id)
            ->executeQuery()
            ->rowCount();
    }
}
