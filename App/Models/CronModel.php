<?php
namespace Models;
use System\Database\Model as BaseModel;

class CronModel extends BaseModel
{
    protected $table = 'yt_cron';
    protected $primaryKey = 'id';

    public function __construct(){ parent::__construct($this->table, $this->primaryKey); }

    public function schedule(string $name, string $hook, $payload = null, ?string $schedule_at = null): int {
        return (int)$this->insert(['name'=>$name,'hook'=>$hook,'payload'=>$payload?json_encode($payload):null,'schedule_at'=>$schedule_at]);
    }

    public function getDue(int $limit = 50): array {
        $now = date('Y-m-d H:i:s');
        $r = $this->query()->where('schedule_at','<=',$now)->where('status','=','pending')->limit($limit)->executeQuery()->fetchAll(\PDO::FETCH_ASSOC);
        return $r?:[];
    }

    public function markDone(int $id): int { return $this->query('update')->where('id','=',$id)->data(['status'=>'done'])->executeQuery()->rowCount(); }
}
