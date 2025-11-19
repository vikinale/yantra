<?php
namespace Models;
use System\Database\Model as BaseModel;

class PageModel extends BaseModel
{
    protected $table = 'yt_pages';
    protected $primaryKey = 'page_id';

    public function __construct(){ parent::__construct($this->table, $this->primaryKey); }

    public function getBySlug(string $slug): ?array { $r = $this->query()->where('slug','=',trim($slug))->getResult(\PDO::FETCH_ASSOC); return $r?:null; }

    public function createPage(array $data): int { return (int)$this->insert($data); }

    public function updatePage(int $id, array $data): int { return $this->query('update')->where('page_id','=',$id)->data($data)->executeQuery()->rowCount(); }
}
