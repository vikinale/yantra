<?php
// App/Models/UserModel.php
namespace Models;

use Exception;
use System\Exceptions\FieldException;
use System\Database\Model as BaseModel;

class UserModel extends BaseModel
{
    protected $table = 'yt_users';
    protected $primaryKey = 'user_id';

    public function __construct()
    {
        parent::__construct($this->table, $this->primaryKey);
    }

    /**
     * Create a new user.
     *
     * Required: user_name, user_email, user_pass
     * Optional: user_mobile, nickname, display_name, full_name, status
     *
     * @param array $data
     * @param bool $hashPassword
     * @return int inserted user_id or 0
     * @throws Exception
     */
    public function createUser(array $data, bool $hashPassword = true): int
    {
        if (empty($data['user_name']) || empty($data['user_email']) || empty($data['user_pass'])) {
            throw new FieldException('user_name, user_email and user_pass are required');
        }

        $payload = [
            'user_name'    => trim($data['user_name']),
            'user_email'   => trim($data['user_email']),
            'user_mobile'  => $data['user_mobile'] ?? null,
            'nickname'     => $data['nickname'] ?? null,
            'display_name' => $data['display_name'] ?? null,
            'full_name'    => $data['full_name'] ?? null,
            'status'       => $data['status'] ?? 'active',
        ];

        $pass = $data['user_pass'];
        if ($hashPassword) {
            $pass = password_hash($pass, PASSWORD_DEFAULT);
        }
        $payload['user_pass'] = $pass;

        return (int) $this->insert($payload);
    }

    /**
     * Get user by primary id as associative array or null
     *
     * @param int $id
     * @return array|null
     * @throws Exception
     */
    public function getById(int $id): ?array
    {
        $res = $this->get($id);
        if ($res === false) return null;

        // Base get() may return object or associative array; normalize to array
        if (is_object($res)) {
            return (array)$res;
        }
        return (array)$res;
    }

    /**
     * Get user by email
     *
     * @param string $email
     * @return array|null
     * @throws Exception
     */
    public function getByEmail(string $email): ?array
    {
        $row = $this->query()
            ->where('user_email', '=', trim($email))
            ->getResult(\PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Get user by username
     *
     * @param string $username
     * @return array|null
     * @throws Exception
     */
    public function getByUsername(string $username): ?array
    {
        $row = $this->query()
            ->where('user_name', '=', trim($username))
            ->getResult(\PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Update user by id. Hash password if provided and $hashPassword true.
     *
     * @param int $id
     * @param array $data
     * @param bool $hashPassword
     * @return int affected rows
     * @throws Exception
     */
    public function updateUser(int $id, array $data, bool $hashPassword = true): int
    {
        if (isset($data['user_pass']) && $hashPassword) {
            $data['user_pass'] = password_hash($data['user_pass'], PASSWORD_DEFAULT);
        }

        return $this->query('update')
            ->where($this->primaryKey, '=', $id)
            ->data($data)
            ->executeQuery()
            ->rowCount();
    }

    /**
     * Delete user by id (uses BaseModel::delete)
     *
     * @param int $id
     * @return int deleted rows
     * @throws Exception
     */
    public function deleteUser(int $id): int
    {
        return $this->delete($id);
    }

    /**
     * Authenticate user by email or username + plain password.
     * Returns user array (without password) or null on failure.
     *
     * @param string $identifier
     * @param string $password
     * @return array|null
     * @throws Exception
     */
    public function authenticate(string $identifier, string $password): ?array
    {
        $user = $this->getByEmail($identifier);
        if (!$user) $user = $this->getByUsername($identifier);
        if (!$user) return null;

        if (password_verify($password, $user['user_pass'])) {
            unset($user['user_pass']);
            return $user;
        }
        return null;
    }

    /**
     * Paginated list wrapper that calls the parent's getPage to avoid recursion.
     *
     * @param int $page
     * @param int $perPage
     * @param array $search
     * @param array $order
     * @return array
     * @throws Exception
     */
    public function getPage(int $page = 1, int $perPage = 25, array $search = [], array $order = []): array
    {
        // call parent's implementation (Model::getPage)
        return parent::getPage($page, $perPage, $search, $order);
    }
}
