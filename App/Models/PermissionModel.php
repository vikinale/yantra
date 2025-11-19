<?php
namespace Models;
use System\Database\Model as BaseModel;

class PermissionModel extends BaseModel
{
    protected $table = 'yt_permissions';
    protected $primaryKey = 'perm_id';

    public function __construct(){ parent::__construct($this->table, $this->primaryKey); }

    public function getBySlug(string $slug): ?array { $r = $this->query()->where('slug','=',trim($slug))->getResult(\PDO::FETCH_ASSOC); return $r?:null; }

    public function createPermission(string $name, string $slug, ?string $desc = null): int { return (int)$this->insert(['name'=>$name,'slug'=>$slug,'description'=>$desc]); }
}
