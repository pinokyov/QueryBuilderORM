<?php

namespace App\Orm\Relations;

use App\Orm\Model;
use App\Orm\QueryBuilder;

abstract class Relation
{
    protected Model $parent;
    protected Model $related;
    protected string $foreignKey;
    protected string $localKey;

    public function __construct(Model $parent, Model $related, string $foreignKey, string $localKey = 'id')
    {
        $this->parent = $parent;
        $this->related = $related;
        $this->foreignKey = $foreignKey;
        $this->localKey = $localKey;
    }

    abstract public function getResults();

    protected function getRelationQuery(): QueryBuilder
    {
        return $this->related->query()->where(
            $this->foreignKey, 
            '=', 
            $this->parent->{$this->localKey}
        );
    }

    public function getRelated(): Model
    {
        return $this->related;
    }
}
