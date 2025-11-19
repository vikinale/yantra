<?php
namespace Models;
use System\Database\Model as BaseModel;

class TermModel extends BaseModel
{
    protected $table = 'yt_terms';
    protected $primaryKey = 'term_id';

    public function __construct(){ parent::__construct($this->table, $this->primaryKey); }

    public function getBySlugTax(string $slug, string $taxonomy='category'): ?array { $r = $this->query()->where('slug','=',trim($slug))->where('taxonomy','=',trim($taxonomy))->getResult(\PDO::FETCH_ASSOC); return $r?:null; }

    public function createTerm(array $data): int { return (int)$this->insert($data); }
}
