<?php
namespace Models;
use System\Database\Model as BaseModel;

class MediaModel extends BaseModel
{
    protected $table = 'yt_media';
    protected $primaryKey = 'media_id';

    public function __construct(){ parent::__construct($this->table, $this->primaryKey); }

    public function createMedia(array $data): int { return (int)$this->insert($data); }

    public function getById(int $id): ?array { $r = $this->get($id); return $r===false?null:(array)$r; }

    public function deleteMedia(int $id): int { return $this->delete($id); }
}
