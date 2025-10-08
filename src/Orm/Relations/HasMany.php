<?php

namespace App\Orm\Relations;

use App\Orm\Model;
use App\Orm\QueryBuilder;

class HasMany extends Relation
{
    protected string $localKey;

    public function __construct(Model $parent, Model $related, string $foreignKey, string $localKey = 'id')
    {
        $this->localKey = $localKey;
        parent::__construct($parent, $related, $foreignKey);
    }

    public function getResults()
    {
        return $this->getRelationQuery()->get();
    }
    
    public function getRelationQuery(): QueryBuilder
    {
        $query = $this->related->query();
        
        return $query->where(
            $this->foreignKey,
            '=',
            $this->parent->{$this->localKey}
        );
    }
    
    public function getForeignKey(): string
    {
        return $this->foreignKey;
    }
}
