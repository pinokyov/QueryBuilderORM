<?php

namespace App\Orm\Relations;

use App\Orm\Model;
use App\Orm\QueryBuilder;

class BelongsTo extends Relation
{
    protected string $ownerKey = 'id';
    protected ?string $relationName = null;

    public function __construct(Model $parent, Model $related, string $foreignKey, string $ownerKey = 'id', string $relationName = null)
    {
        $this->ownerKey = $ownerKey;
        $this->relationName = $relationName;
        
        parent::__construct($parent, $related, $foreignKey);
    }

    public function getResults()
    {
        $value = $this->parent->{$this->foreignKey};
        
        if (is_null($value)) {
            return null;
        }
        
        return $this->related
            ->query()
            ->where($this->ownerKey, '=', $value)
            ->first();
    }
    
    public function getRelationName(): string
    {
        return $this->relationName ?: $this->foreignKey;
    }
}
