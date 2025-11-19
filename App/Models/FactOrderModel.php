<?php
namespace Models;
use System\Database\Model as BaseModel;

class FactOrderModel extends BaseModel
{
    protected $table = 'fact_order';
    protected $primaryKey = 'order_id';

    public function __construct(){ parent::__construct($this->table, $this->primaryKey); }

    public function insertOrder(array $data): int { return (int)$this->insert($data); }

    public function getOrdersByDate(string $from, string $to): array {
        $r = $this->query()->where('order_dt','>=',$from)->where('order_dt','<=',$to)->executeQuery()->fetchAll(\PDO::FETCH_ASSOC);
        return $r?:[];
    }
}
