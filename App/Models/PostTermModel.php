<?php
namespace Models;
use System\Database\Model as BaseModel;

class PostTermModel extends BaseModel
{
    protected $table = 'yt_post_terms';
    protected $primaryKey = 'id';

    public function __construct(){ parent::__construct($this->table, $this->primaryKey); }

    public function attach(int $post_id, int $term_id): int { return (int)$this->insert(['post_id'=>$post_id,'term_id'=>$term_id]); }

    public function detach(int $post_id, int $term_id): int { return $this->query('delete')->where('post_id','=',$post_id)->where('term_id','=',$term_id)->executeQuery()->rowCount(); }

    public function getTermsForPost(int $post_id): array { $r = $this->query()->where('post_id','=',$post_id)->executeQuery()->fetchAll(\PDO::FETCH_ASSOC); return $r?:[]; }
}
