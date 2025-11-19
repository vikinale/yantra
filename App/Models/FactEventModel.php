<?php
namespace Models;
use System\Database\Model as BaseModel;

class FactEventModel extends BaseModel
{
    protected $table = 'fact_event';
    protected $primaryKey = 'event_id';

    public function __construct(){ parent::__construct($this->table, $this->primaryKey); }

    public function insertEvent(array $data): int { return (int)$this->insert($data); }

    public function bulkInsert(array $rows): int { return (int)$this->batchInsert($rows); }

    public function queryByTypeAndDate(string $type, string $from, string $to): array {
        $r = $this->query()->where('event_type','=',$type)->where('event_dt','>=',$from)->where('event_dt','<=',$to)->executeQuery()->fetchAll(\PDO::FETCH_ASSOC);
        return $r?:[];
    }
}
