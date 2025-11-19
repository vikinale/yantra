<?php
namespace Models;
use System\Database\Model as BaseModel;

class SiteMenuItemModel extends BaseModel
{
    protected $table = 'yt_site_menu_items';
    protected $primaryKey = 'item_id';

    public function __construct(){ parent::__construct($this->table, $this->primaryKey); }

    public function addItem(array $data): int { return (int)$this->insert($data); }

    public function getItemsForMenu(int $menu_id): array { $r = $this->query()->where('menu_id','=',$menu_id)->orderBy('sort_order','ASC')->executeQuery()->fetchAll(\PDO::FETCH_ASSOC); return $r?:[]; }

    public function removeItem(int $id): int { return $this->delete($id); }
}
