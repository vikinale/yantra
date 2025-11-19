<?php
namespace Models;
use System\Database\Model as BaseModel;

class UserRoleModel extends BaseModel
{
    protected $table = 'yt_user_roles';
    protected $primaryKey = 'role_id';

    public function __construct(){ parent::__construct($this->table, $this->primaryKey); }

    public function createRole(string $name, string $slug, ?string $desc=null): int { return (int)$this->insert(['name'=>$name,'slug'=>$slug,'description'=>$desc]); }

    public function getBySlug(string $slug): ?array { $r = $this->query()->where('slug','=',trim($slug))->getResult(\PDO::FETCH_ASSOC); return $r?:null; }
}
