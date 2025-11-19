<?php
namespace Models;
use System\Database\Model as BaseModel;

class CommentModel extends BaseModel
{
    protected $table = 'yt_comments';
    protected $primaryKey = 'comment_id';

    public function __construct(){ parent::__construct($this->table, $this->primaryKey); }

    public function createComment(array $data): int { return (int)$this->insert($data); }

    public function getByPost(int $post_id, int $limit = 100): array {
        $r = $this->query()->where('post_id','=',$post_id)->orderBy('created_at','DESC')->limit($limit)->executeQuery()->fetchAll(\PDO::FETCH_ASSOC);
        return $r?:[];
    }

    public function deleteComment(int $id): int { return $this->delete($id); }
}
