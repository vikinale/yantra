<?php
namespace Models;
use System\Database\Model as BaseModel;

class SiteMenuModel extends BaseModel
{
    protected $table = 'yt_site_menus';
    protected $primaryKey = 'menu_id';

    public function __construct(){ parent::__construct($this->table, $this->primaryKey); }

    public function createMenu(array $data): int { return (int)$this->insert($data); }

    public function getBySlug(string $slug): ?array { $r = $this->query()->where('slug','=',trim($slug))->getResult(\PDO::FETCH_ASSOC); return $r?:null; }
}
