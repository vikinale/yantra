<?php
namespace Models;
use System\Database\Model as BaseModel;

class UserRoleMapModel extends BaseModel
{
    protected $table = 'yt_user_role_map';
    protected $primaryKey = 'id';

    public function __construct(){ parent::__construct($this->table, $this->primaryKey); }

    public function assignRole(int $user_id, int $role_id): int { return (int)$this->insert(['user_id'=>$user_id,'role_id'=>$role_id]); }

    public function removeRole(int $user_id, int $role_id): int { return $this->query('delete')->where('user_id','=',$user_id)->where('role_id','=',$role_id)->executeQuery()->rowCount(); }

    public function getRoles(int $user_id): array { $r = $this->query()->where('user_id','=',$user_id)->executeQuery()->fetchAll(\PDO::FETCH_ASSOC); return $r?:[]; }
}
