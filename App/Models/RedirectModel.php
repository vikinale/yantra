<?php
namespace Models;
use System\Database\Model as BaseModel;

class RedirectModel extends BaseModel
{
    protected $table = 'yt_redirects';
    protected $primaryKey = 'id';

    public function __construct(){ parent::__construct($this->table, $this->primaryKey); }

    public function createRedirect(string $source, string $target, int $status_code = 301, bool $active = true): int {
        return (int)$this->insert(['source'=>$source,'target'=>$target,'status_code'=>$status_code,'active'=>$active?1:0]);
    }

    public function findActive(string $source): ?array { $r = $this->query()->where('source','=',trim($source))->where('active','=',1)->getResult(\PDO::FETCH_ASSOC); return $r?:null; }
}
