<?php
namespace Models;
use Exception;
use System\Database\Model as BaseModel;

class OptionModel extends BaseModel
{
    protected $table = 'yt_options';
    protected $primaryKey = 'op_id';

    public function __construct(){ parent::__construct($this->table, $this->primaryKey); }

    public function getOption(string $key, string $level='global', int $scope_id=0, $default=null) {
        $r = $this->query()->where('op_key','=',trim($key))->where('level','=',trim($level))->where('scope_id','=',$scope_id)->getResult(\PDO::FETCH_ASSOC);
        return $r ? $r['op_value'] : $default;
    }

    public function setOption(string $key, $value, string $level='global', int $scope_id=0, bool $autoload=false): int {
        $data = ['op_key'=>trim($key),'op_value'=>$value,'level'=>trim($level),'scope_id'=>$scope_id,'autoload'=>$autoload?1:0];
        return $this->save($data, ['op_value','autoload','updated_at']);
    }

    public function deleteOption(string $key, string $level='global', int $scope_id=0): int {
        return $this->query('delete')->where('op_key','=',trim($key))->where('level','=',trim($level))->where('scope_id','=',$scope_id)->executeQuery()->rowCount();
    }

    public function getAutoloadList(): array {
        $res = $this->query()->where('autoload','=',1)->executeQuery()->fetchAll(\PDO::FETCH_ASSOC);
        return $res?:[];
    }
}
