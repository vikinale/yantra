<?php
namespace Models;
use System\Database\Model as BaseModel;

class ActivityLogModel extends BaseModel
{
    protected $table = 'yt_activity_log';
    protected $primaryKey = 'log_id';

    public function __construct(){ parent::__construct($this->table, $this->primaryKey); }

    public function log(?int $admin_id, string $action, ?string $target_type = null, ?int $target_id = null, $meta = null): int {
        $data = ['admin_id'=>$admin_id,'action'=>$action,'target_type'=>$target_type,'target_id'=>$target_id,'meta'=>$meta?json_encode($meta):null];
        return (int)$this->insert($data);
    }

    public function getRecentForAdmin(int $admin_id, int $limit = 50): array {
        $r = $this->query()->where('admin_id','=',$admin_id)->orderBy('created_at','DESC')->limit($limit)->executeQuery()->fetchAll(\PDO::FETCH_ASSOC);
        return $r?:[];
    }
}
