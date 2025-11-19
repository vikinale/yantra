<?php
namespace Models;
use System\Database\Model as BaseModel;

class PostModel extends BaseModel
{
    protected $table = 'yt_posts';
    protected $primaryKey = 'post_id';

    public function __construct(){ parent::__construct($this->table, $this->primaryKey); }

    public function getById(int $id): ?array { $r = $this->get($id); return $r===false?null:(array)$r; }

    public function getBySlug(string $slug): ?array { $r = $this->query()->where('post_slug','=',trim($slug))->getResult(\PDO::FETCH_ASSOC); return $r?:null; }

    public function createPost(array $data): int { return (int)$this->insert($data); }

    public function updatePost(int $id, array $data): int { return $this->query('update')->where('post_id','=',$id)->data($data)->executeQuery()->rowCount(); }

    public function deletePost(int $id): int { return $this->delete($id); }
}
