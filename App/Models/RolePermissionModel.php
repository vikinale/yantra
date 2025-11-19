<?php
namespace Models;
use System\Database\Model as BaseModel;

class RolePermissionModel extends BaseModel
{
    protected $table = 'yt_role_permissions';
    protected $primaryKey = 'id';

    public function __construct(){ parent::__construct($this->table, $this->primaryKey); }

    public function grant(int $role_id, int $perm_id): int { return (int)$this->insert(['role_id'=>$role_id,'perm_id'=>$perm_id]); }

    public function revoke(int $role_id, int $perm_id): int { return $this->query('delete')->where('role_id','=',$role_id)->where('perm_id','=',$perm_id)->executeQuery()->rowCount(); }
}
