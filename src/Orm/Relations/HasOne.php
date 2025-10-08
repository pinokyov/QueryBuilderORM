<?php

namespace App\Orm\Relations;

use App\Orm\Model;

class HasOne extends Relation
{
    public function getResults()
    {
        return $this->getRelationQuery()->first();
    }
}
